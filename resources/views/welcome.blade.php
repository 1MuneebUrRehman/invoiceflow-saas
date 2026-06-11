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
        <div class="relative flex min-h-screen flex-col overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-48 left-1/2 size-[44rem] -translate-x-1/3 rounded-full bg-lapis/10 blur-3xl"></div>
                <div class="absolute -bottom-56 -left-40 size-[32rem] rounded-full bg-verdant/10 blur-3xl"></div>
            </div>

            <header class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
                <span class="flex items-center gap-2.5 font-display text-xl font-semibold tracking-tight">
                    <x-ui.logo-mark class="size-8" />
                    InvoiceFlow
                </span>

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
                            class="rounded-field bg-lapis px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                        >
                            Get started
                        </a>
                    @endauth
                </nav>
            </header>

            <main class="flex flex-1 items-center">
                <div class="mx-auto w-full max-w-6xl px-4 py-20 sm:px-6">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-medium text-slate ring-1 ring-midnight/10">
                            <span class="size-1.5 rounded-full bg-verdant"></span>
                            Built for freelancers &amp; small teams
                        </span>

                        <h1 class="mt-5 font-display text-4xl font-semibold tracking-tight text-midnight sm:text-6xl">
                            Invoicing that gets you paid.
                        </h1>
                        <p class="mt-5 max-w-xl text-lg text-slate">
                            InvoiceFlow keeps your clients, invoices, and payments in one place — and chases late
                            payments for you, so you can get back to the work that matters.
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center gap-2 rounded-field bg-lapis px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                            >
                                Start invoicing free
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6" />
                                </svg>
                            </a>
                            <a
                                href="{{ route('login') }}"
                                class="rounded-field px-5 py-2.5 text-sm font-medium text-midnight transition hover:text-lapis focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                            >
                                Sign in
                            </a>
                        </div>
                    </div>

                    <div class="mt-20 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
                            <span class="flex size-10 items-center justify-center rounded-field bg-lapis/10 text-lapis">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                                    <circle cx="10" cy="7" r="4" />
                                    <path d="M21 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <h2 class="mt-4 font-display text-lg font-semibold">Clients in one place</h2>
                            <p class="mt-1.5 text-sm text-slate">
                                Every client, contact, and billing detail organized — no more digging through email threads.
                            </p>
                        </div>

                        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
                            <span class="flex size-10 items-center justify-center rounded-field bg-lapis/10 text-lapis">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                    <path d="M14 3v5h5" />
                                    <path d="M9 13h6M9 17h4" />
                                </svg>
                            </span>
                            <h2 class="mt-4 font-display text-lg font-semibold">Professional invoices</h2>
                            <p class="mt-1.5 text-sm text-slate">
                                Send polished invoices in your currency, track what's outstanding, and see status at a glance.
                            </p>
                        </div>

                        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
                            <span class="flex size-10 items-center justify-center rounded-field bg-lapis/10 text-lapis">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="2" y="6" width="20" height="12" rx="2" />
                                    <circle cx="12" cy="12" r="2.5" />
                                    <path d="M6 12h.01M18 12h.01" />
                                </svg>
                            </span>
                            <h2 class="mt-4 font-display text-lg font-semibold">Payments tracked</h2>
                            <p class="mt-1.5 text-sm text-slate">
                                Record payments as they land and let gentle reminders chase the late ones for you.
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
