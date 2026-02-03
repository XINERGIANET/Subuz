<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}" />
	<title>Subuz</title>
	<link rel="stylesheet" href="{{ asset('assets/css/tabler.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/tabler-vendors.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/tabler-icons.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
	<link rel="icon" href="{{ asset('assets/images/xinergia-icon.svg') }}">
	<style>
		.watermark {
			position: fixed; width: 150px; bottom: 20px; right: 20px
		}
	</style>
</head>
<body class="d-flex flex-column bg-white">
	<div class="row g-0 flex-fill">
	  <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
	    <div class="container container-tight my-5 px-lg-5">
	      <div class="text-center mb-4">
	        <a href="#" class="navbar-brand navbar-brand-autodark"><img src="{{ asset('assets/images/logo.svg') }}" height="36" alt=""></a>
	      </div>
	      <h2 class="h3 text-center mb-3">
	        Ingresa con tu cuenta
	      </h2>
	      <form action="{{ route('auth.check') }}" method="POST" autocomplete="off">
	      	@csrf
	        <div class="mb-3">
	          <label class="form-label">Usuario</label>
	          <input type="text" name="user" class="form-control @error('user') is-invalid @enderror" placeholder="Tu usuario" value="{{ old('user') }}" autocomplete="off">
	          @error('user')
	          <div class="invalid-feedback">{{ $message }}</div>
	          @enderror
	        </div>
	        <div class="mb-2">
	          <label class="form-label">
	          	Contraseña
	          	<span class="form-label-description">
	          		<a href="#">Olvidé mi contraseña</a>
	          	</span>
	          </label>
	          <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Tu contraseña" autocomplete="off">
	          @error('password')
	          <div class="invalid-feedback">{{ $message }}</div>
	          @enderror
	        </div>
	        <div class="form-footer">
	          <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
	        </div>
	      </form>
	      <div class="text-center text-muted mt-3">
	        Elaborado por Xinergia de <a href="#">Corporacion Xpande</a>
	      </div>
	    </div>
	  </div>
	  <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
	    <div class="bg-cover h-100 min-vh-100" style="background-image: url({{ asset('assets/images/bg-login.jpg') }})"></div>
	  </div>
	</div>
	<img src="{{ asset('assets/images/xinergia-white.png') }}" class="d-none d-lg-block watermark">
	<img src="{{ asset('assets/images/xinergia.png') }}" class="d-lg-none watermark">
	<script src="{{ asset('assets/js/tabler.min.js') }}"></script>
	<script src="{{ asset('assets/js/theme.min.js') }}"></script>
	<script src="{{ asset('assets/js/tom-select.base.min.js') }}"></script>
	<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
	<script>
		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});
	</script>
</body>
</html>