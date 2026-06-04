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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_dated_cheques');
    }
};
