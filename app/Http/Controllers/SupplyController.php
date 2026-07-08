<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplyController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'stock' => 'required|numeric',
            'unit' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first(),
            ]);
        }

        Supply::create($request->only('name', 'stock', 'unit'));

        return response()->json(['status' => true]);
    }

    public function edit(Supply $supply)
    {
        return response()->json($supply);
    }

    public function update(Request $request, Supply $supply)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'stock' => 'required|numeric',
            'unit' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first(),
            ]);
        }

        $supply->update($request->only('name', 'stock', 'unit'));

        return response()->json(['status' => true]);
    }

    public function destroy(Supply $supply)
    {
        $supply->delete();

        return response()->json(['status' => true]);
    }

    public function purchaseHistory(Supply $supply)
    {
        $expenses = \App\Models\Expense::with('payment_method')
            ->where('description', 'like', "Compra de stock: {$supply->name} (Insumo)%")
            ->orderBy('date', 'desc')
            ->get()
            ->map(function($e) {
                preg_match('/Cant: ([\d\.]+)/', $e->description, $matches);
                $quantity = $matches[1] ?? '0';

                return [
                    'date' => $e->date->format('d/m/Y H:i'),
                    'real_date' => $e->real_date ? \Carbon\Carbon::parse($e->real_date)->format('d/m/Y') : '-',
                    'quantity' => $quantity,
                    'amount' => $e->amount,
                    'payment_method' => $e->payment_method ? $e->payment_method->name : '-',
                    'receipt_number' => $e->receipt_number ?: '-',
                    'operation_number' => $e->operation_number ?: '-'
                ];
            });

        return response()->json($expenses);
    }
}
