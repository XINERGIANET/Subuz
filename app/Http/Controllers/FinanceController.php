<?php

namespace App\Http\Controllers;

use App\Models\BankLoan;
use App\Models\LoanPayment;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FinanceController extends Controller
{
    public function index()
    {
        $loans = BankLoan::with('payments')->latest()->get();
        
        $events = [];
        foreach($loans as $loan){
            $startDate = \Carbon\Carbon::parse($loan->start_date);
            $amountPerInstallment = $loan->monthly_amount ?? ($loan->total_amount / $loan->installments_total);
            $paidInstallments = $loan->payments->pluck('installment_number')->unique()->toArray();

            for ($i = 1; $i <= $loan->installments_total; $i++) {
                $dueDate = $startDate->copy()->addMonths($i - 1);
                $isPaid = in_array($i, $paidInstallments);

                $events[] = [
                    'title' => ($loan->currency == 'USD' ? '$' : 'S/') . number_format($amountPerInstallment, 2) . ' - ' . $loan->bank_name,
                    'start' => $dueDate->toDateString(),
                    'url' => route('finances.show', $loan->id),
                    'backgroundColor' => $isPaid ? '#2fb344' : ($dueDate->isPast() ? '#d63939' : '#206bc4'),
                    'borderColor' => $isPaid ? '#2fb344' : ($dueDate->isPast() ? '#d63939' : '#206bc4'),
                    'allDay' => true,
                    'extendedProps' => [
                        'bank' => $loan->bank_name,
                        'amount' => number_format($amountPerInstallment, 2),
                        'status' => $isPaid ? 'Pagado' : 'Pendiente'
                    ]
                ];
            }
        }

        return view('finances.index', compact('loans', 'events'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'installments_total' => 'required|integer|min:1',
            'monthly_amount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'currency' => 'required|string|in:PEN,USD'
        ]);

        BankLoan::create($data);

        return redirect()->route('finances.index')->with('success', 'Crédito registrado correctamente.');
    }

    public function show($id)
    {
        $loan = BankLoan::with(['payments' => function($q) {
            $q->orderBy('payment_date', 'asc')->with('payment_method');
        }])->findOrFail($id);

        $installments = [];
        $startDate = Carbon::parse($loan->start_date);
        $amountPerInstallment = $loan->monthly_amount ?? ($loan->total_amount / $loan->installments_total);

        for ($i = 1; $i <= $loan->installments_total; $i++) {
            $dueDate = $startDate->copy()->addMonths($i - 1);
            $payments = $loan->payments->where('installment_number', $i);

            $status = 'Pendiente';
            if ($payments->count() > 0) {
                $status = 'Pagado';
            } elseif ($dueDate->isPast() && !$dueDate->isToday()) {
                $status = 'Vencido';
            }

            $installments[] = (object) [
                'number' => $i,
                'due_date' => $dueDate,
                'payments' => $payments,
                'status' => $status,
                'amount' => $amountPerInstallment
            ];
        }

        $payment_methods = \App\Models\PaymentMethod::all();
        
        return view('finances.show', compact('loan', 'installments', 'payment_methods'));
    }

    public function storePayment(Request $request)
    {
        $data = $request->validate([
            'bank_loan_id' => 'required|exists:bank_loans,id',
            'payment_date' => 'required|date',
            'installment_number' => 'required|integer',
            'is_external' => 'nullable',
            'amount' => 'nullable|numeric',
            'payments' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $loan = BankLoan::findOrFail($data['bank_loan_id']);

        if ($request->is_external) {
            LoanPayment::create([
                'bank_loan_id' => $data['bank_loan_id'],
                'amount' => $data['amount'] ?? 0,
                'payment_date' => $data['payment_date'],
                'installment_number' => $data['installment_number'],
                'payment_method_id' => null,
                'notes' => $data['notes']
            ]);
        } else {
            foreach ($data['payments'] as $payment) {
                if ($payment['amount'] > 0) {
                    LoanPayment::create([
                        'bank_loan_id' => $data['bank_loan_id'],
                        'amount' => $payment['amount'],
                        'payment_date' => $data['payment_date'],
                        'installment_number' => $data['installment_number'],
                        'payment_method_id' => $payment['method_id'],
                        'notes' => $data['notes']
                    ]);

                    Expense::create([
                        'description' => 'Pago de Cuota ' . $data['installment_number'] . ' - Crédito Banco ' . $loan->bank_name,
                        'amount' => $payment['amount'],
                        'payment_method_id' => $payment['method_id'],
                        'date' => now()
                    ]);
                }
            }
        }

        if ($loan->fresh()->remaining_balance <= 0.1) {
            $loan->update(['status' => 'Pagado']);
        }

        return redirect()->back()->with('success', 'Pago registrado correctamente.');
    }

    public function edit($id)
    {
        $loan = BankLoan::findOrFail($id);
        return response()->json($loan);
    }

    public function update(Request $request, $id)
    {
        $loan = BankLoan::findOrFail($id);
        $data = $request->validate([
            'bank_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'installments_total' => 'required|integer|min:1',
            'monthly_amount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'status' => 'required|string',
            'currency' => 'required|string|in:PEN,USD'
        ]);

        $loan->update($data);

        return redirect()->route('finances.index')->with('success', 'Crédito actualizado correctamente.');
    }

    public function destroy($id)
    {
        $loan = BankLoan::findOrFail($id);
        $loan->delete();

        return response()->json(['status' => true]);
    }

    public function editPayment($id)
    {
        $payment = LoanPayment::with('payment_method')->findOrFail($id);
        return response()->json($payment);
    }

    public function updatePayment(Request $request, $id)
    {
        $payment = LoanPayment::findOrFail($id);
        $old_amount = $payment->amount;
        $old_method = $payment->payment_method_id;
        $old_date = $payment->payment_date->toDateString();

        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'notes' => 'nullable|string'
        ]);

        // Find associated expense
        $loan = $payment->loan;
        $description = 'Pago de Cuota ' . $payment->installment_number . ' - Crédito Banco ' . $loan->bank_name;
        
        $expense = Expense::where('description', $description)
            ->where('amount', $old_amount)
            ->where('payment_method_id', $old_method)
            // Using whereDate because 'date' column might be date or datetime
            ->whereDate('date', $old_date)
            ->first();

        $payment->update($data);

        if ($expense) {
            if ($data['payment_method_id']) {
                $expense->update([
                    'amount' => $data['amount'],
                    'payment_method_id' => $data['payment_method_id'],
                    'date' => $data['payment_date']
                ]);
            } else {
                // If it became external, remove expense
                $expense->delete();
            }
        } elseif ($data['payment_method_id']) {
            // If it wasn't internal but now it is, create expense
            Expense::create([
                'description' => $description,
                'amount' => $data['amount'],
                'payment_method_id' => $data['payment_method_id'],
                'date' => $data['payment_date']
            ]);
        }

        // Check loan status
        if ($loan->fresh()->remaining_balance <= 0.1) {
            $loan->update(['status' => 'Pagado']);
        } else {
            $loan->update(['status' => 'Activo']);
        }

        return response()->json(['status' => true]);
    }

    public function destroyPayment($id)
    {
        $payment = LoanPayment::findOrFail($id);
        
        // Find associated expense
        $loan = $payment->loan;
        $description = 'Pago de Cuota ' . $payment->installment_number . ' - Crédito Banco ' . $loan->bank_name;
        
        $expense = Expense::where('description', $description)
            ->where('amount', $payment->amount)
            ->where('payment_method_id', $payment->payment_method_id)
            ->whereDate('date', $payment->payment_date->toDateString())
            ->first();

        if ($expense) {
            $expense->delete();
        }

        $payment->delete();

        // Check loan status
        if ($loan->fresh()->remaining_balance > 0.1) {
            $loan->update(['status' => 'Activo']);
        }

        return response()->json(['status' => true]);
    }
}
