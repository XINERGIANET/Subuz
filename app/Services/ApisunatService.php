<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\BranchElectronicBillingConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApisunatService
{
    protected $client;

    public function __construct()
    {
        // No instanciamos el cliente HTTP aquí para permitir configuración por sucursal dinámica
    }

    /**
     * Emite un comprobante electrónico basado en una Factura (Invoice)
     */
    public function emitInvoice(Invoice $invoice)
    {
        $invoice->load(['client', 'sales.details.product']);

        $config = $this->getConfig();
        if (!$config || !$config->enabled) {
            return ['status' => 'SKIPPED', 'message' => 'Facturación electrónica no configurada o desactivada.'];
        }

        $apiUrl = $config->api_url ?: config('apisunat.url', 'https://back.apisunat.com');
        $apiUrl = rtrim($apiUrl, '/'); // Eliminar barra final si existe
        
        if (empty($apiUrl) || $apiUrl === '/') {
            $apiUrl = 'https://back.apisunat.com';
        }

        $personaId = $config->persona_id ?: config('apisunat.id');
        $personaToken = $config->persona_token ?: config('apisunat.token.prod');

        // Determinar tipo de documento (01 Factura, 03 Boleta)
        $isRuc = $invoice->document_type === 'factura' || strlen($invoice->client->document) === 11;
        $docType = $isRuc ? '01' : '03';
        $serie = $isRuc ? ($config->series_factura ?: 'F001') : ($config->series_boleta ?: 'B001');

        // 1. Obtener número correlativo sugerido si no tiene uno
        $correlativeResp = Http::timeout(20)->post($apiUrl . '/personas/lastDocument', [
            'personaId' => (string) $personaId,
            'personaToken' => (string) $personaToken,
            'type' => $docType,
            'serie' => $serie,
        ]);

        if ($correlativeResp->failed()) {
            return ['status' => 'ERROR', 'message' => 'Error al obtener correlativo: ' . $correlativeResp->body()];
        }

        $suggestedNumber = $correlativeResp->json('suggestedNumber');
        $rucEmisor = config('apisunat.company.ruc', '20615250024');
        $fileName = sprintf('%s-%s-%s-%s', $rucEmisor, $docType, $serie, str_pad($suggestedNumber, 8, '0', STR_PAD_LEFT));

        // 2. Construir cuerpo del documento
        $documentBody = $this->buildDocumentBody($invoice, $docType, $serie, $suggestedNumber);

        // 3. Enviar a Apisunat
        $sendResp = Http::timeout(35)->post($apiUrl . '/personas/v1/sendBill', [
            'personaId' => (string) $personaId,
            'personaToken' => (string) $personaToken,
            'fileName' => $fileName,
            'documentBody' => $documentBody,
        ]);

        if ($sendResp->failed()) {
            return ['status' => 'ERROR', 'message' => 'Error al enviar comprobante: ' . $sendResp->body()];
        }

        $documentId = $sendResp->json('documentId');

        // 4. Actualizar Invoice con resultados
        $invoice->update([
            'electronic_invoice_provider' => 'apisunat',
            'electronic_invoice_status' => 'SENT',
            'electronic_invoice_external_id' => $documentId,
            'electronic_invoice_series' => $serie,
            'electronic_invoice_number' => $suggestedNumber,
            'electronic_invoice_file_name' => $fileName,
            'electronic_invoice_pdf_a4_url' => "{$apiUrl}/documents/{$documentId}/getPDF/A4/{$fileName}.pdf",
            'electronic_invoice_xml_url' => "{$apiUrl}/documents/{$documentId}/getXML",
            'electronic_invoice_cdr_url' => "{$apiUrl}/documents/{$documentId}/getCDR",
            'electronic_invoice_response' => $sendResp->json(),
        ]);

        return ['status' => 'SENT', 'documentId' => $documentId];
    }

    protected function getConfig()
    {
        // Como no hay sucursales, tomamos la primera configuración disponible
        $config = BranchElectronicBillingConfig::first();

        // Si no existe, lo creamos
        if (!$config) {
            $config = BranchElectronicBillingConfig::create([
                'id' => 1,
                'enabled' => true,
                'api_url' => config('apisunat.url', 'https://back.apisunat.com'),
                'persona_id' => config('apisunat.id'),
                'persona_token' => config('apisunat.token.prod'),
                'series_boleta' => config('apisunat.series.boleta', 'B001'),
                'series_factura' => config('apisunat.series.factura', 'F001'),
            ]);
        } 
        // Si existe pero tiene campos nulos (debido a errores previos de caché), los reparamos
        elseif (empty($config->persona_id) || empty($config->persona_token)) {
            $config->update([
                'api_url' => $config->api_url ?: (config('apisunat.url') ?: env('APISUNAT_URL', 'https://back.apisunat.com')),
                'persona_id' => $config->persona_id ?: (config('apisunat.id') ?: env('APISUNAT_ID')),
                'persona_token' => $config->persona_token ?: (config('apisunat.token.prod') ?: env('APISUNAT_TOKEN_PROD')),
            ]);
        }

        return $config;
    }

    protected function buildDocumentBody(Invoice $invoice, $docType, $serie, $number)
    {
        $client = $invoice->client;
        
        // Calcular totales con precisión
        $total = (float) $invoice->total;
        $igvRate = 0.18;
        $subtotal = round($total / (1 + $igvRate), 2);
        $totalIgv = round($total - $subtotal, 2);

        $rucEmisor = config('apisunat.company.ruc', '20615250024');
        $razonSocialEmisor = config('apisunat.company.legal_name', 'SUBUZ SAC');
        $direccionEmisor = config('apisunat.company.address', '-');

        // Lógica de identificación de cliente refinada
        $docCliente = (string) $client->document;
        $isRuc = strlen($docCliente) === 11;
        $schemeID = $isRuc ? "6" : (strlen($docCliente) === 8 ? "1" : "0");
        $razonSocialCliente = trim($client->business_name ?: $client->name) ?: '-';
        $direccionCliente = trim($client->address ?: '-');

        $body = [
            "cbc:UBLVersionID" => "2.1",
            "cbc:CustomizationID" => "2.0",
            "cbc:ID" => "{$serie}-{$number}",
            "cbc:IssueDate" => $invoice->date->format('Y-m-d'),
            "cbc:IssueTime" => now()->format('H:i:s'),
            "cbc:InvoiceTypeCode" => [
                "listID" => "0101",
                "value" => $docType
            ],
            "cbc:DocumentCurrencyCode" => "PEN",
            "cac:AccountingSupplierParty" => [
                "cac:Party" => [
                    "cac:PartyIdentification" => [
                        "cbc:ID" => [
                            "schemeID" => "6",
                            "value" => (string) $rucEmisor
                        ]
                    ],
                    "cac:PartyName" => [
                        "cbc:Name" => $razonSocialEmisor
                    ],
                    "cac:PartyLegalEntity" => [
                        "cbc:RegistrationName" => $razonSocialEmisor,
                        "cac:RegistrationAddress" => [
                            "cbc:AddressTypeCode" => "0000",
                            "cac:AddressLine" => [
                                "cbc:Line" => $direccionEmisor
                            ],
                            "cac:Country" => [
                                "cbc:IdentificationCode" => "PE"
                            ]
                        ]
                    ]
                ]
            ],
            "cac:AccountingCustomerParty" => [
                "cac:Party" => [
                    "cac:PartyIdentification" => [
                        "cbc:ID" => [
                            "schemeID" => (string) $schemeID,
                            "value" => (string) ($docCliente ?: "00000000")
                        ]
                    ],
                    "cac:PartyLegalEntity" => [
                        "cbc:RegistrationName" => $razonSocialCliente,
                        "cac:RegistrationAddress" => [
                            "cac:AddressLine" => [
                                "cbc:Line" => $direccionCliente
                            ],
                            "cac:Country" => [
                                "cbc:IdentificationCode" => "PE"
                            ]
                        ]
                    ]
                ]
            ],
            "cac:TaxTotal" => [
                [
                    "cbc:TaxAmount" => [
                        "currencyID" => "PEN",
                        "value" => number_format($totalIgv, 2, '.', '')
                    ],
                    "cac:TaxSubtotal" => [
                        [
                            "cbc:TaxableAmount" => [
                                "currencyID" => "PEN",
                                "value" => number_format($subtotal, 2, '.', '')
                            ],
                            "cbc:TaxAmount" => [
                                "currencyID" => "PEN",
                                "value" => number_format($totalIgv, 2, '.', '')
                            ],
                            "cac:TaxCategory" => [
                                "cac:TaxScheme" => [
                                    "cbc:ID" => "1000",
                                    "cbc:Name" => "IGV",
                                    "cbc:TaxTypeCode" => "VAT"
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "cac:LegalMonetaryTotal" => [
                "cbc:LineExtensionAmount" => [
                    "currencyID" => "PEN",
                    "value" => number_format($subtotal, 2, '.', '')
                ],
                "cbc:TaxInclusiveAmount" => [
                    "currencyID" => "PEN",
                    "value" => number_format($total, 2, '.', '')
                ],
                "cbc:PayableAmount" => [
                    "currencyID" => "PEN",
                    "value" => number_format($total, 2, '.', '')
                ]
            ],
            "cac:InvoiceLine" => []
        ];

        // Líneas de la factura
        $itemsCount = 1;
        foreach ($invoice->sales as $sale) {
            foreach ($sale->details as $detail) {
                $itemPrice = (float) $detail->price;
                $itemQty = (float) $detail->quantity;
                $itemTotal = $itemPrice * $itemQty;
                
                $itemSubtotal = round($itemTotal / (1 + $igvRate), 2);
                $itemIgv = round($itemTotal - $itemSubtotal, 2);
                $valueUnitPrice = round($itemPrice / (1 + $igvRate), 4);

                $body["cac:InvoiceLine"][] = [
                    "cbc:ID" => (string) $itemsCount++,
                    "cbc:InvoicedQuantity" => [
                        "unitCode" => "NIU",
                        "value" => number_format($itemQty, 2, '.', '')
                    ],
                    "cbc:LineExtensionAmount" => [
                        "currencyID" => "PEN",
                        "value" => number_format($itemSubtotal, 2, '.', '')
                    ],
                    "cac:PricingReference" => [
                        "cac:AlternativeConditionPrice" => [
                            [
                                "cbc:PriceAmount" => [
                                    "currencyID" => "PEN",
                                    "value" => number_format($itemPrice, 2, '.', '')
                                ],
                                "cbc:PriceTypeCode" => "01"
                            ]
                        ]
                    ],
                    "cac:TaxTotal" => [
                        [
                            "cbc:TaxAmount" => [
                                "currencyID" => "PEN",
                                "value" => number_format($itemIgv, 2, '.', '')
                            ],
                            "cac:TaxSubtotal" => [
                                [
                                    "cbc:TaxableAmount" => [
                                        "currencyID" => "PEN",
                                        "value" => number_format($itemSubtotal, 2, '.', '')
                                    ],
                                    "cbc:TaxAmount" => [
                                        "currencyID" => "PEN",
                                        "value" => number_format($itemIgv, 2, '.', '')
                                    ],
                                    "cac:TaxCategory" => [
                                        "cbc:Percent" => "18",
                                        "cbc:TaxExemptionReasonCode" => "10",
                                        "cac:TaxScheme" => [
                                            "cbc:ID" => "1000",
                                            "cbc:Name" => "IGV",
                                            "cbc:TaxTypeCode" => "VAT"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "cac:Item" => [
                        "cbc:Description" => (string) ($detail->product->name ?? 'Producto')
                    ],
                    "cac:Price" => [
                        "cbc:PriceAmount" => [
                            "currencyID" => "PEN",
                            "value" => number_format($valueUnitPrice, 4, '.', '')
                        ]
                    ]
                ];
            }
        }

        return $body;
    }
}
