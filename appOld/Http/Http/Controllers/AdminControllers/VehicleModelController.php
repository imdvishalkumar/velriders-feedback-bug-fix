<?php

namespace App\Http\Controllers\AdminControllers;

use App\Models\VehicleModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{VehicleManufacturer, VehicleCategory, Vehicle, TripAmountCalculationRule, VehicleModelPriceDetail};

class VehicleModelController extends Controller
{
    public function getAllModelsList(){

        /*$modal = VehicleModel::get();
        foreach($modal as $key => $val){
            $vehicle = Vehicle::where('model_id', $val->model_id)->first();
            
            if($vehicle !== null){
                $val->category_id = $vehicle->category_id;
                $val->save();
            }
        }
        die;*/
        hasPermission('vehicle-models');
        return view('admin.vehicle-models');
    }

    public function getAllModels(Request $request)
    {
        //fetch models with manufacturer
        $models = VehicleModel::with('manufacturer', 'category')->where('is_deleted', 0)->get();
        
        return response()->json([
            'status' => true,
            'data' => $models,
        ]);
    }

    public function editModel($id){
        $model = VehicleModel::where('model_id', $id)->first();
        $vehicleManufacturerList = VehicleManufacturer::where('is_deleted', 0)->get();
        $vehicleCategoryList = VehicleCategory::select('category_id', 'name', 'is_deleted')->where('is_deleted', 0)->get();
        $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get();
        $vehicleModelMinPriceDetails = VehicleModelPriceDetail::where(['vehicle_model_id' => $id, 'type' => 1])->get();
        $vehicleModelMaxPriceDetails = VehicleModelPriceDetail::where(['vehicle_model_id'=> $id, 'type' => 2])->get();

        return view('admin.vehicleModel.edit', compact('model', 'vehicleManufacturerList', 'vehicleCategoryList', 'rules', 'vehicleModelMinPriceDetails', 'vehicleModelMaxPriceDetails'));
    }

