<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Drop old enum status column
            $table->dropColumn('status');
            // Add FK to lead_statuses
            $table->unsignedBigInteger('lead_status_id')->nullable()->after('requirement');
            $table->foreign('lead_status_id')->references('id')->on('lead_statuses')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['lead_status_id']);
            $table->dropColumn('lead_status_id');
            $table->enum('status', ['New', 'Contacted', 'Interested', 'Follow-up', 'Won', 'Lost'])
                  ->default('New')
                  ->after('requirement');
        });
    }
};
