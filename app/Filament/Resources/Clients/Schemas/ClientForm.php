<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Tenant;
use App\Tenancy\CurrentTenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('company_name')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Select::make('currency')
                            ->options(Tenant::currencyOptions())
                            ->default(fn (): string => app(CurrentTenant::class)->get()->default_currency ?? 'USD')
                            ->searchable()
                            ->required()
                            ->helperText('New invoices for this client default to this currency.'),
                    ]),
                Section::make('Billing address')
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextInput::make('address_line1')
                            ->label('Address line 1')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('address_line2')
                            ->label('Address line 2')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('city')
                            ->maxLength(255),
                        TextInput::make('state')
                            ->label('State / province')
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->maxLength(255),
                        TextInput::make('country')
                            ->label('Country code')
                            ->placeholder('US')
                            ->length(2)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper($state) : null),
                    ]),
                Section::make('Notes')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->rows(3)
                            ->placeholder('Internal notes — never shown to the client.'),
                    ]),
            ]);
    }
}
