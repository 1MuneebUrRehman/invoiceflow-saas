<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\StatsOverview;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Str;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        $firstName = Str::before(auth()->user()->name ?? 'there', ' ');

        return "Welcome back, {$firstName}";
    }

    public function getSubheading(): ?string
    {
        return "Here's where your business stands today.";
    }

    protected function getHeaderWidgets(): array
    {
        return [StatsOverview::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getFooterWidgets(): array
    {
        return [RevenueChartWidget::class];
    }

    protected function getActions(): array
    {
        return [
            Action::make('manageClients')
                ->label('Manage clients')
                ->url(ClientResource::getUrl())
                ->color('gray')
                ->outlined(),
            Action::make('newInvoice')
                ->label('New invoice')
                ->url(InvoiceResource::getUrl('create'))
                ->color('primary'),
        ];
    }
}
