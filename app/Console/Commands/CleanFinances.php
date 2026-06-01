<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expense;
use App\Models\LoanPayment;
use App\Models\BankLoan;

class CleanFinances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean orphaned expenses and convert active loan payments to external';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando limpieza de finanzas...");

        // 1. LIMPIAR GASTOS HUÉRFANOS (De créditos eliminados)
        $expenses = Expense::where('description', 'like', 'Pago de Cuota%')->get();
        $orphaned_expenses_count = 0;
        $orphaned_expenses_amount = 0;
        $valid_bank_names = BankLoan::pluck('bank_name')->toArray();

        foreach ($expenses as $expense) {
            $is_orphan = true;
            foreach ($valid_bank_names as $bn) {
                if (strpos($expense->description, $bn) !== false) {
                    $is_orphan = false;
                    break;
                }
            }
            
            if ($is_orphan) {
                $orphaned_expenses_amount += $expense->amount;
                $orphaned_expenses_count++;
                $expense->delete();
            }
        }
        $this->info("1. Se eliminaron {$orphaned_expenses_count} gastos huérfanos por un total de S/ " . number_format($orphaned_expenses_amount, 2));

        // 2. LIMPIAR PAGOS HUÉRFANOS
        $payments = LoanPayment::all();
        $orphaned_payments_count = 0;
        foreach ($payments as $p) {
            if (!$p->loan) {
                $orphaned_payments_count++;
                $p->delete();
            }
        }
        $this->info("2. Se eliminaron {$orphaned_payments_count} registros de pagos huérfanos.");

        // 3. CONVERTIR PAGOS ACTIVOS A "EXTERNOS" (Para sacarlos de la caja)
        $active_payments = LoanPayment::whereNotNull('payment_method_id')->get();
        $fixed_count = 0;
        $fixed_total = 0;

        foreach ($active_payments as $payment) {
            $loan = BankLoan::find($payment->bank_loan_id);
            if (!$loan) continue; 
            
            $description = 'Pago de Cuota ' . $payment->installment_number . ' - Crédito Banco ' . $loan->bank_name;
            
            // Buscar y borrar el Gasto espejo que está restando de la caja
            $expense = Expense::where('description', $description)
                ->where('amount', $payment->amount)
                ->where('payment_method_id', $payment->payment_method_id)
                ->first();
                
            if ($expense) {
                $expense->delete();
                $fixed_count++;
                $fixed_total += $payment->amount;
            }
            
            // Cambiar el método de pago a Nulo (Externo)
            $payment->payment_method_id = null;
            $payment->save();
        }

        $this->info("3. Se convirtieron {$fixed_count} pagos de cuotas activas a método externo.");
        $this->info("Total recuperado en las cajas de pagos activos: S/ " . number_format($fixed_total, 2));
        $this->info("¡Limpieza completada!");
    }
}
