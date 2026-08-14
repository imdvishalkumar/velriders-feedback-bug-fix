<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transmission;
use App\Models\VehicleType;
use App\Models\Vehicle;
use App\Models\VehicleProperty;

class VehicleTransmissionController extends Controller
{
    public function index(Request $request) {
        hasPermission('vehicle-transmissions');
        $vehicleTypes = VehicleType::all(); // Fetch all vehicle types
        return view('admin.vehicle-transmission', compact('vehicleTypes'));
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'vehicleType' => 'required|exists:vehicle_types,type_id', 
        ]);
        $logStatus = '';

        if(isset($request->tId) && $request->tId != ''){
            $transmission = Transmission::where('transmission_id', $request->tId)->first(); 
            $logStatus = 'edit';
            $oldVal = clone $transmission; 
        }else{
            $transmission = new Transmission();
            $logStatus = 'add';
        }
        $transmission->name = $request->input('name');
        $transmission->vehicle_type_id = $request->input('vehicleType');
        $transmission->save();
        $newVal = $transmission;
        if($logStatus != '' && $logStatus == 'add')
            logAdminActivity('Vehicle Transmission Creation', $transmission, NULL);
        if($logStatus != '' && $logStatus == 'edit'){
            $differences = compareArray($oldVal, $newVal);
            if(isset($differences) && is_countable($differences) && count($differences) > 0){
                logAdminActivity('Vehicle Transmission Updation', $oldVal, $newVal);
            }
        }

        return redirect()->route('admin.vehicle-transmission')->with('success', 'Vehicle Transmission Set successfully!');
    }

    public function getAllVehicleTransmission()
    {
        $transmission = Transmission::with('getVehicleType')->where('is_deleted', 0)->get();
        return response()->json([
            'status' => true,
            'data' => $transmission,
        ], 200);
    }

    public function getVehicleTransmission(Request $request)
    {
        $transmission = Transmission::find($request->id);

        return response()->json([
            'data' => $transmission,
            'status' => true,
        ]);
    }

    public function deleteVehicleTransmission(Request $request)
    {
        $message = 'Something went Wrong';
        $status = false;
        $vehicleTransmission = Transmission::find($request->id);
        $vehicleTransmissionCnt = VehicleProperty::where('transmission_id', $request->id)->count();
        if($vehicleTransmissionCnt > 0){
            $message = 'You can not delete this Vehicle Transmission due to its associated with any vehicle.';
            $status = false;
        }else{
            $vehicleTransmission->is_deleted = 1;
            $vehicleTransmission->save();
            $message = 'Vehicle Transmission deleted successfully.';
            $status = true;
            logAdminActivity("Vehicle Transmission Deletion", $vehicleTransmission, NULL);
        }

        return response()->json([
            'data' => $vehicleTransmission,
            'message' => $message,
            'status' => $status,
        ]);
    }
}
