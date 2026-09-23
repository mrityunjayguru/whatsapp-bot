<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Country;

class CountryStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            'India' => ['IN', [
                'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 
                'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 
                'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 
                'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 
                'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 
                'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
                'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu', 
                'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry'
            ]],
            'United States' => ['US', ['California', 'New York', 'Texas', 'Florida', 'Illinois', 'Pennsylvania', 'Ohio']],
            'United Kingdom' => ['GB', ['England', 'Scotland', 'Wales', 'Northern Ireland']],
            'Canada' => ['CA', ['Ontario', 'Quebec', 'British Columbia', 'Alberta', 'Manitoba', 'Nova Scotia']],
            'Australia' => ['AU', ['New South Wales', 'Victoria', 'Queensland', 'Western Australia', 'South Australia', 'Tasmania']],
        ];

        foreach ($countries as $countryName => $data) {
            $country = Country::create([
                'name' => $countryName,
                'code' => $data[0]
            ]);

            foreach ($data[1] as $stateName) {
                $country->states()->create([
                    'name' => $stateName
                ]);
            }
        }
    }
}
