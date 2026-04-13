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

<div class="row row-cards">
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Seleccionar Cliente para Facturar</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('invoices.pending') }}" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <select class="form-select ts-clients" name="client_id" onchange="this.form.submit()">
                            <option value="">Seleccione un cliente...</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ request()->client_id == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>

        @if($selected_client)
        <form action="{{ route('invoices.store') }}" method="POST">
            @csrf
            <input type="hidden" name="client_id" value="{{ $selected_client->id }}">
            
            <div class="card mb-3 border-primary shadow-sm" style="border-left-width: 5px;">
                <div class="card-header bg-primary-lt">
                    <h3 class="card-title text-primary"><i class="ti ti-file-pencil me-2"></i>Nueva Factura: {{ $selected_client->name }}</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">Número de Factura</label>
                            <input type="text" class="form-control" name="number" placeholder="Ej: F001-00001" required value="{{ old('number') }}">
                            <small class="text-muted fs-6">Formato sugerido: Serie-Número</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Fecha de Emisión</label>
                            <input type="date" class="form-control" name="date" required value="{{ old('date', date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Observaciones</label>
                            <input type="text" class="form-control" name="notes" placeholder="Nota opcional" value="{{ old('notes') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ventas de {{ $selected_client->name }} sin Facturar</h3>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr>
                                <th class="w-1"><input type="checkbox" class="form-check-input" id="check-all"></th>
                                <th>Fecha</th>
                                <th>Pedido</th>
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
                        <i class="ti ti-file-check icon"></i> Generar Factura
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
            if(btnSubmit) btnSubmit.disabled = (count === 0);
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
                render: {
                    no_results: function(data, escape){
                        return '<div class="no-results">No se encontraron resultados</div>';
                    }
                }
            });
        }
    });
</script>
@endsection
