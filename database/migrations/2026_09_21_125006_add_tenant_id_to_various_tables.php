<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        Schema::table('bot_configs', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        // Set existing records to tenant_id = 1001 to avoid orphaned data
        DB::statement('UPDATE tags SET tenant_id = 1001 WHERE tenant_id IS NULL');
        DB::statement('UPDATE faqs SET tenant_id = 1001 WHERE tenant_id IS NULL');
        DB::statement('UPDATE bot_configs SET tenant_id = 1001 WHERE tenant_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('bot_configs', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
