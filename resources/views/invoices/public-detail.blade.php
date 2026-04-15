<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Comprobante {{ $serieNumero }}</title>
	<style>
		:root { color-scheme: light; }
		body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; padding: 16px; background: #f4f4f5; color: #18181b; }
		.wrap { max-width: 640px; margin: 0 auto; }
		.card { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
		h1 { font-size: 1.15rem; margin: 0 0 4px; }
		.muted { color: #71717a; font-size: .85rem; }
		.kv { display: grid; grid-template-columns: 130px 1fr; gap: 6px 12px; font-size: .9rem; margin-top: 12px; }
		.kv dt { font-weight: 600; color: #3f3f46; }
		.kv dd { margin: 0; }
		.badge { display: inline-block; background: #18181b; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: .8rem; letter-spacing: .02em; }
		table { width: 100%; border-collapse: collapse; font-size: .88rem; margin-top: 10px; }
		th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #e4e4e7; }
		th { background: #18181b; color: #fff; font-weight: 600; }
		td.num, th.num { text-align: right; }
		.sale-head { font-weight: 700; margin: 18px 0 8px; font-size: .95rem; color: #27272a; }
		.totals { margin-top: 16px; text-align: right; font-size: .95rem; }
		.totals strong { display: inline-block; min-width: 140px; text-align: left; margin-right: 8px; }
		footer { text-align: center; font-size: .75rem; color: #a1a1aa; margin-top: 24px; }
	</style>
</head>
<body>
<div class="wrap">
	<div class="card">
		<span class="badge">{{ $docLabel }}</span>
		<h1 style="margin-top:12px">{{ $company['legal_name'] ?? 'Empresa' }}</h1>
		<p class="muted">RUC {{ $company['ruc'] ?? '—' }} @if(!empty($company['address']))<br>{{ $company['address'] }}@endif</p>
		<dl class="kv">
			<dt>Comprobante</dt>
			<dd><strong>{{ $serieNumero }}</strong></dd>
			<dt>Fecha emisión</dt>
			<dd>{{ $invoice->date->format('d/m/Y') }}</dd>
			<dt>Cliente</dt>
			<dd>{{ $invoice->client->business_name ?: $invoice->client->name }}</dd>
			<dt>RUC / DNI</dt>
			<dd>{{ $invoice->client->document ?: '—' }}</dd>
			<dt>Dirección</dt>
			<dd>{{ $invoice->client->address ?: '—' }}</dd>
			<dt>Estado</dt>
			<dd>{{ $invoice->status }}</dd>
		</dl>
	</div>

	@foreach($invoice->sales as $sale)
		<div class="card">
			<div class="sale-head">Venta / pedido #{{ $sale->order ?? $sale->id }}</div>
			<p class="muted" style="margin:0 0 8px">
				Fecha: {{ $sale->date ? $sale->date->format('d/m/Y') : '—' }}
				@if($sale->payment_method) · Pago: {{ $sale->payment_method->name ?? '—' }}@endif
				@if($sale->status) · Estado: {{ $sale->status }}@endif
			</p>
			<table>
				<thead>
					<tr>
						<th>Producto</th>
						<th class="num">Cant.</th>
						<th class="num">P. unit.</th>
						<th class="num">Importe</th>
					</tr>
				</thead>
				<tbody>
					@foreach($sale->details as $d)
						@php
							$line = round((float) $d->price * (float) $d->quantity, 2);
						@endphp
						<tr>
							<td>{{ $d->product->name ?? 'Producto' }}</td>
							<td class="num">{{ number_format($d->quantity, 2) }}</td>
							<td class="num">S/ {{ number_format($d->price, 2) }}</td>
							<td class="num">S/ {{ number_format($line, 2) }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
			<p class="totals"><strong>Subtotal venta</strong> S/ {{ number_format($sale->total, 2) }}</p>
		</div>
	@endforeach

	<div class="card">
		<div class="totals">
			<div><strong>Op. gravada</strong> S/ {{ number_format($opGravada, 2) }}</div>
			<div><strong>I.G.V. ({{ (int) round($igvRate * 100) }}%)</strong> S/ {{ number_format($igvTotal, 2) }}</div>
			<div style="margin-top:10px;font-size:1.1rem"><strong>Total comprobante</strong> S/ {{ number_format($invoice->total, 2) }}</div>
		</div>
		@if($invoice->notes)
			<p style="margin-top:16px;font-size:.9rem"><strong>Observación:</strong> {{ $invoice->notes }}</p>
		@endif
	</div>

	<footer>Consulta generada desde código QR · Comprobante interno #{{ $invoice->number }}</footer>
</div>
</body>
</html>
