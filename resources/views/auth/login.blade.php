@php $forceLightMode = true; @endphp
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Login | Subuz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="icon" href="{{ asset('assets/images/xinergia-icon.svg') }}">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            300: '#93c5fd',
                            500: '#3b82f6',
                            600: '#2563eb',
                            800: '#1e40af',
                            950: '#0f172a',
                        },
                        'error': { 500: '#ef4444' }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .auth-grid-bg {
            background-image: linear-gradient(rgba(148, 163, 184, 0.12) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.12) 1px, transparent 1px);
            background-size: 48px 48px;
        }
    </style>
</head>
<body class="h-full bg-white text-gray-800">
    <div class="relative flex min-h-screen w-full flex-col justify-center lg:flex-row lg:h-screen">
        <!-- Form -->
        <div class="flex w-full flex-1 flex-col lg:w-1/2">
            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 py-10 sm:p-10">
                <div class="mb-5 sm:mb-8">
                    <h1 class="text-2xl font-semibold text-gray-800 sm:text-3xl mb-2">
                        Login
                    </h1>
                    <p class="text-sm text-gray-500">
                        Ingresa tu usuario y contraseña para continuar.
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('auth.check') }}">
                    @csrf
                    <div class="space-y-5">
                        <div>
                            <label for="user" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Usuario<span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="user" name="user" value="{{ old('user') }}" required autocomplete="username" placeholder="Ingresa tu usuario"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-400 focus:ring-3 focus:ring-blue-400/20 focus:outline-none" />
                        </div>
                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Contraseña<span class="text-red-500">*</span>
                            </label>
                            <div x-data="{ showPassword: false }" class="relative">
                                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                                    placeholder="Ingresa tu contraseña"
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-400 focus:ring-3 focus:ring-blue-400/20 focus:outline-none" />
                                <span @click="showPassword = !showPassword"
                                    class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer text-gray-500">
                                    <svg x-show="!showPassword" class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="#98A2B3" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="#98A2B3" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                Sign In
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right panel: brand -->
        <div class="bg-slate-900 auth-grid-bg relative hidden min-h-screen w-full items-center lg:flex lg:h-full lg:min-h-0 lg:w-1/2">
            <div class="relative z-10 flex w-full items-center justify-center p-10">
                <div class="flex max-w-xs flex-col items-center">
                    <a href="/" class="mb-4 block">
                        <img src="{{ asset('assets/images/logo.svg') }}" alt="Logo" class="max-h-12 w-auto" onerror="this.src='{{ asset('assets/images/xinergia.png') }}'">
                    </a>
                    <p class="text-center text-gray-400">
                        Lo mejor en soluciones tecnológicas para tu negocio.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
