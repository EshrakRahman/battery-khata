<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerLedgerRelationManager extends RelationManager
{
    protected static string $relationship = 'ledgers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Ledger');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label(__('Transaction Date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->label(__('Transaction Type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('debit')
                    ->label(__('Debit'))
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('credit')
                    ->label(__('Credit'))
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('running_balance')
                    ->label(__('Running Balance'))
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(40)
                    ->wrap(),
            ])
            ->defaultSort('transaction_date', 'asc')
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
