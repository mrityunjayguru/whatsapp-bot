<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_email')->nullable();
            $table->string('widget_token')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Every existing Conversation/Message row has tenant_id = 1001
        // (see MetaWebhookController's hardcoded placeholder). Seed a
        // company row with that SAME id, rather than migrating old data,
        // so all your existing WhatsApp conversations keep working
        // exactly as before with zero data changes - this row just
        // becomes their real "company" going forward.
        DB::table('companies')->insert([
            'id' => 1001,
            'name' => 'Track Route Pro (Internal)',
            'contact_email' => null,
            'widget_token' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
