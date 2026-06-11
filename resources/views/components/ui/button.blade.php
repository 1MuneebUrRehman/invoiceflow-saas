<button {{ $attributes->merge(['class' => 'inline-flex w-full items-center justify-center gap-2 rounded-field bg-lapis px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
