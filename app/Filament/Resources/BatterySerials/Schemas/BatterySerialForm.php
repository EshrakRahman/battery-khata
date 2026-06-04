<?php

namespace App\Filament\Resources\BatterySerials\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BatterySerialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product')
                    ->getOptionLabelFromRecordUsing(fn (Product $record) => "{$record->brand_name} - {$record->model_name}")
                    ->label(__('Product'))
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('serial_no')
                    ->label(__('Serial Number'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('current_status')
                    ->label(__('Status'))
                    ->required()
                    ->options([
                        'in_stock' => __('In Stock'),
                        'sold' => __('Sold'),
                        'reserved' => __('Reserved'),
                        'warranty_claim' => __('Warranty Claim'),
                        'buffer_issued' => __('Buffer Issued'),
                        'supplier_returned' => __('Supplier Returned'),
                        'scrap' => __('Scrap'),
                    ])
                    ->default('in_stock'),
            ]);
    }
}
