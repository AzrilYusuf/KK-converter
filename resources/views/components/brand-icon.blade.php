@props(['class' => 'h-5 w-5'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-lg bg-brand-500 text-white p-1.5']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="{{ $class }}">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 2h8l6 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6" />
    </svg>
</span>
