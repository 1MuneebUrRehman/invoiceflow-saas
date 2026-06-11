<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $title ?? config('app.name') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="relative flex min-h-screen flex-col items-center overflow-hidden px-4 py-12">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-48 left-1/2 size-[40rem] -translate-x-1/2 rounded-full bg-lapis/10 blur-3xl"></div>
            </div>

            <main class="w-full max-w-3xl">
                {{ $slot }}
            </main>

            <p class="mt-8 text-xs text-slate/70">
                Powered by <span class="font-medium text-slate">InvoiceFlow</span> — invoicing that gets you paid.
            </p>
        </div>
    </body>
</html>
