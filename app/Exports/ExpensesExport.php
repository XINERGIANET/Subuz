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
                return $query->whereMonth('date', $month);
            })->when($this->request->year, function($query, $year){
                return $query->whereYear('date', $year);
            })->latest('date')->get();
    }

    public function map($expense): array
    {
        return [
            $expense->description,
            $expense->amount,
            optional($expense->payment_method)->name ?? 'N/A',
            optional($expense->date)->format('d/m/Y')
        ];
    }

    public function headings(): array
    {
        return [
            'Descripción',
            'Monto',
            'Método de pago',
            'Fecha'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
