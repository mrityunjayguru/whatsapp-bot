<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('name')->unique()->after('id');
            $table->string('display_name')->after('name');
        });

        // Seed default roles: 1 = superadmin, 2 = company
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'superadmin', 'display_name' => 'Super Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'company',    'display_name' => 'Company',     'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->truncate();
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn(['name', 'display_name']);
        });
    }
};
