<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         | The order timeline. Append-only: one row per status change, plus rows
         | for notable non-status events such as a refund being issued.
         */
        Schema::create('order_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->string('note')->nullable();

            /*
             | Null when the change was made by the system, for example by an
             | inbound payment webhook rather than a staff member.
             */
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_events');
    }
};
