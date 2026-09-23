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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('employee_code');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('display_name');
            $table->string('email');
            $table->string('mobile_number')->nullable();
            $table->string('password_hash');
            $table->string('profile_photo')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->enum('role', ['ADMIN', 'MANAGER', 'EMPLOYEE'])->default('EMPLOYEE');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'INVITED', 'BLOCKED'])->default('ACTIVE');
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_online')->default(false);
            $table->integer('assigned_conversation_count')->default(0);
            $table->integer('resolved_conversation_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
