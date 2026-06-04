<?php

namespace App\Filament\Resources\BatterySerials\RelationManagers;

use App\Enums\TransactionType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Transactions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_type')
                    ->label(__('Transaction Type'))
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof TransactionType ? $state->value : $state) {
                        'purchase' => 'success',
                        'sale' => 'gray',
                        'transfer_in', 'transfer_out' => 'info',
                        'warranty_in', 'warranty_out' => 'danger',
                        'buffer_issue', 'buffer_return' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state instanceof TransactionType ? $state->value : $state) {
                        'purchase' => __('Purchase'),
                        'sale' => __('Sale'),
                        'transfer_in' => __('Transfer In'),
                        'transfer_out' => __('Transfer Out'),
                        'warranty_in' => __('Warranty In'),
                        'warranty_out' => __('Warranty Out'),
                        'buffer_issue' => __('Buffer Issue'),
                        'buffer_return' => __('Buffer Return'),
                        default => (string) $state,
                    })
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label(__('Warehouse'))
                    ->sortable(),
                TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->wrap(),
                TextColumn::make('creator.name')
                    ->label(__('Performed By'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Date & Time'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
