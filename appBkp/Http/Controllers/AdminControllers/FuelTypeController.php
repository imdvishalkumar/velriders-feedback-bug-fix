<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FuelType;
use App\Models\VehicleType;
use App\Models\Vehicle;
use App\Models\VehicleProperty;

class FuelTypeController extends Controller
{
    public function index(Request $request){
        $vehicleTypes = VehicleType::all(); // Fetch all vehicle types
        return view('admin.fuel-types', compact('vehicleTypes'));
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'vehicleType' => 'required|exists:vehicle_types,type_id', 
        ]);
        $logStatus = '';

        if(isset($request->fId) && $request->fId != ''){
            $fuelType = FuelType::where('fuel_type_id', $request->fId)->first();    
            $logStatus = 'edit';
            $oldVal = clone $fuelType;
        }else{
            $fuelType = new FuelType();
            $logStatus = 'add';
        }
        $fuelType->name = $request->input('name');
        $fuelType->vehicle_type_id = $request->input('vehicleType');
        $fuelType->save();
        $newVal = $fuelType;
        if($logStatus != '' && $logStatus == 'add')
            logAdminActivity('Fuel Type Creation', $fuelType, NULL);
        if($logStatus != '' && $logStatus == 'edit'){
            $differences = compareArray($oldVal, $newVal);
            if(isset($differences) && is_countable($differences) && count($differences) > 0){
                logAdminActivity('Fuel Type Updation', $oldVal, $newVal);
            }
        }

        return redirect()->route('admin.fuel-types')->with('success', 'Fuel Type Set successfully!');
    }

    public function getAllFuelTypes()
    {
        $types = FuelType::with('getVehicleType')->where('is_deleted', 0)->get();
        return response()->json([
            'status' => true,
            'data' => $types,
        ], 200);
    }

    public function getFuelType(Request $request)
    {
        $fuel = FuelType::find($request->id);

        return response()->json([
            'data' => $fuel,
            'status' => true,
        ]);
    }

    public function deleteFuelTypes(Request $request)
    {
        $message = 'Something went Wrong';
        $status = false;
        $vahicleFuelType = FuelType::find($request->id);
        $vehicleFuelTypeCnt = VehicleProperty::where('fuel_type_id', $request->id)->count();
        if($vehicleFuelTypeCnt > 0){
            $message = 'You can not delete Vehicle Fuel Type due to its associated with any vehicle.';
            $status = false;
        }else{
            $vahicleFuelType->is_deleted = 1;
            $vahicleFuelType->save();    
            $message = 'Vehicle Fuel Type deleted successfully.';
            $status = true;

            logAdminActivity("Fuel Type Deletion", $vahicleFuelType, NULL);
        }

        return response()->json([
            'data' => $vahicleFuelType,
            'message' => $message,
            'status' => $status,
        ]);

    }

}
