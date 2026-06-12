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
        <div class="flex min-h-screen flex-col bg-mist">
            <header class="sticky top-0 z-40 border-b border-midnight/5 bg-white/88 backdrop-blur-md">
                <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-6 px-4 sm:px-6">
                    <div class="flex items-center gap-5">
                        <a
                            href="{{ route('dashboard') }}"
                            class="flex shrink-0 items-center gap-2.5 rounded-field font-display text-xl font-semibold tracking-tight text-midnight focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-lapis"
                        >
                            <x-ui.logo-mark class="size-8" />
                            <span class="hidden sm:inline">InvoiceFlow</span>
                        </a>

                        @auth
                            <nav class="hidden items-center gap-0.5 sm:flex">
                                @foreach ([
                                    ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
                                    ['label' => 'Clients',   'route' => 'clients.index',  'match' => 'clients*'],
                                    ['label' => 'Invoices',  'route' => 'invoices.index', 'match' => 'invoices*'],
                                ] as $item)
                                    <a
                                        href="{{ route($item['route']) }}"
                                        @class([
                                            'rounded-field px-3.5 py-2 text-sm font-medium transition',
                                            'bg-lapis/8 text-midnight'                     => request()->routeIs($item['match']),
                                            'text-slate hover:bg-mist hover:text-midnight' => !request()->routeIs($item['match']),
                                        ])
                                    >
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </nav>
                        @endauth
                    </div>

                    <div class="flex items-center gap-3">
                        @auth
                            @if (auth()->user()?->tenant)
                                <span class="hidden rounded-full bg-mist px-3 py-1 text-xs font-medium text-slate ring-1 ring-midnight/5 lg:inline-block">
                                    {{ auth()->user()->tenant->name }}
                                </span>
                            @endif

                            <div class="flex items-center gap-2.5">
                                <span class="flex size-8 items-center justify-center rounded-full bg-lapis/10 text-xs font-semibold text-lapis">
                                    {{ Str::upper(auth()->user()?->initials() ?? '') }}
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
                        @endauth
                    </div>
                </div>

                {{-- Mobile nav --}}
                @auth
                    <div class="border-t border-midnight/5 sm:hidden">
                        <div class="flex gap-0.5 px-4 py-2">
                            @foreach ([
                                ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
                                ['label' => 'Clients',   'route' => 'clients.index',  'match' => 'clients*'],
                                ['label' => 'Invoices',  'route' => 'invoices.index', 'match' => 'invoices*'],
                            ] as $item)
                                <a
                                    href="{{ route($item['route']) }}"
                                    @class([
                                        'rounded-field px-3 py-1.5 text-sm font-medium transition',
                                        'bg-lapis/8 text-midnight'                     => request()->routeIs($item['match']),
                                        'text-slate hover:bg-mist hover:text-midnight' => !request()->routeIs($item['match']),
                                    ])
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endauth
            </header>

            @if (session('success'))
                <div class="mx-auto w-full max-w-6xl px-4 pt-5 sm:px-6">
                    <div class="rounded-field bg-verdant/10 px-4 py-3 text-sm font-medium text-verdant ring-1 ring-verdant/20">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            {{-- Toast notification (dispatched from Livewire) --}}
            <div
                x-data="{ show: false, message: '', type: 'success' }"
                x-on:notify.window="message = $event.detail.message; type = $event.detail.type ?? 'success'; show = true; setTimeout(() => show = false, 4000)"
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="fixed bottom-5 right-5 z-50 flex items-center gap-3 rounded-card bg-white px-4 py-3 shadow-pop ring-1 ring-midnight/10"
                style="display: none"
            >
                <span
                    :class="{
                        'size-2 rounded-full shrink-0': true,
                        'bg-verdant': type === 'success',
                        'bg-garnet': type === 'error',
                        'bg-lapis': type === 'info',
                    }"
                ></span>
                <span x-text="message" class="text-sm font-medium text-midnight"></span>
            </div>

            <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-10 sm:px-6">
                {{ $slot }}
            </main>
        </div>

        {{-- Confirmation Modal --}}
        <div
            x-data="{
                show: false,
                title: 'Confirm',
                message: '',
                confirmLabel: 'Confirm',
                variant: 'danger',
                onConfirm: null,
                open(detail) {
                    this.title = detail.title ?? 'Confirm';
                    this.message = detail.message;
                    this.confirmLabel = detail.confirmLabel ?? 'Confirm';
                    this.variant = detail.variant ?? 'danger';
                    this.onConfirm = detail.onConfirm ?? null;
                    this.show = true;
                },
                async confirm() {
                    if (this.onConfirm) { await this.onConfirm(); }
                    this.show = false;
                }
            }"
            x-on:confirm-action.window="open($event.detail)"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
            style="display: none"
        >
            <div x-on:click="show = false" class="fixed inset-0 bg-midnight/40 backdrop-blur-sm"></div>

            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative z-10 w-full max-w-md rounded-card bg-white p-6 shadow-pop ring-1 ring-midnight/8 sm:mx-4"
            >
                <div class="flex items-start gap-4">
                    <div
                        :class="variant === 'danger' ? 'bg-garnet/10 text-garnet' : 'bg-lapis/10 text-lapis'"
                        class="flex size-10 shrink-0 items-center justify-center rounded-full"
                    >
                        <template x-if="variant === 'danger'">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </template>
                        <template x-if="variant !== 'danger'">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3 x-text="title" class="font-display text-base font-semibold text-midnight"></h3>
                        <p x-text="message" class="mt-1.5 text-sm leading-relaxed text-slate"></p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        x-on:click="show = false"
                        class="rounded-field px-4 py-2 text-sm font-medium text-slate ring-1 ring-midnight/15 transition hover:bg-mist hover:text-midnight"
                    >
                        Cancel
                    </button>
                    <button
                        x-on:click="confirm()"
                        :class="variant === 'danger' ? 'bg-garnet hover:bg-garnet/90' : 'bg-lapis hover:bg-lapis-deep'"
                        class="rounded-field px-4 py-2 text-sm font-semibold text-white shadow-sm transition focus-visible:outline-2 focus-visible:outline-offset-2"
                        x-text="confirmLabel"
                    ></button>
                </div>
            </div>
        </div>
    </body>
</html>
