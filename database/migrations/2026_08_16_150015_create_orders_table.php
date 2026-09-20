<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();

            /*
             | Nullable so guest checkout is possible; the email is always
             | captured and is what receipts are sent to.
             */
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('phone', 32)->nullable();

            $table->string('status', 24)->default(OrderStatus::Pending->value);
            $table->string('currency', 3)->default('USD');

            $table->unsignedBigInteger('subtotal_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('shipping_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('grand_total_amount')->default(0);
            $table->unsignedBigInteger('refunded_amount')->default(0);

            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 64)->nullable();

            /*
             | Addresses are stored as immutable snapshots so editing an address
             | book entry later never rewrites historical orders.
             */
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();

            $table->string('shipping_method_name')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('staff_note')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
