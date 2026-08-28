<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KK Converter')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-50 text-gray-900 antialiased h-full">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-brand-100">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-6">
                <a href="{{ route('upload.create') }}" class="flex items-center gap-2 font-bold text-gray-900 shrink-0">
                    <x-brand-icon />
                    KK Converter
                </a>

                @auth
                    <nav class="hidden sm:flex items-center gap-6 text-sm font-medium">
                        <a href="{{ route('upload.create') }}"
                            class="transition {{ request()->routeIs('upload.create') ? 'text-brand-600' : 'text-gray-600 hover:text-gray-900' }}">
                            Convert
                        </a>
                        <a href="{{ route('keluarga.index') }}"
                            class="transition {{ request()->routeIs('keluarga.*') ? 'text-brand-600' : 'text-gray-600 hover:text-gray-900' }}">
                            Daftar KK
                        </a>
                    </nav>

                    <div class="flex items-center gap-3 shrink-0">
                        <span class="hidden sm:inline text-sm text-gray-500">{{ auth()->user()->email }}</span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="rounded-full border border-brand-300 px-4 py-2 text-sm font-semibold text-brand-600 hover:bg-brand-50 transition">
                                Keluar
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        <main class="flex-1">
            @if (session('success'))
                <div class="max-w-7xl mx-auto px-4 pt-4">
                    <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto px-4 pt-4">
                    <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
