<?php

namespace App\Filament\Resources\Brokers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BrokerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('mobile')
                    ->label(__('Mobile'))
                    ->maxLength(255),
                TextInput::make('commission_rate')
                    ->label(__('Commission Rate'))
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0),
            ]);
    }
}
