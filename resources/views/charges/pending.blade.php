@extends('template.app')

@section('title', 'Cobranza de Pendiente de pago')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
    <li class="breadcrumb-item active">Pendiente de pago</li>
  </ol>
</nav>
<div class="card">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div>
			
		</div>
		<div class="text-center">
			<span class="d-block small">
				Tienes un total de
			</span>
			<span class="fs-2 fw-bold text-primary">
					S/{{ number_format($total, 2) }}
				</span>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>#</th>
					<th>Guía de remisión</th>
					<th>Fecha</th>
					<th>Cliente</th>
					<th>Total</th>
					<th>Pagar</th>
				</tr>
			</thead>
			<tbody>
				@if($sales->count() > 0)
				@foreach($sales as $sale)
				<tr>
					<td>{{ $loop->iteration }}</td>
					<td>{{ $sale->guide }}</td>
					<td>{{ $sale->date->format('d/m/Y') }}</td>
					<td>{{ optional($sale->client)->name }}</td>
					<td>{{ $sale->total }}</td>
					<td>
						@if(auth()->user()->hasRole('admin'))
						<div class="d-flex gap-2">
							<form id="paymentForm" method="POST">
								<div class="input-group">
									<select class="form-select" name="payment_method_id">
										<option value="">Seleccionar</option>
										@foreach($payment_methods as $payment_method)
										<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
										@endforeach
									</select>
									<input type="hidden" name="sale_id" value="{{ $sale->id }}">
									<input type="hidden" name="client_id" value="{{ $sale->client_id }}">
									<input type="hidden" name="type" value="Pago pendiente">
									<button class="btn btn-icon btn-brand" data-id="{{ $sale->id }}">
										<i class="ti ti-cash icon"></i>
									</button>
								</div>
							</form>
						</div>
						@endif
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="9" align="center">No se han encontrado registros</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($sales->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $sales->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection

@section('scripts')
<script>

	$('#paymentForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('payments.store') }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					location.reload();
				}else{
					alert(data.error);
				}
			},
			error: function(err){
				console.log(err);
			}
		});

	});
</script>
@endsection