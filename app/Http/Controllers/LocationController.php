<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Country;

class LocationController extends Controller
{
    public function getStates($countryName)
    {
        $country = Country::where('name', $countryName)->first();
        if ($country) {
            return response()->json($country->states);
        }
        return response()->json([]);
    }
}
