@extends('template.app')

@section('title', 'Gráficos de Productos Vendidos')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Productos</li>
  </ol>
</nav>

<div class="row">
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('reports.products') }}" method="GET" id="filter-form">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Periodo</label>
                            <select name="period" class="form-select" id="period-select">
                                <option value="day" {{ $period == 'day' ? 'selected' : '' }}>Por Día</option>
                                <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Por Mes</option>
                                <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Por Año</option>
                                <option value="custom" {{ $period == 'custom' ? 'selected' : '' }}>Rango de Fechas</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 custom-date" style="{{ $period == 'custom' ? '' : 'display: none;' }}">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $start_date }}">
                        </div>
                        
                        <div class="col-md-3 custom-date" style="{{ $period == 'custom' ? '' : 'display: none;' }}">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $end_date }}">
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Gráfico de Ventas por Producto</h3>
            </div>
            <div class="card-body">
                <canvas id="productsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detalle de Productos</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-end">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td class="text-end fw-bold">{{ $item->total_quantity }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center">No hay datos para mostrar</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle custom date fields
        document.getElementById('period-select').addEventListener('change', function() {
            var isCustom = this.value === 'custom';
            var customFields = document.querySelectorAll('.custom-date');
            customFields.forEach(function(field) {
                field.style.display = isCustom ? 'block' : 'none';
            });
        });

        // Setup Chart
        var ctx = document.getElementById('productsChart').getContext('2d');
        
        var labels = {!! json_encode($data->pluck('name')) !!};
        var quantities = {!! json_encode($data->pluck('total_quantity')) !!};

        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: quantities,
                    backgroundColor: 'rgba(2, 93, 166, 0.7)',
                    borderColor: 'rgba(2, 93, 166, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endsection
