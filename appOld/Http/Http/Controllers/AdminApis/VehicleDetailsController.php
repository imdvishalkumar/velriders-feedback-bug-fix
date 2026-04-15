<?php

namespace App\Http\Controllers\AdminApis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{VehicleType, VehicleCategory, Vehicle, FuelType, Transmission, VehicleFeature, VehicleManufacturer, VehicleModel, TripAmountCalculationRule, VehicleModelPriceDetail};
use Illuminate\Support\Facades\Validator;

class VehicleDetailsController extends Controller
{
    public function getAllVehicleTypes(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'type_id' => 'nullable|exists:vehicle_types,type_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $types = VehicleType::where('is_deleted', 0);
        if(isset($request->type_id) && $request->type_id != NULL){
            $types = $types->where('type_id', $request->type_id)->first();
            return $types ? $this->successResponse($types, 'Vehicle Types get Successfully') : $this->errorResponse('Vehicle Types are not Found');
        }

        if(isset($search) && $search != ''){
            $types = $types->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(convenience_fees) LIKE LOWER(?)', ["%$search%"]);
            });
        }

        if($orderColumn != '' && $orderType != ''){
            $adminsQuery = $adminsQuery->orderBy($orderColumn, $orderType);
        }
        
        if ($page !== null && $pageSize !== null) {
            $typeData = $types->paginate($pageSize, ['*'], 'page', $page);
            $decodedtype = json_decode(json_encode($typeData->getCollection()->values()), FALSE);

            return $this->successResponse([
                'vehicle_types' => $decodedtype,
                'pagination' => [
                    'total' => $typeData->total(),
                    'per_page' => $typeData->perPage(),
                    'current_page' => $typeData->currentPage(),
                    'last_page' => $typeData->lastPage(),
                    'from' => ($typeData->currentPage() - 1) * $typeData->perPage() + 1,
                    'to' => min($typeData->currentPage() * $typeData->perPage(), $typeData->total()),
                ]], 'Vehicle Types fetched successfully');
        }else{
            $VehicleTypes = [
                'vehicle_types' => $types->get(),
            ];
            if(isset($VehicleTypes) && is_countable($VehicleTypes) && count($VehicleTypes) > 0){
                return $this->successResponse($VehicleTypes, 'Vehicle Types are fetched successfully');
            }else{
                return $this->errorResponse('Vehicle Types are not found');
            }
        }
    }

    public function createOrUpdateVehicleTypes(Request $request) {
        $validator = Validator::make($request->all(), [
            'type_id' => 'nullable|exists:vehicle_types,type_id',
            'name' => 'required',
            'convenience_fees' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldType = null;
        if($request->type_id != NULL){
            $type = VehicleType::where('type_id', $request->type_id)->first();
            if($type == ''){
                return $this->errorResponse('Vehicle Type not Found');        
            }
            $oldType = $type;
        }else{
            $type = new VehicleType();
        }
        $type->name = $request->name;
        $type->convenience_fees = $request->convenience_fees;
        $type->save();

        logAdminActivities("Create or Update Vehicle Types", $oldType, $type, null);
        return $this->successResponse($type, 'Vehicle type set Successfully');
    }

    public function deleteVehicleTypes(Request $request){
        $validator = Validator::make($request->all(), [
            'type_id' => 'nullable|exists:vehicle_types,type_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $vehicleType = VehicleType::find($request->type_id);
        $vehicleTypeCnt = VehicleCategory::where('vehicle_type_id', $request->type_id)->count();
        if($vehicleTypeCnt > 0){
            return $this->errorResponse('You can not delete Vehicle Type due to its associated with any category.');
        }else{
            $vehicleType->is_deleted = 1;
            $vehicleType->save();
            logAdminActivities("Vehicle Type Deletion", $vehicleType, NULL);
            return $this->successResponse($vehicleType, 'Vehicle Type deleted successfully');
        }
    }

    public function getVehicleCategories(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:vehicle_categories,category_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $categories = VehicleCategory::select(
            'vehicle_categories.category_id',
            'vehicle_categories.vehicle_type_id',
            'vehicle_categories.name',
            'vehicle_categories.icon',
            'vehicle_categories.sort',
            'vehicle_categories.is_deleted',
            'vehicle_types.type_id',
            'vehicle_types.name as vehicle_type_name',
        )->where('vehicle_categories.is_deleted', 0)->leftJoin('vehicle_types', 'vehicle_types.type_id', '=', 'vehicle_categories.vehicle_type_id');
        if(isset($request->category_id) && $request->category_id != NULL){
            $categories = $categories->where('vehicle_categories.category_id', $request->category_id)->first();
            return $categories ? $this->successResponse($categories, 'Vehicle category get Successfully') : $this->errorResponse('Vehicle category not Found');
        }
        if(isset($search) && $search != ''){
            $categories = $categories->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(vehicle_categories.name) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(vehicle_types.name) LIKE LOWER(?)', ["%$search%"]);
            });
        }
        if($orderColumn != '' && $orderType != ''){
            $categories = $categories->orderBy($orderColumn, $orderType);
        }
        if ($page !== null && $pageSize !== null) {
            $categories = $categories->paginate($pageSize, ['*'], 'page', $page);
            $decodedCategories = json_decode(json_encode($categories->getCollection()->values()), FALSE);

            return $this->successResponse([
                'categories' => $decodedCategories,
                'pagination' => [
                    'total' => $categories->total(),
                    'per_page' => $categories->perPage(),
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'from' => ($categories->currentPage() - 1) * $categories->perPage() + 1,
                    'to' => min($categories->currentPage() * $categories->perPage(), $categories->total()),
                ]], 'Vehicle categories are get successfully');
        }else{
            $categories = [
                'categories' => $categories->get(),
            ];
            if(isset($categories) && is_countable($categories) && count($categories) > 0){
                return $this->successResponse($categories, 'Vehicle categories are get Successfully');
            }else{
                return $this->errorResponse('Vehicle categories are not Found');
            }
        }
    }

    public function createOrUpdateVehicleCategories(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'vehicle_type_id' => 'required|exists:vehicle_types,type_id', 
            'icon' => 'image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:20480',
            'category_id' => 'nullable|exists:vehicle_categories,category_id',
        ]);
        $validator->sometimes(['icon'], 'required', function ($input) {
            return is_null($input->category_id);
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldCat = null;
        if($request->category_id != ''){
            $vehicleCategory = VehicleCategory::where('category_id', $request->category_id)->where('is_deleted', 0)->first();
            if($vehicleCategory == ''){
                return $this->errorResponse('Vehicle category not Found');
            }
            $oldCat = $vehicleCategory;
        }else{
            $vehicleCategory = new VehicleCategory();
        }
        $vehicleCategory->name = $request->name ?? '';
        $vehicleCategory->vehicle_type_id = $request->vehicle_type_id;
        if(isset($request->category_id) && $request->category_id != '' && isset($request->icon) && $request->icon != '' &&  $vehicleCategory != '' && $vehicleCategory->icon != ''){
            $parsedUrl = parse_url($vehicleCategory->icon);
            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
            $path = public_path($path);    
            if (file_exists($path)){
                gc_collect_cycles();
                unlink($path);
            } 
        }
        if($request->file('icon') != ''){
            $image = $request->file('icon');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/vehicle_categories'), $filename);
            $vehicleCategory->icon = $filename;
        }
        $vehicleCategory->save();

        logAdminActivities('Vehicle Category Creation', $oldCat, $vehicleCategory, NULL);
        return $this->successResponse($vehicleCategory, 'Vehicle category data set Successfully');
    }

    public function deleteVehicleCategory(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:vehicle_categories,category_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $catId = $request->category_id;
        $vahicleCategory = VehicleCategory::find($catId);
        $vehicleCatCnt = Vehicle::whereHas('model.category', function ($q) use ($catId) {
                            $q->where('category_id', $catId);
                        })->count();
        if($vehicleCatCnt > 0){
            return $this->errorResponse('You can not delete Vehicle Category due to its associated with any vehicle.');
        }else{
            $vahicleCategory->is_deleted = 1;
            $vahicleCategory->save();    

            logAdminActivities('Vehicle Category Deletion', $vahicleCategory, NULL);
            return $this->successResponse($vahicleCategory, 'Vehicle category deleted Successfully');
        }
    }

    public function getAllFuelTypes(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'fuel_type_id' => 'nullable|exists:vehicle_fuel_types,fuel_type_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $types = FuelType::select(
            'vehicle_fuel_types.fuel_type_id',
            'vehicle_fuel_types.vehicle_type_id',
            'vehicle_fuel_types.name',
            'vehicle_fuel_types.is_deleted',
            'vehicle_types.type_id',
            'vehicle_types.name as vehicle_type_name',
        )->where('vehicle_fuel_types.is_deleted', 0)->leftJoin('vehicle_types', 'vehicle_types.type_id', '=', 'vehicle_fuel_types.vehicle_type_id');
        if(isset($request->fuel_type_id) && $request->fuel_type_id != NULL){
            $types = $types->where('vehicle_fuel_types.fuel_type_id', $request->fuel_type_id)->first();
            return $types ? $this->successResponse($types, 'Fuel type get Successfully') : $this->errorResponse('Fuel type not Found');
        }
        if(isset($search) && $search != ''){
            $types = $types->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(vehicle_fuel_types.name) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(vehicle_types.name) LIKE LOWER(?)', ["%$search%"]);
            });
        }
        if($orderColumn != '' && $orderType != ''){
            $types = $types->orderBy($orderColumn, $orderType);
        }
        if ($page !== null && $pageSize !== null) {
            $fuelTypes = $types->paginate($pageSize, ['*'], 'page', $page);
            $decodedFuelTypes = json_decode(json_encode($fuelTypes->getCollection()->values()), FALSE);

            return $this->successResponse([
                'fuel_types' => $decodedFuelTypes,
                'pagination' => [
                    'total' => $fuelTypes->total(),
                    'per_page' => $fuelTypes->perPage(),
                    'current_page' => $fuelTypes->currentPage(),
                    'last_page' => $fuelTypes->lastPage(),
                    'from' => ($fuelTypes->currentPage() - 1) * $fuelTypes->perPage() + 1,
                    'to' => min($fuelTypes->currentPage() * $fuelTypes->perPage(), $fuelTypes->total()),
                ]], 'Fuel types are get Successfully');
        }else{
            $fuelTypes = [
                'fuel_types' => $types->get(),
            ];
            if(isset($fuelTypes) && is_countable($fuelTypes) && count($fuelTypes) > 0){
                return $this->successResponse($fuelTypes, 'Fuel types are get Successfully');
            }else{
                return $this->errorResponse('Fuel types are not Found');
            }
        }
    }

    public function createOrUpdateFuelType(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'vehicle_type_id' => 'required|exists:vehicle_types,type_id', 
            'fuel_type_id' => 'nullable|exists:vehicle_fuel_types,fuel_type_id'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldType = null;
        if($request->fuel_type_id != NULL){
            $type = FuelType::where('fuel_type_id', $request->fuel_type_id)->first();
            if($type == ''){
                return $this->errorResponse('Vehicle Fuel Type not Found');        
            }
            $oldType = $type;
        }else{
            $type = new FuelType();
        }
        $type->name = $request->name;
        $type->vehicle_type_id = $request->vehicle_type_id;
        $type->save();
        
        logAdminActivities("Create or Update Vehicle Fuel Types", $oldType, $type, null);
        return $this->successResponse($type, 'Vehicle fuel type set Successfully');
    }

    public function deleteFuelType(Request $request){
        $validator = Validator::make($request->all(), [
            'fuel_type_id' => 'required|exists:vehicle_fuel_types,fuel_type_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $fuelTypeId = $request->fuel_type_id;
        $fuelType = FuelType::find($fuelTypeId);
        $vehicleFuelCnt = Vehicle::whereHas('properties', function ($q) use ($fuelTypeId) {
                            $q->where('fuel_type_id', $fuelTypeId);
                        })->count();
        if($vehicleFuelCnt > 0){
            return $this->errorResponse('You can not delete this Fuel type due to its associated with any vehicle.');
        }else{
            $fuelType->is_deleted = 1;
            $fuelType->save();    

            logAdminActivities('Fuel Type Deletion', $fuelType, NULL);
            return $this->successResponse($fuelType, 'Fuel Type deleted Successfully');
        }
    }

    public function getVehicleTransmissions(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'transmission_id' => 'nullable|exists:vehicle_transmissions,transmission_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $transmission = Transmission::select(
            'vehicle_transmissions.transmission_id',
            'vehicle_transmissions.vehicle_type_id',
            'vehicle_transmissions.name',
            'vehicle_transmissions.is_deleted',
            'vehicle_types.type_id',
            'vehicle_types.name as vehicle_type_name',
        )->where('vehicle_transmissions.is_deleted', 0)->leftJoin('vehicle_types', 'vehicle_types.type_id', '=', 'vehicle_transmissions.vehicle_type_id');

        if(isset($request->transmission_id) && $request->transmission_id != NULL){
            $transmission = $transmission->where('transmission_id', $request->transmission_id)->first();
            return $transmission ? $this->successResponse($transmission, 'Transmission get Successfully') : $this->errorResponse('Transmission not Found');
        }
        if(isset($search) && $search != ''){
            $transmission = $transmission->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(vehicle_transmissions.name) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(vehicle_types.name) LIKE LOWER(?)', ["%$search%"]);
            });
        }
        if($orderColumn != '' && $orderType != ''){
            $transmission = $transmission->orderBy($orderColumn, $orderType);
        }
        if ($page !== null && $pageSize !== null) {
            $vehicleTransmission = $transmission->paginate($pageSize, ['*'], 'page', $page);
            $decodedTransmission = json_decode(json_encode($vehicleTransmission->getCollection()->values()), FALSE);

            return $this->successResponse([
                'vehicle_transmission' => $decodedTransmission,
                'pagination' => [
                    'total' => $vehicleTransmission->total(),
                    'per_page' => $vehicleTransmission->perPage(),
                    'current_page' => $vehicleTransmission->currentPage(),
                    'last_page' => $vehicleTransmission->lastPage(),
                    'from' => ($vehicleTransmission->currentPage() - 1) * $vehicleTransmission->perPage() + 1,
                    'to' => min($vehicleTransmission->currentPage() * $vehicleTransmission->perPage(), $vehicleTransmission->total()),
                ]], 'Transmission are get Successfully');
        }else{
            $vehicleTransmission = [
                'vehicle_transmission' => $transmission->get(),
            ];
            if(isset($vehicleTransmission) && is_countable($vehicleTransmission) && count($vehicleTransmission) > 0){
                return $this->successResponse($vehicleTransmission, 'Transmission are get Successfully');
            }else{
                return $this->errorResponse('Transmission are not Found');
            }
        }
    }

    public function createOrUpdateVehicleTrasmission(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'vehicle_type_id' => 'required|exists:vehicle_types,type_id', 
            'transmission_id' => 'nullable|exists:vehicle_transmissions,transmission_id'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldTransmission = null;
        if($request->transmission_id != NULL){
            $transmission = Transmission::where('transmission_id', $request->transmission_id)->first();
            if($transmission == ''){
                return $this->errorResponse('Vehicle Transmission not Found');        
            }
            $oldTransmission = $transmission;
        }else{
            $transmission = new Transmission();
        }
        $transmission->name = $request->name;
        $transmission->vehicle_type_id = $request->vehicle_type_id;
        $transmission->save();
        
        logAdminActivities("Create or Update Vehicle Transmission", $oldTransmission, $transmission, null);
        return $this->successResponse($transmission, 'Vehicle Transmission set Successfully');
    }

    public function deleteVehicleTransmission(Request $request){
        $validator = Validator::make($request->all(), [
            'transmission_id' => 'required|exists:vehicle_transmissions,transmission_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $transmissionId = $request->transmission_id;
        $transmission = Transmission::find($transmissionId);
        $vehicleTransmissionCnt = Vehicle::whereHas('properties', function ($q) use ($transmissionId) {
                            $q->where('transmission_id', $transmissionId);
                        })->count();
        if($vehicleTransmissionCnt > 0){
            return $this->errorResponse('You can not delete this Transmission due to its associated with any vehicle.');
        }else{
            $transmission->is_deleted = 1;
            $transmission->save();    

            logAdminActivities('Transmission Deletion', $transmission, NULL);
            return $this->successResponse($transmission, 'Transmission deleted Successfully');
        }
    }

    public function getVehicleFeatureList(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'feature_id' => 'nullable|exists:vehicle_features,feature_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $feature = VehicleFeature::where('is_deleted', 0);
        if(isset($request->feature_id) && $request->feature_id != NULL){
            $feature = $feature->where('feature_id', $request->feature_id)->first();
            return $feature ? $this->successResponse($feature, 'Vehicle Feature get Successfully') : $this->errorResponse('Vehicle Feature not Found');
        }
        if(isset($search) && $search != ''){
            $feature = $feature->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%$search%"]);
            });
        }

        if($orderColumn != '' && $orderType != ''){
            $feature = $feature->orderBy($orderColumn, $orderType);
        }

        if ($page !== null && $pageSize !== null) {
            $vehicleFeatures = $feature->paginate($pageSize, ['*'], 'page', $page);
            $decodedVehicleFeatures = json_decode(json_encode($vehicleFeatures->getCollection()->values()), FALSE);

            return $this->successResponse([
                'vehicle_features' => $decodedVehicleFeatures,
                'pagination' => [
                    'total' => $vehicleFeatures->total(),
                    'per_page' => $vehicleFeatures->perPage(),
                    'current_page' => $vehicleFeatures->currentPage(),
                    'last_page' => $vehicleFeatures->lastPage(),
                    'from' => ($vehicleFeatures->currentPage() - 1) * $vehicleFeatures->perPage() + 1,
                    'to' => min($vehicleFeatures->currentPage() * $vehicleFeatures->perPage(), $vehicleFeatures->total()),
                ]], 'Vehicle Featue are get Successfully');
        }else{
            $vehicleFeatures = [
                'vehicle_features' => $feature->get(),
            ];
            if(isset($vehicleFeatures) && is_countable($vehicleFeatures) && count($vehicleFeatures) > 0){
                return $this->successResponse($vehicleFeatures, 'Vehicle Featue are get Successfully');
            }else{
                return $this->errorResponse('Vehicle Feature are not Found');
            }
        }
    }

    public function createOrUpdateVehicleFeature(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'icon' => 'image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:20480',
            'feature_id' => 'nullable|exists:vehicle_features,feature_id',
        ]);
        $validator->sometimes(['icon'], 'required', function ($input) {
            return is_null($input->feature_id);
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldFeature = null;
        if($request->feature_id != ''){
            $vehicleFeature = VehicleFeature::where('feature_id', $request->feature_id)->where('is_deleted', 0)->first();
            if($vehicleFeature == ''){
                return $this->errorResponse('Vehicle Feature not Found');
            }
            $oldFeature = $vehicleFeature;
        }else{
            $vehicleFeature = new VehicleFeature();
        }
        $vehicleFeature->name = $request->name ?? '';
        if(isset($request->feature_id) && $request->feature_id != '' && isset($request->icon) && $request->icon != '' &&  $vehicleFeature != '' && $vehicleFeature->icon != ''){
            $parsedUrl = parse_url($vehicleFeature->icon);
            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
            $path = public_path($path);    
            if (file_exists($path)){
                gc_collect_cycles();
                unlink($path);
            } 
        }
        if($request->file('icon') != ''){
            $image = $request->file('icon');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/vehicle_features'), $filename);
            $vehicleFeature->icon = $filename;
        }
        $vehicleFeature->save();

        logAdminActivities('Vehicle Feature Creation', $oldFeature, $vehicleFeature, NULL);
        return $this->successResponse($vehicleFeature, 'Vehicle Feature data set Successfully');
    }

    public function deleteVehicleFeatures(Request $request){
        $validator = Validator::make($request->all(), [
            'feature_id' => 'nullable|exists:vehicle_features,feature_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $featureId = $request->feature_id;
        $vehicleFeature = VehicleFeature::where('feature_id', $featureId)->first();
        $vehicleFeatureCnt = Vehicle::whereHas('featuresMapping', function ($q) use ($featureId) {
                            $q->where('feature_id', $featureId);
                        })->count();
        if($vehicleFeatureCnt > 0){
            return $this->errorResponse('You can not delete this Vehicle Feature due to its associated with any vehicle.');
        }else{
            $vehicleFeature->is_deleted = 1;
            $vehicleFeature->save();    

            logAdminActivities('Vehicle Feature Deletion', $vehicleFeature, NULL);
            return $this->successResponse($vehicleFeature, 'Vehicle Feature deleted Successfully');
        }
    }

    public function getManufacturerList(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $search = $request->search ?? '';
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'manufacturer_id' => 'nullable|exists:vehicle_manufacturers,manufacturer_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $manufacturer = VehicleManufacturer::select(
            'vehicle_manufacturers.manufacturer_id',
            'vehicle_manufacturers.name',
            'vehicle_manufacturers.vehicle_type_id',
            'vehicle_manufacturers.created_at as creation_at',
            'vehicle_manufacturers.logo',
            'vehicle_types.type_id',
            'vehicle_types.name as vehicle_type_name',
        )->where('vehicle_manufacturers.is_deleted', 0)->leftJoin('vehicle_types', 'vehicle_types.type_id', '=', 'vehicle_manufacturers.vehicle_type_id');

        if(isset($request->manufacturer_id) && $request->manufacturer_id != NULL){
            $manufacturer = $manufacturer->where('manufacturer_id', $request->manufacturer_id)->first();
            return $manufacturer ? $this->successResponse($manufacturer, 'Manufacturer get Successfully') : $this->errorResponse('Manufacturer not Found');
        }
        if(isset($search) && $search != ''){
            $manufacturer = $manufacturer->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(vehicle_manufacturers.name) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(vehicle_types.name) LIKE LOWER(?)', ["%$search%"]);
            });
        }
        if($orderColumn != '' && $orderType != ''){
            $manufacturer = $manufacturer->orderBy($orderColumn, $orderType);
        }

        if ($page !== null && $pageSize !== null) {
            $manufacturers = $manufacturer->paginate($pageSize, ['*'], 'page', $page);
            if(isset($manufacturers) && is_countable($manufacturers) && count($manufacturers) > 0){
                foreach($manufacturers as $k => $v){
                    $v->created_at = $v->created_at;
                }
            }
            $decodedManufacturers = json_decode(json_encode($manufacturers->getCollection()->values()), FALSE);
            return $this->successResponse([
                'manufacturers' => $decodedManufacturers,
                'pagination' => [
                    'total' => $manufacturers->total(),
                    'per_page' => $manufacturers->perPage(),
                    'current_page' => $manufacturers->currentPage(),
                    'last_page' => $manufacturers->lastPage(),
                    'from' => ($manufacturers->currentPage() - 1) * $manufacturers->perPage() + 1,
                    'to' => min($manufacturers->currentPage() * $manufacturers->perPage(), $manufacturers->total()),
                ]], 'Manufacturer are get Successfully');
        }else{
            $manufacturers = [
                'manufacturers' => $manufacturer->get(),
            ];
            if(isset($manufacturers) && is_countable($manufacturers) && count($manufacturers) > 0){
                return $this->successResponse($manufacturers, 'Manufacturer are get Successfully');
            }else{
                return $this->errorResponse('Manufacturer are not Found');
            }   
        }
    }

    public function createOrUpdateVehicleManufacturer(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'logo' => 'image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:20480',
            'vehicle_type_id' => 'required|exists:vehicle_types,type_id', 
            'manufacturer_id' => 'nullable|exists:vehicle_manufacturers,manufacturer_id',
        ]);
        $validator->sometimes(['logo'], 'required', function ($input) {
            return is_null($input->manufacturer_id);
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldManufacturer = null;
        if($request->manufacturer_id != ''){
            $vehicleManufacturer = VehicleManufacturer::where('manufacturer_id', $request->manufacturer_id)->where('is_deleted', 0)->first();
            if($vehicleManufacturer == ''){
                return $this->errorResponse('Vehicle Manufacturer not Found');
            }
            $oldManufacturer = $vehicleManufacturer;
        }else{
            $vehicleManufacturer = new VehicleManufacturer();
        }

        $vehicleManufacturer->name = $request->name ?? '';
        $vehicleManufacturer->vehicle_type_id = $request->vehicle_type_id ?? '';
        if(isset($request->manufacturer_id) && $request->manufacturer_id != '' && isset($request->logo) && $request->logo != '' &&  $vehicleManufacturer != '' && $vehicleManufacturer->icon != ''){
            $parsedUrl = parse_url($vehicleManufacturer->logo);
            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
            $path = public_path($path);    
            if (file_exists($path)){
                gc_collect_cycles();
                unlink($path);
            } 
        }
        if($request->file('logo') != ''){
            $image = $request->file('logo');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/vehicle_manufacturers'), $filename);
            $vehicleManufacturer->logo = $filename;
        }
        $vehicleManufacturer->save();

        logAdminActivities('Vehicle Manufacturer Creation', $oldManufacturer, $vehicleManufacturer, NULL);
        return $this->successResponse($vehicleManufacturer, 'Vehicle Manufacturer data set Successfully');
    }

    public function deleteVehicleManufacturer(Request $request){
        $validator = Validator::make($request->all(), [
            'manufacturer_id' => 'required|exists:vehicle_manufacturers,manufacturer_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $manufacturerId = $request->manufacturer_id;
        $vehicleManufacturer = VehicleManufacturer::where('manufacturer_id', $manufacturerId)->first();
        $vehicleManufacturerCnt = Vehicle::whereHas('model', function ($q) use ($manufacturerId) {
                            $q->where('manufacturer_id', $manufacturerId);
                        })->count();
        if($vehicleManufacturerCnt > 0){
            return $this->errorResponse('You can not delete this Vehicle Manufacturer due to its associated with any vehicle.');
        }else if($vehicleManufacturer != ''){
            $vehicleManufacturer->is_deleted = 1;
            $vehicleManufacturer->save();    

            logAdminActivities('Vehicle Manufacturer Deletion', $vehicleManufacturer, NULL);
            return $this->successResponse($vehicleManufacturer, 'Vehicle Manufacturer deleted Successfully');
        }
    }

    public function getVehicleModels(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'model_id' => 'nullable|exists:vehicle_models,model_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $model = VehicleModel::select(
            'vehicle_models.model_id',
            'vehicle_models.category_id',
            'vehicle_models.name',
            'vehicle_models.manufacturer_id as manufacturerid',
            'vehicle_models.model_image',
            'vehicle_models.min_price',
            'vehicle_models.max_price',
            'vehicle_categories.name as category_name',
            'vehicle_manufacturers.name as manufacturer_name',
            'vehicle_models.min_km_limit',
            'vehicle_models.max_km_limit',
            'vehicle_models.min_deposit_amount',
            'vehicle_models.max_deposit_amount',
        )->where('vehicle_models.is_deleted', 0)->with('modelPriceSummary', function($q){
            $q->select('id', 'vehicle_model_id', 'type', 'rental_price', 'hours', 'rate', 'multiplier', 'duration', 'per_hour_rate', 'trip_amount_km_limit', 'unlimited_km_trip_amount');
        })
        ->leftJoin('vehicle_categories', 'vehicle_categories.category_id', '=', 'vehicle_models.category_id')
        ->leftJoin('vehicle_manufacturers', 'vehicle_manufacturers.manufacturer_id', '=', 'vehicle_models.manufacturer_id');

        if(isset($request->model_id) && $request->model_id != NULL){
            $model = $model->where('model_id', $request->model_id)->first();
            return $model ? $this->successResponse($model, 'Model get Successfully') : $this->errorResponse('Model not Found');
        }
        if(isset($search) && $search != ''){
            $checkModel = VehicleModel::where('model_id', (int)$search)->exists();
            if($checkModel){
                $model = $model->where('model_id', $search);
            }
            else{
                $model = $model->where(function ($query) use ($search) {
                    $query->whereRaw('LOWER(vehicle_models.name) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(vehicle_models.min_price) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(vehicle_models.max_price) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(vehicle_categories.name) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(vehicle_manufacturers.name) LIKE LOWER(?)', ["%$search%"]);
                });
            }
        }
        if($orderColumn != '' && $orderType != ''){
            $model = $model->orderBy($orderColumn, $orderType);
        }

        if ($page !== null && $pageSize !== null) {
            $models = $model->paginate($pageSize, ['*'], 'page', $page);
            $decodedModels = json_decode(json_encode($models->getCollection()->values()), FALSE);

            return $this->successResponse([
                'models' => $decodedModels,
                'pagination' => [
                    'total' => $models->total(),
                    'per_page' => $models->perPage(),
                    'current_page' => $models->currentPage(),
                    'last_page' => $models->lastPage(),
                    'from' => ($models->currentPage() - 1) * $models->perPage() + 1,
                    'to' => min($models->currentPage() * $models->perPage(), $models->total()),
                ]], 'Models are get Successfully');
        }else{
            $models = [
                'models' => $model->get(),
            ];
            if(isset($models) && is_countable($models) && count($models) > 0){
                return $this->successResponse($models, 'Models are get Successfully');         
            }else{
                return $this->errorResponse('Models are not Found');
            }
        }
    }

    public function createOrUpdateVehicleModel(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'category_id' => 'required|exists:vehicle_categories,category_id',
            'manufacturer_id' => 'required|exists:vehicle_manufacturers,manufacturer_id', 
            'min_price' => 'required|numeric|min:0|max:99999999.99',
            'min_price_calc' => 'required|json',
            'max_price' => 'required|numeric|min:0|max:99999999.99', 
            'max_price_calc' => 'required|json',
            'model_image' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:3000',
            'model_id' => 'nullable|exists:vehicle_models,model_id',
            'min_km_limit' => 'required|numeric|max:99999999.99', 
            'max_km_limit' => 'required|numeric|max:99999999.99|gt:min_km_limit',
            'min_deposit_amount' => 'required|numeric|max:99999999.99', 
            'max_deposit_amount' => 'required|numeric|max:99999999.99|gt:min_deposit_amount',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $decodedMinPrice = json_decode($request->min_price_calc, true);
        $decodedMinPrice = array_reverse($decodedMinPrice, true);
        $decodedMaxPrice = json_decode($request->max_price_calc, true);
        $decodedMaxPrice = array_reverse($decodedMaxPrice, true);
        $oldVal = $newVal = '';
        if(isset($request->model_id) && $request->model_id != ''){
            $model = VehicleModel::where('model_id', $request->model_id)->first();
        }else{
            $model = new VehicleModel();
            $oldVal = clone $model;
            $model->is_deleted = 0;
        }
        
        $model->name = $request->name;
        $model->manufacturer_id = $request->manufacturer_id;
        $model->category_id = $request->category_id;
        $model->updated_at = now();
        $model->min_price = $request->min_price;
        $model->max_price = $request->max_price;
        $model->min_km_limit = $request->min_km_limit;
        $model->max_km_limit = $request->max_km_limit;
        $model->min_deposit_amount = $request->min_deposit_amount;
        $model->max_deposit_amount = $request->max_deposit_amount;
        $model->save();
        $newVal = $model;

        if ($request->hasFile('model_image')) {
            $file = $request->file('model_image');
            $filename = 'vehicle_model_'.$model->id.'_'.time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/vehicle_models'), $filename);
            $model->model_image = $filename;
            $model->save();
        }
        if(isset($request->model_id) && $request->model_id != ''){
            logAdminActivities("Vehicle Model Creation", $model);
        }
        else{
            $differences = compareArray($oldVal, $newVal);
            if(isset($differences) && is_countable($differences) && count($differences) > 0){
                logAdminActivities('Vehicle Model Updation', $oldVal, $newVal);
            }
        }

        //VEHICLE MODEL MINIMUM PRICE
        $minPriceCalc = $decodedMinPrice ?? '';
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
        
        //VEHICLE MODEL MAXIMUM PRICE
        $maxPriceCalc = $decodedMaxPrice ?? '';
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
       
        return $this->successResponse($model, 'Price Summary set Successfully');
    }   

    public function getPriceSummary(Request $request){
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $minPriceArr = $maxPriceArr = [];
        $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get();
        $rentalPrice = $request->price;
        if ($rentalPrice > 0 && $rentalPrice != '') {
            foreach ($rules as $rule){
                $hours = $rule->hours;
                $multiplier = $rule->multiplier;
                $tripAmount = $multiplier * $rentalPrice;
                $duration = $hours <= 24 ? $hours. ' hours ': ($hours / 24).' days';
                $minCalculationItem['duration'] = $duration;
                $minCalculationItem['value'] = $tripAmount;
                $minPriceArr[] = $minCalculationItem;
            }
            return $this->successResponse($minPriceArr, 'Price Summary get Successfully');
        } else {
            foreach ($rules as $rule){
                $hours = $rule->hours;
                $duration = $hours <= 24 ? $hours. ' hours ': ($hours / 24).' days';
                $minCalculationItem['duration'] = $duration;
                $minCalculationItem['value'] = 0;
                $minPriceArr[] = $minCalculationItem;
            }
            return $this->successResponse($minPriceArr, 'Price Summary get Successfully');
        }
    }

    public function deleteVehicleModel(Request $request){
        $validator = Validator::make($request->all(), [
            'model_id' => 'required|exists:vehicle_models,model_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $modelId = $request->model_id;
        $vehicleModel = VehicleModel::where('model_id', $modelId)->first();
        $vehicleModelCnt = Vehicle::where('model_id', $modelId)->count();
        if($vehicleModelCnt > 0){
            return $this->errorResponse('You can not delete this Vehicle Model due to its associated with any vehicle.');
        }else if($vehicleModel != ''){
            $vehicleModel->is_deleted = 1;
            $vehicleModel->save();    

            logAdminActivities('Vehicle Model Deletion', $vehicleModel, NULL);
            return $this->successResponse($vehicleModel, 'Vehicle Model deleted Successfully');
        }
    }

    public function unlinkImages($carHostVehicleImage){
        $filePath = public_path().'/images/car_host/'.basename($carHostVehicleImage->vehicle_img);
        if(file_exists($filePath)){
            unlink($filePath);
        }
        $carHostVehicleImage->delete();
    }

    protected function unlinkImagesArr($carHostVehicleImages){
        foreach($carHostVehicleImages as $k => $v){
            $filePath = public_path().'/images/car_host/'.basename($v->vehicle_img);
            if(file_exists($filePath)){
                unlink($filePath);
            }
            $v->delete();
        }
    }

}
