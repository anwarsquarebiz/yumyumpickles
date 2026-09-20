<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 32);

            /*
             | The remote gateway's identifier. Unique per gateway so a repeated
             | webhook delivery can be recognised and ignored.
             */
            $table->string('gateway_reference', 191)->nullable();

            $table->string('status', 24)->default(PaymentStatus::Pending->value);
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('USD');
            $table->text('redirect_url')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_reference']);
            $table->index('status');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
