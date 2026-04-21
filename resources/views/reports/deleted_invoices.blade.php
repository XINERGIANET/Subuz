@extends('template.app')

@section('title', 'Comprobantes Eliminados')

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reportes</a></li>
            <li class="breadcrumb-item active">Comprobantes Eliminados</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Historial de Comprobantes Anulados</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Cliente</th>
                        <th>Fecha Emisión</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Estado local</th>
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
                                <span class="badge bg-red-lt">
                                    <i class="ti ti-trash me-1"></i> {{ $invoice->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">No se encontraron comprobantes anulados.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection