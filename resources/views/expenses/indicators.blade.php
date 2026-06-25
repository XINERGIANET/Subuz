@extends('template.app')

@section('title', 'Indicadores de Gastos')

@section('styles')
    <style>
        .expense-dashboard {
            --expense-blue: #3B82F6;
            --expense-green: #22C55E;
            --expense-purple: #8B5CF6;
            --expense-amber: #F59E0B;
            --expense-cyan: #06B6D4;
            --expense-red: #EF4444;
            --expense-ink: #0F172A;
            --expense-muted: #64748B;
            --expense-line: #E2E8F0;
        }

        .expense-dashboard .dashboard-toolbar,
        .expense-dashboard .indicator-card,
        .expense-dashboard .chart-card {
            background: #FFFFFF;
            border: 1px solid rgba(226, 232, 240, 0.95) !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06) !important;
        }

        .expense-dashboard .dashboard-toolbar {
            padding: 1rem;
        }

        .expense-dashboard .dashboard-title {
            color: var(--expense-ink);
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .expense-dashboard .dashboard-subtitle,
        .expense-dashboard .chart-subtitle,
        .expense-dashboard .metric-label {
            color: var(--expense-muted);
        }

        .expense-dashboard .metric-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .expense-dashboard .metric-value {
            color: var(--expense-ink);
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .expense-dashboard .indicator-card {
            min-height: 132px;
        }

        .expense-dashboard .indicator-icon {
            align-items: center;
            border-radius: 50%;
            display: flex;
            flex: 0 0 52px;
            height: 52px;
            justify-content: center;
            width: 52px;
        }

        .expense-dashboard .indicator-icon i {
            font-size: 1.65rem;
        }

        .expense-dashboard .chart-card {
            min-height: 360px;
        }

        .expense-dashboard .chart-shell {
            height: 270px;
            position: relative;
            width: 100%;
        }

        .expense-dashboard .chart-shell.chart-shell-lg {
            height: 300px;
        }

        .expense-dashboard .legend-dot {
            border-radius: 999px;
            display: inline-block;
            flex: 0 0 10px;
            height: 10px;
            width: 10px;
        }

        .expense-dashboard .mini-table td,
        .expense-dashboard .mini-table th {
            padding: 0.45rem 0.35rem;
            vertical-align: middle;
        }

        .expense-dashboard .empty-state {
            align-items: center;
            color: var(--expense-muted);
            display: flex;
            height: 100%;
            justify-content: center;
            min-height: 220px;
            text-align: center;
        }

        .expense-dashboard .progress {
            height: 0.55rem;
        }

        @media (max-width: 767.98px) {
            .expense-dashboard .dashboard-title {
                font-size: 1.2rem;
            }

            .expense-dashboard .chart-card {
                min-height: auto;
            }

            .expense-dashboard .chart-shell,
            .expense-dashboard .chart-shell.chart-shell-lg {
                height: 250px;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $formatMoney = function($value) {
            return 'S/' . number_format((float) $value, 2);
        };

        $trendClass = function($value) {
            if ($value > 0) return 'text-danger';
            if ($value < 0) return 'text-success';
            return 'text-muted';
        };

        $trendIcon = function($value) {
            if ($value > 0) return 'ti-trending-up';
            if ($value < 0) return 'ti-trending-down';
            return 'ti-minus';
        };
    @endphp

    <nav class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item">Gastos</li>
            <li class="breadcrumb-item active">Indicadores</li>
        </ol>
    </nav>

    <div class="expense-dashboard">
        <div class="dashboard-toolbar mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg">
                    <div class="dashboard-title">Dashboard de Gastos</div>
                    <div class="dashboard-subtitle">Resumen general de gastos por categor&iacute;a y subcategor&iacute;a</div>
                </div>
                <div class="col-lg-auto">
                    <form class="row g-2 align-items-end" method="GET" action="{{ route('expenses.indicators') }}">
                        <div class="col-sm-auto">
                            <label class="form-label small fw-bold text-muted mb-1">Desde</label>
                            <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                        </div>
                        <div class="col-sm-auto">
                            <label class="form-label small fw-bold text-muted mb-1">Hasta</label>
                            <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                        </div>
                        <div class="col-sm-auto">
                            <label class="form-label small fw-bold text-muted mb-1">Categor&iacute;a</label>
                            <select class="form-select" name="expense_category_id">
                                <option value="">Todas</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @if((string) $categoryId === (string) $category->id) selected @endif>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-auto d-flex gap-2">
                            <button class="btn btn-brand" type="submit">
                                <i class="ti ti-filter me-1"></i> Filtrar
                            </button>
                            <a class="btn btn-outline-secondary" href="{{ route('expenses.indicators') }}">
                                <i class="ti ti-refresh me-1"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row row-cards mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card indicator-card">
                    <div class="card-body d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label mb-2">Gasto Total</div>
                            <div class="metric-value">{{ $formatMoney($totalAmount) }}</div>
                            <div class="small mt-3 {{ $trendClass($metricChanges['total']) }}">
                                <i class="ti {{ $trendIcon($metricChanges['total']) }} me-1"></i>
                                {{ abs($metricChanges['total']) }}% vs. periodo anterior
                            </div>
                        </div>
                        <div class="indicator-icon bg-blue-lt text-blue">
                            <i class="ti ti-currency-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card indicator-card">
                    <div class="card-body d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label mb-2">N&uacute;mero de Gastos</div>
                            <div class="metric-value">{{ number_format($expenseCount) }}</div>
                            <div class="small mt-3 {{ $trendClass($metricChanges['count']) }}">
                                <i class="ti {{ $trendIcon($metricChanges['count']) }} me-1"></i>
                                {{ abs($metricChanges['count']) }}% vs. periodo anterior
                            </div>
                        </div>
                        <div class="indicator-icon bg-green-lt text-green">
                            <i class="ti ti-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card indicator-card">
                    <div class="card-body d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label mb-2">Gasto Promedio</div>
                            <div class="metric-value">{{ $formatMoney($averageAmount) }}</div>
                            <div class="small mt-3 {{ $trendClass($metricChanges['average']) }}">
                                <i class="ti {{ $trendIcon($metricChanges['average']) }} me-1"></i>
                                {{ abs($metricChanges['average']) }}% vs. periodo anterior
                            </div>
                        </div>
                        <div class="indicator-icon bg-purple-lt text-purple">
                            <i class="ti ti-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card indicator-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3">
                            <div class="min-w-0">
                                <div class="metric-label mb-2">Categor&iacute;a Principal</div>
                                <div class="metric-value text-truncate">{{ $topCategory['name'] }}</div>
                            </div>
                            <div class="indicator-icon bg-yellow-lt text-yellow">
                                <i class="ti ti-target-arrow"></i>
                            </div>
                        </div>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-warning" style="width: {{ min($topCategory['percent'], 100) }}%"></div>
                        </div>
                        <div class="small text-muted mt-2">
                            {{ $formatMoney($topCategory['total']) }} · {{ $topCategory['percent'] }}% del total
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards">
            <div class="col-xl-6">
                <div class="card chart-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h3 class="card-title mb-1">Gastos por Categor&iacute;a</h3>
                                <div class="chart-subtitle small">Distribuci&oacute;n del gasto total por categor&iacute;a</div>
                            </div>
                            <i class="ti ti-dots-vertical text-muted"></i>
                        </div>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                @if($categorySummary->count())
                                    <div class="chart-shell">
                                        <canvas id="categoryChart"></canvas>
                                    </div>
                                @else
                                    <div class="empty-state">No hay datos para el periodo seleccionado</div>
                                @endif
                            </div>
                            <div class="col-md-7">
                                <div class="table-responsive">
                                    <table class="table mini-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Categor&iacute;a</th>
                                                <th class="text-end">Monto</th>
                                                <th class="text-end">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($categorySummary as $item)
                                                <tr>
                                                    <td>
                                                        <span class="d-inline-flex align-items-center gap-2">
                                                            <span class="legend-dot" style="background: {{ $item['color'] }}"></span>
                                                            <span>{{ $item['name'] }}</span>
                                                        </span>
                                                    </td>
                                                    <td class="text-end fw-bold">{{ $formatMoney($item['total']) }}</td>
                                                    <td class="text-end">{{ $item['percent'] }}%</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-muted text-center py-4">Sin datos</td>
                                                </tr>
                                            @endforelse
                                            @if($categorySummary->count())
                                                <tr>
                                                    <td class="fw-bold">Total</td>
                                                    <td class="text-end fw-bold">{{ $formatMoney($totalAmount) }}</td>
                                                    <td class="text-end fw-bold">100%</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card chart-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h3 class="card-title mb-1">Top 5 Categor&iacute;as por Gasto</h3>
                                <div class="chart-subtitle small">Las categor&iacute;as con mayor gasto en el periodo</div>
                            </div>
                            <i class="ti ti-dots-vertical text-muted"></i>
                        </div>
                        @if($topCategories->count())
                            <div class="chart-shell chart-shell-lg">
                                <canvas id="topCategoriesChart"></canvas>
                            </div>
                        @else
                            <div class="empty-state">No hay datos para graficar</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card chart-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h3 class="card-title mb-1">Gastos por Subcategor&iacute;a</h3>
                                <div class="chart-subtitle small">Desglose de gastos por subcategor&iacute;a (Top 8)</div>
                            </div>
                            <i class="ti ti-dots-vertical text-muted"></i>
                        </div>
                        @if($subcategorySummary->count())
                            <div class="chart-shell chart-shell-lg">
                                <canvas id="subcategoriesChart"></canvas>
                            </div>
                        @else
                            <div class="empty-state">No hay datos para graficar</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card chart-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h3 class="card-title mb-1">Evoluci&oacute;n de Gastos en el Tiempo</h3>
                                <div class="chart-subtitle small">Comparaci&oacute;n de gastos en el periodo seleccionado</div>
                            </div>
                            <i class="ti ti-dots-vertical text-muted"></i>
                        </div>
                        <div class="chart-shell chart-shell-lg">
                            <canvas id="evolutionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center text-muted small mt-4">
            <i class="ti ti-info-circle me-1"></i> Todos los montos est&aacute;n expresados en soles.
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categorySummary = @json($categorySummary->values());
            const topCategories = @json($topCategories->values());
            const subcategorySummary = @json($subcategorySummary->values());
            const evolutionLabels = @json($evolutionLabels);
            const evolutionData = @json($evolutionData);
            const evolutionLabel = @json($evolutionLabel);
            const gridColor = 'rgba(148, 163, 184, 0.22)';
            const textColor = '#475569';
            const moneyFormatter = new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN',
                minimumFractionDigits: 2
            });

            function money(value) {
                return moneyFormatter.format(Number(value || 0));
            }

            function compactMoney(value) {
                const number = Number(value || 0);
                if (Math.abs(number) >= 1000) {
                    return 'S/' + (number / 1000).toFixed(number >= 10000 ? 0 : 1) + 'K';
                }

                return 'S/' + number.toFixed(0);
            }

            const sharedPlugins = {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${money(context.parsed.x ?? context.parsed ?? context.raw)}`;
                        }
                    }
                }
            };

            if (document.getElementById('categoryChart')) {
                new Chart(document.getElementById('categoryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: categorySummary.map(item => item.name),
                        datasets: [{
                            data: categorySummary.map(item => item.total),
                            backgroundColor: categorySummary.map(item => item.color),
                            borderColor: '#FFFFFF',
                            borderWidth: 3,
                            cutout: '58%'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: sharedPlugins
                    }
                });
            }

            if (document.getElementById('topCategoriesChart')) {
                new Chart(document.getElementById('topCategoriesChart'), {
                    type: 'bar',
                    data: {
                        labels: topCategories.map(item => item.name),
                        datasets: [{
                            label: 'Monto',
                            data: topCategories.map(item => item.total),
                            backgroundColor: '#3B82F6',
                            borderRadius: 4,
                            barThickness: 16
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: sharedPlugins,
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: textColor, callback: compactMoney },
                                title: { display: true, text: 'Monto (S/)', color: textColor }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { color: textColor }
                            }
                        }
                    }
                });
            }

            if (document.getElementById('subcategoriesChart')) {
                new Chart(document.getElementById('subcategoriesChart'), {
                    type: 'bar',
                    data: {
                        labels: subcategorySummary.map(item => item.name),
                        datasets: [{
                            label: 'Monto',
                            data: subcategorySummary.map(item => item.total),
                            backgroundColor: '#3B82F6',
                            borderRadius: 4,
                            barThickness: 14
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: sharedPlugins,
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: textColor, callback: compactMoney },
                                title: { display: true, text: 'Monto (S/)', color: textColor }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { color: textColor }
                            }
                        }
                    }
                });
            }

            new Chart(document.getElementById('evolutionChart'), {
                type: 'line',
                data: {
                    labels: evolutionLabels,
                    datasets: [{
                        label: evolutionLabel,
                        data: evolutionData,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.12)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointBackgroundColor: '#3B82F6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: { boxWidth: 14, color: textColor }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${money(context.parsed.y)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: { color: textColor, callback: compactMoney }
                        }
                    }
                }
            });
        });
    </script>
@endsection
