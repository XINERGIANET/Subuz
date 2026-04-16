<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\BranchElectronicBillingConfig;
use App\Services\ApisunatService;
use App\Support\SolesEnLetras;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Support\Facades\URL;

class InvoiceController extends Controller
{
    protected $apisunatService;

    public function __construct(ApisunatService $apisunatService)
    {
        $this->apisunatService = $apisunatService;
    }

    public function index(Request $request)
    {
        $query = Invoice::query();

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->type) {
            $query->where('document_type', $request->type);
        }

        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $invoices = $query->with(['client', 'sales'])->orderBy('date', 'desc')->orderBy('number', 'desc')->paginate(15);
        $clients = Client::all();
        $selected_client = $request->client_id ? Client::find($request->client_id) : null;

        return view('invoices.index', compact('invoices', 'clients', 'selected_client'));
    }

    public function pending(Request $request)
    {
        // For the pending view, we usually want to see sales grouped by client or at least filterable
        $query = Sale::whereDoesntHave('invoices')
            ->where('status', '!=', 'Anulado');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->type && !$request->client_id) {
            $query->whereHas('client', function($q) use ($request) {
                if ($request->type == 'factura') {
                    $q->whereRaw('LENGTH(document) = 11');
                } elseif ($request->type == 'boleta') {
                    $q->whereRaw('(LENGTH(document) != 11 OR document IS NULL OR document = "")');
                }
            });
        }

        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $sales = $query->with('client')->latest('date')->get();
        $clients = Client::all();
        $selected_client = $request->client_id ? Client::find($request->client_id) : null;
        $selected_type = $request->type;

        // Asegurar que exista al menos un registro en settings
        if (DB::table('settings')->count() === 0) {
            DB::table('settings')->insert([
                'id' => 1,
                'factura_count' => 0,
                'boleta_count' => 0
            ]);
        }

        $settings = DB::table('settings')->first();
        $next_factura = str_pad(($settings->factura_count ?? 0) + 1, 8, "0", STR_PAD_LEFT);
        $next_boleta = str_pad(($settings->boleta_count ?? 0) + 1, 8, "0", STR_PAD_LEFT);

        $selected_type = $request->type;
        // Default number to show depending on type (will be updated via JS too)
        $next_invoice = ($selected_type == 'factura') ? $next_factura : $next_boleta;

        return view('invoices.pending', compact('sales', 'clients', 'selected_client', 'next_invoice', 'next_factura', 'next_boleta', 'selected_type'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'sales' => 'required|array',
            'sales.*' => 'exists:sales,id'
        ], [
            'sales.required' => 'Debe seleccionar al menos una venta para emitir el comprobante.'
        ]);

        try {
            DB::transaction(function() use ($request) {
                $sales = Sale::whereIn('id', $request->sales)->with('client')->get();
                $total = $sales->sum('total');
                $client = $sales->first()->client;
                $documentType = $request->document_type ?: (strlen($client->document) === 11 ? 'factura' : 'boleta');

                // Validaciones SUNAT
                if ($documentType === 'factura' && strlen($client->document) !== 11) {
                    throw new \Exception('No se puede emitir una Factura a un cliente sin RUC (11 dígitos).');
                }

                if ($documentType === 'boleta' && ($client->document === '0' || $client->document === '' || $client->document === '00000000') && $total > 700) {
                    throw new \Exception('No se puede emitir una Boleta sin identificación que supere los S/ 700.00.');
                }

                // Asegurar que exista al menos un registro en settings para evitar duplicados si está vacío
                if (DB::table('settings')->count() === 0) {
                    DB::table('settings')->insert([
                        'id' => 1,
                        'factura_count' => 0,
                        'boleta_count' => 0
                    ]);
                }

                // Obtener correlativo directamente de la base de datos de manera segura según el tipo
                $counter_column = ($documentType === 'factura') ? 'factura_count' : 'boleta_count';
                $current_count = DB::table('settings')->lockForUpdate()->value($counter_column) ?? 0;
                $next_number = str_pad($current_count + 1, 8, "0", STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'number' => $next_number,
                    'client_id' => $request->client_id,
                    'document_type' => $documentType,
                    'date' => $request->date,
                    'total' => $total,
                    'status' => 'Emitida',
                    'notes' => $request->notes
                ]);

                $invoice->sales()->attach($request->sales);

                // Increment correct count in settings
                DB::table('settings')->increment($counter_column);

                // Emitir comprobante electrónico
                $this->apisunatService->emitInvoice($invoice);
                
                $request->merge(['new_invoice_id' => $invoice->id]);
            });

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Comprobante emitido con éxito.',
                    'pdf_url' => route('invoices.local_pdf', ['invoice' => $request->new_invoice_id])
                ]);
            }

            return redirect()->route('invoices.index')->with('message', 'Comprobante emitido y enviado a SUNAT con éxito.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['status' => false, 'error' => $e->getMessage()]);
            }
            return back()->with('error', 'Error al emitir el comprobante: ' . $e->getMessage());
        }
    }

    public function resend(Request $request, Invoice $invoice)
    {
        try {
            $result = $this->apisunatService->emitInvoice($invoice);

            if ($result['status'] === 'SENT') {
                if ($request->ajax()) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Comprobante reenviado con éxito.',
                    ]);
                }
                return redirect()->route('invoices.index')->with('message', 'Comprobante reenviado con éxito.');
            }

            if ($result['status'] === 'SKIPPED') {
                if ($request->ajax()) {
                    return response()->json(['status' => false, 'error' => $result['message'] ?? 'Omitido']);
                }
                return back()->with('error', $result['message'] ?? 'Omitido');
            }

            $msg = 'Error al reenviar: ' . ($result['message'] ?? 'Respuesta desconocida');
            if ($request->ajax()) {
                return response()->json(['status' => false, 'error' => $msg]);
            }
            return back()->with('error', $msg);
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['status' => false, 'error' => 'Error al procesar el reenvío: ' . $e->getMessage()]);
            }
            return back()->with('error', 'Error al procesar el reenvío: ' . $e->getMessage());
        }
    }

    public function localPdf(Invoice $invoice)
    {
        $invoice->load(['sales.details.product', 'client']);

        $igvRate = 0.18;
        $client = $invoice->client;
        $docType = $invoice->document_type ?: (strlen((string) $client->document) === 11 ? 'factura' : 'boleta');
        $isFactura = $docType === 'factura';

        $company = config('apisunat.company');
        $rucEmisor = $company['ruc'] ?? '20615250024';
        $razonSocial = $company['legal_name'] ?? 'SUBUZ SAC';
        $direccionEmisor = $company['address'] ?? '';

        $billing = BranchElectronicBillingConfig::first();
        $serieDefault = $isFactura
            ? ($billing->series_factura ?? config('apisunat.series.factura', 'F001'))
            : ($billing->series_boleta ?? config('apisunat.series.boleta', 'B001'));

        if ($invoice->electronic_invoice_series && $invoice->electronic_invoice_number !== null && $invoice->electronic_invoice_number !== '') {
            $serie = $invoice->electronic_invoice_series;
            $numeroFiscal = (int) $invoice->electronic_invoice_number;
        } else {
            $serie = $serieDefault;
            $numeroFiscal = (int) ltrim((string) $invoice->number, '0') ?: (int) $invoice->number;
        }
        $serieNumero = $serie . '-' . str_pad((string) $numeroFiscal, 8, '0', STR_PAD_LEFT);

        $tituloComprobante = $isFactura ? 'FACTURA DE VENTA ELECTRÓNICA' : 'BOLETA DE VENTA ELECTRÓNICA';

        $lines = [];
        $idx = 1;
        foreach ($invoice->sales as $sale) {
            foreach ($sale->details as $detail) {
                $pu = (float) $detail->price;
                $qty = (float) $detail->quantity;
                $importeConIgv = round($pu * $qty, 2);
                $valorVenta = round($importeConIgv / (1 + $igvRate), 2);
                $vu = $qty > 0 ? round($valorVenta / $qty, 3) : 0;
                $lines[] = [
                    'n' => $idx++,
                    'desc' => $detail->product->name ?? 'Producto',
                    'um' => 'NIU',
                    'qty' => $qty,
                    'vu' => $vu,
                    'pu' => $pu,
                    'dto' => 0.0,
                    'vv' => $valorVenta,
                ];
            }
        }

        $opGravada = round((float) $invoice->total / (1 + $igvRate), 2);
        $igvTotal = round((float) $invoice->total - $opGravada, 2);
        $nombreCliente = trim((string) ($client->business_name ?: $client->name)) ?: '-';
        $docCliente = $client->document ?: '';
        $dirCliente = $client->address ?: '-';

        $fpdf = new Fpdf;
        $fpdf->SetMargins(10, 10, 10);
        $fpdf->SetAutoPageBreak(true, 18);
        $fpdf->AddPage();

        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        $pdf = function (string $s) {
            $t = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);

            return $t !== false ? $t : utf8_decode($s);
        };

        $fpdf->SetDrawColor(0, 0, 0);
        $fpdf->SetLineWidth(0.2);

        // Logo (izquierda)
        $logoY = 10;
        if (file_exists(public_path('assets/images/logo.jpg'))) {
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, $logoY, 32);
        }

        // Caja tipo SUNAT (derecha)
        $boxW = 78;
        $boxH = 28;
        $boxX = 210 - 10 - $boxW;
        $fpdf->Rect($boxX, $logoY, $boxW, $boxH);
        $fpdf->SetXY($boxX, $logoY + 2);
        $fpdf->SetFont('Montserrat', 'B', 9);
        $fpdf->MultiCell($boxW, 4, $pdf($tituloComprobante), 0, 'C');
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->SetX($boxX);
        $fpdf->Cell($boxW, 4, $pdf('RUC: ' . $rucEmisor), 0, 1, 'C');
        $fpdf->SetFont('Montserrat', 'B', 9);
        $fpdf->SetX($boxX);
        $fpdf->Cell($boxW, 5, $pdf($serieNumero), 0, 1, 'C');

        $y = max($logoY + 32, $logoY + $boxH + 4);
        $fpdf->SetY($y);

        // Razón social emisor
        $fpdf->SetFont('Montserrat', 'B', 11);
        $fpdf->Cell(190, 6, $pdf($razonSocial), 0, 1, 'L');
        $fpdf->SetFont('Montserrat', '', 7);
        $fpdf->Cell(190, 4, $pdf($razonSocial), 0, 1, 'L');
        if ($direccionEmisor !== '') {
            $fpdf->MultiCell(190, 3, $pdf(mb_strtoupper($direccionEmisor, 'UTF-8')), 0, 'L');
        }

        $fpdf->Ln(3);

        // Cliente / metadata
        $fpdf->SetFont('Montserrat', 'B', 8);
        $meta = [
            ['Fecha de emisión:', $invoice->date->format('d/m/Y')],
            ['Señor(es):', $nombreCliente],
            ['RUC/DNI:', $docCliente !== '' ? $docCliente : '-'],
            ['Dirección:', $dirCliente],
            ['Moneda:', 'PEN'],
        ];
        foreach ($meta as [$lab, $val]) {
            $fpdf->SetFont('Montserrat', 'B', 8);
            $fpdf->Cell(38, 5, $pdf($lab), 0, 0, 'L');
            $fpdf->SetFont('Montserrat', '', 8);
            $fpdf->MultiCell(152, 5, $pdf($val), 0, 'L');
        }

        $fpdf->Ln(2);

        // Tabla ítems
        $w = [8, 78, 10, 14, 22, 22, 12, 24];
        $hHeader = 7;
        $rowH = 6;

        $fpdf->SetFont('Montserrat', 'B', 7);
        $fpdf->SetFillColor(0, 0, 0);
        $fpdf->SetTextColor(255, 255, 255);
        $headers = ['Item', 'Descripción', 'U.M.', 'Cant.', 'V.U.', 'P.U.', 'Dcto.', 'Valor de venta'];
        foreach ($headers as $i => $h) {
            $align = $i >= 3 ? 'R' : 'C';
            if ($i === 1) {
                $align = 'L';
            }
            $fpdf->Cell($w[$i], $hHeader, $pdf($h), 1, 0, $align, true);
        }
        $fpdf->Ln();

        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->SetFillColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', '', 7);

        foreach ($lines as $ln) {
            $desc = mb_strlen($ln['desc'], 'UTF-8') > 48 ? mb_substr($ln['desc'], 0, 45, 'UTF-8') . '...' : $ln['desc'];
            $fpdf->Cell($w[0], $rowH, (string) $ln['n'], 1, 0, 'C');
            $fpdf->Cell($w[1], $rowH, $pdf($desc), 1, 0, 'L');
            $fpdf->Cell($w[2], $rowH, $pdf($ln['um']), 1, 0, 'C');
            $fpdf->Cell($w[3], $rowH, number_format($ln['qty'], 2, '.', ''), 1, 0, 'R');
            $fpdf->Cell($w[4], $rowH, 'S/ ' . number_format($ln['vu'], 3, '.', ''), 1, 0, 'R');
            $fpdf->Cell($w[5], $rowH, 'S/ ' . number_format($ln['pu'], 2, '.', ''), 1, 0, 'R');
            $fpdf->Cell($w[6], $rowH, 'S/ ' . number_format($ln['dto'], 2, '.', ''), 1, 0, 'R');
            $fpdf->Cell($w[7], $rowH, 'S/ ' . number_format($ln['vv'], 2, '.', ''), 1, 1, 'R');
        }

        $fpdf->Ln(1);

        // Totales (alineados a la derecha)
        $tw = 62;
        $lx = 10 + 190 - $tw;
        $fpdf->SetX($lx);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(34, 5, $pdf('Op. gravada:'), 0, 0, 'R');
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(28, 5, 'S/ ' . number_format($opGravada, 2, '.', ''), 0, 1, 'R');

        $fpdf->SetX($lx);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(34, 5, 'I.G.V.:', 0, 0, 'R');
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(28, 5, 'S/ ' . number_format($igvTotal, 2, '.', ''), 0, 1, 'R');

        $fpdf->SetX($lx);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(34, 5, $pdf('Op. exonerada:'), 0, 0, 'R');
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(28, 5, 'S/ 0.00', 0, 1, 'R');

        $fpdf->SetX($lx);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(34, 5, $pdf('Op. inafecta:'), 0, 0, 'R');
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(28, 5, 'S/ 0.00', 0, 1, 'R');

        $fpdf->Ln(1);
        $fpdf->Line($lx, $fpdf->GetY(), $lx + $tw, $fpdf->GetY());
        $fpdf->Ln(0.5);

        $fpdf->SetX($lx);
        $fpdf->SetFont('Montserrat', 'B', 9);
        $fpdf->Cell(34, 6, $pdf('Importe total:'), 0, 0, 'R');
        $fpdf->Cell(28, 6, 'S/ ' . number_format((float) $invoice->total, 2, '.', ''), 0, 1, 'R');

        $fpdf->Ln(4);

        // Observación y letras
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->Cell(24, 5, $pdf('Observación:'), 0, 0, 'L');
        $fpdf->SetFont('Montserrat', '', 8);
        $obs = $invoice->notes ?: '';
        $fpdf->MultiCell(166, 5, $pdf($obs), 0, 'L');

        $fpdf->Ln(1);
        $fpdf->SetFont('Montserrat', 'B', 7);
        $son = 'SON: ' . strtoupper(SolesEnLetras::format((float) $invoice->total));
        $fpdf->MultiCell(190, 4, $pdf($son), 0, 'L');

        $fpdf->Ln(2);
        $qrY = $fpdf->GetY();
        $qrSize = 28;
        $hasQr = false;
        $qrPath = null;

        try {
            $qrUrl = URL::signedRoute('invoices.public_detail', ['invoice' => $invoice], null, true);
            $qrOptions = new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'scale' => 3,
                'eccLevel' => EccLevel::M,
            ]);
            $qr = new QRCode($qrOptions);
            $qrPath = tempnam(sys_get_temp_dir(), 'invqr_') . '.png';
            $qr->render($qrUrl, $qrPath);
            if (is_file($qrPath) && filesize($qrPath) > 0) {
                $fpdf->Image($qrPath, 10, $qrY, $qrSize);
                $hasQr = true;
            }
        } catch (\Throwable $e) {
            if ($qrPath !== null && is_file($qrPath)) {
                @unlink($qrPath);
            }
            $qrPath = null;
        }

        $leyenda = 'Representación impresa del comprobante electrónico, consulte en https://www.sunat.gob.pe/legislacion/comprobante-de-pago';
        $textLeft = $hasQr ? (10 + $qrSize + 4) : 10;
        $textW = $hasQr ? (190 - $qrSize - 4) : 190;
        $fpdf->SetXY($textLeft, $qrY);
        $fpdf->SetFont('Montserrat', '', 6);
        $fpdf->MultiCell($textW, 3.5, $pdf($leyenda), 0, 'L');

        $followY = max($hasQr ? ($qrY + $qrSize + 1) : ($qrY + 2), $fpdf->GetY() + 2);
        $fpdf->SetY($followY);

        $fpdf->SetFont('Montserrat', '', 7);
        if ($hasQr) {
            $fpdf->SetX(10);
            $fpdf->Cell($qrSize, 4, $pdf($serieNumero), 0, 0, 'C');
            $fpdf->Cell(190 - $qrSize, 4, $pdf('Pedidos: ' . $invoice->sales->pluck('order')->filter()->implode(', ')), 0, 1, 'R');
        } else {
            $fpdf->SetX(10);
            $fpdf->Cell(95, 4, $pdf($serieNumero), 0, 0, 'L');
            $fpdf->Cell(95, 4, $pdf('Pedidos: ' . $invoice->sales->pluck('order')->filter()->implode(', ')), 0, 1, 'R');
        }

        $fpdf->Ln(2);
        $fpdf->SetFont('Montserrat', '', 6);
        $fpdf->Cell(190, 4, $pdf('Documento de referencia interna. El PDF oficial SUNAT se obtiene desde el enlace del proveedor de facturación.'), 0, 1, 'C');

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $name = 'Comprobante_' . $invoice->number . '.pdf';
        $pdfBody = $fpdf->Output('S', $name);

        if ($qrPath !== null && is_file($qrPath)) {
            @unlink($qrPath);
        }

        return response($pdfBody)
            ->header('Content-Type', 'application/pdf');
    }

    /**
     * Detalle del comprobante para consulta pública (URL firmada en el código QR del PDF).
     */
    public function publicDetail(Invoice $invoice)
    {
        $invoice->load([
            'client',
            'sales.details.product',
            'sales.payment_method',
        ]);

        $igvRate = 0.18;
        $opGravada = round((float) $invoice->total / (1 + $igvRate), 2);
        $igvTotal = round((float) $invoice->total - $opGravada, 2);
        $company = config('apisunat.company');
        $client = $invoice->client;
        $docType = $invoice->document_type ?: (strlen((string) ($client->document ?? '')) === 11 ? 'factura' : 'boleta');
        $docLabel = $docType === 'factura' ? 'Factura de venta electrónica' : 'Boleta de venta electrónica';

        $isFactura = $docType === 'factura';
        $billing = BranchElectronicBillingConfig::first();
        $serieDefault = $isFactura
            ? ($billing->series_factura ?? config('apisunat.series.factura', 'F001'))
            : ($billing->series_boleta ?? config('apisunat.series.boleta', 'B001'));
        if ($invoice->electronic_invoice_series && $invoice->electronic_invoice_number !== null && $invoice->electronic_invoice_number !== '') {
            $serie = $invoice->electronic_invoice_series;
            $numeroFiscal = (int) $invoice->electronic_invoice_number;
        } else {
            $serie = $serieDefault;
            $numeroFiscal = (int) ltrim((string) $invoice->number, '0') ?: (int) $invoice->number;
        }
        $serieNumero = $serie . '-' . str_pad((string) $numeroFiscal, 8, '0', STR_PAD_LEFT);

        return view('invoices.public-detail', compact(
            'invoice',
            'opGravada',
            'igvTotal',
            'company',
            'igvRate',
            'docLabel',
            'serieNumero'
        ));
    }

    public function showPdf(Invoice $invoice)
    {
        if (!$invoice->electronic_invoice_pdf_a4_url) {
            return back()->with('error', 'El PDF no está disponible para este comprobante.');
        }
        return redirect()->away($invoice->electronic_invoice_pdf_a4_url);
    }

    public function downloadXml(Invoice $invoice)
    {
        if (!$invoice->electronic_invoice_xml_url) {
            return back()->with('error', 'El XML no está disponible.');
        }
        return redirect()->away($invoice->electronic_invoice_xml_url);
    }

    public function downloadCdr(Invoice $invoice)
    {
        if (!$invoice->electronic_invoice_cdr_url) {
            return back()->with('error', 'El CDR no está disponible.');
        }
        return redirect()->away($invoice->electronic_invoice_cdr_url);
    }
}
