@extends('template.app')

@section('title', 'Inicio')

@section('styles')
<style>
    .form-select, .input-group-text {
        color: var(--text-main) !important;
        background-color: var(--card-bg) !important;
    }
    [data-bs-theme='dark'] .form-select, [data-bs-theme='dark'] .input-group-text {
        color: #f8fafc !important;
    }
    .form-select option {
        background-color: var(--card-bg);
        color: var(--text-main);
    }
    .metric-card-featured .text-uppercase {
        color: rgba(255, 255, 255, 0.9) !important;
        opacity: 1 !important;
    }
</style>
@endsection

@section('content')
<div class="card welcome-card-premium border-0 shadow-sm mb-4">
    <!-- Sophisticated Decorations -->
    <div class="welcome-card-decoration welcome-card-decoration-1"></div>
    <div class="welcome-card-decoration welcome-card-decoration-2"></div>
    
    <div class="card-body py-5 position-relative">
        <div class="row align-items-center">
            <div class="col-auto d-none d-md-block">
                <div class="welcome-profile-ring">
                    <div class="welcome-profile-avatar">
                        <i class="ti ti-user-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 ms-md-3">
                <h1 class="h1 mb-2">¡Hola, {{ auth()->user()->name }}! 👋</h1>
                <p class="text-secondary mb-0">Bienvenido al panel central de Subuz. Gestiona tus operaciones diarias con eficiencia.</p>
            </div>
            <div class="col-lg-4 ms-auto d-flex justify-content-lg-end gap-2 mt-4 mt-lg-0">
                <a href="{{ route('sales.create') }}" class="btn btn-brand btn-pill px-4 shadow-sm py-2 fw-bold">
                    <i class="ti ti-plus icon me-1"></i> Nueva Venta
                </a>
                <a href="{{ route('expenses.index', ['create' => 1]) }}" class="btn btn-outline-primary btn-pill px-4 shadow-sm py-2 fw-bold" style="background: white; color: var(--brand-color); border-color: var(--brand-color);">
                    <i class="ti ti-receipt icon me-1"></i> Gastos
                </a>
            </div>
        </div>
    </div>
</div>

<div class="hr-text hr-text-left text-primary fw-bold mb-3 uppercase-text">SALDOS DE CUENTAS (TIEMPO REAL)</div>

<div class="row row-cards mb-4">
    <div class="col-12">
        <div class="card metric-card metric-card-featured border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #244BB3 0%, #1a3a8a 100%); color: white;">
            <div class="card-body p-4 text-center">
			<div class="text-uppercase small mb-1 fw-bold text-white">
				Balance Total en Cuentas
			</div>
                <div class="h1 mb-0 fw-extrabold" id="total_balance">S/0.00</div>
            </div>
        </div>
    </div>
</div>

<div id="dynamic_methods_container" class="row row-cards mb-4">
    <!-- Dynamic cards will be injected here -->
</div>

