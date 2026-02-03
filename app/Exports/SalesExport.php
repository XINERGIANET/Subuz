<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Sale::latest('date')->get();
    }

    public function map($sale): array
    {
        return [
            $sale->guide,
            $sale->date->format('d/m/Y'),
            $sale->type,
            optional($sale->payment_method)->name,
            optional($sale->client)->name,
            optional($sale->client)->district,
            $sale->total
        ];
    }

    public function headings(): array
    {
        return [
            'Guía',
            'Fecha',
            'Tipo de venta',
            'Método de pago',
            'Cliente',
            'Distrito',
            'Total'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
