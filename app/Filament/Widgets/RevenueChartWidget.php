<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Revenue — last 12 months';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(
            fn (int $n) => now()->subMonths($n)->startOfMonth()
        );

        $revenueByMonth = Invoice::query()
            ->where('status', InvoiceStatus::Paid)
            ->where('paid_at', '>=', $months->first())
            ->get(['paid_at', 'total'])
            ->groupBy(fn (Invoice $i): string => Carbon::parse($i->paid_at)->format('Y-m'))
            ->map(fn ($group): float => round($group->sum('total') / 100, 2));

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => $months->map(fn ($m) => $revenueByMonth->get($m->format('Y-m'), 0))->values()->toArray(),
                'backgroundColor' => 'rgba(31, 94, 174, 0.08)',
                'borderColor' => '#1f5eae',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.4,
                'pointBackgroundColor' => '#1f5eae',
                'pointRadius' => 3,
                'pointHoverRadius' => 5,
            ]],
            'labels' => $months->map(fn ($m) => $m->format('M Y'))->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
