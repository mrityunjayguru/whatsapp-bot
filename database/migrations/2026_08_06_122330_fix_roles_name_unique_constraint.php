<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Drop the global unique on name
            $table->dropUnique('roles_name_unique');

            // Add composite unique: same name allowed only once per company
            $table->unique(['name', 'created_by'], 'roles_name_created_by_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_created_by_unique');
            $table->unique('name', 'roles_name_unique');
        });
    }
};
