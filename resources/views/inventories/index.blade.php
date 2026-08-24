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
                    Control de Saldo Inicial, Ingresos, Salidas y Saldo Final por Insumos, Activos y Productos
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNewSupply">
                        <i class="ti ti-plus me-1"></i> Nuevo Insumo
                    </button>
                    <button class="btn btn-primary" onclick="openMovementModal()">
                        <i class="ti ti-arrows-left-right me-1"></i> Registrar Movimiento
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
                                {{ $supplies->count() }} Insumos Registrados
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

    <!-- Tabs Container -->

    <!-- Tabs Container -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                <li class="nav-item">
                    <a href="#tab-supplies" class="nav-link active" data-bs-toggle="tab">
                        <i class="ti ti-box me-1"></i> Insumos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-assets" class="nav-link" data-bs-toggle="tab">
                        <i class="ti ti-devices me-1"></i> Activos (Equipos)
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-products" class="nav-link" data-bs-toggle="tab">
                        <i class="ti ti-building-store me-1"></i> Productos Terminados
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content">
            <!-- TAB 1: INSUMOS -->
            <div class="tab-pane active show" id="tab-supplies">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr class="bg-light">
                                <th>Insumo</th>
                                <th>Unidad</th>
                                <th class="text-center">Saldo Inicial</th>
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
                                <td class="text-center fw-bold text-muted">
                                    {{ number_format($s->saldo_inicial, 2) }}
                                    <button class="btn btn-sm btn-icon btn-ghost-secondary ms-1" 
                                            onclick="openInitialBalanceModal('supply', '{{ $s->id }}', '{{ $s->name }}', '{{ $s->saldo_inicial }}')" 
                                            title="Editar Saldo Inicial">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                </td>
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
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="openMovementModal('supply', '{{ $s->id }}', '{{ $s->name }}', 'income')">
                                            <i class="ti ti-plus me-1"></i> Ingreso
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="openMovementModal('supply', '{{ $s->id }}', '{{ $s->name }}', 'outcome')">
                                            <i class="ti ti-minus me-1"></i> Salida
                                        </button>
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
                                <td colspan="7" class="text-center text-muted py-4">
                                    No hay insumos registrados. Presiona <strong>"Nuevo Insumo"</strong> para comenzar.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: ACTIVOS (EQUIPOS) -->
            <div class="tab-pane" id="tab-assets">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr class="bg-light">
                                <th>Categoría de Activo</th>
                                <th>Disponibles / Prestados</th>
                                <th class="text-center">Saldo Inicial</th>
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
                                <td class="text-center fw-bold text-muted">
                                    {{ number_format($a->saldo_inicial, 0) }}
                                    <button class="btn btn-sm btn-icon btn-ghost-secondary ms-1" 
                                            onclick="openInitialBalanceModal('fixed_asset', null, '{{ $a->category }}', '{{ $a->saldo_inicial }}')" 
                                            title="Editar Saldo Inicial">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                </td>
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
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="openMovementModal('fixed_asset', null, '{{ $a->category }}', 'income')">
                                            <i class="ti ti-plus me-1"></i> Ingreso
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="openMovementModal('fixed_asset', null, '{{ $a->category }}', 'outcome')">
                                            <i class="ti ti-minus me-1"></i> Salida
                                        </button>
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
                                <td colspan="7" class="text-center text-muted py-4">No hay activos registrados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: PRODUCTOS TERMINADOS -->
            <div class="tab-pane" id="tab-products">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr class="bg-light">
                                <th>Producto</th>
                                <th>Precio Venta</th>
                                <th class="text-center">Saldo Inicial</th>
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
                                <td class="text-center fw-bold text-muted">
                                    {{ number_format($p->saldo_inicial, 2) }}
                                    <button class="btn btn-sm btn-icon btn-ghost-secondary ms-1" 
                                            onclick="openInitialBalanceModal('product', '{{ $p->id }}', '{{ $p->name }}', '{{ $p->saldo_inicial }}')" 
                                            title="Editar Saldo Inicial">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                </td>
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
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="openMovementModal('product', '{{ $p->id }}', '{{ $p->name }}', 'income')">
                                            <i class="ti ti-plus me-1"></i> Ingreso
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="openMovementModal('product', '{{ $p->id }}', '{{ $p->name }}', 'outcome')">
                                            <i class="ti ti-minus me-1"></i> Salida
                                        </button>
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
                                <td colspan="7" class="text-center text-muted py-4">No hay productos registrados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

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

