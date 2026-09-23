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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('whatsapp_phone_number_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('assigned_tenant_user_id')->nullable();
            $table->string('status')->default('OPEN');
            $table->integer('unread_count')->default(0);
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->string('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('first_message_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
