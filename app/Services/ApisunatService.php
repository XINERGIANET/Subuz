<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integración Apisunat (SUNAT vía https://back.apisunat.com).
 * Credenciales y series desde .env / config('apisunat.*') únicamente.
 * Flujo: lastDocument → sendBill (JSON explícito) → getById (xml/cdr).
 */
class ApisunatService
{
    private const IGV = 0.18;

    public function emitInvoice(Invoice $invoice): array
    {
        $invoice->load(['client', 'sales.details.product', 'details.product']);

        if (!filter_var(config('apisunat.enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            return ['status' => 'SKIPPED', 'message' => 'Facturación electrónica desactivada (APISUNAT_ENABLED).'];
        }

        $extId = trim((string) ($invoice->electronic_invoice_external_id ?? ''));
        $st = strtoupper((string) ($invoice->electronic_invoice_status ?? ''));
        if ($extId !== '' && $st === 'SENT') {
            return [
                'status' => 'SENT',
                'message' => 'Comprobante ya registrado en Apisunat (idempotente).',
                'documentId' => $extId,
            ];
        }

        $apiUrl = rtrim(trim((string) config('apisunat.url', 'https://back.apisunat.com')), '/');
        $personaId = trim((string) config('apisunat.id'));
        $personaToken = trim((string) config('apisunat.token.prod'));
        if ($personaId === '' || $personaToken === '') {
            return ['status' => 'ERROR', 'message' => 'APISUNAT: falta APISUNAT_ID o APISUNAT_TOKEN_PROD en .env. Ejecute limpieza de caché de configuración si ya los definió.'];
        }

        $client = $invoice->client;
        $dt = strtolower(trim((string) ($invoice->document_type ?? '')));
        if ($dt === 'boleta') {
            $isFactura = false;
        } elseif ($dt === 'factura') {
            $isFactura = true;
        } else {
            $isFactura = strlen(preg_replace('/\D/', '', (string) ($client->document ?? ''))) === 11;
        }
        $docType = $isFactura ? '01' : '03';
        $serie = $this->normalizeSerie($isFactura ? (string) config('apisunat.series.factura', 'F001') : (string) config('apisunat.series.boleta', 'B001'));
        if (strlen($serie) !== 4) {
            return ['status' => 'ERROR', 'message' => 'Serie debe tener 4 caracteres (APISUNAT_SERIES_*).'];
        }

        if ($isFactura) {
            $rucCli = preg_replace('/\D/', '', (string) ($client->document ?? ''));
            if (strlen($rucCli) !== 11) {
                return ['status' => 'ERROR', 'message' => 'Factura: el cliente debe tener RUC de 11 dígitos.'];
            }
        }

        $cliDigits = preg_replace('/\D/', '', (string) ($client->document ?? ''));
        if (!$isFactura && strlen($cliDigits) === 11) {
            return [
                'status' => 'ERROR',
                'message' => 'Boleta electrónica (tipo SUNAT 03) no puede emitirse a un cliente con RUC de 11 dígitos. SUNAT exige Factura (01) entre contribuyentes; Apisunat suele responder error vacío en este caso.',
                'fileName' => '',
                'debug_file' => null,
                'hint' => 'En SUBUZ cambie este comprobante a Factura o use un cliente con DNI (8 dígitos). Luego reenvíe o emita de nuevo.',
            ];
        }

        $last = $this->jsonPost($apiUrl . '/personas/lastDocument', [
            'personaId' => $personaId,
            'personaToken' => $personaToken,
            'type' => $docType,
            'serie' => $serie,
        ], 25);
        if ($last->failed()) {
            $this->persistInvoiceError($invoice, 'Error lastDocument: ' . $last->body(), $last->json(), []);

            return ['status' => 'ERROR', 'message' => 'Error al obtener correlativo: ' . $last->body()];
        }
        $lastJson = $last->json();
        $suggested = is_array($lastJson) ? ($lastJson['suggestedNumber'] ?? null) : null;
        if ($suggested === null || $suggested === '') {
            $this->persistInvoiceError($invoice, 'Correlativo inválido en Apisunat.', $lastJson, ['lastDocument' => $lastJson]);

            return ['status' => 'ERROR', 'message' => 'Correlativo inválido en respuesta Apisunat: ' . $last->body()];
        }

        $ruc = $this->resolveEmisorRuc();
        if ($ruc === null) {
            return ['status' => 'ERROR', 'message' => 'COMPANY_RUC: 11 dígitos del emisor en .env.'];
        }

        $corr8 = $this->normalizeCorrelativo($suggested);
        $fileName = sprintf('%s-%s-%s-%s', $ruc, $docType, $serie, $corr8);

        $documentBody = $this->buildDocumentBody($invoice, $docType, $serie, $corr8, $ruc);
        if (empty($documentBody['cac:InvoiceLine'])) {
            return ['status' => 'ERROR', 'message' => 'El comprobante no tiene líneas de detalle.'];
        }

        $sendBillVariants = $this->buildSendBillDocumentBodyVariants($docType, $documentBody);
        $sendBillUrls = $this->resolveSendBillEndpoints($apiUrl);

        $this->delayBeforeApisunatSendBill();

        $send = null;
        $sendBody = '';
        $sendJson = null;
        $usedVariant = 'flat';
        $usedSendBillUrl = $sendBillUrls[0] ?? ($apiUrl . '/personas/v1/sendBill');
        $postedDocumentBody = $documentBody;
        $documentBodyEncoding = 'object';
        $lastSendBillDocumentBody = null;

        foreach ($sendBillUrls as $urlIndex => $sendBillUrl) {
            foreach ($sendBillVariants as $variantKey => $docPayload) {
                $encodings = [['suffix' => '', 'stringify' => false]];
                if ($urlIndex === 0 && $variantKey === 'flat_minimal') {
                    $encodings[] = ['suffix' => '_docbody_json_string', 'stringify' => true];
                }
                foreach ($encodings as $enc) {
                    $usedVariant = (string) $variantKey . $enc['suffix'];
                    $usedSendBillUrl = $sendBillUrl;
                    $postedDocumentBody = $docPayload;
                    $documentBodyEncoding = $enc['stringify'] ? 'json_string' : 'object';

                    $bodyField = $docPayload;
                    if ($enc['stringify']) {
                        $encoded = json_encode($docPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $bodyField = $encoded !== false ? $encoded : $docPayload;
                    }

                    $lastSendBillDocumentBody = $bodyField;

                    $send = $this->jsonPost($sendBillUrl, $this->buildSendBillRequestBody($invoice, $personaId, $personaToken, $fileName, $bodyField), 18);
                    $sendBody = $send->body();
                    $sendJson = $send->json();
                    if ($send->ok() && is_array($sendJson) && ($sendJson['status'] ?? '') === 'PENDIENTE') {
                        goto apisunat_sendbill_ok;
                    }
                }
            }
        }
        apisunat_sendbill_ok:

        if ($send === null) {
            return ['status' => 'ERROR', 'message' => 'No se pudo contactar a Apisunat (sendBill).'];
        }

        if ($send->failed()) {
            $debugFile = $this->writeApisunatDebugPayload($invoice->id, $fileName, $postedDocumentBody, $sendJson, $sendBody, $lastJson, $usedVariant, $send->status(), array_merge($this->apisunatDebugMeta($invoice, $docType, $serie, $client), [
                'sendBill_url' => $usedSendBillUrl,
                'documentBody_encoding' => $documentBodyEncoding,
            ]));
            Log::error('Apisunat sendBill HTTP error', [
                'invoice_id' => $invoice->id,
                'fileName' => $fileName,
                'variant' => $usedVariant,
                'sendBill_url' => $usedSendBillUrl,
                'debug_file' => $debugFile,
                'body' => mb_substr($sendBody, 0, 8000),
            ]);
            $this->persistInvoiceError($invoice, 'Error sendBill HTTP: ' . $sendBody, $sendJson, [
                'fileName' => $fileName,
                'debug_disk' => $debugFile,
                'lastDocument' => $lastJson,
                'sendBill_variant' => $usedVariant,
                'sendBill_url' => $usedSendBillUrl,
                'documentBody_posted' => $postedDocumentBody,
            ]);

            $msgHttp = is_array($sendJson)
                ? $this->formatApisunatError($sendJson, $sendBody)
                : ('Error al enviar comprobante: ' . $sendBody);

            return $this->apisunatErrorResult($msgHttp, $fileName, $debugFile, $invoice->id, array_merge(
                $this->apisunatErrorExtras($send, $sendJson, $sendBody, $usedVariant, $usedSendBillUrl),
                $this->apisunatEmitFailureDebugPackage($invoice, $apiUrl, $personaId, $personaToken, $ruc, $docType, $serie, $corr8, $fileName, $lastJson, $postedDocumentBody, $documentBodyEncoding, $usedSendBillUrl, $lastSendBillDocumentBody)
            ));
        }
        if (!is_array($sendJson) || ($sendJson['status'] ?? '') !== 'PENDIENTE') {
            $msg = $this->formatApisunatError($sendJson, $sendBody);
            $debugFile = $this->writeApisunatDebugPayload($invoice->id, $fileName, $postedDocumentBody, $sendJson, $sendBody, $lastJson, $usedVariant, $send->status(), array_merge($this->apisunatDebugMeta($invoice, $docType, $serie, $client), [
                'sendBill_url' => $usedSendBillUrl,
                'documentBody_encoding' => $documentBodyEncoding,
            ]));
            Log::error('Apisunat sendBill rechazado', [
                'invoice_id' => $invoice->id,
                'fileName' => $fileName,
                'variant' => $usedVariant,
                'sendBill_url' => $usedSendBillUrl,
                'debug_file' => $debugFile,
                'apisunat_message' => $msg,
            ]);
            $this->persistInvoiceError($invoice, $msg, $sendJson, [
                'fileName' => $fileName,
                'debug_disk' => $debugFile,
                'lastDocument' => $lastJson,
                'sendBill_variant' => $usedVariant,
                'sendBill_url' => $usedSendBillUrl,
                'documentBody_posted' => $postedDocumentBody,
            ]);

            return $this->apisunatErrorResult($msg, $fileName, $debugFile, $invoice->id, array_merge(
                $this->apisunatErrorExtras($send, $sendJson, $sendBody, $usedVariant, $usedSendBillUrl),
                $this->apisunatEmitFailureDebugPackage($invoice, $apiUrl, $personaId, $personaToken, $ruc, $docType, $serie, $corr8, $fileName, $lastJson, $postedDocumentBody, $documentBodyEncoding, $usedSendBillUrl, $lastSendBillDocumentBody)
            ));
        }

        $documentId = isset($sendJson['documentId']) ? trim((string) $sendJson['documentId']) : '';
        if ($documentId === '') {
            $this->persistInvoiceError($invoice, 'Respuesta sin documentId.', $sendJson, [
                'fileName' => $fileName,
                'lastDocument' => $lastJson,
            ]);

            return ['status' => 'ERROR', 'message' => 'Respuesta Apisunat sin documentId.'];
        }

        $urls = $this->resolveDocumentUrls($apiUrl, $documentId, $fileName);

        $getBy = Http::timeout(25)
            ->withHeaders(['Accept' => 'application/json'])
            ->get($apiUrl . '/documents/' . rawurlencode($documentId) . '/getById');
        if ($getBy->ok()) {
            $doc = $getBy->json();
            if (is_array($doc)) {
                if (!empty($doc['xml']) && is_string($doc['xml'])) {
                    $urls['electronic_invoice_xml_url'] = $doc['xml'];
                }
                if (!empty($doc['cdr']) && is_string($doc['cdr'])) {
                    $urls['electronic_invoice_cdr_url'] = $doc['cdr'];
                }
            }
        }

        $invoice->update(array_merge([
            'electronic_invoice_provider' => 'apisunat',
            'electronic_invoice_status' => 'SENT',
            'electronic_invoice_external_id' => $documentId,
            'electronic_invoice_series' => $serie,
            'electronic_invoice_number' => (int) ltrim($corr8, '0') ?: (int) $corr8,
            'electronic_invoice_file_name' => $fileName,
            'electronic_invoice_response' => [
                'sendBill' => $sendJson,
                'sendBill_variant' => $usedVariant,
                'sendBill_url' => $usedSendBillUrl,
                'getById' => $getBy->ok() ? $getBy->json() : null,
            ],
        ], $urls));

        return ['status' => 'SENT', 'documentId' => $documentId];
    }

    protected function resolveDocumentUrls(string $apiUrl, string $documentId, string $fileName): array
    {
        $fnPdf = $fileName . '.pdf';

        return [
            'electronic_invoice_pdf_a4_url' => "{$apiUrl}/documents/{$documentId}/getPDF/A4/{$fnPdf}",
            'electronic_invoice_pdf_ticket_url' => "{$apiUrl}/documents/{$documentId}/getPDF/ticket80mm/{$fnPdf}",
            'electronic_invoice_xml_url' => "{$apiUrl}/documents/{$documentId}/getXML",
            'electronic_invoice_cdr_url' => "{$apiUrl}/documents/{$documentId}/getCDR",
        ];
    }

    protected function jsonPost(string $url, array $body, int $timeout)
    {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return Http::timeout($timeout)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->withBody($json !== false ? $json : '{}', 'application/json')
            ->post($url);
    }

    /** URL sendBill: solo /personas/v1/sendBill. /api-rest/personas/… devuelve 404 en back.apisunat.com. */
    protected function resolveSendBillEndpoints(string $apiUrl): array
    {
        $b = rtrim(trim((string) $apiUrl), '/');

        return [$b . '/personas/v1/sendBill'];
    }

    /** Pausa opcional antes del primer sendBill (config apisunat.delay_ms_before_send_bill). */
    private function delayBeforeApisunatSendBill(): void
    {
        $ms = (int) config('apisunat.delay_ms_before_send_bill', 0);
        if ($ms < 1) {
            return;
        }
        if ($ms > 60000) {
            $ms = 60000;
        }
        usleep($ms * 1000);
    }

    /** Cuerpo POST sendBill; documentBody puede ser array (UBL) o string JSON según intento. */
    protected function buildSendBillRequestBody(Invoice $invoice, string $personaId, string $personaToken, string $fileName, $documentBody): array
    {
        $payload = [
            'personaId' => $personaId,
            'personaToken' => $personaToken,
            'fileName' => $fileName,
            'documentBody' => $documentBody,
        ];
        $email = trim((string) ($invoice->client->email ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $payload['customerEmail'] = $email;
        }

        return $payload;
    }

    protected function persistInvoiceError(Invoice $invoice, string $message, $payload, array $extra = []): void
    {
        try {
            $invoice->update([
                'electronic_invoice_provider' => 'apisunat',
                'electronic_invoice_status' => 'ERROR',
                'electronic_invoice_response' => array_merge([
                    'message' => $message,
                    'payload' => $payload,
                    'captured_at' => now()->toIso8601String(),
                ], $extra),
            ]);
        } catch (\Throwable $e) {
            // no-op
        }
    }

    /**
     * Guarda JSON enviado + respuesta Apisunat en disco (para soporte / comparación con el generador del portal).
     *
     * @return string|null nombre del archivo creado
     */
    /**
     * Metadatos para depuración (no se envían a Apisunat).
     */
    protected function apisunatDebugMeta(Invoice $invoice, string $docTypeSunat, string $serie, $client): array
    {
        $digits = preg_replace('/\D/', '', (string) ($client->document ?? ''));

        return [
            'invoice_id' => $invoice->id,
            'invoice_document_type' => $invoice->document_type,
            'sunat_type_code' => $docTypeSunat,
            'serie' => $serie,
            'client_id' => $client->id ?? null,
            'client_document_digits_length' => strlen($digits),
            'client_is_ruc_11' => strlen($digits) === 11,
            'docs_apisunat' => 'https://docs.apisunat.com/api-rest/personas/documentbody',
            'causas_tipicas_error_vacio' => [
                'Serie (ej. B002) no habilitada en Apisunat o en ambiente producción.',
                'Correlativo ya usado o duplicado para ese RUC + tipo + serie.',
                'Boleta (03) con cliente RUC: SUNAT exige factura (01); bloqueado también en emitInvoice.',
                'Token/personaId incorrectos o plan vencido (revisar panel Apisunat).',
            ],
        ];
    }

    protected function writeApisunatDebugPayload(int $invoiceId, string $fileName, array $documentBody, $sendJson, string $sendBody, $lastDocument, ?string $sendBillVariant = null, ?int $httpStatus = null, array $meta = []): ?string
    {
        try {
            $dir = storage_path('app/apisunat-debug');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $basename = $invoiceId . '_' . date('Ymd_His') . '.json';
            $path = $dir . DIRECTORY_SEPARATOR . $basename;
            $dump = [
                'fileName' => $fileName,
                'sendBill_variant' => $sendBillVariant,
                'sendBill_http_status' => $httpStatus,
                'lastDocument' => $lastDocument,
                'apisunat_response' => $sendJson,
                'apisunat_response_raw' => $sendBody,
                'raw_body_length' => strlen($sendBody),
                'diagnostics' => $meta,
                'documentBody' => $documentBody,
            ];
            file_put_contents($path, json_encode($dump, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return $basename;
        } catch (\Throwable $e) {
            Log::warning('No se pudo escribir apisunat-debug', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function apisunatErrorResult(string $message, string $fileName, ?string $debugBasename, int $invoiceId, array $extra = []): array
    {
        $diskHint = $debugBasename
            ? 'storage/app/apisunat-debug/' . $debugBasename
            : '(no se pudo guardar archivo de depuración; revise permisos de storage/app/)';

        return array_merge([
            'status' => 'ERROR',
            'message' => $message,
            'fileName' => $fileName,
            'debug_file' => $debugBasename,
            'hint' => 'Apisunat falló al validar el envío. (1) Archivo: ' . $diskHint . ' — campo diagnostics y sendBill_http_status. (2) Log: storage/logs/laravel.log "Apisunat sendBill". (3) Portal Apisunat: serie/correlativo habilitados para ' . $fileName . '. (4) Boleta con RUC: el sistema ya bloquea; si el error fue antes del cambio, use Factura. id=' . $invoiceId . '. (5) Si sendBill responde 400/TypeError, Apisunat no registra el comprobante: la lista Ventas puede quedar vacía hasta que responda status PENDIENTE.',
        ], $extra);
    }

    /**
     * Datos extra para la respuesta JSON (modal de reenvío) y diagnóstico local cuando Apisunat solo devuelve TypeError.
     *
     * @param  \Illuminate\Http\Client\Response|null  $send
     */
    protected function apisunatErrorExtras($send, $sendJson, string $sendBody, string $usedVariant, string $sendBillUrl = ''): array
    {
        $status = $send !== null ? $send->status() : null;
        $apisunatResponse = is_array($sendJson) ? $sendJson : null;
        $local = '';
        $escalation = '';
        if (is_array($sendJson)) {
            $errMsg = '';
            $e = $sendJson['error'] ?? null;
            if (is_array($e) && isset($e['message'])) {
                $errMsg = (string) $e['message'];
            }
            if ($errMsg !== '' && stripos($errMsg, '_text') !== false) {
                $local = 'SUBUZ ya probó: variante portal-like (_text/_attributes, Note vacía, PaymentTerms+PaymentMeans para SUNAT 3244), variantes solo-_text, PaymentMeans como bloque en lista, documentBody como string (flat_minimal), customerEmail si hay correo, y el resto de variantes xml2js en POST .../personas/v1/sendBill. Si el TypeError _text sigue, el fallo está en el validador Node de Apisunat (el documento no se crea en el portal hasta que sendBill responda PENDIENTE).';
                $escalation = 'Escalación Apisunat: envíe a soporte@apisunat.com o WhatsApp del panel el archivo apisunat-debug, el URL usado (send_bill_url), la variante (sendBill_variant), y el texto literal: "sendBill devuelve TypeError reading _text con documentBody generado por su generador { } del portal". Pida corrección del endpoint /personas/v1/sendBill o revisión de su cuenta en producción.';
            }
        }

        return [
            'apisunat_response' => $apisunatResponse,
            'apisunat_response_raw' => $sendBody,
            'sendBill_http_status' => $status,
            'sendBill_variant' => $usedVariant,
            'send_bill_url' => $sendBillUrl !== '' ? $sendBillUrl : null,
            'local_diagnosis' => $local,
            'escalation_apisunat' => $escalation,
        ];
    }

    /**
     * Datos de depuración para la respuesta JSON del reenvío (token nunca en claro).
     *
     * @param  array|string|null  $postedDocumentBody
     * @param  array|string|null  $sendBillDocumentBodyField
     */
    protected function apisunatEmitFailureDebugPackage(
        Invoice $invoice,
        string $apiUrl,
        string $personaId,
        string $personaToken,
        string $rucEmisor,
        string $docTypeSunat,
        string $serie,
        string $corr8,
        string $fileName,
        $lastJson,
        $postedDocumentBody,
        string $documentBodyEncoding,
        string $sendBillUrl,
        $sendBillDocumentBodyField
    ): array {
        $base = rtrim(trim((string) $apiUrl), '/');
        $docField = $sendBillDocumentBodyField !== null ? $sendBillDocumentBodyField : $postedDocumentBody;
        $sendReq = $this->buildSendBillRequestBody($invoice, $personaId, $personaToken, $fileName, $docField);
        $sendReq['personaToken'] = $this->maskApisunatSecret($personaToken);

        $lastReq = [
            'personaId' => $personaId,
            'personaToken' => $this->maskApisunatSecret($personaToken),
            'type' => $docTypeSunat,
            'serie' => $serie,
        ];

        $postedEnc = json_encode($postedDocumentBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $postedBytes = is_string($postedEnc) ? strlen($postedEnc) : 0;

        $client = $invoice->client;

        return [
            'subuz_diagnostics' => $this->apisunatDebugMeta($invoice, $docTypeSunat, $serie, $client),
            'apisunat_api_base_url' => $base,
            'apisunat_last_document_post_url' => $base . '/personas/lastDocument',
            'apisunat_send_bill_post_url' => $sendBillUrl,
            'apisunat_enabled' => filter_var(config('apisunat.enabled', true), FILTER_VALIDATE_BOOLEAN),
            'apisunat_delay_ms_before_send_bill' => (int) config('apisunat.delay_ms_before_send_bill', 0),
            'persona_id' => $personaId,
            'persona_token_preview' => $this->maskApisunatSecret($personaToken),
            'persona_token_length' => strlen($personaToken),
            'emisor_ruc' => $rucEmisor,
            'emisor_legal_name' => $this->txt(config('apisunat.company.legal_name'), ''),
            'sunat_type_code' => $docTypeSunat,
            'serie' => $serie,
            'correlativo_8' => $corr8,
            'file_name_sunat' => $fileName,
            'last_document_request_redacted' => $lastReq,
            'last_document_response' => is_array($lastJson) ? $lastJson : $lastJson,
            'send_bill_request_redacted' => $sendReq,
            'document_body_encoding' => $documentBodyEncoding,
            'document_body_posted_is_string' => is_string($docField),
            'document_body_posted' => $postedDocumentBody,
            'document_body_posted_json_bytes' => $postedBytes,
            'invoice' => [
                'id' => $invoice->id,
                'date' => $invoice->date ? $invoice->date->format('Y-m-d') : null,
                'document_type' => $invoice->document_type,
            ],
            'client' => [
                'id' => $client->id ?? null,
                'document' => $client->document ?? null,
                'business_name' => $client->business_name ?? null,
                'name' => $client->name ?? null,
                'email' => $client->email ?? null,
            ],
        ];
    }

    /** Vista del token para JSON (no exponer secreto completo). */
    private function maskApisunatSecret(string $secret): string
    {
        $len = strlen($secret);
        if ($len === 0) {
            return '(vacío)';
        }
        if ($len <= 6) {
            return '*** (len=' . $len . ')';
        }

        return substr($secret, 0, 3) . '…' . substr($secret, -4) . ' (len=' . $len . ')';
    }

    protected function formatApisunatError($sendJson, string $rawBody): string
    {
        if (is_array($sendJson)) {
            $err = $sendJson['error'] ?? null;
            if (is_array($err) && isset($err['message'])) {
                return 'Apisunat: ' . (string) $err['message'];
            }
            if (is_object($err) && isset($err->message)) {
                return 'Apisunat: ' . (string) $err->message;
            }
            if (is_string($err) && $err !== '') {
                return 'Apisunat: ' . $err;
            }
            $errFlat = is_array($err) ? $err : (is_object($err) ? (array) $err : null);
            if ($err !== null && $err !== '' && is_array($errFlat) && $errFlat === []) {
                return 'Apisunat: rechazo sin detalle (error vacío). Revise en el portal: serie habilitada, correlativo no duplicado, token/persona. Si era boleta con cliente RUC, use factura o DNI. Compare documentBody del archivo apisunat-debug con el botón { } del portal.';
            }
            if (is_array($errFlat) && $errFlat !== []) {
                $enc = json_encode($errFlat, JSON_UNESCAPED_UNICODE);
                if ($enc !== false && $enc !== '{}' && $enc !== '[]') {
                    return 'Apisunat: ' . $enc;
                }
            }
        }

        return 'Error al enviar comprobante: ' . $rawBody;
    }

    protected function resolveEmisorRuc(): ?string
    {
        $candidates = [];
        $c = config('apisunat.company.ruc');
        if ($c !== null && $c !== '') {
            $candidates[] = (string) $c;
        }
        $fromFile = $this->readCompanyRucFromDotEnv();
        if ($fromFile !== null && $fromFile !== '') {
            $candidates[] = $fromFile;
        }
        foreach ($candidates as $raw) {
            $n = $this->only11DigitsRuc($raw);
            if ($n !== null) {
                return $n;
            }
        }

        return null;
    }

    protected function readCompanyRucFromDotEnv(): ?string
    {
        $path = base_path('.env');
        if (!is_readable($path)) {
            return null;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return null;
        }
        foreach ($lines as $line) {
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            $t = ltrim($line);
            if ($t === '' || $t[0] === '#') {
                continue;
            }
            if (!preg_match('/^\s*COMPANY_RUC\s*=\s*(.*)$/', $line, $m)) {
                continue;
            }
            $v = trim($m[1]);
            if ($v !== '' && strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                $v = substr($v, 1, -1);
            }
            $p = strpos($v, ' #');
            if ($p !== false) {
                $v = trim(substr($v, 0, $p));
            }
            $v = trim($v, " \t\n\r\"'");

            return $v !== '' ? $v : null;
        }

        return null;
    }

    protected function only11DigitsRuc(?string $ruc): ?string
    {
        if ($ruc === null) {
            return null;
        }
        $ruc = trim($ruc);
        if ($ruc === '') {
            return null;
        }
        $ruc = preg_replace('/^\xEF\xBB\xBF/', '', $ruc);
        $ruc = trim($ruc, " \t\n\r\0\x0B\"'`");
        $d = preg_replace('/\D/', '', $ruc);

        return strlen($d) === 11 ? $d : null;
    }

    protected function normalizeSerie(string $serie): string
    {
        return substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', $serie)), 0, 4);
    }

    protected function normalizeCorrelativo($n): string
    {
        $d = preg_replace('/\D/', '', (string) $n);
        if ($d === '') {
            $d = '0';
        }
        if (strlen($d) > 8) {
            $d = substr($d, -8);
        }

        return str_pad($d, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Variantes sendBill solo en formato xml2js (_/_text/$). Las variantes #text/@_ hacían que Apisunat
     * siguiera leyendo ._text y fallara siempre.
     */
    protected function buildSendBillDocumentBodyVariants(string $docType, array $documentBody): array
    {
        $portalLike = $this->buildPortalLikeDocumentBodyVariant($documentBody);
        $strip = $this->stripUblDuplicateText($documentBody);
        $linesArray = $this->forceInvoiceLineAsArray($documentBody);
        $dropUnderscore = $this->stripUblDropUnderscoreWhenTextPresent($documentBody);
        $pmBlocks = $this->documentBodyPaymentMeansAsBlocks($documentBody);
        $minimal = $this->documentBodyCloneWithoutKeys($documentBody, ['cac:PaymentMeans', 'cac:PaymentTerms']);
        $minimalNoStax = $this->documentBodyWithoutSupplierPartyTaxScheme($minimal);

        $variants = [];
        $variants['flat_portal_like'] = $portalLike;
        if ($docType !== '03') {
            $variants['invoice_root_portal_like'] = ['Invoice' => $portalLike];
        }
        $variants['flat_drop_underscore'] = $dropUnderscore;
        $variants['flat_pm_block_array'] = $pmBlocks;
        if ($docType !== '03') {
            $variants['invoice_root_drop_underscore'] = ['Invoice' => $dropUnderscore];
            $variants['invoice_root_pm_block_array'] = ['Invoice' => $pmBlocks];
        }
        $variants['flat_minimal'] = $minimal;
        $variants['flat_minimal_no_supplier_taxscheme'] = $minimalNoStax;
        $variants['flat'] = $documentBody;
        if ($docType !== '03') {
            $variants['invoice_root_minimal'] = ['Invoice' => $minimal];
            $variants['invoice_root'] = ['Invoice' => $documentBody];
        }
        $variants['flat_strip_dup_text'] = $strip;
        $variants['flat_lines_array'] = $linesArray;
        if ($docType !== '03') {
            $variants['invoice_root_strip'] = ['Invoice' => $strip];
            $variants['invoice_root_lines_array'] = ['Invoice' => $linesArray];
        }

        return $variants;
    }

    /**
     * Forma compatible con el generador del portal Apisunat:
     * - usa _text y _attributes (sin _ / $)
     * - elimina nodos opcionales que suelen romper su validador Node.
     */
    protected function buildPortalLikeDocumentBodyVariant(array $documentBody): array
    {
        $body = $this->convertUblNodeToPortalShape($documentBody);

        // Nota vacía estilo portal; se conservan PaymentTerms (SUNAT 3244) y PaymentMeans (catálogo 59).
        $body['cbc:Note'] = [];

        // Campos opcionales que no son necesarios para validar sendBill.
        unset($body['cbc:ProfileID'], $body['cbc:UUID'], $body['cbc:LineCountNumeric']);

        // Cabecera: en ejemplos del portal TaxSubtotal va como objeto único.
        if (isset($body['cac:TaxTotal']['cac:TaxSubtotal']) && is_array($body['cac:TaxTotal']['cac:TaxSubtotal']) && array_is_list($body['cac:TaxTotal']['cac:TaxSubtotal'])) {
            $body['cac:TaxTotal']['cac:TaxSubtotal'] = $body['cac:TaxTotal']['cac:TaxSubtotal'][0] ?? [];
        }

        // Línea: AlternativeConditionPrice suele ir como objeto (no arreglo).
        $lines = $body['cac:InvoiceLine'] ?? null;
        if ($lines !== null && is_array($lines)) {
            $lineList = array_is_list($lines) ? $lines : [$lines];
            foreach ($lineList as &$line) {
                if (!is_array($line)) {
                    continue;
                }
                if (isset($line['cac:PricingReference']['cac:AlternativeConditionPrice']) && is_array($line['cac:PricingReference']['cac:AlternativeConditionPrice']) && array_is_list($line['cac:PricingReference']['cac:AlternativeConditionPrice'])) {
                    $line['cac:PricingReference']['cac:AlternativeConditionPrice'] = $line['cac:PricingReference']['cac:AlternativeConditionPrice'][0] ?? [];
                }
            }
            $body['cac:InvoiceLine'] = array_is_list($lines) ? $lineList : ($lineList[0] ?? []);
        }

        return $body;
    }

    /** Convierte nodos UBL xml2js de _/$ a _text/_attributes. */
    protected function convertUblNodeToPortalShape($node)
    {
        if (!is_array($node)) {
            return $node;
        }
        if (array_is_list($node)) {
            return array_map(function ($item) {
                return $this->convertUblNodeToPortalShape($item);
            }, $node);
        }

        $hasValue = array_key_exists('_text', $node) || array_key_exists('_', $node);
        $hasAttrs = array_key_exists('$', $node) && is_array($node['$']);
        $otherKeys = [];
        foreach ($node as $k => $v) {
            if ($k !== '_' && $k !== '_text' && $k !== '$') {
                $otherKeys[] = $k;
            }
        }

        if ($hasValue && $hasAttrs && count($otherKeys) === 0) {
            return [
                '_attributes' => $node['$'],
                '_text' => array_key_exists('_text', $node) ? $node['_text'] : $node['_'],
            ];
        }
        if ($hasValue && count($otherKeys) === 0) {
            return [
                '_text' => array_key_exists('_text', $node) ? $node['_text'] : $node['_'],
            ];
        }
        if ($hasAttrs && count($otherKeys) === 0) {
            return [
                '_attributes' => $node['$'],
            ];
        }

        $out = [];
        foreach ($node as $k => $v) {
            if ($k === '_') {
                if (!array_key_exists('_text', $node)) {
                    $out['_text'] = $v;
                }
                continue;
            }
            if ($k === '$') {
                $out['_attributes'] = $v;
                continue;
            }
            $out[$k] = $this->convertUblNodeToPortalShape($v);
        }

        return $out;
    }

    /** Copia profunda y elimina claves de primer nivel. */
    protected function documentBodyCloneWithoutKeys(array $documentBody, array $keysToRemove): array
    {
        $json = json_encode($documentBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $copy = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($copy)) {
            return $documentBody;
        }
        foreach ($keysToRemove as $k) {
            unset($copy[$k]);
        }

        return $copy;
    }

    /** Quita cac:PartyTaxScheme del emisor (placeholders "-" a veces rompen validadores). */
    protected function documentBodyWithoutSupplierPartyTaxScheme(array $documentBody): array
    {
        $json = json_encode($documentBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $copy = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($copy)) {
            return $documentBody;
        }
        unset($copy['cac:AccountingSupplierParty']['cac:Party']['cac:PartyTaxScheme']);

        return $copy;
    }

    /** Una sola línea puede ir como objeto; algunos validadores exigen siempre arreglo de líneas. */
    protected function forceInvoiceLineAsArray(array $body): array
    {
        $il = $body['cac:InvoiceLine'] ?? null;
        if ($il !== null && is_array($il) && isset($il['cbc:ID'])) {
            $body['cac:InvoiceLine'] = [$il];
        }

        return $body;
    }

    /** Quita _text cuando duplica _ (algunos parsers de Apisunat solo leen uno de los dos). */
    protected function stripUblDuplicateText(array $data): array
    {
        if (isset($data['_'], $data['_text']) && (string) $data['_'] === (string) $data['_text']) {
            unset($data['_text']);
        }
        foreach ($data as $k => $v) {
            if ($k === '_' || $k === '_text' || $k === '$') {
                continue;
            }
            if (is_array($v)) {
                $data[$k] = array_is_list($v)
                    ? array_map(function ($item) {
                        return is_array($item) ? $this->stripUblDuplicateText($item) : $item;
                    }, $v)
                    : $this->stripUblDuplicateText($v);
            }
        }

        return $data;
    }

    /** Deja solo `_text` (y `$`) en nodos hoja; algunos validadores Node esperan exclusivamente `_text`. */
    protected function stripUblDropUnderscoreWhenTextPresent(array $data): array
    {
        if (isset($data['_'], $data['_text'])) {
            unset($data['_']);
        }
        foreach ($data as $k => $v) {
            if ($k === '_' || $k === '_text' || $k === '$') {
                continue;
            }
            if (is_array($v)) {
                $data[$k] = array_is_list($v)
                    ? array_map(function ($item) {
                        return is_array($item) ? $this->stripUblDropUnderscoreWhenTextPresent($item) : $item;
                    }, $v)
                    : $this->stripUblDropUnderscoreWhenTextPresent($v);
            }
        }

        return $data;
    }

    /** Medio de pago como lista de un bloque (xml2js con explicitArray en cac:PaymentMeans). */
    protected function documentBodyPaymentMeansAsBlocks(array $documentBody): array
    {
        $json = json_encode($documentBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $copy = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($copy)) {
            return $documentBody;
        }
        $pm = $copy['cac:PaymentMeans'] ?? null;
        if (is_array($pm) && isset($pm['cbc:PaymentMeansCode'])) {
            $copy['cac:PaymentMeans'] = [
                [
                    'cbc:PaymentMeansCode' => $pm['cbc:PaymentMeansCode'],
                ],
            ];
        }

        return $copy;
    }

    /**
     * Cuerpo UBL 2.1 en JSON para Apisunat.
     * Catálogo tipo de documento según guía SUNAT (catalogo01); importes/cantidades como texto decimal.
     */
    protected function buildDocumentBody(Invoice $invoice, string $docType, string $serie, string $corr8, string $rucEmisor): array
    {
        $igv = self::IGV;
        $client = $invoice->client;
        $razonEmisor = $this->txt(config('apisunat.company.legal_name'), 'SUBUZ SAC');
        $dirEmisor = $this->txt(config('apisunat.company.address'), '-');

        $docDigits = preg_replace('/\D/', '', (string) ($client->document ?? ''));
        if (strlen($docDigits) === 11) {
            $scheme = '6';
            $docVal = $docDigits;
        } elseif (strlen($docDigits) === 8) {
            $scheme = '1';
            $docVal = $docDigits;
        } elseif (strlen($docDigits) > 0 && strlen($docDigits) < 8) {
            $scheme = '1';
            $docVal = str_pad($docDigits, 8, '0', STR_PAD_LEFT);
        } else {
            $scheme = '0';
            $docVal = '00000000';
        }

        $nombreCliente = $this->txt($client->business_name ?: $client->name, '-');
        $dirCliente = $this->txt($client->address, '-');
        $dirClienteUbigeo = (string) config('apisunat.company.client_ubigeo_default', '140101');
        $emisorDistrict = $this->txt(config('apisunat.company.district'), 'CHICLAYO');
        $clienteDistrict = $this->txt($client->district ?? null, $emisorDistrict);

        $lines = [];
        $i = 1;
        $sumOpGravada = 0.0;
        $sumIgv = 0.0;
        $items = [];
        if ($invoice->details()->count() > 0) {
            foreach ($invoice->details as $detail) {
                $items[] = [
                    'price' => $detail->price,
                    'quantity' => $detail->quantity,
                    'description' => $detail->description,
                    'product_id' => $detail->product_id
                ];
            }
        } else {
            foreach ($invoice->sales as $sale) {
                foreach ($sale->details as $detail) {
                    $items[] = [
                        'price' => $detail->price,
                        'quantity' => $detail->quantity,
                        'description' => $this->txt(data_get($detail, 'product.name'), 'Producto'),
                        'product_id' => $detail->product_id
                    ];
                }
            }
        }

        foreach ($items as $item) {
            $pu = (float) $item['price'];
            $qty = (float) $item['quantity'];
            $importeConIgv = round($pu * $qty, 2);
            $valorVenta = round($importeConIgv / (1 + $igv), 2);
            $igvLinea = round($importeConIgv - $valorVenta, 2);
            $vu = $qty > 0 ? round($valorVenta / $qty, 6) : 0.0;
            $sumOpGravada += $valorVenta;
            $sumIgv += $igvLinea;
            $desc = $this->txt($item['description'], 'Producto');

            $qtyStr = abs($qty - round($qty, 4)) < 0.00001
                ? (string) (int) round($qty)
                : $this->decStr($qty, 4);
            $lines[] = [
                'cbc:ID' => $this->ublText((string) $i),
                'cbc:InvoicedQuantity' => $this->ublQty('NIU', $qtyStr),
                'cbc:LineExtensionAmount' => $this->ublAmt('PEN', $this->decStr($valorVenta, 2)),
                'cac:PricingReference' => [
                    'cac:AlternativeConditionPrice' => [
                        'cbc:PriceAmount' => $this->ublAmt('PEN', $this->decStr($pu, 2)),
                        'cbc:PriceTypeCode' => $this->ublText('01'),
                    ],
                ],
                'cac:TaxTotal' => [
                    'cbc:TaxAmount' => $this->ublAmt('PEN', $this->decStr($igvLinea, 2)),
                    'cac:TaxSubtotal' => [
                        'cbc:TaxableAmount' => $this->ublAmt('PEN', $this->decStr($valorVenta, 2)),
                        'cbc:TaxAmount' => $this->ublAmt('PEN', $this->decStr($igvLinea, 2)),
                        'cac:TaxCategory' => [
                            'cbc:Percent' => $this->ublText('18'),
                            'cbc:TaxExemptionReasonCode' => $this->ublText('10'),
                            'cac:TaxScheme' => [
                                'cbc:ID' => $this->ublTaxSchemeIdPortal('1000'),
                                'cbc:Name' => $this->ublText('IGV'),
                                'cbc:TaxTypeCode' => $this->ublText('VAT'),
                            ],
                        ],
                    ],
                ],
                'cac:Item' => [
                    'cbc:Description' => $this->ublText($desc),
                ],
                'cac:Price' => [
                    'cbc:PriceAmount' => $this->ublAmt('PEN', $this->decStr($vu, 2)),
                ],
            ];
            $i++;
        }

        $sumOpGravada = round($sumOpGravada, 2);
        $sumIgv = round($sumIgv, 2);
        $total = round($sumOpGravada + $sumIgv, 2);

        $idComprobante = $serie . '-' . $corr8;

        return [
            'cbc:UBLVersionID' => $this->ublText('2.1'),
            'cbc:CustomizationID' => $this->ublText('2.0'),
            'cbc:ID' => $this->ublText($idComprobante),
            'cbc:IssueDate' => $this->ublText($invoice->date->format('Y-m-d')),
            'cbc:IssueTime' => $this->ublText(now()->timezone('America/Lima')->format('H:i:s')),
            'cbc:InvoiceTypeCode' => $this->ublInvoiceTypeCode($docType),
            'cbc:DocumentCurrencyCode' => $this->ublText('PEN'),
            'cac:AccountingSupplierParty' => [
                'cac:Party' => [
                    'cac:PartyIdentification' => [
                        'cbc:ID' => $this->ublSchemeId('6', $rucEmisor),
                    ],
                    'cac:PartyLegalEntity' => [
                        'cbc:RegistrationName' => $this->ublText($razonEmisor),
                        'cac:RegistrationAddress' => $this->sunatRegistrationAddressPortalMinimal($dirEmisor),
                    ],
                ],
            ],
            'cac:AccountingCustomerParty' => [
                'cac:Party' => $this->buildAccountingCustomerParty(
                    $docType,
                    $scheme,
                    $docVal,
                    $nombreCliente,
                    $dirCliente,
                    $clienteDistrict,
                    $dirClienteUbigeo
                ),
            ],
            // SUNAT 3244: debe informarse forma de pago (Contado/Crédito) — cac:PaymentTerms obligatorio en validación OSE.
            'cac:PaymentTerms' => [
                'cbc:ID' => $this->ublText('FormaPago'),
                'cbc:PaymentMeansID' => $this->ublText('Contado'),
                'cbc:Amount' => $this->ublAmt('PEN', $this->decStr($total, 2)),
            ],
            'cac:PaymentMeans' => [
                'cbc:PaymentMeansCode' => $this->ublPaymentMeansCode('009'),
            ],
            'cac:TaxTotal' => [
                'cbc:TaxAmount' => $this->ublAmt('PEN', $this->decStr($sumIgv, 2)),
                'cac:TaxSubtotal' => [
                    [
                        'cbc:TaxableAmount' => $this->ublAmt('PEN', $this->decStr($sumOpGravada, 2)),
                        'cbc:TaxAmount' => $this->ublAmt('PEN', $this->decStr($sumIgv, 2)),
                        'cac:TaxCategory' => [
                            'cac:TaxScheme' => [
                                'cbc:ID' => $this->ublTaxSchemeIdPortal('1000'),
                                'cbc:Name' => $this->ublText('IGV'),
                                'cbc:TaxTypeCode' => $this->ublText('VAT'),
                            ],
                        ],
                    ],
                ],
            ],
            'cac:LegalMonetaryTotal' => [
                'cbc:LineExtensionAmount' => $this->ublAmt('PEN', $this->decStr($sumOpGravada, 2)),
                'cbc:TaxInclusiveAmount' => $this->ublAmt('PEN', $this->decStr($total, 2)),
                'cbc:PayableAmount' => $this->ublAmt('PEN', $this->decStr($total, 2)),
            ],
            'cac:InvoiceLine' => count($lines) === 1 ? $lines[0] : $lines,
        ];
    }

    private function decStr(float $n, int $decimals): string
    {
        return number_format($n, $decimals, '.', '');
    }

    /**
     * Texto UBL-JSON para Apisunat: `_` + `_text` (xml2js / validador interno).
     */
    private function ublText(string $s): array
    {
        return ['_' => $s, '_text' => $s];
    }

    /** Monto: `_` + `_text` + `$` (sin claves `@`, evita nodos undefined en boleta). */
    private function ublAmt(string $currencyId, string $amount): array
    {
        return [
            '_' => $amount,
            '_text' => $amount,
            '$' => ['currencyID' => $currencyId],
        ];
    }

    /** Cantidad: `_` + `_text` + `$`. */
    private function ublQty(string $unitCode, string $qty): array
    {
        return [
            '_' => $qty,
            '_text' => $qty,
            '$' => ['unitCode' => $unitCode],
        ];
    }

    /** cbc:ID con scheme de documento. */
    private function ublSchemeId(string $schemeId, string $text): array
    {
        return [
            '_' => $text,
            '_text' => $text,
            '$' => ['schemeID' => $schemeId],
        ];
    }

    /** cbc:InvoiceTypeCode (catálogo 01 SUNAT), forma corta como XML del portal Apisunat. */
    private function ublInvoiceTypeCode(string $docType): array
    {
        return [
            '_' => $docType,
            '_text' => $docType,
            '$' => [
                'listID' => '0101',
            ],
        ];
    }

    /** cbc:ID del tributo en TaxScheme sin atributos de catálogo (XML portal Apisunat). */
    private function ublTaxSchemeIdPortal(string $code): array
    {
        return $this->ublText($code);
    }

    /** cbc:PaymentMeansCode (catálogo 59 SUNAT — medios de pago). */
    private function ublPaymentMeansCode(string $code): array
    {
        return [
            '_' => $code,
            '_text' => $code,
            '$' => [
                'listID' => '59',
            ],
        ];
    }

    /** cbc:ID del TaxScheme (catálogo SUNAT 05). */
    private function ublSunatTaxSchemeCatalogId(string $code): array
    {
        return [
            '_' => $code,
            '_text' => $code,
            '$' => [
                'schemeName' => 'SUNAT:Identificador del tributo',
                'schemeAgencyName' => 'PE:SUNAT',
                'schemeURI' => 'urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo05',
            ],
        ];
    }

    /**
     * Party del cliente alineado al XML del portal Apisunat:
     * - Boleta (03) doc. 0: solo identificación + razón social (sin PartyName ni dirección).
     * - Boleta (03) DNI (1): razón social + CompanyID + dirección mínima (tipo + línea).
     * - Factura (01) RUC (6): PartyName + legal entity con dirección texto + país (Greenter/SUNAT).
     */
    protected function buildAccountingCustomerParty(
        string $docType,
        string $scheme,
        string $docVal,
        string $nombreCliente,
        string $dirCliente,
        string $clienteDistrict,
        string $dirClienteUbigeo
    ): array {
        $party = [
            'cac:PartyIdentification' => [
                'cbc:ID' => $this->ublSchemeId((string) $scheme, (string) $docVal),
            ],
        ];
        if ($docType === '03') {
            if ((string) $scheme === '1') {
                $party['cac:PartyLegalEntity'] = [
                    'cbc:RegistrationName' => $this->ublText($nombreCliente),
                    'cbc:CompanyID' => $this->ublSchemeId((string) $scheme, (string) $docVal),
                    'cac:RegistrationAddress' => $this->sunatRegistrationAddressPortalMinimal($dirCliente),
                ];
            } else {
                $party['cac:PartyLegalEntity'] = [
                    'cbc:RegistrationName' => $this->ublText($nombreCliente),
                ];
            }

            return $party;
        }
        if ($docType === '01' && (string) $scheme === '6') {
            $party['cac:PartyName'] = [
                'cbc:Name' => $this->ublText($nombreCliente),
            ];
            $party['cac:PartyLegalEntity'] = [
                'cbc:RegistrationName' => $this->ublText($nombreCliente),
                'cac:RegistrationAddress' => $this->sunatRegistrationAddressCustomerRucFactura($dirCliente, $clienteDistrict),
            ];

            return $party;
        }
        if ((string) $scheme === '6') {
            $party['cac:PartyTaxScheme'] = [
                'cbc:RegistrationName' => $this->ublText($nombreCliente),
                'cbc:CompanyID' => $this->ublSchemeId((string) $scheme, (string) $docVal),
                'cac:TaxScheme' => [
                    'cbc:ID' => $this->ublSunatTaxSchemeCatalogId('1000'),
                    'cbc:Name' => $this->ublText('IGV'),
                    'cbc:TaxTypeCode' => $this->ublText('VAT'),
                ],
            ];
        }
        $party['cac:PartyLegalEntity'] = [
            'cbc:RegistrationName' => $this->ublText($nombreCliente),
            'cbc:CompanyID' => $this->ublSchemeId((string) $scheme, (string) $docVal),
            'cac:RegistrationAddress' => $this->sunatRegistrationAddress($dirCliente, $clienteDistrict, $dirClienteUbigeo),
        ];

        return $party;
    }

    /**
     * Dirección del adquiriente RUC en factura (01): UBL aceptado por SUNAT/OSE (ej. Greenter) — solo línea y país.
     */
    protected function sunatRegistrationAddressCustomerRucFactura(string $line, string $district): array
    {
        $line = $this->txt($line, '-');
        $dist = $this->txt($district, '-');
        $composed = $line;
        if ($dist !== '' && $dist !== '-') {
            $composed .= ' - ' . $dist;
        }

        return [
            'cac:AddressLine' => [
                'cbc:Line' => $this->ublText($composed),
            ],
            'cac:Country' => [
                'cbc:IdentificationCode' => $this->ublText('PE'),
            ],
        ];
    }

    /**
     * Dirección emisor/cliente al estilo XML que genera el portal Apisunat (solo tipo establecimiento + línea).
     */
    protected function sunatRegistrationAddressPortalMinimal(string $line): array
    {
        return [
            'cbc:AddressTypeCode' => $this->ublText('0000'),
            'cac:AddressLine' => [
                'cbc:Line' => $this->ublText($this->txt($line, '-')),
            ],
        ];
    }

    /**
     * Dirección extendida (ubigeo, ciudad, etc.) por si se reutiliza fuera del flujo portal mínimo.
     */
    protected function sunatRegistrationAddress(string $line, string $district, string $ubigeoRaw): array
    {
        $ubigeo = $this->normalizeUbigeo($ubigeoRaw);
        $city = $this->txt(config('apisunat.company.city'), 'CHICLAYO');
        $region = $this->txt(config('apisunat.company.region'), 'LAMBAYEQUE');
        $dist = $this->txt($district, 'CHICLAYO');

        return [
            'cbc:ID' => $this->ublText($ubigeo),
            'cbc:AddressTypeCode' => $this->ublText('0000'),
            'cbc:CitySubdivisionName' => $this->ublText('-'),
            'cbc:CityName' => $this->ublText($city),
            'cbc:CountrySubentity' => $this->ublText($region),
            'cbc:District' => $this->ublText($dist),
            'cac:AddressLine' => [
                'cbc:Line' => $this->ublText($line),
            ],
            'cac:Country' => [
                'cbc:IdentificationCode' => $this->ublText('PE'),
            ],
        ];
    }

    protected function normalizeUbigeo(string $raw): string
    {
        $d = preg_replace('/\D/', '', $raw);
        if (strlen($d) >= 6) {
            return substr($d, 0, 6);
        }
        if ($d !== '') {
            return str_pad($d, 6, '0', STR_PAD_LEFT);
        }

        return (string) config('apisunat.company.ubigeo', '140101');
    }

    private function txt($v, string $fallback): string
    {
        $s = trim((string) ($v ?? ''));
        if ($s === '') {
            return $fallback;
        }
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
        }

        return $s;
    }
}
