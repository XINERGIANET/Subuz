<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}" />
	<title>Subuz</title>
	<link rel="icon" href="{{ asset('assets/images/xinergia-icon.svg') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/tabler.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/tabler-vendors.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/tabler-icons.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/sweetalert2-theme-material-ui.css') }}">
	@yield('styles')
</head>
<body>
	@php
		$isDespachador = auth()->user()->hasRole('despachador');
		$roleLabel = [
			'admin' => 'Administrador',
			'seller' => 'Vendedor',
			'viewer' => 'Visualizador',
			'despachador' => 'Despachador'
		][auth()->user()->role] ?? auth()->user()->role;
	@endphp
	<div class="page">
		<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark border-end-0 shadow" style="background-color: var(--brand-color) !important;">
			<div class="container-fluid">
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<h1 class="navbar-brand navbar-brand-autodark">
					<a href=".">
						<img src="{{ asset('assets/images/logo.svg') }}" alt="Subuz" class="navbar-brand-image">
					</a>
				</h1>
				<div class="navbar-nav flex-row d-lg-none">
					<div class="nav-item dropdown">
						<a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
							<span class="avatar avatar-sm text-white">
								{{-- <i class="ti ti-user icon"></i> --}}
								<img src="{{ asset('assets/images/avatar.webp') }}">
							</span>
							<div class="d-none d-xl-block ps-2">
								<div>{{ auth()->user()->name }}</div>
								<div class="mt-1 small text-muted">{{ $roleLabel }}</div>
							</div>
						</a>
						<div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
							<a href="{{ route('settings.index') }}" class="dropdown-item">Ajustes</a>
							<form method="POST" action="{{ route('auth.logout') }}">
								@csrf
								<a href="javascript:void(0)" class="dropdown-item" onclick="this.closest('form').submit()">Cerrar sesión</a>
							</form>
						</div>
					</div>
				</div>
				<div class="collapse navbar-collapse" id="sidebar-menu">
					<!-- Header for mobile view with close button -->
					<div class="mobile-menu-header">
						<img src="{{ asset('assets/images/logo.svg') }}" alt="Subuz" class="mobile-logo">
						<button class="btn-close-mobile" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-label="Close menu">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<ul class="navbar-nav pt-lg-3">
						@if($isDespachador)
						<li class="nav-item">
							<a class="nav-link" href="{{ route('sales.index') }}" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-shopping-cart icon"></i>
								</span>
								<span class="nav-link-title">
									Ventas
								</span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="{{ route('cashbox.index') }}" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-cash icon"></i>
								</span>
								<span class="nav-link-title">
									Caja
								</span>
							</a>
						</li>
						@else
						<li class="nav-item {{ request()->is('/') ? 'active' : '' }}">
							<a class="nav-link fw-bold" href="{{ url('/') }}" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-dashboard icon"></i>
								</span>
								<span class="nav-link-title">
									Panel de Control
								</span>
							</a>
						</li>
						@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller'))
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#navbar-register" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-edit icon"></i>
								</span>
								<span class="nav-link-title">
									Registro
								</span>
							</a>
							<div class="dropdown-menu">
								<div class="dropdown-menu-columns">
									<div class="dropdown-menu-column">
										@if(auth()->user()->hasRole('admin'))
										<a class="dropdown-item" href="{{ route('products.index') }}">
											<i class="ti ti-package icon me-2"></i> Productos
										</a>
										@endif
										<a class="dropdown-item" href="{{ route('clients.index') }}">
											<i class="ti ti-users icon me-2"></i> Clientes
										</a>
										@if(auth()->user()->hasRole('admin'))
										<a class="dropdown-item" href="{{ route('prices.index') }}">
											<i class="ti ti-tag icon me-2"></i> Precios especiales
										</a>
										<a class="dropdown-item" href="{{ route('users.dispatchers.index') }}">
											<i class="ti ti-truck-delivery icon me-2"></i> Despachadores
										</a>
										<a class="dropdown-item" href="{{ route('payment_methods.index') }}">
											<i class="ti ti-credit-card icon me-2"></i> Cuentas
										</a>
										@endif
									</div>
								</div>
							</div>
						</li>
						@endif
						<li class="nav-item">
							<a class="nav-link" href="{{ route('sales.index') }}" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-shopping-cart icon"></i>
								</span>
								<span class="nav-link-title">
									Ventas
								</span>
							</a>
						</li>
						@if(auth()->user()->hasRole('admin'))
						<li class="nav-item">
							<a class="nav-link" href="{{ route('cashbox.index') }}" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-cash icon"></i>
								</span>
								<span class="nav-link-title">
									Caja
								</span>
							</a>
						</li>
						@endif
						@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('viewer'))
						<li class="nav-item">
							<a class="nav-link" href="{{ route('expenses.index') }}" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-truck-loading icon"></i>
								</span>
								<span class="nav-link-title">
									Gastos
								</span>
							</a>
						</li>
						@endif
						@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller') || auth()->user()->hasRole('viewer'))
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#navbar-reports" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-printer icon"></i>
								</span>
								<span class="nav-link-title">
									Reportes
								</span>
							</a>
							<div class="dropdown-menu">
								<div class="dropdown-menu-columns">
									<div class="dropdown-menu-column">
										<a class="dropdown-item" href="{{ route('reports.liquidation') }}">
											<i class="ti ti-file-text icon me-2"></i> Liquidaci&oacute;n
										</a>
									</div>
								</div>
							</div>
						</li>
						@endif
						@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('viewer'))
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#navbar-charges" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-report-money icon"></i>
								</span>
								<span class="nav-link-title">
									Cobranzas
								</span>
							</a>
							<div class="dropdown-menu">
								<div class="dropdown-menu-columns">
									<div class="dropdown-menu-column">
										<a class="dropdown-item" href="{{ route('charges.credit') }}">
											<i class="ti ti-credit-card icon me-2"></i> Cr&eacute;dito
										</a>
										<a class="dropdown-item" href="{{ route('charges.pending') }}">
											<i class="ti ti-clock icon me-2"></i> Pendiente de pago
										</a>
										<a class="dropdown-item" href="{{ route('charges.history') }}">
											<i class="ti ti-history icon me-2"></i> Historial
										</a>
									</div>
								</div>
							</div>
						</li>
						@endif
						<li class="nav-item">
							<a class="nav-link" href="{{ url('/') }}" >
								<span class="nav-link-icon d-md-none d-lg-inline-block">
									<i class="ti ti-help icon"></i>
								</span>
								<span class="nav-link-title">
									Ayuda
								</span>
							</a>
						</li>
						@endif
					</ul>
				</div>
			</div>
		</aside>
		<header class="navbar navbar-expand-md d-none d-lg-flex d-print-none" >
			<div class="container-fluid">
                <button type="button" class="btn btn-icon btn-ghost-secondary me-3" id="sidebar-toggle" title="Contraer/Expandir menú">
                    <i class="ti ti-menu-2 icon"></i>
                </button>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="navbar-nav flex-row order-md-last">
					<div class="d-none d-md-flex">
						<a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Activar modo oscuro" data-bs-toggle="tooltip"
						data-bs-placement="bottom">
						<i class="ti ti-moon icon"></i>
					</a>
					<a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Activar modo claro" data-bs-toggle="tooltip"
					data-bs-placement="bottom">
					<i class="ti ti-sun icon"></i>
				</a>
			</div>
			<div class="nav-item dropdown">
				<a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
					<span class="avatar avatar-sm">
						{{-- <i class="ti ti-user icon"></i> --}}
						<img src="{{ asset('assets/images/avatar.webp') }}">
					</span>
					<div class="d-none d-xl-block ps-2">
						<div>{{ auth()->user()->name }}</div>
						<div class="mt-1 small text-muted">{{ $roleLabel }}</div>
					</div>
				</a>
				<div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
					<a href="{{ route('settings.index') }}" class="dropdown-item">Ajustes</a>
					<form method="POST" action="{{ route('auth.logout') }}">
						@csrf
						<a href="javascript:void(0)" class="dropdown-item" onclick="this.closest('form').submit()">Cerrar sesión</a>
					</form>
				</div>
			</div>
		</div>
		<div class="collapse navbar-collapse" id="navbar-menu">
		    {{-- <div>
		      <form action="" method="get" autocomplete="off" novalidate>
		        <div class="input-icon">
		          <span class="input-icon-addon">
		            <i class="ti ti-search icon"></i>
		          </span>
		          <input type="text" value="" class="form-control" placeholder="Buscar" aria-label="Search in website">
		        </div>
		      </form>
		  </div> --}}
		</div>
	</div>
