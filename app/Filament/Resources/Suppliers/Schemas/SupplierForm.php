<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Supplier Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('mobile')
                    ->label(__('Mobile'))
                    ->maxLength(255),
                Select::make('supplier_type')
                    ->label(__('Supplier Type'))
                    ->options([
                        'Regular' => __('Regular'),
                        'Mahajon' => __('Mahajon'),
                        'Manufacturer' => __('Manufacturer'),
                    ])
                    ->default('Regular')
                    ->required(),
                Textarea::make('address')
                    ->label(__('Address'))
                    ->rows(3)
                    ->maxLength(65535),
            ]);
    }
}
