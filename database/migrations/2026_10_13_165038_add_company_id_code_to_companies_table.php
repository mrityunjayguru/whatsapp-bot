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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('company_code')->nullable()->unique()->after('id');
        });

        // Generate DES+4digit unique code for existing companies
        $companies = \Illuminate\Support\Facades\DB::table('companies')->whereNull('company_code')->get();
        foreach ($companies as $comp) {
            do {
                $code = 'DES' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (\Illuminate\Support\Facades\DB::table('companies')->where('company_code', $code)->exists());

            \Illuminate\Support\Facades\DB::table('companies')
                ->where('id', $comp->id)
                ->update(['company_code' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('company_code');
        });
    }
};
