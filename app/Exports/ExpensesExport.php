<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ExpensesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Expense::with('payment_method')
            ->when($this->request->month, function($query, $month){
                return $query->whereMonth(\Illuminate\Support\Facades\DB::raw('COALESCE(real_date, date)'), $month);
            })->when($this->request->year, function($query, $year){
                return $query->whereYear(\Illuminate\Support\Facades\DB::raw('COALESCE(real_date, date)'), $year);
            })->when($this->request->from_date, function($query, $from){
                return $query->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(real_date, date)'), '>=', $from);
            })->when($this->request->to_date, function($query, $to){
                return $query->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(real_date, date)'), '<=', $to);
            })->when($this->request->payment_method_id, function($query, $payment_method_id){
                return $query->where('payment_method_id', $payment_method_id);
            })->latest('date')->get();
    }

    public function map($expense): array
    {
        return [
            $expense->description,
            $expense->amount,
            optional($expense->payment_method)->name ?? 'N/A',
            optional($expense->date)->format('d/m/Y'),
            $expense->real_date ? date('d/m/Y', strtotime($expense->real_date)) : '',
            $expense->receipt_number,
            $expense->operation_number,
        ];
    }

    public function headings(): array
    {
        return [
            'Descripción',
            'Monto',
            'Método de pago',
            'Fecha Registro',
            'Fecha Real',
            'N° Comprobante',
            'N° Operación',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
