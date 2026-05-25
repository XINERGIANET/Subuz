<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Expense;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Models\Week;
use App\Models\Payment;
use App\Models\Cashbox;
use App\Models\CashboxMovement;
use App\Models\Client;
use Codedge\Fpdf\Fpdf\Fpdf;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['payment_method', 'client', 'movements']);

        $query->when($request->client_id, function ($q, $client_id) {
            return $q->where('client_id', $client_id);
        })
            ->when($request->type, function ($q, $type) {
                if ($type == 'Pago pendiente') {
                    return $q->where(function ($sq) {
                        $sq->where('type', 'Pago pendiente')
                            ->orWhere(function ($ssq) {
                                $ssq->where('type', 'Contado')
                                    ->where('paid', 0)
                                    ->whereHas('movements', function ($mq) {
                                        $mq->where('type', 'debt');
                                    });
                            });
                    });
                }
                return $q->where('type', $type);
            })
            ->when($request->start_date, function ($q, $start_date) {
                return $q->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date, function ($q, $end_date) {
                return $q->whereDate('date', '<=', $end_date);
            })
            ->when($request->payment_method_id, function ($q, $pm_id) {
                if ($pm_id == 'credit') {
                    return $q->where('type', 'Credito');
                }
                return $q->where('payment_method_id', $pm_id);
            })
            ->when($request->delivery_status, function ($q, $delivery_status) {
                if ($delivery_status == 'delivered') {
                    return $q->where(function ($sq) {
                        $sq->where('paid', 1)
                            ->orWhere('type', 'Pago pendiente')
                            ->orWhereHas('movements', function ($mq) {
                                $mq->where('type', 'debt');
                            });
                    });
                } elseif ($delivery_status == 'pending') {
                    return $q->where('status', '!=', 'Anulado')
                        ->where('paid', 0)
                        ->where('type', '!=', 'Pago pendiente')
                        ->whereDoesntHave('movements', function ($mq) {
                            $mq->where('type', 'debt');
                        });
                }
            });

        if (auth()->check() && (auth()->user()->hasRole('despachador') || auth()->user()->hasRole('asistente'))) {
            $query->whereDate('date', now());
        } elseif (!$request->start_date && !$request->end_date) {
            if (!$request->client_id && !$request->type && !$request->delivery_status) {
                $query->whereDate('date', now());
            }
        }

        // Totals for delivered sales
        $total_sales_query = (clone $query)
            ->where('status', '!=', 'Anulado')
            ->where(function ($q) {
                $q->where('paid', 1)
                    ->orWhere('type', 'Pago pendiente')
                    ->orWhereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            });

        if (auth()->check() && auth()->user()->hasRole('despachador')) {
            $total_sales_query->whereHas('movements', function ($mq) {
                $mq->where('user_id', auth()->id())->whereIn('type', ['paid', 'debt']);
            });
        }

        $formatMetricSale = function ($sale) {
            return [
                'date' => optional($sale->date)->format('d/m/Y'),
                'guide' => $sale->guide,
                'client' => optional($sale->client)->name ?? 'Consumidor Final',
                'type' => $sale->type,
                'amount' => (float) $sale->total,
            ];
        };

        $total_sales = $total_sales_query->sum('total');
        $total_sales_details = (clone $total_sales_query)
            ->with('client')
            ->latest('date')
            ->get()
            ->map($formatMetricSale)
            ->values();
        // Total cash for dispatchers (actual cash payments for filtered sales)
        $total_cash_query = (clone $query)->where('status', '!=', 'Anulado');
        if (auth()->check() && auth()->user()->hasRole('despachador')) {
            $total_cash_query->whereHas('movements', function ($mq) {
                $mq->where('user_id', auth()->id())->whereIn('type', ['paid', 'debt']);
            });
        }
        $payment_total_sale_ids = $total_cash_query->pluck('id');

        $payments_for_totals = Payment::with(['sale.client', 'payment_method'])
            ->whereIn('payments.sale_id', $payment_total_sale_ids)
            ->latest('date')
            ->get();

        $unique_payments_for_totals = $payments_for_totals
            ->unique(function ($payment) {
                return implode('|', [
                    $payment->sale_id,
                    $payment->payment_method_id,
                    number_format((float) $payment->amount, 2, '.', '')
                ]);
            })
            ->values();

        $total_cash = $unique_payments_for_totals
            ->where('payment_method_id', 1) // 1 = Efectivo
            ->sum('amount');

        // Total by payment methods for dispatchers
        $payment_totals = $unique_payments_for_totals
            ->groupBy('payment_method_id')
            ->map(function ($payments, $payment_method_id) {
                $payment_method = optional($payments->first()->payment_method);

                return (object) [
                    'id' => $payment_method_id,
                    'name' => $payment_method->name ?? 'N/A',
                    'total' => $payments->sum('amount'),
                ];
            })
            ->values();

        $payment_total_details = $unique_payments_for_totals
            ->groupBy('payment_method_id')
            ->map(function ($payments) {
                return $payments->map(function ($payment) {
                    return [
                        'date' => optional($payment->date)->format('d/m/Y'),
                        'guide' => optional($payment->sale)->guide ?? 'Venta',
                        'client' => optional(optional($payment->sale)->client)->name ?? 'Consumidor Final',
                        'type' => optional($payment->sale)->type ?? 'Pago',
                        'amount' => (float) $payment->amount,
                    ];
                })->values();
            });

        // Annulled sales for metrics and modal
        $annulled_sales_query = (clone $query)->where('status', 'Anulado');
        $annulled_count = $annulled_sales_query->count();
        $annulled_sales = $annulled_sales_query->get();

        // Count of delivered orders today (for dispatchers and metrics)
        $delivered_count_query = (clone $query)
            ->where('status', '!=', 'Anulado')
            ->where(function ($q) {
                $q->where('paid', 1)
                    ->orWhere('type', 'Pago pendiente')
                    ->orWhereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            });

        if (auth()->check() && auth()->user()->hasRole('despachador')) {
            $delivered_count_query->whereHas('movements', function ($mq) {
                $mq->where('user_id', auth()->id())->whereIn('type', ['paid', 'debt']);
            });
        }

        $delivered_count = $delivered_count_query->count();

        if (auth()->check() && auth()->user()->hasRole('despachador')) {
            $query->where('status', '!=', 'Anulado')
                ->where('paid', 0)
                ->where('type', '!=', 'Pago pendiente')
                ->whereDoesntHave('movements', function ($mq) {
                    $mq->where('type', 'debt');
                });
        }

        // Total No Entregado (Not annulled, unpaid, not 'Pago pendiente', and no debt movement)
        $not_delivered_query = (clone $query)
            ->where('status', '!=', 'Anulado')
            ->where('paid', 0)
            ->where('type', '!=', 'Pago pendiente')
            ->whereDoesntHave('movements', function ($mq) {
                $mq->where('type', 'debt');
            });
        $total_not_delivered = (clone $not_delivered_query)->sum('total');
        $total_not_delivered_details = (clone $not_delivered_query)
            ->with('client')
            ->latest('date')
            ->get()
            ->map($formatMetricSale)
            ->values();

        // Total No Pagado (Not annulled, unpaid, but delivered via 'Pago pendiente' or debt movement)
        $unpaid_delivered_query = (clone $query)
            ->where('status', '!=', 'Anulado')
            ->where('paid', 0)
            ->where(function ($sq) {
                $sq->where('type', 'Pago pendiente')
                    ->orWhereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            });
        $total_unpaid_delivered = (clone $unpaid_delivered_query)->sum('total');
        $total_unpaid_delivered_details = (clone $unpaid_delivered_query)
            ->with('client')
            ->latest('date')
            ->get()
            ->map($formatMetricSale)
            ->values();

        // Total by selected type (Breakdown)
        $total_by_type = null;
        $total_by_type_delivered = 0;
        $total_by_type_not_delivered = 0;

        if ($request->type) {
            $type_query = (clone $query)->where('status', '!=', 'Anulado');
            $total_by_type = (clone $type_query)->sum('total');

            // Delivered of this type
            $total_by_type_delivered = (clone $type_query)
                ->where(function ($q) {
                    $q->where('paid', 1)
                        ->orWhere('type', 'Pago pendiente')
                        ->orWhereHas('movements', function ($mq) {
                            $mq->where('type', 'debt');
                        });
                })->sum('total');

            // Not Delivered of this type
            $total_by_type_not_delivered = (clone $type_query)
                ->where('paid', 0)
                ->where('type', '!=', 'Pago pendiente')
                ->whereDoesntHave('movements', function ($mq) {
                    $mq->where('type', 'debt');
                })->sum('total');
        }

        $sales = $query->latest('date')->paginate(10);

        $payment_methods = PaymentMethod::all();
        $cashbox = Cashbox::currentOpen();
        $selected_client = $request->client_id ? Client::find($request->client_id) : null;

        $products = Product::all();
        return view('sales.index', compact('sales', 'total_sales', 'total_sales_details', 'total_cash', 'payment_totals', 'payment_total_details', 'total_not_delivered', 'total_not_delivered_details', 'total_unpaid_delivered', 'total_unpaid_delivered_details', 'total_by_type', 'total_by_type_delivered', 'total_by_type_not_delivered', 'annulled_count', 'annulled_sales', 'payment_methods', 'cashbox', 'products', 'selected_client', 'delivered_count'));
    }

    public function create()
    {
        $sale_count = DB::table('settings')->pluck('sale_count')->first();
        $order = 'V' . str_pad($sale_count + 1, 4, "0", STR_PAD_LEFT);
        $products = Product::all();
        return view('sales.create', compact('order', 'products'));
    }

    public function store(Request $request)
    {

        $cart = session()->get('cart') ? session()->get('cart') : [
            'items' => [],
            'subtotal' => '0.00',
            'igv' => '0.00',
            'total' => '0.00'
        ];


        $validator = Validator::make($request->all(), [
            'guide' => 'nullable|unique:sales',
            'type' => 'required|in:Contado,Credito',
            'date' => 'required|date',
            'client_id' => 'required'
        ]);

        $validator->after(function ($validator) use ($cart) {

            if (count($cart['items']) == 0) {
                $validator->errors()->add('cart', 'Debe agregar por lo menos 1 producto');
            }

        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $sale_count = DB::table('settings')->pluck('sale_count')->first();
        $order = 'V' . str_pad($sale_count + 1, 4, "0", STR_PAD_LEFT);

        $week = Week::where('number', now()->format('W'))->first();

        if (!$week) {
            $number = now()->format('W');
            $year = now()->format('Y');
            $start_date = date('Y-m-d', strtotime("{$year}W{$number}"));
            $end_date = date('Y-m-d', strtotime("{$year}W{$number} +6 days"));
            $week = Week::create([
                'number' => $number,
                'year' => $year,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
        }

        $client = Client::find($request->client_id);
        $isNewClient = $client->sales()->where('status', '!=', 'Anulado')->count() == 0;

        $sale = Sale::create([
            'order' => $order,
            'date' => $request->date . ' ' . now()->format('H:i:s'),
            'week_id' => $week->id,
            'guide' => $request->guide,
            'type' => $client->type,
            'payment_method_id' => null,
            'client_id' => $request->client_id,
            'total' => $cart['total'],
            'debt' => $request->type == 'Credito' ? $cart['total'] : 0,
            'paid' => 0
        ]);

        foreach ($cart['items'] as $item) {
            $product = Product::find($item['id']);

            if ($product && $product->stock !== null) {
                if ($product->reduces_stock) {
                    $product->decrement('stock', $item['quantity']);
                }
            }

            SaleDetail::create([
                'sale_id' => $sale->id,
                'product_id' => $item['id'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'special' => $item['special'] ? 1 : 0,
            ]);
        }

        DB::table('settings')->update([
            'sale_count' => $sale_count + 1
        ]);

        session()->forget('cart');

        return response()->json(['status' => true]);
    }

    public function details(Request $request, Sale $sale)
    {

        if (!$sale) {
            return response()->json([
                'status' => false
            ]);
        }

        return response()->json([
            'status' => true,
            'photo' => $sale->photo ? url('photo-view/' . $sale->photo) : null,
            'total' => number_format($sale->total, 2, '.', ''),
            'details' => optional($sale)->details()->with('product')->get()
        ]);
    }

    public function edit(Request $request, Sale $sale)
    {
        return response()->json([
            'status' => true,
            'id' => $sale->id,
            'date' => optional($sale->date)->format('Y-m-d'),
            'type' => $sale->type,
            'details' => optional($sale)->details()->with('product')->get()
        ]);
    }

    public function update(Request $request, Sale $sale)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'type' => 'required|string',
            'details.id.*' => 'required|integer',
            'details.price.*' => 'required|numeric',
            'details.quantity.*' => 'required|integer'
        ]);

        $total = 0;

        $details = $request->details;

        foreach ($details['id'] as $key => $value) {

            $detail = SaleDetail::findOrFail($value);

            $price = $details['price'][$key];
            $quantity = $details['quantity'][$key];

            $detail->update([
                'price' => $price,
                'quantity' => $quantity
            ]);

            $total += floatval($price) * intval($quantity);
        }

        $debt = $sale->debt;
        if ($request->type == 'Credito') {
            $total_paid = $sale->payments()->sum('amount');
            $debt = $total - $total_paid;
        } else {
            $debt = 0;
        }

        $sale->update([
            'date' => $request->date,
            'type' => $request->type,
            'total' => $total,
            'debt' => $debt > 0 ? $debt : 0
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => 'El formulario no ha sido validado'
            ]);
        }

        return response()->json(['status' => true]);
    }

    public function destroy(Request $request, Sale $sale)
    {
        $isAdmin = auth()->user()->hasRole('admin');

        if ($isAdmin) {
            try {
                DB::transaction(function () use ($sale) {
                    // Restore stock
                    foreach ($sale->details as $detail) {
                        $product = $detail->product;
                        if ($product && $product->stock !== null && $product->reduces_stock) {
                            $product->increment('stock', $detail->quantity);
                        }
                    }

                    // Delete related records
                    $sale->details()->delete();
                    $sale->payments()->delete();
                    $sale->movements()->delete();
                    if (Schema::hasTable('invoice_sale')) {
                        $sale->invoices()->detach();
                    }
                    $sale->delete();
                });

                return response()->json(['status' => true]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'error' => 'Error al eliminar la venta: ' . $e->getMessage()
                ]);
            }
        }

        $isDelivered = $sale->paid || $sale->type == 'Pago pendiente' || $sale->movements->where('type', 'debt')->isNotEmpty();

        if ($isDelivered) {
            return response()->json([
                'status' => false,
                'error' => 'No se puede anular una venta que ya ha sido entregada.'
            ]);
        }

        try {
            DB::transaction(function () use ($sale) {
                foreach ($sale->details as $detail) {
                    $product = $detail->product;
                    if ($product && $product->stock !== null && $product->reduces_stock) {
                        $product->increment('stock', $detail->quantity);
                    }
                }
                $sale->update(['status' => 'Anulado']);
            });

            return response()->json(['status' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al anular la venta: ' . $e->getMessage()
            ]);
        }
    }

    public function excel(Request $request)
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = "ReporteVentas_" . now()->format('dm') . ".xlsx";
        return Excel::download(new SalesExport($request), $name);
    }

    public function pdf(Request $request)
    {
        $query = Sale::with(['payment_method', 'client'])
            ->when($request->client_id, function ($q, $client_id) {
                return $q->where('client_id', $client_id);
            })
            ->when($request->type, function ($q, $type) {
                return $q->where('type', $type);
            })
            ->when($request->start_date, function ($q, $start_date) {
                return $q->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date, function ($q, $end_date) {
                return $q->whereDate('date', '<=', $end_date);
            })
            ->when($request->is_pending, function ($q) {
                return $q->whereIn('type', ['Contado', 'Pago pendiente'])
                    ->where('paid', 0)
                    ->whereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            })
            ->when($request->is_credit, function ($q) {
                return $q->where('type', 'Credito')
                    ->where('paid', 0)
                    ->whereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            });

        if (!$request->start_date && !$request->end_date && !$request->is_pending && !$request->is_credit && !$request->client_id) {
            $query->whereDate('date', now());
        }

        $sales = $query->latest('date')->get();

        $fpdf = new Fpdf;
        $fpdf->AddPage();

        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        if (file_exists(public_path('assets/images/logo.jpg'))) {
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 30);
        }

        $fpdf->SetFont('Montserrat', 'B', 16);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('REPORTE DE VENTAS'), 0, 1, 'C');

        $period = "Filtro: ";
        if ($request->is_pending)
            $period = "REPORTE DE VENTAS PENDIENTES DE PAGO";
        elseif ($request->is_credit)
            $period = "REPORTE DE CRÉDITOS PENDIENTES";
        else {
            if ($request->start_date)
                $period .= "Desde " . date('d/m/Y', strtotime($request->start_date)) . " ";
            if ($request->end_date)
                $period .= "Hasta " . date('d/m/Y', strtotime($request->end_date));
            if (!$request->start_date && !$request->end_date)
                $period .= "Ventas de hoy (" . now()->format('d/m/Y') . ")";
        }

        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(190, 8, utf8_decode($period), 0, 1, 'C');
        $fpdf->Ln(10);

        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);

        $isDebtReport = $request->is_pending || $request->is_credit;

        $fpdf->Cell(25, 10, utf8_decode('GUÍA'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('FECHA'), 1, 0, 'C', true);
        $fpdf->Cell(60, 10, utf8_decode('CLIENTE'), 1, 0, 'C', true);
        $fpdf->Cell(30, 10, utf8_decode('TIPO'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('PAGO'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode($isDebtReport ? 'DEUDA' : 'TOTAL'), 1, 1, 'C', true);

        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->SetFont('Montserrat', '', 9);
        $total = 0;

        foreach ($sales as $sale) {
            if ($sale->status == 'Anulado')
                continue;

            $amount = $isDebtReport ? $sale->debt : $sale->total;

            $fpdf->Cell(25, 8, utf8_decode($sale->guide), 1, 0, 'C');
            $fpdf->Cell(25, 8, $sale->date->format('d/m/Y'), 1, 0, 'C');
            $fpdf->Cell(60, 8, utf8_decode(optional($sale->client)->name ?? 'Consumidor Final'), 1, 0, 'L');
            $fpdf->Cell(30, 8, utf8_decode($sale->type), 1, 0, 'C');
            $fpdf->Cell(25, 8, $sale->paid ? 'SI' : 'NO', 1, 0, 'C');
            $fpdf->Cell(25, 8, 'S/' . number_format($amount, 2), 1, 1, 'R');
            $total += $amount;
        }

        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(165, 10, utf8_decode($isDebtReport ? 'TOTAL DEUDA' : 'TOTAL EN VENTAS'), 1, 0, 'R');
        $fpdf->Cell(25, 10, 'S/' . number_format($total, 2), 1, 1, 'R');

        $fpdf->Ln(10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(190, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $name = "ReporteVentas_" . now()->format('dm') . ".pdf";
        if (ob_get_level() > 0)
            ob_end_clean();
        $fpdf->Output('D', $name);
    }

    public function markDispatch(Request $request, Sale $sale)
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('despachador') && !auth()->user()->hasRole('asistente')) {
            return response()->json([
                'status' => false,
                'error' => 'No autorizado'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'paid' => 'required|boolean',
            'guide' => 'required|string|max:255',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:204800'
        ], [
            'guide.required' => 'El número de guía es obligatorio para despachar.',
            'photo.required' => 'La foto de evidencia es obligatoria para despachar.',
            'photo.image' => 'El archivo debe ser una imagen válida.',
            'photo.max' => 'La foto excede el límite permitido (200 MB).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        if ($sale->paid) {
            return response()->json([
                'status' => false,
                'error' => 'La venta ya esta marcada como pagada.'
            ]);
        }

        $cashbox = Cashbox::currentOpen();
        if (!$cashbox) {
            return response()->json([
                'status' => false,
                'error' => 'Debe aperturar caja antes de registrar el despacho.'
            ]);
        }

        // Handle Photo Upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'dispatch_' . $sale->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('dispatches', $filename, 'public');
        }

        try {
            DB::transaction(function () use ($request, $sale, $cashbox, $photoPath) {
                $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

                if ($request->paid && $sale->paid) {
                    throw new \Exception('La venta ya esta marcada como pagada.');
                }

                $commonUpdate = [
                    'guide' => $request->guide,
                    'photo' => $photoPath
                ];

                if ($request->paid) {
                    $payments = $request->payments;
                    $totalPaid = 0;

                    if (!$payments || count($payments) == 0) {
                        throw new \Exception("Debe agregar por lo menos un método de pago.");
                    }

                    foreach ($payments as $payment) {
                        if (!isset($payment['method_id']) || !isset($payment['amount'])) {
                            throw new \Exception("Datos de pago incompletos.");
                        }
                        $totalPaid += floatval($payment['amount']);
                    }

                    if (abs($totalPaid - floatval($sale->total)) > 0.01) {
                        throw new \Exception("La suma de los pagos (S/" . number_format($totalPaid, 2) . ") debe ser igual al total de la venta (S/" . number_format($sale->total, 2) . ").");
                    }

                    // Update sale as paid
                    $sale->update(array_merge($commonUpdate, [
                        'type' => 'Contado',
                        'payment_method_id' => $payments[0]['method_id'], // Use first as primary reference
                        'debt' => 0,
                        'paid' => 1
                    ]));

                    // Register all movements and payments
                    foreach ($payments as $payment) {
                        Payment::create([
                            'sale_id' => $sale->id,
                            'payment_method_id' => $payment['method_id'],
                            'amount' => $payment['amount'],
                            'date' => now()
                        ]);

                        CashboxMovement::create([
                            'cashbox_id' => $cashbox->id,
                            'sale_id' => $sale->id,
                            'user_id' => auth()->id(),
                            'payment_method_id' => $payment['method_id'],
                            'type' => 'paid',
                            'amount' => $payment['amount'],
                            'date' => now()
                        ]);
                    }

                } else {
                    if (!auth()->user()->hasRole('despachador') && !auth()->user()->hasRole('admin') && !auth()->user()->hasRole('asistente')) {
                        throw new \Exception("Solo el despachador, asistente o administrador puede marcar pendiente de pago.");
                    }

                    $sale->update(array_merge($commonUpdate, [
                        'type' => $sale->type,
                        'payment_method_id' => null,
                        'debt' => $sale->total,
                        'paid' => 0
                    ]));

                    $hasDebt = CashboxMovement::where('sale_id', $sale->id)
                        ->where('type', 'debt')
                        ->exists();

                    if (!$hasDebt) {
                        CashboxMovement::create([
                            'cashbox_id' => $cashbox->id,
                            'sale_id' => $sale->id,
                            'user_id' => auth()->id(),
                            'payment_method_id' => null,
                            'type' => 'debt',
                            'amount' => $sale->total,
                            'date' => now()
                        ]);
                    }
                }
            });

            return response()->json([
                'status' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateDeliveryStatus(Request $request, Sale $sale)
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('despachador') && !auth()->user()->hasRole('asistente')) {
            return response()->json([
                'status' => false,
                'error' => 'No autorizado'
            ], 403);
        }

        $status = $request->status; // 1 = Entregado, 0 = No entregado

        if ($status == 1) {
            // Confirm delivery without payment (mark as Pago pendiente)
            return $this->markDispatch($request->merge(['paid' => 0]), $sale);
        } else {
            // Revert delivery: allow re-dispatch with new guide/photo.
            DB::transaction(function () use ($sale) {
                // Remove movements and payments created during dispatch.
                CashboxMovement::where('sale_id', $sale->id)
                    ->whereIn('type', ['paid', 'debt'])
                    ->delete();
                Payment::where('sale_id', $sale->id)->delete();

                $sale->update([
                    'type' => $sale->type,
                    'paid' => 0,
                    'debt' => $sale->total,
                    'payment_method_id' => null,
                    'photo' => null
                ]);
            });

            return response()->json(['status' => true]);
        }
    }
    public function addDetail(Request $request, Sale $sale)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        $product = Product::find($request->product_id);

        $price = $product->price;
        $special_price = \App\Models\Price::where('client_id', $sale->client_id)
            ->where('product_id', $product->id)
            ->first();
        if ($special_price) {
            $price = $special_price->price;
        }

        $quantity = $request->quantity;

        DB::transaction(function () use ($sale, $product, $price, $quantity) {
            // Check if product already in sale
            $detail = $sale->details()->where('product_id', $product->id)->first();

            if ($detail) {
                // If it exists, we update quantity and price (in case special price changed)
                $detail->increment('quantity', $quantity);
                $detail->update(['price' => $price]);
            } else {
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'price' => $price,
                    'quantity' => $quantity,
                    'special' => 0
                ]);
            }

            // Calculate precise new total
            $realTotal = $sale->details()->get()->sum(function ($d) {
                return $d->price * $d->quantity;
            });

            $diff = $realTotal - $sale->total;

            // Update sale total
            $sale->update(['total' => $realTotal]);

            // If it was a credit sale, update debt
            if ($sale->type == 'Credito' && !$sale->paid) {
                // Determine new debt based on total - paid
                $totalPaid = $sale->payments()->sum('amount');
                $newDebt = max(0, $realTotal - $totalPaid);
                $sale->update(['debt' => $newDebt]);

                // Update or create debt movement in cashbox
                $movement = $sale->movements()->where('type', 'debt')->first();
                if ($movement) {
                    $movement->update(['amount' => $newDebt]);
                }
            }

            // Update stock
            if ($product->stock !== null && $product->reduces_stock) {
                $product->decrement('stock', $quantity);
            }
        });

        return response()->json(['status' => true]);
    }

    public function updateDetail(Request $request, Sale $sale, SaleDetail $detail)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        if ($detail->sale_id !== $sale->id) {
            return response()->json(['status' => false, 'error' => 'Detalle no válido']);
        }

        DB::transaction(function () use ($sale, $detail, $request) {
            $product = $detail->product;
            $oldQuantity = $detail->quantity;
            $newQuantity = $request->quantity;

            // Update detail
            $detail->update([
                'quantity' => $newQuantity,
                'price' => $request->price
            ]);

            // Calculate precise new total
            $realTotal = $sale->details()->get()->sum(function ($d) {
                return $d->price * $d->quantity;
            });

            // Update sale total
            $sale->update(['total' => $realTotal]);

            // If it was a credit sale, update debt
            if ($sale->type == 'Credito' && !$sale->paid) {
                $totalPaid = $sale->payments()->sum('amount');
                $newDebt = max(0, $realTotal - $totalPaid);
                $sale->update(['debt' => $newDebt]);

                $movement = $sale->movements()->where('type', 'debt')->first();
                if ($movement) {
                    $movement->update(['amount' => $newDebt]);
                }
            }

            // Update stock
            if ($product && $product->stock !== null && $product->reduces_stock) {
                $diff = $newQuantity - $oldQuantity;
                $product->decrement('stock', $diff);
            }
        });

        return response()->json(['status' => true]);
    }

    public function destroyDetail(Request $request, Sale $sale, SaleDetail $detail)
    {
        // Ensure detail belongs to sale
        if ($detail->sale_id !== $sale->id) {
            return response()->json(['status' => false, 'error' => 'Detalle no válido']);
        }

        DB::transaction(function () use ($sale, $detail) {
            $product = $detail->product;
            $quantity = $detail->quantity;

            // Delete the detail
            $detail->delete();

            // Calculate precise new total
            $realTotal = $sale->details()->get()->sum(function ($d) {
                return $d->price * $d->quantity;
            });

            // Update sale total
            $sale->update(['total' => $realTotal]);

            // If it was a credit sale, update debt
            if ($sale->type == 'Credito' && !$sale->paid) {
                $totalPaid = $sale->payments()->sum('amount');
                $newDebt = max(0, $realTotal - $totalPaid);
                $sale->update(['debt' => $newDebt]);

                // Update debt movement if exists
                $movement = $sale->movements()->where('type', 'debt')->first();
                if ($movement) {
                    if ($newDebt > 0) {
                        $movement->update(['amount' => $newDebt]);
                    } else {
                        $movement->update(['amount' => 0]);
                    }
                }
            }

            // Update stock
            if ($product && $product->stock !== null && $product->reduces_stock) {
                $product->increment('stock', $quantity);
            }
        });

        return response()->json(['status' => true]);
    }
    public function reportData(Request $request)
    {
        $start_date = $request->start_date ?? now()->toDateString();
        $end_date = $request->end_date ?? now()->toDateString();

        // Base query for sales in period
        $sales_period = Sale::whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->where('status', '!=', 'Anulado')
            ->when($request->client_id, function ($q, $client_id) {
                return $q->where('client_id', $client_id);
            })
            ->when($request->type, function ($q, $type) {
                if ($type == 'Pago pendiente') {
                    return $q->where(function ($sq) {
                        $sq->where('type', 'Pago pendiente')
                            ->orWhere(function ($ssq) {
                                $ssq->where('type', 'Contado')
                                    ->where('paid', 0)
                                    ->whereHas('movements', function ($mq) {
                                        $mq->where('type', 'debt');
                                    });
                            });
                    });
                }
                return $q->where('type', $type);
            })
            ->when($request->payment_method_id, function ($q, $pm_id) {
                if ($pm_id == 'credit') {
                    return $q->where('type', 'Credito');
                }
                return $q->where('payment_method_id', $pm_id);
            })
            ->when($request->delivery_status, function ($q, $delivery_status) {
                if ($delivery_status == 'delivered') {
                    return $q->where(function ($sq) {
                        $sq->where('paid', 1)
                            ->orWhere('type', 'Pago pendiente')
                            ->orWhereHas('movements', function ($mq) {
                                $mq->where('type', 'debt');
                            });
                    });
                } elseif ($delivery_status == 'pending') {
                    return $q->where('status', '!=', 'Anulado')
                        ->where('paid', 0)
                        ->where('type', '!=', 'Pago pendiente')
                        ->whereDoesntHave('movements', function ($mq) {
                            $mq->where('type', 'debt');
                        });
                }
            });

        // 1. Total Entregado (Sum of total for sales delivered in period)
        $total_delivered = (clone $sales_period)
            ->where(function ($q) {
                $q->where('paid', 1)
                    ->orWhere('type', 'Pago pendiente')
                    ->orWhereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            })->sum('total');

        // 2. No Pagado (Unpaid but delivered)
        $total_unpaid_delivered = (clone $sales_period)
            ->where('paid', 0)
            ->where(function ($sq) {
                $sq->where('type', 'Pago pendiente')
                    ->orWhereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            })
            ->sum('total');

        // 3. No Entregado (Unpaid and not delivered)
        $total_not_delivered = (clone $sales_period)
            ->where('paid', 0)
            ->where('type', '!=', 'Pago pendiente')
            ->whereDoesntHave('movements', function ($mq) {
                $mq->where('type', 'debt');
            })
            ->sum('total');

        // 4. Movements in period (including payments for previous sales)
        $movements_query = CashboxMovement::whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with(['payment_method', 'sale']);

        if ($request->client_id || $request->type || $request->payment_method_id || $request->delivery_status) {
            $movements_query->whereIn('sale_id', (clone $sales_period)->pluck('id'));
        }
        $movements = $movements_query->get();

        $methods_totals = [];
        $previous_days_payments = [];
        $previous_days_payments_count = 0;

        foreach ($movements as $mov) {
            if ($mov->type == 'paid' || $mov->type == 'income') {
                $method_name = optional($mov->payment_method)->name ?? 'Manual';
                if (!isset($methods_totals[$method_name]))
                    $methods_totals[$method_name] = 0;
                $methods_totals[$method_name] += $mov->amount;

                // Check if it's a payment for a sale from previous days
                if ($mov->type == 'paid' && $mov->sale_id && $mov->sale) {
                    $sale_date = $mov->sale->date->toDateString();
                    if ($sale_date < $start_date) {
                        $previous_days_payments_count++;
                        $previous_days_payments[] = [
                            'client' => optional($mov->sale->client)->name ?? 'Consumidor Final',
                            'guide' => $mov->sale->guide ?? $mov->sale->order,
                            'sale_date' => $mov->sale->date->format('d/m/Y'),
                            'amount' => number_format($mov->amount, 2, '.', ''),
                            'method' => $method_name
                        ];
                    }
                }
            }
        }

        // 5. Expenses in period
        $expenses_query = Expense::whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with('payment_method');

        if ($request->client_id || $request->type || $request->payment_method_id || $request->delivery_status) {
            $expenses_query->whereId(0); // Ignore expenses if filtering
        }
        $expenses = $expenses_query->get();

        $total_expenses = $expenses->sum('amount');
        $expenses_methods_totals = [];

        foreach ($expenses as $exp) {
            $method_name = optional($exp->payment_method)->name ?? 'S/M';
            if (!isset($expenses_methods_totals[$method_name]))
                $expenses_methods_totals[$method_name] = 0;
            $expenses_methods_totals[$method_name] += $exp->amount;
        }

        return response()->json([
            'status' => true,
            'period' => [
                'start' => date('d/m/Y', strtotime($start_date)),
                'end' => date('d/m/Y', strtotime($end_date))
            ],
            'summary' => [
                'total_delivered' => number_format($total_delivered, 2, '.', ''),
                'total_unpaid_delivered' => number_format($total_unpaid_delivered, 2, '.', ''),
                'total_not_delivered' => number_format($total_not_delivered, 2, '.', ''),
                'total_expenses' => number_format($total_expenses, 2, '.', ''),
                'methods' => $methods_totals,
                'expenses_methods' => $expenses_methods_totals,
                'previous_payments_count' => $previous_days_payments_count,
                'previous_payments' => $previous_days_payments
            ]
        ]);
    }

    public function reportPdf(Request $request)
    {
        $start_date = $request->start_date ?? now()->toDateString();
        $end_date = $request->end_date ?? now()->toDateString();

        // Base query for sales in period
        $sales_period = Sale::whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->where('status', '!=', 'Anulado')
            ->when($request->client_id, function ($q, $client_id) {
                return $q->where('client_id', $client_id);
            })
            ->when($request->type, function ($q, $type) {
                if ($type == 'Pago pendiente') {
                    return $q->where(function ($sq) {
                        $sq->where('type', 'Pago pendiente')
                            ->orWhere(function ($ssq) {
                                $ssq->where('type', 'Contado')
                                    ->where('paid', 0)
                                    ->whereHas('movements', function ($mq) {
                                        $mq->where('type', 'debt');
                                    });
                            });
                    });
                }
                return $q->where('type', $type);
            })
            ->when($request->payment_method_id, function ($q, $pm_id) {
                if ($pm_id == 'credit') {
                    return $q->where('type', 'Credito');
                }
                return $q->where('payment_method_id', $pm_id);
            })
            ->when($request->delivery_status, function ($q, $delivery_status) {
                if ($delivery_status == 'delivered') {
                    return $q->where(function ($sq) {
                        $sq->where('paid', 1)
                            ->orWhere('type', 'Pago pendiente')
                            ->orWhereHas('movements', function ($mq) {
                                $mq->where('type', 'debt');
                            });
                    });
                } elseif ($delivery_status == 'pending') {
                    return $q->where('status', '!=', 'Anulado')
                        ->where('paid', 0)
                        ->where('type', '!=', 'Pago pendiente')
                        ->whereDoesntHave('movements', function ($mq) {
                            $mq->where('type', 'debt');
                        });
                }
            });

        $total_delivered = (clone $sales_period)
            ->where(function ($q) {
                $q->where('paid', 1)
                    ->orWhere('type', 'Pago pendiente')
                    ->orWhereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            })->sum('total');

        $total_unpaid_delivered = (clone $sales_period)
            ->where('paid', 0)
            ->where(function ($sq) {
                $sq->where('type', 'Pago pendiente')
                    ->orWhereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            })
            ->sum('total');

        $total_not_delivered = (clone $sales_period)
            ->where('paid', 0)
            ->where('type', '!=', 'Pago pendiente')
            ->whereDoesntHave('movements', function ($mq) {
                $mq->where('type', 'debt');
            })
            ->sum('total');

        $movements_query = CashboxMovement::whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with(['payment_method', 'sale']);

        if ($request->client_id || $request->type || $request->payment_method_id || $request->delivery_status) {
            $movements_query->whereIn('sale_id', (clone $sales_period)->pluck('id'));
        }
        $movements = $movements_query->get();

        $expenses_query = Expense::whereBetween('date', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
            ->with('payment_method');

        if ($request->client_id || $request->type || $request->payment_method_id || $request->delivery_status) {
            $expenses_query->whereId(0); // Ignore expenses if filtering
        }
        $expenses = $expenses_query->get();
        $total_expenses = $expenses->sum('amount');

        $expenses_methods_totals = [];
        foreach ($expenses as $exp) {
            $method_name = optional($exp->payment_method)->name ?? 'S/M';
            if (!isset($expenses_methods_totals[$method_name]))
                $expenses_methods_totals[$method_name] = 0;
            $expenses_methods_totals[$method_name] += $exp->amount;
        }

        $fpdf = new Fpdf;
        $fpdf->AddPage();

        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        if (file_exists(public_path('assets/images/logo.jpg'))) {
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 30);
        }

        $fpdf->SetFont('Montserrat', 'B', 16);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('REPORTE GENERAL DE VENTAS'), 0, 1, 'C');

        $period = "Periodo: " . date('d/m/Y', strtotime($start_date)) . " al " . date('d/m/Y', strtotime($end_date));
        $fpdf->SetFont('Montserrat', '', 10);
        $fpdf->SetTextColor(80, 80, 80);
        $fpdf->Cell(190, 8, utf8_decode($period), 0, 1, 'C');
        $fpdf->Ln(5);

        // Summary Section
        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('RESUMEN GENERAL'), 0, 1, 'L');

        $fpdf->SetFont('Montserrat', '', 11);
        $fpdf->SetTextColor(0, 0, 0);

        $fpdf->Cell(60, 8, utf8_decode('Total Entregado:'), 0, 0, 'L');
        $fpdf->Cell(30, 8, 'S/ ' . number_format($total_delivered, 2), 0, 1, 'R');

        $fpdf->Cell(60, 8, utf8_decode('No Pagado:'), 0, 0, 'L');
        $fpdf->Cell(30, 8, 'S/ ' . number_format($total_unpaid_delivered, 2), 0, 1, 'R');

        $fpdf->Cell(60, 8, utf8_decode('No Entregado:'), 0, 0, 'L');
        $fpdf->Cell(30, 8, 'S/ ' . number_format($total_not_delivered, 2), 0, 1, 'R');

        $fpdf->Cell(60, 8, utf8_decode('Total Gastos:'), 0, 0, 'L');
        $fpdf->Cell(30, 8, 'S/ ' . number_format($total_expenses, 2), 0, 1, 'R');
        $fpdf->Ln(5);

        // Ingresos por método de pago
        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('INGRESOS POR MÉTODO DE PAGO'), 0, 1, 'L');

        $methods_totals = [];
        $prev_payments_data = [];
        foreach ($movements as $mov) {
            if ($mov->type == 'paid' || $mov->type == 'income') {
                $method_name = optional($mov->payment_method)->name ?? 'Manual';
                if (!isset($methods_totals[$method_name]))
                    $methods_totals[$method_name] = 0;
                $methods_totals[$method_name] += $mov->amount;

                if ($mov->type == 'paid' && $mov->sale_id && $mov->sale) {
                    $sale_date = $mov->sale->date->toDateString();
                    if ($sale_date < $start_date) {
                        $prev_payments_data[] = [
                            'client' => optional($mov->sale->client)->name ?? 'Consumidor Final',
                            'guide' => $mov->sale->guide ?? $mov->sale->order,
                            'sale_date' => $mov->sale->date->format('d/m/Y'),
                            'amount' => $mov->amount,
                            'method' => $method_name
                        ];
                    }
                }
            }
        }

        $fpdf->SetFont('Montserrat', '', 11);
        $fpdf->SetTextColor(0, 0, 0);
        foreach ($methods_totals as $name => $amount) {
            $fpdf->Cell(60, 8, utf8_decode($name . ':'), 0, 0, 'L');
            $fpdf->Cell(30, 8, 'S/ ' . number_format($amount, 2), 0, 1, 'R');
        }

        // Egresos por método de pago
        if (count($expenses_methods_totals) > 0) {
            $fpdf->Ln(5);
            $fpdf->SetFont('Montserrat', 'B', 12);
            $fpdf->SetTextColor(166, 2, 2);
            $fpdf->Cell(190, 10, utf8_decode('EGRESOS POR MÉTODO DE PAGO'), 0, 1, 'L');

            $fpdf->SetFont('Montserrat', '', 11);
            $fpdf->SetTextColor(0, 0, 0);
            foreach ($expenses_methods_totals as $name => $amount) {
                $fpdf->Cell(60, 8, utf8_decode($name . ':'), 0, 0, 'L');
                $fpdf->Cell(30, 8, 'S/ ' . number_format($amount, 2), 0, 1, 'R');
            }
        }

        if (count($prev_payments_data) > 0) {
            $fpdf->Ln(5);
            $fpdf->SetFont('Montserrat', 'B', 12);
            $fpdf->SetTextColor(28, 115, 71);
            $fpdf->Cell(190, 10, utf8_decode('DETALLE DE PAGOS DE VENTAS ANTERIORES'), 0, 1, 'L');

            $fpdf->SetFillColor(230, 245, 235);
            $fpdf->SetTextColor(0, 0, 0);
            $fpdf->SetFont('Montserrat', 'B', 9);

            $fpdf->Cell(25, 8, utf8_decode('GUÍA'), 1, 0, 'C', true);
            $fpdf->Cell(25, 8, utf8_decode('F. VENTA'), 1, 0, 'C', true);
            $fpdf->Cell(70, 8, utf8_decode('CLIENTE'), 1, 0, 'C', true);
            $fpdf->Cell(35, 8, utf8_decode('MÉTODO'), 1, 0, 'C', true);
            $fpdf->Cell(35, 8, utf8_decode('MONTO'), 1, 1, 'C', true);

            $fpdf->SetFont('Montserrat', '', 8);
            foreach ($prev_payments_data as $pp) {
                $fpdf->Cell(25, 7, utf8_decode($pp['guide']), 1, 0, 'C');
                $fpdf->Cell(25, 7, $pp['sale_date'], 1, 0, 'C');
                $fpdf->Cell(70, 7, utf8_decode(substr($pp['client'], 0, 40)), 1, 0, 'L');
                $fpdf->Cell(35, 7, utf8_decode($pp['method']), 1, 0, 'C');
                $fpdf->Cell(35, 7, 'S/ ' . number_format($pp['amount'], 2), 1, 1, 'R');
            }
        }
        $fpdf->Ln(10);



        // Detailed Sales Table (Sales DELIVERED in period)
        $sales_generated = (clone $sales_period)
            ->where(function ($q) {
                $q->where('paid', 1)
                    ->orWhere('type', 'Pago pendiente')
                    ->orWhereHas('movements', function ($mq) {
                        $mq->where('type', 'debt');
                    });
            })
            ->with(['client', 'payments.payment_method'])
            ->get();

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 10, utf8_decode('DETALLE DE VENTAS ENTREGADAS EN EL PERIODO'), 0, 1, 'L');

        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetFont('Montserrat', 'B', 10);

        $fpdf->Cell(25, 10, utf8_decode('GUÍA'), 1, 0, 'C', true);
        $fpdf->Cell(25, 10, utf8_decode('FECHA'), 1, 0, 'C', true);
        $fpdf->Cell(70, 10, utf8_decode('CLIENTE'), 1, 0, 'C', true);
        $fpdf->Cell(35, 10, utf8_decode('TIPO'), 1, 0, 'C', true);
        $fpdf->Cell(35, 10, utf8_decode('TOTAL'), 1, 1, 'C', true);

        $fpdf->SetTextColor(0, 0, 0);
        $fpdf->SetFont('Montserrat', '', 9);

        foreach ($sales_generated as $sale) {
            $clientName = utf8_decode(optional($sale->client)->name ?? 'Consumidor Final');

            // Determine type text based on payment status and methods
            $typeText = $sale->type;
            if ($sale->type == 'Contado' || $sale->type == 'Pago pendiente') {
                if ($sale->paid) {
                    $methods = $sale->payments->map(function ($p) {
                        return optional($p->payment_method)->name;
                    })->filter()->unique()->implode(', ');
                    $typeText = $methods ?: 'Contado';
                } else {
                    $typeText = 'Pago pendiente';
                }
            }

            $x = $fpdf->GetX();
            $y = $fpdf->GetY();
            $rowHeight = 12; // Fixed height to accommodate long names

            // Page break check
            if ($y + $rowHeight > 275) {
                $fpdf->AddPage();
                $y = $fpdf->GetY();
                $x = $fpdf->GetX();
            }

            $fpdf->Cell(25, $rowHeight, utf8_decode($sale->guide), 1, 0, 'C');
            $fpdf->Cell(25, $rowHeight, $sale->date->format('d/m/Y'), 1, 0, 'C');

            // MultiCell for client name
            $fpdf->SetXY($x + 50, $y);
            $fpdf->MultiCell(70, 4, $clientName, 0, 'L');

            // Draw the border for the client cell manually
            $fpdf->SetXY($x + 50, $y);
            $fpdf->Cell(70, $rowHeight, '', 1, 0);

            $fpdf->SetXY($x + 120, $y);
            $fpdf->Cell(35, $rowHeight, utf8_decode($typeText), 1, 0, 'C');
            $fpdf->Cell(35, $rowHeight, 'S/ ' . number_format($sale->total, 2), 1, 1, 'R');
        }

        $fpdf->Ln(10);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->Cell(190, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i')), 0, 1, 'R');

        $name = "ReporteGeneral_" . now()->format('dm') . ".pdf";
        if (ob_get_level() > 0)
            ob_end_clean();
        $fpdf->Output('D', $name);
    }
}

