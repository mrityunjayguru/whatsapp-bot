<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'hello@designdemonz.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('superadmin'),
                'role_id'  => 1, // superadmin
            ]
        );
    }
}
