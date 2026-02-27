<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Login | Subuz</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100%;
        }

        body {
            margin: 0;
            background: #f2f3f5;
            color: #10284c;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .right-panel {
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            background: radial-gradient(circle at 50% 35%, rgba(23, 45, 110, 0.4), rgba(2, 10, 42, 1) 58%), #020a2a;
            box-shadow: inset 0 6px 0 #020a2a;
        }

        .right-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(132, 153, 203, 0.16) 1px, transparent 1px),
                linear-gradient(90deg, rgba(132, 153, 203, 0.16) 1px, transparent 1px);
            background-size: 64px 64px;
            opacity: .42;
        }

        .input-base {
            width: 100%;
            height: 56px;
            border-radius: 12px;
            border: 1px solid #ccd5e3;
            background: #dfe6f1;
            color: #0f172a;
            padding: 0 18px;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .input-base:focus {
            border-color: #8aa0c6;
            box-shadow: 0 0 0 3px rgba(70, 98, 171, 0.14);
        }

        .auth-layout {
            min-height: 100vh;
        }

        .left-panel {
            min-height: 100vh;
        }

        .right-shell {
            display: none;
        }

        @media (min-width: 1024px) {
            html, body {
                overflow: hidden;
            }

            .auth-layout {
                height: 100vh;
                overflow: hidden;
            }

            .left-panel {
                width: 50%;
                height: 100vh;
            }

            .right-shell {
                display: flex !important;
                position: fixed;
                top: 0;
                right: 0;
                width: 50vw;
                height: 100vh;
                z-index: 5;
            }
        }
    </style>
</head>
<body class="h-full">
    <div class="auth-layout relative">
        <section class="left-panel flex items-center justify-center px-6 py-10 sm:px-10 lg:px-12">
            <div class="w-full max-w-[560px]">
                <div class="mb-10">
                    <h1 class="mb-4 text-5xl font-semibold text-[#10284c]">Login</h1>
                    <p class="text-[18px] text-[#4f6684]">Ingresa tu usuario y contraseña para continuar.</p>
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

                <form method="POST" action="{{ route('auth.check') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="user" class="mb-2 block text-[20px] font-medium text-[#1c355e]">
                            Usuario<span class="text-red-500">*</span>
                        </label>
                        <input
                            id="user"
                            name="user"
                            type="text"
                            value="{{ old('user') }}"
                            required
                            autocomplete="username"
                            class="input-base text-[16px]"
                        >
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-[20px] font-medium text-[#1c355e]">
                            Contraseña<span class="text-red-500">*</span>
                        </label>
                        <div x-data="{ showPassword: false }" class="relative">
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="input-base pr-14 text-[16px]"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-[#7c879d]"
                            >
                                <svg x-show="!showPassword" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 3L21 21" stroke="currentColor" stroke-width="2"/>
                                    <path d="M10.58 10.58C10.21 10.95 10 11.46 10 12C10 13.1 10.9 14 12 14C12.54 14 13.05 13.79 13.42 13.42" stroke="currentColor" stroke-width="2"/>
                                    <path d="M9.88 5.09C10.57 4.8 11.27 4.65 12 4.65C19 4.65 23 12 23 12C22.39 13.15 21.64 14.22 20.79 15.18" stroke="currentColor" stroke-width="2"/>
                                    <path d="M6.61 6.61C3.92 8.43 2.26 10.99 1 12C1 12 5 19.35 12 19.35C13.73 19.35 15.32 18.91 16.74 18.18" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 text-[15px] text-[#243b61]">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="checkbox" class="h-4 w-4 rounded border border-[#b9c3d3] text-blue-700 focus:ring-blue-600">
                            <span>Mantener sesión activa</span>
                        </label>
                        <a href="#" class="text-[#344ad9] hover:underline">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button
                        type="submit"
                        class="h-12 w-full rounded-xl bg-[#3152b6] text-[18px] font-semibold text-white transition hover:bg-[#2946a3]"
                    >
                        Sign In
                    </button>

                    <p class="text-[15px] text-[#22395f]">
                        ¿No tienes cuenta? <a href="#" class="text-[#2348cc] hover:underline">Regístrate</a>
                    </p>
                </form>
            </div>
        </section>

        <aside class="right-shell right-panel items-center justify-center">
            <div class="relative z-10 mx-auto max-w-md px-10 text-center">
                <img src="{{ asset('assets/images/xinergia.png') }}" alt="Xinergia" class="mx-auto mb-8 w-[360px] max-w-full" />
                <p class="text-[18px] leading-snug text-[#c9d5f2]">
                    Lo mejor en soluciones tecnológicas para tu negocio.
                </p>
            </div>
        </aside>
    </div>
</body>
</html>
