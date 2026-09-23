<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('channel')->default('whatsapp')->after('tenant_id');
            $table->string('widget_token')->nullable()->after('channel');
        });
        // Raw SQL instead of ->change() - doctrine/dbal isn't installed,
        // which ->change() requires. MySQL syntax (matches DB_CONNECTION
        // in .env).
        DB::statement('ALTER TABLE conversations MODIFY whatsapp_phone_number_id BIGINT UNSIGNED NULL');

        Schema::table('messages', function (Blueprint $table) {
            $table->string('channel')->default('whatsapp')->after('tenant_id');
            // meta_message_id is required today, but a widget message has
            // no Meta id - MetaWebhookController/ConversationController
            // both already fall back to uniqid() when there's no real
            // one, so a synthetic id ('web_...') satisfies this the same
            // way without a schema change.
        });
        DB::statement('ALTER TABLE messages MODIFY whatsapp_phone_number_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['channel', 'widget_token']);
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};
