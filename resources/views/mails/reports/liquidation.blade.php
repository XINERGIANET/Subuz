<p>Hola {{ $client->name }}</p>
<p>
	A continuación te enviamos el reporte de liquidación del día <b>{{ $week->start_date->format('d/m/Y') }}</b> al <b>{{ $week->end_date->format('d/m/Y') }}.</b>
</p>
<p>Gracias por ser nuestro cliente.</p>