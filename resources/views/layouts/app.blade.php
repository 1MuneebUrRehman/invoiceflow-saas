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
            <header class="sticky top-0 z-40 border-b border-midnight/5 bg-white/85 backdrop-blur">
                <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
                    <div class="flex items-center gap-4">
                        <a
                            href="{{ route('dashboard') }}"
                            class="flex items-center gap-2.5 rounded-field font-display text-xl font-semibold tracking-tight text-midnight focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-lapis"
                        >
                            <x-ui.logo-mark class="size-8" />
                            InvoiceFlow
                        </a>

                        @if (auth()->user()?->tenant)
                            <span class="hidden rounded-full bg-mist px-3 py-1 text-xs font-medium text-slate ring-1 ring-midnight/5 sm:inline-block">
                                {{ auth()->user()->tenant->name }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 items-center justify-center rounded-full bg-lapis/10 text-xs font-semibold text-lapis">
                                {{ collect(explode(' ', auth()->user()?->name ?? ''))->filter()->take(2)->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))->implode('') }}
                            </span>
                            <span class="hidden text-sm font-medium text-midnight sm:inline">{{ auth()->user()?->name }}</span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-field px-3 py-1.5 text-sm font-medium text-slate transition hover:bg-mist hover:text-midnight focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
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
