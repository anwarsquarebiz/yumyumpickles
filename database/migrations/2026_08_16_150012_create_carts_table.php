<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            /*
             | Opaque token stored in a signed cookie so guests keep their cart
             | across requests. The cart is claimed by the customer on login.
             */
            $table->uuid('token')->unique();

            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('USD');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
