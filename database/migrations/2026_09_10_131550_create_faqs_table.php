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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('faq_hash_id')->unique()->nullable();
            $table->string('question');
            $table->string('category')->nullable();
            $table->string('keywords')->nullable();
            $table->string('match_type')->nullable();
            $table->string('priority')->nullable();
            $table->text('answer');
            $table->string('attachment')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
