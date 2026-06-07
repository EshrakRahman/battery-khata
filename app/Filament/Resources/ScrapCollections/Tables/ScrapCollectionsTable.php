<?php

namespace App\Filament\Resources\ScrapCollections\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ScrapCollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice.invoice_no')
                    ->label(__('Invoice No'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('warehouse.name')
                    ->label(__('Warehouse'))
                    ->sortable(),

                TextColumn::make('scrap_type')
                    ->label(__('Scrap Type'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label(__('Quantity'))
                    ->sortable(),

                TextColumn::make('unit_value')
                    ->label(__('Unit Value'))
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('total_value')
                    ->label(__('Total Value'))
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'InWarehouse' => 'warning',
                        'Disposed' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('scrap_disposal_id')
                    ->label(__('Disposal ID'))
                    ->formatStateUsing(fn ($state) => $state ? __('Disposal #').$state : '-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'InWarehouse' => __('In Warehouse'),
                        'Disposed' => __('Disposed'),
                    ]),

                SelectFilter::make('warehouse_id')
                    ->label(__('Warehouse'))
                    ->relationship('warehouse', 'name'),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
