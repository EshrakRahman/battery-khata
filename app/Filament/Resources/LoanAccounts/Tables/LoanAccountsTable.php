<?php

namespace App\Filament\Resources\LoanAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('loan_name')
                    ->label(__('Loan Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lender_type')
                    ->label(__('Lender Type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'supplier' => 'gray',
                        'customer' => 'info',
                        'staff' => 'warning',
                        'external' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __($state))
                    ->sortable(),

                TextColumn::make('lender_name')
                    ->label(__('Lender Name')),

                TextColumn::make('principal_amount')
                    ->label(__('Principal Amount'))
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('outstanding_balance')
                    ->label(__('Outstanding Balance'))
                    ->money('BDT')
                    ->sortable(),
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
            ->defaultSort('start_date', 'desc');
    }
}
