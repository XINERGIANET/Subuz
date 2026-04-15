@extends('template.app')

@section('title', 'Pendientes de Facturar')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Facturación</a></li>
    <li class="breadcrumb-item active">Ventas Pendientes</li>
  </ol>
</nav>

<style>
	.ts-dropdown {
		z-index: 2000 !important;
	}
	.card-filter-container {
		overflow: visible !important;
	}
</style>

<div class="row row-cards">
    <div class="col-12">
        <div class="card mb-3 card-filter-container">
            <div class="card-header">
                <h3 class="card-title">Seleccionar Cliente para Facturar</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('invoices.pending') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Cliente</label>
                        <select class="ts-clients" name="client_id" onchange="this.form.submit()">
                            <option value="">Seleccione un cliente...</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ request()->client_id == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="type" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="factura" {{ request()->type == 'factura' ? 'selected' : '' }}>Factura</option>
                            <option value="boleta" {{ request()->type == 'boleta' ? 'selected' : '' }}>Boleta</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" class="form-control" name="start_date" value="{{ request()->start_date }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}" onchange="this.form.submit()">
                    </div>
                    @if(request()->client_id || request()->start_date || request()->end_date || request()->type)
                    <div class="col-md-1 d-flex align-items-end">
                        <a href="{{ route('invoices.pending') }}" class="btn btn-outline-secondary w-100 btn-icon" title="Limpiar filtros">
                            <i class="ti ti-filter-off"></i>
                        </a>
                    </div>
                    @endif
                </form>
            </div>
        </div>

        @if($selected_client)
        <form id="invoice-form" action="{{ route('invoices.store') }}" method="POST">
            @csrf
            <input type="hidden" name="client_id" value="{{ $selected_client->id }}">
            
            <div class="card mb-3 border-primary shadow-sm" style="border-left-width: 5px;">
                <div class="card-header bg-primary-lt">
                    <h3 class="card-title text-primary"><i class="ti ti-file-pencil me-2"></i>Nuevo Comprobante: {{ $selected_client->name }}</h3>
                </div>
                <div class="card-body">
                    @php
                        $isRuc = $selected_client && strlen($selected_client->document) === 11;
                        $defaultType = request()->type ?: ($isRuc ? 'factura' : 'boleta');
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Número de Comprobante</label>
                            @php
                                $initialNumber = ($defaultType == 'factura') ? $next_factura : $next_boleta;
                            @endphp
                            <input type="text" id="next-invoice-display" class="form-control bg-light" disabled value="{{ $initialNumber }}">
                            <small class="text-muted fs-6">Auto-generado: <span id="next-invoice-hint" class="fw-bold text-primary">{{ $initialNumber }}</span> (Se asignará al guardar)</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Tipo de Comprobante</label>
                            <select class="form-select" name="document_type" id="document_type_selector" required>
                                <option value="factura" {{ $defaultType == 'factura' ? 'selected' : '' }}>Factura</option>
                                <option value="boleta" {{ $defaultType == 'boleta' ? 'selected' : '' }}>Boleta</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Fecha de Emisión</label>
                            <input type="date" class="form-control" name="date" required value="{{ old('date', date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Observaciones</label>
                            <input type="text" class="form-control" name="notes" placeholder="Nota opcional" value="{{ old('notes') }}">
                        </div>
                        <div class="col-12" id="ruc-warning" style="display: none;">
                            <div class="alert alert-warning mb-0">
                                <i class="ti ti-alert-triangle me-2"></i>
                                <span>Atención: Las Facturas solo pueden emitirse a clientes con RUC (11 dígitos).</span>
                            </div>
                        </div>
                        <div class="col-12" id="dni0-warning" style="display: none;">
                            <div class="alert alert-danger mb-0">
                                <i class="ti ti-alert-circle me-2"></i>
                                <span>No se puede emitir una boleta sin identificación que supere los S/ 700.00.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ventas de {{ $selected_client->name }} sin Comprobante</h3>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr>
                                <th class="w-1"><input type="checkbox" class="form-check-input" id="check-all"></th>
                                <th>Fecha</th>
                                <th>Pedido</th>
                                <th>Comprobante</th>
                                <th>Tipo de Venta</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                            <tr>
                                <td><input type="checkbox" class="form-check-input sale-checkbox" name="sales[]" value="{{ $sale->id }}" data-total="{{ $sale->total }}"></td>
                                <td class="text-muted">{{ date('d/m/Y', strtotime($sale->date)) }}</td>
                                <td class="fw-bold text-primary">{{ $sale->order }}</td>
                                <td>
                                    @php $isFactura = $defaultType == 'factura'; @endphp
                                    <span class="badge {{ $isFactura ? 'bg-purple-lt' : 'bg-azure-lt' }} doc-type-badge">
                                        <i class="ti {{ $isFactura ? 'ti-building' : 'ti-user' }} me-1"></i>
                                        <span class="doc-type-text">{{ $isFactura ? 'Factura' : 'Boleta' }}</span>
                                    </span>
                                </td>
                                <td>
                                    @if($sale->type == 'Credito')
                                        <span class="badge bg-warning-lt">Crédito</span>
                                    @else
                                        <span class="badge bg-green-lt">{{ $sale->type }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">S/ {{ number_format($sale->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="ti ti-check bg-success-lt p-2 rounded-circle mb-2" style="font-size: 2rem;"></i>
                                    <div>No hay ventas pendientes de facturar para este cliente.</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($sales->count() > 0)
                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-blue me-2" id="selected-count">0</span> ventas seleccionadas | 
                        <span class="fw-bold">Total a Facturar: </span> <span class="text-primary fs-3 fw-bold">S/ <span id="selected-total">0.00</span></span>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg" id="btn-submit" disabled>
                        <i class="ti ti-file-check icon"></i> Generar Comprobante
                    </button>
                </div>
                @endif
            </div>
        </form>
        @else
        <div class="empty bg-white p-5 border rounded shadow-sm text-center">
            <div class="empty-icon mb-3">
                <i class="ti ti-users" style="font-size: 4rem; color: #465fff;"></i>
            </div>
            <p class="empty-title fs-2 fw-bold">Panel de Facturación</p>
            <p class="empty-subtitle text-muted mx-auto" style="max-width: 500px;">
                Para comenzar a facturar ventas, por favor seleccione un cliente del menú superior. El sistema listará todos los pedidos entregados que aún no tienen una factura asociada.
            </p>
        </div>
        @endif
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('check-all');
        const checkboxes = document.querySelectorAll('.sale-checkbox');
        const selectedCount = document.getElementById('selected-count');
        const selectedTotal = document.getElementById('selected-total');
        const btnSubmit = document.getElementById('btn-submit');

        const docTypeSelector = document.getElementById('document_type_selector');
        const rucWarning = document.getElementById('ruc-warning');
        const dni0Warning = document.getElementById('dni0-warning');
        const clientDocument = @json($selected_client->document ?? '');
        const nextFactura = @json($next_factura ?? '00000001');
        const nextBoleta = @json($next_boleta ?? '00000001');
        const nextInvoiceDisplay = document.getElementById('next-invoice-display');
        const nextInvoiceHint = document.getElementById('next-invoice-hint');

        function updateTotals() {
            let count = 0;
            let total = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    count++;
                    total += parseFloat(cb.dataset.total);
                }
            });
            if(selectedCount) selectedCount.textContent = count;
            if(selectedTotal) selectedTotal.textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            // Validations
            const type = docTypeSelector ? docTypeSelector.value : '';
            let disabled = (count === 0);

            if (rucWarning) rucWarning.style.display = 'none';
            if (dni0Warning) dni0Warning.style.display = 'none';

            if (type === 'factura' && clientDocument.length !== 11) {
                if (rucWarning) rucWarning.style.display = 'block';
                disabled = true;
            }

            if (type === 'boleta' && (clientDocument === '0' || clientDocument === '' || clientDocument === '00000000') && total > 700) {
                if (dni0Warning) dni0Warning.style.display = 'block';
                disabled = true;
            }

            // Update next number display
            const nextNumber = (type === 'factura') ? nextFactura : nextBoleta;
            if (nextInvoiceDisplay) nextInvoiceDisplay.value = nextNumber;
            if (nextInvoiceHint) nextInvoiceHint.textContent = nextNumber;

            if(btnSubmit) btnSubmit.disabled = disabled;

            // Update badges in the table
            const badges = document.querySelectorAll('.doc-type-badge');
            badges.forEach(badge => {
                const textSpan = badge.querySelector('.doc-type-text');
                const icon = badge.querySelector('i');
                if (type === 'factura') {
                    badge.classList.remove('bg-azure-lt');
                    badge.classList.add('bg-purple-lt');
                    if(textSpan) textSpan.textContent = 'Factura';
                    if(icon) {
                        icon.classList.remove('ti-user');
                        icon.classList.add('ti-building');
                    }
                } else {
                    badge.classList.remove('bg-purple-lt');
                    badge.classList.add('bg-azure-lt');
                    if(textSpan) textSpan.textContent = 'Boleta';
                    if(icon) {
                        icon.classList.remove('ti-building');
                        icon.classList.add('ti-user');
                    }
                }
            });
        }

        if (docTypeSelector) {
            docTypeSelector.addEventListener('change', updateTotals);
        }

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = checkAll.checked);
                updateTotals();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateTotals);
        });

        if (typeof TomSelect !== 'undefined') {
            new TomSelect('.ts-clients', {
                copyClassesToDropdown: false,
                dropdownClass: 'dropdown-menu ts-dropdown',
                optionClass:'dropdown-item',
                controlInput: '<input>',
                dropdownParent: 'body',
                render: {
                    no_results: function(data, escape){
                        return '<div class="no-results">No se encontraron resultados</div>';
                    }
                }
            });
        }

        // AJAX Form Submission
        $('#invoice-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = btnSubmit;
            const originalHtml = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Procesando...';

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.status) {
                        // Open PDF in new tab
                        if (response.pdf_url) {
                            window.open(response.pdf_url, '_blank');
                        }
                        
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message || 'Comprobante generado correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Continuar'
                        }).then(() => {
                            window.location.href = "{{ route('invoices.index') }}";
                        });
                    } else {
                        Swal.fire('Error', response.error || 'Ocurrió un error al procesar la solicitud.', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error de servidor';
                    Swal.fire('Error', error, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            });
        });

        // Initialize UI state
        updateTotals();
    });
</script>
@endsection
