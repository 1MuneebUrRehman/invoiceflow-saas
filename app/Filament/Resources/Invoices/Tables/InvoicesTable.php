<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Actions\Invoices\SendInvoice;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('client.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total')
                    ->money(fn (Invoice $record): string => $record->currency, divideBy: 100)
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn (Invoice $record): ?string => $record->status === InvoiceStatus::Overdue ? 'danger' : null),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->placeholder('Not sent')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('send')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->requiresConfirmation()
                    ->modalHeading('Send invoice')
                    ->modalDescription(fn (Invoice $record): string => "Email {$record->number} to {$record->client->email} with a secure payment link?")
                    ->visible(fn (Invoice $record): bool => $record->status->isPayable() || $record->status === InvoiceStatus::Draft)
                    ->action(function (Invoice $record): void {
                        app(SendInvoice::class)->execute($record);

                        Notification::make()
                            ->title("Invoice {$record->number} is on its way")
                            ->body("The PDF is being generated and emailed to {$record->client->email}.")
                            ->success()
                            ->send();
                    }),
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (Invoice $record): bool => filled($record->pdf_path))
                    ->action(fn (Invoice $record): StreamedResponse => Storage::disk('local')->download($record->pdf_path, "{$record->number}.pdf")),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