@if(auth()->user()->hasRole('admin'))
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form id="form1">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <div class="avatar bg-primary-lt text-primary rounded shadow-none border-0">
                        <i class="ti ti-calendar-stats"></i>
                    </div>
                </div>
                <div class="col">
                    <h3 class="card-title mb-0">Periodo de Análisis</h3>
                    <div class="text-muted small">Filtra los resultados generales por fecha</div>
                </div>
                <div class="col-md-5">
                    <div class="input-group input-group-flat border rounded-3 overflow-hidden shadow-none" style="border-color: #e2e8f0 !important;">
                        <input type="date" class="form-control border-0 px-3" id="start_date">
                        <span class="input-group-text border-0 bg-light px-2" style="font-size: 0.7rem;">AL</span>
                        <input type="date" class="form-control border-0 px-3" id="end_date">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-brand px-4">
                        <i class="ti ti-refresh icon"></i> Actualizar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="row row-cards mb-4">
	<div class="col-sm-6 col-lg-3">
		<div class="card metric-card border-0 shadow-sm h-100">
            <div class="card-status-start bg-primary"></div>
			<div class="card-body p-3">
				<div class="row g-3 align-items-center">
					<div class="col-auto">
						<div class="bg-primary-lt text-primary avatar avatar-md shadow-none">
							<i class="ti ti-shopping-cart fs-2"></i>
						</div>
					</div>
					<div class="col">
						<div class="text-uppercase mb-1">Ventas</div>
						<div class="h1 mb-0 fw-extrabold" id="sales" style="color: var(--brand-color);">...</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-sm-6 col-lg-3">
		<div class="card metric-card border-0 shadow-sm h-100">
            <div class="card-status-start bg-danger"></div>
			<div class="card-body p-3">
				<div class="row g-3 align-items-center">
					<div class="col-auto">
						<div class="bg-danger-lt text-danger avatar avatar-md shadow-none">
							<i class="ti ti-truck-loading fs-2"></i>
						</div>
					</div>
					<div class="col">
						<div class="text-uppercase mb-1">Compras</div>
						<div class="h1 mb-0 fw-extrabold text-danger" id="expenses">...</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-sm-6 col-lg-3">
		<div class="card metric-card border-0 shadow-sm h-100">
            <div class="card-status-start bg-success"></div>
			<div class="card-body p-3">
				<div class="row g-3 align-items-center">
					<div class="col-auto">
						<div class="bg-success-lt text-success avatar avatar-md shadow-none">
							<i class="ti ti-shield-check fs-2"></i>
						</div>
					</div>
					<div class="col">
						<div class="text-uppercase mb-1">Rentabilidad</div>
						<div class="h1 mb-0 fw-extrabold text-success" id="revenues">...</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-sm-6 col-lg-3">
		<div class="card metric-card border-0 shadow-sm h-100">
            <div class="card-status-start bg-warning"></div>
			<div class="card-body p-3">
				<div class="row g-3 align-items-center">
					<div class="col-auto">
						<div class="bg-warning-lt text-warning avatar avatar-md shadow-none">
							<i class="ti ti-clock fs-2"></i>
						</div>
					</div>
					<div class="col">
						<div class="text-uppercase mb-1">Pendiente</div>
						<div class="h1 mb-0 fw-extrabold text-warning" id="pending">...</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-12">
		<div class="card border-0 shadow-sm mt-4 overflow-hidden">
            <div class="card-header border-0 py-3">
                <h3 class="card-title fw-bold"><i class="ti ti-chart-line me-2"></i>Evolución Operativa</h3>
            </div>
			<div class="card-body pt-0">
				<div style="height: 400px;">
					<canvas id="chart1"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="hr-text hr-text-left text-primary fw-bold mb-4">ANÁLISIS POR PRODUCTO</div>

