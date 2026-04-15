<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VehicleType;
use App\Models\VehicleCategory;

class VehicleTypeController extends Controller
{
    public function getAllTypesList(){
        hasPermission('vehicle-types');
        return view('admin.vehicle-types');
    }

    public function getAllTypes()
    {
        $types = VehicleType::where('is_deleted', 0)->get();
        return response()->json([
            'status' => true,
            'data' => $types,
        ], 200);
    }

    public function getVehicleTypes(Request $request)
    {
        $type = VehicleType::find($request->id);
        return response()->json([
            'data' => $type,
            'status' => true,
        ]);
    }

    public function createVehicleTypes(Request $request)
    {
        $type = new VehicleType();
        $type->name = $request->name;
        $type->convenience_fees = $request->c_fees;
        $type->save();
        logAdminActivity("Vehicle Type Creation", $type, NULL);

        return response()->json([
            'data' => $type,
            'status' => true,
            'message' => 'Vehicle created successfully.',
        ]);
    }

    public function updateVehicleTypes(Request $request)
    {
        $type = VehicleType::where('type_id', $request->id)->first();
        $oldVal = clone $type;
        $type->name = $request->name;
        $type->convenience_fees = $request->c_fees;
        $type->save();
        $newVal = $type;

        $differences = compareArray($oldVal, $newVal);
        if(isset($differences) && is_countable($differences) && count($differences) > 0)
            logAdminActivity("Vehicle Type Updation", $oldVal, $newVal);

        return response()->json([
            'data' => $type,
            'status' => true,
            'message' => 'Vehicle updated successfully.',
        ]);
    }

    public function deleteVehicleTypes(Request $request)
    {
        $message = 'Something went Wrong';
        $status = false;
        $vehicleType = VehicleType::find($request->id);

        $vehicleTypeCnt = VehicleCategory::where('vehicle_type_id', $request->id)->count();
        if($vehicleTypeCnt > 0){
            $message = 'You can not delete Vehicle Type due to its associated with any category.';
            $status = false;
        }else{
            $vehicleType->is_deleted = 1;
            $vehicleType->save();
            $message = 'Vehicle Type deleted successfully.';
            $status = true;
        }
        logAdminActivity("Vehicle Type Deletion", $vehicleType, NULL);

        return response()->json([
            'data' => $vehicleType,
            'message' => $message,
            'status' => $status,
        ]);

    }

    public function checkVehicleTypes(Request $request){
        $vehicleType = VehicleType::where('name',  $request->value)->where('is_deleted', 0);
        if (isset($request->id) && $request->id != '') {
           $vehicleType = $vehicleType->where('type_id','!=',$request->id);
        }
        $typeCount = $vehicleType->where('is_deleted',0)->count();        
        if ($typeCount > 0) {
            return false;
        }
        return true;        
    }
}
