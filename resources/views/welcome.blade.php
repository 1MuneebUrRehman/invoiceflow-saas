<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }} — Invoicing that gets you paid</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="flex min-h-screen flex-col">
            <header class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
                <span class="font-display text-xl font-semibold tracking-tight">InvoiceFlow</span>

                <nav class="flex items-center gap-2">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="rounded-field px-4 py-2 text-sm font-medium text-midnight transition hover:text-lapis focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="rounded-field px-4 py-2 text-sm font-medium text-midnight transition hover:text-lapis focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                        >
                            Sign in
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="rounded-field bg-lapis px-4 py-2 text-sm font-semibold text-white transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                        >
                            Get started
                        </a>
                    @endauth
                </nav>
            </header>

            <main class="flex flex-1 items-center">
                <div class="mx-auto w-full max-w-6xl px-4 py-20 sm:px-6">
                    <div class="max-w-2xl">
                        <h1 class="font-display text-4xl font-semibold tracking-tight text-midnight sm:text-5xl">
                            Invoicing that gets you paid.
                        </h1>
                        <p class="mt-4 max-w-xl text-lg text-slate">
                            InvoiceFlow keeps your clients, invoices, and payments in one place — and chases late
                            payments for you, so you can get back to the work that matters.
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <a
                                href="{{ route('register') }}"
                                class="rounded-field bg-lapis px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                            >
                                Start invoicing free
                            </a>
                            <a
                                href="{{ route('login') }}"
                                class="rounded-field px-5 py-2.5 text-sm font-medium text-midnight transition hover:text-lapis focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                            >
                                Sign in
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
