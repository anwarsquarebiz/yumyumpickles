<?php

use App\Enums\RefundStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();

            /*
             | The staff member who issued the refund.
             */
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedBigInteger('amount');
            $table->string('reason')->nullable();
            $table->string('status', 24)->default(RefundStatus::Pending->value);
            $table->string('gateway_reference', 191)->nullable();
            $table->boolean('restock')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
