@extends('template.app')

@section('title', 'Inicio')

@section('content')
@if(auth()->user()->hasRole('admin'))
<h2>Resultados generales</h2>
<form id="form1" class="mb-4">
	<div class="row">
		<div class="col-md-3">
			<div class="mb-3">
				<label class="form-label">Fecha inicial</label>
				<input type="date" class="form-control" id="start_date">
			</div>
		</div>
		<div class="col-md-3">
			<div class="mb-3">
				<label class="form-label">Mes</label>
				<input type="date" class="form-control" id="end_date">
			</div>
		</div>
	</div>
	<button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
</form>
<div class="row">
	<div class="col-md-3">
		<div class="card bg-primary-lt mb-4">
			<div class="card-stamp">
				<div class="card-stamp-icon bg-primary">
						<i class="ti ti-shopping-cart"></i>
				</div>
			</div>
			<div class="card-body">
				<h5 class="card-title">Ventas</h5>
				<span class="d-block fs-1 text-center" id="sales"></span>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="card bg-danger-lt mb-4">
			<div class="card-stamp">
				<div class="card-stamp-icon bg-danger">
					<i class="ti ti-truck-loading"></i>
				</div>
			</div>
			<div class="card-body">
				<h5 class="card-title">Compras</h5>
				<span class="d-block fs-1 text-center" id="expenses"></span>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="card bg-success-lt mb-4">
			<div class="card-stamp">
				<div class="card-stamp-icon bg-success">
					<i class="ti ti-check"></i>
				</div>
			</div>
			<div class="card-body">
				<h5 class="card-title">Rentabilidad</h5>
				<span class="d-block fs-1 text-center" id="revenues"></span>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="card bg-warning-lt mb-4">
			<div class="card-stamp">
				<div class="card-stamp-icon bg-warning">
					<i class="ti ti-clock"></i>
				</div>
			</div>
			<div class="card-body">
				<h5 class="card-title">Pago pendiente</h5>
				<span class="d-block fs-1 text-center" id="pending"></span>
			</div>
		</div>
	</div>
	<div class="col-md-12">
		<div class="card mb-4">
			<div class="card-body">
				<h5 class="card-title">Gráfico de lineas de evolución de ventas y compras por mes</h5>
				<div>
					<canvas id="chart1"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>
<h2>Resultados por producto</h2>
<form id="form2" class="mb-4">
	<div class="row">
		<div class="col-md-3">
			<div class="mb-3">
				<label class="form-label">Año</label>
                <select class="form-select" id="year">
					<option value="2023">2023</option>
					<option value="2024">2024</option>
					<option value="2025">2025</option>
					<option value="2026">2026</option>
					<option value="2027">2027</option>
					<option value="2028">2028</option>
					<option value="2029">2029</option>
					<option value="2030">2030</option>
				</select>
			</div>
		</div>
		<div class="col-md-3">
			<div class="mb-3">
				<label class="form-label">Mes</label>
				<select class="form-select" id="month">
					<option value="1">Enero</option>
					<option value="2">Febrero</option>
					<option value="3">Marzo</option>
					<option value="4">Abril</option>
					<option value="5">Mayo</option>
					<option value="6">Junio</option>
					<option value="7">Julio</option>
					<option value="8">Agosto</option>
					<option value="9">Septiembre</option>
					<option value="10">Octubre</option>
					<option value="11">Noviembre</option>
					<option value="12">Diciembre</option>
				</select>
			</div>
		</div>
		<div class="col-md-6">
			<div class="mb-3">
				<label class="form-label">Producto</label>
				<select class="form-select ts-products" id="product_id">
					<option value="">Seleccionar</option>
					@foreach($products as $product)
					<option value="{{ $product->id }}">{{ $product->name }} - S/{{ $product->price }}</option>
					@endforeach
				</select>
			</div>
		</div>
	</div>
	<button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
