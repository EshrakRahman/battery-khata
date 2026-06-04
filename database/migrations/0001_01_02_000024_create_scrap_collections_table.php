<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove circular payments FK before dropping
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['scrap_collection_id']);
        });

        Schema::dropIfExists('scrap_collections');
    }
};
