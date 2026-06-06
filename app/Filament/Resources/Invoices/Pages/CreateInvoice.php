<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Enums\TransactionDirection;
use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\BatterySerial;
use App\Models\Broker;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\ScrapCollection;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\LedgerService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mount(): void
    {
        parent::mount();

        $activeSession = CashRegisterSession::where('opened_by', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if (! $activeSession) {
            Notification::make()
                ->warning()
                ->title(__('Active Cash Register Session Required'))
                ->body(__('An active cash register session is required to perform sales checkout.'))
                ->send();

            $this->redirect(CashRegisterSessionResource::getUrl('index'));
        }
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $customerId = $data['customer_id'] ?? null;
        if (! $customerId) {
            return;
        }

        $customer = Customer::find($customerId);
        if (! $customer) {
            return;
        }

        // Calculate expected remaining due
        $subTotal = (float) ($data['sub_total'] ?? 0);
        $discount = (float) ($data['discount_amount'] ?? 0);
        $scrapVal = (float) ($data['scrap_adjustment'] ?? 0);
        $grandTotal = max(0, $subTotal - $discount - $scrapVal);

        $totalPaid = 0.00;
        foreach ($data['payments'] ?? [] as $payment) {
            $totalPaid += (float) ($payment['amount'] ?? 0);
        }

        $remainingDue = max(0, $grandTotal - $totalPaid);

        if ($remainingDue > 0) {
            $latestLedger = CustomerLedger::where('customer_id', $customerId)
                ->latest('id')
                ->first();
            $currentExposure = $latestLedger ? (float) $latestLedger->running_balance : 0.00;

            if (($currentExposure + $remainingDue) > (float) $customer->credit_limit) {
                throw ValidationException::withMessages([
                    'data.customer_id' => __('The remaining due amount exceeds the customer\'s credit limit.'),
                ]);
            }
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $activeSession = CashRegisterSession::where('opened_by', $user->id)
                ->whereNull('closed_at')
                ->firstOrFail();

            // 1. Generate/Set invoice details
            $subTotal = (float) ($data['sub_total'] ?? 0);
            $discount = (float) ($data['discount_amount'] ?? 0);
            $scrapAdjustment = (float) ($data['scrap_adjustment'] ?? 0);
            $grandTotal = max(0, $subTotal - $discount - $scrapAdjustment);

            $invoice = Invoice::create([
                'invoice_no' => $data['invoice_no'] ?? 'INV-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'customer_id' => $data['customer_id'],
                'broker_id' => $data['broker_id'] ?? null,
                'cash_register_session_id' => $activeSession->id,
                'invoice_date' => now(),
                'sub_total' => $subTotal,
                'discount_amount' => $discount,
                'scrap_adjustment' => $scrapAdjustment,
                'grand_total' => $grandTotal,
                'invoice_status' => InvoiceStatus::Completed,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // 2. Create items and inventory transactions
            $inventoryService = app(InventoryService::class);
            foreach ($data['items'] ?? [] as $itemData) {
                $isSet = (bool) ($itemData['is_set'] ?? false);
                $serialIds = $isSet ? ($itemData['battery_serial_ids'] ?? []) : [$itemData['battery_serial_id'] ?? null];

                foreach ($serialIds as $serialId) {
                    if (! $serialId) {
                        continue;
                    }

                    $serial = BatterySerial::findOrFail($serialId);

                    // Derive warehouse of the serial
                    $warehouse = $inventoryService->getCurrentWarehouse($serial);
                    if (! $warehouse) {
                        $warehouse = Warehouse::first();
                    }

                    // Record Sale transaction in stock
                    $inventoryService->recordSale($serial, $warehouse, $invoice, $user);

                    // Create InvoiceItem
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $itemData['product_id'],
                        'battery_serial_id' => $serial->id,
                        'sale_price' => $itemData['sale_price'],
                        'warranty_months' => $itemData['warranty_months'],
                    ]);
                }
            }

            // 3. Create Scrap collections if any
            $scrapCollections = [];
            foreach ($data['scrap_items'] ?? [] as $scrapData) {
                $scrapCollections[] = ScrapCollection::create([
                    'customer_id' => $invoice->customer_id,
                    'invoice_id' => $invoice->id,
                    'warehouse_id' => $scrapData['warehouse_id'] ?? Warehouse::first()?->id,
                    'scrap_type' => $scrapData['scrap_type'],
                    'quantity' => $scrapData['quantity'],
                    'unit_value' => $scrapData['unit_value'],
                    'total_value' => $scrapData['total_value'],
                    'status' => 'InWarehouse',
                    'created_by' => $user->id,
                ]);
            }

            // 4. Create Payments & Allocations & Cashbook entries
            $payments = [];
            foreach ($data['payments'] ?? [] as $payData) {
                $payment = Payment::create([
                    'customer_id' => $invoice->customer_id,
                    'cash_register_session_id' => $activeSession->id,
                    'scrap_collection_id' => $scrapCollections[0]->id ?? null,
                    'payment_date' => now(),
                    'payment_method' => $payData['payment_method'],
                    'total_amount' => $payData['amount'],
                    'service_charge' => $payData['service_charge'] ?? 0.00,
                    'reference_no' => $payData['reference_no'] ?? null,
                    'notes' => $payData['notes'] ?? null,
                    'received_by' => $user->id,
                ]);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'allocated_amount' => $payment->total_amount,
                ]);

                // Log Cashbook entry
                CashbookEntry::create([
                    'cash_register_session_id' => $activeSession->id,
                    'entry_type' => 'Sale',
                    'direction' => TransactionDirection::In,
                    'payment_method' => $payData['payment_method'],
                    'amount' => $payment->total_amount,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'created_by' => $user->id,
                ]);

                $payments[] = $payment;
            }

            // 5. Record Ledger Transactions
            $ledgerService = app(LedgerService::class);
            $customer = Customer::findOrFail($invoice->customer_id);

            // Record Debit for Invoice
            $ledgerService->recordCustomerTransaction(
                $customer,
                $invoice->grand_total,
                0.00,
                'Invoice',
                $invoice,
                'Sale invoice #'.$invoice->invoice_no
            );

            // Record Credit for each Payment
            foreach ($payments as $payment) {
                $ledgerService->recordCustomerTransaction(
                    $customer,
                    0.00,
                    $payment->total_amount,
                    'Payment',
                    $payment,
                    'Payment received for invoice #'.$invoice->invoice_no
                );
            }

            // Record Broker Commission if broker attached
            if ($invoice->broker_id && $data['broker_commission'] > 0) {
                $broker = Broker::findOrFail($invoice->broker_id);
                $ledgerService->recordBrokerTransaction(
                    $broker,
                    0.00,
                    $data['broker_commission'],
                    'Commission',
                    $invoice,
                    'Commission earned for sale invoice #'.$invoice->invoice_no
                );
            }

            return $invoice;
        });
    }
}
