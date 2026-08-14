<?php

namespace App\Http\Controllers\FrontAppApis;

use App\Http\Controllers\Controller; 
use App\Models\{Branch, City};
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
        $cities = City::select('id', 'name')->where('is_deleted', 0)->where('id', '<', 7)->get();
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
