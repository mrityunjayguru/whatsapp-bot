<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Null = your own internal team (Design Demonz) - sees every
            // company. Set = an agent for exactly one client company,
            // scoped to only that company's conversations (enforced in a
            // later stage's auth middleware/policies - this migration
            // just adds the column so that stage has something to scope
            // against).
            $table->foreignId('company_id')->nullable()->after('id')
                ->constrained('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
