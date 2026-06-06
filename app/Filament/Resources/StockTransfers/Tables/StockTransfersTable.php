<?php

namespace App\Filament\Resources\StockTransfers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_date')
                    ->label(__('Transfer Date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('sourceWarehouse.name')
                    ->label(__('Source Warehouse'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destinationWarehouse.name')
                    ->label(__('Destination Warehouse'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'gray',
                        'InTransit' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __($state))
                    ->sortable(),
                TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transfer_date', 'desc');
    }
}
