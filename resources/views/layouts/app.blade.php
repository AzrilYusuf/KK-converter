<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KK Converter')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 antialiased h-full">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
                <a href="{{ route('upload.create') }}" class="font-semibold text-gray-800">KK Converter</a>
            </div>
        </header>

        <main class="flex-1">
            @if (session('success'))
                <div class="max-w-7xl mx-auto px-4 pt-4">
                    <div class="rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto px-4 pt-4">
                    <div class="rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
