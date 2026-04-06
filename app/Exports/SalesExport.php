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
        $query = Sale::with(['payment_method', 'client'])
            ->when($this->request->client_id, function($q, $client_id){
                return $q->where('client_id', $client_id);
            })
            ->when($this->request->type, function($q, $type){
                return $q->where('type', $type);
            })
            ->when($this->request->start_date, function($q, $start_date){
                return $q->whereDate('date', '>=', $start_date);
            })
            ->when($this->request->end_date, function($q, $end_date){
                return $q->whereDate('date', '<=', $end_date);
            })
            ->when($this->request->is_pending, function($q){
                return $q->whereIn('type', ['Contado', 'Pago pendiente'])->where('paid', 0);
            })
            ->when($this->request->is_credit, function($q){
                return $q->where('type', 'Credito')->where('paid', 0);
            });

        if(!$this->request->start_date && !$this->request->end_date && !$this->request->is_pending && !$this->request->is_credit && !$this->request->client_id){
            $query->whereDate('date', now());
        }

        return $query->latest('date')->get();
    }

    public function map($sale): array
    {
        return [
            $sale->guide,
            optional($sale->date)->format('d/m/Y'),
            $sale->type,
            optional($sale->payment_method)->name ?? 'S/M',
            optional($sale->client)->name ?? 'Consumidor Final',
            optional($sale->client)->district ?? 'N/A',
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