</form>
<div class="row">
	<div class="col-md-3">
		<div class="card bg-primary-lt mb-4">
			<div class="card-stamp">
				<div class="card-stamp-icon bg-primary">
						<i class="ti ti-shopping-cart"></i>
				</div>
			</div>
			<div class="card-body">
				<h5 class="card-title">Ventas por año</h5>
				<span class="d-block fs-1 text-center text-primary" id="sales_year">S/0.00</span>
			</div>
		</div>
		<div class="card bg-primary-lt mb-4">
			<div class="card-stamp">
				<div class="card-stamp-icon bg-primary">
						<i class="ti ti-shopping-cart"></i>
				</div>
			</div>
			<div class="card-body">
				<h5 class="card-title">Ventas por mes</h5>
				<span class="d-block fs-1 text-center text-primary" id="sales_month">S/0.00</span>
			</div>
		</div>
	</div>
	<div class="col-md-9">
		<div class="card mb-4">
			<div class="card-body">
				<h5 class="card-title">Gráfico de lineas de evolución de ventas por mes</h5>
				<div>
					<canvas id="chart2"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>
<form id="form3" class="mb-4">
	<div class="row">
		<div class="col-md-3">
			<div class="mb-3">
				<label class="form-label">Día</label>
				<input type="date"  class="form-control" id="form3_day">
			</div>
		</div>
		<div class="col-md-3">
			<div class="mb-3">
				<label class="form-label">Cantidad vendida</label>
				<input type="text" class="form-control" disabled>
			</div>
		</div>
		<div class="col-md-3">
			<div class="mb-3">
				<label class="form-label">Cantidad despachada</label>
				<input type="text" class="form-control" disabled>
			</div>
		</div>
	</div>
</form>

@else
<p>Bienvenido a Subuz</p>
@endif
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

	const ctx_chart1 = document.getElementById('chart1');
	const ctx_chart2 = document.getElementById('chart2');
	var chart1, chart2;

	$(document).ready(function(){

		chart1 = new Chart(ctx_chart1, {
			type: 'bar',
			data: {
				labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
				datasets: [
					{
						label: 'Ventas',
						data: [],
						borderWidth: 1
					},
					{
						label: 'Compras',
						data: [],
						borderWidth: 1
					}
				]
			},
			options: {
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
		});

		chart2 = new Chart(ctx_chart2, {
			type: 'bar',
			data: {
				labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
				datasets: [
					{
						label: 'Ventas',
						data: [],
						borderWidth: 1
					}
				]
			},
			options: {
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
		})

		new TomSelect('.ts-products', {
			valueField: 'id',
			labelField: 'name',
			searchField: 'name',
			copyClassesToDropdown: false,
			dropdownClass: 'dropdown-menu ts-dropdown',
    		optionClass:'dropdown-item',
			render: {
				no_results: function(data, escape){
					return '<div class="no-results">No se encontraron resultados</div>'
				}
			}
		});

		dashboard();

	});

	$('#form1').submit(function(e){
		e.preventDefault();
		
		var start_date = $('#start_date').val();
		var end_date = $('#end_date').val();
		
		dashboard(start_date, end_date);
	});

	$('#form2').submit(function(e){
		e.preventDefault();

		var year = $('#year').val();
		var month = $('#month').val();
		var product_id = $('#product_id').val();

		console.log(product_id);

		$.ajax({
			url: '{{ route('dashboard.product.api') }}',
			method: 'GET',
			data: {
				year, month, product_id
			},
			success: function(data){
				$('#sales_year').text('S/'+data.sales_year);
				$('#sales_month').text('S/'+data.sales_month);
				chart2.data.datasets[0].data = data.chart_sales_month;
				chart2.update();
			}
		});
	});

	
	function dashboard( start_date = null, end_date = null){
		$.ajax({
			url: '{{ route('dashboard.api') }}',
			method: 'GET',
			data: {
				start_date, end_date
			},
			success: function(data){
				$('#sales').text('S/'+data.sales);
				$('#expenses').text('S/'+data.expenses);
				$('#revenues').text('S/'+data.revenues);
				$('#pending').text('S/'+data.pending);

				chart1.data.datasets[0].data = data.totalSales;
				chart1.data.datasets[1].data = data.totalExpenses;
				chart1.update();
			}
		});
	}

</script>
@endsection