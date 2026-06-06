<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_no')
                    ->label(__('Invoice No'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->label(__('Invoice Date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('sub_total')
                    ->label(__('Sub Total'))
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label(__('Discount Amount'))
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('scrap_adjustment')
                    ->label(__('Scrap Adjustment'))
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->label(__('Grand Total'))
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('invoice_status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => match ($state) {
                        InvoiceStatus::Draft => 'gray',
                        InvoiceStatus::Completed => 'success',
                        InvoiceStatus::Cancelled => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => __($state->name))
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('invoice_date', 'desc');
    }
}
