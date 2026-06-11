<?php

use App\Models\Client;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Clients')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showArchived = false;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingShowArchived(): void { $this->resetPage(); }

    #[Computed]
    public function clients()
    {
        return Client::query()
            ->when($this->showArchived, fn ($q) => $q->withTrashed())
            ->when($this->search, fn ($q) => $q->where(
                fn ($q2) => $q2
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('company_name', 'like', "%{$this->search}%")
            ))
            ->withCount('invoices')
            ->orderBy('name')
            ->paginate(20);
    }

    public function delete(int $id): void
    {
        Client::findOrFail($id)->delete();
        unset($this->clients);
        $this->dispatch('notify', message: 'Client archived.', type: 'success');
    }

    public function restore(int $id): void
    {
        Client::withTrashed()->findOrFail($id)->restore();
        unset($this->clients);
        $this->dispatch('notify', message: 'Client restored.', type: 'success');
    }
};
?>

<div class="relative">
    <div aria-hidden="true" class="pointer-events-none absolute -inset-x-4 top-0 -z-10">
        <div class="absolute -top-24 left-1/2 size-[30rem] -translate-x-1/3 rounded-full bg-lapis/6 blur-3xl"></div>
    </div>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-midnight">Clients</h1>
            <p class="mt-1 text-sm text-slate">Manage your client contacts and billing details.</p>
        </div>
        <a
            href="{{ route('clients.create') }}"
            class="rounded-field bg-lapis px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
        >
            + New client
        </a>
    </div>

    <div class="mt-6 rounded-card bg-white shadow-card ring-1 ring-midnight/5 overflow-hidden">
        {{-- Search toolbar --}}
        <div class="flex items-center gap-3 border-b border-midnight/6 px-5 py-3.5">
            <div class="relative flex-1 max-w-sm">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search clients…"
                    class="w-full rounded-field border-0 bg-mist py-2 pl-9 pr-4 text-sm text-midnight placeholder-slate ring-1 ring-midnight/10 focus:ring-2 focus:ring-lapis/40 focus:outline-none"
                >
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate">
                <input wire:model.live="showArchived" type="checkbox" class="rounded border-slate/30 text-lapis focus:ring-lapis/40">
                Show archived
            </label>
            <span class="hidden text-sm text-slate sm:inline">{{ $this->clients->total() }} {{ Str::plural('client', $this->clients->total()) }}</span>
        </div>

        @if ($this->clients->isEmpty())
            <div class="px-5 py-16 text-center">
                <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-lapis/10 text-lapis">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M21 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                @if ($search)
                    <p class="mt-4 text-sm font-medium text-midnight">No clients match "{{ $search }}"</p>
                    <p class="mt-1 text-sm text-slate">Try a different name or email.</p>
                @else
                    <p class="mt-4 font-display text-lg font-semibold text-midnight">No clients yet</p>
                    <p class="mt-1 text-sm text-slate">Add your first client to get started.</p>
                    <a href="{{ route('clients.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-field bg-lapis px-4 py-2 text-sm font-semibold text-white transition hover:bg-lapis-deep">
                        Add first client
                    </a>
                @endif
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-midnight/6">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate">Name</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate">Email</th>
                        <th class="hidden px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate md:table-cell">Currency</th>
                        <th class="hidden px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate sm:table-cell">Invoices</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-midnight/5">
                    @foreach ($this->clients as $client)
                        <tr class="group transition {{ $client->trashed() ? 'opacity-50' : '' }} hover:bg-mist/60">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-midnight">{{ $client->name }}</p>
                                @if ($client->company_name)
                                    <p class="text-xs text-slate">{{ $client->company_name }}</p>
                                @endif
                                @if ($client->trashed())
                                    <span class="mt-0.5 inline-flex items-center rounded-full bg-slate/10 px-2 py-0.5 text-xs font-medium text-slate">Archived</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-slate">{{ $client->email }}</td>
                            <td class="hidden px-5 py-3.5 md:table-cell">
                                <span class="inline-flex items-center rounded-full bg-slate/8 px-2.5 py-0.5 text-xs font-medium text-slate">
                                    {{ $client->currency }}
                                </span>
                            </td>
                            <td class="hidden px-5 py-3.5 text-right text-slate sm:table-cell">{{ $client->invoices_count }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 transition group-hover:opacity-100">
                                    @if ($client->trashed())
                                        <button
                                            wire:click="restore({{ $client->id }})"
                                            class="rounded-field px-3 py-1.5 text-xs font-medium text-verdant ring-1 ring-verdant/20 transition hover:bg-verdant/8"
                                        >
                                            Restore
                                        </button>
                                    @else
                                        <a
                                            href="{{ route('clients.edit', $client) }}"
                                            class="rounded-field px-3 py-1.5 text-xs font-medium text-slate ring-1 ring-midnight/10 transition hover:bg-mist hover:text-midnight"
                                        >
                                            Edit
                                        </a>
                                        <button
                                            wire:click="delete({{ $client->id }})"
                                            wire:confirm="Archive {{ $client->name }}? Their invoices will be kept."
                                            class="rounded-field px-3 py-1.5 text-xs font-medium text-garnet ring-1 ring-garnet/20 transition hover:bg-garnet/8"
                                        >
                                            Archive
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($this->clients->hasPages())
                <div class="flex items-center justify-between border-t border-midnight/6 px-5 py-3">
                    <p class="text-xs text-slate">
                        Showing {{ $this->clients->firstItem() }}–{{ $this->clients->lastItem() }} of {{ $this->clients->total() }}
                    </p>
                    <div class="flex items-center gap-1.5">
                        @if ($this->clients->onFirstPage())
                            <span class="rounded-field px-3 py-1.5 text-xs font-medium text-slate/40 ring-1 ring-midnight/8 cursor-not-allowed">← Prev</span>
                        @else
                            <button wire:click="previousPage" class="rounded-field px-3 py-1.5 text-xs font-medium text-slate ring-1 ring-midnight/10 transition hover:bg-mist hover:text-midnight">← Prev</button>
                        @endif
                        @if ($this->clients->hasMorePages())
                            <button wire:click="nextPage" class="rounded-field px-3 py-1.5 text-xs font-medium text-slate ring-1 ring-midnight/10 transition hover:bg-mist hover:text-midnight">Next →</button>
                        @else
                            <span class="rounded-field px-3 py-1.5 text-xs font-medium text-slate/40 ring-1 ring-midnight/8 cursor-not-allowed">Next →</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
