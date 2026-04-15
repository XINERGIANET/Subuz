@extends('template.app')

@section('title', 'Facturación')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Facturación</li>
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

<div class="card card-filter-container">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Comprobantes Emitidos</h3>
        <a href="{{ route('invoices.pending') }}" class="btn btn-brand">
            <i class="ti ti-plus icon"></i> Emitir Comprobantes Pendientes
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
                <div class="col-lg-2">
                    <label class="form-label">Desde</label>
                    <input type="date" class="form-control" name="start_date" value="{{ request()->start_date }}">
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="type">
                        <option value="">Todos</option>
                        <option value="factura" {{ request()->type == 'factura' ? 'selected' : '' }}>Factura</option>
                        <option value="boleta" {{ request()->type == 'boleta' ? 'selected' : '' }}>Boleta</option>
                    </select>
                </div>
                <div class="col-lg-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
                @if(request()->client_id || request()->start_date || request()->end_date || request()->type)
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
                    <th>Documento</th>
                    <th>Cliente</th>
                    <th>Fecha Emisión</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Estado SUNAT</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td>
                        @php
                            $isFactura = $invoice->document_type == 'factura';
                            $badgeClass = $isFactura ? 'bg-purple-lt' : 'bg-azure-lt';
                            $prefix = $isFactura ? 'FACT' : 'BOL';
                        @endphp
                        <span class="badge {{ $badgeClass }} fw-bold">
                            {{ $prefix }} #{{ $invoice->number }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $invoice->client->name }}</div>
                        <div class="text-muted small">{{ $invoice->client->document }}</div>
                    </td>
                    <td class="text-muted">{{ $invoice->date->format('d/m/Y') }}</td>
                    <td class="text-end fw-bold">S/ {{ number_format($invoice->total, 2) }}</td>
                    <td class="text-center">
                        @if($invoice->electronic_invoice_status == 'SENT')
                            <span class="badge bg-green-lt"><i class="ti ti-check me-1"></i> Aceptado</span>
                        @elseif($invoice->electronic_invoice_status == 'ERROR')
                            <span class="badge bg-red-lt" title="{{ $invoice->electronic_invoice_response['message'] ?? 'Error desconocido' }}">Error SUNAT</span>
                        @else
                            <span class="badge bg-secondary-lt">No emitido</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group">
                            @php
                                $pdfUrl = $invoice->electronic_invoice_pdf_a4_url ?: route('invoices.local_pdf', $invoice);
                            @endphp
                            <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-sm btn-primary" title="Ver PDF">
                                <i class="ti ti-file-text icon-inline me-1"></i> Ver PDF
                            </a>
                            
                            @if($invoice->electronic_invoice_status == 'SENT')
                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Opciones</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('invoices.xml', $invoice) }}">
                                        <i class="ti ti-file-code me-2"></i> Descargar XML
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('invoices.cdr', $invoice) }}">
                                        <i class="ti ti-file-check me-2"></i> Descargar CDR
                                    </a>
                                </li>
                            </ul>
                            @endif
                        </div>

                        @if($invoice->electronic_invoice_status != 'SENT')
                        <form action="{{ route('invoices.resend', $invoice) }}" method="POST" class="d-inline resend-form ms-1">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning" title="Reenviar a SUNAT">
                                <i class="ti ti-rotate icon-inline me-1"></i> Reenviar
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="text-muted">No se encontraron comprobantes correspondientes a la búsqueda.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $invoices->appends(request()->query())->links() }}
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
                dropdownParent: 'body',
                render: {
                    no_results: function(data, escape){
                        return '<div class="no-results">No se encontraron resultados</div>';
                    }
                }
            });
        }

        // Confirmación para Reenvío a SUNAT
        const resendForms = document.querySelectorAll('.resend-form');
        resendForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "Se intentará enviar el comprobante a SUNAT nuevamente.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#465fff',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, reenviar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                } else {
                    if (confirm('¿Estás seguro de que deseas reenviar este comprobante a SUNAT?')) {
                        form.submit();
                    }
                }
            });
        });
    });
</script>
@endsection