<div class="row row-cards mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold mb-0"><i class="ti ti-chart-pie me-2"></i>Distribución de Ventas por Producto</h3>
            </div>
            <div class="card-body border-bottom bg-light-lt py-3">
                <form id="form_distribution">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-medium mb-1 text-muted text-uppercase">Año</label>
                            <div class="input-group input-group-flat shadow-none border rounded">
                                <span class="input-group-text border-0 ps-2 pe-0">
                                    <i class="ti ti-calendar text-muted fs-3"></i>
                                </span>
                                <select class="form-select border-0 ps-2" id="dist_year">
                                    @for($i = 2023; $i <= 2030; $i++)
                                    <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-medium mb-1 text-muted text-uppercase">Mes</label>
                            <div class="input-group input-group-flat shadow-none border rounded">
                                <span class="input-group-text border-0 ps-2 pe-0">
                                    <i class="ti ti-calendar-month text-muted fs-3"></i>
                                </span>
                                <select class="form-select border-0 ps-2" id="dist_month">
                                    @php $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']; @endphp
                                    @foreach($months as $index => $name)
                                    <option value="{{ $index + 1 }}" {{ ($index + 1) == date('m') ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-brand w-100 py-2 fw-bold text-uppercase shadow-sm">
                                <i class="ti ti-refresh me-2 fs-3"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div style="height: 350px;">
                    <canvas id="chart3"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<h3 class="mt-2 mb-3">Resultados detallados</h3>
<div class="card mb-4 border-0 shadow-sm card-filter-container">
    <div class="card-body">
        <form id="form2">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Año</label>
                    <select class="form-select" id="year">
                        <option value="2023">2023</option>
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026"  selected>2026</option>
                        <option value="2027">2027</option>
                        <option value="2028">2028</option>
                        <option value="2029">2029</option>
                        <option value="2030">2030</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Mes</label>
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
                <div class="col-md-6">
                    <label class="form-label fw-bold">Producto</label>
                    <select class="form-select ts-products" id="product_id">
                        <option value="">Seleccionar producto</option>
                        @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} - S/{{ $product->price }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand w-100">
                        <i class="ti ti-filter icon"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="row row-cards mb-4">
	<div class="col-sm-6">
		<div class="card border-0 shadow-sm overflow-hidden h-100">
            <div class="card-status-start bg-primary"></div>
			<div class="card-body p-3">
				<div class="font-weight-medium text-muted small text-uppercase fw-bold mb-1">Ventas por año</div>
				<div class="h2 mb-0 fw-bold" id="sales_year" style="color: var(--brand-color);">S/0.00</div>
			</div>
		</div>
    </div>
    <div class="col-sm-6">
		<div class="card border-0 shadow-sm overflow-hidden h-100">
            <div class="card-status-start bg-primary"></div>
			<div class="card-body p-3">
				<div class="font-weight-medium text-muted small text-uppercase fw-bold mb-1">Ventas por mes</div>
				<div class="h2 mb-0 fw-bold" id="sales_month" style="color: var(--brand-color);">S/0.00</div>
			</div>
		</div>
	</div>
</div>

<div class="row row-cards mb-4">
    <div class="col-12">
		<div class="card border-0 shadow-sm overflow-hidden h-100">
            <div class="card-header border-0 py-3">
                <h3 class="card-title fw-bold"><i class="ti ti-chart-bar me-2"></i>Evolución Mensual</h3>
            </div>
			<div class="card-body pt-0">
				<div style="height: 350px;">
					<canvas id="chart2"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>
@endif

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

	const ctx_chart1 = document.getElementById('chart1');
	const ctx_chart2 = document.getElementById('chart2');
	const ctx_chart3 = document.getElementById('chart3');
	var chart1, chart2, chart3;
    
    // Theme aware chart colors
    const isDark = document.body.getAttribute('data-bs-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
    const textColor = isDark ? '#94A3B8' : '#6B7280';

	$(document).ready(function(){

		chart1 = new Chart(ctx_chart1, {
			type: 'line',
			data: {
				labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
				datasets: [
					{
						label: 'Ventas',
						data: [],
						borderColor: '#244BB3',
						backgroundColor: isDark ? 'rgba(36, 75, 179, 0.2)' : 'rgba(36, 75, 179, 0.1)',
						fill: true,
						tension: 0.4,
						borderWidth: 3,
						pointRadius: 4,
						pointBackgroundColor: '#244BB3'
					},
					{
						label: 'Compras',
						data: [],
						borderColor: '#EF4444',
						backgroundColor: 'rgba(239, 68, 68, 0.1)',
						fill: true,
						tension: 0.4,
						borderWidth: 3,
						pointRadius: 4,
						pointBackgroundColor: '#EF4444'
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'top',
                        labels: {
                            color: textColor,
                            font: { weight: '600' }
                        }
					},
					tooltip: {
						mode: 'index',
						intersect: false,
                        backgroundColor: isDark ? '#1E293B' : '#ffffff',
                        titleColor: isDark ? '#F8FAFC' : '#111827',
                        bodyColor: isDark ? '#CBD5E1' : '#6B7280',
                        borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                        borderWidth: 1
					}
				},
				scales: {
					y: {
						beginAtZero: true,
                        ticks: { color: textColor },
						grid: {
							display: true,
							drawBorder: false,
							color: gridColor
						}
					},
					x: {
                        ticks: { color: textColor },
						grid: {
							display: false
						}
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
						backgroundColor: '#3B82F6',
						borderRadius: 6,
						hoverBackgroundColor: '#244BB3'
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: false
					},
                    tooltip: {
                        backgroundColor: isDark ? '#1E293B' : '#ffffff',
                        titleColor: isDark ? '#F8FAFC' : '#111827',
                        bodyColor: isDark ? '#CBD5E1' : '#6B7280'
                    }
				},
				scales: {
					y: {
						beginAtZero: true,
                        ticks: { color: textColor },
						grid: {
							display: true,
							drawBorder: false,
							color: gridColor
						}
					},
					x: {
                        ticks: { color: textColor },
						grid: {
							display: false
						}
					}
				}
			}
		})

		chart3 = new Chart(ctx_chart3, {
			type: 'pie',
			data: {
				labels: [],
				datasets: [
					{
						data: [],
						backgroundColor: [
							'#244BB3', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', 
							'#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316'
						],
						borderWidth: 1
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'right',
						labels: {
							color: textColor,
							font: { weight: '600' }
						}
					},
					tooltip: {
						backgroundColor: isDark ? '#1E293B' : '#ffffff',
						titleColor: isDark ? '#F8FAFC' : '#111827',
						bodyColor: isDark ? '#CBD5E1' : '#6B7280'
					}
				}
			}
		});

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

		loadDashboard();
		loadDistribution();

	});

	$('#form_distribution').submit(function(e){
		e.preventDefault();
		loadDistribution();
	});

	$('#form1').submit(function(e){
		e.preventDefault();
		loadDashboard();
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

	function loadDistribution(){
		$.ajax({
			url: '{{ route('dashboard.distribution.api') }}',
			method: 'GET',
			data: {
				year: $('#dist_year').val(),
				month: $('#dist_month').val()
			},
			success: function(data){
				chart3.data.labels = data.distribution.map(item => `${item.name} - S/${parseFloat(item.total).toFixed(2)}`);
				chart3.data.datasets[0].data = data.distribution.map(item => item.total);
				chart3.update();
			}
		});
	}

	
	function loadDashboard(){
		$.ajax({
			url: '{{ url("dashboard/api") }}',
			method: 'GET',
			data: {
				start_date: $('#start_date').val(),
				end_date: $('#end_date').val()
			},
			success: function(res){
				$('#sales').text('S/'+res.sales);
				$('#expenses').text('S/'+res.expenses);
				$('#revenues').text('S/'+res.revenues);
				$('#pending').text('S/'+res.pending);

                $('#total_balance').text('S/'+res.total_balance);
                
                let methods_html = '';
                const colors = ['bg-azure', 'bg-blue', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-orange', 'bg-yellow', 'bg-green', 'bg-teal'];
                const icons = ['ti-cash', 'ti-building-bank', 'ti-landmark', 'ti-device-mobile', 'ti-credit-card', 'ti-wallet', 'ti-receipt', 'ti-coin'];

                res.methods.forEach((method, index) => {
                    let color = colors[index % colors.length];
                    let icon = icons[index % icons.length];
                    let lightColor = color + '-lt text-' + color.replace('bg-', '');
                    
                    methods_html += `
                        <div class="col-sm-6 col-lg-3">
                            <div class="card metric-card border-0 shadow-sm h-100">
                                <div class="card-status-start ${color}"></div>
                                <div class="card-body p-3">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto">
                                            <div class="${lightColor} avatar avatar-md shadow-none">
                                                <i class="ti ${icon} fs-2"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="text-uppercase small mb-0 fw-bold text-muted">${method.name}</div>
                                            <div class="h2 mb-0 fw-bold">S/${method.total}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#dynamic_methods_container').html(methods_html);

				chart1.data.datasets[0].data = res.totalSales;
				chart1.data.datasets[1].data = res.totalExpenses;
				chart1.update();
			}
		});
	}

</script>
@endsection