@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="rounded-3xl border border-brand-100 bg-white px-8 py-10">
        <div class="mx-auto mb-6 flex flex-col items-center gap-3">
            <x-brand-icon class="h-6 w-6" />
            <h1 class="text-xl font-bold text-gray-900">Masuk ke KK Converter</h1>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                <input type="password" name="password" id="password" required
                    class="w-full rounded-md border-gray-300 border px-3 py-2 text-sm">
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300">
                Ingat saya
            </label>

            <button type="submit"
                class="w-full rounded-full bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition">
                Masuk
            </button>
        </form>
    </div>
</div>
@endsection
