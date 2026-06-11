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
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-slate/15 bg-white">
                <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
                    <div class="flex items-center gap-4">
                        <a
                            href="{{ route('dashboard') }}"
                            class="rounded-field font-display text-xl font-semibold tracking-tight text-midnight focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-lapis"
                        >
                            InvoiceFlow
                        </a>

                        @if (auth()->user()?->tenant)
                            <span class="hidden rounded-full bg-mist px-3 py-1 text-xs font-medium text-slate sm:inline-block">
                                {{ auth()->user()->tenant->name }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate">{{ auth()->user()?->name }}</span>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-field px-3 py-1.5 text-sm font-medium text-slate transition hover:text-midnight focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                            >
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-10 sm:px-6">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
