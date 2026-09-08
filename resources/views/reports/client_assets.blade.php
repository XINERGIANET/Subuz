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
                <div class="col-md-2 col-12 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ti ti-filter me-1"></i> Filtrar
                    </button>
                    @if($startDate || $endDate || $clientId || $assetFilter)
                        <a href="{{ route('reports.client_assets') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-x me-1"></i> Limpiar
                        </a>
                    @endif
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
                        <th class="text-end" style="width: 230px;">ACCIONES</th>
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
                            @if($row->total_assets != 0)
                                <span class="badge bg-light text-dark border fw-bold fs-6 px-3 py-1">
                                    {{ number_format($row->total_assets, 0) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end align-middle">
                            <div class="btn-list justify-content-end flex-nowrap">
                                <a href="{{ route('inventories.client_assets.detailed_pdf', ['client_id' => $row->client_id] + request()->query()) }}" 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-danger px-2" 
                                   title="Exportar Reporte en PDF">
                                    <i class="ti ti-file-text"></i> PDF
                                </a>
                                <button class="btn btn-sm btn-outline-primary px-2" 
                                        title="Ver Historial de Movimientos" 
                                        onclick="showClientHistory({{ $row->client_id }}, '{{ addslashes($row->client_name) }}', '{{ $row->client_document }}')">
                                    <i class="ti ti-history"></i> Kardex
                                </button>
                                @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller') || auth()->user()->hasRole('asistente'))
                                <button class="btn btn-sm btn-edit-corporate px-2" 
                                        title="Editar Datos del Cliente" 
                                        onclick="openEditClientModal({{ $row->client_id }})">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                @endif
                                @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('asistente'))
                                <button class="btn btn-sm btn-delete-corporate px-2" 
                                        title="Eliminar / Depurar Cliente" 
                                        onclick="deleteClientRecord({{ $row->client_id }}, '{{ addslashes($row->client_name) }}')">
                                    <i class="ti ti-trash"></i>
                                </button>
                                @endif
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
                            </tr>
                        </thead>
                        <tbody id="historyTbody">
                            <tr><td colspan="7" class="text-center py-4">Cargando...</td></tr>
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

<!-- Modal: Editar Cliente -->
<div class="modal fade" id="modalEditClientFromReport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow" id="formEditClientFromReport">
            @csrf
            <input type="hidden" id="edit_client_id" name="id">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="ti ti-edit me-1"></i> Editar Datos del Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">RUC o DNI</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm" name="document" id="edit_client_document">
                            <button class="btn btn-outline-primary" type="button" id="btnSearchEditDoc" title="Buscar en RENIEC/SUNAT">
                                <i class="ti ti-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold required">Nombre Comercial</label>
                        <input type="text" class="form-control form-control-sm" name="name" id="edit_client_name" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Razón Social</label>
                        <input type="text" class="form-control form-control-sm" name="business_name" id="edit_client_business_name">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold required">Dirección</label>
                        <input type="text" class="form-control form-control-sm" name="address" id="edit_client_address" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold required">Distrito</label>
                        <select class="form-select form-select-sm" name="district" id="edit_client_district" required>
                            <option value="">Seleccionar</option>
                            <option value="Chiclayo">Chiclayo</option>
                            <option value="Jose Leonardo Ortiz">Jose Leonardo Ortiz</option>
                            <option value="Lambayeque">Lambayeque</option>
                            <option value="Pimentel">Pimentel</option>
                            <option value="La Victoria">La Victoria</option>
                            <option value="Reque">Reque</option>
                            <option value="Puerto Eten">Puerto Eten</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Teléfono</label>
                        <input type="text" class="form-control form-control-sm" name="phone" id="edit_client_phone">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Teléfono 2</label>
                        <input type="text" class="form-control form-control-sm" name="phone_2" id="edit_client_phone_2">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Correo Electrónico</label>
                        <input type="email" class="form-control form-control-sm" name="email" id="edit_client_email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold required">Tipo de Cliente</label>
                        <select class="form-select form-select-sm" name="type" id="edit_client_type" required>
                            <option value="Contado">Contado</option>
                            <option value="Credito">Crédito</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary" id="btnSaveEditClient">
                    <i class="ti ti-device-floppy me-1"></i> Guardar Cambios
                </button>
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

        // Búsqueda RENIEC/SUNAT en modal de edición
        $('#btnSearchEditDoc').click(function() {
            let doc = $('#edit_client_document').val().trim();
            if (doc.length === 8) {
                queryDoc(doc, 'reniec');
            } else if (doc.length === 11) {
                queryDoc(doc, 'ruc');
            } else {
                if (typeof ToastError !== 'undefined') {
                    ToastError.fire({ text: 'El documento debe tener 8 (DNI) o 11 (RUC) dígitos.' });
                } else {
                    alert('El documento debe tener 8 (DNI) u 11 (RUC) dígitos.');
                }
            }
        });
    });

    function queryDoc(docNumber, type) {
        let url = type === 'reniec' ? '/api/reniec?dni=' + docNumber : '/api/ruc?ruc=' + docNumber;
        let btn = $('#btnSearchEditDoc');
        let originalIcon = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(data) {
                btn.html(originalIcon).prop('disabled', false);
                if (data.status) {
                    if (type === 'reniec') {
                        $('#edit_client_name').val(data.nombre_completo);
                        if (typeof ToastMessage !== 'undefined') ToastMessage.fire({ text: 'DNI encontrado' });
                    } else {
                        $('#edit_client_name').val(data.trade_name || data.legal_name);
                        $('#edit_client_business_name').val(data.legal_name);
                        $('#edit_client_address').val(data.address);
                        if (typeof ToastMessage !== 'undefined') ToastMessage.fire({ text: 'RUC encontrado' });
                    }
                } else {
                    if (typeof ToastError !== 'undefined') ToastError.fire({ text: data.message || 'No se encontró información' });
                    else alert(data.message || 'No se encontró información');
                }
            },
            error: function(err) {
                btn.html(originalIcon).prop('disabled', false);
                if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error al consultar documento' });
                else alert('Error al consultar documento');
            }
        });
    }

    // Filtro instantáneo de texto
    function filterTable() {
        let val = document.getElementById('searchInput').value.toLowerCase().trim();
        let rows = document.querySelectorAll('#tableClientsReport tbody tr.client-row');

        rows.forEach(r => {
            let text = r.innerText.toLowerCase();
            r.style.display = text.includes(val) ? '' : 'none';
        });
    }

    // Ver Kardex
    function showClientHistory(clientId, clientName, clientDoc) {
        document.getElementById('historyTitle').innerText = clientName;
        document.getElementById('historySubtitle').innerText = clientDoc ? 'Documento: ' + clientDoc : 'Historial de activos en préstamo';

        let pdfUrl = new URL("{{ route('inventories.client_assets.detailed_pdf') }}");
        pdfUrl.searchParams.set('client_id', clientId);
        document.getElementById('btnExportPdf').href = pdfUrl.toString();

        let tbody = document.getElementById('historyTbody');
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><span class="spinner-border text-primary me-2"></span> Cargando...</td></tr>`;

        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHistory'));
        modal.show();

        fetch(`{{ url('inventories/client-assets/history') }}/${clientId}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.status && data.movements) {
                if (data.movements.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No hay movimientos registrados.</td></tr>`;
                    return;
                }
                let html = '';
                data.movements.forEach(m => {
                    let badgeClass = 'bg-secondary-lt text-secondary';
                    if (m.movement_type === 'initial_balance') badgeClass = 'bg-info-lt text-info fw-bold';
                    else if (m.movement_type === 'income') badgeClass = 'bg-success-lt text-success fw-bold';
                    else if (m.movement_type === 'outcome') badgeClass = 'bg-danger-lt text-danger fw-bold';

                    html += `
                        <tr>
                            <td class="text-nowrap fw-bold">${m.date}</td>
                            <td><span class="badge bg-blue-lt">${m.asset_name}</span></td>
                            <td><span class="badge ${badgeClass}">${m.type_label}</span></td>
                            <td class="text-center fw-bold fs-6">${m.quantity}</td>
                            <td class="small text-muted">${m.dispatcher_name || '-'}</td>
                            <td class="small">${m.notes || '-'}</td>
                            <td class="small text-muted">${m.user_name || 'Sistema'}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Error al cargar historial.</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Error de conexión.</td></tr>`;
        });
    }

    // Abrir Modal de Edición de Cliente
    function openEditClientModal(clientId) {
        let form = document.getElementById('formEditClientFromReport');
        if (form) form.reset();
        document.getElementById('edit_client_id').value = clientId;

        fetch(`{{ url('clients') }}/${clientId}/edit`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if (data) {
                document.getElementById('edit_client_document').value = data.document || '';
                document.getElementById('edit_client_name').value = data.name || '';
                document.getElementById('edit_client_business_name').value = data.business_name || '';
                document.getElementById('edit_client_address').value = data.address || '';
                document.getElementById('edit_client_district').value = data.district || '';
                document.getElementById('edit_client_phone').value = data.phone || '';
                document.getElementById('edit_client_phone_2').value = data.phone_2 || '';
                document.getElementById('edit_client_email').value = data.email || '';
                document.getElementById('edit_client_type').value = data.type || 'Contado';

                let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditClientFromReport'));
                modal.show();
            } else {
                if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'No se pudieron obtener los datos del cliente.' });
                else alert('No se pudieron obtener los datos del cliente.');
            }
        })
        .catch(err => {
            if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error al conectar para cargar datos del cliente.' });
            else alert('Error al conectar para cargar datos del cliente.');
        });
    }

    // Submit Edición de Cliente
    let formEditClient = document.getElementById('formEditClientFromReport');
    if (formEditClient) {
        formEditClient.addEventListener('submit', function(e) {
            e.preventDefault();
            let btn = document.getElementById('btnSaveEditClient');
            let originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            let clientId = document.getElementById('edit_client_id').value;
            let formData = new FormData(this);
            formData.append('_method', 'PATCH');

            fetch(`{{ url('clients') }}/${clientId}`, {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    let modal = bootstrap.Modal.getInstance(document.getElementById('modalEditClientFromReport'));
                    if (modal) modal.hide();
                    if (typeof ToastMessage !== 'undefined') {
                        ToastMessage.fire({ text: 'Cliente actualizado correctamente' })
                            .then(() => location.reload());
                    } else {
                        location.reload();
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (typeof ToastError !== 'undefined') ToastError.fire({ text: data.error || 'Error al actualizar' });
                    else alert(data.error || 'Ocurrió un error al actualizar el cliente.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error al procesar la solicitud' });
                else alert('Error al procesar la actualización del cliente.');
            });
        });
    }

    // Eliminar / Depurar Cliente
    function deleteClientRecord(clientId, clientName) {
        const executeDelete = () => {
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
                    if (typeof ToastMessage !== 'undefined') {
                        ToastMessage.fire({ text: 'Cliente eliminado correctamente' })
                            .then(() => location.reload());
                    } else {
                        location.reload();
                    }
                } else {
                    if (typeof ToastError !== 'undefined') {
                        ToastError.fire({ text: data.error || 'No se pudo eliminar el cliente. Verifique que no tenga ventas vinculadas.' });
                    } else {
                        alert(data.error || 'No se pudo eliminar el cliente. Verifique que no tenga ventas vinculadas.');
                    }
                }
            })
            .catch(err => {
                if (typeof ToastError !== 'undefined') ToastError.fire({ text: 'Error de conexión al eliminar cliente.' });
                else alert('Error de conexión al intentar eliminar el cliente.');
            });
        };

        if (typeof ToastConfirm !== 'undefined') {
            ToastConfirm.fire({
                title: '¿Depurar Cliente?',
                text: `¿Está seguro de eliminar o depurar al cliente "${clientName}"?`,
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeDelete();
                }
            });
        } else {
            if (confirm(`¿Está seguro de eliminar o depurar al cliente "${clientName}"?\n\nEsta acción eliminará el registro del cliente del sistema.`)) {
                executeDelete();
            }
        }
    }
</script>
@endsection
