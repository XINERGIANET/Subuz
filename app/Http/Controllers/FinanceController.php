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
        return view('finances.index', compact('loans'));
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
}
