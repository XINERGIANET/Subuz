<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Client;
use Codedge\Fpdf\Fpdf\Fpdf;

class QuoteController extends Controller
{
    public function index()
    {
        $quotes = Quote::orderBy('id', 'desc')->paginate(15);
        return view('quotes.index', compact('quotes'));
    }

    public function create()
    {
        $products = Product::orderBy('name', 'asc')->get();
        return view('quotes.create', compact('products'));
    }

    public function store(Request $request)
    {
        $total = 0;
        $products = [];
        if($request->has('products')){
            foreach($request->products as $p){
                $prod = Product::find($p['id']);
                if($prod){
                    $qty = isset($p['quantity']) ? intval($p['quantity']) : 1;
                    $products[] = [
                        'id' => $p['id'],
                        'name' => $prod->name,
                        'price' => $p['price'],
                        'quantity' => $qty
                    ];
                    $total += (floatval($p['price']) * $qty);
                }
            }
        }

        $quote = Quote::create([
            'date' => $request->date,
            'client_name' => $request->client_name,
            'client_ruc' => $request->client_ruc,
            'client_address' => $request->client_address,
            'products' => $products,
            'total' => $total
        ]);

        return response()->json([
            'status' => true,
            'url' => route('quotes.pdf', $quote->id)
        ]);
    }

    public function approve(Quote $quote)
    {
        $products = is_string($quote->products) ? json_decode($quote->products, true) : $quote->products;
        $cartItems = [];
        $total = 0;
        
        if (is_array($products)) {
            foreach($products as $p) {
                $prodModel = Product::find($p['id']);
                $price = floatval($p['price']);
                $qty = isset($p['quantity']) ? intval($p['quantity']) : 1;
                $cartItems[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'price' => number_format($price, 2, '.', ''),
                    'original_price' => number_format($price, 2, '.', ''),
                    'quantity' => $qty,
                    'amount' => number_format($price * $qty, 2, '.', ''),
                    'special' => false,
                    'is_loanable' => $prodModel ? $prodModel->is_loanable : 0,
                    'is_loaned' => false
                ];
                $total += ($price * $qty);
            }
        }
        
        $subtotal = $total / 1.18;
        $igv = $total - $subtotal;

        $cart = [
            'items' => $cartItems,
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'igv' => number_format($igv, 2, '.', ''),
            'total' => number_format($total, 2, '.', '')
        ];
        
        session()->put('cart', $cart);
        session()->put('active_quote_id', $quote->id);

        $client = \App\Models\Client::where('document', $quote->client_ruc)->first();
        $url = route('sales.create');
        if ($client) {
            $url .= '?client_id=' . $client->id;
        }

        return response()->json([
            'status' => true,
            'url' => $url
        ]);
    }

    public function edit(Quote $quote)
    {
        $products = \App\Models\Product::all();
        return view('quotes.edit', compact('quote', 'products'));
    }

    public function update(Request $request, Quote $quote)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'client_name' => 'required|string',
            'client_ruc' => 'required|string',
            'client_address' => 'required|string',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.price' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $items = [];
        $total = 0;
        foreach ($request->products as $p) {
            $prod = \App\Models\Product::find($p['id']);
            $qty = isset($p['quantity']) ? intval($p['quantity']) : 1;
            $items[] = [
                'id' => $prod->id,
                'name' => $prod->name,
                'price' => $p['price'],
                'quantity' => $qty
            ];
            $total += (floatval($p['price']) * $qty);
        }

        $quote->update([
            'date' => $request->date,
            'client_name' => $request->client_name,
            'client_ruc' => $request->client_ruc,
            'client_address' => $request->client_address,
            'products' => $items,
            'total' => $total
        ]);

