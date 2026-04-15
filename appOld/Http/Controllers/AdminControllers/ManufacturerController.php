<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\VehicleManufacturer;
use App\Models\VehicleType;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
{
    public function index(Request $request)
    {
        hasPermission('vehicle-manufacturers');
        $manufacturers = VehicleManufacturer::where('is_deleted', 0)->get();
        $vehicleTypes = VehicleType::all(); // Fetch all vehicle types
        return view('admin.vehicle-manufacturers', compact('vehicleTypes'));
    }

    public function getAllManufacturers(Request $request)
    {
        $manufacturers = VehicleManufacturer::where('is_deleted', 0)->get();
        return response()->json([
            'status' => true,
            'data' => $manufacturers,
        ], 200);
    }

    public function getManufacturer(Request $request)
    {
        $manufacturer = VehicleManufacturer::find($request->id);
        return response()->json([
            'status' => true,
            'data' => $manufacturer,
        ], 200);
    }

    public function deleteManufacturer(Request $request)
    {
        $manufacturer = VehicleManufacturer::find($request->input('id'));
        $manufacturer->is_deleted = 1;
        $manufacturer->save();
        return response()->json([
            'status' => true,
            'message' => 'Manufacturer deleted successfully',
        ], 200);
    }

    public function store(Request $request)
    {
        $rules = [
            'manufacturerName' => 'required|string|max:255',
            'vehicleType' => 'required|exists:vehicle_types,type_id', 
        ];
        $logStatus = '';
        if(isset($request->mId) && $request->mId != ''){
            $manufacturer = VehicleManufacturer::where('manufacturer_id', $request->mId)->first();

            if($request->hasFile('manufacturerLogo')){
                if(isset($manufacturer->logo) && $manufacturer->logo != ''){
                    $parsedUrl = parse_url($manufacturer->logo);
                    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                    $path = public_path($path);    
                    if (file_exists($path)){
                        gc_collect_cycles();
                        unlink($path);
                    } 
                }
            }
            $rules['manufacturerLogo'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:20480';
            $logStatus = 'edit';
            $manufactureLogo = $manufacturer->logo;
            $manufactureLogo = basename($manufactureLogo);
            unset($manufacturer->logo);
            $oldVal = clone $manufacturer;
        }
        else{
            $rules['manufacturerLogo'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20480';
            $manufacturer = new VehicleManufacturer();
            $logStatus = 'add';
        }
        $request->validate($rules);

        // Handle file upload for logo
        if ($request->hasFile('manufacturerLogo')) {
            $file = $request->file('manufacturerLogo');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/vehicle_manufacturers'), $filename);
            $logo = $filename;
            $manufacturer->logo = $logo;
        } else {
            $logo = null;
        }

        $manufacturer->name = $request->input('manufacturerName');
        $manufacturer->vehicle_type_id = $request->input('vehicleType');
        $manufacturer->is_deleted = $request->has('isDeleted') ? 1 : 0;
        $manufacturer->save();

        $newVal = $manufacturer;
        if($logStatus != '' && $logStatus == 'add')
            logAdminActivity('Vehicle Manufacturer Creation', $manufacturer, NULL);
        if($logStatus != '' && $logStatus == 'edit'){
            $differences = compareArray($oldVal, $newVal);
            if($request->hasFile('featureIcon') || (isset($differences) && is_countable($differences) && count($differences) > 0)){
                logAdminActivity('Vehicle Manufacturer Updation', $oldVal, $newVal, $manufactureLogo);
            }
        }

        // Redirect back to the form with a success message or do any further processing
        return redirect()->route('admin.manufacturers')->with('success', 'Manufacturer added successfully!');
    }

    public function deleteVehicleManufacturer(Request $request)
    {
        $message = 'Something went Wrong';
        $status = false;
        $manufacturer = VehicleManufacturer::find($request->id);
        $vehicleManufacturerCnt = VehicleModel::where('manufacturer_id', $request->id)->count();
        if($vehicleManufacturerCnt > 0){
            $message = 'You can not delete Vehicle Manufacturer due to its associated with any Vehicle Model.';
            $status = false;
        }else{
            $manufacturer->is_deleted = 1;
            $manufacturer->save();    
            $message = 'Vehicle Manufacturer deleted successfully.';
            $status = true;
            logAdminActivity("Vehicle Manufacturer Deletion", $manufacturer);
        }

        return response()->json([
            'data' => $manufacturer,
            'message' => $message,
            'status' => $status,
        ]);
    }

}
