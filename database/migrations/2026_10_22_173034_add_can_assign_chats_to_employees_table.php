<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'can_assign_chats')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->boolean('can_assign_chats')->default(false)->after('role_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'can_assign_chats')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('can_assign_chats');
            });
        }
    }
};