        return response()->json([
            'status' => true,
            'url' => route('quotes.pdf', $quote->id)
        ]);
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();
        return response()->json(['status' => true]);
    }

    public function pdf(Quote $quote)
    {
        $fpdf = new Fpdf;
        $fpdf->AddPage();
        
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        // Logo
        if(file_exists(public_path('assets/images/logo.jpg'))){
            $fpdf->Image(public_path('assets/images/logo.jpg'), 145, 10, 45);
        }

        // Header Text
        $fpdf->SetFont('Montserrat', 'B', 9);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(100, 5, utf8_decode('RUC: 20615250024'), 0, 1);
        $fpdf->SetFont('Montserrat', 'B', 11);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(100, 6, utf8_decode('GRUPO DTS SAC'), 0, 1);
        $fpdf->SetFont('Montserrat', '', 9);
        $fpdf->SetTextColor(100, 100, 100);
        $fpdf->Cell(100, 5, utf8_decode('Chiclayo - Lambayeque - Perú'), 0, 1);
        $fpdf->Cell(100, 5, utf8_decode('Teléfono: 920488526 - 920381594'), 0, 1);
        
        // Date under logo
        $fpdf->SetXY(145, 30);
        $meses = ['01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril','05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto','09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'];
        $date = date('d', strtotime($quote->date)) . ' de ' . $meses[date('m', strtotime($quote->date))] . ' de ' . date('Y', strtotime($quote->date));
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(45, 5, utf8_decode('Chiclayo, ' . $date), 0, 1, 'R');

        $fpdf->Ln(15);
        $fpdf->SetX(10);

        // Title
        $fpdf->SetFont('Montserrat', 'B', 18);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('C O T I Z A C I Ó N'), 0, 1, 'C');
        
        $fpdf->SetDrawColor(200, 200, 200);
        $fpdf->Line(75, $fpdf->GetY(), 135, $fpdf->GetY());
        $fpdf->Ln(8);

        // Client info
        $fpdf->SetFillColor(245, 247, 250);
        $fpdf->SetDrawColor(220, 225, 230);
        $fpdf->SetLineWidth(0.2);
        
        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->SetTextColor(50, 50, 50);
        $fpdf->Cell(25, 8, utf8_decode('  Sr(es):'), 'LT', 0, 'L', true);
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->Cell(165, 8, utf8_decode(' ' . $quote->client_name), 'TR', 1, 'L', true);

        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(25, 8, utf8_decode('  RUC / DNI:'), 'L', 0, 'L', true);
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->Cell(165, 8, utf8_decode(' ' . $quote->client_ruc), 'R', 1, 'L', true);

        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(25, 8, utf8_decode('  Dirección:'), 'LB', 0, 'L', true);
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->Cell(165, 8, utf8_decode(' ' . $quote->client_address), 'RB', 1, 'L', true);
        
        $fpdf->Ln(8);

        // Body text
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetTextColor(60, 60, 60);
        $text1 = utf8_decode("Sr(es) ".$quote->client_name.", por medio del presente adjunto la cotización de los productos a solicitud.");
        $fpdf->MultiCell(190, 6, $text1);
        $fpdf->Ln(3);

        $text2 = utf8_decode("Subuz, es una empresa construida con disciplina, enfoque y estándares altos. Sabemos que nuestros clientes no buscan solo un proveedor, buscan seguridad, puntualidad y excelencia. Por eso trabajamos cada día para garantizar procesos limpios, productos seguros y un servicio que cumpla o supere lo prometido.");
        $fpdf->MultiCell(190, 6, $text2);
        $fpdf->Ln(3);

        $fpdf->Cell(190, 6, utf8_decode("A continuación, adjunto los precios:"), 0, 1);
        $fpdf->Ln(4);

        // Table
        $fpdf->SetDrawColor(2, 93, 166);
        $fpdf->SetLineWidth(0.3);
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->SetX(20);
        $fpdf->Cell(80, 9, utf8_decode('PRODUCTO'), 1, 0, 'C', true);
        $fpdf->Cell(20, 9, utf8_decode('CANT.'), 1, 0, 'C', true);
        $fpdf->Cell(30, 9, utf8_decode('PRECIO UNIT.'), 1, 0, 'C', true);
        $fpdf->Cell(30, 9, utf8_decode('TOTAL'), 1, 1, 'C', true);

        $fpdf->SetTextColor(50, 50, 50);
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetFillColor(248, 249, 250);
        $fill = false;

        $products = is_string($quote->products) ? json_decode($quote->products, true) : $quote->products;
        $grandTotal = 0;
        if(is_array($products)){
            foreach($products as $p){
                $qty = isset($p['quantity']) ? $p['quantity'] : 1;
                $rowTotal = floatval($p['price']) * $qty;
                $grandTotal += $rowTotal;

                $fpdf->SetX(20);
                $fpdf->Cell(80, 9, utf8_decode('  ' . $p['name']), 1, 0, 'L', $fill);
                $fpdf->Cell(20, 9, $qty, 1, 0, 'C', $fill);
                $fpdf->Cell(30, 9, 'S/ ' . number_format($p['price'], 2), 1, 0, 'C', $fill);
                $fpdf->Cell(30, 9, 'S/ ' . number_format($rowTotal, 2), 1, 1, 'C', $fill);
                $fill = !$fill;
            }
        }
        
        $fpdf->SetX(20);
        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(130, 9, utf8_decode('TOTAL A PAGAR:  '), 1, 0, 'R', false);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(30, 9, 'S/ ' . number_format($grandTotal, 2), 1, 1, 'C', false);
        $fpdf->SetTextColor(50, 50, 50);

        $fpdf->Ln(12);
        $fpdf->SetTextColor(60, 60, 60);
        $fpdf->Cell(190, 6, utf8_decode("Quedo atento a cualquier solicitud, saludos y buen día."), 0, 1);
        
        $fpdf->Ln(25);
        $fpdf->SetDrawColor(150, 150, 150);
        $fpdf->Line(10, $fpdf->GetY(), 70, $fpdf->GetY());
        $fpdf->Ln(2);

        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->Cell(190, 6, utf8_decode("Diego Hurtado"), 0, 1);
        $fpdf->SetFont('Montserrat', '', 9);
        $fpdf->SetTextColor(100, 100, 100);
        $fpdf->Cell(190, 5, utf8_decode("Tel. 961281857"), 0, 1);

        $name = "Cotizacion_00" . $quote->id . "_" . str_replace(' ', '_', $quote->client_name) . ".pdf";
        if (ob_get_level() > 0) ob_end_clean();
        $fpdf->Output('I', $name);
    }
}
