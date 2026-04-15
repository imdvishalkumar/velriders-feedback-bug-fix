<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VehicleCategory;
use App\Models\VehicleType;
use App\Models\Vehicle;

class VehicleCategoryController extends Controller
{
    public function index(Request $request){
        hasPermission('vehicle-categories');
        $vehicleTypes = VehicleType::all(); // Fetch all vehicle types
        return view('admin.vehicle-categories', compact('vehicleTypes'));
    }

    public function store(Request $request){
        $rules = [
            'name' => 'required|string|max:255',
            'vehicleType' => 'required|exists:vehicle_types,type_id', 
        ];
        $logStatus = '';
        if(isset($request->cId) && $request->cId != ''){
            $vehicleCategory = VehicleCategory::where('category_id', $request->cId)->first();
            if($request->hasFile('icon')){
                if(isset($vehicleCategory->icon) && $vehicleCategory->icon != ''){
                    $parsedUrl = parse_url($vehicleCategory->icon);
                    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                    $path = public_path($path);    
                    if (file_exists($path)){
                        gc_collect_cycles();
                        unlink($path);
                    } 
                }
            }
            $rules['icon'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:20480';
            $logStatus = 'edit';
            $catIcon = $vehicleCategory->icon;
            $catIcon = basename($catIcon);
            unset($vehicleCategory->icon);
            $oldVal = clone $vehicleCategory;
        }
        else{
            $rules['icon'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20480';
            $vehicleCategory = new VehicleCategory();
            $logStatus = 'add';
        }
        $request->validate($rules);
        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/vehicle_categories'), $filename);
            $icon = $filename;
            $vehicleCategory->icon = $icon;
        }
        $lastSort = VehicleCategory::orderBy('sort', 'DESC')->value('sort');
        $vehicleCategory->name = $request->name;
        $vehicleCategory->sort = $lastSort ? $lastSort + 1 : 1;
        $vehicleCategory->is_deleted = $request->has('isDeleted') ? 1 : 0;
        $vehicleCategory->vehicle_type_id = $request->input('vehicleType');
        $vehicleCategory->save();
        $newVal = $vehicleCategory;
        if($logStatus != '' && $logStatus == 'add')
            logAdminActivity('Vehicle Category Creation', $vehicleCategory, NULL);
        if($logStatus != '' && $logStatus == 'edit'){
            $differences = compareArray($oldVal, $newVal);
            if($request->hasFile('icon') || (isset($differences) && is_countable($differences) && count($differences) > 0)){
                logAdminActivity('Vehicle Category Updation', $oldVal, $newVal, $catIcon);
            }
        }

        return redirect()->route('admin.vehicle-categories')->with('success', 'Category Set successfully!');
    }

    public function getAllVehicleCategories()
    {
        $types = VehicleCategory::with('getVehicleType')->where('is_deleted', 0)->get();
        return response()->json([
            'status' => true,
            'data' => $types,
        ], 200);
    }

    public function getVehicleCategory(Request $request)
    {
        $category = VehicleCategory::find($request->id);

        return response()->json([
            'data' => $category,
            'status' => true,
        ]);
    }

    public function deleteVehicleCategory(Request $request)
    {
        $message = 'Something went Wrong';
        $status = false;
        $catId = $request->id;
        $vahicleCategory = VehicleCategory::find($catId);
        $vehicleCatCnt = Vehicle::whereHas('model.category', function ($q) use ($catId) {
                            $q->where('category_id', $catId);
                        })->count();
        if($vehicleCatCnt > 0){
            $message = 'You can not delete Vehicle Category due to its associated with any vehicle.';
            $status = false;
        }else{
            $vahicleCategory->is_deleted = 1;
            $vahicleCategory->save();    
            $message = 'Vehicle Category deleted successfully.';
            $status = true;

            logAdminActivity('Vehicle Category Deletion', $vahicleCategory, NULL);
        }

        return response()->json([
            'data' => $vahicleCategory,
            'message' => $message,
            'status' => $status,
        ]);
    }

}
