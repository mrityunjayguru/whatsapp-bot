<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class InterestedInSeeder extends Seeder
{
    public function run(): void
    {
        // Get company owner (role_id = 2) as created_by, or use first user
        $company = User::where('role_id', 2)->first() ?? User::first();
        $createdBy = $company?->id;

        $items = [
            'Website Development',
            'E-commerce Website',
            'Mobile App Development',
            'UI/UX Design',
            'Logo Design',
            'Branding',
            'Graphic Design',
            'ERP Development',
            'CRM Development',
            'GPS Tracking Solution',
            'Fleet Management System',
            'SaaS Product Development',
            'AI Automation',
            'Digital Marketing',
            'SEO',
            'Social Media Marketing',
            'White-label Software',
        ];

        $rows = [];
        foreach ($items as $title) {
            // Skip if already exists
            $exists = DB::table('interested_ins')->where('title', $title)->exists();
            if (!$exists) {
                $rows[] = [
                    'title'      => $title,
                    'status'     => 1,
                    'created_by' => $createdBy,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('interested_ins')->insert($rows);
        }
    }
}
