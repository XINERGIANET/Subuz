@extends('template.app')

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

    /* Bordes limpios de cuadrícula estructurada estilo Excel / Contable */
    .table-bordered {
        border: 1px solid #e2e8f0 !important;
    }
    .table-bordered th, .table-bordered td {
        border: 1px solid #e2e8f0 !important;
    }
    .table-hover tbody tr:hover {
        background-color: #f8fafc !important;
    }

    /* Fila de Planta Pinned / Destacada */
    .row-planta {
        background-color: #f8fafc !important;
        border-bottom: 2px solid #cbd5e1 !important;
    }

    /* Filas de Totales limpias */
    .table-totals-row td {
        border-top: 1px solid #e2e8f0 !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }
</style>
@endsection

@section('content')
<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title text-primary fw-bold">
                    <i class="ti ti-boxes me-2"></i> Control de Inventarios y Kardex
                </h2>
                <div class="text-muted mt-1">
                    @if($isDispatcher)
                        Módulo de recepción y devolución de bidones / insumos en ruta
                    @else
                        Control de Saldo Inicial, Ingresos, Salidas y Saldo Final por Insumos, Activos y Productos
                    @endif
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    @if(!$isDispatcher)
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNewSupply">
                        <i class="ti ti-plus me-1"></i> Nuevo Insumo
                    </button>
                    @endif
                    <button class="btn btn-primary" onclick="openMovementModal()">
                        <i class="ti ti-rotate-2 me-1"></i> {{ $isDispatcher ? 'Registrar Devolución' : 'Registrar Movimiento / Devolución' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Filter Bar -->
    <form method="GET" action="{{ route('inventories.index') }}" class="card card-body bg-light mb-3 shadow-sm py-2 border-0">
        <div class="row g-2 align-items-center">
            <div class="col-md-3 col-sm-6">
                <label class="form-label mb-1 text-muted small fw-bold"><i class="ti ti-calendar me-1"></i>Fecha Desde</label>
                <input type="date" class="form-control form-control-sm" name="start_date" id="filter_start_date" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label mb-1 text-muted small fw-bold"><i class="ti ti-calendar me-1"></i>Fecha Hasta</label>
                <input type="date" class="form-control form-control-sm" name="end_date" id="filter_end_date" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-6 col-12 d-flex align-items-end gap-1 mt-md-4">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="ti ti-filter me-1"></i> Filtrar
                </button>
                @if(request('start_date') || request('end_date'))
                    <a href="{{ route('inventories.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-x me-1"></i> Limpiar Filtro
                    </a>
                @endif
                <button type="button" class="btn btn-sm btn-ghost-primary ms-auto" onclick="setQuickDate('today')">Hoy</button>
                <button type="button" class="btn btn-sm btn-ghost-primary" onclick="setQuickDate('month')">Este Mes</button>
            </div>
        </div>
    </form>

    <!-- Indicator Cards -->
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-blue text-white avatar">
                                <i class="ti ti-package icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ $supplies->count() }} Insumos
                            </div>
                            <div class="text-muted small">
                                Tapas, sellos, bolsas, etc.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-teal text-white avatar">
                                <i class="ti ti-snowflake icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ $assetsData->count() }} Categorías
                            </div>
                            <div class="text-muted small">
                                Activos en empresa
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-purple text-white avatar">
                                <i class="ti ti-shopping-cart icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ $products->count() }} Terminados
                            </div>
                            <div class="text-muted small">
                                Hielo en bolsa y agua
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-orange text-white avatar">
                                <i class="ti ti-users icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ number_format($clientGrandTotal ?? 0, 0) }} Activos / Bidones
                            </div>
                            <div class="text-muted small">
                                En clientes y planta
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $activeTab = request('tab', 'supplies');
        if (!in_array($activeTab, ['supplies', 'assets', 'products', 'client-assets'])) {
            if ($supplies->count() > 0) {
                $activeTab = 'supplies';
            } elseif ($assetsData->count() > 0) {
                $activeTab = 'assets';
            } elseif ($products->count() > 0) {
                $activeTab = 'products';
            } else {
                $activeTab = 'client-assets';
            }
        }
    @endphp

    <!-- Tabs Container -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                <li class="nav-item">
                    <a href="#tab-supplies" class="nav-link {{ $activeTab === 'supplies' ? 'active' : '' }}" data-bs-toggle="tab">
                        <i class="ti ti-box me-1"></i> Insumos
                        @if($isDispatcher && $supplies->count() > 0)
                            <span class="badge bg-blue-lt ms-1">{{ $supplies->count() }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-assets" class="nav-link {{ $activeTab === 'assets' ? 'active' : '' }}" data-bs-toggle="tab">
                        <i class="ti ti-devices me-1"></i> Activos (Equipos)
                        @if($isDispatcher && $assetsData->count() > 0)
                            <span class="badge bg-teal-lt ms-1">{{ $assetsData->count() }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-products" class="nav-link {{ $activeTab === 'products' ? 'active' : '' }}" data-bs-toggle="tab">
                        <i class="ti ti-building-store me-1"></i> Productos Terminados
                        @if($isDispatcher && $products->count() > 0)
                            <span class="badge bg-purple-lt ms-1">{{ $products->count() }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-client-assets" class="nav-link {{ $activeTab === 'client-assets' ? 'active' : '' }}" data-bs-toggle="tab">
                        <i class="ti ti-users me-1"></i> Activos por Cliente (y Planta)
                        @if(isset($clientAssetsRows) && $clientAssetsRows->count() > 0)
                            <span class="badge bg-primary text-white ms-1">{{ $clientAssetsRows->count() }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content">
            <!-- TAB 1: INSUMOS -->
            <div class="tab-pane {{ $activeTab === 'supplies' ? 'active show' : '' }}" id="tab-supplies">
                <div class="table-responsive">
                    <table class="table table-bordered table-vcenter table-hover card-table">
                        <thead class="table-corporate-header">
                            <tr>
                                <th>Insumo</th>
                                <th>Unidad</th>
                                @if(!$isDispatcher)
                                <th class="text-center" title="Permitir que los despachadores registren devoluciones/movimientos de este insumo">
                                    <i class="ti ti-truck-delivery me-1"></i> Para Despachador
                                </th>
                                <th class="text-center">Saldo Inicial</th>
                                @endif
                                <th class="text-center">Ingresos (+)</th>
                                <th class="text-center">Salidas (-)</th>
                                <th class="text-center">Saldo Final (=)</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplies as $s)
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="ti ti-point me-1 text-primary"></i> {{ $s->name }}
                                </td>
                                <td><span class="badge bg-secondary-lt">{{ $s->unit }}</span></td>
                                @if(!$isDispatcher)
                                <td class="text-center">
                                    <label class="form-check form-switch form-check-inline m-0">
                                        <input class="form-check-input" type="checkbox" 
                                               onchange="toggleDispatcherPermission('supply', {{ $s->id }}, this.checked)"
                                               {{ $s->allowed_for_dispatchers ? 'checked' : '' }}
                                               title="Habilitar/Deshabilitar para Despachadores">
                                    </label>
                                </td>
                                <td class="text-center fw-bold text-muted">
                                    {{ number_format($s->saldo_inicial, 2) }}
                                    <button class="btn btn-sm btn-icon btn-ghost-secondary ms-1" 
                                            onclick="openInitialBalanceModal('supply', '{{ $s->id }}', '{{ $s->name }}', '{{ $s->saldo_inicial }}')" 
                                            title="Editar Saldo Inicial">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                </td>
                                @endif
                                <td class="text-center text-success fw-bold">
                                    +{{ number_format($s->ingresos, 2) }}
                                </td>
                                <td class="text-center text-danger fw-bold">
                                    -{{ number_format($s->salidas, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary text-white fs-6 px-3 py-2">
                                        {{ number_format($s->saldo_final, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-list justify-content-end">
                                        @if(!$isDispatcher)
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="openMovementModal('supply', '{{ $s->id }}', '{{ $s->name }}', 'income')">
                                            <i class="ti ti-plus me-1"></i> Ingreso
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="openMovementModal('supply', '{{ $s->id }}', '{{ $s->name }}', 'outcome')">
                                            <i class="ti ti-minus me-1"></i> Salida
                                        </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="openMovementModal('supply', '{{ $s->id }}', '{{ $s->name }}', 'return')">
                                            <i class="ti ti-rotate-2 me-1"></i> Devolución
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="showKardexHistory('supply', '{{ $s->id }}', '{{ $s->name }}')">
                                            <i class="ti ti-history me-1"></i> Kardex
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $isDispatcher ? 6 : 8 }}" class="text-center text-muted py-4">
                                    No hay insumos registrados o habilitados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: ACTIVOS (EQUIPOS) -->
            <div class="tab-pane {{ $activeTab === 'assets' ? 'active show' : '' }}" id="tab-assets">
                <div class="table-responsive">
                    <table class="table table-bordered table-vcenter table-hover card-table">
                        <thead class="table-corporate-header">
                            <tr>
                                <th>Categoría de Activo</th>
                                <th>Disponibles / Prestados</th>
                                @if(!$isDispatcher)
                                <th class="text-center" title="Permitir que los despachadores registren devoluciones de esta categoría de activos">
                                    <i class="ti ti-truck-delivery me-1"></i> Para Despachador
                                </th>
                                <th class="text-center">Saldo Inicial</th>
                                @endif
                                <th class="text-center">Ingresos (+)</th>
                                <th class="text-center">Salidas (-)</th>
                                <th class="text-center">Saldo Final (=)</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assetsData as $a)
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="ti ti-snowflake me-1 text-info"></i> {{ $a->category }}
                                </td>
                                <td>
                                    <span class="badge bg-success-lt me-1" title="Disponibles">{{ $a->available_count }} disp.</span>
                                    <span class="badge bg-warning-lt" title="Asignados a clientes">{{ $a->assigned_count }} prest.</span>
                                </td>
                                @if(!$isDispatcher)
                                <td class="text-center">
                                    <label class="form-check form-switch form-check-inline m-0">
                                        <input class="form-check-input" type="checkbox" 
                                               onchange="toggleDispatcherPermission('fixed_asset', '{{ $a->category }}', this.checked)"
                                               {{ $a->allowed_for_dispatchers ? 'checked' : '' }}
                                               title="Habilitar/Deshabilitar para Despachadores">
                                    </label>
                                </td>
                                <td class="text-center fw-bold text-muted">
                                    {{ number_format($a->saldo_inicial, 0) }}
                                    <button class="btn btn-sm btn-icon btn-ghost-secondary ms-1" 
                                            onclick="openInitialBalanceModal('fixed_asset', null, '{{ $a->category }}', '{{ $a->saldo_inicial }}')" 
                                            title="Editar Saldo Inicial">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                </td>
                                @endif
                                <td class="text-center text-success fw-bold">
                                    +{{ number_format($a->ingresos, 0) }}
                                </td>
                                <td class="text-center text-danger fw-bold">
                                    -{{ number_format($a->salidas, 0) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info text-white fs-6 px-3 py-2">
                                        {{ number_format($a->saldo_final, 0) }} Unid.
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-list justify-content-end">
                                        @if(!$isDispatcher)
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="openMovementModal('fixed_asset', null, '{{ $a->category }}', 'income')">
                                            <i class="ti ti-plus me-1"></i> Ingreso
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="openMovementModal('fixed_asset', null, '{{ $a->category }}', 'outcome')">
                                            <i class="ti ti-minus me-1"></i> Salida
                                        </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="openMovementModal('fixed_asset', null, '{{ $a->category }}', 'return')">
                                            <i class="ti ti-rotate-2 me-1"></i> Devolución
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="showKardexHistory('fixed_asset', null, '{{ $a->category }}')">
                                            <i class="ti ti-history me-1"></i> Kardex
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $isDispatcher ? 6 : 7 }}" class="text-center text-muted py-4">No hay activos registrados o habilitados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: PRODUCTOS TERMINADOS -->
            <div class="tab-pane {{ $activeTab === 'products' ? 'active show' : '' }}" id="tab-products">
                <div class="table-responsive">
                    <table class="table table-bordered table-vcenter table-hover card-table">
                        <thead class="table-corporate-header">
                            <tr>
                                <th>Producto</th>
                                <th>Precio Venta</th>
                                @if(!$isDispatcher)
                                <th class="text-center" title="Permitir que los despachadores registren devoluciones/movimientos de este producto">
                                    <i class="ti ti-truck-delivery me-1"></i> Para Despachador
                                </th>
                                <th class="text-center">Saldo Inicial</th>
                                @endif
                                <th class="text-center">Ingresos (+)</th>
                                <th class="text-center">Salidas (-)</th>
                                <th class="text-center">Saldo Final (=)</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $p)
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="ti ti-shopping-bag me-1 text-purple"></i> {{ $p->name }}
                                </td>
                                <td><span class="badge bg-green-lt">S/ {{ number_format($p->price, 2) }}</span></td>
                                @if(!$isDispatcher)
                                <td class="text-center">
                                    <label class="form-check form-switch form-check-inline m-0">
                                        <input class="form-check-input" type="checkbox" 
                                               onchange="toggleDispatcherPermission('product', {{ $p->id }}, this.checked)"
                                               {{ $p->allowed_for_dispatchers ? 'checked' : '' }}
                                               title="Habilitar/Deshabilitar para Despachadores">
                                    </label>
                                </td>
                                <td class="text-center fw-bold text-muted">
                                    {{ number_format($p->saldo_inicial, 2) }}
                                    <button class="btn btn-sm btn-icon btn-ghost-secondary ms-1" 
                                            onclick="openInitialBalanceModal('product', '{{ $p->id }}', '{{ $p->name }}', '{{ $p->saldo_inicial }}')" 
                                            title="Editar Saldo Inicial">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                </td>
                                @endif
                                <td class="text-center text-success fw-bold">
                                    +{{ number_format($p->ingresos, 2) }}
                                </td>
                                <td class="text-center text-danger fw-bold">
                                    -{{ number_format($p->salidas, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-purple text-white fs-6 px-3 py-2">
                                        {{ number_format($p->saldo_final, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-list justify-content-end">
                                        @if(!$isDispatcher)
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="openMovementModal('product', '{{ $p->id }}', '{{ $p->name }}', 'income')">
                                            <i class="ti ti-plus me-1"></i> Ingreso
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="openMovementModal('product', '{{ $p->id }}', '{{ $p->name }}', 'outcome')">
                                            <i class="ti ti-minus me-1"></i> Salida
                                        </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="openMovementModal('product', '{{ $p->id }}', '{{ $p->name }}', 'return')">
                                            <i class="ti ti-rotate-2 me-1"></i> Devolución
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="showKardexHistory('product', '{{ $p->id }}', '{{ $p->name }}')">
                                            <i class="ti ti-history me-1"></i> Kardex
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $isDispatcher ? 6 : 8 }}" class="text-center text-muted py-4">No hay productos registrados o habilitados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: ACTIVOS POR CLIENTE (Y PLANTA) -->
            <div class="tab-pane {{ $activeTab === 'client-assets' ? 'active show' : '' }}" id="tab-client-assets">
                <!-- Resumen de Stock General (Planta vs Clientes) -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-4 col-12">
                        <div class="card card-sm border shadow-none bg-white">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-primary text-white me-3 shadow-sm">
                                        <i class="ti ti-building-warehouse fs-2"></i>
                                    </span>
                                    <div>
                                        <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.03em;">En Almacén (Planta)</div>
                                        <div class="fs-3 fw-bold text-dark">{{ number_format(array_sum($plantaTotals ?? []), 0) }} <span class="fs-6 fw-normal text-muted">activos disp.</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 col-12">
                        <div class="card card-sm border shadow-none bg-white">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-teal text-white me-3 shadow-sm">
                                        <i class="ti ti-users fs-2"></i>
                                    </span>
                                    <div>
                                        <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.03em;">Prestados en Clientes</div>
                                        <div class="fs-3 fw-bold text-dark">{{ number_format(array_sum($clientsTotals ?? []), 0) }} <span class="fs-6 fw-normal text-muted">en custodia</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 col-12">
                        <div class="card card-sm border shadow-none bg-white">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-dark text-white me-3 shadow-sm">
                                        <i class="ti ti-packages fs-2"></i>
                                    </span>
                                    <div>
                                        <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.03em;">Total en la Empresa</div>
                                        <div class="fs-3 fw-bold text-dark">{{ number_format($clientGrandTotal ?? 0, 0) }} <span class="fs-6 fw-normal text-muted">patrimonio total</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra de Filtros y Acciones de Clientes -->
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-3 col-sm-6">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search"></i>
                            </span>
                            <input type="text" class="form-control form-control-sm" id="searchClientAssetsInput" placeholder="Buscar cliente en tabla..." onkeyup="filterClientAssetsTable()">
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <select class="form-select select2-filter" id="clientFilterSelect" style="width: 100%;">
                            <option value=""> Todos los Clientes y Planta</option>
                            @if(isset($plantaClient))
                                <option value="{{ $plantaClient->id }}" {{ request('client_id') == $plantaClient->id ? 'selected' : '' }}>🏭 PLANTA (Sede Principal) - Almacén</option>
                            @endif
                            @foreach($clients as $c)
                                @if(!isset($plantaClient) || $c->id != $plantaClient->id)
                                    <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} {{ $c->document ? "({$c->document})" : '' }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <select class="form-select form-select-sm" id="assetFilterSelect" onchange="applyAssetFilter(this.value)">
                            <option value=""> Todos los Activos</option>
                            @foreach($clientAssetTypes as $at)
                                <option value="{{ $at }}" {{ request('asset_type') == $at ? 'selected' : '' }}>{{ $at }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-12 d-flex justify-content-md-end gap-1 flex-wrap">
                        @if(!$isDispatcher)
                        <button type="button" class="btn btn-sm btn-primary" onclick="openClientMovementModal()">
                            <i class="ti ti-plus me-1"></i> Movimiento
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openClientInitialBalanceModal()">
                            <i class="ti ti-adjustments me-1"></i> Saldo Inicial
                        </button>
                        @endif
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-printer me-1"></i> Reportes PDF
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventories.client_assets.summary_pdf', request()->query()) }}" target="_blank">
                                        <i class="ti ti-file-text me-2 text-danger"></i> Reporte Resumen (PDF)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventories.client_assets.detailed_pdf', request()->query()) }}" target="_blank">
                                        <i class="ti ti-file-description me-2 text-primary"></i> Reporte Detallado (PDF)
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-vcenter table-hover card-table mb-0" id="tableClientAssets">
                        <thead class="table-corporate-header">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>CLIENTE / UBICACIÓN</th>
                                <th class="text-center" style="width: 120px;">EXHIBIDORES</th>
                                <th class="text-center" style="width: 120px;">CONGELADORES</th>
                                <th class="text-center" style="width: 120px;">MOSTRADORES</th>
                                <th class="text-center" style="width: 110px;">COOLER</th>
                                <th class="text-center" style="width: 120px;">BIDONES</th>
                                <th class="text-center" style="width: 120px;">TOTAL ACTIVOS</th>
                                <th class="text-end" style="width: 190px;">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $rowIdx = 1; @endphp
                            @forelse($clientAssetsRows as $row)
                            <tr class="client-asset-row {{ $row->is_planta ? 'row-planta' : '' }}" data-client-id="{{ $row->client_id }}">
                                <td class="text-center align-middle">
                                    @if($row->is_planta)
                                        <span class="badge bg-primary text-white"><i class="ti ti-building-warehouse me-1"></i>PLANTA</span>
                                    @else
                                        <span class="text-muted fw-bold">{{ $rowIdx++ }}</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="fw-bold {{ $row->is_planta ? 'text-primary fs-5' : 'text-dark' }}">
                                        {{ $row->client_name }}
                                    </div>
                                    <div class="text-muted small">
                                        @if($row->is_planta)
                                            <span class="text-muted"><i class="ti ti-building-warehouse me-1 text-primary"></i>Almacén Central - Stock disponible para entrega</span>
                                        @else
                                            @if($row->client_document) <span class="me-2"><i class="ti ti-id me-1"></i>{{ $row->client_document }}</span> @endif
                                            @if($row->client_phone) <span class="me-2"><i class="ti ti-phone me-1"></i>{{ $row->client_phone }}</span> @endif
                                            @if($row->client_address) <span><i class="ti ti-map-pin me-1"></i>{{ Str::limit($row->client_address, 35) }}</span> @endif
                                        @endif
                                    </div>
                                </td>
                                @foreach($clientAssetTypes as $asset)
                                    @php
                                        $aData = $row->assets[$asset] ?? ['saldo_final' => 0, 'ingresos' => 0, 'salidas' => 0];
                                        $sFinal = $aData['saldo_final'];
                                    @endphp
                                    <td class="text-center align-middle">
                                        @if($sFinal != 0)
                                            <span class="fw-bold fs-5 {{ $sFinal < 0 ? 'text-danger' : 'text-dark' }}">
                                                {{ number_format($sFinal, 0) }}
                                            </span>
                                            @if($aData['ingresos'] > 0 || $aData['salidas'] > 0)
                                                <div class="d-flex justify-content-center gap-1 mt-1" style="font-size: 0.70rem;">
                                                    @if($aData['ingresos'] > 0)<span class="badge bg-success-lt px-1 py-0">+{{ number_format($aData['ingresos'], 0) }}</span>@endif
                                                    @if($aData['salidas'] > 0)<span class="badge bg-danger-lt px-1 py-0">-{{ number_format($aData['salidas'], 0) }}</span>@endif
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted fw-light">-</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center align-middle">
                                    @if($row->is_planta)
                                        <span class="badge bg-dark text-white fs-6 px-3 py-1">
                                            {{ number_format($row->total_assets, 0) }}
                                        </span>
                                    @else
                                        @if($row->total_assets != 0)
                                            <span class="badge bg-light text-dark border fw-bold fs-6 px-3 py-1">
                                                {{ number_format($row->total_assets, 0) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end align-middle">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-outline-primary" 
                                                title="Ver Kardex / Historial de Movimientos" 
                                                onclick="showClientAssetHistory({{ $row->client_id }}, '{{ addslashes($row->client_name) }}', '{{ $row->client_document }}')">
                                            <i class="ti ti-history me-1"></i> Kardex
                                        </button>
                                        @if(!$isDispatcher)
                                        <button class="btn btn-outline-success" 
                                                title="Registrar Movimiento" 
                                                onclick="openClientMovementModal({{ $row->client_id }})">
                                            <i class="ti ti-plus"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" 
                                                title="Saldo Inicial" 
                                                onclick="openClientInitialBalanceModal({{ $row->client_id }})">
                                            <i class="ti ti-adjustments"></i>
                                        </button>
                                        @endif
                                        <a href="{{ route('inventories.client_assets.detailed_pdf', ['client_id' => $row->client_id] + request()->query()) }}" 
                                           target="_blank" 
                                           class="btn btn-outline-danger" 
                                           title="Descargar Ficha PDF">
                                            <i class="ti ti-file-text"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <div class="empty">
                                        <div class="empty-icon"><i class="ti ti-filter-off fs-1 text-muted"></i></div>
                                        <p class="empty-title fw-bold">No se encontraron registros para los filtros seleccionados</p>
                                        <p class="empty-subtitle text-muted">
                                            @if(request('asset_type') || request('client_id'))
                                                Actualmente tiene filtros activos. Puede restablecerlos para ver todos los clientes y activos.
                                                <div class="mt-2">
                                                    <a href="{{ url()->current() }}?tab=client-assets" class="btn btn-sm btn-outline-primary">
                                                        <i class="ti ti-refresh me-1"></i> Ver Todos los Clientes y Activos
                                                    </a>
                                                </div>
                                            @else
                                                Utilice el botón "+ Movimiento" o "Saldo Inicial" para comenzar el registro.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(isset($clientAssetsRows) && $clientAssetsRows->count() > 0)
                        <tfoot style="border-top: 2px solid #244BB3;">
                            <!-- 1. Subtotal Stock en Almacén Planta -->
                            <tr class="bg-white fw-bold table-totals-row">
                                <td colspan="2" class="text-end text-uppercase py-2" style="font-size: 0.78rem; letter-spacing: 0.03em; color: #475569;">
                                    <i class="ti ti-building-warehouse me-1 text-primary"></i> Stock Disponible en Almacén (Planta):
                                </td>
                                @foreach($clientAssetTypes as $asset)
                                    @php $pVal = $plantaTotals[$asset] ?? 0; @endphp
                                    <td class="text-center py-2 fs-5 {{ $pVal < 0 ? 'text-danger' : 'text-dark' }}">
                                        {{ $pVal != 0 ? number_format($pVal, 0) : '-' }}
                                    </td>
                                @endforeach
                                <td class="text-center py-2">
                                    <span class="badge bg-secondary text-white fs-6 px-2 py-1">
                                        {{ number_format(array_sum($plantaTotals ?? []), 0) }}
                                    </span>
                                </td>
                                <td></td>
                            </tr>
                            <!-- 2. Subtotal Prestado en Clientes -->
                            <tr class="bg-white fw-bold table-totals-row">
                                <td colspan="2" class="text-end text-uppercase py-2" style="font-size: 0.78rem; letter-spacing: 0.03em; color: #475569;">
                                    <i class="ti ti-users me-1 text-teal"></i> Total Prestados en Clientes:
                                </td>
                                @foreach($clientAssetTypes as $asset)
                                    @php $cVal = $clientsTotals[$asset] ?? 0; @endphp
                                    <td class="text-center py-2 fs-5 {{ $cVal < 0 ? 'text-danger' : 'text-dark' }}">
                                        {{ $cVal != 0 ? number_format($cVal, 0) : '-' }}
                                    </td>
                                @endforeach
                                <td class="text-center py-2">
                                    <span class="badge bg-teal text-white fs-6 px-2 py-1">
                                        {{ number_format(array_sum($clientsTotals ?? []), 0) }}
                                    </span>
                                </td>
                                <td></td>
                            </tr>
                            <!-- 3. Gran Total Empresa -->
                            <tr class="fw-bold" style="background-color: #f1f5f9; border-top: 2px solid #cbd5e1;">
                                <td colspan="2" class="text-end text-uppercase py-3" style="font-size: 0.82rem; letter-spacing: 0.04em; color: #0f172a;">
                                    <i class="ti ti-packages me-1 text-primary"></i> TOTAL GENERAL EN LA EMPRESA (PLANTA + CLIENTES):
                                </td>
                                @foreach($clientAssetTypes as $asset)
                                    @php $tVal = $clientAssetTotals[$asset] ?? 0; @endphp
                                    <td class="text-center py-3 fs-5 text-primary fw-bold">
                                        {{ $tVal != 0 ? number_format($tVal, 0) : '-' }}
                                    </td>
                                @endforeach
                                <td class="text-center py-3">
                                    <span class="badge bg-primary text-white fs-5 px-3 py-1 shadow-sm">
                                        {{ number_format($clientGrandTotal ?? 0, 0) }}
                                    </span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registrar Movimiento de Activo por Cliente (y Planta) -->
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
                        <label class="form-label required"><i class="ti ti-user me-1"></i>Cliente o Almacén de Destino</label>
                        <select class="form-select select2-modal" name="client_id" id="client_mov_client_id" required style="width: 100%;">
                            <option value="">-- Seleccionar Cliente o Planta --</option>
                            @if(isset($plantaClient))
                                <option value="{{ $plantaClient->id }}" class="fw-bold text-primary">🏭 PLANTA (Sede Principal) - Almacén</option>
                            @endif
                            @foreach($clients as $c)
                                @if(!isset($plantaClient) || $c->id != $plantaClient->id)
                                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->document ? "({$c->document})" : '' }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label required"><i class="ti ti-calendar me-1"></i>Fecha del Movimiento</label>
                        <input type="date" class="form-control" name="date" id="client_mov_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label required"><i class="ti ti-arrows-sort me-1"></i>Tipo de Movimiento</label>
                        <select class="form-select" name="movement_type" id="client_mov_type" required>
                            <option value="delivery" selected>📤 Salida de Planta y Entrega a Cliente (+ Cliente, - Planta)</option>
                            <option value="return">📥 Devolución de Cliente e Ingreso a Planta (- Cliente, + Planta)</option>
                        </select>
                        <small class="text-muted d-block mt-1" id="movementTypeHelp">
                            La entrega descuenta del almacén de Planta y aumenta en el cliente.
                        </small>
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
                            @foreach($dispatchers as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
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
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i> Guardar Movimiento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Saldo Inicial de Activo por Cliente -->
<div class="modal fade" id="modalClientInitialBalance" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow-lg border-0" id="formClientInitialBalance">
            @csrf
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">
                    <i class="ti ti-adjustments me-1"></i> Configurar Saldo Inicial por Cliente / Planta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label required"><i class="ti ti-user me-1"></i>Cliente o Planta</label>
                        <select class="form-select select2-modal" name="client_id" id="init_client_select" required style="width: 100%;">
                            <option value="">-- Seleccionar Cliente --</option>
                            @if(isset($plantaClient))
                                <option value="{{ $plantaClient->id }}" class="fw-bold text-primary">🏭 PLANTA (Sede Principal)</option>
                            @endif
                            @foreach($clients as $c)
                                @if(!isset($plantaClient) || $c->id != $plantaClient->id)
                                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->document ? "({$c->document})" : '' }}</option>
                                @endif
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
                        <input type="text" class="form-control" name="notes" id="init_client_notes" placeholder="Ej: Inventario físico inicial">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-dark">
                    <i class="ti ti-check me-1"></i> Guardar Saldo Inicial
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Kardex / Historial de Movimientos de un Cliente -->
<div class="modal fade" id="modalClientHistory" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-primary mb-1">
                        <i class="ti ti-history me-1"></i> Historial de Movimientos: <span id="clientHistoryTitle" class="text-dark"></span>
                    </h5>
                    <div class="text-muted small" id="clientHistorySubtitle"></div>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <a href="#" id="btnClientHistoryPdf" target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="ti ti-file-text me-1"></i> Exportar PDF
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <!-- Filtro rápido por activo en modal -->
                <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 small text-muted fw-bold">Filtrar por Activo:</label>
                        <select class="form-select form-select-sm w-auto" id="modalHistoryAssetFilter" onchange="renderClientHistoryTable()">
                            <option value="">Todos los Activos</option>
                            @foreach($clientAssetTypes as $at)
                                <option value="{{ $at }}">{{ $at }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="text-muted small" id="historyCounterLabel"></div>
                </div>

                <div class="table-responsive" style="min-height: 250px;">
                    <table class="table table-bordered table-vcenter table-striped table-hover mb-0">
                        <thead class="table-corporate-header">
                            <tr>
                                <th>Fecha</th>
                                <th>Activo</th>
                                <th>Tipo de Movimiento</th>
                                <th class="text-center">Cantidad</th>
                                <th>Despachador</th>
                                <th>Observaciones / Ref.</th>
                                <th>Registrado Por</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="clientHistoryTbody">
                            <!-- Filas dinámicas JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@if(!$isDispatcher)
<!-- Modal: Establecer Saldo Inicial -->
<div class="modal fade" id="modalInitialBalance" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="formInitialBalance">
            @csrf
            <input type="hidden" name="item_type" id="init_item_type">
            <input type="hidden" name="item_id" id="init_item_id">
            <input type="hidden" name="item_name" id="init_item_name_val">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Establecer Saldo Inicial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Ítem / Categoría</label>
                    <input type="text" class="form-control bg-light fw-bold" id="init_item_name" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Cantidad Saldo Inicial</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="quantity" id="init_quantity" required>
                    <small class="form-hint">Este valor servirá como punto de partida para el cálculo de Kardex.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Saldo Inicial</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Modal: Registrar Movimiento (Ingreso / Salida / Devolución) -->
<div class="modal fade" id="modalMovement" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="formMovement">
            @csrf
            <input type="hidden" name="item_type" id="mov_item_type">
            <input type="hidden" name="item_id" id="mov_item_id">
            <input type="hidden" name="item_name" id="mov_item_name_val">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="movModalTitle">Registrar Movimiento / Devolución</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Seleccionar Ítem</label>
                    <select class="form-select" id="mov_select_item" onchange="onSelectItemChange(this)">
                        <optgroup label="Insumos / Bidones">
                            @foreach($supplies as $s)
                                <option value="supply|{{ $s->id }}|{{ $s->name }}">Insumo: {{ $s->name }}</option>
                            @endforeach
                        </optgroup>
                        @if(isset($assetsData) && $assetsData->count() > 0)
                        <optgroup label="Activos Fijos (Equipos)">
                            @foreach($assetsData as $a)
                                <option value="fixed_asset||{{ $a->category }}">Activo: {{ $a->category }}</option>
                            @endforeach
                        </optgroup>
                        @endif
                        <optgroup label="Productos Terminados">
                            @foreach($products as $p)
                                <option value="product|{{ $p->id }}|{{ $p->name }}">Producto: {{ $p->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                @if(!$isDispatcher)
                <div class="mb-3">
                    <label class="form-label required">Tipo de Movimiento</label>
                    <select class="form-select" name="movement_type" id="mov_type" onchange="onMovementTypeChange(this.value)" required>
                        <option value="income">Ingreso (+)</option>
                        <option value="outcome">Salida (-)</option>
                        <option value="return" selected>Devolución (+)</option>
                    </select>
                </div>
                @else
                    <input type="hidden" name="movement_type" id="mov_type" value="return">
                    <div class="mb-3">
                        <label class="form-label text-muted small"><i class="ti ti-rotate-2 me-1 text-warning"></i>Tipo de Operación</label>
                        <input type="text" class="form-control form-control-sm bg-light fw-bold text-warning" value="Devolución de Ítem (+)" readonly>
                    </div>
                @endif

                <!-- Campo de Selección de Cliente -->
                <div class="mb-3" id="group_mov_client">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span><i class="ti ti-user me-1 text-primary"></i> Cliente <span id="client_optional_badge" class="text-muted fw-normal small">(Recomendado en Devoluciones)</span></span>
                        <span id="client_required_indicator" class="badge bg-warning-lt">Devolución de Cliente</span>
                    </label>
                    <select class="form-select ts-select" name="client_id" id="mov_client_id" style="width: 100%;">
                        <option value="">-- Seleccionar Cliente (Ninguno / Stock General) --</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} {{ $c->business_name ? '('.$c->business_name.')' : '' }} {{ $c->document ? '['.$c->document.']' : '' }}</option>
                        @endforeach
                    </select>
                    <small class="form-hint text-muted" id="client_hint">
                        Escribe para buscar el cliente por nombre, razón social o documento.
                    </small>
                </div>

                <!-- Campo de Selección de Despachador (Solo visible para Admin/Asistente) -->
                @if(!$isDispatcher)
                <div class="mb-3" id="group_mov_dispatcher">
                    <label class="form-label">
                        <i class="ti ti-truck-delivery me-1 text-info"></i> Despachador Responsable <span class="text-muted fw-normal small">(Opcional / Quien recuperó el ítem)</span>
                    </label>
                    <select class="form-select ts-select" name="dispatcher_id" id="mov_dispatcher_id" style="width: 100%;">
                        <option value="">-- Sin despachador asignado --</option>
                        @foreach($dispatchers as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                    <div class="mb-3">
                        <label class="form-label text-muted small"><i class="ti ti-user-check me-1 text-success"></i>Despachador Registrador</label>
                        <input type="text" class="form-control form-control-sm bg-light" value="{{ auth()->user()->name }}" readonly>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label required">Cantidad</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="quantity" id="mov_quantity" placeholder="Ej: 5" required>
                </div>

                @if(!$isDispatcher)
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Monto S/ (Opcional)</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" id="mov_amount" placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Método de Pago (Opcional)</label>
                        <select class="form-select" name="payment_method_id" id="mov_payment_method_id">
                            <option value="">-- Sin afección a caja --</option>
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <small class="form-hint text-muted">
                            <i class="ti ti-info-circle me-1"></i> Si especificas Monto y Método de Pago, afectará la Caja (Ingreso o Egreso según el tipo de movimiento).
                        </small>
                    </div>
                </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Notas / Observación</label>
                    <input type="text" class="form-control" name="notes" id="mov_notes" placeholder="Ej: Devolución de 5 bidones vacíos del cliente">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success" id="btnSaveMov">Registrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Historial Kardex -->
<div class="modal fade" id="modalKardexHistory" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="kardexModalTitle">Historial de Movimientos Kardex</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="p-3 bg-light border-bottom">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label mb-1 text-muted small fw-bold"><i class="ti ti-calendar me-1"></i>Desde</label>
                        <input type="date" class="form-control form-control-sm" id="modal_kardex_start_date">
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label mb-1 text-muted small fw-bold"><i class="ti ti-calendar me-1"></i>Hasta</label>
                        <input type="date" class="form-control form-control-sm" id="modal_kardex_end_date">
                    </div>
                    <div class="col-md-4 col-12 d-flex align-items-end gap-1 mt-md-4">
                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="applyModalKardexFilter()">
                            <i class="ti ti-filter me-1"></i> Filtrar Fecha
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearModalKardexFilter()">
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Cliente</th>
                                <th>Despachador</th>
                                <th>Notas / Referencia</th>
                                <th>Registrado Por</th>
                            </tr>
                        </thead>
                        <tbody id="kardexHistoryBody">
                            <tr>
                                <td colspan="7" class="text-center py-4">Cargando movimientos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@if(!$isDispatcher)
<!-- Modal: Nuevo Insumo -->
<div class="modal fade" id="modalNewSupply" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="formNewSupply">
            @csrf
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Agregar Nuevo Insumo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Nombre del Insumo</label>
                    <input type="text" class="form-control" name="name" placeholder="Ej: Bidones 20L, Tapas rojas, Bolsas 3kg" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unidad de Medida</label>
                    <input type="text" class="form-control" name="unit" placeholder="Ej: Unidades, Millares, Paquetes" value="Unidades">
                </div>
                <div class="mb-3">
                    <label class="form-label">Saldo Inicial</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="stock" value="0">
                </div>
                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allowed_for_dispatchers" value="1" checked>
                        <span class="form-check-label fw-bold">Permitir a Despachadores registrar devoluciones de este insumo</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Insumo</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    let currentKardexItem = { type: null, id: null, name: null };

    $(document).ready(function() {
        if ($.fn.modal) {
            $.fn.modal.Constructor.prototype._enforceFocus = function() {};
        }

        $('#modalMovement').on('shown.bs.modal', function () {
            $('#mov_client_id').select2({
                dropdownParent: $('#modalMovement'),
                placeholder: "-- Buscar y seleccionar cliente --",
                allowClear: true,
                width: '100%'
            });

            if ($('#mov_dispatcher_id').length) {
                $('#mov_dispatcher_id').select2({
                    dropdownParent: $('#modalMovement'),
                    placeholder: "-- Buscar despachador --",
                    allowClear: true,
                    width: '100%'
                });
            }
        });

        // Inicializar Select2 en Modal de Movimiento de Activos por Cliente
        $('#modalClientMovement').on('shown.bs.modal', function () {
            $('#client_mov_client_id').select2({
                dropdownParent: $('#modalClientMovement'),
                placeholder: "-- Buscar cliente o planta por nombre o documento --",
                allowClear: false,
                width: '100%'
            });

            if ($('#client_mov_dispatcher_id').length) {
                $('#client_mov_dispatcher_id').select2({
                    dropdownParent: $('#modalClientMovement'),
                    placeholder: "-- Buscar despachador (opcional) --",
                    allowClear: true,
                    width: '100%'
                });
            }
        });

        // Inicializar Select2 en Modal de Saldo Inicial por Cliente
        $('#modalClientInitialBalance').on('shown.bs.modal', function () {
            $('#init_client_select').select2({
                dropdownParent: $('#modalClientInitialBalance'),
                placeholder: "-- Buscar cliente o planta por nombre o documento --",
                allowClear: false,
                width: '100%'
            });
        });

        // Inicializar Select2 en Barra de Filtros de Clientes (Permite escribir y buscar entre miles de clientes)
        $('#clientFilterSelect').select2({
            placeholder: "🏢 Todos los Clientes y Planta",
            allowClear: true,
            width: '100%'
        }).on('select2:select', function (e) {
            applyClientFilter($(this).val());
        }).on('select2:clear', function (e) {
            applyClientFilter('');
        });

        // Reajustar ancho al cambiar de pestaña si estaba oculto
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') === '#tab-client-assets') {
                $('#clientFilterSelect').select2({
                    placeholder: "🏢 Todos los Clientes y Planta",
                    allowClear: true,
                    width: '100%'
                });
            }
        });
    });

    function toggleDispatcherPermission(type, id, allowed) {
        let payload = {
            item_type: type,
            allowed: allowed ? 1 : 0
        };
        if (type === 'fixed_asset') {
            payload.item_name = id;
        } else {
            payload.item_id = id;
        }

        fetch("{{ route('inventories.toggle_dispatcher_permission') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (!data.status) {
                alert(data.error || 'Error al actualizar permiso.');
                location.reload();
            }
        })
        .catch(err => {
            alert('Error de conexión al actualizar permiso.');
            location.reload();
        });
    }

    function setQuickDate(preset) {
        let startInput = document.getElementById('filter_start_date');
        let endInput = document.getElementById('filter_end_date');
        let now = new Date();
        let yyyy = now.getFullYear();
        let mm = String(now.getMonth() + 1).padStart(2, '0');
        let dd = String(now.getDate()).padStart(2, '0');

        if (preset === 'today') {
            let todayStr = `${yyyy}-${mm}-${dd}`;
            startInput.value = todayStr;
            endInput.value = todayStr;
        } else if (preset === 'month') {
            startInput.value = `${yyyy}-${mm}-01`;
            endInput.value = `${yyyy}-${mm}-${dd}`;
        }
    }

    function openInitialBalanceModal(type, id, name, currentVal) {
        let modalEl = document.getElementById('modalInitialBalance');
        if (!modalEl) return;
        document.getElementById('init_item_type').value = type;
        document.getElementById('init_item_id').value = id || '';
        document.getElementById('init_item_name_val').value = name || '';
        document.getElementById('init_item_name').value = name;
        document.getElementById('init_quantity').value = currentVal || 0;
        
        let modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function onMovementTypeChange(movType) {
        let reqInd = document.getElementById('client_required_indicator');
        let badgeOpt = document.getElementById('client_optional_badge');
        let notesInput = document.getElementById('mov_notes');

        if (movType === 'return') {
            if (reqInd) reqInd.classList.remove('d-none');
            if (badgeOpt) badgeOpt.innerText = '(Recomendado)';
            if (notesInput && !notesInput.value) notesInput.placeholder = 'Ej: Devolución de 5 bidones vacíos del cliente';
        } else {
            if (reqInd) reqInd.classList.add('d-none');
            if (badgeOpt) badgeOpt.innerText = '(Opcional)';
            if (notesInput && !notesInput.value) notesInput.placeholder = 'Ej: Compra de lote, ajuste de stock, consumo en producción';
        }
    }

    function openMovementModal(type = null, id = null, name = null, movType = 'return', clientId = null, dispatcherId = null) {
        if (type && name) {
            let val = type + '|' + (id || '') + '|' + name;
            let sel = document.getElementById('mov_select_item');
            if (sel) {
                sel.value = val;
                onSelectItemChange(sel);
            }
        } else {
            let sel = document.getElementById('mov_select_item');
            if (sel && sel.options.length > 0) {
                onSelectItemChange(sel);
            }
        }

        let movTypeElem = document.getElementById('mov_type');
        if (movTypeElem) {
            movTypeElem.value = movType;
            onMovementTypeChange(movType);
        }

        document.getElementById('mov_quantity').value = '';
        if (document.getElementById('mov_amount')) document.getElementById('mov_amount').value = '';
        if (document.getElementById('mov_payment_method_id')) document.getElementById('mov_payment_method_id').value = '';
        if (document.getElementById('mov_notes')) document.getElementById('mov_notes').value = '';
        
        // Reset or set select2 client
        if ($('#mov_client_id').data('select2')) {
            $('#mov_client_id').val(clientId || '').trigger('change');
        } else if (document.getElementById('mov_client_id')) {
            document.getElementById('mov_client_id').value = clientId || '';
        }

        // Reset or set select2 dispatcher
        if ($('#mov_dispatcher_id').length) {
            if ($('#mov_dispatcher_id').data('select2')) {
                $('#mov_dispatcher_id').val(dispatcherId || '').trigger('change');
            } else {
                document.getElementById('mov_dispatcher_id').value = dispatcherId || '';
            }
        }

        let modal = new bootstrap.Modal(document.getElementById('modalMovement'));
        modal.show();
    }

    function onSelectItemChange(selectElem) {
        let parts = selectElem.value.split('|');
        document.getElementById('mov_item_type').value = parts[0] || '';
        document.getElementById('mov_item_id').value = parts[1] || '';
        document.getElementById('mov_item_name_val').value = parts[2] || '';
    }

    function showKardexHistory(type, id, name) {
        currentKardexItem = { type: type, id: id, name: name };
        document.getElementById('kardexModalTitle').innerText = 'Historial Kardex - ' + name;
        
        // Copiar fechas del filtro principal si existen
        let mainStart = document.getElementById('filter_start_date').value;
        let mainEnd = document.getElementById('filter_end_date').value;
        document.getElementById('modal_kardex_start_date').value = mainStart || '';
        document.getElementById('modal_kardex_end_date').value = mainEnd || '';

        let modal = new bootstrap.Modal(document.getElementById('modalKardexHistory'));
        modal.show();

        loadKardexData(type, id, name, mainStart, mainEnd);
    }

    function applyModalKardexFilter() {
        let start = document.getElementById('modal_kardex_start_date').value;
        let end = document.getElementById('modal_kardex_end_date').value;
        loadKardexData(currentKardexItem.type, currentKardexItem.id, currentKardexItem.name, start, end);
    }

    function clearModalKardexFilter() {
        document.getElementById('modal_kardex_start_date').value = '';
        document.getElementById('modal_kardex_end_date').value = '';
        loadKardexData(currentKardexItem.type, currentKardexItem.id, currentKardexItem.name, '', '');
    }

    function loadKardexData(type, id, name, startDate = '', endDate = '') {
        let body = document.getElementById('kardexHistoryBody');
        body.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando...</td></tr>';

        let url = "{{ url('inventories/history') }}/" + type + "/" + (id || 0) + "?item_name=" + encodeURIComponent(name);
        if (startDate) url += '&start_date=' + encodeURIComponent(startDate);
        if (endDate) url += '&end_date=' + encodeURIComponent(endDate);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay movimientos registrados para este ítem en el rango de fechas seleccionado.</td></tr>';
                    return;
                }
                let html = '';
                data.forEach(m => {
                    let badgeClass = 'bg-secondary-lt';
                    if (m.movement_type === 'initial_balance') badgeClass = 'bg-info-lt';
                    if (m.movement_type === 'income') badgeClass = 'bg-success-lt';
                    if (m.movement_type === 'outcome') badgeClass = 'bg-danger-lt';
                    if (m.movement_type === 'return') badgeClass = 'bg-warning-lt';

                    let clientBadge = m.client_name 
                        ? `<span class="badge bg-blue-lt fw-bold"><i class="ti ti-user me-1"></i>${m.client_name}</span>`
                        : '<span class="text-muted small">-</span>';

                    let dispatcherBadge = m.dispatcher_name
                        ? `<span class="badge bg-purple-lt fw-bold"><i class="ti ti-truck-delivery me-1"></i>${m.dispatcher_name}</span>`
                        : '<span class="text-muted small">-</span>';

                    html += `
                        <tr>
                            <td>${m.date}</td>
                            <td><span class="badge ${badgeClass}">${m.type_label}</span></td>
                            <td class="fw-bold">${m.quantity}</td>
                            <td>${clientBadge}</td>
                            <td>${dispatcherBadge}</td>
                            <td>${m.notes}</td>
                            <td><small class="text-muted">${m.user}</small></td>
                        </tr>
                    `;
                });
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Error al cargar historial.</td></tr>';
            });
    }

    // Submit Initial Balance
    let formInitialBalance = document.getElementById('formInitialBalance');
    if (formInitialBalance) {
        formInitialBalance.addEventListener('submit', function(e) {
            e.preventDefault();
            let btn = this.querySelector('button[type="submit"]');
            if (btn.disabled) return;
            let originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';

            let formData = new FormData(this);
            fetch("{{ route('inventories.initial_balance') }}", {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    location.reload();
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    alert(data.error || 'Ocurrió un error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert('Ocurrió un error al procesar la solicitud.');
            });
        });
    }

    // Submit Movement
    document.getElementById('formMovement').addEventListener('submit', function(e) {
        e.preventDefault();
        let btn = this.querySelector('button[type="submit"]');
        if (btn.disabled) return;
        let originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';

        let formData = new FormData(this);
        fetch("{{ route('inventories.movement') }}", {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                location.reload();
            } else {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert(data.error || 'Ocurrió un error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            alert('Ocurrió un error al procesar la solicitud.');
        });
    });

    // Submit New Supply
    let formNewSupply = document.getElementById('formNewSupply');
    if (formNewSupply) {
        formNewSupply.addEventListener('submit', function(e) {
            e.preventDefault();
            let btn = this.querySelector('button[type="submit"]');
            if (btn.disabled) return;
            let originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';

            let formData = new FormData(this);
            fetch("{{ route('inventories.supplies.store') }}", {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    location.reload();
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    alert(data.error || 'Ocurrió un error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert('Ocurrió un error al procesar la solicitud.');
            });
        });
    }

    // =========================================================================
    // JAVASCRIPT: CONTROL DE ACTIVOS POR CLIENTE (Y PLANTA)
    // =========================================================================

    let currentClientHistoryMovs = [];
    let currentClientId = null;

    // Filtro en tiempo real en la tabla
    function filterClientAssetsTable() {
        let input = document.getElementById('searchClientAssetsInput').value.toLowerCase();
        let rows = document.querySelectorAll('#tableClientAssets tbody tr.client-asset-row');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    }

    // Filtro por selector de cliente
    function applyClientFilter(clientId) {
        let url = new URL(window.location.href);
        if (clientId) {
            url.searchParams.set('client_id', clientId);
        } else {
            url.searchParams.delete('client_id');
        }
        url.searchParams.set('tab', 'client-assets');
        window.location.href = url.toString();
    }

    // Filtro por tipo de activo
    function applyAssetFilter(assetType) {
        let url = new URL(window.location.href);
        if (assetType) {
            url.searchParams.set('asset_type', assetType);
        } else {
            url.searchParams.delete('asset_type');
        }
        url.searchParams.set('tab', 'client-assets');
        window.location.href = url.toString();
    }

    const plantaClientId = "{{ isset($plantaClient) ? $plantaClient->id : 0 }}";

    function updateMovementModalTypeOptions() {
        let selClient = $('#client_mov_client_id').val();
        let typeSelect = $('#client_mov_type');
        let helpText = $('#movementTypeHelp');
        let titleModal = $('#modalClientMovementTitle');

        let currentVal = typeSelect.val();
        typeSelect.empty();

        if (selClient && selClient == plantaClientId) {
            titleModal.html('<i class="ti ti-building-warehouse me-1"></i> Movimiento Directo en Planta (Almacén Central)');
            helpText.text('Movimiento directo en el almacén de Planta (sin cliente involucrado).');
            typeSelect.append('<option value="income">🟢 Ingreso directo a Planta (+) (Compra / Adquisición)</option>');
            typeSelect.append('<option value="outcome">🔴 Salida directa de Planta (-) (Baja técnica / Merma / Retiro)</option>');
            if (currentVal === 'income' || currentVal === 'outcome') {
                typeSelect.val(currentVal);
            }
        } else {
            titleModal.html('<i class="ti ti-arrows-left-right me-1"></i> Salida de Planta y Entrega a Cliente / Devolución');
            helpText.text('Al entregar, disminuye el stock físico de Planta y se suma al cliente. Al devolver, regresa al stock de Planta.');
            typeSelect.append('<option value="delivery" selected>📤 Salida de Planta y Entrega a Cliente (+ Cliente, - Planta)</option>');
            typeSelect.append('<option value="return">📥 Devolución de Cliente e Ingreso a Planta (- Cliente, + Planta)</option>');
            if (currentVal === 'delivery' || currentVal === 'return') {
                typeSelect.val(currentVal);
            }
        }
    }

    $('#client_mov_client_id').on('change', function() {
        updateMovementModalTypeOptions();
    });

    // Abrir modal de movimiento de activo por cliente
    function openClientMovementModal(clientId = null, assetType = null) {
        let form = document.getElementById('formClientMovement');
        if (form) form.reset();

        document.getElementById('client_mov_date').value = "{{ date('Y-m-d') }}";

        if (clientId) {
            let sel = document.getElementById('client_mov_client_id');
            if (sel) {
                sel.value = clientId;
                if ($(sel).data('select2')) {
                    $(sel).val(clientId).trigger('change');
                }
            }
        } else {
            if ($('#client_mov_client_id').data('select2')) {
                $('#client_mov_client_id').val('').trigger('change');
            }
        }

        updateMovementModalTypeOptions();

        if (assetType) {
            let aSel = document.getElementById('client_mov_asset_type');
            if (aSel) aSel.value = assetType;
        }

        if ($('#client_mov_dispatcher_id').data('select2')) {
            $('#client_mov_dispatcher_id').val('').trigger('change');
        }

        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalClientMovement'));
        modal.show();
    }

    // Abrir modal de saldo inicial por cliente
    function openClientInitialBalanceModal(clientId = null, assetType = null) {
        let form = document.getElementById('formClientInitialBalance');
        if (form) form.reset();

        document.getElementById('init_client_date').value = "{{ request('start_date') ?: date('Y-m-d') }}";

        if (clientId) {
            let sel = document.getElementById('init_client_select');
            if (sel) {
                sel.value = clientId;
                if ($(sel).data('select2')) {
                    $(sel).val(clientId).trigger('change');
                }
            }
        } else {
            if ($('#init_client_select').data('select2')) {
                $('#init_client_select').val('').trigger('change');
            }
        }

        if (assetType) {
            let aSel = document.getElementById('init_asset_type_select');
            if (aSel) aSel.value = assetType;
        }

        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalClientInitialBalance'));
        modal.show();
    }

    // Mostrar Kardex / Historial de cliente
    function showClientAssetHistory(clientId, clientName, clientDoc = '') {
        currentClientId = clientId;
        document.getElementById('clientHistoryTitle').innerText = (clientId == plantaClientId) ? '🏭 ALMACÉN CENTRAL - PLANTA' : clientName;
        document.getElementById('clientHistorySubtitle').innerText = (clientId == plantaClientId) ? 'Control y trazabilidad de activos en almacén central' : (clientDoc ? 'Documento: ' + clientDoc : 'Detalle de activos prestados y devueltos');

        // URL para el PDF individual
        let pdfUrl = new URL("{{ route('inventories.client_assets.detailed_pdf') }}");
        pdfUrl.searchParams.set('client_id', clientId);
        @if(request('start_date')) pdfUrl.searchParams.set('start_date', "{{ request('start_date') }}"); @endif
        @if(request('end_date')) pdfUrl.searchParams.set('end_date', "{{ request('end_date') }}"); @endif
        document.getElementById('btnClientHistoryPdf').href = pdfUrl.toString();

        let tbody = document.getElementById('clientHistoryTbody');
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4"><span class="spinner-border text-primary me-2"></span> Cargando historial...</td></tr>`;

        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalClientHistory'));
        modal.show();

        // Parámetros de consulta
        let params = new URLSearchParams();
        @if(request('start_date')) params.append('start_date', "{{ request('start_date') }}"); @endif
        @if(request('end_date')) params.append('end_date', "{{ request('end_date') }}"); @endif

        fetch(`{{ url('inventories/client-assets/history') }}/${clientId}?${params.toString()}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                currentClientHistoryMovs = data.movements || [];
                renderClientHistoryTable();
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${data.error || 'Error al cargar'}</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Error de conexión al cargar historial.</td></tr>`;
        });
    }

    // Renderizar tabla de historial en modal
    function renderClientHistoryTable() {
        let tbody = document.getElementById('clientHistoryTbody');
        let filter = document.getElementById('modalHistoryAssetFilter').value;

        let filtered = currentClientHistoryMovs;
        if (filter) {
            filtered = currentClientHistoryMovs.filter(m => m.asset_name === filter);
        }

        document.getElementById('historyCounterLabel').innerText = `${filtered.length} registro(s) encontrado(s)`;

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">No se encontraron movimientos para este filtro.</td></tr>`;
            return;
        }

        let html = '';
        filtered.forEach(m => {
            let badgeClass = 'bg-secondary-lt text-secondary';
            if (m.movement_type === 'initial_balance') {
                badgeClass = 'bg-info-lt text-info fw-bold';
            } else if (m.movement_type === 'income' || m.movement_type === 'delivery') {
                badgeClass = 'bg-success-lt text-success fw-bold';
            } else if (m.movement_type === 'outcome' || m.movement_type === 'return' || m.movement_type === 'withdrawal') {
                badgeClass = 'bg-danger-lt text-danger fw-bold';
            }

            let deleteBtn = '';
            if (m.can_delete) {
                deleteBtn = `<button class="btn btn-sm btn-icon btn-ghost-danger" title="Eliminar registro" onclick="deleteClientMovement(${m.id})"><i class="ti ti-trash"></i></button>`;
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
                    <td class="text-end">${deleteBtn}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    // Eliminar movimiento de cliente
    function deleteClientMovement(movementId) {
        if (!confirm('¿Está seguro de eliminar este registro de movimiento? Esta acción no se puede deshacer.')) {
            return;
        }

        fetch(`{{ url('inventories/client-assets/movement') }}/${movementId}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                if (currentClientId) {
                    showClientAssetHistory(currentClientId, document.getElementById('clientHistoryTitle').innerText);
                } else {
                    location.reload();
                }
            } else {
                alert(data.error || 'No se pudo eliminar el movimiento.');
            }
        })
        .catch(err => alert('Error de conexión al eliminar.'));
    }

    // Enviar Movimiento de Cliente
    let formClientMovement = document.getElementById('formClientMovement');
    if (formClientMovement) {
        formClientMovement.addEventListener('submit', function(e) {
            e.preventDefault();
            let btn = this.querySelector('button[type="submit"]');
            if (btn.disabled) return;
            let originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';

            let formData = new FormData(this);
            fetch("{{ route('inventories.client_assets.movement') }}", {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    let url = new URL(window.location.href);
                    url.searchParams.set('tab', 'client-assets');
                    let movedAsset = formData.get('asset_type');
                    let currentAssetFilter = url.searchParams.get('asset_type');
                    if (currentAssetFilter && currentAssetFilter !== movedAsset) {
                        url.searchParams.delete('asset_type');
                    }
                    window.location.href = url.toString();
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    alert(data.error || 'Ocurrió un error al guardar');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert('Ocurrió un error al procesar la solicitud.');
            });
        });
    }

    // Enviar Saldo Inicial de Cliente
    let formClientInitialBalance = document.getElementById('formClientInitialBalance');
    if (formClientInitialBalance) {
        formClientInitialBalance.addEventListener('submit', function(e) {
            e.preventDefault();
            let btn = this.querySelector('button[type="submit"]');
            if (btn.disabled) return;
            let originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';

            let formData = new FormData(this);
            fetch("{{ route('inventories.client_assets.initial_balance') }}", {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    let url = new URL(window.location.href);
                    url.searchParams.set('tab', 'client-assets');
                    window.location.href = url.toString();
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    alert(data.error || 'Ocurrió un error al guardar');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert('Ocurrió un error al procesar la solicitud.');
            });
        });
    }

    // Preservar pestaña activa en cambio de pestañas
    document.addEventListener('DOMContentLoaded', function() {
        let tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                let target = e.target.getAttribute('href');
                let tabName = target.replace('#tab-', '');
                let url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url.toString());
            });
        });
    });
</script>
@endsection
