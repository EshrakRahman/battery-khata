<?php

namespace App\Filament\Resources\CashbookEntries\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CashbookEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('cash_register_session_id')
                            ->label(__('Cash Register Session'))
                            ->disabled(),
                        TextInput::make('entry_type')
                            ->label(__('Entry Type'))
                            ->disabled(),
                        TextInput::make('direction')
                            ->label(__('Direction'))
                            ->disabled(),
                        TextInput::make('payment_method')
                            ->label(__('Payment Method'))
                            ->disabled(),
                        TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->prefix('৳')
                            ->disabled(),
                        Placeholder::make('reference')
                            ->label(__('Reference'))
                            ->content(fn ($record) => $record && $record->reference_type ? (class_basename($record->reference_type).' #'.$record->reference_id) : '-'),
                        TextInput::make('creator.name')
                            ->label(__('Created By'))
                            ->disabled(),
                        TextInput::make('created_at')
                            ->label(__('Created At'))
                            ->disabled(),
                        Textarea::make('notes')
                            ->label(__('Notes'))
                            ->columnSpanFull()
                            ->disabled(),
                    ]),
            ]);
    }
}
