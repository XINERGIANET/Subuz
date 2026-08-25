@extends('template.app')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
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
    .select2-dropdown {
        border-color: #dadcde;
        z-index: 9999;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #dadcde;
        border-radius: 4px;
        padding: 6px 10px;
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
        <div class="col-sm-6 col-lg-4">
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
                                {{ $supplies->count() }} Insumos / Bidones
                            </div>
                            <div class="text-muted small">
                                Bidones, tapas, sellos, etiquetas y bolsas
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
                                <i class="ti ti-snowflake icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ $assetsData->count() }} Categorías de Activos
                            </div>
                            <div class="text-muted small">
                                Dispensadores, congeladoras y exhibidores
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
                            <span class="bg-purple text-white avatar">
                                <i class="ti ti-shopping-cart icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ $products->count() }} Productos Terminados
                            </div>
                            <div class="text-muted small">
                                Hielo en bolsa y bidones de agua
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $activeTab = 'supplies';
        if ($supplies->count() > 0) {
            $activeTab = 'supplies';
        } elseif ($assetsData->count() > 0) {
            $activeTab = 'assets';
        } elseif ($products->count() > 0) {
            $activeTab = 'products';
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
            </ul>
        </div>
        <div class="card-body tab-content">
            <!-- TAB 1: INSUMOS -->
            <div class="tab-pane {{ $activeTab === 'supplies' ? 'active show' : '' }}" id="tab-supplies">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr class="bg-light">
                                <th>Insumo</th>
                                <th>Unidad</th>
                                @if(!$isDispatcher)
                                <th class="text-center" title="Permitir que los despachadores registren devoluciones/movimientos de este insumo">
                                    <i class="ti ti-truck-delivery me-1"></i> Para Despachador
                                </th>
                                <th class="text-center">Saldo Inicial</th>
                                @endif
                                <th class="text-center text-success">Ingresos (+)</th>
                                <th class="text-center text-danger">Salidas (-)</th>
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
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr class="bg-light">
                                <th>Categoría de Activo</th>
                                <th>Disponibles / Prestados</th>
                                @if(!$isDispatcher)
                                <th class="text-center" title="Permitir que los despachadores registren devoluciones de esta categoría de activos">
                                    <i class="ti ti-truck-delivery me-1"></i> Para Despachador
                                </th>
                                <th class="text-center">Saldo Inicial</th>
                                @endif
                                <th class="text-center text-success">Ingresos (+)</th>
                                <th class="text-center text-danger">Salidas (-)</th>
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
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr class="bg-light">
                                <th>Producto</th>
                                <th>Precio Venta</th>
                                @if(!$isDispatcher)
                                <th class="text-center" title="Permitir que los despachadores registren devoluciones/movimientos de este producto">
                                    <i class="ti ti-truck-delivery me-1"></i> Para Despachador
                                </th>
                                <th class="text-center">Saldo Inicial</th>
                                @endif
                                <th class="text-center text-success">Ingresos (+)</th>
                                <th class="text-center text-danger">Salidas (-)</th>
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
</script>
@endsection
