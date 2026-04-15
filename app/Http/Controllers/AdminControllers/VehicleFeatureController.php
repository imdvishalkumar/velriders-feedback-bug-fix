<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VehicleFeature;
use App\Models\VehicleFeatureMapping;

class VehicleFeatureController extends Controller
{
    public function getVehicleFeatureList(){
        hasPermission('vehicle-features');
        return view('admin.vehicle-features');
    }

    public function getFeatures(Request $request)
    {
        $features = VehicleFeature::where('is_deleted', 0)->get();

        return response()->json([
            'status' => true,
            'data' => $features,
        ], 200);
    }

    public function getVehicleFeatures(Request $request)
    {
        $feature = VehicleFeature::find($request->id);
        return response()->json([
            'data' => $feature,
            'status' => true,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'featureName' => 'required|string|max:255',
        ];
        $logStatus = ''; 

        if(isset($request->fId) && $request->fId != ''){
            $vehicleFeature = VehicleFeature::where('feature_id', $request->fId)->first();

            if($request->hasFile('featureIcon')){
                if(isset($vehicleFeature->icon) && $vehicleFeature->icon != ''){
                    $parsedUrl = parse_url($vehicleFeature->icon);
                    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                    $path = public_path($path);    
                    if (file_exists($path)){
                        gc_collect_cycles();
                        unlink($path);
                    } 
                }
            }
            $rules['featureIcon'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:20480';

            $logStatus = 'edit';
            $featureIcon = $vehicleFeature->icon;
            $featureIcon = basename($featureIcon);
            unset($vehicleFeature->icon);
            $oldVal = clone $vehicleFeature;
        }
        else{
            $rules['featureIcon'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20480';
            $vehicleFeature = new VehicleFeature();
            $logStatus = 'add';
        }
        $request->validate($rules);
        // Handle file upload for icon
        if ($request->hasFile('featureIcon')) {
            $file = $request->file('featureIcon');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/vehicle_features'), $filename);
            $icon = $filename;
            $vehicleFeature->icon = $icon;
        }

        $vehicleFeature->name = $request->input('featureName');
        $vehicleFeature->is_deleted = $request->has('isDeleted') ? 1 : 0;
        $vehicleFeature->save();

        $newVal = $vehicleFeature;
        if($logStatus != '' && $logStatus == 'add')
            logAdminActivity('Vehicle Feature Creation', $vehicleFeature, NULL);
        if($logStatus != '' && $logStatus == 'edit'){
            $differences = compareArray($oldVal, $newVal);
            if($request->hasFile('featureIcon') || (isset($differences) && is_countable($differences) && count($differences) > 0)){
                logAdminActivity('Vehicle Feature Updation', $oldVal, $newVal, $featureIcon);
            }
        }

        // Redirect back to the form with a success message or do any further processing
        return redirect()->route('admin.vehicle-features')->with('success', 'Vehicle Feature added successfully!');
    }

    public function deleteVehicleFeatures(Request $request)
    {
        $message = 'Something went Wrong';
        $status = false;
        $feature = VehicleFeature::find($request->id);
        $vehicleFeatureCnt = VehicleFeatureMapping::where('feature_id', $request->id)->count();
        if($vehicleFeatureCnt > 0){
            $message = 'You can not delete Vehicle Feature due to its associated with any vehicle.';
            $status = false;
        }else{
            $feature->is_deleted = 1;
            $feature->save();    
            $message = 'Vehicle Feature deleted successfully.';
            $status = true;
            logAdminActivity("Vehicle Feature Deletion", $feature);
        }

        return response()->json([
            'data' => $feature,
            'message' => $message,
            'status' => $status,
        ]);
    }
}
