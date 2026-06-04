<?php

namespace App\Services;

use App\Models\Broker;
use App\Models\BrokerLedger;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Record a customer ledger entry with transactional locking.
     */
    public function recordCustomerTransaction(
        Customer $customer,
        float|string $debit,
        float|string $credit,
        string $transactionType,
        ?Model $reference = null,
        ?string $notes = null,
        ?Carbon $transactionDate = null
    ): CustomerLedger {
        return DB::transaction(function () use (
            $customer,
            $debit,
            $credit,
            $transactionType,
            $reference,
            $notes,
            $transactionDate
        ) {
            // Lock the parent Customer row to serialize running balance updates for this customer
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            // Get the latest customer ledger entry
            $latest = CustomerLedger::where('customer_id', $lockedCustomer->id)
                ->latest('id')
                ->first();

            $prevBalance = $latest ? (float) $latest->running_balance : 0.00;

            // Customer Running Balance: outstanding balance increases with Debit (sale), decreases with Credit (payment)
            $runningBalance = $prevBalance + (float) $debit - (float) $credit;

            return CustomerLedger::create([
                'customer_id' => $lockedCustomer->id,
                'transaction_date' => $transactionDate ?? now(),
                'transaction_type' => $transactionType,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Record a supplier ledger entry with transactional locking.
     */
    public function recordSupplierTransaction(
        Supplier $supplier,
        float|string $debit,
        float|string $credit,
        string $transactionType,
        ?Model $reference = null,
        ?string $notes = null,
        ?Carbon $transactionDate = null
    ): SupplierLedger {
        return DB::transaction(function () use (
            $supplier,
            $debit,
            $credit,
            $transactionType,
            $reference,
            $notes,
            $transactionDate
        ) {
            // Lock the parent Supplier row to serialize running balance updates for this supplier
            $lockedSupplier = Supplier::where('id', $supplier->id)->lockForUpdate()->firstOrFail();

            // Get the latest supplier ledger entry
            $latest = SupplierLedger::where('supplier_id', $lockedSupplier->id)
                ->latest('id')
                ->first();

            $prevBalance = $latest ? (float) $latest->running_balance : 0.00;

            // Supplier Running Balance: our outstanding liability to them increases with Credit (purchase), decreases with Debit (payment)
            $runningBalance = $prevBalance + (float) $credit - (float) $debit;

            return SupplierLedger::create([
                'supplier_id' => $lockedSupplier->id,
                'transaction_date' => $transactionDate ?? now(),
                'transaction_type' => $transactionType,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Record a broker ledger entry with transactional locking.
     */
    public function recordBrokerTransaction(
        Broker $broker,
        float|string $debit,
        float|string $credit,
        string $transactionType,
        ?Model $reference = null,
        ?string $notes = null,
        ?Carbon $transactionDate = null
    ): BrokerLedger {
        return DB::transaction(function () use (
            $broker,
            $debit,
            $credit,
            $transactionType,
            $reference,
            $notes,
            $transactionDate
        ) {
            // Lock the parent Broker row to serialize running balance updates for this broker
            $lockedBroker = Broker::where('id', $broker->id)->lockForUpdate()->firstOrFail();

            // Get the latest broker ledger entry
            $latest = BrokerLedger::where('broker_id', $lockedBroker->id)
                ->latest('id')
                ->first();

            $prevBalance = $latest ? (float) $latest->running_balance : 0.00;

            // Broker Running Balance: commission owed increases with Credit (earned commission), decreases with Debit (payout)
            $runningBalance = $prevBalance + (float) $credit - (float) $debit;

            return BrokerLedger::create([
                'broker_id' => $lockedBroker->id,
                'transaction_date' => $transactionDate ?? now(),
                'transaction_type' => $transactionType,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'notes' => $notes,
            ]);
        });
    }
}
