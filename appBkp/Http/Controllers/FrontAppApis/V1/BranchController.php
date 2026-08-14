<?php

namespace App\Http\Controllers\FrontAppApis\V1;

use App\Http\Controllers\Controller; 
use App\Models\{Branch, City, Vehicle};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    public function __construct()
    {
       // $this->middleware('auth:api');
    }

    public function index()
    {
        $branches = Branch::all();
        return $this->successResponse($branches);
    }

    public function getNearestBranch(Request $request)
    {
        // Validate latitude and longitude
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Retrieve latitude and longitude from the request
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        // Example: Get the nearest branch based on latitude and longitude
        $nearestBranch = Branch::nearest($latitude, $longitude);

        return $this->successResponse($nearestBranch);
    }
    
    public function getCities(Request $request){
        $cities = [];
        $adminVehicleBranchIds = Vehicle::select('branch_id')->where('is_deleted', 0)->whereNotNull('branch_id')->distinct()->pluck('branch_id')->toArray();
        $adminVehicleCities = Branch::select('city_id')->whereIn('branch_id', $adminVehicleBranchIds)->where('is_deleted', 0)->whereNotNull('city_id')->distinct()->pluck('city_id')->toArray();
        $cities['admin'] = $adminVehicleCities;
        $hostVehicleCities = Vehicle::select('temp_city_id')->where('is_deleted', 0)->where('publish', 1)->whereNotNull('temp_city_id')->pluck('temp_city_id')->toArray();
        $hostVehicleCities = array_values(array_unique($hostVehicleCities));
        $cities['host'] = $hostVehicleCities;
        $allCityIds = array_unique(array_merge($cities['admin'], $cities['host']));
        $cities = City::select('id', 'name')->where('is_deleted', 0)->whereIn('id', $allCityIds)->get();
        if(is_countable($cities) && count($cities) > 0 ){
          return $this->successResponse($cities, 'Cities are get successfully.');
        }else{
            return $this->errorResponse('Cities are not Found');
        }
    }

    public function getNearestCity(Request $request)
    {
        // Validate latitude and longitude
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Retrieve latitude and longitude from the request
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $nearestBranch = City::nearest($latitude, $longitude);

        return $this->successResponse($nearestBranch);
    }

}
