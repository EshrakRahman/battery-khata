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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
    }
};
