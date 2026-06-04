<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Basic Information'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('category_id')
                                    ->relationship(name: 'category', titleAttribute: 'name')
                                    ->label(__('Category'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('brand_name')
                                    ->label(__('Brand Name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('model_name')
                                    ->label(__('Model Name'))
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make(__('Technical Specifications'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('voltage')
                                    ->label(__('Voltage'))
                                    ->maxLength(255),
                                TextInput::make('capacity_ah')
                                    ->label(__('Capacity (Ah)'))
                                    ->maxLength(255),
                                TextInput::make('plate_count')
                                    ->label(__('Plate Count'))
                                    ->numeric()
                                    ->minValue(0),
                                TextInput::make('warranty_months')
                                    ->label(__('Warranty (Months)'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ]),
                    ]),

                Section::make(__('Pricing & Inventory Settings'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('purchase_cost')
                                    ->label(__('Purchase Cost'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('mrp_price')
                                    ->label(__('MRP Price'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),
                                TextInput::make('dealer_price')
                                    ->label(__('Dealer Price'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),
                                TextInput::make('set_price')
                                    ->label(__('Set Price'))
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                        Grid::make(4)
                            ->schema([
                                TextInput::make('standard_set_qty')
                                    ->label(__('Standard Set Qty'))
                                    ->numeric()
                                    ->default(4)
                                    ->minValue(1),
                                TextInput::make('alert_threshold_qty')
                                    ->label(__('Alert Threshold Qty'))
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(0),
                                Toggle::make('has_serial_tracking')
                                    ->label(__('Has Serial Tracking'))
                                    ->default(true),
                                Toggle::make('is_active')
                                    ->label(__('Is Active'))
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }
}
