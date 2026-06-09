<?php

namespace App\Filament\Resources\ScrapDisposals\Schemas;

use App\Enums\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ScrapDisposalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->label(__('Supplier/Factory'))
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required(fn ($get) => $get('payment_method') === PaymentMethod::ScrapAdjustment->value),

                DatePicker::make('disposal_date')
                    ->label(__('Disposal Date'))
                    ->required()
                    ->default(now()),

                Select::make('payment_method')
                    ->label(__('Payment Method'))
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($method) => [$method->value => __($method->value)])->toArray())
                    ->required()
                    ->live(),

                TextInput::make('total_received')
                    ->label(__('Total Value Received'))
                    ->numeric()
                    ->required()
                    ->minValue(0.00)
                    ->step(0.01),

                Select::make('collections')
                    ->label(__('Select Scrap Cores to Dispose'))
                    ->relationship(
                        name: 'collections',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn ($query, $record) => $query->where(function ($q) use ($record) {
                            $q->where('status', 'InWarehouse');
                            if ($record) {
                                $q->orWhere('scrap_disposal_id', $record->id);
                            }
                        })
                    )
                    ->multiple()
                    ->preload()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - {$record->scrap_type} (Val: {$record->total_value} BDT)")
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
