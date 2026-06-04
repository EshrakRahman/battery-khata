<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $product_id
 * @property string $serial_no
 * @property \App\Enums\BatteryStatus $current_status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Product|null $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InventoryTransaction> $transactions
 * @property-read int|null $transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatterySerial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatterySerial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatterySerial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatterySerial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatterySerial whereCurrentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatterySerial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatterySerial whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatterySerial whereSerialNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatterySerial whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBatterySerial {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $mobile
 * @property numeric $commission_rate
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Broker newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Broker newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Broker query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Broker whereCommissionRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Broker whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Broker whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Broker whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Broker whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Broker whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBroker {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $broker_id
 * @property \Carbon\CarbonImmutable $transaction_date
 * @property string $transaction_type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property numeric $debit
 * @property numeric $credit
 * @property numeric $running_balance
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereBrokerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereRunningBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereTransactionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereTransactionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrokerLedger whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBrokerLedger {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warranty_claim_id
 * @property int $battery_serial_id
 * @property \Carbon\CarbonImmutable $issued_date
 * @property \Carbon\CarbonImmutable|null $returned_date
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\BatterySerial $bufferSerial
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue whereBatterySerialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue whereIssuedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue whereReturnedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BufferBatteryIssue whereWarrantyClaimId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBufferBatteryIssue {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $opened_by
 * @property \Carbon\CarbonImmutable $opened_at
 * @property \Carbon\CarbonImmutable|null $closed_at
 * @property numeric $opening_cash
 * @property numeric|null $expected_cash
 * @property numeric|null $closing_cash
 * @property numeric|null $shortage_excess
 * @property array<array-key, mixed>|null $denominations
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereClosingCash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereDenominations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereExpectedCash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereOpenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereOpenedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereOpeningCash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereShortageExcess($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegisterSession whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCashRegisterSession {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cash_register_session_id
 * @property string $entry_type
 * @property \App\Enums\TransactionDirection $direction
 * @property \App\Enums\PaymentMethod $payment_method
 * @property numeric $amount
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereCashRegisterSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereEntryType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashbookEntry whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCashbookEntry {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $mobile
 * @property string|null $national_id
 * @property string|null $image_path
 * @property string|null $division
 * @property string|null $district
 * @property string|null $upazila
 * @property string|null $address
 * @property string $customer_type
 * @property numeric $credit_limit
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreditLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCustomerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereDistrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereDivision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereNationalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpazila($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCustomer {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_id
 * @property \Carbon\CarbonImmutable $transaction_date
 * @property string $transaction_type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property numeric $debit
 * @property numeric $credit
 * @property numeric $running_balance
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereRunningBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereTransactionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereTransactionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerLedger whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCustomerLedger {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $expense_category_id
 * @property int|null $cash_register_session_id
 * @property numeric $amount
 * @property \App\Enums\PaymentMethod $payment_method
 * @property \Carbon\CarbonImmutable $expense_date
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\ExpenseCategory $category
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereCashRegisterSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereExpenseCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereExpenseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperExpense {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperExpenseCategory {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $battery_serial_id
 * @property int $warehouse_id
 * @property \App\Enums\TransactionType $transaction_type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\BatterySerial $serial
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereBatterySerialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereTransactionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereWarehouseId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperInventoryTransaction {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $invoice_no
 * @property int $customer_id
 * @property int|null $broker_id
 * @property int $cash_register_session_id
 * @property \Carbon\CarbonImmutable $invoice_date
 * @property numeric $sub_total
 * @property numeric $discount_amount
 * @property numeric $scrap_adjustment
 * @property numeric $grand_total
 * @property \App\Enums\InvoiceStatus $invoice_status
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Broker|null $broker
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvoiceItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereBrokerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCashRegisterSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereScrapAdjustment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperInvoice {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $invoice_id
 * @property int $product_id
 * @property int $battery_serial_id
 * @property numeric $sale_price
 * @property int $warranty_months
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Invoice|null $invoice
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\BatterySerial $serial
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereBatterySerialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereWarrantyMonths($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperInvoiceItem {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \App\Enums\LenderType $lender_type
 * @property int $lender_reference_id
 * @property string $loan_name
 * @property numeric $principal_amount
 * @property numeric $outstanding_balance
 * @property \Carbon\CarbonImmutable $start_date
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount whereLenderReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount whereLenderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount whereLoanName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount whereOutstandingBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount wherePrincipalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAccount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLoanAccount {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $loan_account_id
 * @property \Carbon\CarbonImmutable $transaction_date
 * @property string $transaction_type
 * @property numeric $amount
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction whereLoanAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction whereTransactionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction whereTransactionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLoanTransaction {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $recipient
 * @property string $notification_type
 * @property string|null $payload
 * @property string $delivery_status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereDeliveryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereNotificationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereRecipient($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperNotificationLog {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_id
 * @property int $cash_register_session_id
 * @property int|null $scrap_collection_id
 * @property \Carbon\CarbonImmutable $payment_date
 * @property \App\Enums\PaymentMethod $payment_method
 * @property numeric $total_amount
 * @property numeric $service_charge
 * @property string|null $reference_no
 * @property string|null $notes
 * @property int $received_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PaymentAllocation> $allocations
 * @property-read int|null $allocations_count
 * @property-read \App\Models\Customer $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCashRegisterSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReferenceNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereScrapCollectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereServiceCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $payment_id
 * @property int $invoice_id
 * @property numeric $allocated_amount
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Invoice|null $invoice
 * @property-read \App\Models\Payment|null $payment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereAllocatedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPaymentAllocation {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $payment_id
 * @property string $cheque_number
 * @property string $bank_name
 * @property numeric $amount
 * @property \Carbon\CarbonImmutable $maturity_date
 * @property \Carbon\CarbonImmutable|null $deposit_date
 * @property \Carbon\CarbonImmutable|null $cleared_date
 * @property string|null $bounce_reason
 * @property \App\Enums\PdcStatus $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Payment|null $payment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereBounceReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereChequeNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereClearedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereDepositDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereMaturityDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostDatedCheque whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPostDatedCheque {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $category_id
 * @property string $brand_name
 * @property string $model_name
 * @property string|null $voltage
 * @property string|null $capacity_ah
 * @property int|null $plate_count
 * @property int $warranty_months
 * @property numeric $mrp_price
 * @property numeric $dealer_price
 * @property numeric|null $set_price
 * @property int $standard_set_qty
 * @property numeric $purchase_cost
 * @property bool $has_serial_tracking
 * @property int $alert_threshold_qty
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\ProductCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BatterySerial> $serials
 * @property-read int|null $serials_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereAlertThresholdQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBrandName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCapacityAh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDealerPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereHasSerialTracking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereMrpPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePlateCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePurchaseCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSetPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStandardSetQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereVoltage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereWarrantyMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProduct {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductCategory {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property string $invoice_no
 * @property string $purchase_date
 * @property numeric $sub_total
 * @property numeric $discount_amount
 * @property numeric $grand_total
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Supplier $supplier
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereInvoiceNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseInvoice whereWarehouseId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPurchaseInvoice {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $purchase_invoice_id
 * @property int $product_id
 * @property int $battery_serial_id
 * @property numeric $purchase_price
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\PurchaseInvoice $purchaseInvoice
 * @property-read \App\Models\BatterySerial $serial
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereBatterySerialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem wherePurchaseInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem wherePurchasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPurchaseItem {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $customer_id
 * @property int|null $invoice_id
 * @property int $warehouse_id
 * @property string $scrap_type
 * @property numeric $quantity
 * @property numeric|null $estimated_weight
 * @property numeric $unit_value
 * @property numeric $total_value
 * @property string $status
 * @property int|null $scrap_disposal_id
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\ScrapDisposal|null $scrapDisposal
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereEstimatedWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereScrapDisposalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereScrapType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereTotalValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereUnitValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection whereWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapCollection withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperScrapCollection {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $supplier_id
 * @property \Carbon\CarbonImmutable $disposal_date
 * @property string $payment_method
 * @property numeric $total_received
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal whereDisposalDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal whereTotalReceived($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapDisposal whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperScrapDisposal {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $source_warehouse_id
 * @property int $destination_warehouse_id
 * @property string $transfer_date
 * @property string $status
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Warehouse $destinationWarehouse
 * @property-read \App\Models\Warehouse $sourceWarehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereDestinationWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereSourceWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereTransferDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockTransfer {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $mobile
 * @property string|null $address
 * @property string $supplier_type
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereSupplierType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSupplier {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $supplier_id
 * @property \Carbon\CarbonImmutable $transaction_date
 * @property string $transaction_type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property numeric $debit
 * @property numeric $credit
 * @property numeric $running_balance
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereRunningBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereTransactionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereTransactionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierLedger whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSupplierLedger {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $supplier_id
 * @property int $cash_register_session_id
 * @property \Carbon\CarbonImmutable $payment_date
 * @property \App\Enums\PaymentMethod $payment_method
 * @property numeric $amount
 * @property string|null $reference_no
 * @property string|null $notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereCashRegisterSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereReferenceNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierPayment withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSupplierPayment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property ?string $phone
 * @property string $email
 * @property UserRole $role
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $location
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperWarehouse {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $invoice_item_id
 * @property int $battery_serial_id
 * @property string $claim_no
 * @property \Carbon\CarbonImmutable $claim_date
 * @property string|null $supplier_claim_no
 * @property string|null $customer_issue
 * @property \App\Enums\ClaimStatus $claim_status
 * @property \Carbon\CarbonImmutable|null $supplier_sent_date
 * @property \Carbon\CarbonImmutable|null $resolved_date
 * @property int|null $replacement_battery_serial_id
 * @property string|null $resolution_notes
 * @property int $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\BatterySerial $faultySerial
 * @property-read \App\Models\BatterySerial|null $replacementSerial
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereBatterySerialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereClaimDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereClaimNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereClaimStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereCustomerIssue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereInvoiceItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereReplacementBatterySerialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereResolutionNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereResolvedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereSupplierClaimNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereSupplierSentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarrantyClaim whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperWarrantyClaim {}
}

