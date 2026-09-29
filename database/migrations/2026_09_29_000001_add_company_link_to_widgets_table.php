<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Turns the `widgets` table from an unused stub (just id + timestamps)
     * into the real one-company-to-many-widgets link. Previously a
     * company could only ever be tied to ONE widget, via a single
     * `widget_token` column on `companies` - creating a second widget for
     * the same company silently overwrote that column, orphaning the
     * first widget (it kept running on the Python side, but the CRM lost
     * track of it: it stopped showing a company name in the widget list,
     * and company-scoped logins could only ever see their latest widget).
     *
     * is_active/valid_from/expiry_date move here too (per-widget) instead
     * of living on `companies` (shared across all of a company's
     * widgets) - this actually matches how the Python side already
     * stores them, per-token, via WidgetApiService::updateWidgetConfig().
     */
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('token')->nullable()->unique()->after('company_id');
            $table->boolean('is_active')->default(true)->after('token');
            $table->date('valid_from')->nullable()->after('is_active');
            $table->date('expiry_date')->nullable()->after('valid_from');
        });

        // Backfill: every company that already has a widget_token gets a
        // matching row here, carrying over its current active window, so
        // existing widgets keep working exactly as before after this
        // deploys - nothing existing goes dark or loses its dates.
        DB::table('companies')->whereNotNull('widget_token')->orderBy('id')->get()->each(function ($company) {
            DB::table('widgets')->insert([
                'company_id' => $company->id,
                'token' => $company->widget_token,
                'is_active' => (bool) $company->is_active,
                'valid_from' => $company->valid_from,
                'expiry_date' => $company->expiry_date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'token', 'is_active', 'valid_from', 'expiry_date']);
        });
    }
};
