<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
            <a
                href="{{ route('home') }}"
                class="rounded-field font-display text-2xl font-semibold tracking-tight text-midnight focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-lapis"
            >
                InvoiceFlow
            </a>

            <main class="mt-8 w-full max-w-md">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
