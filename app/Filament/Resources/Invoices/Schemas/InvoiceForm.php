<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Tenant;
use App\Tenancy\CurrentTenant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('number')
                            ->label('Invoice number')
                            ->weight(FontWeight::SemiBold)
                            ->visibleOn('edit'),
                        Select::make('status')
                            ->options(InvoiceStatus::class)
                            ->required()
                            ->visibleOn('edit'),
                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $clientCurrency = Client::query()->whereKey($state)->value('currency');

                                if ($clientCurrency !== null) {
                                    $set('currency', $clientCurrency);
                                }
                            }),
                        Select::make('currency')
                            ->options(Tenant::currencyOptions())
                            ->default(fn (): string => app(CurrentTenant::class)->get()->default_currency ?? 'USD')
                            ->searchable()
                            ->required(),
                        DatePicker::make('issue_date')
                            ->default(today())
                            ->required(),
                        DatePicker::make('due_date')
                            ->default(today()->addDays(14))
                            ->afterOrEqual('issue_date')
                            ->required(),
                    ]),
                Section::make('Line items')
                    ->description('Amounts are in the invoice currency.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            ->table([
                                TableColumn::make('Description')
                                    ->markAsRequired(),
                                TableColumn::make('Qty')
                                    ->markAsRequired()
                                    ->width('110px'),
                                TableColumn::make('Unit price')
                                    ->markAsRequired()
                                    ->width('160px'),
                            ])
                            ->schema([
                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->required(),
                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->formatStateUsing(fn (int|float|string|null $state): ?float => filled($state) ? ((float) $state) / 100 : null)
                                    ->dehydrateStateUsing(fn (int|float|string|null $state): int => (int) round(((float) $state) * 100)),
                            ])
                            ->orderColumn('position')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->required()
                            ->addActionLabel('Add line item'),
                    ]),
                Section::make('Summary')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            TextInput::make('tax_rate')
                                ->label('Tax rate')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->required(),
                            Textarea::make('notes')
                                ->rows(3)
                                ->placeholder('Payment terms, thank-you note… shown to the client.'),
                        ]),
                        Group::make([
                            Text::make(<<<'JS'
                                (() => {
                                    const items = Object.values($get('items') ?? {});
                                    const subtotal = items.reduce((carry, item) => carry + (parseFloat(item?.quantity) || 0) * (parseFloat(item?.unit_price) || 0), 0);
                                    const format = new Intl.NumberFormat(undefined, { style: 'currency', currency: $get('currency') || 'USD' });
                                    return `Subtotal: ${format.format(subtotal)}`;
                                })()
                                JS)
                                ->js(),
                            Text::make(<<<'JS'
                                (() => {
                                    const items = Object.values($get('items') ?? {});
                                    const subtotal = items.reduce((carry, item) => carry + (parseFloat(item?.quantity) || 0) * (parseFloat(item?.unit_price) || 0), 0);
                                    const tax = subtotal * (parseFloat($get('tax_rate')) || 0) / 100;
                                    const format = new Intl.NumberFormat(undefined, { style: 'currency', currency: $get('currency') || 'USD' });
                                    return `Tax: ${format.format(tax)}`;
                                })()
                                JS)
                                ->js(),
                            Text::make(<<<'JS'
                                (() => {
                                    const items = Object.values($get('items') ?? {});
                                    const subtotal = items.reduce((carry, item) => carry + (parseFloat(item?.quantity) || 0) * (parseFloat(item?.unit_price) || 0), 0);
                                    const total = subtotal * (1 + (parseFloat($get('tax_rate')) || 0) / 100);
                                    const format = new Intl.NumberFormat(undefined, { style: 'currency', currency: $get('currency') || 'USD' });
                                    return `Total: ${format.format(total)}`;
                                })()
                                JS)
                                ->js()
                                ->size(TextSize::Large)
                                ->weight(FontWeight::Bold),
                        ]),
                    ]),
            ]);
    }
}
