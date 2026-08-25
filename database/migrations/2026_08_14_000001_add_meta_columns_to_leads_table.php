<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('meta_lead_id')->nullable()->unique()->after('lead_id');
            $table->string('meta_form_id')->nullable()->after('meta_lead_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique(['meta_lead_id']);
            $table->dropColumn(['meta_lead_id', 'meta_form_id']);
        });
    }
};
