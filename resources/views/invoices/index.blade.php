@extends('template.app')

@section('title', 'Facturación')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Facturación</li>
  </ol>
</nav>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Ventas Facturadas</h3>
        <a href="{{ route('invoices.pending') }}" class="btn btn-brand">
            <i class="ti ti-plus icon"></i> Facturar Ventas Pendientes
        </a>
    </div>
    <div class="card-body border-bottom">
        <form class="mb-0">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label">Filtrar por Cliente</label>
                    <select class="form-select ts-clients" name="client_id">
                        <option value="">Todos los clientes</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request()->client_id == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
                @if(request()->client_id)
                <div class="col-lg-2 d-flex align-items-end">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
                </div>
                @endif
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>Fecha Venta</th>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Factura(s) Asociada(s)</th>
                    <th class="text-end">Total Venta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr>
                    <td class="text-muted">{{ date('d/m/Y', strtotime($sale->date)) }}</td>
                    <td class="fw-bold">{{ $sale->order }}</td>
                    <td>{{ $sale->client->name }}</td>
                    <td>
                        @foreach($sale->invoices as $invoice)
                            <span class="badge bg-blue-lt mb-1">FACT #{{ $invoice->number }}</span>
                        @endforeach
                    </td>
                    <td class="text-end fw-bold">S/ {{ number_format($sale->total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div class="text-muted">No se encontraron ventas facturadas correspondientes a la búsqueda.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($sales->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $sales->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
