<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('published_at');
            $table->index('position');
            $table->index(['status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status', 'position']);
            $table->dropIndex(['position']);
            $table->dropColumn('position');
        });
    }
};
