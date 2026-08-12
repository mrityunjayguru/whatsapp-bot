<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_id')->unique();                          // LEAD0001
            $table->string('lead_name');
            $table->string('company_name')->nullable();
            $table->string('phone_number');
            $table->string('whatsapp_number')->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('lead_source_id')->nullable();
            $table->text('requirement');
            $table->enum('status', ['New', 'Contacted', 'Interested', 'Follow-up', 'Won', 'Lost'])->default('New');
            $table->date('follow_up_date')->nullable();
            $table->time('follow_up_time')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->foreign('lead_source_id')->references('id')->on('lead_sources')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
