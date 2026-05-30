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
            <div class="zoom-controls d-flex align-items-center bg-white border rounded px-2 py-1 shadow-sm">
                <i class="ti ti-zoom-out text-muted" style="cursor: pointer;" id="zoom_out" title="Reducir Zoom"></i>
                <input type="range" class="form-range mx-2" id="zoom_slider" min="0.5" max="1.5" step="0.05" value="1"
                    style="width: 100px;">
                <i class="ti ti-zoom-in text-muted" style="cursor: pointer;" id="zoom_in" title="Aumentar Zoom"></i>
                <span class="ms-2 fw-bold small text-muted" id="zoom_val" style="min-width: 45px;">100%</span>
            </div>
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
                        <div class="filter-btn-red">Seleccione Año</div>
                        <select class="filter-select" id="filter_year">
                            @for($i = 2023; $i <= 2030; $i++)
                                <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <div class="filter-btn-red mt-1">Seleccione Mes</div>
                        <select class="filter-select" id="filter_month">
                            <option value="">Todo el Año</option>
                            @php $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']; @endphp
                            @foreach($months as $index => $name)
                                <option value="{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}" {{ ($index + 1) == date('m') ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
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
                        <div class="card card-full border-danger" style="border-width: 1px; border-radius: 10px;">
                            <div class="card-body text-center p-2 d-flex flex-column justify-content-center">
                                <div class="metric-value text-success mb-0" id="balance_total">S/0.00</div>
                                <div class="metric-title metric-title-red mt-1">Balance Actual</div>
                            </div>
                        </div>
                        <div class="card card-full border-danger" style="border-width: 1px; border-radius: 10px;">
                            <div class="card-body text-center p-2 d-flex flex-column justify-content-center">
                                <div class="metric-value text-danger mb-0" id="gastos_totales">S/0.00</div>
                                <div class="metric-title metric-title-red mt-1">Gastos Totales</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sparkline Chart -->
                    <div class="card card-full" style="flex: 1; min-width: 0; border-radius: 10px;">
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
                    <div class="summary-item" style="background-color: #d4a373;">
                        <span class="metric-title text-white">Ventas</span>
                        <span class="metric-value" id="summary_ventas" style="font-size: 1.1rem;">S/0.00</span>
                    </div>
                    <div class="summary-item" style="background-color: #28a745;">
                        <span class="metric-title text-white">Ingresos Caja</span>
                        <span class="metric-value" id="summary_caja" style="font-size: 1.1rem;">S/0.00</span>
                    </div>
                    <div class="summary-item" style="background-color: #2e8b57;">
                        <span class="metric-title text-white">Balance Total</span>
                        <span class="metric-value" id="summary_balance" style="font-size: 1.1rem;">S/0.00</span>
                    </div>
                    <div class="summary-item" style="background-color: #3cb371;">
                        <span class="metric-title text-white">Rentabilidad</span>
                        <span class="metric-value" id="summary_rentabilidad" style="font-size: 1.1rem;">S/0.00</span>
                    </div>
                    <div class="summary-item" style="background-color: #d32f2f;">
                        <span class="metric-title text-white">Gastos</span>
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
                        <div class="kpi-card">
                            <div class="metric-value text-dark" id="kpi_1">S/0.00</div>
                            <div class="metric-title metric-title-red mt-1">Pendiente / Crédito</div>
                        </div>
                        <div class="kpi-card">
                            <div class="metric-value text-dark" id="kpi_2">S/0.00</div>
                            <div class="metric-title metric-title-red mt-1">Efectivo</div>
                        </div>
                        <div class="kpi-card">
                            <div class="metric-value text-dark" id="kpi_3">S/0.00</div>
                            <div class="metric-title metric-title-red mt-1">Transferencias</div>
                        </div>
                        <div class="kpi-card">
                            <div class="metric-value text-dark" id="kpi_4">S/0.00</div>
                            <div class="metric-title metric-title-red mt-1">Yape / Plin</div>
                        </div>
                        <div class="kpi-card">
                            <div class="metric-value text-dark" id="kpi_5">0</div>
                            <div class="metric-title metric-title-red mt-1">Ventas Hoy</div>
                        </div>
                        <div class="kpi-card">
                            <div class="metric-value text-dark" id="kpi_6">0</div>
                            <div class="metric-title metric-title-red mt-1">Despachados Hoy</div>
                        </div>
                    </div>

                    <!-- Bar Chart -->
                    <div class="card card-full"
                        style="flex: 1; min-width: 0; border: 1px solid rgba(211, 47, 47, 0.3); border-radius: 10px;">
                        <div class="card-body p-2 d-flex flex-column" style="min-height: 0;">
                            <div class="text-center metric-title metric-title-red mb-1">Ingresos y Egresos por Mes</div>
                            <div class="chart-container flex-grow-1" style="position: relative; width: 100%; height: 100%; min-height: 0; padding: 0 10px 15px 10px;">
                                <canvas id="bar_chart_mensual"></canvas>
                            </div>
                        </div>
                    </div>
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

        $(document).ready(function () {
            initCharts();
            loadDashboardData();

            $('#filter_form').submit(function (e) {
                e.preventDefault();
                loadDashboardData();
            });

            // Zoom Logic
            const $grid = $('#dashboard_grid');
            const $slider = $('#zoom_slider');
            const $val = $('#zoom_val');

            function applyZoom(scale) {
                $slider.val(scale);
                $val.text(Math.round(scale * 100) + '%');
                $grid.css('transform', 'scale(' + scale + ')');

                if (scale > 1) {
                    const newWidth = 100 * scale;
                    $grid.css({
                        'width': newWidth + '%',
                        'height': newWidth + '%',
                        'flex-grow': '0'
                    });
                } else {
                    $grid.css({
                        'width': '100%',
                        'height': '100%',
                        'flex-grow': '1'
                    });
                }
            }

            $slider.on('input', function () {
                applyZoom(parseFloat($(this).val()));
            });

            $('#zoom_in').click(function () {
                let current = parseFloat($slider.val());
                if (current < 1.5) applyZoom(Math.min(1.5, current + 0.05));
            });

            $('#zoom_out').click(function () {
                let current = parseFloat($slider.val());
                if (current > 0.5) applyZoom(Math.max(0.5, current - 0.05));
            });

            // Fullscreen Logic
            const $wrapper = $('#dashboard_wrapper');
            const $btnFull = $('#btn_fullscreen');
            let isFullscreen = false;

            $btnFull.click(function () {
                isFullscreen = !isFullscreen;
                applyZoom(1);

                if (isFullscreen) {
                    $wrapper.addClass('fullscreen');
                    $btnFull.html('<i class="ti ti-minimize me-1"></i> Salir de Pantalla Completa');
                    $('body').css('overflow', 'hidden');
                } else {
                    $wrapper.removeClass('fullscreen');
                    $btnFull.html('<i class="ti ti-maximize me-1"></i> Pantalla Completa');
                    $('body').css('overflow', 'hidden');
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
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    datasets: [{
                        label: 'Ventas',
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
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
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

        function loadDashboardData() {
            const year = $('#filter_year').val();
            const month = $('#filter_month').val();

            let startDate, endDate;
            if (month) {
                startDate = `${year}-${month}-01`;
                const lastDay = new Date(year, month, 0).getDate();
                endDate = `${year}-${month}-${lastDay}`;
            } else {
                startDate = `${year}-01-01`;
                endDate = `${year}-12-31`;
            }

            $.ajax({
                url: '{{ route("dashboard.api") }}',
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate
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

                    // Optional sub-text for gauge
                    let pctIncrease = '';
                    if (month) pctIncrease = `Mes: ${month}`; else pctIncrease = `Año: ${year}`;
                    $('#ventas_gauge_sub').text(pctIncrease);

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
                        $('#status_alert').text('Atención, los gastos superan ingresos.');
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
                    chartBar.data.datasets[0].data = res.totalSales;
                    chartBar.data.datasets[1].data = res.totalExpenses;
                    chartBar.update();

                    chartSparkline.data.datasets[0].data = res.totalSales;
                    chartSparkline.update();

                    // Update KPIs from Payment Methods
                    $('#kpi_1').text('S/' + res.pending); // Pendiente

                    if (res.methods && res.methods.length > 0) {
                        $('#kpi_2').text('S/' + (res.methods.find(m => m.name.toLowerCase().includes('efectivo') || m.name.toLowerCase().includes('caja'))?.total || '0.00'));
                        $('#kpi_3').text('S/' + (res.methods.find(m => m.name.toLowerCase().includes('transferencia'))?.total || '0.00'));
                        $('#kpi_4').text('S/' + (res.methods.find(m => m.name.toLowerCase().includes('yape') || m.name.toLowerCase().includes('plin'))?.total || '0.00'));
                    }

                    // Fetch daily stats for the other 2 KPIs
                    fetchDailyStats(new Date().toISOString().split('T')[0]);
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
    </script>
@endsection