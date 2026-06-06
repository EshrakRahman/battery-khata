<?php

namespace App\Filament\Resources\WarrantyClaims\Pages;

use App\Enums\BatteryStatus;
use App\Enums\ClaimStatus;
use App\Filament\Resources\WarrantyClaims\WarrantyClaimResource;
use App\Models\BatterySerial;
use App\Models\BufferBatteryIssue;
use App\Models\InvoiceItem;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditWarrantyClaim extends EditRecord
{
    protected static string $resource = WarrantyClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // State Transition: Send to Supplier
            Action::make('send_to_supplier')
                ->label(__('Send to Supplier'))
                ->color('warning')
                ->visible(fn () => $this->record->claim_status === ClaimStatus::Received)
                ->form([
                    DatePicker::make('supplier_sent_date')
                        ->label(__('Supplier Sent Date'))
                        ->required()
                        ->default(today()),
                    Textarea::make('supplier_claim_no')
                        ->label(__('Supplier Claim No / Slip'))
                        ->rows(1)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'claim_status' => ClaimStatus::SentToSupplier,
                        'supplier_sent_date' => $data['supplier_sent_date'],
                        'supplier_claim_no' => $data['supplier_claim_no'],
                    ]);
                    $this->refreshFormData(['claim_status', 'supplier_sent_date', 'supplier_claim_no']);
                }),

            // State Transition: Log Supplier Response
            Action::make('supplier_response')
                ->label(__('Supplier Response'))
                ->color('info')
                ->visible(fn () => $this->record->claim_status === ClaimStatus::SentToSupplier)
                ->form([
                    Select::make('response')
                        ->label(__('Response'))
                        ->options([
                            'approved' => __('Approved'),
                            'rejected' => __('Rejected'),
                        ])
                        ->required(),
                    Textarea::make('resolution_notes')
                        ->label(__('Notes'))
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $newStatus = $data['response'] === 'approved' ? ClaimStatus::Approved : ClaimStatus::Rejected;
                    $this->record->update([
                        'claim_status' => $newStatus,
                        'resolution_notes' => $data['resolution_notes'] ?? null,
                    ]);
                    $this->refreshFormData(['claim_status', 'resolution_notes']);
                }),

            // State Transition: Resolve Claim
            Action::make('resolve_claim')
                ->label(__('Resolve Claim'))
                ->color('success')
                ->visible(fn () => in_array($this->record->claim_status, [ClaimStatus::Approved, ClaimStatus::Rejected]))
                ->form(function () {
                    $formSchema = [];

                    if ($this->record->claim_status === ClaimStatus::Approved) {
                        $formSchema[] = Select::make('replacement_battery_serial_id')
                            ->label(__('Replacement Battery Serial'))
                            ->options(function () {
                                $faultySerial = BatterySerial::find($this->record->battery_serial_id);
                                if (! $faultySerial || ! $faultySerial->product) {
                                    return BatterySerial::where('current_status', BatteryStatus::InStock)
                                        ->pluck('serial_no', 'id');
                                }

                                return BatterySerial::query()
                                    ->where('current_status', BatteryStatus::InStock)
                                    ->whereHas('product', function ($q) use ($faultySerial) {
                                        $q->where('category_id', $faultySerial->product->category_id);
                                    })
                                    ->pluck('serial_no', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload();

                        $formSchema[] = Select::make('warehouse_id')
                            ->label(__('Issue Warehouse'))
                            ->options(Warehouse::pluck('name', 'id'))
                            ->required()
                            ->default(fn () => Warehouse::first()?->id);
                    }

                    $formSchema[] = DatePicker::make('resolved_date')
                        ->label(__('Resolved Date'))
                        ->required()
                        ->default(today());

                    $formSchema[] = Textarea::make('resolution_notes')
                        ->label(__('Resolution Notes'))
                        ->rows(2);

                    // Check if buffer battery is currently active
                    $activeBuffer = $this->record->bufferIssues()->whereNull('returned_date')->first();
                    if ($activeBuffer) {
                        $formSchema[] = DatePicker::make('buffer_returned_date')
                            ->label(__('Buffer Returned Date'))
                            ->required()
                            ->default(today());

                        $formSchema[] = Select::make('buffer_return_warehouse_id')
                            ->label(__('Buffer Return Warehouse'))
                            ->options(Warehouse::pluck('name', 'id'))
                            ->required()
                            ->default(fn () => Warehouse::first()?->id);
                    }

                    return $formSchema;
                })
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $user = auth()->user();
                        $inventoryService = app(InventoryService::class);

                        if ($this->record->claim_status === ClaimStatus::Approved) {
                            $replacementSerial = BatterySerial::findOrFail($data['replacement_battery_serial_id']);
                            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
                            $faultySerial = BatterySerial::findOrFail($this->record->battery_serial_id);

                            // Mark replacement serial as Sold, log WarrantyOut
                            $inventoryService->recordWarrantyOut($replacementSerial, $warehouse, $this->record, $user, BatteryStatus::Sold);

                            // Mark faulty serial as Scrap
                            $faultySerial->update(['current_status' => BatteryStatus::Scrap]);

                            $this->record->replacement_battery_serial_id = $replacementSerial->id;
                        } elseif ($this->record->claim_status === ClaimStatus::Rejected) {
                            $faultySerial = BatterySerial::findOrFail($this->record->battery_serial_id);
                            $invoiceItem = InvoiceItem::where('battery_serial_id', $faultySerial->id)->first();
                            $warehouse = $invoiceItem
                                ? ($inventoryService->getCurrentWarehouse($faultySerial) ?? Warehouse::first())
                                : Warehouse::first();

                            // Return faulty battery to customer, log WarrantyOut to status Sold
                            $inventoryService->recordWarrantyOut($faultySerial, $warehouse, $this->record, $user, BatteryStatus::Sold);
                        }

                        // Handle buffer return if applicable
                        $activeBuffer = $this->record->bufferIssues()->whereNull('returned_date')->first();
                        if ($activeBuffer && ! empty($data['buffer_returned_date']) && ! empty($data['buffer_return_warehouse_id'])) {
                            $bufferSerial = BatterySerial::findOrFail($activeBuffer->battery_serial_id);
                            $returnWarehouse = Warehouse::findOrFail($data['buffer_return_warehouse_id']);

                            $activeBuffer->update([
                                'returned_date' => $data['buffer_returned_date'],
                            ]);

                            $inventoryService->recordBufferReturn($bufferSerial, $returnWarehouse, $this->record->id, $user);
                        }

                        $this->record->update([
                            'claim_status' => ClaimStatus::Resolved,
                            'resolved_date' => $data['resolved_date'],
                            'resolution_notes' => $data['resolution_notes'] ?? $this->record->resolution_notes,
                        ]);
                    });
                    $this->refreshFormData(['claim_status', 'resolved_date', 'resolution_notes', 'replacement_battery_serial_id']);
                }),

            // Buffer Management Action: Issue Buffer
            Action::make('issue_buffer')
                ->label(__('Issue Buffer'))
                ->color('info')
                ->visible(fn () => in_array($this->record->claim_status, [ClaimStatus::Received, ClaimStatus::SentToSupplier]) &&
                    $this->record->bufferIssues()->whereNull('returned_date')->count() === 0
                )
                ->form([
                    Select::make('buffer_battery_serial_id')
                        ->label(__('Buffer Battery Serial'))
                        ->options(function () {
                            $faultySerial = BatterySerial::find($this->record->battery_serial_id);
                            if (! $faultySerial || ! $faultySerial->product) {
                                return BatterySerial::where('current_status', BatteryStatus::InStock)
                                    ->pluck('serial_no', 'id');
                            }

                            return BatterySerial::query()
                                ->where('current_status', BatteryStatus::InStock)
                                ->whereHas('product', function ($q) use ($faultySerial) {
                                    $q->where('category_id', $faultySerial->product->category_id);
                                })
                                ->pluck('serial_no', 'id');
                        })
                        ->required()
                        ->searchable()
                        ->preload(),

                    Select::make('warehouse_id')
                        ->label(__('Warehouse'))
                        ->options(Warehouse::pluck('name', 'id'))
                        ->required()
                        ->default(fn () => Warehouse::first()?->id),

                    DatePicker::make('issued_date')
                        ->label(__('Issued Date'))
                        ->required()
                        ->default(today()),

                    Textarea::make('notes')
                        ->label(__('Notes'))
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $user = auth()->user();
                        $bufferSerial = BatterySerial::findOrFail($data['buffer_battery_serial_id']);
                        $warehouse = Warehouse::findOrFail($data['warehouse_id']);

                        BufferBatteryIssue::create([
                            'warranty_claim_id' => $this->record->id,
                            'battery_serial_id' => $bufferSerial->id,
                            'issued_date' => $data['issued_date'],
                            'notes' => $data['notes'] ?? null,
                            'created_by' => $user->id,
                        ]);

                        app(InventoryService::class)->recordBufferIssue($bufferSerial, $warehouse, $this->record->id, $user);
                    });
                }),

            // Buffer Management Action: Return Buffer
            Action::make('return_buffer')
                ->label(__('Return Buffer'))
                ->color('success')
                ->visible(fn () => $this->record->bufferIssues()->whereNull('returned_date')->count() > 0
                )
                ->form([
                    DatePicker::make('returned_date')
                        ->label(__('Returned Date'))
                        ->required()
                        ->default(today()),

                    Select::make('warehouse_id')
                        ->label(__('Warehouse'))
                        ->options(Warehouse::pluck('name', 'id'))
                        ->required()
                        ->default(fn () => Warehouse::first()?->id),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $user = auth()->user();
                        $activeBuffer = $this->record->bufferIssues()->whereNull('returned_date')->firstOrFail();
                        $bufferSerial = BatterySerial::findOrFail($activeBuffer->battery_serial_id);
                        $warehouse = Warehouse::findOrFail($data['warehouse_id']);

                        $activeBuffer->update([
                            'returned_date' => $data['returned_date'],
                        ]);

                        app(InventoryService::class)->recordBufferReturn($bufferSerial, $warehouse, $this->record->id, $user);
                    });
                }),

            DeleteAction::make(),
        ];
    }
}
