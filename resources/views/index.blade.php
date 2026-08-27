@extends('template.app')

@section('title', 'Reporte de Ingresos')

@section('styles')
    <style>
        /* Allow scroll on the view if content is larger */
        body {
            overflow-y: auto !important;
            background-color: #f3f4f6;
        }

        .page-body {
            min-height: calc(100vh - 110px);
            margin-top: 0.5rem;
            padding-bottom: 0.5rem;
            display: flex;
            flex-direction: column;
        }

        .container-xl {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            max-width: 100%;
            padding: 0 1rem;
        }

        /* Controls */
        .dashboard-controls {
            position: absolute;
            top: -45px;
            right: 0;
            z-index: 100;
        }

        /* Layout Grid */
        .dashboard-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: visible;
            /* Allow overflow to trigger main window scroll */
            position: relative;
            background-color: #f3f4f6;
            border-radius: 8px;
        }

        .dashboard-wrapper.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            padding: 1rem;
            background-color: #f3f4f6;
            overflow: auto;
        }

        .dashboard-wrapper.fullscreen .zoom-controls {
            display: none !important;
        }

        .dashboard-wrapper.fullscreen .dashboard-controls {
            top: 1rem;
            right: 1rem;
        }

        .dashboard-grid {
            display: flex;
            flex-grow: 1;
            gap: 1rem;
            height: 100%;
            min-height: 500px;
            transform-origin: top left;
            transition: transform 0.2s ease;
        }

        .sidebar-panel {
            width: 22%;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .main-panel {
            width: 78%;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Cards Setup */
        .card-full {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            background: var(--card-bg, #fff);
        }

        .card-full .card-body {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 1rem;
            position: relative;
        }

        [data-bs-theme='dark'] .card-full {
            background: #1e293b;
            border-color: rgba(255, 255, 255, 0.05);
        }

        /* Summary Bar */
        .summary-bar {
            display: flex;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            min-height: 50px;
        }

        .summary-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.25rem;
            text-align: center;
            color: white;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .summary-item:last-child {
            border-right: none;
        }

        /* Typography & Metrics */
        .metric-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .metric-title-red {
            color: #d32f2f !important;
        }

        .metric-title-dark {
            color: #333 !important;
        }

        .metric-value {
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .metric-value-lg {
            font-size: 1.8rem;
            font-weight: 800;
        }

        .metric-value-xl {
            font-size: 2.2rem;
            font-weight: 900;
        }

        /* Chart Containers */
        .chart-container {
            position: relative;
            width: 100%;
            flex-grow: 1;
            min-height: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chart-container canvas {
            max-height: 100%;
        }

        /* Gauge Label */
        .gauge-label {
            position: absolute;
            bottom: 5%;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            width: 100%;
        }

        /* Bottom Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 0.75rem;
            width: 35%;
        }

        /* Interactive Card Styling */
        .kpi-card {
            background: var(--card-bg, #fff);
            border: 1px solid rgba(211, 47, 47, 0.3);
            /* Red accent border */
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            position: relative;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.15);
            border-color: #d32f2f;
        }

        .summary-item.clickable-metric,
        .card.clickable-metric {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .summary-item.clickable-metric:hover {
            filter: brightness(1.1);
            transform: scale(1.02);
            z-index: 2;
        }

        .card.clickable-metric:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .card-clickable-badge {
            position: absolute;
            top: 4px;
            right: 6px;
            font-size: 0.65rem;
            opacity: 0.6;
        }

        [data-bs-theme='dark'] .kpi-card {
            background: #1e293b;
            border-color: rgba(211, 47, 47, 0.4);
        }

        /* Form Styles */
        .filter-btn-red {
            background-color: #d32f2f;
            color: white;
            border-radius: 6px 6px 0 0;
            padding: 4px 10px;
            font-size: 0.8rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0;
        }

        .filter-select {
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.85rem;
            color: #555;
            background-color: #f9f9f9;
            width: 100%;
        }
    </style>
@endsection

@section('content')
    <div class="dashboard-wrapper" id="dashboard_wrapper">
        <div class="dashboard-controls d-flex justify-content-end gap-2 d-print-none align-items-center">
            <button class="btn btn-sm btn-white shadow-sm fw-bold border" id="btn_fullscreen"
                title="Ver en pantalla completa">
                <i class="ti ti-maximize me-1"></i> Pantalla Completa
            </button>
        </div>

        <div class="dashboard-grid" id="dashboard_grid">
            <!-- SIDEBAR (22%) -->
            <div class="sidebar-panel">

                <!-- Filters -->
                <form id="filter_form" class="m-0 d-flex flex-column gap-2">
                    <div>
                        <div class="filter-btn-red">Seleccione A&ntilde;o</div>
                        <select class="filter-select" id="filter_year">
                            @for($i = 2023; $i <= 2030; $i++)
                                <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <div class="filter-btn-red mt-1">Seleccione Mes</div>
                        <select class="filter-select" id="filter_month">
                            <option value="">Todo el A&ntilde;o</option>
                            @php $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']; @endphp
                            @foreach($months as $index => $name)
                                <option value="{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}" {{ ($index + 1) == date('m') ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="filter-btn-red mt-1">Seleccione D&iacute;a</div>
                        <select class="filter-select" id="filter_day">
                            <option value="">Todo el Mes</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-danger w-100 mt-1 fw-bold"
                        style="background-color: #d32f2f;">Actualizar</button>
                </form>

                <!-- Alert Success equivalent -->
                <div class="card bg-success-lt border-success" style="flex-grow: 0; border-radius: 8px;">
                    <div class="card-body p-2 d-flex align-items-center">
                        <i class="ti ti-check text-success fs-3 me-2"></i>
                        <span class="text-success fw-bold" style="font-size: 0.75rem;" id="status_alert">Excelente, balance
                            positivo.</span>
                    </div>
                </div>

                <!-- Rentabilidad Bruta (Gauge) -->
                <div class="card card-full">
                    <div class="card-body p-2 d-flex flex-column align-items-center justify-content-center" style="min-height: 0;">
                        <div class="metric-title metric-title-red mb-1">Rentabilidad Bruta</div>
                        <div class="chart-container" style="position: relative; width: 100%; height: 100%; min-height: 0;">
                            <canvas id="rentabilidad_gauge"></canvas>
                            <div class="gauge-label" style="bottom: 5px;">
                                <div class="metric-value-lg text-dark" style="font-size: 1.6rem; line-height: 1;"
                                    id="rentabilidad_pct">0%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ingresos vs Egresos (Donut) -->
                <div class="card card-full">
                    <div class="card-body p-2 d-flex flex-column align-items-center justify-content-center" style="min-height: 0;">
                        <div class="chart-container" style="position: relative; width: 100%; height: 100%; min-height: 0;">
                            <canvas id="ingresos_egresos_donut"></canvas>
                        </div>
                        <div class="d-flex justify-content-center gap-3 mt-2 small fw-bold">
                            <span style="color: #d32f2f;"><i class="ti ti-circle-filled"></i> Ingresos</span>
                            <span style="color: #1a3a8a;"><i class="ti ti-circle-filled"></i> Egresos</span>
                        </div>
                        <div class="small fw-bold mt-1 text-muted" id="donut_total">S/0.00</div>
                    </div>
                </div>
            </div>

            <!-- MAIN PANEL (78%) -->
            <div class="main-panel">

                <!-- TOP ROW (35%) -->
                <div class="d-flex gap-2 w-100" style="flex: 0 0 30%;">
                    <!-- Big Gauge: Ventas -->
                    <div class="card card-full" style="flex: 0 0 40%; border-radius: 12px; background: #fff;">
                        <div class="card-body p-2 d-flex flex-column justify-content-center" style="min-height: 0;">
                            <div class="chart-container" style="position: relative; width: 100%; height: 100%; min-height: 0;">
                                <canvas id="ventas_gauge"></canvas>
                                <div class="gauge-label" style="bottom: 5px;">
                                    <div class="metric-value-xl text-dark" style="line-height: 1;" id="ventas_gauge_text">
                                        S/0.00</div>
                                    <div class="metric-title text-success mt-1" id="ventas_gauge_sub">Total</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comp Cards -->
                    <div class="d-flex flex-column gap-2" style="flex: 0 0 25%;">
                        <div class="card card-full border-danger clickable-metric btn-open-detail" data-type="balance" title="Click para ver detalle de Balance" style="border-width: 1px; border-radius: 10px; position: relative;">
                            <span class="card-clickable-badge text-muted"><i class="ti ti-eye"></i></span>
                            <div class="card-body text-center p-2 d-flex flex-column justify-content-center">
                                <div class="metric-value text-success mb-0" id="balance_total">S/0.00</div>
                                <div class="metric-title metric-title-red mt-1">Balance Actual</div>
                            </div>
                        </div>
                        <div class="card card-full border-danger clickable-metric btn-open-detail" data-type="gastos" title="Click para ver detalle de Gastos" style="border-width: 1px; border-radius: 10px; position: relative;">
                            <span class="card-clickable-badge text-muted"><i class="ti ti-eye"></i></span>
                            <div class="card-body text-center p-2 d-flex flex-column justify-content-center">
                                <div class="metric-value text-danger mb-0" id="gastos_totales">S/0.00</div>
                                <div class="metric-title metric-title-red mt-1">Gastos Totales</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sparkline Chart -->
                    <div class="card card-full clickable-metric btn-open-detail" data-type="ingresos_caja" title="Click para ver detalle de Ingresos a Caja" style="flex: 1; min-width: 0; border-radius: 10px; position: relative;">
                        <span class="card-clickable-badge text-muted"><i class="ti ti-eye"></i></span>
                        <div class="card-body p-2 d-flex flex-column" style="min-height: 0;">
                            <div class="text-center">
                                <div class="metric-value text-dark" id="ingreso_caja">S/0.00</div>
                                <div class="metric-title text-muted">Ingresos a Caja</div>
                            </div>
                            <div class="chart-container mt-1" style="position: relative; width: 100%; height: 100%; min-height: 0;">
                                <canvas id="sparkline_chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MIDDLE ROW (Summary Bar - 10%) -->
                <div class="summary-bar" style="flex: 0 0 50px;">
                    <div class="summary-item clickable-metric btn-open-detail" data-type="ventas" title="Click para ver detalle de Ventas" style="background-color: #d4a373;">
                        <span class="metric-title text-white">Ventas <i class="ti ti-eye fs-5 ms-1"></i></span>
                        <span class="metric-value" id="summary_ventas" style="font-size: 1.1rem;">S/0.00</span>
                    </div>
                    <div class="summary-item clickable-metric btn-open-detail" data-type="ingresos_caja" title="Click para ver detalle de Ingresos a Caja" style="background-color: #28a745;">
                        <span class="metric-title text-white">Ingresos Caja <i class="ti ti-eye fs-5 ms-1"></i></span>
                        <span class="metric-value" id="summary_caja" style="font-size: 1.1rem;">S/0.00</span>
                    </div>
                    <div class="summary-item clickable-metric btn-open-detail" data-type="balance" title="Click para ver detalle del Balance" style="background-color: #2e8b57;">
                        <span class="metric-title text-white">Balance Total <i class="ti ti-eye fs-5 ms-1"></i></span>
                        <span class="metric-value" id="summary_balance" style="font-size: 1.1rem;">S/0.00</span>
                    </div>
                    <div class="summary-item" style="background-color: #3cb371;">
                        <span class="metric-title text-white">Rentabilidad</span>
                        <span class="metric-value" id="summary_rentabilidad" style="font-size: 1.1rem;">S/0.00</span>
                    </div>
                    <div class="summary-item clickable-metric btn-open-detail" data-type="gastos" title="Click para ver detalle de Gastos" style="background-color: #d32f2f;">
                        <span class="metric-title text-white">Gastos <i class="ti ti-eye fs-5 ms-1"></i></span>
                        <span class="metric-value" id="summary_gastos" style="font-size: 1.1rem;">S/0.00</span>
                    </div>
                    <div class="summary-item" style="background-color: #adb5bd;">
                        <span class="metric-title text-white">% Rentabilidad</span>
                        <span class="metric-value" id="summary_pendiente" style="font-size: 1.1rem;">0%</span>
                    </div>
                </div>

                <!-- BOTTOM ROW (55%) -->
                <div class="d-flex gap-2 w-100" style="flex: 1; min-height: 0;">
                    <!-- KPI Grid -->
                    <div class="kpi-grid" style="width: auto; flex: 0 0 35%;">
                        <!-- We map Subuz metrics to these 6 cards -->
                        <div class="kpi-card btn-open-detail" data-type="pendiente" title="Click para ver detalle de Pendientes / Crédito">
                            <span class="card-clickable-badge text-muted"><i class="ti ti-zoom-in"></i></span>
                            <div class="metric-value text-dark" id="kpi_1">S/0.00</div>
                            <div class="metric-title metric-title-red mt-1">Pendiente / Cr&eacute;dito</div>
                        </div>
                        <div class="kpi-card btn-open-detail" data-type="efectivo" title="Click para ver detalle de Efectivo (Ingresos / Egresos / Cajas)">
                            <span class="card-clickable-badge text-muted"><i class="ti ti-zoom-in"></i></span>
                            <div class="metric-value text-dark" id="kpi_2">S/0.00</div>
                            <div class="metric-title metric-title-red mt-1">Efectivo</div>
                        </div>
                        <div class="kpi-card btn-open-detail" data-type="transferencias" title="Click para ver detalle de Transferencias Bancarias">
                            <span class="card-clickable-badge text-muted"><i class="ti ti-zoom-in"></i></span>
                            <div class="metric-value text-dark" id="kpi_3">S/0.00</div>
                            <div class="metric-title metric-title-red mt-1">Transferencias</div>
                        </div>
                        <div class="kpi-card btn-open-detail" data-type="yape_plin" title="Click para ver detalle de Yape / Plin">
                            <span class="card-clickable-badge text-muted"><i class="ti ti-zoom-in"></i></span>
                            <div class="metric-value text-dark" id="kpi_4">S/0.00</div>
                            <div class="metric-title metric-title-red mt-1">Yape / Plin</div>
                        </div>
                        <div class="kpi-card btn-open-detail" data-type="ventas" title="Click para ver detalle de Ventas">
                            <span class="card-clickable-badge text-muted"><i class="ti ti-zoom-in"></i></span>
                            <div class="metric-value text-dark" id="kpi_5">0</div>
                            <div class="metric-title metric-title-red mt-1" id="kpi_5_label">Ventas Hoy</div>
                        </div>
                        <div class="kpi-card" title="Despachos realizados en el periodo">
                            <div class="metric-value text-dark" id="kpi_6">0</div>
                            <div class="metric-title metric-title-red mt-1" id="kpi_6_label">Despachados Hoy</div>
                        </div>
                    </div>

                    <!-- Bar Chart -->
                    <div class="card card-full"
                        style="flex: 1; min-width: 0; border: 1px solid rgba(211, 47, 47, 0.3); border-radius: 10px;">
                        <div class="card-body p-2 d-flex flex-column" style="min-height: 0;">
                            <div class="text-center metric-title metric-title-red mb-1" id="bar_chart_title">Ingresos y Egresos por Mes</div>
                            <div class="chart-container flex-grow-1" style="position: relative; width: 100%; height: 100%; min-height: 0; padding: 0 10px 15px 10px;">
                                <canvas id="bar_chart_mensual"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Detalle de Métricas / Movimientos -->
    <div class="modal modal-blur fade" id="dashboardDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-danger text-white py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-report-money fs-1"></i>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-0" id="detailModalTitle">Detalle de Movimientos</h5>
                            <span class="small text-white-50" id="detailModalSubtitle">Periodo seleccionado</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3">
                    <!-- Summary Badges in Modal -->
                    <div class="row g-2 mb-3" id="detailSummaryRow">
                        <div class="col-md-4">
                            <div class="card p-2 border-0 bg-success-lt text-center">
                                <div class="text-uppercase small fw-bold text-success">Total Ingresos / Cobros</div>
                                <div class="h2 mb-0 fw-bold text-success" id="modalTotalIncome">S/ 0.00</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-2 border-0 bg-danger-lt text-center">
                                <div class="text-uppercase small fw-bold text-danger">Total Egresos / Gastos</div>
                                <div class="h2 mb-0 fw-bold text-danger" id="modalTotalExpense">S/ 0.00</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-2 border-0 bg-primary-lt text-center">
                                <div class="text-uppercase small fw-bold text-primary">Saldo Neto / Total</div>
                                <div class="h2 mb-0 fw-bold text-primary" id="modalNetTotal">S/ 0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Filter Search Input within modal table -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="small text-muted fw-bold text-uppercase">
                            <i class="ti ti-list-details me-1"></i> Lista de Registros (<span id="modalItemsCount">0</span>)
                        </div>
                        <div style="width: 250px;">
                            <input type="text" id="modalSearchFilter" class="form-control form-control-sm" placeholder="Buscar en la tabla...">
                        </div>
                    </div>

                    <!-- Movements Table -->
                    <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-vcenter table-hover table-striped mb-0" id="detailTable">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th style="width: 140px;">Fecha / Hora</th>
                                    <th>Tipo</th>
                                    <th>Concepto / Referencia</th>
                                    <th>Caja / Origen</th>
                                    <th>Responsable</th>
                                    <th>Método</th>
                                    <th class="text-end" style="width: 120px;">Monto</th>
                                </tr>
                            </thead>
                            <tbody id="detailTableBody">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Theme colors
        const isDark = document.body.getAttribute('data-bs-theme') === 'dark';
        const textColor = isDark ? '#f8fafc' : '#333';
        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

        // Chart instances
        let chartRentabilidad, chartDonut, chartVentas, chartSparkline, chartBar;
        const MONTH_NAMES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        const MONTH_SHORT_NAMES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        const DEFAULT_YEAR = '{{ date('Y') }}';
        const DEFAULT_MONTH = '{{ date('m') }}';
        const DEFAULT_DAY = '{{ date('d') }}';

        $(document).ready(function () {
            populateDayOptions();
            initCharts();
            loadDashboardData();

            $('#filter_year, #filter_month').on('change', function () {
                populateDayOptions();
            });

            $('#filter_form').submit(function (e) {
                e.preventDefault();
                loadDashboardData();
            });

            // Fullscreen Logic
            const $wrapper = $('#dashboard_wrapper');
            const $btnFull = $('#btn_fullscreen');
            let isFullscreen = false;

            $btnFull.click(function () {
                isFullscreen = !isFullscreen;

                if (isFullscreen) {
                    $wrapper.addClass('fullscreen');
                    $btnFull.html('<i class="ti ti-minimize me-1"></i> Salir de Pantalla Completa');
                    $('body').css('overflow', 'hidden');
                } else {
                    $wrapper.removeClass('fullscreen');
                    $btnFull.html('<i class="ti ti-maximize me-1"></i> Pantalla Completa');
                    $('body').css('overflow', 'auto');
                }
            });
        });

        function initCharts() {
            // 1. Rentabilidad Bruta (Gauge)
            const ctxRent = document.getElementById('rentabilidad_gauge').getContext('2d');
            chartRentabilidad = new Chart(ctxRent, {
                type: 'doughnut',
                data: {
                    labels: ['Rentabilidad', 'Costo'],
                    datasets: [{
                        data: [0, 100],
                        backgroundColor: ['#28a745', '#e0e0e0'],
                        borderWidth: 0,
                        cutout: '75%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2, // Forces a 2:1 rectangle to perfectly fit the half circle
                    rotation: -90,
                    circumference: 180,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } }
                }
            });

            // 2. Ingresos vs Egresos (Donut)
            const ctxDonut = document.getElementById('ingresos_egresos_donut').getContext('2d');
            chartDonut = new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: ['Ingresos', 'Egresos'],
                    datasets: [{
                        data: [1, 1],
                        backgroundColor: ['#d32f2f', '#1a3a8a'], // Red and Dark Blue
                        borderWidth: 0,
                        cutout: '50%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1,
                    plugins: { legend: { display: false } },
                    layout: { padding: 5 }
                }
            });

            // 3. Ventas Totales (Big Gauge)
            const ctxVentas = document.getElementById('ventas_gauge').getContext('2d');
            chartVentas = new Chart(ctxVentas, {
                type: 'doughnut',
                data: {
                    labels: ['Realizado'],
                    datasets: [{
                        data: [100],
                        backgroundColor: ['#d32f2f'], // Gesrest Red
                        borderWidth: 0,
                        cutout: '65%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2, // Forces perfect fit for half circle
                    rotation: -90,
                    circumference: 180,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } }
                }
            });

            // 4. Sparkline
            const ctxSpark = document.getElementById('sparkline_chart').getContext('2d');
            chartSparkline = new Chart(ctxSpark, {
                type: 'line',
                data: {
                    labels: MONTH_SHORT_NAMES,
                    datasets: [{
                        label: 'Ingresos a Caja',
                        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#2e8b57', // Green
                        backgroundColor: 'rgba(46, 139, 87, 0.15)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { display: false },
                        y: { display: false, min: 0 }
                    }
                }
            });

            // 5. Bar Chart Mensual
            const ctxBar = document.getElementById('bar_chart_mensual').getContext('2d');
            chartBar = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: MONTH_SHORT_NAMES,
                    datasets: [
                        {
                            label: 'Ingresos',
                            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                            backgroundColor: '#6b8e23', // Olive green
                            borderRadius: 2
                        },
                        {
                            label: 'Egresos',
                            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                            backgroundColor: '#d32f2f', // Red
                            borderRadius: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: textColor, font: { size: 10 } }, grid: { display: false } },
                        y: { ticks: { color: textColor, font: { size: 10 } }, grid: { color: gridColor } }
                    }
                }
            });
        }

        function pad2(value) {
            return String(value).padStart(2, '0');
        }

        function getTodayString() {
            const now = new Date();
            return `${now.getFullYear()}-${pad2(now.getMonth() + 1)}-${pad2(now.getDate())}`;
        }

        function populateDayOptions() {
            const year = $('#filter_year').val();
            const month = $('#filter_month').val();
            const previousDay = $('#filter_day').val();
            const $day = $('#filter_day');

            $day.empty();
            $day.append('<option value="">Todo el Mes</option>');

            if (!month) {
                $day.val('');
                $day.prop('disabled', true);
                return;
            }

            const lastDay = new Date(parseInt(year, 10), parseInt(month, 10), 0).getDate();

            for (let day = 1; day <= lastDay; day++) {
                const value = pad2(day);
                $day.append(`<option value="${value}">${day}</option>`);
            }

            if (previousDay && parseInt(previousDay, 10) <= lastDay) {
                $day.val(previousDay);
            } else if (year === DEFAULT_YEAR && month === DEFAULT_MONTH && parseInt(DEFAULT_DAY, 10) <= lastDay) {
                $day.val(DEFAULT_DAY);
            } else {
                $day.val('');
            }

            $day.prop('disabled', false);
        }

        function getSelectedRange() {
            const year = $('#filter_year').val();
            const month = $('#filter_month').val();
            const day = $('#filter_day').val();

            if (month && day) {
                const selectedDate = `${year}-${month}-${day}`;

                return {
                    year,
                    month,
                    day,
                    period: 'day',
                    startDate: selectedDate,
                    endDate: selectedDate,
                    label: `D\u00eda: ${day}/${month}/${year}`,
                    chartTitle: 'Ingresos y Egresos del D\u00eda',
                    dailyStatsDate: selectedDate
                };
            }

            if (month) {
                const lastDay = new Date(parseInt(year, 10), parseInt(month, 10), 0).getDate();

                return {
                    year,
                    month,
                    day: '',
                    period: 'month',
                    startDate: `${year}-${month}-01`,
                    endDate: `${year}-${month}-${lastDay}`,
                    label: `Mes: ${MONTH_NAMES[parseInt(month, 10) - 1]} ${year}`,
                    chartTitle: 'Ingresos y Egresos por Semana del Mes',
                    dailyStatsDate: getTodayString()
                };
            }

            return {
                year,
                month: '',
                day: '',
                period: 'year',
                startDate: `${year}-01-01`,
                endDate: `${year}-12-31`,
                label: `A\u00f1o: ${year}`,
                chartTitle: 'Ingresos y Egresos por Mes',
                dailyStatsDate: getTodayString()
            };
        }

        function updatePeriodTexts(range) {
            $('#ventas_gauge_sub').text(range.label);
            $('#bar_chart_title').text(range.chartTitle);

            if (range.period === 'day') {
                $('#kpi_5_label').text('Ventas D\u00eda');
                $('#kpi_6_label').text('Despachados D\u00eda');
            } else {
                $('#kpi_5_label').text('Ventas Hoy');
                $('#kpi_6_label').text('Despachados Hoy');
            }
        }

        function loadDashboardData() {
            const range = getSelectedRange();
            updatePeriodTexts(range);

            $.ajax({
                url: '{{ route("dashboard.api") }}',
                method: 'GET',
                data: {
                    start_date: range.startDate,
                    end_date: range.endDate,
                    period: range.period
                },
                success: function (res) {
                    // Parse values safely
                    const v_sales = parseFloat(res.sales.replace(/,/g, '')) || 0;
                    const v_expenses = parseFloat(res.expenses.replace(/,/g, '')) || 0;
                    const v_manual_income = parseFloat(res.manual_income.replace(/,/g, '')) || 0;
                    const v_revenues = parseFloat(res.revenues.replace(/,/g, '')) || 0;
                    const v_pending = parseFloat(res.pending.replace(/,/g, '')) || 0;
                    const v_balance = parseFloat(res.total_balance.replace(/,/g, '')) || 0;

                    const totalIngresos = v_sales + v_manual_income;

                    // Update texts
                    $('#ventas_gauge_text').text('S/' + res.sales);

                    $('#balance_total').text('S/' + res.total_balance);
                    $('#gastos_totales').text('S/' + res.expenses);
                    $('#ingreso_caja').text('S/' + res.manual_income);

                    // Summary bar
                    $('#summary_ventas').text('S/' + res.sales);
                    $('#summary_caja').text('S/' + res.manual_income);
                    $('#summary_rentabilidad').text('S/' + res.revenues);
                    $('#summary_balance').text('S/' + res.total_balance);
                    $('#summary_gastos').text('S/' + res.expenses);

                    // Alert & % Rentabilidad
                    let rentPct = 0;
                    if (totalIngresos > 0) {
                        rentPct = Math.max(0, Math.round(((totalIngresos - v_expenses) / totalIngresos) * 100));
                    }
                    $('#summary_pendiente').text(rentPct + '%');

                    if (v_balance >= 0) {
                        $('#status_alert').text('Excelente, su negocio registra ingresos.');
                        $('#status_alert').parent().parent().removeClass('bg-danger-lt border-danger').addClass('bg-success-lt border-success');
                        $('#status_alert').siblings('i').removeClass('ti-alert-triangle text-danger').addClass('ti-check text-success');
                    } else {
                        $('#status_alert').text('Atenci\u00f3n, los gastos superan ingresos.');
                        $('#status_alert').parent().parent().removeClass('bg-success-lt border-success').addClass('bg-danger-lt border-danger');
                        $('#status_alert').siblings('i').removeClass('ti-check text-success').addClass('ti-alert-triangle text-danger');
                    }

                    // Update Donut
                    chartDonut.data.datasets[0].data = [totalIngresos, v_expenses];
                    chartDonut.update();
                    $('#donut_total').text('S/' + totalIngresos.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    // Update Rentabilidad Gauge
                    $('#rentabilidad_pct').text(rentPct + '%');
                    chartRentabilidad.data.datasets[0].data = [rentPct, 100 - rentPct];
                    chartRentabilidad.update();

                    // Update Bar & Sparkline
                    const chartLabels = (res.chartLabels && res.chartLabels.length) ? res.chartLabels : MONTH_SHORT_NAMES;
                    chartBar.data.labels = chartLabels;
                    chartBar.data.datasets[0].data = res.totalSales;
                    chartBar.data.datasets[1].data = res.totalExpenses;
                    chartBar.update();

                    chartSparkline.data.labels = chartLabels;
                    chartSparkline.data.datasets[0].data = res.totalManualIncome || res.totalSales;
                    chartSparkline.update();

                    // Update KPIs from Payment Methods
                    $('#kpi_1').text('S/' + res.pending); // Pendiente

                    if (res.methods && res.methods.length > 0) {
                        let totalEfectivo = 0;
                        let totalTransferencias = 0;
                        let totalDigital = 0;

                        res.methods.forEach(m => {
                            const name = (m.name || '').toLowerCase();
                            const val = parseFloat((m.total || '0').replace(/,/g, '')) || 0;

                            if (name.includes('yape') || name.includes('plin')) {
                                totalDigital += val;
                            } else if (name.includes('efectivo') || name === 'caja') {
                                totalEfectivo += val;
                            } else {
                                totalTransferencias += val;
                            }
                        });

                        $('#kpi_2').text('S/' + totalEfectivo.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#kpi_3').text('S/' + totalTransferencias.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#kpi_4').text('S/' + totalDigital.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    }

                    // Fetch daily stats for the other 2 KPIs
                    fetchDailyStats(range.dailyStatsDate);
                }
            });
        }

        function fetchDailyStats(date) {
            $.ajax({
                url: '{{ route("dashboard.daily.api") }}',
                method: 'GET',
                data: { date: date },
                success: function (res) {
                    $('#kpi_5').text(res.sold || 0);
                    $('#kpi_6').text(res.dispatched || 0);
                },
                error: function () {
                    $('#kpi_5').text('-');
                    $('#kpi_6').text('-');
                }
            });
        }

        // --- DASHBOARD DETAIL MODAL LOGIC ---
        let currentModalItems = [];

        $(document).on('click', '.btn-open-detail', function () {
            const metricType = $(this).data('type');
            if (!metricType) return;

            const range = getSelectedRange();
            loadMetricDetail(metricType, range);
        });

        function loadMetricDetail(type, range) {
            Swal.fire({
                title: 'Cargando desglose...',
                text: 'Consultando ingresos, egresos y cajas...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("dashboard.detail.api") }}',
                method: 'GET',
                data: {
                    type: type,
                    start_date: range.startDate,
                    end_date: range.endDate
                },
                success: function (res) {
                    Swal.close();
                    if (!res.status) {
                        Swal.fire('Error', 'No se pudo obtener el detalle.', 'error');
                        return;
                    }

                    $('#detailModalTitle').text(res.title || 'Detalle de Movimientos');
                    $('#detailModalSubtitle').text('Periodo: ' + range.label);

                    $('#modalTotalIncome').text('S/ ' + res.summary.total_income);
                    $('#modalTotalExpense').text('S/ ' + res.summary.total_expense);
                    $('#modalNetTotal').text('S/ ' + res.summary.net_total);

                    currentModalItems = res.items || [];
                    $('#modalItemsCount').text(currentModalItems.length);
                    $('#modalSearchFilter').val('');

                    renderModalTable(currentModalItems);

                    const modalInstance = new bootstrap.Modal(document.getElementById('dashboardDetailModal'));
                    modalInstance.show();
                },
                error: function () {
                    Swal.close();
                    Swal.fire('Error', 'Ocurrió un error al cargar la información.', 'error');
                }
            });
        }

        function renderModalTable(items) {
            const $tbody = $('#detailTableBody');
            $tbody.empty();

            if (!items || items.length === 0) {
                $tbody.html('<tr><td colspan="7" class="text-center py-4 text-muted"><i class="ti ti-info-circle fs-2 mb-1 d-block"></i>No se encontraron movimientos registrados en este periodo.</td></tr>');
                return;
            }

            let html = '';
            items.forEach(function (item) {
                let badgeClass = 'bg-secondary-lt';
                let amountClass = 'text-dark';
                let sign = '';

                if (item.type === 'income' || item.type === 'sale') {
                    badgeClass = 'bg-success-lt';
                    amountClass = 'text-success fw-bold';
                    sign = '+ ';
                } else if (item.type === 'expense') {
                    badgeClass = 'bg-danger-lt';
                    amountClass = 'text-danger fw-bold';
                    sign = '- ';
                } else if (item.type === 'pending') {
                    badgeClass = 'bg-warning-lt';
                    amountClass = 'text-warning fw-bold';
                }

                html += `
                    <tr>
                        <td class="small">
                            <div class="fw-bold">${item.date}</div>
                            <div class="text-muted" style="font-size: 0.75rem;"><i class="ti ti-clock me-1"></i>${item.time}</div>
                        </td>
                        <td>
                            <span class="badge ${badgeClass} text-uppercase px-2 py-1">${item.type_label}</span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">${item.concept}</div>
                        </td>
                        <td>
                            <span class="badge bg-blue-lt"><i class="ti ti-archive me-1"></i>${item.cashbox || 'Caja'}</span>
                        </td>
                        <td class="small text-muted">
                            <i class="ti ti-user me-1"></i>${item.user || '-'}
                        </td>
                        <td>
                            <span class="small fw-semibold text-secondary">${item.method || '-'}</span>
                        </td>
                        <td class="text-end ${amountClass}" style="font-size: 1.05rem;">
                            ${sign}S/ ${parseFloat(item.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                    </tr>
                `;
            });

            $tbody.html(html);
        }

        // Modal Search Filter
        $('#modalSearchFilter').on('keyup', function () {
            const query = $(this).val().toLowerCase();
            if (!query) {
                renderModalTable(currentModalItems);
                $('#modalItemsCount').text(currentModalItems.length);
                return;
            }

            const filtered = currentModalItems.filter(item => {
                return (item.date && item.date.toLowerCase().includes(query)) ||
                    (item.time && item.time.toLowerCase().includes(query)) ||
                    (item.type_label && item.type_label.toLowerCase().includes(query)) ||
                    (item.concept && item.concept.toLowerCase().includes(query)) ||
                    (item.cashbox && item.cashbox.toLowerCase().includes(query)) ||
                    (item.user && item.user.toLowerCase().includes(query)) ||
                    (item.method && item.method.toLowerCase().includes(query)) ||
                    (item.amount && item.amount.toString().includes(query));
            });

            $('#modalItemsCount').text(filtered.length);
            renderModalTable(filtered);
        });
    </script>
@endsection
