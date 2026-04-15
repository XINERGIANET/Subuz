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

        $apiUrl = $config->api_url ?: config('apisunat.url');
        $personaId = $config->persona_id;
        $personaToken = $config->persona_token;

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
        $rucEmisor = config('apisunat.company.ruc', '20100100100');
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
        return BranchElectronicBillingConfig::first();
    }

    protected function buildDocumentBody(Invoice $invoice, $docType, $serie, $number)
    {
        $client = $invoice->client;
        
        // Calcular totales
        $total = $invoice->total;
        $igvRate = 0.18;
        $subtotal = round($total / (1 + $igvRate), 2);
        $totalIgv = round($total - $subtotal, 2);

        $rucEmisor = config('apisunat.company.ruc', '20100100100');
        $razonSocial = config('apisunat.company.legal_name', 'SUBUZ SAC');

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
                            "value" => $rucEmisor
                        ]
                    ],
                    "cac:PartyName" => [
                        "cbc:Name" => $razonSocial
                    ],
                    "cac:PartyLegalEntity" => [
                        "cbc:RegistrationName" => $razonSocial,
                        "cac:RegistrationAddress" => [
                            "cbc:AddressTypeCode" => "0000",
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
                            "schemeID" => strlen($client->document) === 11 ? "6" : "1",
                            "value" => $client->document ?: "00000000"
                        ]
                    ],
                    "cac:PartyLegalEntity" => [
                        "cbc:RegistrationName" => $client->business_name ?: $client->name,
                    ]
                ]
            ],
            "cac:TaxTotal" => [
                [
                    "cbc:TaxAmount" => [
                        "currencyID" => "PEN",
                        "value" => $totalIgv
                    ],
                    "cac:TaxSubtotal" => [
                        [
                            "cbc:TaxableAmount" => [
                                "currencyID" => "PEN",
                                "value" => $subtotal
                            ],
                            "cbc:TaxAmount" => [
                                "currencyID" => "PEN",
                                "value" => $totalIgv
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
                    "value" => $subtotal
                ],
                "cbc:TaxInclusiveAmount" => [
                    "currencyID" => "PEN",
                    "value" => $total
                ],
                "cbc:PayableAmount" => [
                    "currencyID" => "PEN",
                    "value" => $total
                ]
            ],
            "cac:InvoiceLine" => []
        ];

        // Líneas de la factura (Consolidar de todos los Sales)
        $itemsCount = 1;
        foreach ($invoice->sales as $sale) {
            foreach ($sale->details as $detail) {
                $itemTotal = $detail->price * $detail->quantity;
                $itemSubtotal = round($itemTotal / (1 + $igvRate), 2);
                $itemIgv = round($itemTotal - $itemSubtotal, 2);

                $body["cac:InvoiceLine"][] = [
                    "cbc:ID" => (string) $itemsCount++,
                    "cbc:InvoicedQuantity" => [
                        "unitCode" => "NIU",
                        "value" => $detail->quantity
                    ],
                    "cbc:LineExtensionAmount" => [
                        "currencyID" => "PEN",
                        "value" => $itemSubtotal
                    ],
                    "cac:PricingReference" => [
                        "cac:AlternativeConditionPrice" => [
                            [
                                "cbc:PriceAmount" => [
                                    "currencyID" => "PEN",
                                    "value" => $detail->price
                                ],
                                "cbc:PriceTypeCode" => "01"
                            ]
                        ]
                    ],
                    "cac:TaxTotal" => [
                        [
                            "cbc:TaxAmount" => [
                                "currencyID" => "PEN",
                                "value" => $itemIgv
                            ],
                            "cac:TaxSubtotal" => [
                                [
                                    "cbc:TaxableAmount" => [
                                        "currencyID" => "PEN",
                                        "value" => $itemSubtotal
                                    ],
                                    "cbc:TaxAmount" => [
                                        "currencyID" => "PEN",
                                        "value" => $itemIgv
                                    ],
                                    "cac:TaxCategory" => [
                                        "cbc:Percent" => 18,
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
                        "cbc:Description" => $detail->product->name
                    ],
                    "cac:Price" => [
                        "cbc:PriceAmount" => [
                            "currencyID" => "PEN",
                            "value" => round($detail->price / (1 + $igvRate), 2)
                        ]
                    ]
                ];
            }
        }

        return $body;
    }
}
