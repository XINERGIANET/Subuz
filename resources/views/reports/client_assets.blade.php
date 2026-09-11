@extends('template.app')

@section('title', 'Reporte de Clientes y Activos')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Estilos Select2 */
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #dadcde;
        border-radius: 4px;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 10px;
        color: #1e293b;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 8px;
    }
    .select2-container--open {
        z-index: 99999 !important;
    }
    .select2-dropdown {
        border-color: #90b5e2;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        z-index: 99999 !important;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #90b5e2;
        border-radius: 4px;
        padding: 8px 12px;
        outline: none;
    }

    /* Cabecera Corporativa Azul Estándar del Sistema */
    .table-corporate-header th {
        background-color: var(--brand-color, #244BB3) !important;
        color: #FFFFFF !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: 0.74rem !important;
        letter-spacing: 0.04em !important;
        vertical-align: middle !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
        padding-top: 11px !important;
        padding-bottom: 11px !important;
    }

    .table-bordered {
        border: 1px solid #e2e8f0 !important;
    }
    .table-bordered th, .table-bordered td {
        border: 1px solid #e2e8f0 !important;
    }
    .table-hover tbody tr:hover {
        background-color: #f8fafc !important;
    }
    .btn-edit-corporate {
        color: #0284c7 !important;
        border-color: #bae6fd !important;
        background-color: #f0f9ff !important;
    }
    .btn-edit-corporate:hover {
        background-color: #0284c7 !important;
        color: #ffffff !important;
    }
    .btn-delete-corporate {
        color: #e11d48 !important;
        border-color: #fecdd3 !important;
        background-color: #fff1f2 !important;
    }
    .btn-delete-corporate:hover {
        background-color: #e11d48 !important;
        color: #ffffff !important;
    }

    /* Columna de Acciones Fija (Sticky) */
    .sticky-actions-col {
        position: sticky !important;
        right: 0 !important;
        background-color: #ffffff !important;
        z-index: 5 !important;
        box-shadow: -3px 0 6px rgba(0, 0, 0, 0.06) !important;
    }
    thead .sticky-actions-col {
        background-color: var(--brand-color, #244BB3) !important;
        z-index: 6 !important;
    }
    .table-hover tbody tr:hover .sticky-actions-col {
        background-color: #f1f5f9 !important;
    }
</style>
@endsection

@section('content')
<div class="container-xl">
    <!-- Breadcrumb -->
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reportes</a></li>
            <li class="breadcrumb-item active">Clientes y Activos</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title text-primary fw-bold">
                    <i class="ti ti-users me-2"></i> Reporte de Clientes y Activos en Custodia
                </h2>
                <div class="text-muted mt-1">
                    Verificación de cartera de clientes, conteo de equipos/bidones prestados y depuración de registros
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('asistente'))
                    <button class="btn btn-primary" onclick="openClientMovementModal()">
                        <i class="ti ti-arrows-left-right me-1"></i> Registrar Movimiento
                    </button>
                    <button class="btn btn-outline-secondary" onclick="openClientInitialBalanceModal()">
                        <i class="ti ti-adjustments me-1"></i> Ajustar Saldo Inicial
                    </button>
                    @endif
                    <div class="dropdown">
                        <button class="btn btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-printer me-1"></i> Exportar PDF
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li>
                                <a class="dropdown-item" href="{{ route('inventories.client_assets.summary_pdf', request()->query()) }}" target="_blank">
                                    <i class="ti ti-file-text me-2 text-danger"></i> Reporte Resumen General (PDF)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('inventories.client_assets.detailed_pdf', request()->query()) }}" target="_blank">
                                    <i class="ti ti-file-description me-2 text-primary"></i> Reporte Detallado de Movimientos (PDF)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicator Cards -->
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-blue text-white avatar">
                                <i class="ti ti-users icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium fs-3 fw-bold">
                                {{ $clientRows->count() }}
                            </div>
                            <div class="text-muted small">
                                Clientes con Activos Registrados
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-teal text-white avatar">
                                <i class="ti ti-package icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium fs-3 fw-bold text-teal">
                                {{ number_format($clientsTotals['Bidones'] ?? 0, 0) }}
                            </div>
                            <div class="text-muted small">
                                Bidones en Custodia de Clientes
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-dark text-white avatar">
                                <i class="ti ti-devices icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium fs-3 fw-bold text-dark">
                                {{ number_format($grandTotal, 0) }}
                            </div>
                            <div class="text-muted small">
                                Total de Activos y Equipos en Clientes
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-3 bg-light">
        <div class="card-body py-2 px-3">
            <form action="{{ route('reports.client_assets') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3 col-sm-6">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Filtrar por nombre, RUC o dirección..." onkeyup="filterTable()">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <select class="form-select select2-filter" name="client_id" id="clientFilter" style="width: 100%;" onchange="this.form.submit()">
                        <option value="">🏢 Todos los Clientes</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ $clientId == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} {{ $c->document ? "({$c->document})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <select class="form-select form-select-sm" name="asset_type" onchange="this.form.submit()">
                        <option value="">Todos los Activos</option>
                        @foreach($clientAssetTypes as $at)
                            <option value="{{ $at }}" {{ $assetFilter == $at ? 'selected' : '' }}>{{ $at }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <input type="date" class="form-control form-control-sm" name="start_date" value="{{ $startDate }}" placeholder="Fecha Desde">
                </div>
                <div class="col-md-2 col-12 d-flex align-items-center gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ti ti-filter me-1"></i> Filtrar
                    </button>
                    @if($startDate || $endDate || $clientId || $assetFilter || $hideZeroBalances)
                        <a href="{{ route('reports.client_assets') }}" class="btn btn-sm btn-outline-secondary" title="Restablecer">
                            <i class="ti ti-x"></i> Limpiar
                        </a>
                    @endif
                </div>
                <div class="col-12 mt-1">
                    <label class="form-check form-check-inline form-switch mb-0 cursor-pointer">
                        <input class="form-check-input" type="checkbox" name="hide_zero_balances" value="1" {{ !empty($hideZeroBalances) ? 'checked' : '' }} onchange="this.form.submit()">
                        <span class="form-check-label small text-muted fw-semibold">
                            <i class="ti ti-eye-off me-1 text-primary"></i> Ocultar clientes con saldo 0 (Solo clientes con activos activos)
                        </span>
                    </label>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-bordered table-vcenter table-hover card-table mb-0" id="tableClientsReport">
                <thead class="table-corporate-header">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>CLIENTE / DATOS COMERCIALES</th>
                        <th>DIRECCIÓN Y CONTACTO</th>
                        <th class="text-center" style="width: 110px;">EXHIBIDORES</th>
                        <th class="text-center" style="width: 110px;">CONGELADORES</th>
                        <th class="text-center" style="width: 110px;">MOSTRADORES</th>
                        <th class="text-center" style="width: 100px;">COOLER</th>
                        <th class="text-center" style="width: 110px;">BIDONES</th>
                        <th class="text-center" style="width: 110px;">TOTAL</th>
                        <th class="text-end sticky-actions-col" style="width: 250px;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @php $idx = 1; @endphp
                    @forelse($clientRows as $row)
                    <tr class="client-row" data-client-id="{{ $row->client_id }}">
                        <td class="text-center align-middle text-muted fw-bold">
                            {{ $idx++ }}
                        </td>
                        <td class="align-middle">
                            <div class="fw-bold text-dark fs-5">
                                {{ $row->client_name }}
                            </div>
                            <div class="text-muted small">
                                @if($row->client_document)
                                    <span class="badge bg-light text-secondary border me-1">
                                        <i class="ti ti-id me-1"></i>{{ $row->client_document }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="align-middle small">
                            @if($row->client_address)
                                <div><i class="ti ti-map-pin me-1 text-danger"></i>{{ Str::limit($row->client_address, 40) }}</div>
                            @endif
                            @if($row->client_phone)
                                <div class="text-muted mt-1"><i class="ti ti-phone me-1 text-success"></i>{{ $row->client_phone }}</div>
                            @endif
                            @if(!$row->client_address && !$row->client_phone)
                                <span class="text-muted fst-italic">Sin datos de contacto</span>
                            @endif
                        </td>
                        @foreach($clientAssetTypes as $asset)
                            @php
                                $aData = $row->assets[$asset] ?? ['saldo_final' => 0, 'ingresos' => 0, 'salidas' => 0];
                                $sFinal = $aData['saldo_final'];
                            @endphp
                            <td class="text-center align-middle" style="cursor: pointer;" 
                                title="Click para editar los activos de {{ $row->client_name }}"
                                onclick="openEditClientAssetsModal({{ $row->client_id }}, '{{ addslashes($row->client_name) }}', '{{ $row->client_document }}')">
                                @if($sFinal != 0)
                                    <span class="fw-bold fs-5 {{ $sFinal < 0 ? 'text-danger' : 'text-dark' }}">
                                        {{ number_format($sFinal, 0) }}
                                    </span>
                                    @if($aData['ingresos'] > 0 || $aData['salidas'] > 0)
                                        <div class="d-flex justify-content-center gap-1 mt-1" style="font-size: 0.70rem;">
                                            @if($aData['ingresos'] > 0)<span class="badge bg-success-lt px-1 py-0" title="Entregas">+{{ number_format($aData['ingresos'], 0) }}</span>@endif
                                            @if($aData['salidas'] > 0)<span class="badge bg-danger-lt px-1 py-0" title="Devoluciones">-{{ number_format($aData['salidas'], 0) }}</span>@endif
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted fw-light">-</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center align-middle">
                            @if($row->total_assets != 0)
                                <span class="badge bg-light text-dark border fw-bold fs-6 px-3 py-1">
                                    {{ number_format($row->total_assets, 0) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end align-middle sticky-actions-col">
                            <div class="btn-list justify-content-end flex-nowrap">
                                <button class="btn btn-sm btn-outline-primary px-2" 
                                        title="Ver Kardex / Historial de Activos y Eliminar Movimientos" 
                                        onclick="showClientHistory({{ $row->client_id }}, '{{ addslashes($row->client_name) }}', '{{ $row->client_document }}')">
                                    <i class="ti ti-history me-1"></i> Kardex
                                </button>
                                @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('asistente'))
                                <button class="btn btn-sm btn-outline-warning text-dark px-2 fw-semibold" 
                                        title="Editar los activos que presenta el cliente" 
                                        onclick="openEditClientAssetsModal({{ $row->client_id }}, '{{ addslashes($row->client_name) }}', '{{ $row->client_document }}')">
                                    <i class="ti ti-edit me-1 text-dark"></i> Editar Activos
                                </button>
                                <button class="btn btn-sm btn-outline-success px-2" 
                                        title="Registrar Entrega o Devolución de Activo (+/-)" 
                                        onclick="openClientMovementModal({{ $row->client_id }})">
                                    <i class="ti ti-plus"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger px-2" 
                                        title="Depurar Activos: Eliminar todos los activos del cliente y dejar saldo en 0" 
                                        onclick="resetClientAssetsRecord({{ $row->client_id }}, '{{ addslashes($row->client_name) }}')">
                                    <i class="ti ti-trash me-1"></i> Depurar
                                </button>
                                @endif
                                <a href="{{ route('inventories.client_assets.detailed_pdf', ['client_id' => $row->client_id] + request()->query()) }}" 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-secondary px-2" 
                                   title="Exportar Ficha PDF">
                                    <i class="ti ti-file-text"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <div class="empty">
                                <div class="empty-icon"><i class="ti ti-users-minus fs-1 text-muted"></i></div>
                                <p class="empty-title fw-bold">No se encontraron clientes para los filtros aplicados</p>
                                <p class="empty-subtitle text-muted">Verifique los filtros de búsqueda o fecha.</p>
                                <a href="{{ route('reports.client_assets') }}" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="ti ti-refresh me-1"></i> Restablecer Filtros
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($clientRows->count() > 0)
        <div class="card-footer d-flex justify-content-between align-items-center py-2 bg-light">
            <span class="text-muted small">Mostrando {{ $clientRows->count() }} clientes registrados</span>
            <div class="fw-bold text-dark small">
                Total Activos en Custodia: <span class="badge bg-primary text-white fs-6 ms-1">{{ number_format($grandTotal, 0) }}</span>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal: Kardex de Cliente -->
<div class="modal fade" id="modalHistory" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-primary mb-0">
                        <i class="ti ti-history me-1"></i> Historial de Activos: <span id="historyTitle" class="text-dark"></span>
                    </h5>
                    <div class="text-muted small" id="historySubtitle"></div>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <a href="#" id="btnExportPdf" target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="ti ti-file-text me-1"></i> Exportar Ficha PDF
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="min-height: 250px;">
                    <table class="table table-bordered table-vcenter table-striped table-hover mb-0">
                        <thead class="table-corporate-header">
                            <tr>
                                <th>Fecha</th>
                                <th>Activo</th>
                                <th>Tipo Movimiento</th>
                                <th class="text-center">Cantidad</th>
                                <th>Despachador</th>
                                <th>Observaciones</th>
                                <th>Registrado Por</th>
                                <th class="text-end" style="width: 80px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="historyTbody">
                            <tr><td colspan="8" class="text-center py-4">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registrar Movimiento de Activo (Entrega / Devolución) -->
<div class="modal fade" id="modalClientMovement" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow-lg border-0" id="formClientMovement">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalClientMovementTitle">
                    <i class="ti ti-arrows-left-right me-1"></i> Registrar Movimiento de Activo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label required"><i class="ti ti-user me-1"></i>Cliente</label>
                        <select class="form-select select2-modal" name="client_id" id="client_mov_client_id" required style="width: 100%;">
                            <option value="">-- Seleccionar Cliente --</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} {{ $c->document ? "({$c->document})" : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label required"><i class="ti ti-calendar me-1"></i>Fecha del Movimiento</label>
                        <input type="date" class="form-control" name="date" id="client_mov_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label required"><i class="ti ti-arrows-sort me-1"></i>Tipo de Operación</label>
                        <select class="form-select" name="movement_type" id="client_mov_type" required>
                            <option value="delivery" selected>📤 Salida de Planta y Entrega a Cliente (+ Cliente, - Planta)</option>
                            <option value="return">📥 Devolución de Cliente e Ingreso a Planta (- Cliente, + Planta)</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label required"><i class="ti ti-package me-1"></i>Tipo de Activo</label>
                        <select class="form-select" name="asset_type" id="client_mov_asset_type" required>
                            @foreach($clientAssetTypes as $at)
                                <option value="{{ $at }}">{{ $at }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label required"><i class="ti ti-hash me-1"></i>Cantidad</label>
                        <input type="number" step="1" min="1" class="form-control fw-bold" name="quantity" id="client_mov_quantity" placeholder="Ej: 2" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><i class="ti ti-truck me-1"></i>Despachador Responsable (Opcional)</label>
                        <select class="form-select" name="dispatcher_id" id="client_mov_dispatcher_id">
                            <option value="">-- Sin despachador específico --</option>
                            @if(isset($dispatchers))
                                @foreach($dispatchers as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><i class="ti ti-notes me-1"></i>Observaciones / N° Guía / Referencia</label>
                        <input type="text" class="form-control" name="notes" id="client_mov_notes" placeholder="Ej: Guía N° 4589, entrega en préstamo, cambio por avería">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSaveClientMov">
                    <i class="ti ti-check me-1"></i> Guardar Movimiento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Saldo Inicial / Ajuste de Activo por Cliente -->
<div class="modal fade" id="modalClientInitialBalance" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow-lg border-0" id="formClientInitialBalance">
            @csrf
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">
                    <i class="ti ti-adjustments me-1"></i> Configurar Saldo Inicial / Ajuste de Activos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label required"><i class="ti ti-user me-1"></i>Cliente</label>
                        <select class="form-select select2-modal" name="client_id" id="init_client_select" required style="width: 100%;">
                            <option value="">-- Seleccionar Cliente --</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} {{ $c->document ? "({$c->document})" : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label required"><i class="ti ti-package me-1"></i>Tipo de Activo</label>
                        <select class="form-select" name="asset_type" id="init_asset_type_select" required>
                            @foreach($clientAssetTypes as $at)
                                <option value="{{ $at }}">{{ $at }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label required"><i class="ti ti-hash me-1"></i>Saldo Inicial</label>
                        <input type="number" step="1" min="0" class="form-control fw-bold" name="quantity" id="init_client_quantity" placeholder="0" required>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label"><i class="ti ti-calendar me-1"></i>Fecha del Saldo Inicial</label>
                        <input type="date" class="form-control" name="date" id="init_client_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label"><i class="ti ti-notes me-1"></i>Observación</label>
                        <input type="text" class="form-control" name="notes" id="init_client_notes" placeholder="Ej: Inventario físico inicial / ajuste auditado">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-dark" id="btnSaveInitBalance">
                    <i class="ti ti-check me-1"></i> Guardar Saldo Inicial
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Activos Prestados del Cliente -->
<div class="modal fade" id="modalEditClientAssets" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content shadow-lg border-0" id="formEditClientAssets">
            @csrf
            <input type="hidden" name="client_id" id="edit_assets_client_id">
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="ti ti-edit me-1"></i> Editar Activos del Cliente: <span id="editAssetsClientName" class="text-white"></span>
                    </h5>
                    <div class="small opacity-75" id="editAssetsClientDoc"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info py-2 d-flex align-items-center mb-3">
                    <i class="ti ti-info-circle fs-2 me-2"></i>
                    <div>
                        <strong>Modificación y Auditoría de Activos en Custodia</strong>
                        <div class="small text-muted">A continuación se muestran los activos y bidones que el cliente tiene registrados actualmente. Modifique las cantidades que presenta para actualizar su inventario.</div>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-vcenter table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 45%;">Activo / Equipo</th>
                                <th class="text-center" style="width: 25%;">Saldo Actual</th>
                                <th class="text-center" style="width: 30%;">Nuevo Saldo (Modificar)</th>
                            </tr>
                        </thead>
                        <tbody id="editAssetsTbody">
                            @foreach($clientAssetTypes as $asset)
                            <tr>
                                <td class="align-middle">
                                    <div class="fw-bold text-dark">
                                        @if($asset == 'Bidones') <i class="ti ti-cup text-primary me-1"></i>
                                        @elseif($asset == 'Congeladores') <i class="ti ti-snowflake text-info me-1"></i>
                                        @elseif($asset == 'Exhibidores') <i class="ti ti-layout-grid text-warning me-1"></i>
                                        @elseif($asset == 'Mostradores') <i class="ti ti-table text-secondary me-1"></i>
                                        @elseif($asset == 'Cooler') <i class="ti ti-box text-success me-1"></i>
                                        @endif
                                        {{ $asset }}
                                    </div>
                                    <small class="text-muted">Equipos / envases en custodia</small>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-light text-dark border fs-6 px-2 py-1" id="current_val_{{ Str::slug($asset, '_') }}">
                                        0
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="1" min="0" 
                                               name="balances[{{ $asset }}]" 
                                               id="input_val_{{ Str::slug($asset, '_') }}" 
                                               class="form-control form-control-sm text-center fw-bold fs-5" 
                                               value="0" required>
                                        <button type="button" class="btn btn-outline-danger" 
                                                title="Dejar en 0" 
                                                onclick="document.getElementById('input_val_{{ Str::slug($asset, '_') }}').value = 0;">
                                            <i class="ti ti-eraser"></i> 0
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="ti ti-calendar me-1"></i>Fecha de Auditoría / Modificación</label>
                        <input type="date" class="form-control form-control-sm" name="date" id="edit_assets_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="ti ti-notes me-1"></i>Motivo / Observación</label>
                        <input type="text" class="form-control form-control-sm" name="notes" id="edit_assets_notes" placeholder="Ej: Conteo físico / regularización de activos">
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light d-flex justify-content-between flex-wrap gap-2">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnPurgeAllFromEditModal" title="Restablecer todos los saldos de equipos y bidones a 0">
                        <i class="ti ti-eraser me-1"></i> Depurar Activos a 0
                    </button>
                    @if(auth()->user()->hasRole('admin'))
                    <button type="button" class="btn btn-danger btn-sm" id="btnDeleteClientFromEditModal" title="Eliminar cliente por completo del sistema">
                        <i class="ti ti-trash me-1"></i> Eliminar Cliente (BD)
                    </button>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btnSaveEditAssets">
                        <i class="ti ti-device-floppy me-1"></i> Guardar Cambios de Activos
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#clientFilter').select2({
            placeholder: "🏢 Todos los Clientes",
            allowClear: true,
            width: '100%'
        });
    });

    // Filtro instantáneo de texto
    function filterTable() {
        let val = document.getElementById('searchInput').value.toLowerCase().trim();
        let rows = document.querySelectorAll('#tableClientsReport tbody tr.client-row');

        rows.forEach(r => {
            let text = r.innerText.toLowerCase();
            r.style.display = text.includes(val) ? '' : 'none';
        });
    }

    let currentHistoryClientId = null;
    let currentHistoryClientName = '';
    let currentHistoryClientDoc = '';

    // Ver Kardex
    function showClientHistory(clientId, clientName, clientDoc) {
        currentHistoryClientId = clientId;
        currentHistoryClientName = clientName;
        currentHistoryClientDoc = clientDoc || '';

        document.getElementById('historyTitle').innerText = clientName;
        document.getElementById('historySubtitle').innerText = clientDoc ? 'Documento: ' + clientDoc : 'Historial de activos en préstamo';

        let pdfUrl = new URL("{{ route('inventories.client_assets.detailed_pdf') }}");
        pdfUrl.searchParams.set('client_id', clientId);
        document.getElementById('btnExportPdf').href = pdfUrl.toString();

        let tbody = document.getElementById('historyTbody');
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4"><span class="spinner-border text-primary me-2"></span> Cargando...</td></tr>`;

        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHistory'));
        modal.show();

        fetch(`{{ url('inventories/client-assets/history') }}/${clientId}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.status && data.movements) {
                if (data.movements.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">No hay movimientos registrados para este cliente.</td></tr>`;
                    return;
                }
                let html = '';
                data.movements.forEach(m => {
                    let badgeClass = 'bg-secondary-lt text-secondary';
                    if (m.movement_type === 'initial_balance') badgeClass = 'bg-info-lt text-info fw-bold';
                    else if (m.movement_type === 'income') badgeClass = 'bg-success-lt text-success fw-bold';
                    else if (m.movement_type === 'outcome') badgeClass = 'bg-danger-lt text-danger fw-bold';

                    let delBtn = '';
                    if (m.can_delete) {
                        delBtn = `<button class="btn btn-sm btn-icon btn-outline-danger" title="Eliminar este movimiento" onclick="deleteClientMovement(${m.id})"><i class="ti ti-trash"></i></button>`;
                    }

                    html += `
                        <tr>
                            <td class="text-nowrap fw-bold">${m.date}</td>
                            <td><span class="badge bg-blue-lt">${m.asset_name}</span></td>
                            <td><span class="badge ${badgeClass}">${m.type_label}</span></td>
                            <td class="text-center fw-bold fs-6">${m.quantity}</td>
                            <td class="small text-muted">${m.dispatcher_name || '-'}</td>
                            <td class="small">${m.notes || '-'}</td>
                            <td class="small text-muted">${m.user_name || 'Sistema'}</td>
                            <td class="text-end">${delBtn}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Error al cargar historial.</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Error de conexión.</td></tr>`;
        });
    }

    // Eliminar Movimiento de Activo
    function deleteClientMovement(movementId) {
        const executeDelete = () => {
            fetch(`{{ url('inventories/client-assets/movement') }}/${movementId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    if (typeof ToastMessage !== 'undefined') {
                        ToastMessage.fire({ text: 'Movimiento de activo eliminado correctamente' });
                    }
                    if (currentHistoryClientId) {
                        showClientHistory(currentHistoryClientId, currentHistoryClientName, currentHistoryClientDoc);
                    } else {
                        location.reload();
                    }
                } else {
                    if (typeof ToastError !== 'undefined') ToastError.fire({ text: data.error || 'No se pudo eliminar el movimiento' });
                    else alert(data.error || 'No se pudo eliminar el movimiento');
                }
            })
            .catch(err => {
                if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error de conexión' });
                else alert('Error de conexión');
            });
        };

        if (typeof ToastConfirm !== 'undefined') {
            ToastConfirm.fire({
                title: '¿Eliminar Movimiento?',
                text: '¿Está seguro de eliminar este registro de movimiento de activo? Esta acción recalculará el saldo.',
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeDelete();
                }
            });
        } else {
            if (confirm('¿Está seguro de eliminar este registro de movimiento de activo? Esta acción recalculará el saldo.')) {
                executeDelete();
            }
        }
    }

    // Abrir Modal de Movimiento de Activo
    function openClientMovementModal(clientId = null, assetType = null) {
        let form = document.getElementById('formClientMovement');
        if (form) form.reset();
        document.getElementById('client_mov_date').value = "{{ date('Y-m-d') }}";

        if (clientId) {
            let sel = document.getElementById('client_mov_client_id');
            if (sel) {
                sel.value = clientId;
                if ($(sel).data('select2')) $(sel).val(clientId).trigger('change');
            }
        }

        if (assetType) {
            let aSel = document.getElementById('client_mov_asset_type');
            if (aSel) aSel.value = assetType;
        }

        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalClientMovement'));
        modal.show();
    }

    // Submit Movimiento de Activo
    let formClientMovement = document.getElementById('formClientMovement');
    if (formClientMovement) {
        formClientMovement.addEventListener('submit', function(e) {
            e.preventDefault();
            let btn = document.getElementById('btnSaveClientMov');
            let originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            let formData = new FormData(this);
            fetch("{{ route('inventories.client_assets.movement') }}", {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    let modal = bootstrap.Modal.getInstance(document.getElementById('modalClientMovement'));
                    if (modal) modal.hide();
                    if (typeof ToastMessage !== 'undefined') {
                        ToastMessage.fire({ text: 'Movimiento de activo registrado con éxito' })
                            .then(() => location.reload());
                    } else {
                        location.reload();
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (typeof ToastError !== 'undefined') ToastError.fire({ text: data.error || 'Error al guardar' });
                    else alert(data.error || 'Error al guardar');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error al procesar la solicitud' });
                else alert('Error al procesar la solicitud');
            });
        });
    }

    // Abrir Modal de Saldo Inicial / Ajuste de Activos
    function openClientInitialBalanceModal(clientId = null, assetType = null) {
        let form = document.getElementById('formClientInitialBalance');
        if (form) form.reset();
        document.getElementById('init_client_date').value = "{{ date('Y-m-d') }}";

        if (clientId) {
            let sel = document.getElementById('init_client_select');
            if (sel) {
                sel.value = clientId;
                if ($(sel).data('select2')) $(sel).val(clientId).trigger('change');
            }
        }

        if (assetType) {
            let aSel = document.getElementById('init_asset_type_select');
            if (aSel) aSel.value = assetType;
        }

        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalClientInitialBalance'));
        modal.show();
    }

    // Submit Saldo Inicial de Activo
    let formClientInitialBalance = document.getElementById('formClientInitialBalance');
    if (formClientInitialBalance) {
        formClientInitialBalance.addEventListener('submit', function(e) {
            e.preventDefault();
            let btn = document.getElementById('btnSaveInitBalance');
            let originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            let formData = new FormData(this);
            fetch("{{ route('inventories.client_assets.initial_balance') }}", {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    let modal = bootstrap.Modal.getInstance(document.getElementById('modalClientInitialBalance'));
                    if (modal) modal.hide();
                    if (typeof ToastMessage !== 'undefined') {
                        ToastMessage.fire({ text: 'Saldo de activo configurado correctamente' })
                            .then(() => location.reload());
                    } else {
                        location.reload();
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (typeof ToastError !== 'undefined') ToastError.fire({ text: data.error || 'Error al guardar' });
                    else alert(data.error || 'Error al guardar');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error al procesar la solicitud' });
                else alert('Error al procesar la solicitud');
            });
        });
    }

    let currentEditAssetsClientId = null;
    let currentEditAssetsClientName = '';

    // Abrir Modal Integral de Edición de Activos del Cliente
    function openEditClientAssetsModal(clientId, clientName, clientDoc) {
        currentEditAssetsClientId = clientId;
        currentEditAssetsClientName = clientName;

        document.getElementById('edit_assets_client_id').value = clientId;
        document.getElementById('editAssetsClientName').innerText = clientName;
        document.getElementById('editAssetsClientDoc').innerText = clientDoc ? 'Documento: ' + clientDoc : 'Activos y bidones en custodia';
        document.getElementById('edit_assets_date').value = "{{ date('Y-m-d') }}";
        document.getElementById('edit_assets_notes').value = "Ajuste / corrección de inventario de activos";

        // Resetear visualmente campos
        @foreach($clientAssetTypes as $asset)
            let curBadge_{{ Str::slug($asset, '_') }} = document.getElementById('current_val_{{ Str::slug($asset, '_') }}');
            let inputField_{{ Str::slug($asset, '_') }} = document.getElementById('input_val_{{ Str::slug($asset, '_') }}');
            if (curBadge_{{ Str::slug($asset, '_') }}) curBadge_{{ Str::slug($asset, '_') }}.innerText = '...';
            if (inputField_{{ Str::slug($asset, '_') }}) inputField_{{ Str::slug($asset, '_') }}.value = 0;
        @endforeach

        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditClientAssets'));
        modal.show();

        // Cargar los saldos actuales reales del cliente vía API
        fetch(`{{ url('inventories/client-assets/balances') }}/${clientId}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.status && data.balances) {
                for (let asset in data.balances) {
                    let slug = asset.toLowerCase().replace(/[^a-z0-9]/g, '_');
                    let val = parseFloat(data.balances[asset]) || 0;

                    let curBadge = document.getElementById('current_val_' + slug);
                    let inputField = document.getElementById('input_val_' + slug);

                    if (curBadge) {
                        curBadge.innerText = val;
                        curBadge.className = 'badge ' + (val > 0 ? 'bg-primary-lt fw-bold' : (val < 0 ? 'bg-danger-lt fw-bold' : 'bg-light text-muted border')) + ' fs-6 px-2 py-1';
                    }
                    if (inputField) {
                        inputField.value = val >= 0 ? val : 0;
                    }
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error al cargar los activos del cliente.' });
        });
    }

    // Guardar los cambios de activos del cliente
    let formEditClientAssets = document.getElementById('formEditClientAssets');
    if (formEditClientAssets) {
        formEditClientAssets.addEventListener('submit', function(e) {
            e.preventDefault();
            let btn = document.getElementById('btnSaveEditAssets');
            let originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            let clientId = document.getElementById('edit_assets_client_id').value;
            let formData = new FormData(this);

            fetch(`{{ url('inventories/client-assets/update-balances') }}/${clientId}`, {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    let modal = bootstrap.Modal.getInstance(document.getElementById('modalEditClientAssets'));
                    if (modal) modal.hide();
                    if (typeof ToastMessage !== 'undefined') {
                        ToastMessage.fire({ text: 'Activos del cliente actualizados exitosamente' })
                            .then(() => location.reload());
                    } else {
                        location.reload();
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (typeof ToastError !== 'undefined') ToastError.fire({ text: data.error || 'Error al guardar activos' });
                    else alert(data.error || 'Error al guardar activos');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error de conexión al guardar activos.' });
                else alert('Error de conexión al guardar activos.');
            });
        });
    }

    // Botón Depurar Todos desde el modal de edición
    let btnPurgeAllFromModal = document.getElementById('btnPurgeAllFromEditModal');
    if (btnPurgeAllFromModal) {
        btnPurgeAllFromModal.addEventListener('click', function() {
            if (!currentEditAssetsClientId) return;
            resetClientAssetsRecord(currentEditAssetsClientId, currentEditAssetsClientName);
        });
    }

    // Botón Eliminar Cliente de BD desde el modal de edición
    let btnDeleteClientFromModal = document.getElementById('btnDeleteClientFromEditModal');
    if (btnDeleteClientFromModal) {
        btnDeleteClientFromModal.addEventListener('click', function() {
            if (!currentEditAssetsClientId) return;
            let clientId = currentEditAssetsClientId;
            let clientName = currentEditAssetsClientName;

            const executeClientDelete = () => {
                fetch(`{{ url('clients') }}/${clientId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status) {
                        let modal = bootstrap.Modal.getInstance(document.getElementById('modalEditClientAssets'));
                        if (modal) modal.hide();
                        if (typeof ToastMessage !== 'undefined') {
                            ToastMessage.fire({ text: `Cliente "${clientName}" eliminado exitosamente de la base de datos.` })
                                .then(() => location.href = "{{ route('reports.client_assets') }}");
                        } else {
                            location.href = "{{ route('reports.client_assets') }}";
                        }
                    } else {
                        if (typeof ToastError !== 'undefined') ToastError.fire({ text: data.error || 'No se pudo eliminar el cliente (puede tener ventas asociadas).' });
                        else alert(data.error || 'No se pudo eliminar el cliente.');
                    }
                })
                .catch(err => {
                    if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error de comunicación al eliminar el cliente.' });
                    else alert('Error de comunicación al eliminar el cliente.');
                });
            };

            if (typeof ToastConfirm !== 'undefined') {
                ToastConfirm.fire({
                    title: '¿Eliminar Cliente de la BD?',
                    text: `¿Está seguro de eliminar definitivamente a "${clientName}" del sistema? Esta acción es irreversible.`,
                    icon: 'warning'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeClientDelete();
                    }
                });
            } else {
                if (confirm(`¿Está seguro de eliminar definitivamente a "${clientName}" de la base de datos?`)) {
                    executeClientDelete();
                }
            }
        });
    }

    // Depurar TODOS los Activos de un Cliente (Resetear todo a 0 sin eliminar al cliente)
    function resetClientAssetsRecord(clientId, clientName) {
        const executeReset = () => {
            fetch(`{{ url('inventories/client-assets/reset-all') }}/${clientId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    let modal = bootstrap.Modal.getInstance(document.getElementById('modalEditClientAssets'));
                    if (modal) modal.hide();
                    if (typeof ToastMessage !== 'undefined') {
                        ToastMessage.fire({ text: 'Activos del cliente depurados a 0 correctamente.' })
                            .then(() => location.reload());
                    } else {
                        location.reload();
                    }
                } else {
                    if (typeof ToastError !== 'undefined') ToastError.fire({ text: data.error || 'No se pudieron depurar los activos.' });
                    else alert(data.error || 'No se pudieron depurar los activos.');
                }
            })
            .catch(err => {
                if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error de conexión al depurar activos.' });
                else alert('Error de conexión al depurar activos.');
            });
        };

        if (typeof ToastConfirm !== 'undefined') {
            ToastConfirm.fire({
                title: '¿Depurar Activos del Cliente?',
                text: `Esta acción restablecerá a 0 todos los activos y bidones en custodia de "${clientName}". El cliente se conservará en el sistema.`,
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeReset();
                }
            });
        } else {
            if (confirm(`¿Está seguro de depurar todos los activos de "${clientName}"?\n\nTodos sus saldos de equipos y bidones volverán a 0. El cliente NO será eliminado.`)) {
                executeReset();
            }
        }
    }
</script>
@endsection
