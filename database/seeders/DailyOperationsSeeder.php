<?php

namespace Database\Seeders;

use App\Enums\BatteryStatus;
use App\Enums\ClaimStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LenderType;
use App\Enums\PaymentMethod;
use App\Enums\PdcStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\BatterySerial;
use App\Models\Broker;
use App\Models\BufferBatteryIssue;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LoanAccount;
use App\Models\LoanTransaction;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PostDatedCheque;
use App\Models\Product;
use App\Models\ScrapCollection;
use App\Models\ScrapDisposal;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarrantyClaim;
use App\Services\InventoryService;
use App\Services\LedgerService;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DailyOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create();
        $ledgerService = app(LedgerService::class);
        $inventoryService = app(InventoryService::class);

        $this->command->info('10. Seeding Register Sessions, Sales, Expenses, and Cheques (Day-by-Day)...');

        $admin = User::where('role', UserRole::Admin)->first();
        $boys = User::where('role', UserRole::CounterBoy)->get();
        $godown = Warehouse::where('name', 'West Godown Store')->first();
        $showroom = Warehouse::where('name', 'Main Showroom')->first();
        $scrapYard = Warehouse::where('name', 'Scrap Storage Room')->first();
        $products = Product::all();
        $suppliers = Supplier::all();
        $brokers = Broker::all();
        $expenseCategories = ExpenseCategory::all();
        $customers = Customer::all();

        $usedSerialIds = [];
        $sessionDays = [];
        for ($d = 180; $d >= 0; $d -= 1) {
            $sessionDays[] = Carbon::instance(now()->subDays($d));
        }

        foreach ($sessionDays as $dayIndex => $dayDate) {
            $isToday = $dayDate->isToday();

            // Choose a counter boy or manager to run the register session
            $boy = $boys->random();
            auth()->login($boy); // Login boy to trigger auth events and audit logs!

            // Open cash session
            $openingCash = $faker->randomElement([1000.00, 2000.00, 5000.00]);

            $session = CashRegisterSession::create([
                'opened_by' => $boy->id,
                'opened_at' => $dayDate->copy()->setTime(9, 0, 0),
                'opening_cash' => $openingCash,
                'expected_cash' => $openingCash,
                'closing_cash' => 0.00,
                'shortage_excess' => 0.00,
                'denominations' => [],
                'notes' => 'Register session started for morning shift',
            ]);

            $cashInflows = collect([]);
            $cashOutflows = collect([]);

            // Seed Sales Invoices under this session (1 to 4 invoices per session)
            $salesCount = rand(1, 4);
            // On the final day (today), increase sales counts to populate today's targets
            if ($isToday) {
                $salesCount = rand(4, 7);
            }

            for ($s = 0; $s < $salesCount; $s++) {
                $customer = $customers->random();
                $broker = (rand(1, 10) > 7) ? $brokers->random() : null; // 30% chance of broker

                // Pick 1 to 2 products to sell
                $itemsToSell = [];
                $invoiceSubTotal = 0.00;

                $itemLoopCount = rand(1, 2);
                for ($itemIndex = 0; $itemIndex < $itemLoopCount; $itemIndex++) {
                    $product = $products->random();
                    $isSet = ($product->standard_set_qty > 1 && rand(1, 10) > 7); // 30% chance of set sale for sets
                    $qtyToSell = $isSet ? $product->standard_set_qty : 1;

                    // Pull serials from available stock in warehouse
                    $serials = BatterySerial::where('product_id', $product->id)
                        ->where('current_status', BatteryStatus::InStock)
                        ->whereNotIn('id', $usedSerialIds)
                        ->limit($qtyToSell)
                        ->get();

                    if ($serials->count() < $qtyToSell) {
                        continue; // skip if not enough stock
                    }

                    foreach ($serials as $serial) {
                        $usedSerialIds[] = $serial->id;
                    }

                    $unitPrice = ($customer->customer_type === 'Dealer') ? $product->dealer_price : $product->mrp_price;
                    if ($isSet && $product->set_price) {
                        $unitPrice = $product->set_price / $product->standard_set_qty; // price per unit in set
                    }

                    $itemsToSell[] = [
                        'product' => $product,
                        'serials' => $serials,
                        'unit_price' => $unitPrice,
                        'is_set' => $isSet,
                    ];

                    $invoiceSubTotal += ($unitPrice * $qtyToSell);
                }

                if (empty($itemsToSell)) {
                    continue;
                }

                // Calculate discount and scrap trade-ins
                $discountAmount = (rand(1, 10) > 7) ? $faker->randomElement([500.00, 1000.00]) : 0.00;

                // Scrap trade-ins
                $scrapAdjustment = 0.00;
                $scrapItemsSeeded = [];
                if (rand(1, 10) > 7) { // 30% chance of scrap return
                    $scrapQty = rand(1, 2);
                    $scrapUnitValue = $faker->randomElement([800.00, 1200.00, 1500.00]);
                    $scrapValTotal = $scrapQty * $scrapUnitValue;

                    $scrapItemsSeeded[] = [
                        'scrap_type' => $faker->randomElement(['Car', 'EasyBike', 'IPS']),
                        'quantity' => $scrapQty,
                        'unit_value' => $scrapUnitValue,
                        'total_value' => $scrapValTotal,
                    ];
                    $scrapAdjustment = $scrapValTotal;
                }

                $grandTotal = max(0.00, $invoiceSubTotal - $discountAmount - $scrapAdjustment);

                // Broker commission
                $brokerCommission = 0.00;
                if ($broker) {
                    $brokerCommission = ($invoiceSubTotal - $discountAmount) * ($broker->commission_rate / 100);
                }

                // Determine checkout payment splits
                // 70% paid fully, 30% partial/credit
                $paymentAmount = $grandTotal;
                $isCredit = (rand(1, 10) > 7);
                if ($isCredit) {
                    $paymentAmount = number_format($grandTotal * $faker->randomElement([0.2, 0.5, 0.7]), 2, '.', '');
                }

                $invoiceDate = Carbon::instance($dayDate->copy()->setTime(rand(9, 18), rand(0, 59), rand(0, 59)));

                // Create Invoice
                $invoice = Invoice::create([
                    'invoice_no' => 'INV-'.$invoiceDate->format('Ymd').'-'.rand(1000, 9999),
                    'customer_id' => $customer->id,
                    'broker_id' => $broker?->id,
                    'cash_register_session_id' => $session->id,
                    'invoice_date' => $invoiceDate,
                    'sub_total' => $invoiceSubTotal,
                    'discount_amount' => $discountAmount,
                    'scrap_adjustment' => $scrapAdjustment,
                    'grand_total' => $grandTotal,
                    'invoice_status' => InvoiceStatus::Completed,
                    'notes' => 'Walk-in sale checkout',
                    'created_by' => $boy->id,
                ]);

                // Create InvoiceItems and InventoryTransactions
                foreach ($itemsToSell as $sellData) {
                    $prod = $sellData['product'];
                    $sPrice = $sellData['unit_price'];

                    foreach ($sellData['serials'] as $serial) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'product_id' => $prod->id,
                            'battery_serial_id' => $serial->id,
                            'sale_price' => $sPrice,
                            'warranty_months' => $prod->warranty_months,
                        ]);

                        $inventoryService->recordSale($serial, $showroom, $invoice, $boy);
                    }
                }

                // Create Scrap collections if any
                $scrapCollections = collect([]);
                foreach ($scrapItemsSeeded as $scrapItem) {
                    $scrapCollections->push(ScrapCollection::create([
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'warehouse_id' => $scrapYard->id,
                        'scrap_type' => $scrapItem['scrap_type'],
                        'quantity' => $scrapItem['quantity'],
                        'unit_value' => $scrapItem['unit_value'],
                        'total_value' => $scrapItem['total_value'],
                        'status' => 'InWarehouse',
                        'created_by' => $boy->id,
                    ]));
                }

                // Payments, cashbook logs
                $paymentsRecorded = collect([]);
                if ($paymentAmount > 0) {
                    $paymentMethod = $faker->randomElement([
                        PaymentMethod::Cash, PaymentMethod::Cash, PaymentMethod::Cash, // Cash is more common
                        PaymentMethod::Bkash, PaymentMethod::Nagad,
                        PaymentMethod::Bank, PaymentMethod::Cheque,
                    ]);

                    $serviceCharge = 0.00;
                    if ($paymentMethod === PaymentMethod::Bkash) {
                        $serviceCharge = $paymentAmount * 0.0185;
                    } elseif ($paymentMethod === PaymentMethod::Nagad) {
                        $serviceCharge = $paymentAmount * 0.0150;
                    }

                    $payment = Payment::create([
                        'customer_id' => $customer->id,
                        'cash_register_session_id' => $session->id,
                        'scrap_collection_id' => $scrapCollections->first()?->id,
                        'payment_date' => $invoiceDate,
                        'payment_method' => $paymentMethod,
                        'total_amount' => $paymentAmount,
                        'service_charge' => $serviceCharge,
                        'reference_no' => $faker->numerify('REF-#######'),
                        'notes' => 'Checkout checkout split payment',
                        'received_by' => $boy->id,
                    ]);

                    PaymentAllocation::create([
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                        'allocated_amount' => $paymentAmount,
                    ]);

                    $paymentsRecorded->push($payment);

                    // Add to cashbook flow
                    CashbookEntry::create([
                        'cash_register_session_id' => $session->id,
                        'entry_type' => 'Sale',
                        'direction' => TransactionDirection::In,
                        'payment_method' => $paymentMethod,
                        'amount' => $paymentAmount,
                        'reference_type' => Payment::class,
                        'reference_id' => $payment->id,
                        'notes' => 'POS payment for invoice #'.$invoice->invoice_no,
                        'created_by' => $boy->id,
                    ]);

                    if ($paymentMethod === PaymentMethod::Cash) {
                        $cashInflows->push((float) $paymentAmount);
                    }

                    // If cheque, create PostDatedCheque
                    if ($paymentMethod === PaymentMethod::Cheque) {
                        $maturityDays = rand(7, 30);
                        PostDatedCheque::create([
                            'customer_id' => $customer->id,
                            'payment_id' => $payment->id,
                            'cheque_number' => $faker->numerify('CHQ-#######'),
                            'bank_name' => $faker->randomElement(['BRAC Bank', 'Sonali Bank', 'City Bank', 'Islami Bank', 'DBBL']),
                            'amount' => $paymentAmount,
                            'maturity_date' => Carbon::instance($invoiceDate->copy()->addDays($maturityDays)),
                            'status' => PdcStatus::Pending,
                        ]);
                    }
                }

                // Record Customer Ledger Entries
                $ledgerService->recordCustomerTransaction(
                    $customer,
                    $grandTotal, // debit
                    0.00, // credit
                    'Invoice',
                    $invoice,
                    'Invoice grand total #'.$invoice->invoice_no,
                    $invoiceDate
                );

                foreach ($paymentsRecorded as $pRec) {
                    $ledgerService->recordCustomerTransaction(
                        $customer,
                        0.00, // debit
                        $pRec->total_amount, // credit
                        'Payment',
                        $pRec,
                        'Payment logged on POS for invoice #'.$invoice->invoice_no,
                        $invoiceDate
                    );
                }

                // Record Broker commission ledger
                if ($broker && $brokerCommission > 0) {
                    $ledgerService->recordBrokerTransaction(
                        $broker,
                        0.00, // debit
                        $brokerCommission, // credit (increases commission balance)
                        'Commission',
                        $invoice,
                        'Broker commission earned for invoice #'.$invoice->invoice_no,
                        $invoiceDate
                    );
                }
            }

            // Seed daily expenses under this session (1 expense every 2nd session)
            if ($dayIndex % 2 === 0) {
                $category = $expenseCategories->random();
                $expAmount = $faker->randomElement([200.00, 500.00, 1500.00]);

                $expenseDate = Carbon::instance($dayDate->copy()->setTime(rand(10, 17), rand(0, 59)));

                Expense::create([
                    'expense_category_id' => $category->id,
                    'cash_register_session_id' => $session->id,
                    'amount' => $expAmount,
                    'payment_method' => PaymentMethod::Cash,
                    'expense_date' => $expenseDate,
                    'notes' => 'Daily shop expense: '.$category->name,
                    'created_by' => $boy->id,
                ]); // The booted created hook automatically creates a CashbookEntry direction Out!

                $cashOutflows->push((float) $expAmount);
            }

            // Seed supplier payments (1 supplier payment every 4th session)
            if ($dayIndex % 4 === 0) {
                $supplier = $suppliers->random();
                $payAmount = $faker->randomElement([15000.00, 20000.00, 30000.00]);

                $spDate = Carbon::instance($dayDate->copy()->setTime(rand(11, 16), rand(0, 59)));

                SupplierPayment::create([
                    'supplier_id' => $supplier->id,
                    'cash_register_session_id' => $session->id,
                    'payment_date' => $spDate,
                    'payment_method' => PaymentMethod::Cash,
                    'amount' => $payAmount,
                    'reference_no' => $faker->numerify('SUPPAY-#######'),
                    'notes' => 'Payout made to supplier',
                    'created_by' => $boy->id,
                ]); // The booted created hook automatically creates a SupplierLedger debit & CashbookEntry direction Out!

                $cashOutflows->push((float) $payAmount);
            }

            // Seed Stock Transfers (1 stock transfer every 5th session)
            if ($dayIndex % 5 === 0) {
                $product = $products->random();
                $serialToTransfer = BatterySerial::where('product_id', $product->id)
                    ->where('current_status', BatteryStatus::InStock)
                    ->whereNotIn('id', $usedSerialIds)
                    ->first();

                if ($serialToTransfer) {
                    $usedSerialIds[] = $serialToTransfer->id;
                    $transferDate = Carbon::instance($dayDate->copy()->setTime(rand(10, 15), rand(0, 59)));

                    $transfer = StockTransfer::create([
                        'source_warehouse_id' => $godown->id,
                        'destination_warehouse_id' => $showroom->id,
                        'transfer_date' => $transferDate,
                        'status' => 'Completed',
                        'notes' => 'Stock replenishment transfer',
                        'created_by' => $boy->id,
                    ]);

                    // Update serial warehouse using inventory service
                    $inventoryService->recordTransferOut($serialToTransfer, $godown, $transfer, $boy);
                    $inventoryService->recordTransferIn($serialToTransfer, $showroom, $transfer, $boy);
                }
            }

            // Seed Post-sale Customer Payments / Credit collections (1 every 2nd session)
            if ($dayIndex % 2 === 1) {
                // Find a customer with positive outstanding balance
                $customerToCollect = $customers->shuffle()->first();
                $latestLedger = CustomerLedger::where('customer_id', $customerToCollect->id)->latest('id')->first();
                $dues = $latestLedger ? (float) $latestLedger->running_balance : 0.00;

                if ($dues > 1000) {
                    $collectionAmount = number_format($dues * $faker->randomElement([0.3, 0.5, 0.8]), 2, '.', '');
                    $collDate = Carbon::instance($dayDate->copy()->setTime(rand(10, 18), rand(0, 59)));

                    $collectionPayment = Payment::create([
                        'customer_id' => $customerToCollect->id,
                        'cash_register_session_id' => $session->id,
                        'payment_date' => $collDate,
                        'payment_method' => PaymentMethod::Cash,
                        'total_amount' => $collectionAmount,
                        'service_charge' => 0.00,
                        'notes' => 'Post-sale credit collection from customer',
                        'received_by' => $boy->id,
                    ]);

                    // Allocate to unpaid customer invoices in FIFO order
                    $remainingCollect = (float) $collectionAmount;
                    $unpaidInvoices = Invoice::where('customer_id', $customerToCollect->id)
                        ->orderBy('invoice_date', 'asc')
                        ->get()
                        ->filter(function (Invoice $inv) {
                            // Calculate current outstanding on invoice
                            $allocated = PaymentAllocation::where('invoice_id', $inv->id)->sum('allocated_amount');

                            return $allocated < $inv->grand_total;
                        });

                    foreach ($unpaidInvoices as $unpInv) {
                        if ($remainingCollect <= 0) {
                            break;
                        }

                        $allocatedSum = PaymentAllocation::where('invoice_id', $unpInv->id)->sum('allocated_amount');
                        $invoiceDues = $unpInv->grand_total - $allocatedSum;

                        $allocAmount = min($remainingCollect, $invoiceDues);

                        PaymentAllocation::create([
                            'payment_id' => $collectionPayment->id,
                            'invoice_id' => $unpInv->id,
                            'allocated_amount' => $allocAmount,
                        ]);

                        $remainingCollect -= $allocAmount;
                    }

                    // Cashbook entry
                    CashbookEntry::create([
                        'cash_register_session_id' => $session->id,
                        'entry_type' => 'Collection',
                        'direction' => TransactionDirection::In,
                        'payment_method' => PaymentMethod::Cash,
                        'amount' => $collectionAmount,
                        'reference_type' => Payment::class,
                        'reference_id' => $collectionPayment->id,
                        'notes' => 'Collection for outstanding customer balance',
                        'created_by' => $boy->id,
                    ]);

                    $cashInflows->push((float) $collectionAmount);

                    // Customer Ledger entry
                    $ledgerService->recordCustomerTransaction(
                        $customerToCollect,
                        0.00, // debit
                        $collectionAmount, // credit
                        'Payment',
                        $collectionPayment,
                        'Collection payment received',
                        $collDate
                    );
                }
            }

            // Seed Cheque maturity clearance (1 matured cheque cleared/bounced every 3rd session)
            if ($dayIndex % 3 === 0) {
                // Find a pending cheque that is due on or before today
                $chequeToProcess = PostDatedCheque::where('status', PdcStatus::Pending)
                    ->where('maturity_date', '<=', $dayDate)
                    ->first();

                if ($chequeToProcess) {
                    $processDate = Carbon::instance($dayDate->copy()->setTime(rand(10, 14), rand(0, 59)));
                    $shouldClear = (rand(1, 10) > 2); // 80% clear, 20% bounce

                    if ($shouldClear) {
                        $chequeToProcess->update([
                            'status' => PdcStatus::Cleared,
                            'cleared_date' => $processDate,
                        ]);

                        // Bank deposit Cashbook entry
                        CashbookEntry::create([
                            'cash_register_session_id' => $session->id,
                            'entry_type' => 'ChequeClearance',
                            'direction' => TransactionDirection::In,
                            'payment_method' => PaymentMethod::Bank,
                            'amount' => $chequeToProcess->amount,
                            'reference_type' => PostDatedCheque::class,
                            'reference_id' => $chequeToProcess->id,
                            'notes' => 'Matured cheque cleared #'.$chequeToProcess->cheque_number,
                            'created_by' => $boy->id,
                        ]);
                    } else {
                        // Bounce cheque
                        $chequeToProcess->update([
                            'status' => PdcStatus::Bounced,
                            'bounce_reason' => 'Insufficient funds in customer account',
                        ]);

                        // Log SMS notification
                        NotificationLog::create([
                            'recipient' => $chequeToProcess->customer->mobile,
                            'notification_type' => 'SMS',
                            'payload' => "Dear {$chequeToProcess->customer->name}, your cheque #{$chequeToProcess->cheque_number} of BDT {$chequeToProcess->amount} has bounced.",
                            'delivery_status' => 'logged',
                        ]);
                    }
                }
            }

            // Seed Warranty Claims & Buffer Battery Issues (1 claim every 4th session)
            if ($dayIndex % 4 === 1) {
                // Find a sold invoice item whose serial is currently in Sold status
                $soldItem = InvoiceItem::whereHas('serial', function ($query) {
                    $query->where('current_status', BatteryStatus::Sold);
                })->inRandomOrder()->first();
                if ($soldItem) {
                    $claimDate = Carbon::instance($dayDate->copy()->setTime(rand(10, 16), rand(0, 59)));

                    $claim = WarrantyClaim::create([
                        'invoice_item_id' => $soldItem->id,
                        'battery_serial_id' => $soldItem->battery_serial_id,
                        'claim_no' => 'WRN-'.$claimDate->format('Ymd').'-'.rand(1000, 9999),
                        'claim_date' => $claimDate,
                        'customer_issue' => 'Battery not holding charge / charging issue',
                        'claim_status' => ClaimStatus::Received,
                        'created_by' => $boy->id,
                    ]);

                    // Record Warranty Claim transaction in stock (returns to showroom)
                    $inventoryService->recordWarrantyIn($soldItem->serial, $showroom, $claim, $boy);

                    // 50% chance of loaning a buffer battery
                    if (rand(1, 2) === 1) {
                        // Find a buffer battery (we will use a product in stock as a buffer)
                        $bufferSerial = BatterySerial::where('product_id', $soldItem->product_id)
                            ->where('current_status', BatteryStatus::InStock)
                            ->whereNotIn('id', $usedSerialIds)
                            ->first();

                        if ($bufferSerial) {
                            $usedSerialIds[] = $bufferSerial->id;
                            BufferBatteryIssue::create([
                                'warranty_claim_id' => $claim->id,
                                'battery_serial_id' => $bufferSerial->id,
                                'issued_date' => $claimDate,
                                'created_by' => $boy->id,
                            ]);

                            $bufferSerial->update(['current_status' => BatteryStatus::BufferIssued]);

                            // Log inventory outflow
                            InventoryTransaction::create([
                                'battery_serial_id' => $bufferSerial->id,
                                'warehouse_id' => $showroom->id,
                                'transaction_type' => TransactionType::BufferIssue,
                                'reference_type' => WarrantyClaim::class,
                                'reference_id' => $claim->id,
                                'created_by' => $boy->id,
                            ]);
                        }
                    }

                    // Automatically resolve claims created in past sessions (older than 15 days)
                    $claimsToResolve = WarrantyClaim::where('claim_status', ClaimStatus::Received)
                        ->where('claim_date', '<=', Carbon::instance($dayDate->copy()->subDays(15)))
                        ->get();

                    foreach ($claimsToResolve as $oldClaim) {
                        $resDate = Carbon::instance($dayDate->copy()->setTime(rand(10, 16), rand(0, 59)));

                        // Decide result: 70% replace, 30% reject
                        $isApproved = (rand(1, 10) > 3);
                        if ($isApproved) {
                            // Find a replacement battery in stock
                            $repSerial = BatterySerial::where('product_id', $oldClaim->invoiceItem->product_id)
                                ->where('current_status', BatteryStatus::InStock)
                                ->whereNotIn('id', $usedSerialIds)
                                ->first();

                            if ($repSerial) {
                                $usedSerialIds[] = $repSerial->id;
                                $oldClaim->update([
                                    'claim_status' => ClaimStatus::Resolved,
                                    'resolved_date' => $resDate,
                                    'replacement_battery_serial_id' => $repSerial->id,
                                    'resolution_notes' => 'Replaced faulty battery with a brand new unit.',
                                ]);

                                // Log stock transactions
                                $inventoryService->recordWarrantyOut($repSerial, $showroom, $oldClaim, $boy);

                                // If buffer battery was issued, mark it returned
                                $bufIssue = $oldClaim->bufferIssues()->whereNull('returned_date')->first();
                                if ($bufIssue) {
                                    $bufIssue->update(['returned_date' => $resDate]);
                                    $bufIssue->bufferSerial->update(['current_status' => BatteryStatus::InStock]);

                                    InventoryTransaction::create([
                                        'battery_serial_id' => $bufIssue->battery_serial_id,
                                        'warehouse_id' => $showroom->id,
                                        'transaction_type' => TransactionType::BufferReturn,
                                        'reference_type' => WarrantyClaim::class,
                                        'reference_id' => $oldClaim->id,
                                        'created_by' => $boy->id,
                                    ]);
                                }
                            }
                        } else {
                            $oldClaim->update([
                                'claim_status' => ClaimStatus::Rejected,
                                'resolved_date' => $resDate,
                                'resolution_notes' => 'Battery tested. No manufacturing defect found. Physical damage detected.',
                            ]);

                            $oldClaim->faultySerial->update(['current_status' => BatteryStatus::Sold]); // return the same faulty one back to customer

                            // If buffer battery was issued, mark it returned
                            $bufIssue = $oldClaim->bufferIssues()->whereNull('returned_date')->first();
                            if ($bufIssue) {
                                $bufIssue->update(['returned_date' => $resDate]);
                                $bufIssue->bufferSerial->update(['current_status' => BatteryStatus::InStock]);

                                InventoryTransaction::create([
                                    'battery_serial_id' => $bufIssue->battery_serial_id,
                                    'warehouse_id' => $showroom->id,
                                    'transaction_type' => TransactionType::BufferReturn,
                                    'reference_type' => WarrantyClaim::class,
                                    'reference_id' => $oldClaim->id,
                                    'created_by' => $boy->id,
                                ]);
                            }
                        }
                    }
                }
            }

            // Seed Scrap Disposals (1 disposal every 10th session)
            if ($dayIndex % 10 === 0) {
                // Find some InWarehouse scrap collections
                $scraps = ScrapCollection::where('status', 'InWarehouse')->limit(5)->get();
                if ($scraps->count() > 0) {
                    $dispDate = Carbon::instance($dayDate->copy()->setTime(rand(11, 15), rand(0, 59)));
                    $supplier = $suppliers->random();

                    $dispTotal = $scraps->sum('total_value');

                    $disposal = ScrapDisposal::create([
                        'supplier_id' => $supplier->id,
                        'disposal_date' => $dispDate,
                        'payment_method' => 'cash',
                        'total_received' => $dispTotal,
                        'notes' => 'Bulk scrap collection sale to manufacturer factory',
                        'created_by' => $boy->id,
                    ]);

                    foreach ($scraps as $sc) {
                        $sc->update([
                            'status' => 'Disposed',
                            'scrap_disposal_id' => $disposal->id,
                        ]);
                    }

                    // Cashbook entry
                    CashbookEntry::create([
                        'cash_register_session_id' => $session->id,
                        'entry_type' => 'ScrapDisposal',
                        'direction' => TransactionDirection::In,
                        'payment_method' => PaymentMethod::Cash,
                        'amount' => $dispTotal,
                        'reference_type' => ScrapDisposal::class,
                        'reference_id' => $disposal->id,
                        'notes' => 'Scrap disposal payout received',
                        'created_by' => $boy->id,
                    ]);

                    $cashInflows->push((float) $dispTotal);

                    // Supplier Ledger entry (manufacturer buy-back reduces supplier payable balance)
                    $ledgerService->recordSupplierTransaction(
                        $supplier,
                        $dispTotal, // debit (reduces payable)
                        0.00, // credit
                        'Disposal',
                        $disposal,
                        'Scrap core buy-back credit adjustment',
                        $dispDate
                    );
                }
            }

            // Seed Loans transactions (1 loan borrowed or repaid every 8th session)
            if ($dayIndex % 8 === 0) {
                $loanDate = Carbon::instance($dayDate->copy()->setTime(rand(10, 15), rand(0, 59)));

                // Let's decide if we open a new loan account (30% chance) or repay an existing loan (70%)
                $shouldCreateLoan = (rand(1, 10) > 7 || LoanAccount::count() === 0);
                if ($shouldCreateLoan) {
                    $lenderType = $faker->randomElement([LenderType::External, LenderType::Staff]);
                    $lenderRefId = ($lenderType === LenderType::Staff) ? $boys->random()->id : $admin->id;
                    $pAmt = $faker->randomElement([50000.00, 100000.00, 150000.00]);

                    $loanAcc = LoanAccount::create([
                        'lender_type' => $lenderType,
                        'lender_reference_id' => $lenderRefId,
                        'loan_name' => $lenderType === LenderType::External ? 'Lender Loan - '.$faker->lastName() : 'Staff Advance Loan - '.User::find($lenderRefId)->name,
                        'principal_amount' => $pAmt,
                        'outstanding_balance' => 0.00,
                        'start_date' => $loanDate,
                        'notes' => 'Emergency cash advance for shop capital',
                    ]);

                    $loanTrans = new LoanTransaction([
                        'loan_account_id' => $loanAcc->id,
                        'transaction_date' => $loanDate,
                        'transaction_type' => 'Disbursement',
                        'amount' => $pAmt,
                        'notes' => 'Initial capital disbursement received',
                    ]);
                    $loanTrans->payment_method = PaymentMethod::Cash; // pass to custom hook
                    $loanTrans->save(); // This booted created hook automatically updates outstanding balance & logs CashbookEntry direction In!

                    $cashInflows->push((float) $pAmt);
                } else {
                    $loanAcc = LoanAccount::where('outstanding_balance', '>', 0)->inRandomOrder()->first();
                    if ($loanAcc) {
                        $repayAmt = min($loanAcc->outstanding_balance, $faker->randomElement([5000.00, 10000.00, 20000.00]));

                        $loanTrans = new LoanTransaction([
                            'loan_account_id' => $loanAcc->id,
                            'transaction_date' => $loanDate,
                            'transaction_type' => 'Repayment',
                            'amount' => $repayAmt,
                            'notes' => 'Loan repayment installment',
                        ]);
                        $loanTrans->payment_method = PaymentMethod::Cash; // pass to custom hook
                        $loanTrans->save(); // This booted created hook automatically updates outstanding balance & logs CashbookEntry direction Out!

                        $cashOutflows->push((float) $repayAmt);
                    }
                }
            }

            // Close session if not today
            if (! $isToday) {
                // Calculate expected cash = opening + inflows - outflows
                $expectedCash = $openingCash + $cashInflows->sum() - $cashOutflows->sum();
                $closingCash = $expectedCash; // set exactly matching to avoid shortage/excess on simulated history

                // Construct realistic denominations count
                // 1000, 500, 200, 100, 50, 20, 10, 5, 2, 1
                $remainingCash = (int) $closingCash;
                $denoms = [1000 => 0, 500 => 0, 200 => 0, 100 => 0, 50 => 0, 20 => 0, 10 => 0, 5 => 0, 2 => 0, 1 => 0];
                foreach ([1000, 500, 200, 100, 50, 20, 10, 5, 2, 1] as $note) {
                    if ($remainingCash >= $note) {
                        $count = (int) ($remainingCash / $note);
                        $denoms[$note] = $count;
                        $remainingCash -= ($count * $note);
                    }
                }

                $session->update([
                    'closed_at' => Carbon::instance($dayDate->copy()->setTime(19, 0, 0)),
                    'expected_cash' => $expectedCash,
                    'closing_cash' => $closingCash,
                    'shortage_excess' => 0.00,
                    'denominations' => $denoms,
                    'notes' => 'Register session closed. Reconciliation completed successfully.',
                ]);
            }

            auth()->logout();
        }

        $this->command->info('11. Database Seeding completed successfully.');
    }
}
