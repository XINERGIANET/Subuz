<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Invoice;
use App\Models\Client;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::whereHas('invoices');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $sales = $query->with(['invoices', 'client'])->latest('date')->paginate(15);
        $clients = Client::all();
        $selected_client = $request->client_id ? Client::find($request->client_id) : null;

        return view('invoices.index', compact('sales', 'clients', 'selected_client'));
    }

    public function pending(Request $request)
    {
        // For the pending view, we usually want to see sales grouped by client or at least filterable
        $query = Sale::whereDoesntHave('invoices')
            ->where('status', '!=', 'Anulado');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $sales = $query->with('client')->latest('date')->get();
        $clients = Client::all();
        $selected_client = $request->client_id ? Client::find($request->client_id) : null;

        return view('invoices.pending', compact('sales', 'clients', 'selected_client'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|string|unique:invoices,number',
            'date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'sales' => 'required|array',
            'sales.*' => 'exists:sales,id'
        ], [
            'number.unique' => 'El número de factura ya existe.',
            'sales.required' => 'Debe seleccionar al menos una venta para facturar.'
        ]);

        try {
            DB::transaction(function() use ($request) {
                $sales = Sale::whereIn('id', $request->sales)->get();
                $total = $sales->sum('total');

                $invoice = Invoice::create([
                    'number' => $request->number,
                    'client_id' => $request->client_id,
                    'date' => $request->date,
                    'total' => $total,
                    'status' => 'Emitida',
                    'notes' => $request->notes
                ]);

                $invoice->sales()->attach($request->sales);
            });

            return redirect()->route('invoices.index')->with('message', 'Factura creada con éxito.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear la factura: ' . $e->getMessage());
        }
    }
}
