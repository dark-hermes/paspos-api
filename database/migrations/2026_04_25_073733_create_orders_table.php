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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->enum('type', ['pos', 'online']);
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('customer_id')->nullable()->constrained('users');
            $table->foreignId('cashier_id')->nullable()->constrained('users');
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'qris', 'cod', 'pay_later']);
            $table->enum('payment_status', ['paid', 'unpaid', 'partial'])->default('unpaid');
            $table->enum('status', ['completed', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->string('shipping_name')->nullable();
            $table->string('shipping_receiver_name')->nullable();
            $table->string('shipping_receiver_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
