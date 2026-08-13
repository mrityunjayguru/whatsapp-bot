<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            'Website',
            'Google Search',
            'Google Ads',
            'LinkedIn',
            'Facebook',
            'Instagram',
            'WhatsApp',
            'Email Campaign',
            'Referral',
            'Existing Client',
            'JustDial/IndiaMART',
            'Walk-in',
        ];

        foreach ($sources as $name) {
            DB::table('lead_sources')->updateOrInsert(
                ['name' => $name],
                ['name' => $name, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
