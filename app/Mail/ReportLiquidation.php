<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Client;
use App\Models\Week;

class ReportLiquidation extends Mailable
{
    use Queueable, SerializesModels;

    public $client, $week, $request;

    public function __construct(Client $client, Week $week, $request)
    {
        $this->client = $client;
        $this->week = $week;
        $this->request = $request;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Reporte de liquidación")
                    ->view('mails.reports.liquidation')
                    ->attach(route('reports.pdf', [
                        'client_id' => $this->request['client_id'],
                        'date' => $this->request['date'],
                        'payment_date' => $this->request['payment_date']
                    ]),[
                        'as' => 'ReporteLiquidacion.pdf',
                        'mime' => 'application/pdf'
                    ]);
    }
}
