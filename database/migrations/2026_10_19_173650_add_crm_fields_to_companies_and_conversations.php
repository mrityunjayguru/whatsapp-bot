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
        // 1. Company level settings for CRM integration
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('crm_integration_enabled')->default(false)->after('bot_usage_type');
            $table->string('crm_api_url')->nullable()->after('crm_integration_enabled');
            $table->string('crm_api_token')->nullable()->after('crm_api_url');
        });

        // 2. Conversation tracking for CRM sync
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_sent_to_crm')->default(false)->after('status');
            $table->string('crm_lead_id')->nullable()->after('is_sent_to_crm');
            $table->timestamp('sent_to_crm_at')->nullable()->after('crm_lead_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['crm_integration_enabled', 'crm_api_url', 'crm_api_token']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['is_sent_to_crm', 'crm_lead_id', 'sent_to_crm_at']);
        });
    }
};
