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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('whatsapp_phone_number_id');
            $table->string('meta_message_id');
            $table->string('reply_to_meta_message_id')->nullable();
            $table->string('message_type');
            $table->string('direction');
            $table->string('sender_type');
            $table->unsignedBigInteger('tenant_user_id')->nullable();
            $table->text('message_text')->nullable();
            $table->string('media_id')->nullable();
            $table->string('media_url')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('file_name')->nullable();
            $table->string('caption')->nullable();
            $table->string('status');
            $table->string('failure_reason')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_forwarded')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_edited')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
