<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{City, Branch};

class CityController extends Controller
{
    public function getAllCityList(){
        hasPermission('cities');
        return view('admin.cities');
    }

    public function getAllCities(Request $request)
    {
        $cities = City::where('is_deleted', 0)->get();
        return response()->json([
            'data' => $cities,
            'status' => true,
        ]);
    }

    public function createCity(Request $request)
    {
        $city = new City();
        $city->name = $request->name;
        $city->latitude = $request->latitude;
        $city->longitude = $request->longitude;
        $city->save();
        logAdminActivity("City Creation", $city);

        return response()->json([
            'data' => $city,
            'status' => true,
            'message' => 'City created successfully.',
        ]);
    }

    public function getCity(Request $request)
    {
        $city = City::find($request->id);
        return response()->json([
            'data' => $city,
            'status' => true,
        ]);
    }

    public function updateCity(Request $request)
    {
        $city = City::find($request->id);
        $oldVal = clone $city;

        $city->name = $request->name;
        $city->latitude = $request->latitude;
        $city->longitude = $request->longitude;
        $city->save();

        $newVal = $city;
        $differences = compareArray($oldVal, $newVal);
        if(isset($differences) && is_countable($differences) && count($differences) > 0){
            logAdminActivity('City Updation', $oldVal, $newVal);
        }

        return response()->json([
            'data' => $city,
            'status' => true,
            'message' => 'City updated successfully.',
        ]);
    }

    public function deleteCity(Request $request)
    {
        $message = 'Something went Wrong';
        $status = false;
        $city = City::find($request->id);
        $branchCnt = Branch::where('city_id', $request->id)->count();
        if($branchCnt > 0){
            $message = 'You can not delete this City due to its associated with any Branch.';
            $status = false;
        }else{
            $city->is_deleted = 1;
            $city->save();
            $message = 'City deleted successfully.';
            $status = true;
            logAdminActivity("City Deletion", $city);
        }

        return response()->json([
            'data' => $city,
            'message' => $message,
            'status' => $status,
        ]);

    }

}
