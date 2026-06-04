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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
