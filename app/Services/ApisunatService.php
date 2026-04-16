<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $invoice->load(['client', 'sales.details.product']);

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

        $sendBillVariants = $docType === '03'
            ? ['flat' => $documentBody]
            : [
                'flat' => $documentBody,
                'invoice_root' => ['Invoice' => $documentBody],
            ];
        $send = null;
        $sendBody = '';
        $sendJson = null;
        $usedVariant = 'flat';
        $postedDocumentBody = $documentBody;
        foreach ($sendBillVariants as $variantKey => $docPayload) {
            $usedVariant = (string) $variantKey;
            $postedDocumentBody = $docPayload;
            $send = $this->jsonPost($apiUrl . '/personas/v1/sendBill', [
                'personaId' => $personaId,
                'personaToken' => $personaToken,
                'fileName' => $fileName,
                'documentBody' => $docPayload,
            ], 45);
            $sendBody = $send->body();
            $sendJson = $send->json();
            if ($send->ok() && is_array($sendJson) && ($sendJson['status'] ?? '') === 'PENDIENTE') {
                break;
            }
            if ($variantKey === 'invoice_root') {
                break;
            }
        }

        if ($send === null) {
            return ['status' => 'ERROR', 'message' => 'No se pudo contactar a Apisunat (sendBill).'];
        }

        if ($send->failed()) {
            $debugFile = $this->writeApisunatDebugPayload($invoice->id, $fileName, $postedDocumentBody, $sendJson, $sendBody, $lastJson, $usedVariant);
            Log::error('Apisunat sendBill HTTP error', [
                'invoice_id' => $invoice->id,
                'fileName' => $fileName,
                'variant' => $usedVariant,
                'debug_file' => $debugFile,
                'body' => mb_substr($sendBody, 0, 8000),
            ]);
            $this->persistInvoiceError($invoice, 'Error sendBill HTTP: ' . $sendBody, $sendJson, [
                'fileName' => $fileName,
                'debug_disk' => $debugFile,
                'lastDocument' => $lastJson,
                'sendBill_variant' => $usedVariant,
                'documentBody_posted' => $postedDocumentBody,
            ]);

            $msgHttp = 'Error al enviar comprobante: ' . $sendBody;

            return $this->apisunatErrorResult($msgHttp, $fileName, $debugFile, $invoice->id);
        }
        if (!is_array($sendJson) || ($sendJson['status'] ?? '') !== 'PENDIENTE') {
            $msg = $this->formatApisunatError($sendJson, $sendBody);
            $debugFile = $this->writeApisunatDebugPayload($invoice->id, $fileName, $postedDocumentBody, $sendJson, $sendBody, $lastJson, $usedVariant);
            Log::error('Apisunat sendBill rechazado', [
                'invoice_id' => $invoice->id,
                'fileName' => $fileName,
                'variant' => $usedVariant,
                'debug_file' => $debugFile,
                'apisunat_message' => $msg,
            ]);
            $this->persistInvoiceError($invoice, $msg, $sendJson, [
                'fileName' => $fileName,
                'debug_disk' => $debugFile,
                'lastDocument' => $lastJson,
                'sendBill_variant' => $usedVariant,
                'documentBody_posted' => $postedDocumentBody,
            ]);

            return $this->apisunatErrorResult($msg, $fileName, $debugFile, $invoice->id);
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
    protected function writeApisunatDebugPayload(int $invoiceId, string $fileName, array $documentBody, $sendJson, string $sendBody, $lastDocument, ?string $sendBillVariant = null): ?string
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
                'lastDocument' => $lastDocument,
                'apisunat_response' => $sendJson,
                'apisunat_response_raw' => $sendBody,
                'documentBody' => $documentBody,
            ];
            file_put_contents($path, json_encode($dump, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return $basename;
        } catch (\Throwable $e) {
            Log::warning('No se pudo escribir apisunat-debug', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function apisunatErrorResult(string $message, string $fileName, ?string $debugBasename, int $invoiceId): array
    {
        $diskHint = $debugBasename
            ? 'storage/app/apisunat-debug/' . $debugBasename
            : '(no se pudo guardar archivo de depuración; revise permisos de storage/app/)';

        return [
            'status' => 'ERROR',
            'message' => $message,
            'fileName' => $fileName,
            'debug_file' => $debugBasename,
            'hint' => 'Apisunat falló al validar el JSON enviado. (1) Archivo en el servidor: ' . $diskHint . ' — compárelo con el JSON del botón { } en el portal Apisunat. (2) Log: storage/logs/laravel.log buscando "Apisunat sendBill". (3) Confirme que fileName SUNAT ' . $fileName . ' use serie y correlativo habilitados en Apisunat. Comprobante interno id=' . $invoiceId . '.',
        ];
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
                return 'Apisunat: rechazo sin detalle (error vacío). Revise serie/correlativo en el portal y que el JSON coincida con el generador { } de Apisunat.';
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
        $dirEmisorUbigeo = (string) config('apisunat.company.ubigeo', '140101');
        $emisorDistrict = $this->txt(config('apisunat.company.district'), 'CHICLAYO');
        $clienteDistrict = $this->txt($client->district ?? null, $emisorDistrict);

        $lines = [];
        $i = 1;
        $sumOpGravada = 0.0;
        $sumIgv = 0.0;
        foreach ($invoice->sales as $sale) {
            foreach ($sale->details as $detail) {
                $pu = (float) $detail->price;
                $qty = (float) $detail->quantity;
                $importeConIgv = round($pu * $qty, 2);
                $valorVenta = round($importeConIgv / (1 + $igv), 2);
                $igvLinea = round($importeConIgv - $valorVenta, 2);
                $vu = $qty > 0 ? round($valorVenta / $qty, 6) : 0.0;
                $sumOpGravada += $valorVenta;
                $sumIgv += $igvLinea;
                $desc = $this->txt(data_get($detail, 'product.name'), 'Producto');

                $lines[] = [
                    'cbc:ID' => $this->ublText((string) $i),
                    'cbc:InvoicedQuantity' => $this->ublQty('NIU', $this->decStr($qty, 4)),
                    'cbc:LineExtensionAmount' => $this->ublAmt('PEN', $this->decStr($valorVenta, 2)),
                    'cac:PricingReference' => [
                        'cac:AlternativeConditionPrice' => [
                            [
                                'cbc:PriceAmount' => $this->ublAmt('PEN', $this->decStr($pu, 2)),
                                'cbc:PriceTypeCode' => $this->ublText('01'),
                            ],
                        ],
                    ],
                    'cac:TaxTotal' => [
                        'cbc:TaxAmount' => $this->ublAmt('PEN', $this->decStr($igvLinea, 2)),
                        'cac:TaxSubtotal' => [
                            [
                                'cbc:TaxableAmount' => $this->ublAmt('PEN', $this->decStr($valorVenta, 2)),
                                'cbc:TaxAmount' => $this->ublAmt('PEN', $this->decStr($igvLinea, 2)),
                                'cac:TaxCategory' => [
                                    'cbc:Percent' => $this->ublText($this->decStr(18.0, 2)),
                                    'cbc:TaxExemptionReasonCode' => $this->ublText('10'),
                                    'cac:TaxScheme' => [
                                        'cbc:ID' => $this->ublSunatTaxSchemeCatalogId('1000'),
                                        'cbc:Name' => $this->ublText('IGV'),
                                        'cbc:TaxTypeCode' => $this->ublText('VAT'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'cac:Item' => [
                        'cbc:Description' => $this->ublText($desc),
                        'cac:SellersItemIdentification' => [
                            'cbc:ID' => $this->ublSellersItemId((string) ($detail->product_id ?? $i)),
                        ],
                    ],
                    'cac:Price' => [
                        'cbc:PriceAmount' => $this->ublAmt('PEN', $this->decStr($vu, 6)),
                    ],
                ];
                $i++;
            }
        }

        $sumOpGravada = round($sumOpGravada, 2);
        $sumIgv = round($sumIgv, 2);
        $total = round($sumOpGravada + $sumIgv, 2);

        $idComprobante = $serie . '-' . $corr8;
        $uuid = (string) Str::uuid();

        return [
            'cbc:UBLVersionID' => $this->ublText('2.1'),
            'cbc:CustomizationID' => $this->ublText('2.0'),
            'cbc:ProfileID' => $this->ublText('0101'),
            'cbc:ID' => $this->ublText($idComprobante),
            'cbc:UUID' => $this->ublText($uuid),
            'cbc:IssueDate' => $this->ublText($invoice->date->format('Y-m-d')),
            'cbc:IssueTime' => $this->ublText(now()->timezone('America/Lima')->format('H:i:s')),
            'cbc:InvoiceTypeCode' => $this->ublInvoiceTypeCode($docType),
            'cbc:DocumentCurrencyCode' => $this->ublText('PEN'),
            'cbc:LineCountNumeric' => $this->ublText((string) count($lines)),
            'cac:AccountingSupplierParty' => [
                'cac:Party' => [
                    'cac:PartyIdentification' => [
                        'cbc:ID' => $this->ublSchemeId('6', $rucEmisor),
                    ],
                    'cac:PartyName' => [
                        'cbc:Name' => $this->ublText($razonEmisor),
                    ],
                    'cac:PartyTaxScheme' => [
                        'cbc:RegistrationName' => $this->ublText($razonEmisor),
                        'cbc:CompanyID' => $this->ublSchemeId('6', $rucEmisor),
                        'cac:TaxScheme' => [
                            'cbc:ID' => $this->ublText('-'),
                            'cbc:Name' => $this->ublText('-'),
                            'cbc:TaxTypeCode' => $this->ublText('-'),
                        ],
                    ],
                    'cac:PartyLegalEntity' => [
                        'cbc:RegistrationName' => $this->ublText($razonEmisor),
                        'cbc:CompanyID' => $this->ublSchemeId('6', $rucEmisor),
                        'cac:RegistrationAddress' => $this->sunatRegistrationAddress($dirEmisor, $emisorDistrict, $dirEmisorUbigeo),
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
            'cac:TaxTotal' => [
                [
                    'cbc:TaxAmount' => $this->ublAmt('PEN', $this->decStr($sumIgv, 2)),
                    'cac:TaxSubtotal' => [
                        [
                            'cbc:TaxableAmount' => $this->ublAmt('PEN', $this->decStr($sumOpGravada, 2)),
                            'cbc:TaxAmount' => $this->ublAmt('PEN', $this->decStr($sumIgv, 2)),
                            'cac:TaxCategory' => [
                                'cbc:Percent' => $this->ublText($this->decStr(18.0, 2)),
                                'cbc:TaxExemptionReasonCode' => $this->ublText('10'),
                                'cac:TaxScheme' => [
                                    'cbc:ID' => $this->ublSunatTaxSchemeCatalogId('1000'),
                                    'cbc:Name' => $this->ublText('IGV'),
                                    'cbc:TaxTypeCode' => $this->ublText('VAT'),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'cac:LegalMonetaryTotal' => [
                'cbc:LineExtensionAmount' => $this->ublAmt('PEN', $this->decStr($sumOpGravada, 2)),
                'cbc:TaxExclusiveAmount' => $this->ublAmt('PEN', $this->decStr($sumOpGravada, 2)),
                'cbc:TaxInclusiveAmount' => $this->ublAmt('PEN', $this->decStr($total, 2)),
                'cbc:AllowanceTotalAmount' => $this->ublAmt('PEN', $this->decStr(0.0, 2)),
                'cbc:ChargeTotalAmount' => $this->ublAmt('PEN', $this->decStr(0.0, 2)),
                'cbc:PayableAmount' => $this->ublAmt('PEN', $this->decStr($total, 2)),
            ],
            'cac:InvoiceLine' => $lines,
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

    /** Ubigeo en dirección. */
    private function ublUbigeoId(string $ubigeo): array
    {
        return [
            '_' => $ubigeo,
            '_text' => $ubigeo,
            '$' => ['schemeName' => 'Ubigeo'],
        ];
    }

    /** cbc:InvoiceTypeCode (catálogo 01 SUNAT). */
    private function ublInvoiceTypeCode(string $docType): array
    {
        return [
            '_' => $docType,
            '_text' => $docType,
            '$' => [
                'listID' => '0101',
                'listAgencyName' => 'PE:SUNAT',
                'listName' => 'SUNAT:Identificador de Tipo de Documento',
                'listURI' => 'urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01',
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

    /** Código de producto interno (schemeID 999). */
    private function ublSellersItemId(string $id): array
    {
        return [
            '_' => $id,
            '_text' => $id,
            '$' => ['schemeID' => '999'],
        ];
    }

    /**
     * Party del cliente: boleta (03) incluye cac:PartyName (nombre del adquirente), requerido en varios validadores SUNAT/Apisunat.
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
            $party['cac:PartyName'] = [
                'cbc:Name' => $this->ublText($nombreCliente),
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
     * Dirección SUNAT/UBL con ubigeo y localidad; Apisunat suele leer estos nodos (si faltan provoca TypeError en su validador).
     */
    protected function sunatRegistrationAddress(string $line, string $district, string $ubigeoRaw): array
    {
        $ubigeo = $this->normalizeUbigeo($ubigeoRaw);
        $city = $this->txt(config('apisunat.company.city'), 'CHICLAYO');
        $region = $this->txt(config('apisunat.company.region'), 'LAMBAYEQUE');
        $dist = $this->txt($district, 'CHICLAYO');

        return [
            'cbc:AddressTypeCode' => $this->ublText('0000'),
            'cbc:CityName' => $this->ublText($city),
            'cbc:CountrySubentity' => $this->ublText($region),
            'cbc:District' => $this->ublText($dist),
            'cbc:ID' => $this->ublUbigeoId($ubigeo),
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
