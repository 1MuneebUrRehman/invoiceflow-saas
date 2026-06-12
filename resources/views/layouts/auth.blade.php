<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        {{-- Apply theme before first paint to prevent flash --}}
        <script>
            (function () {
                const t = localStorage.getItem('theme') || 'system';
                const dark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (dark) document.documentElement.classList.add('dark');
            })();
        </script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-12">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-48 left-1/2 size-[40rem] -translate-x-1/2 rounded-full bg-lapis/10 blur-3xl dark:bg-lapis/6"></div>
                <div class="absolute -right-40 -bottom-56 size-[30rem] rounded-full bg-verdant/10 blur-3xl dark:bg-verdant/6"></div>
            </div>

            <a
                href="{{ route('home') }}"
                class="flex items-center gap-2.5 rounded-field focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-lapis"
            >
                <x-ui.logo-mark class="size-9" />
                <span class="font-display text-2xl font-semibold tracking-tight text-midnight">InvoiceFlow</span>
            </a>

            <main class="mt-8 w-full max-w-md">
                {{ $slot }}
            </main>

            <p class="mt-8 text-xs text-slate/70">&copy; {{ date('Y') }} InvoiceFlow. Invoicing that gets you paid.</p>
        </div>
    </body>
</html>
