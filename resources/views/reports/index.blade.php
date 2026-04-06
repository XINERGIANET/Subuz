@extends('template.app')

@section('title', 'Reportes')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Reportes</li>
  </ol>
</nav>

<div class="row mt-4">
    <div class="col-md-4">
        <a href="{{ route('reports.liquidation') }}" class="card border-0 shadow-sm text-decoration-none transition-hover">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-file-invoice-dollar fa-3x text-primary"></i>
                </div>
                <h5 class="card-title text-dark">Liquidación de Clientes</h5>
                <p class="card-text text-muted small">Reporte detallado de compras por cliente.</p>
            </div>
        </a>
    </div>
    <div class="col-md-4 mt-3 mt-md-0">
        <a href="{{ route('reports.cashbox') }}" class="card border-0 shadow-sm text-decoration-none transition-hover">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-cash-register fa-3x text-success"></i>
                </div>
                <h5 class="card-title text-dark">Cierres de Caja</h5>
                <p class="card-text text-muted small">Filtrar y visualizar movimientos por cierre diario.</p>
            </div>
        </a>
    </div>
</div>

<style>
    .transition-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .transition-hover {
        transition: all .3s ease;
    }
</style>
@endsection