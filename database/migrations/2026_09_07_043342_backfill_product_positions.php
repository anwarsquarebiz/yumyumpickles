<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('products')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->pluck('id');

        $position = 1;

        foreach ($ids as $id) {
            DB::table('products')->where('id', $id)->update(['position' => $position]);
            $position++;
        }
    }

    public function down(): void
    {
        DB::table('products')->update(['position' => 0]);
    }
};
