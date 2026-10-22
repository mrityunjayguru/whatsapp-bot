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
        // 1. Roles table scoped by tenant_id
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name'); // e.g. "Admin", "Manager", "Support Agent"
            $table->string('slug'); // e.g. "admin", "manager", "support-agent"
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // cannot be deleted if system default
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        // 2. Permissions catalog (system-defined modules and actions)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module');       // e.g. "conversations", "contacts", "faqs", "employees", etc.
            $table->string('module_label'); // e.g. "Conversations & Chat"
            $table->string('name');         // e.g. "conversations.view"
            $table->string('label');        // e.g. "View Conversations"
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique('name');
        });

        // 3. Role Permission Pivot
        Schema::create('role_permission', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

        // 4. Add role_id foreign key to employees table
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'role_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->nullable()->after('role');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'role_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('role_id');
            });
        }
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
