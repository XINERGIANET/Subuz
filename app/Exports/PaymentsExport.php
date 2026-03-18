<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class PaymentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        return Payment::with(['sale.client', 'payment_method'])
        ->when($this->request->client_id, function($query, $client_id){
            return $query->whereHas('sale', function($query) use ($client_id){
                return $query->where('client_id', $client_id);
            });
        })->when($this->request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($this->request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->when($this->request->type, function($query, $type){
            return $query->whereHas('sale', function($query) use ($type){
                return $query->where('type', $type);
            });
        })->latest('date')->get();
    }

    public function map($payment): array
    {
        return [
            optional(optional($payment->sale)->client)->name ?? 'Consumidor Final',
            optional($payment->sale)->guide ?? 'Venta Manual',
            $payment->amount,
            optional($payment->payment_method)->name ?? 'N/A',
            optional($payment->date)->format('d/m/Y')
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Guía/Venta',
            'Monto',
            'Forma de pago',
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
