<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeadStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = ['New', 'Contacted', 'Follow-up', 'Interested', 'Quotation Sent', 'Won', 'Lost', 'Not Interested'];
        $userIds = \App\Models\User::pluck('id')->toArray();
        foreach ($userIds as $userId) {
            foreach ($statuses as $status) {
                \App\Models\LeadStatus::firstOrCreate([
                    'name' => $status,
                    'created_by' => $userId
                ], [
                    'status' => 1
                ]);
            }
        }
    }
}
