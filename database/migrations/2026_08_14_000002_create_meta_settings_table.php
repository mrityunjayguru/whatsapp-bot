<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->string('app_id')->nullable();
            $table->string('app_secret')->nullable();
            $table->string('page_id')->nullable();
            $table->text('page_access_token')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->string('graph_api_version')->default('v18.0');
            $table->unsignedBigInteger('default_created_by')->nullable()->comment('User ID jinke created_by meta leads assign honge');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('default_created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_settings');
    }
};
