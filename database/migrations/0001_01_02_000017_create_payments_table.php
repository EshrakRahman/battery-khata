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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('cash_register_session_id')->constrained('cash_register_sessions');
            $table->unsignedBigInteger('scrap_collection_id')->nullable(); // Circular link column
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
