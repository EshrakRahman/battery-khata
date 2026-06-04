<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. INDEPENDENT BASE TABLES
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('supplier_type')->default('Regular'); // Regular, Mahajon
            $table->timestamps();
        });

        Schema::create('brokers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // 2. PRODUCT CATALOG & SERIAL REGISTRY
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories');
            $table->string('brand_name');
            $table->string('model_name');
            $table->string('voltage')->nullable();
            $table->string('capacity_ah')->nullable();
            $table->integer('plate_count')->nullable();
            $table->integer('warranty_months')->default(0);
            $table->decimal('mrp_price', 12, 2);
            $table->decimal('dealer_price', 12, 2);
            $table->decimal('set_price', 12, 2)->nullable();
            $table->integer('standard_set_qty')->default(4);
            $table->decimal('purchase_cost', 12, 2)->default(0.00);
            $table->boolean('has_serial_tracking')->default(true);
            $table->integer('alert_threshold_qty')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('battery_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('serial_no')->unique();
            $table->string('current_status')->default('in_stock'); // BatteryStatus Enum
            $table->timestamps();
        });

        // 3. TRANSFERS & INVENTORY TRANSACTIONS
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_warehouse_id')->constrained('warehouses');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses');
            $table->date('transfer_date');
            $table->string('status')->default('completed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battery_serial_id')->constrained('battery_serials');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('transaction_type'); // TransactionType Enum
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 4. SUPPLIER PURCHASES SUPPLY CHAIN
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('invoice_no')->unique();
            $table->date('purchase_date');
            $table->decimal('sub_total', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('battery_serial_id')->unique()->constrained('battery_serials');
            $table->decimal('purchase_price', 12, 2);
            $table->timestamps();
        });

        // 5. CUSTOMERS (WITH PG_TRGM & GIN SEARCH INDEXES)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile')->unique();
            $table->string('national_id')->unique()->nullable();
            $table->string('image_path')->nullable();
            $table->string('division')->nullable();
            $table->string('district')->nullable();
            $table->string('upazila')->nullable();
            $table->text('address')->nullable();
            $table->string('customer_type')->default('Retail'); // Retail, Dealer, Garage
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Enable PostgreSQL fuzzy trigram matching and compile GIN indexes on lookup fields if pgsql
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX customers_name_trgm_idx ON customers USING gin (name gin_trgm_ops)');
            DB::statement('CREATE INDEX customers_mobile_trgm_idx ON customers USING gin (mobile gin_trgm_ops)');
            DB::statement('CREATE INDEX customers_national_id_trgm_idx ON customers USING gin (national_id gin_trgm_ops)');
        }

        // 6. COUNTER SESSIONS & CASHBOOK
        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opened_by')->constrained('users');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 12, 2)->default(0.00);
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('closing_cash', 12, 2)->nullable();
            $table->decimal('shortage_excess', 12, 2)->nullable();
            $table->json('denominations')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cashbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_session_id')->constrained('cash_register_sessions');
            $table->string('entry_type');
            $table->string('direction'); // In, Out
            $table->string('payment_method'); // PaymentMethod Enum
            $table->decimal('amount', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 7. POS SALES INVOICES & DECOUPLED PAYMENTS
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('broker_id')->nullable()->constrained('brokers');
            $table->foreignId('cash_register_session_id')->constrained('cash_register_sessions');
            $table->timestamp('invoice_date');
            $table->decimal('sub_total', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('scrap_adjustment', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->string('invoice_status')->default('completed'); // InvoiceStatus Enum
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('battery_serial_id')->constrained('battery_serials');
            $table->decimal('sale_price', 12, 2);
            $table->integer('warranty_months');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('cash_register_session_id')->constrained('cash_register_sessions');
            $table->unsignedBigInteger('scrap_collection_id')->nullable(); // Circular link, resolved column-only here
            $table->timestamp('payment_date');
            $table->string('payment_method'); // PaymentMethod Enum
            $table->decimal('total_amount', 12, 2);
            $table->decimal('service_charge', 12, 2)->default(0.00);
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments');
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->decimal('allocated_amount', 12, 2);
            $table->timestamps();
        });

        // 8. SYMMETRICAL RUNNING LEDGERS
        Schema::create('customer_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->timestamp('transaction_date');
            $table->string('transaction_type');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('debit', 12, 2)->default(0.00);
            $table->decimal('credit', 12, 2)->default(0.00);
            $table->decimal('running_balance', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('cash_register_session_id')->constrained('cash_register_sessions');
            $table->timestamp('payment_date');
            $table->string('payment_method'); // PaymentMethod Enum
            $table->decimal('amount', 12, 2);
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->timestamp('transaction_date');
            $table->string('transaction_type');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('debit', 12, 2)->default(0.00);
            $table->decimal('credit', 12, 2)->default(0.00);
            $table->decimal('running_balance', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('broker_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_id')->constrained('brokers');
            $table->timestamp('transaction_date');
            $table->string('transaction_type');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('debit', 12, 2)->default(0.00);
            $table->decimal('credit', 12, 2)->default(0.00);
            $table->decimal('running_balance', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 9. SCRAP BARTER ENGINE
        Schema::create('scrap_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->date('disposal_date');
            $table->string('payment_method');
            $table->decimal('total_received', 12, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('scrap_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('scrap_type');
            $table->decimal('quantity', 12, 2)->default(1.00);
            $table->decimal('estimated_weight', 12, 2)->nullable();
            $table->decimal('unit_value', 12, 2);
            $table->decimal('total_value', 12, 2);
            $table->string('status')->default('InWarehouse');
            $table->foreignId('scrap_disposal_id')->nullable()->constrained('scrap_disposals');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // Resolve circular link on payments table back to scrap_collections
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('scrap_collection_id')->references('id')->on('scrap_collections');
        });

        // 10. WARRANTY LIFECYCLE & BUFFER UNITS
        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items');
            $table->foreignId('battery_serial_id')->constrained('battery_serials');
            $table->string('claim_no')->unique();
            $table->date('claim_date');
            $table->string('supplier_claim_no')->nullable();
            $table->text('customer_issue')->nullable();
            $table->string('claim_status')->default('received'); // ClaimStatus Enum
            $table->date('supplier_sent_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->foreignId('replacement_battery_serial_id')->nullable()->constrained('battery_serials');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('buffer_battery_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_claim_id')->constrained('warranty_claims');
            $table->foreignId('battery_serial_id')->constrained('battery_serials');
            $table->date('issued_date');
            $table->date('returned_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 11. LOANS, OVERHEAD EXPENSES & POST-DATED CHEQUES
        Schema::create('loan_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('lender_type'); // LenderType Enum (Supplier, Customer, Staff, External)
            $table->unsignedBigInteger('lender_reference_id');
            $table->string('loan_name');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('outstanding_balance', 12, 2);
            $table->date('start_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained('loan_accounts');
            $table->date('transaction_date');
            $table->string('transaction_type'); // Disbursement, Repayment
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained('expense_categories');
            $table->foreignId('cash_register_session_id')->nullable()->constrained('cash_register_sessions');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method'); // PaymentMethod Enum
            $table->date('expense_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('post_dated_cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('payment_id')->nullable()->constrained('payments');
            $table->string('cheque_number');
            $table->string('bank_name');
            $table->decimal('amount', 12, 2);
            $table->date('maturity_date');
            $table->date('deposit_date')->nullable();
            $table->date('cleared_date')->nullable();
            $table->text('bounce_reason')->nullable();
            $table->string('status')->default('pending'); // PdcStatus Enum
            $table->timestamps();
        });

        // 12. ABSTRACT NOTIFICATIONS LOG
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient');
            $table->string('notification_type');
            $table->text('payload')->nullable();
            $table->string('delivery_status')->default('mocked');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in exact reverse dependency order
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('post_dated_cheques');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('loan_transactions');
        Schema::dropIfExists('loan_accounts');
        Schema::dropIfExists('buffer_battery_issues');
        Schema::dropIfExists('warranty_claims');

        // Remove circular payments FK before dropping
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['scrap_collection_id']);
        });

        Schema::dropIfExists('scrap_collections');
        Schema::dropIfExists('scrap_disposals');
        Schema::dropIfExists('broker_ledgers');
        Schema::dropIfExists('supplier_ledgers');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('customer_ledgers');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('cashbook_entries');
        Schema::dropIfExists('cash_register_sessions');

        // Drop customer trigram indexes if pgsql
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS customers_name_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS customers_mobile_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS customers_national_id_trgm_idx');
        }
        Schema::dropIfExists('customers');

        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('battery_serials');
        Schema::dropIfExists('products');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('brokers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('product_categories');
    }
};
