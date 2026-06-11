<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class StatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $currency = auth()->user()?->tenant?->default_currency ?? 'USD';

        $outstanding = (int) Invoice::query()
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])
            ->sum(DB::raw('total - amount_paid'));

        $overdueCount = Invoice::query()
            ->where('status', InvoiceStatus::Overdue)
            ->count();

        $paidThisMonth = (int) Invoice::query()
            ->where('status', InvoiceStatus::Paid)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        return [
            Stat::make('Outstanding', Number::currency($outstanding / 100, in: $currency))
                ->description($overdueCount > 0 ? "{$overdueCount} overdue" : 'All current')
                ->descriptionIcon($overdueCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),

            Stat::make('Paid this month', Number::currency($paidThisMonth / 100, in: $currency))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Overdue', $overdueCount)
                ->description($overdueCount === 0 ? 'Nothing overdue' : ($overdueCount === 1 ? '1 invoice needs attention' : "{$overdueCount} invoices need attention"))
                ->descriptionIcon('heroicon-m-clock')
                ->color($overdueCount > 0 ? 'danger' : 'gray'),
        ];
    }
}