<!-- Modal: Registrar Movimiento (Ingreso / Salida / Devolución) -->
<div class="modal fade" id="modalMovement" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="formMovement">
            @csrf
            <input type="hidden" name="item_type" id="mov_item_type">
            <input type="hidden" name="item_id" id="mov_item_id">
            <input type="hidden" name="item_name" id="mov_item_name_val">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="movModalTitle">Registrar Movimiento de Inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Seleccionar Ítem</label>
                    <select class="form-select" id="mov_select_item" onchange="onSelectItemChange(this)">
                        <optgroup label="Insumos">
                            @foreach($supplies as $s)
                                <option value="supply|{{ $s->id }}|{{ $s->name }}">Insumo: {{ $s->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Activos Fijos">
                            @foreach($assetsData as $a)
                                <option value="fixed_asset||{{ $a->category }}">Activo: {{ $a->category }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Productos Terminados">
                            @foreach($products as $p)
                                <option value="product|{{ $p->id }}|{{ $p->name }}">Producto: {{ $p->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Tipo de Movimiento</label>
                    <select class="form-select" name="movement_type" id="mov_type" onchange="onMovementTypeChange(this.value)" required>
                        <option value="income">Ingreso (+)</option>
                        <option value="outcome">Salida (-)</option>
                        <option value="return">Devolución (+)</option>
                    </select>
                </div>

                <!-- Campo de Selección de Cliente -->
                <div class="mb-3" id="group_mov_client">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span><i class="ti ti-user me-1 text-primary"></i> Cliente <span id="client_optional_badge" class="text-muted fw-normal small">(Opcional / Recomendado en Devoluciones)</span></span>
                        <span id="client_required_indicator" class="badge bg-warning-lt d-none">Devolución de Cliente</span>
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

                <div class="mb-3">
                    <label class="form-label required">Cantidad</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="quantity" id="mov_quantity" required>
                </div>
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
                <div class="mb-3">
                    <label class="form-label">Notas / Observación</label>
                    <input type="text" class="form-control" name="notes" id="mov_notes" placeholder="Ej: Devolución de 5 bidones vacíos, compra de lote, etc.">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success" id="btnSaveMov">Registrar Movimiento</button>
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
                                <th>Cliente / Relación</th>
                                <th>Notas / Referencia</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody id="kardexHistoryBody">
                            <tr>
                                <td colspan="6" class="text-center py-4">Cargando movimientos...</td>
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
                    <input type="text" class="form-control" name="name" placeholder="Ej: Tapas rojas, Bolsas 3kg, Sellos de agua" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unidad de Medida</label>
                    <input type="text" class="form-control" name="unit" placeholder="Ej: Unidades, Millares, Paquetes" value="Unidades">
                </div>
                <div class="mb-3">
                    <label class="form-label">Saldo Inicial</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="stock" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Insumo</button>
            </div>
        </form>
    </div>
</div>

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
        });
    });

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
        document.getElementById('init_item_type').value = type;
        document.getElementById('init_item_id').value = id || '';
        document.getElementById('init_item_name_val').value = name || '';
        document.getElementById('init_item_name').value = name;
        document.getElementById('init_quantity').value = currentVal || 0;
        
        let modal = new bootstrap.Modal(document.getElementById('modalInitialBalance'));
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

    function openMovementModal(type = null, id = null, name = null, movType = 'income', clientId = null) {
        if (type && name) {
            let val = type + '|' + (id || '') + '|' + name;
            let sel = document.getElementById('mov_select_item');
            sel.value = val;
            onSelectItemChange(sel);
        } else {
            let sel = document.getElementById('mov_select_item');
            if (sel.options.length > 0) {
                onSelectItemChange(sel);
            }
        }

        document.getElementById('mov_type').value = movType;
        onMovementTypeChange(movType);

        document.getElementById('mov_quantity').value = '';
        if (document.getElementById('mov_amount')) document.getElementById('mov_amount').value = '';
        if (document.getElementById('mov_payment_method_id')) document.getElementById('mov_payment_method_id').value = '';
        if (document.getElementById('mov_notes')) document.getElementById('mov_notes').value = '';
        
        // Reset or set select2 client
        if ($('#mov_client_id').data('select2')) {
            $('#mov_client_id').val(clientId || '').trigger('change');
        } else {
            document.getElementById('mov_client_id').value = clientId || '';
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
        body.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando...</td></tr>';

        let url = "{{ url('inventories/history') }}/" + type + "/" + (id || 0) + "?item_name=" + encodeURIComponent(name);
        if (startDate) url += '&start_date=' + encodeURIComponent(startDate);
        if (endDate) url += '&end_date=' + encodeURIComponent(endDate);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay movimientos registrados para este ítem en el rango de fechas seleccionado.</td></tr>';
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

                    html += `
                        <tr>
                            <td>${m.date}</td>
                            <td><span class="badge ${badgeClass}">${m.type_label}</span></td>
                            <td class="fw-bold">${m.quantity}</td>
                            <td>${clientBadge}</td>
                            <td>${m.notes}</td>
                            <td><small class="text-muted">${m.user}</small></td>
                        </tr>
                    `;
                });
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Error al cargar historial.</td></tr>';
            });
    }

    // Submit Initial Balance
    document.getElementById('formInitialBalance').addEventListener('submit', function(e) {
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
    document.getElementById('formNewSupply').addEventListener('submit', function(e) {
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
</script>
@endsection
