<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('interested_in_id')->nullable()->after('lead_source_id');
            $table->foreign('interested_in_id')->references('id')->on('interested_ins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['interested_in_id']);
            $table->dropColumn('interested_in_id');
        });
    }
};