    public function updateModel(Request $request, $id){
        $model = VehicleModel::where('model_id', $id)->first();
        $oldVal = clone $model;
        $model->name = $request->name;
        $model->manufacturer_id = $request->manufacturer;
        $model->category_id = $request->category;
        $model->updated_at = now();
        $model->min_price = $request->min_price;
        $model->max_price = $request->max_price;
        $model->save();
        $newVal = $model;
        if ($request->hasFile('model_image')) {
            $file = $request->file('model_image');
            $filename = 'vehicle_model_'.$model->id.'_'.time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/vehicle_models'), $filename);
            $model->model_image = $filename;
            $model->save();
        }
        $differences = compareArray($oldVal, $newVal);
        if(isset($differences) && is_countable($differences) && count($differences) > 0){
            logAdminActivity('Vehicle Model Updation', $oldVal, $newVal);
        }

        // Vehicle Model Minimum Price Update
        $minPriceCalc = $request->minPriceCalc ?? '';
        if($minPriceCalc != '') {
            $rentalPrice = $rentalPriceHour = 0;
            asort($minPriceCalc);// make sort based on its value on ascending order
            if(is_countable($minPriceCalc) && count($minPriceCalc) > 0){
                foreach ($minPriceCalc as $key => $value) {
                    if($value > 0){
                        $rentalPrice = $value;
                        $rentalPriceHour = $key;
                        break;
                    }
                }
            }
            krsort($minPriceCalc); //make sort based on its key on descending order
            $minMultipliers = []; // Array to hold the multipliers
            foreach ($minPriceCalc as $key => $value) {
                $multiplierVal = 0;
                if($rentalPrice <= $value){
                    $multiplierVal = ($value / $rentalPrice);
                }
                $minMultipliers[$key][$value] = round($multiplierVal, 2);
            }
            $vehicleModelPriceDetails = VehicleModelPriceDetail::where(['vehicle_model_id' => $model->model_id, 'type' => 1])->get();
            if(is_countable($vehicleModelPriceDetails) && count($vehicleModelPriceDetails) > 0){
                foreach ($vehicleModelPriceDetails as $key => $value) {
                    $value->delete();
                }
            }
            if(is_countable($minMultipliers) && count($minMultipliers) > 0){
                foreach ($minMultipliers as $key => $value) {
                    $vehicleModelPriceDetail = new VehicleModelPriceDetail();
                    $vehicleModelPriceDetail->type = 1; // 1 Means min price
                    $vehicleModelPriceDetail->vehicle_model_id = $model->model_id;
                    $vehicleModelPriceDetail->rental_price = $rentalPrice;
                    $vehicleModelPriceDetail->hours = $key;
                    foreach ($value as $k => $v) {
                        $vehicleModelPriceDetail->rate = $k;
                        $vehicleModelPriceDetail->multiplier = $v;
                        $perHourRate = $k / $key;
                        $vehicleModelPriceDetail->per_hour_rate = number_format(($perHourRate), 2);
                        $vehicleModelPriceDetail->unlimited_km_trip_amount = $k * 1.3;
                    }
                    $vehicleModelPriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
                    $vehicleModelPriceDetail->trip_amount_km_limit = calculateKmLimit($key)." Km";
                    $vehicleModelPriceDetail->save();   
                }
            }
        }

        // Vehicle Model Maximum Price Update
        $maxPriceCalc = $request->maxPriceCalc ?? '';
        if($maxPriceCalc != '') {
            $rentalPrice = $rentalPriceHour = 0;
            asort($maxPriceCalc);// make sort based on its value on ascending order
            if(is_countable($maxPriceCalc) && count($maxPriceCalc) > 0){
                foreach ($maxPriceCalc as $key => $value) {
                    if($value > 0){
                        $rentalPrice = $value;
                        $rentalPriceHour = $key;
                        break;
                    }
                }
            }
            krsort($maxPriceCalc); //make sort based on its key on descending order
            $maxMultipliers = []; // Array to hold the multipliers
            foreach ($maxPriceCalc as $key => $value) {
                $multiplierVal = 0;
                if($rentalPrice <= $value){
                    $multiplierVal = ($value / $rentalPrice);
                }
                $maxMultipliers[$key][$value] = round($multiplierVal, 2);
            }
            $vehicleModelPriceDetails = VehicleModelPriceDetail::where(['vehicle_model_id' => $model->model_id, 'type' => 2])->get();
            if(is_countable($vehicleModelPriceDetails) && count($vehicleModelPriceDetails) > 0){
                foreach ($vehicleModelPriceDetails as $key => $value) {
                    $value->delete();
                }
            }
            if(is_countable($maxMultipliers) && count($maxMultipliers) > 0){
                foreach ($maxMultipliers as $key => $value) {
                    $vehicleModelPriceDetail = new VehicleModelPriceDetail();
                    $vehicleModelPriceDetail->type = 2; // 2 Means min price
                    $vehicleModelPriceDetail->vehicle_model_id = $model->model_id;
                    $vehicleModelPriceDetail->rental_price = $rentalPrice;
                    $vehicleModelPriceDetail->hours = $key;
                    foreach ($value as $k => $v) {
                        $vehicleModelPriceDetail->rate = $k;
                        $vehicleModelPriceDetail->multiplier = $v;
                        $perHourRate = $k / $key;
                        $vehicleModelPriceDetail->per_hour_rate = number_format(($perHourRate), 2);
                        $vehicleModelPriceDetail->unlimited_km_trip_amount = $k * 1.3;
                    }
                    $vehicleModelPriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
                    $vehicleModelPriceDetail->trip_amount_km_limit = calculateKmLimit($key)." Km";
                    $vehicleModelPriceDetail->save();   
                }
            }
        }

        return redirect()->route('admin.vehicle-models')->with('success', 'Model updated sucessfully!');
    }   

    public function deleteVehicleModels(Request $request)
    {
        $message = 'Something went Wrong';
        $status = false;
        $model = VehicleModel::find($request->id);
        $vehicleManufacturerCnt = Vehicle::where('model_id', $request->id)->count();
        if($vehicleManufacturerCnt > 0){
            $message = 'You can not delete Vehicle Manufacturer due to its associated with any vehicle.';
            $status = false;
        }else{
            $model->is_deleted = 1;
            $model->save();    
            $message = 'Vehicle Model deleted successfully.';
            $status = true;
            logAdminActivity("Vehicle Model Deletion", $model);
        }
        return response()->json([
            'data' => $model,
            'message' => $message,
            'status' => $status,
        ]);
    }
}
