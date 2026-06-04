@extends('template.app')

@section('title', 'Reporte de Bidones')

@section('content')
<nav class="mb-2">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Ventas</a></li>
        <li class="breadcrumb-item active">Reporte de Bidones</li>
    </ol>
</nav>

<div class="row align-items-center mb-3">
    <div class="col">
        <h2 class="page-title">
            Control de Bidones Prestados
        </h2>
    </div>
    <div class="col-auto ms-auto d-print-none">
        <form action="{{ route('sales.jerry_can_report_view') }}" method="GET" class="d-flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar cliente o DNI/RUC...">
            <button type="submit" class="btn btn-primary"><i class="ti ti-search icon"></i> Buscar</button>
            @if(request('search'))
            <a href="{{ route('sales.jerry_can_report_view') }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Limpiar"><i class="ti ti-x icon"></i></a>
            @endif
        </form>
    </div>
    <div class="col-auto">
        <div class="btn-list">
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left icon"></i> Volver a Ventas
            </a>
            <a href="{{ route('sales.jerry_can_report_pdf') }}" target="_blank" class="btn btn-outline-danger">
                <i class="ti ti-file-type-pdf icon"></i> Exportar a PDF
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped">
                <thead>
                    <tr>
                        <th class="w-1">N°</th>
                        <th>Cliente</th>
                        <th class="text-center">Prestados</th>
                        <th class="text-center">Devueltos</th>
                        <th class="text-center">Saldo Pendiente</th>
                        <th class="text-center w-1">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $i = 1; 
                        $totalBorrowed = 0;
                        $totalReturned = 0;
                        $totalBalance = 0;
                        $hasData = false;
                    @endphp
                    @foreach($grouped as $data)
                        @if($data['balance'] == 0 && $data['borrowed'] == 0 && $data['returned'] == 0)
                            @continue
                        @endif
                        @php
                            $hasData = true;
                            $totalBorrowed += $data['borrowed'];
                            $totalReturned += $data['returned'];
                            $totalBalance += $data['balance'];
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $i++ }}</td>
                            <td class="fw-bold">{{ $data['name'] }}</td>
                            <td class="text-center">{{ $data['borrowed'] }}</td>
                            <td class="text-center">{{ $data['returned'] }}</td>
                            <td class="text-center fw-bold text-{{ $data['balance'] > 0 ? 'danger' : ($data['balance'] < 0 ? 'warning' : 'success') }}">
                                {{ $data['balance'] }}
                            </td>
                            <td class="text-center">
                                @if($data['balance'] > 0 && $data['id'] > 0)
                                <button type="button" class="btn btn-sm btn-outline-success btn-return-jerry-cans" 
                                    data-id="{{ $data['id'] }}" 
                                    data-name="{{ $data['name'] }}" 
                                    data-balance="{{ $data['balance'] }}">
                                    Devolver
                                </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if(!$hasData)
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No hay registros de préstamos de bidones.</td>
                        </tr>
                    @endif
                </tbody>
                @if($hasData)
                <tfoot>
                    <tr class="fw-bold bg-light">
                        <td colspan="2" class="text-end">TOTALES:</td>
                        <td class="text-center">{{ $totalBorrowed }}</td>
                        <td class="text-center">{{ $totalReturned }}</td>
                        <td class="text-center text-{{ $totalBalance > 0 ? 'danger' : 'success' }}">{{ $totalBalance }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modal-return-jerry-cans" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <form action="{{ route('sales.jerry_can_return') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="client_id" id="return_client_id">
            <div class="modal-header">
                <h5 class="modal-title">Devolver Bidones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Cliente: <strong id="return_client_name"></strong></p>
                <div class="mb-3">
                    <label class="form-label">Cantidad a devolver</label>
                    <input type="number" class="form-control" name="quantity" id="return_quantity" min="1" required>
                    <small class="form-hint text-danger">Saldo actual: <span id="return_client_balance"></span> pendientes.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Registrar Devolución</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-return-jerry-cans').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('return_client_id').value = this.dataset.id;
                document.getElementById('return_client_name').textContent = this.dataset.name;
                document.getElementById('return_client_balance').textContent = this.dataset.balance;
                document.getElementById('return_quantity').max = this.dataset.balance;
                document.getElementById('return_quantity').value = this.dataset.balance;
                var modal = new bootstrap.Modal(document.getElementById('modal-return-jerry-cans'));
                modal.show();
            });
        });
    });
</script>
@endsection
