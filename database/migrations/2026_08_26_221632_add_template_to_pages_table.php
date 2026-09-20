<?php

use App\Enums\PageTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('template', 32)->default(PageTemplate::Default->value)->after('status');
            $table->index('template');
        });

        DB::table('pages')->where('slug', 'contact-us')->update([
            'template' => PageTemplate::Contact->value,
        ]);
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['template']);
            $table->dropColumn('template');
        });
    }
};
