@extends('template.app')

@section('title', 'Historial de Cotizaciones')

@section('content')
<nav class="mb-3 d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Cotizaciones</li>
    </ol>
</nav>

<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-brand-lt py-3">
        <h3 class="card-title text-brand fw-bold mb-0">
            <i class="ti ti-file-text me-2"></i> Historial de Cotizaciones
        </h3>
        <a href="{{ route('quotes.create') }}" class="btn btn-brand">
            <i class="ti ti-plus icon"></i> Nueva Cotización
        </a>
    </div>
    
    <div class="table-responsive">
        <table class="table card-table table-vcenter table-hover">
            <thead class="table-corporate-header">
                <tr>
                    <th>N°</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>RUC/DNI</th>
                    <th>Productos</th>
                    <th class="text-end">Total (S/)</th>
                    <th class="text-center">Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotes as $quote)
                <tr>
                    <td>{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ date('d/m/Y', strtotime($quote->date)) }}</td>
                    <td class="text-truncate" style="max-width: 250px;">{{ $quote->client_name }}</td>
                    <td>{{ $quote->client_ruc }}</td>
                    <td>
                        @php
                            $prods = is_string($quote->products) ? json_decode($quote->products, true) : $quote->products;
                        @endphp
                        @if(is_array($prods))
                            {{ count($prods) }} prod(s)
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-end fw-bold text-success">{{ number_format($quote->total, 2) }}</td>
                    <td class="text-center">
                        @if($quote->status === 'Aprobada')
                            <span class="badge bg-success">Aprobada</span>
                        @else
                            <span class="badge bg-warning">Pendiente</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('quotes.pdf', $quote->id) }}" target="_blank" class="btn btn-icon btn-outline-danger" title="Ver PDF">
                            <i class="ti ti-file-text icon"></i>
                        </a>
                        <a href="{{ route('quotes.edit', $quote->id) }}" class="btn btn-icon btn-outline-primary ms-1" title="Editar">
                            <i class="ti ti-edit icon"></i>
                        </a>
                        <button class="btn btn-icon btn-outline-secondary ms-1 btn-delete" data-id="{{ $quote->id }}" title="Eliminar">
                            <i class="ti ti-trash icon"></i>
                        </button>
                        @if($quote->status !== 'Aprobada')
                        <button class="btn btn-icon btn-outline-success ms-1 btn-approve" data-id="{{ $quote->id }}" title="Aprobar y Crear Venta">
                            <i class="ti ti-shopping-cart icon"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No hay cotizaciones registradas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($quotes->hasPages())
    <div class="card-footer">
        {{ $quotes->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    $('.btn-delete').click(function() {
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar Cotización?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'text-white',
                cancelButton: 'text-white'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/quotes/' + id,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if(data.status) {
                            window.location.reload();
                        }
                    },
                    error: function(err) {
                        ToastError.fire({ text: 'Ocurrió un error al eliminar la cotización' });
                    }
                });
            }
        });
    });
    $('.btn-approve').click(function() {
        let btn = $(this);
        let id = btn.data('id');
        let originalIcon = btn.html();
        
        Swal.fire({
            title: '¿Aprobar Cotización?',
            text: "Esta acción cargará los productos en una nueva Venta y borrará tu carrito actual.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, aprobar y vender',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'text-white',
                cancelButton: 'text-white'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>').prop('disabled', true);
                
                $.ajax({
                    url: '/quotes/' + id + '/approve',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if(data.status) {
                            window.location.href = data.url;
                        }
                    },
                    error: function(err) {
                        btn.html(originalIcon).prop('disabled', false);
                        ToastError.fire({ text: 'Ocurrió un error al aprobar la cotización' });
                    }
                });
            }
        });
    });
</script>
@endsection
