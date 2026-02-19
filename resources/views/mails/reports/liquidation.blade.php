<p>Hola {{ $client->name }}</p>
<p>
    @if($request['start_date'] && $request['end_date'])
	    A continuación te enviamos el reporte de liquidación del día <b>{{ date('d/m/Y', strtotime($request['start_date'])) }}</b> al <b>{{ date('d/m/Y', strtotime($request['end_date'])) }}.</b>
    @else
        A continuación te enviamos el reporte de liquidación de las compras realizadas.
    @endif
</p>
<p>Gracias por ser nuestro cliente.</p>