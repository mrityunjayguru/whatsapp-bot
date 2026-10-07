<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per client WhatsApp Business number, same shape as the
     * `widgets` table (see 2026_09_29_000001_add_company_link_to_widgets_table.php) -
     * a company can own several numbers, each with its own Meta
     * credentials and its own independent active window. Before this,
     * the WhatsApp side had no multi-tenancy at all: one shared
     * access_token/phone_number_id in .env (config/services.php's
     * 'whatsapp' block) for the WHOLE install, and one shared Python FAQ
     * store/bot config for every number that ever talked to it (see the
     * new whatsapp_config.py/whatsapp_store.py on the Python side). This
     * table plus WhatsappNumberController/MetaWebhookController's updated
     * resolution logic is what actually makes each client's number
     * independent, mirroring how the widgets table already does for
     * embeddable website widgets.
     *
     * access_token is stored encrypted (see WhatsappNumber::$casts) -
     * unlike widget tokens, this is a real secret: anyone with it can
     * send messages as that client's WhatsApp Business number.
     */
    public function up(): void
    {
        Schema::create('whatsapp_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            // Meta's own id for this number - present in every inbound
            // webhook's metadata.phone_number_id, and this number's key
            // on the Python side too (whatsapp_config.py), exactly the
            // role a widget's token plays.
            $table->string('phone_number_id')->unique();
            $table->string('waba_id')->nullable();
            $table->string('display_number')->nullable();
            // Your own reference label, e.g. "Acme Corp - Support" -
            // mirrored to the Python side too (whatsapp_config.py's
            // WhatsAppNumberConfig.label) so it's never out of sync.
            $table->string('label')->nullable();
            $table->text('access_token')->nullable();
            $table->string('graph_version')->default('v23.0');
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_numbers');
    }
};
