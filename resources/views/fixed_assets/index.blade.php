@extends('template.app')

@section('title', 'Activos Fijos')

@section('content')
<nav class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Activos Fijos</li>
  </ol>
</nav>

<div class="row g-4 align-items-center mb-4">
	<div class="col-auto">
		<div class="bg-blue-lt text-blue avatar avatar-md">
			<i class="ti ti-snowflake fs-2"></i>
		</div>
	</div>
	<div class="col">
		<h2 class="page-title fw-bold">
			Gestión de Activos Fijos
		</h2>
		<div class="text-muted small">
			Administración, préstamos y seguimiento de equipos propios
		</div>
	</div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body text-center py-6">
        <div class="mb-4">
            <div class="avatar avatar-xl bg-blue-lt rounded-circle">
                <i class="ti ti-tool text-blue fs-1"></i>
            </div>
        </div>
        <h2 class="h1 fw-bold text-dark mb-3">Módulo en Desarrollo</h2>
        <p class="text-muted fs-4 px-lg-6 mb-5">
            Estamos construyendo esta nueva sección para que puedas administrar de forma profesional el inventario de congeladoras, equipos, sus mantenimientos y el estado de comodato o alquiler con cada cliente.
        </p>
        
        <div class="row g-3 justify-content-center mt-3">
            <div class="col-sm-6 col-md-4">
                <div class="card bg-light-lt border-0 rounded-3 p-3">
                    <i class="ti ti-list-details fs-2 text-primary mb-2"></i>
                    <h4 class="fw-bold mb-1">Catálogo</h4>
                    <p class="small text-muted mb-0">Control de equipos por número de serie</p>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card bg-light-lt border-0 rounded-3 p-3">
                    <i class="ti ti-users fs-2 text-success mb-2"></i>
                    <h4 class="fw-bold mb-1">Préstamos</h4>
                    <p class="small text-muted mb-0">Seguimiento de qué cliente tiene qué equipo</p>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card bg-light-lt border-0 rounded-3 p-3">
                    <i class="ti ti-cash fs-2 text-warning mb-2"></i>
                    <h4 class="fw-bold mb-1">Rentabilidad</h4>
                    <p class="small text-muted mb-0">Control de gastos de compra y cobros</p>
                </div>
            </div>
        </div>
        
        <div class="mt-5">
            <a href="{{ url('/') }}" class="btn btn-brand btn-pill px-4">
                <i class="ti ti-arrow-left icon me-2"></i> Volver al Inicio
            </a>
        </div>
    </div>
</div>
@endsection