</header>
<div class="page-wrapper">
	<!-- Page header -->
	<div class="page-header d-print-none mb-0">
		<div class="container-xl">
			<div class="row g-2 align-items-center">
				<div class="col">
					<div class="page-pretitle text-uppercase fw-bold text-muted small">
						Sistema de Gestión
					</div>
					<h2 class="page-title fw-bold">
						@yield('title')
					</h2>
				</div>
			</div>
		</div>
	</div>
	<!-- Page body -->
	<div class="page-body">
		<div class="container-xl">
			@if(session()->has('message'))
			<div class="alert alert-success">
				{{ session()->get('message') }}
			</div>
			@endif
			@if(session()->has('error'))
			<div class="alert alert-danger">
				{{ session()->get('error') }}
			</div>
			@endif
			@yield('content')
		</div>
	</div>
	<footer class="footer footer-transparent d-print-none mt-auto">
		<div class="container-xl">
			<div class="text-center">
				<span class="text-muted small">&copy; {{ date('Y') }} <span class="text-main fw-bold">xinergia</span>. Todos los derechos reservados.</span>
			</div>
		</div>
	</footer>
</div>
</div>

<script src="{{ asset('assets/js/tabler.min.js') }}"></script>
<script src="{{ asset('assets/js/theme.min.js') }}"></script>
<script src="{{ asset('assets/js/tom-select.base.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
<script>
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});

	const ToastError = Swal.mixin({
		title: 'Error',
		icon: 'error',
		toast: true,
		position: 'bottom-end',
		timer: 2000,
		timerProgressBar: true
	});

	const ToastMessage = Swal.mixin({
		title: 'Mensaje',
		icon: 'success',
		toast: true,
		position: 'bottom-end',
		timer: 2000,
		timerProgressBar: true
	});

	const ToastConfirm = Swal.mixin({
		icon: 'question',
		showDenyButton: true,
		confirmButtonText: 'Aceptar',
		denyButtonText: 'Cancelar',
		toast: true,
		position: 'bottom-end'
	});
</script>
@yield('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebar-toggle');
            const body = document.body;
            const sidebar = document.querySelector('.navbar-vertical');
            
            if (toggleBtn) {
                // Load state
                if (localStorage.getItem('sidebar-mini') === 'true' || localStorage.getItem('sidebar-mini') === null) {
                    body.classList.add('sidebar-mini');
                    localStorage.setItem('sidebar-mini', 'true');
                }
                
                toggleBtn.addEventListener('click', function() {
                    body.classList.toggle('sidebar-mini');
                    localStorage.setItem('sidebar-mini', body.classList.contains('sidebar-mini'));
                });
            }

            // Sticky Hover logic: Open on hover and STAY open
            if (sidebar) {
                sidebar.addEventListener('mouseenter', function() {
                    if (body.classList.contains('sidebar-mini')) {
                        body.classList.remove('sidebar-mini');
                        localStorage.setItem('sidebar-mini', 'false');
                    }
                });
            }
        });
    </script>
</body>
</html>


