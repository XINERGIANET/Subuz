<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'client_id',
        'document_type',
        'date',
        'total',
        'status',
        'notes',
        'electronic_invoice_provider',
        'electronic_invoice_status',
        'electronic_invoice_external_id',
        'electronic_invoice_series',
        'electronic_invoice_number',
        'electronic_invoice_file_name',
        'electronic_invoice_pdf_ticket_url',
        'electronic_invoice_pdf_a4_url',
        'electronic_invoice_xml_url',
        'electronic_invoice_cdr_url',
        'electronic_invoice_response',
    ];

    protected $dates = ['date'];

    protected $casts = [
        'electronic_invoice_response' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'invoice_sale');
    }
}
