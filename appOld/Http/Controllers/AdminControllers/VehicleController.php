<?php

namespace App\Http\Controllers\AdminControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{Branch, Vehicle, VehicleCategory, VehicleModel, VehicleProperty, VehicleImage, CustomerDocument, VehicleManufacturer, VehicleType, VehicleFeature,VehicleFeatureMapping,VehicleDocument, TripAmountCalculationRule, FuelType, Transmission, CarHost, CarEligibility, VehiclePriceDetail, VehicleModelPriceDetail };
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Rules\VerifyRcNumber;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VehicleController extends Controller
{
    public function getAllVehicleList(){
        hasPermission('vehicle');
        return view('admin.vehicles');
    }

  public function getInsertForm()
  {
    hasPermission('vehicle');
    $allBranches = Branch::all();
    /*$allModels = VehicleModel::all()->where('is_deleted', 0);
    $allCategory = VehicleCategory::all();*/
    $allVehicleTypes = VehicleType::select('type_id', 'name')->where('is_deleted', 0)->get();
    $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get();
    $branchDropDown = '';
    foreach ($allBranches as $branch) {
      $branchDropDown .= '<option value="' . $branch->branch_id . '">' . $branch->name . '</option>';
    }
    $vehicleFeature = VehicleFeature::where('is_deleted', 0)->get();
    $carHost = CarHost::where('is_deleted', 0)->get();

    
    return view('admin.vehicle.create', compact('branchDropDown', 'allVehicleTypes', 'rules', 'vehicleFeature', 'carHost'));

  }

  public function getUpdateForm(Request $request , $vehicle_id)
  {
    hasPermission('vehicle');
    $vehicle = Vehicle::with(['vehicleEligibility.carHost'])->find($vehicle_id);

    $VehicleProperty = VehicleProperty::where('vehicle_id' , $vehicle_id)->first();
    $allBranches = Branch::all();
    $allModels = VehicleModel::all()->where('is_deleted', 0);
    $allCategory = VehicleCategory::all();
    $vehicleDocuments = DB::table('vehicle_documents')->where('vehicle_id' , $vehicle_id)->get();
    $rcDoc = [];
    $pucDoc = [];
    $insuranceDoc = [];
    if(isset($vehicleDocuments) && is_countable($vehicleDocuments) && count($vehicleDocuments) > 0){
        foreach ($vehicleDocuments as $key => $value) {
            if(isset($value->document_type) && $value->document_type == 'rc_doc') {
                $rcDoc['id'][] = $value->document_id;
                $rcDoc['image'][] = $value->document_image_url;
                $rcDoc['expiryDate'][] = $value->expiry_date;
                $rcDoc['id_number'] = $value->id_number;
            }
            elseif(isset($value->document_type) && $value->document_type == 'puc_doc'){
                $pucDoc['id'] = $value->document_id;
                $pucDoc['image'] = $value->document_image_url;
                $pucDoc['expiryDate'] = $value->expiry_date;
                $pucDoc['id_number'] = $value->id_number;
            }
            elseif(isset($value->document_type) && $value->document_type == 'insurance_doc'){
                $insuranceDoc['id'] = $value->document_id;
                $insuranceDoc['image'] = $value->document_image_url;
                $insuranceDoc['expiryDate'] = $value->expiry_date;
                $insuranceDoc['id_number'] = $value->id_number;
            }
        }
    }
    $vehicleImage = VehicleImage::where('vehicle_id', $vehicle_id)->get();
    $vehicleCutoutImage = VehicleImage::where(['vehicle_id' => $vehicle_id, 'image_type' => 'cutout'])->first();
    $vehicleBannerImages = VehicleImage::where(['vehicle_id' => $vehicle_id, 'image_type' => 'banner'])->get();
    $vehicleRegularImages = VehicleImage::where(['vehicle_id' => $vehicle_id, 'image_type' => 'regular'])->get();
    
    // $vehicleImage = VehicleImage::select('image_type', 'image_url')
    // ->where('vehicle_id', $vehicle_id)
    // ->groupBy('image_type', 'image_url')
    // ->get();
    $branchDropDown = '';
    foreach ($allBranches as $branch) {
        if(isset($branch) && isset($branch->branch_id) && isset($vehicle->branch_id)){
          if ($branch->branch_id == $vehicle->branch_id) {
            $branchDropDown .= '<option value="' . $branch->branch_id . '" selected>' . $branch->name . '</option>';
          } else {
            $branchDropDown .= '<option value="' . $branch->branch_id . '">' . $branch->name . '</option>';
          }
        }
    }

    $avaliblityDropdown = '';
    if (isset($vehicle->availability) && $vehicle->availability == 1) {
      $avaliblityDropdown = '<option value="1" selected>Yes</option>
                            <option value="0">No</option>';
    } else {
      $avaliblityDropdown = '<option value="1">Yes</option>
                            <option value="0" selected>No</option>';
    }

    if ($vehicle->availability_calendar != '{}') {
      $dates = json_decode($vehicle->availability_calendar);
      $dateContainer = '';
      /*foreach ($dates->unavailable_dates as $date) {
        $dateContainer .= "<div class='card p-2'>
        <div class='form-group'>
            <input type='date' placeholder='please selecte the date' name='dates[]' value='" . $date->date . "' class='form-control ip-date' required>
        </div>
        <div class='form-group'>
            <input type='text' placeholder='please provide a reason' name='reasons[]' value='" . $date->reason . "' class='form-control ip-date-reason' required>
        </div>
        <div class='d-flex justify-content-end align-items-center'>
            <button class='btn btn-sm btn-danger remove-date' type='button' id='btn-remove-ipbox'>Remove</button>
        </div>
    </div>";
      }*/
    } else {
      $dateContainer = '';
    }
    $allVehicleTypes = VehicleType::select('type_id', 'name')->where('is_deleted', 0)->get();
    $vehicleFeature = VehicleFeature::where('is_deleted', 0)->get();
    $featureIds = VehicleFeatureMapping::where('vehicle_id', $vehicle_id)->pluck('feature_id')->toArray();
    $fuelType = FuelType::get();
    $transmission = Transmission::get();
    $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get();  
    $carHost = CarHost::where('is_deleted', 0)->get();
    $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicle_id)->get();
    return view('admin.vehicle.edit', compact('branchDropDown', 'avaliblityDropdown' ,'dateContainer','vehicle', 'VehicleProperty','vehicleDocuments','vehicleImage', 'allVehicleTypes', 'vehicleCutoutImage', 'vehicleBannerImages', 'vehicleRegularImages', 'rcDoc', 'pucDoc', 'insuranceDoc', 'vehicleFeature', 'featureIds', 'fuelType', 'transmission', 'rules', 'carHost', 'vehiclePriceDetails'));
  }

  public function insertVehicle(Request $request)
  {
    hasPermission('vehicle');
     $validator = Validator::make($request->all(), [
     // $validator = $request->validate([
        //'car_host' => 'required',
        'commission_percent' => 'required',
        //'branch' => 'required',
        'year' => 'required|numeric',
        'rental_price' => 'required|numeric',
        'extra_km_rate' => 'required|numeric',
        'extra_hour_rate' => 'required|numeric',
        'vehicle_type' => 'required',
        //'category' => 'required',
        'manufacturer' => 'required',
        'model' => 'required',
        'description' => 'required|max:500',
        'color' => 'required',
        'seating_capacity' => 'nullable|numeric',
        'mileage' => 'nullable',
        //'engine_cc' => 'nullable|numeric',
        //'fuel_capacity' => 'nullable|numeric',
        'licence_plate' => 'required',
        'availability' => 'required|in:0,1',
        'rc_expiry_date' => 'required|date',
        'puc_expiry_date' => 'required|date',
        'insurance_expiry_date' => 'required|date',
        //'rc_number' => [new VerifyRcNumber()],
        //'puc_number' => 'required',
        //'insurance_number' => 'required',
        'document_rc_image.*' => 'required|image|mimes:jpeg,jpg,png,gif,webp,svg',
        'document_puc_image' => 'required|image|mimes:jpeg,jpg,png,gif,webp,svg',
        'document_insurance_image' => 'required|image|mimes:jpeg,jpg,png,gif,webp,svg',
        'cutout_img' => 'required|image|mimes:jpeg,jpg,png,gif,webp,svg',
    ], [
        'commission_percent' => 'Commission Percent is required',
      //'car_host.required' => 'Car Host is required.',
      //'branch.required' => 'Branch is required.',
      'year.required' => 'Manufacture Year is required.',
      'rental_price.required' => 'Rental Price is required.',
      'extra_km_rate.required' => 'Extra Km Rate Price is required.',
      'extra_hour_rate.required' => 'Extra Hour Rate Price is required.',
      'vehicle_type' => 'Vehicle type is required',
      //'category.required' => 'Category is required.',
      'manufacturer.required' => 'Category is required.',
      'model.required' => 'Model is required.',
      'description.required' => 'Description is required.',
      'description.max' => 'Description length is max 500 characters',
      'color.required' => 'Color is required.',
      'licence_plate.required' => 'Registration Number is required.',
      'availability.required' => 'Availability is required.',
      'availability.in' => 'Availability should be either Yes or No.',
      'rc_expiry_date.required' => 'RC Expiry Date is required.',
      'puc_expiry_date.required' => 'PUC Expiry Date is required.',
      'insurance_expiry_date.required' => 'Insurance Expiry Date is required.',
      // 'rc_number.required' => 'RC Number is required.',
      // 'puc_number.required' => 'PUC Number is required.',
      // 'insurance_number.required' => 'Insurance Number is required.',
      'document_rc_image.*.required' => 'RC Document image is required',
      'document_rc_image.*.mimes' => 'You can select only Image',
      'document_puc_image.required' => 'PUC Document image is required',
      'document_puc_image.mimes' => 'You can select only Image',
      'document_insurance_image.required' => 'Document Insurance image is required',
      'document_insurance_image.mimes' => 'You can select only Image',
      'cutout_img.required' => 'Document image is required',
      'cutout_img.mimes' => 'You can select only Image',
    ]);
    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    $Vehicle = new Vehicle();
    $Vehicle->branch_id = $request->branch;
    $Vehicle->model_id = $request->model;
    //$Vehicle->category_id = $request->category;
    //$Vehicle->type_id = $request->vehicle_type;
    //$Vehicle->manufacturer_id = $request->manufacturer;
    $Vehicle->year = $request->year;
    $Vehicle->description = $request->description;
    $Vehicle->color = $request->color;
    $Vehicle->license_plate = $request->licence_plate;
    $Vehicle->availability = $request->availability;
    $Vehicle->is_deleted = 0;
    $Vehicle->rental_price = $request->rental_price;
    //$Vehicle->availability_calendar = $jsonResult;
    $Vehicle->extra_km_rate = $request->extra_km_rate;
    $Vehicle->extra_hour_rate = $request->extra_hour_rate;
    $Vehicle->commission_percent = $request->commission_percent ?? 0;
    $Vehicle->created_at = now();
    $Vehicle->updated_at = now();
    //$Vehicle->chassis_no = $request->chassis_no ?? '';
    $Vehicle->save();

    $priceCalc = $request->priceCalc;
    $rentalPrice = $rentalPriceHour = 0;
    asort($priceCalc);// make sort based on its value on ascending order
    if(is_countable($priceCalc) && count($priceCalc) > 0){
        foreach ($priceCalc as $key => $value) {
            if($value > 0){
                $rentalPrice = $value;
                $rentalPriceHour = $key;
                break;
            }
        }
    }
    krsort($priceCalc); //make sort based on its key on descending order
    $multipliers = []; // Array to hold the multipliers
    foreach ($priceCalc as $key => $value) {
        $multiplierVal = 0;
        if($rentalPrice <= $value){
            $multiplierVal = ($value / $rentalPrice);
        }
        $multipliers[$key][$value] = round($multiplierVal, 2);
    }
    if(is_countable($multipliers) && count($multipliers) > 0){
        foreach ($multipliers as $key => $value) {
            $vehiclePriceDetail = new VehiclePriceDetail();
            $vehiclePriceDetail->vehicle_id = $Vehicle->vehicle_id;
            $vehiclePriceDetail->rental_price = $rentalPrice;
            $vehiclePriceDetail->hours = $key;
            foreach ($value as $k => $v) {
                $vehiclePriceDetail->rate = $k;
                $vehiclePriceDetail->multiplier = $v;
                $perHourRate = $k / $key;
                $vehiclePriceDetail->per_hour_rate = number_format(($perHourRate), 2);
                $vehiclePriceDetail->unlimited_km_trip_amount = $k * 1.3;
            }   
            $vehiclePriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
            $vehiclePriceDetail->trip_amount_km_limit = calculateKmLimit($key)." Km";
            $vehiclePriceDetail->save();   
        }
    }

    if(isset($request->feature) && is_countable($request->feature) && count($request->feature) > 0)
    {
        foreach ($request->feature as $key => $value) {
            $vehicleFeatureMapping = new VehicleFeatureMapping();
            $vehicleFeatureMapping->vehicle_id = $Vehicle->vehicle_id;
            $vehicleFeatureMapping->feature_id  = $value;
            $vehicleFeatureMapping->save();
        }
    }
    $mileage = intval(preg_replace('/[^0-9]/', '', $request->mileage));
    $engine_cc_numeric = intval(preg_replace('/[^0-9]/', '', $request->engine_cc));
    $fuel_capacity_numeric = intval(preg_replace('/[^0-9]/', '', $request->fuel_capacity));    

    // Insert into VehicleProperty
    $VehicleProperty = new VehicleProperty();
    $VehicleProperty->vehicle_id = $Vehicle->vehicle_id;
    $VehicleProperty->mileage = isset($mileage)?$mileage:NULL;
    $VehicleProperty->fuel_type_id = isset($request->fuel_type)?$request->fuel_type:NULL;
    $VehicleProperty->transmission_id  = isset($request->vehicle_transmission)?$request->vehicle_transmission:NULL;
    $VehicleProperty->seating_capacity = isset($request->seating_capacity) ? $request->seating_capacity : NULL;
    $VehicleProperty->engine_cc = isset($engine_cc_numeric)?$engine_cc_numeric:NULL;
    $VehicleProperty->fuel_capacity = isset($fuel_capacity_numeric)?$fuel_capacity_numeric:NULL;
    $VehicleProperty->created_at = now();
    $VehicleProperty->updated_at = now();
    $VehicleProperty->save();

    // Insert into Vehicle Host
    /*$carHostEligibility = new CarEligibility();
    $carHostEligibility->vehicle_id = $Vehicle->vehicle_id;
    $carHostEligibility->car_hosts_id = $request->car_host;
    $carHostEligibility->save();*/

    try{
        if(isset($request->cutout_img) && $request->cutout_img != '')
        {
            $file = $request->cutout_img;
            $extension = $file->getClientOriginalExtension();
            $filename = 'cutout_img_'.time() . '_' . uniqid() . '.' . $extension; // Append a unique identifier to the filename
            $file->move(public_path('images/vehicle_images/'), $filename);
            $vehicleImg = new VehicleImage();
            $vehicleImg->vehicle_id = $Vehicle->vehicle_id;
            $vehicleImg->image_type = 'cutout';
            $vehicleImg->image_url = $filename;
            $vehicleImg->save();
        }
    } catch (\Exception $e) {} 

    try{
        if(isset($request->bannerimgs) && is_countable($request->bannerimgs) && count($request->bannerimgs) > 0){
            foreach($request->bannerimgs as $key => $val){
                $extension = explode('/', mime_content_type($val))[1];
                $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $val));
                $filename = 'banner_img_'.time() . '_' . uniqid() . '.' . $extension;
                $path = public_path().'/images/vehicle_images/'.$filename;
                file_put_contents($path, $decodedImage);
                $vehicleImg = new VehicleImage();
                $vehicleImg->vehicle_id = $Vehicle->vehicle_id;
                $vehicleImg->image_type = 'banner';
                $vehicleImg->image_url = $filename;
                $vehicleImg->save();
            }
        }
    } catch (\Exception $e) {} 

    try{
        if(isset($request->regularimgs) && is_countable($request->regularimgs) && count($request->regularimgs) > 0){
            foreach($request->regularimgs as $key => $val){
                $extension = explode('/', mime_content_type($val))[1];
                $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $val));
                $filename = 'regular_img_'.time() . '_' . uniqid() . '.' . $extension;
                $path = public_path().'/images/vehicle_images/'.$filename;
                file_put_contents($path, $decodedImage);
                $vehicleImg = new VehicleImage();
                $vehicleImg->vehicle_id = $Vehicle->vehicle_id;
                $vehicleImg->image_type = 'regular';
                $vehicleImg->image_url = $filename;
                $vehicleImg->save();
            }
        }  
    } catch (\Exception $e) {}   

    /*$requestData = $request->addMoreInputFields;
    foreach ($requestData as $data) {
        foreach ($data['image_url'] as $imageUrl) {
              $file = $imageUrl;
              $extension = $file->getClientOriginalExtension();
              $filename = time() . '_' . uniqid() . '.' . $extension; // Append a unique identifier to the filename
              $file->move(public_path('images/vehicle_images'), $filename);
              $image = new VehicleImage();
              $image->vehicle_id = $Vehicle->vehicle_id;
              $image->image_type = $data['image_type'];
              $image->image_url = $filename;
              $image->save();
        }
    }*/

    DB::beginTransaction();
    try {
        if(isset($request->document_rc_image) && is_countable($request->document_rc_image) && count($request->document_rc_image) > 0){
            foreach($request->document_rc_image as $key => $val){
                $extension = $val->getClientOriginalExtension();
                $filename = 'doc_rc_img_'.$key.'_'.time() . '_' . uniqid() . '.' . $extension;
                $val->move(public_path('images/documents/'), $filename);
                $vehicleDoc = new VehicleDocument();
                $vehicleDoc->vehicle_id = $Vehicle->vehicle_id;
                $vehicleDoc->document_type = 'rc_doc';
                //$vehicleDoc->id_number = $request->rc_number;
                $vehicleDoc->expiry_date = $request->rc_expiry_date;
                $vehicleDoc->is_approved = 1;
                $vehicleDoc->approved_by = 1;
                $vehicleDoc->document_image_url = $filename;
                $vehicleDoc->created_at = now();
                $vehicleDoc->updated_at = now();
                $vehicleDoc->save();
            }
        }  
        if(isset($request->document_puc_image)){
            $file = $request->document_puc_image;
            $extension = $file->getClientOriginalExtension();
            $filename = 'doc_puc_img_'.time() . '_' . uniqid() . '.' . $extension;
            $file->move(public_path('images/documents'), $filename);
            $vehicleDoc = new VehicleDocument();
            $vehicleDoc->vehicle_id = $Vehicle->vehicle_id;
            $vehicleDoc->document_type = 'puc_doc';
            //$vehicleDoc->id_number = $request->puc_number;
            $vehicleDoc->expiry_date = $request->puc_expiry_date;
            $vehicleDoc->is_approved = 1;
            $vehicleDoc->approved_by = 1;
            $vehicleDoc->document_image_url = $filename;
            $vehicleDoc->created_at = now();
            $vehicleDoc->updated_at = now();
            $vehicleDoc->save();
        }
        if(isset($request->document_insurance_image)){
            $file = $request->document_insurance_image;
            $extension = $file->getClientOriginalExtension();
            $filename = 'doc_insurance_img_'.time() . '_' . uniqid() . '.' . $extension;
            $file->move(public_path('images/documents'), $filename);
            $vehicleDoc = new VehicleDocument();
            $vehicleDoc->vehicle_id = $Vehicle->vehicle_id;
            $vehicleDoc->document_type = 'insurance_doc';
            //$vehicleDoc->id_number = $request->insurance_number;
            $vehicleDoc->expiry_date = $request->insurance_expiry_date;
            $vehicleDoc->is_approved = 1;
            $vehicleDoc->approved_by = 1;
            $vehicleDoc->document_image_url = $filename;
            $vehicleDoc->created_at = now();
            $vehicleDoc->updated_at = now();
            $vehicleDoc->save();
        } 

        //Add Availability Calender
        if(isset($request->calender_start_date) && is_countable($request->calender_start_date) && count($request->calender_start_date) > 0 && isset($request->calender_end_date) && is_countable($request->calender_end_date) && count($request->calender_end_date) > 0){
            $calArray = [];
            foreach ($request->calender_start_date as $key => $value) {
                if(isset($value) && isset($request->calender_end_date[$key])){
                    $calArray[$key]['start_date'] = $value;
                    $calArray[$key]['end_date'] = $request->calender_end_date[$key];
                    $calArray[$key]['reason'] = $request->reason[$key];
                }
            }
            $Vehicle->availability_calendar = json_encode($calArray);
            $Vehicle->save();
        }

        //Add Pricing Control Details
        /*$rules = TripAmountCalculationRule::select('id', 'hours', 'multiplier')->orderBy('hours', 'desc')->get();
        if(is_countable($rules) && count($rules) > 0){
            $pricingShowCase = $rules->map(function ($rule) use ($Vehicle) {
                $tripAmount = $rule->multiplier * $Vehicle->rental_price;
                $unKMtripAmount = ($rule->multiplier * $Vehicle->rental_price) * 1.3;
                $perHourRate = $tripAmount / $rule->hours; // Calculate per hour rate based on the total trip amount and duration
                $duration = ($rule->hours >= 24) ? round($rule->hours / 24, 2) . ' days' : $rule->hours . ' hours';
                $durationHoursLimit = calculateKmLimit($rule->hours);
                return [
                    'duration' => $duration,
                    'per_hour_rate' => number_format(($perHourRate), 2),
                    'trip_amount' => $tripAmount,
                    'trip_amount_km_limit' => $durationHoursLimit." Km",
                    'unlimited_km_trip_amount' => $unKMtripAmount,
                ];
            });
            if(is_countable($pricingShowCase) && count($pricingShowCase) > 0){
                foreach($pricingShowCase as $k => $v){
                    $vehiclePricingControl = new VehiclePricingControl();
                    $vehiclePricingControl->vehicle_id = $Vehicle->vehicle_id;
                    $vehiclePricingControl->duration = $v['duration'];
                    $vehiclePricingControl->per_hour_rate = $v['per_hour_rate'];
                    $vehiclePricingControl->trip_amount = $v['trip_amount'];
                    $vehiclePricingControl->trip_amount_km_limit = $v['trip_amount_km_limit'];
                    $vehiclePricingControl->unlimited_km_trip_amount = $v['unlimited_km_trip_amount'];
                    $vehiclePricingControl->save();    
                }
            }
        }*/

        logAdminActivity("Vehicle Creation", $Vehicle);
        DB::commit();
     } catch (\Exception $e) {
        DB::rollback();
     }

    return redirect('/admin/vehicles')->with('success', 'Vehicle added successfully!');
  }

  public function updateVehicle(Request $request)
  {
    hasPermission('vehicle');
    // $dates = !empty($request->dates) ? explode(',', $request->dates) : [];
    // $reasons = !empty($request->reasons) ? explode(',', $request->reasons) : [];

    // if (empty($dates) && empty($reasons)) {
    //   $jsonResult = json_encode((object)[]);
    // } else {
    //   $unavailableDates = [];
    //   for ($i = 0; $i < count($dates); $i++) {
    //     $reason = isset($reasons[$i]) ? $reasons[$i] : "";
    //     $unavailableDates[] = ['date' => $dates[$i], 'reason' => $reason];
    //   }
    //   $result = ['unavailable_dates' => $unavailableDates];
    //   $jsonResult = json_encode($result);
    // }
    
    $oldVehicleFeature = [];
    $newVehicleFeature = [];
    DB::beginTransaction();

    try {
    // Vehicle 
        $vehicleData = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        $oldVehicleAvailabilityDates = $vehicleData->availability_calendar;
        $oldVehicle = clone $vehicleData;
    
        $vehicleData->branch_id = $request->branch;
        $vehicleData->model_id = $request->model;
        //$vehicleData->category_id = $request->category;
        $vehicleData->year = $request->year;
        $vehicleData->description = $request->description;
        $vehicleData->color = $request->color;
        $vehicleData->license_plate = $request->licence_plate;
        $vehicleData->availability = $request->availability;
        $vehicleData->rental_price = $request->rental_price;
        $vehicleData->extra_km_rate = $request->extra_km_rate;
        $vehicleData->extra_hour_rate = $request->extra_hour_rate;
        //$vehicleData->availability_calendar = $jsonResult;
        $vehicleData->updated_at = now();
        $vehicleData->commission_percent = $request->commission_percent ?? 0;
        //$vehicleData->chassis_no = $request->chassis_no ?? '';
        $vehicleData->save();
        $newVehicle = $vehicleData;

    // Vehicle Properties
        // Extract numeric part of engine displacement (remove "cc")
        $engine_cc_numeric = intval(preg_replace('/[^0-9]/', '', $request->engine_cc));
        $fuel_capacity_numeric = intval(preg_replace('/[^0-9]/', '', $request->fuel_capacity));
        $mileage = intval(preg_replace('/[^0-9]/', '', $request->mileage));

        $propertyData = VehicleProperty::where('vehicle_id', $request->vehicle_id)->first();
        $oldVehicleProperty = '';
        if($propertyData != ''){
            $oldVehicleProperty = clone $propertyData;
            $propertyData->mileage = $mileage;
            $propertyData->fuel_type_id = $request->fuel_type;
            $propertyData->transmission_id = $request->vehicle_transmission;
            $propertyData->seating_capacity = $request->seating_capacity;
            $propertyData->engine_cc = $engine_cc_numeric;
            $propertyData->fuel_capacity = $fuel_capacity_numeric;
            $propertyData->updated_at = now();
            $propertyData->save();
        }
        $newVehicleProperty = $propertyData;

        $priceCalc = $request->priceCalc ?? '';
        if($priceCalc != '') {
            $rentalPrice = $rentalPriceHour = 0;
            asort($priceCalc);// make sort based on its value on ascending order
            if(is_countable($priceCalc) && count($priceCalc) > 0){
                foreach ($priceCalc as $key => $value) {
                    if($value > 0){
                        $rentalPrice = $value;
                        $rentalPriceHour = $key;
                        break;
                    }
                }
            }
            krsort($priceCalc); //make sort based on its key on descending order
            $multipliers = []; // Array to hold the multipliers
            foreach ($priceCalc as $key => $value) {
                $multiplierVal = 0;
                if($rentalPrice <= $value){
                    $multiplierVal = ($value / $rentalPrice);
                }
                $multipliers[$key][$value] = round($multiplierVal, 2);
            }

            $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicleData->vehicle_id)->get();
            if(is_countable($vehiclePriceDetails) && count($vehiclePriceDetails) > 0){
                foreach ($vehiclePriceDetails as $key => $value) {
                    $value->delete();
                }
            }
            if(is_countable($multipliers) && count($multipliers) > 0){
                foreach ($multipliers as $key => $value) {
                    $vehiclePriceDetail = new VehiclePriceDetail();
                    $vehiclePriceDetail->vehicle_id = $vehicleData->vehicle_id;
                    $vehiclePriceDetail->rental_price = $rentalPrice;
                    $vehiclePriceDetail->hours = $key;
                    foreach ($value as $k => $v) {
                        $vehiclePriceDetail->rate = $k;
                        $vehiclePriceDetail->multiplier = $v;
                        $perHourRate = $k / $key;
                        $vehiclePriceDetail->per_hour_rate = number_format(($perHourRate), 2);
                        $vehiclePriceDetail->unlimited_km_trip_amount = $k * 1.3;
                    }
                    $vehiclePriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
                    $vehiclePriceDetail->trip_amount_km_limit = calculateKmLimit($key)." Km";
                    $vehiclePriceDetail->save();   
                }
            }
        }
        //Temporary Commented Vehicle Host
        /*$carHostEligibility = CarEligibility::where('vehicle_id', $request->vehicle_id)->first();
        $carHostEligibility->car_hosts_id = $request->car_host;
        $carHostEligibility->save();*/

    // Vehicle Documents
        try{
            $oldVehicleDoc = VehicleDocument::where('vehicle_id', $request->vehicle_id)->get();
            if(isset($request->rc_expiry_date) && $request->rc_expiry_date != ''){
                if(isset($request->document_rc_image) && is_countable($request->document_rc_image) && count($request->document_rc_image) > 0){
                    $vDoc = VehicleDocument::where(['vehicle_id' => $request->vehicle_id, 'document_type' => 'rc_doc'])->get();
                    foreach($vDoc as $key => $val){
                        $path = public_path().'/images/documents/'.$val->document_image_url;
                        if(file_exists($path)){
                            unlink($path);
                        }
                        $val->delete();
                    }
                    foreach($request->document_rc_image as $key => $val){
                        $extension = $val->getClientOriginalExtension();
                        $filename = 'doc_rc_img_'.$key.'_'.time() . '_' . uniqid() . '.' . $extension;
                        $val->move(public_path('images/documents'), $filename);
                        $vehicleDoc = new VehicleDocument();
                        $vehicleDoc->vehicle_id = $request->vehicle_id;
                        $vehicleDoc->document_type = 'rc_doc';
                        //$vehicleDoc->id_number = $request->rc_number;
                        $vehicleDoc->expiry_date = $request->rc_expiry_date;
                        $vehicleDoc->is_approved = 1;
                        $vehicleDoc->approved_by = 1;
                        $vehicleDoc->document_image_url = $filename;
                        $vehicleDoc->created_at = now();
                        $vehicleDoc->updated_at = now();
                        $vehicleDoc->save();
                    }
                }else{
                    $vDoc = VehicleDocument::where(['vehicle_id' => $request->vehicle_id, 'document_type' => 'rc_doc'])->get();
                    if(isset($vDoc) && is_countable($vDoc) && count($vDoc) > 0){
                        foreach ($vDoc as $key => $value) {
                            $value->expiry_date = $request->rc_expiry_date;
                            //$value->id_number = $request->rc_number;
                            $value->is_approved = 1;
                            $value->approved_by = 1;
                            $value->updated_at = now();
                            $value->save();
                        }
                    }
                }  

            }
            if(isset($request->puc_expiry_date) && $request->puc_expiry_date != NULL /*&& isset($request->puc_number) && $request->puc_number != NULL*/){
                $image = DB::table('vehicle_documents')
                    ->updateOrInsert(
                        ['vehicle_id' => $request->vehicle_id, 'document_type' => 'puc_doc'],
                        [
                            'expiry_date' => $request->puc_expiry_date,
                            //'id_number' => $request->puc_number,
                            'is_approved' => 1,
                            'approved_by' => 1,
                            'updated_at' => now()
                        ]
                    );
            }
            if(isset($request->document_puc_image) && $request->document_puc_image != NULL){
                $file = $request->file('document_puc_image');
                $extension = $file->getClientOriginalExtension();
                $filename = 'doc_puc_img'.time() . '_' . uniqid() . '.' . $extension; 
                $file->move(public_path('images/documents'), $filename);
                $image = DB::table('vehicle_documents')
                        ->updateOrInsert(
                            ['vehicle_id' => $request->vehicle_id, 'document_type' => 'puc_doc'],
                            [
                                'document_image_url' => $filename,
                                'updated_at' => now()
                            ]
                        );
            }
            if(isset($request->insurance_expiry_date) && $request->insurance_expiry_date != NULL /*&& isset($request->insurance_number) && $request->insurance_number != NULL*/){
                $image = DB::table('vehicle_documents')
                    ->updateOrInsert(
                        ['vehicle_id' => $request->vehicle_id, 'document_type' => 'insurance_doc'],
                        [
                            'expiry_date' => $request->insurance_expiry_date,
                            //'id_number' => $request->insurance_number,
                            'is_approved' => 1,
                            'approved_by' => 1,
                            'updated_at' => now()
                        ]
                    );
            }
            if(isset($request->document_insurance_image) && $request->document_insurance_image != NULL){
                $file = $request->file('document_insurance_image');
                $extension = $file->getClientOriginalExtension();
                $filename = 'doc_insurance_img'.time() . '_' . uniqid() . '.' . $extension; 
                $file->move(public_path('images/documents'), $filename);
                $image = DB::table('vehicle_documents')
                        ->updateOrInsert(
                            ['vehicle_id' => $request->vehicle_id, 'document_type' => 'insurance_doc'],
                            [
                                'document_image_url' => $filename,
                                'updated_at' => now()
                            ]
                        );
            }

            $vehicleFeatureIdsArr = VehicleFeatureMapping::where('vehicle_id', $request->vehicle_id)->pluck('feature_id')->toArray();
            $oldVehicleFeature = $vehicleFeatureIdsArr;

            if(isset($request->feature) && is_countable($request->feature) && count($request->feature) > 0)
            {   
                $vehicleFeatureIds = VehicleFeatureMapping::where('vehicle_id', $request->vehicle_id)->delete();
                foreach ($request->feature as $key => $value) {
                    $vFeatureMapping = new VehicleFeatureMapping();
                    $vFeatureMapping->vehicle_id = $request->vehicle_id;
                    $vFeatureMapping->feature_id = $value;
                    $vFeatureMapping->save();
                }
            }
            $newVehicleFeatureIdsArr = VehicleFeatureMapping::where('vehicle_id', $request->vehicle_id)->pluck('feature_id')->toArray();
            $newVehicleFeature = $newVehicleFeatureIdsArr;
            
        } catch (\Exception $e) {}         
        try{
            if(isset($request->cutout_img) && $request->cutout_img != '')
            {
                $file = $request->cutout_img;
                $extension = $file->getClientOriginalExtension();
                $filename = 'cutout_img_'.time() . '_' . uniqid() . '.' . $extension; // Append a unique identifier to the filename
                $file->move(public_path('images/vehicle_images/'), $filename);
                $vehicleImg = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'cutout')->first();
                if($vehicleImg != ''){
                    $parsedUrl = parse_url($vehicleImg->image_url);
                    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                    $path = public_path($path);
                    if (file_exists($path)){
                        unlink($path);
                    }    
                }else{
                    $vehicleImg = new VehicleImage();
                    $vehicleImg->vehicle_id = $request->vehicle_id;
                    $vehicleImg->image_type = 'cutout';
                }
                $vehicleImg->image_url = $filename;
                $vehicleImg->save();
            }
        } catch (\Exception $e) {} 

        /*$requestData = $request->addMoreInputFields; 
        if(isset($requestData) && is_countable($requestData) && count($requestData) > 0){
            foreach ($requestData as $data) {
                foreach ($data['image_url'] as $imageUrl) {

                    $file = $imageUrl;
                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '.' . $extension; // Append a unique identifier to the filename
                    $file->move(public_path('images/vehicle_images'), $filename);
                    
                    $image = new VehicleImage();
                    $image->vehicle_id = $request->vehicle_id;
                    $image->image_type = $data['image_type'];
                    $image->image_url = $filename;
                    $image->save();
                }
            }
        }*/

        //Comparing and Delete Existing from the Banner Images
        if(isset($request->bannerimgsOld) && is_countable($request->bannerimgsOld) && count($request->bannerimgsOld) > 0){
            $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'banner')->get();
            foreach ($vehicleImgs as $k => $v) {
                if(!in_array($v->image_url, $request->bannerimgsOld)){
                    $parsedUrl = parse_url($v->image_url);
                    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                    $path = public_path($path);
                    if (file_exists($path)){
                        unlink($path);
                    }
                    $v->delete();
                }
            }
        }else{
            $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'banner')->get();
            foreach ($vehicleImgs as $k => $v) {
                $v->delete();
            }
        }
        //Comparing and Delete Existing from the Regular Images
        if(isset($request->regularimgsOld) && is_countable($request->regularimgsOld) && count($request->regularimgsOld) > 0){
            $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'regular')->get();
            foreach ($vehicleImgs as $k => $v) {
                if(!in_array($v->image_url, $request->regularimgsOld)){
                    $parsedUrl = parse_url($v->image_url);
                    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                    $path = public_path($path);
                    if (file_exists($path)){
                        unlink($path);
                    }
                    $v->delete();
                }
            }
        }else{
            $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'regular')->get();
            foreach ($vehicleImgs as $k => $v) {
                $v->delete();
            }
        } 
        // Storing New Banners Images
        try{
            if(isset($request->bannerimgs) && is_countable($request->bannerimgs) && count($request->bannerimgs) > 0){
                foreach($request->bannerimgs as $key => $val){
                    $extension = explode('/', mime_content_type($val))[1];
                    $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $val));
                    $filename = 'banner_img_'.time() . '_' . uniqid() . '.' . $extension;
                    $path = public_path().'/images/vehicle_images/'.$filename;
                    file_put_contents($path, $decodedImage);
                    $vehicleImg = new VehicleImage();
                    $vehicleImg->vehicle_id = $request->vehicle_id;
                    $vehicleImg->image_type = 'banner';
                    $vehicleImg->image_url = $filename;
                    $vehicleImg->save();
                }
            }
        } catch (\Exception $e) {} 
        // Storing New Regular Images
        try{
            if(isset($request->regularimgs) && is_countable($request->regularimgs) && count($request->regularimgs) > 0){
                foreach($request->regularimgs as $key => $val){
                    $extension = explode('/', mime_content_type($val))[1];
                    $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $val));
                    $filename = 'regular_img_'.time() . '_' . uniqid() . '.' . $extension;
                    $path = public_path().'/images/vehicle_images/'.$filename;
                    file_put_contents($path, $decodedImage);
                    $vehicleImg = new VehicleImage();
                    $vehicleImg->vehicle_id = $request->vehicle_id;
                    $vehicleImg->image_type = 'regular';
                    $vehicleImg->image_url = $filename;
                    $vehicleImg->save();
                }
            }  
        } catch (\Exception $e) {} 

        $newVehicleDoc = VehicleDocument::where('vehicle_id', $request->vehicle_id)->get();
        
        //Add Availability Calender
        $calExistArray = [];
        $calNewArray = [];
        if(isset($request->calender_start_date_exist) && is_countable($request->calender_start_date_exist) && count($request->calender_start_date_exist) > 0){
            foreach ($request->calender_start_date_exist as $key => $value) {
                if(isset($value) && isset($request->calender_end_date_exist[$key])){
                    $calExistArray[$key]['start_date'] = $value;
                    $calExistArray[$key]['end_date'] = $request->calender_end_date_exist[$key];
                    $calExistArray[$key]['reason'] = $request->reason_exist[$key];
                }
            }
        }
        if(isset($request->calender_start_date) && is_countable($request->calender_start_date) && count($request->calender_start_date) > 0){
            foreach ($request->calender_start_date as $key => $value) {
                if(isset($value) && isset($request->calender_end_date[$key])){
                    $calNewArray[$key]['start_date'] = isset($value)?$value:'';
                    $calNewArray[$key]['end_date'] = isset($request->calender_end_date[$key])?$request->calender_end_date[$key]:'';
                    $calNewArray[$key]['reason'] = isset($request->reason_exist[$key])?$request->reason[$key]:'';
                }
            }
        }
        $finalArr = array_merge($calExistArray,$calNewArray);
        $Vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        $Vehicle->availability_calendar = json_encode($finalArr);
        $Vehicle->save();
        $newVehicleAvailabilityDates = $Vehicle->availability_calendar;

        $oldArr = [
            'vehicle' => $oldVehicle, 
            'vehicleProperty' => $oldVehicleProperty, 
            'vehicleDoc' => $oldVehicleDoc, 
            'vehicleFeature' => $oldVehicleFeature, 
            'vehicleAvailabilityDates' => $oldVehicleAvailabilityDates
        ];
        $newArr = [
            'vehicle' => $newVehicle, 
            'vehicleProperty' => $newVehicleProperty, 
            'vehicleDoc' => $newVehicleDoc, 
            'vehicleFeature' => $newVehicleFeature, 
            'vehicleAvailabilityDates' => $newVehicleAvailabilityDates
        ];

        logAdminActivity('Vehicle Updation', $oldArr, $newArr);
        
        DB::commit();
        return redirect('/admin/vehicles')->with('success', 'Vehicle Updated Successfully');

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json(['error' => 'Failed to update records: ' . $e->getMessage()], 500);
    }
    
  }

  public function getAllVehicles()
  {
    hasPermission('vehicle');
    $vehicles = Vehicle::with(['branch', 'model.manufacturer', 'properties', 'features', 'images'])->where('is_deleted',0)->get();

    return response()->json([
      'data' => $vehicles,
      'status' => true,
    ]);
  }

  public function deleteVehicle(Request $request)
  {
    $deletedVehicle = Vehicle::where('vehicle_id', $request->id)->first();
    $deletedVehicle->is_deleted = 1;
    $deletedVehicle->updated_at = now();
    $deletedVehicle->save();

    logAdminActivity("Vehicle Deletion", $deletedVehicle);      
    return response()->json(['message' => 'Vehicle Deleted Successfully', 'status' => true]);
  }

  public function deleteImage(Request $request)
  {
      $imageId = $request->input('imageId');
      $image = VehicleImage::find($imageId);

      if (!$image) {
          return response()->json(['error' => 'Image not found'], 404);
      }
      
      $image->delete();

      logAdminActivity("Vehicle Image Deletion");
      return response()->json(['message' => 'Image deleted successfully'], 200);
      
  }

  public function deleteDocument(Request $request)
  {
    $imageId = $request->input('documentId');

    $image = DB::table('vehicle_documents')->where('document_id', $imageId)->first();
    
    if (!$image) {
        return response()->json(['error' => 'Image not found'], 404);
    }
    
    $deleted = DB::table('vehicle_documents')->where('document_id', $imageId)->delete();
    
    if ($deleted) {
        logAdminActivity("Vehicle Document Image Deleted");
        return response()->json(['success' => 'Image deleted successfully']);
    } else {
        return response()->json(['error' => 'Failed to delete image'], 500);
    }
    
  }

  public function deleteCutoutImg(Request $request)
  {
    $imageId = $request->input('cutimgId');
    $image = DB::table('vehicle_images')->where('image_id', $imageId)->first();
    if (!$image) {
        return response()->json(['error' => 'Image not found'], 404);
    }
    
    $deleted = DB::table('vehicle_images')->where('image_id', $imageId)->delete();
    if ($deleted) {
        logAdminActivity("Vehicle Cutout Image Deleted");
        return response()->json(['success' => 'Image deleted successfully']);
    } else {
        return response()->json(['error' => 'Failed to delete image'], 500);
    }
  }

  public function insertVehicleModel(Request $request)
  {
    $vehicleManufacturerList = VehicleManufacturer::where('is_deleted', 0)->get();
    $vehicleCategoryList = VehicleCategory::select('category_id', 'name', 'is_deleted')->where('is_deleted', 0)->get();
    $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get();
    return view('admin.vehicleModel.create' , compact('vehicleManufacturerList', 'vehicleCategoryList', 'rules'));
  }

  public function vehicleModel(Request $request)
  { 
    $VehicleModel = new VehicleModel();
    $VehicleModel->name = $request->name;
    $VehicleModel->manufacturer_id = $request->manufacturer;
    $VehicleModel->is_deleted = 0;
    $VehicleModel->created_at = now();
    $VehicleModel->updated_at = now();
    $VehicleModel->category_id = $request->category;
    $VehicleModel->min_price = $request->min_price;
    $VehicleModel->max_price = $request->max_price;
    $VehicleModel->save();
    if ($request->hasFile('model_image')) {
        $file = $request->file('model_image');
        $filename = 'vehicle_model_'.$VehicleModel->id.'_'.time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/vehicle_models'), $filename);
        $VehicleModel->model_image = $filename;
        $VehicleModel->save();
    }
    logAdminActivity("Vehicle Model Creation", $VehicleModel);
    // STORE MIN PRICE DETAILS
    $minPriceCalc = $request->minPriceCalc;
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
    krsort($minPriceCalc); 
    $minMultipliers = [];
    foreach ($minPriceCalc as $key => $value) {
        $multiplierVal = 0;
        if($rentalPrice <= $value){
            $multiplierVal = ($value / $rentalPrice);
        }
        $minMultipliers[$key][$value] = round($multiplierVal, 2);
    }
    if(is_countable($minMultipliers) && count($minMultipliers) > 0){
        foreach ($minMultipliers as $key => $value) {
            $vehicleModelPriceDetail = new VehicleModelPriceDetail();
            $vehicleModelPriceDetail->vehicle_model_id = $VehicleModel->model_id;
            $vehicleModelPriceDetail->type = 1; //1 Means Min Price Detail
            $vehicleModelPriceDetail->rental_price = $rentalPrice;
            $vehicleModelPriceDetail->hours = $key;
            foreach ($value as $k => $v) {
                $vehicleModelPriceDetail->rate = $k;
                $vehicleModelPriceDetail->multiplier = $v;
                $perHourRate = $k / $key;
                $perHourRate = round($perHourRate, 2);
                //$vehicleModelPriceDetail->per_hour_rate = number_format(($perHourRate), 2);
                $vehicleModelPriceDetail->per_hour_rate = $perHourRate;
                $vehicleModelPriceDetail->unlimited_km_trip_amount = $k * 1.3;
            }   
            $vehicleModelPriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
            $vehicleModelPriceDetail->trip_amount_km_limit = calculateKmLimit($key)." Km";
            $vehicleModelPriceDetail->save();   
        }
    }
    // STORE MAX PRICE DETAILS
    $maxPriceCalc = $request->maxPriceCalc;
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
    krsort($maxPriceCalc); 
    $maxMultipliers = [];
    foreach ($maxPriceCalc as $key => $value) {
        $multiplierVal = 0;
        if($rentalPrice <= $value){
            $multiplierVal = ($value / $rentalPrice);
        }
        $maxMultipliers[$key][$value] = round($multiplierVal, 2);
    }
    if(is_countable($maxMultipliers) && count($maxMultipliers) > 0){
        foreach ($maxMultipliers as $key => $value) {
            $vehicleModelPriceDetail = new VehicleModelPriceDetail();
            $vehicleModelPriceDetail->type = 2; //2 Means Min Price Detail
            $vehicleModelPriceDetail->vehicle_model_id = $VehicleModel->model_id;
            $vehicleModelPriceDetail->rental_price = $rentalPrice;
            $vehicleModelPriceDetail->hours = $key;
            foreach ($value as $k => $v) {
                $vehicleModelPriceDetail->rate = $k;
                $vehicleModelPriceDetail->multiplier = $v;
                $perHourRate = $k / $key;
                $perHourRate = round($perHourRate, 2);
                //$vehicleModelPriceDetail->per_hour_rate = number_format(($perHourRate), 2);
                $vehicleModelPriceDetail->per_hour_rate = $perHourRate;
                $vehicleModelPriceDetail->unlimited_km_trip_amount = $k * 1.3;
            }   
            $vehicleModelPriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
            $vehicleModelPriceDetail->trip_amount_km_limit = calculateKmLimit($key)." Km";
            $vehicleModelPriceDetail->save();   
        }
    }
    
    return redirect('/admin/vehicle-models')->with('success', 'Model created sucessfully!');
  }

  public function getCategory(Request $request){
      $modelId = $request->modelId;
      $vehicleModels = VehicleModel::select('model_id','name', 'category_id')->where('model_id', $modelId)->first();
      $catName = $vehicleModels->category->name ?? ''; 
      return response()->json($catName);
  }

  public function getCategoryManufacturer(Request $request)
  {
      $selectedVehicleId = $request->selectedVehicleId;
      $vehicleCategories = VehicleCategory::select('category_id', 'name', 'vehicle_type_id')->where('vehicle_type_id', $selectedVehicleId)->where('name', 'not like', '%Popular%')->get();
      $vehicleManufacturers = VehicleManufacturer::select('manufacturer_id', 'name', 'vehicle_type_id')->where('vehicle_type_id', $selectedVehicleId)->get();
      $fuelTypes = FuelType::where('vehicle_type_id', $selectedVehicleId)->get();
      $transmissions = Transmission::where('vehicle_type_id', $selectedVehicleId)->get();
      return response()->json(['vehicleCategories' => $vehicleCategories, 'vehicleManufacturers' => $vehicleManufacturers, 'fuelTypes' => $fuelTypes, 'transmissions' => $transmissions]);
  }

  public function getModels(Request $request){
      $selectedManufacturerId = $request->selectedManufacturerId;
      $vehicleModels = VehicleModel::select('model_id','name', 'manufacturer_id')->where('manufacturer_id', $selectedManufacturerId)->get();

      return response()->json(['vehicleModels' => $vehicleModels]);
  }

  public function validateRcNumber(Request $request){
    $rcNumber = $request->value != '' ? $request->value : ''; //HJ01ME5678 (valid) HJ01ME5679, HJ01ME5279 (invalid)
    $response = '';
    if($rcNumber == ''){
        return true;
    }
    if($rcNumber != ''){
        $response = validateRc($rcNumber);
    }
    if($response != '' && isset($response['status']) && $response['status'] != '' && strtolower($response['status']) == 'valid'){
        return true;
    }
    return false;
  }

  public function validateEndDate(Request $request){
    $fromDate = isset($request->startDate)?$request->startDate:''; 
    $toDate = isset($request->value)?$request->value:'';
        if($fromDate != '' && $toDate != ''){
            $fromDate = Carbon::parse($fromDate);
            $toDate = Carbon::parse($toDate);
            if($fromDate >= $toDate){
               return false;
            }else{
                return true;    
            }
        }else{
            return false;    
        }
    }

    public function getMinMaxRentalPrice(Request $request){
        $selectedModlId = $request->selectedModlId;
        $minRentaPrice = $maxRentaPrice = 0;
        $modalPrices = vehicleModel::select('model_id', 'min_price', 'max_price')->where('model_id', $selectedModlId)->first();
        if($modalPrices != ''){
            $minRentaPrice = $modalPrices->min_price ?? 0;
            $maxRentaPrice = $modalPrices->max_price ?? 0;
        }
        return response()->json(['minRentaPrice' => $minRentaPrice, 'maxRentaPrice' => $maxRentaPrice]);
    }

    public function checkUpdatedPrice(Request $request){
        $modelId = $request->modelId ?? ''; 
        $enteredPriceVal = (float)$request->enteredPriceVal ?? '';
        $currentHours = $request->currentHours ?? '';
        $minPrice = $maxPrice = 0;
        $status = false;
        if($modelId != ''){
            if($enteredPriceVal != '' && $currentHours != ''){
                $minPrice = VehicleModelPriceDetail::where(['vehicle_model_id' => $modelId, 'type' => 1, 'hours' => $currentHours])->first();
                $maxPrice = VehicleModelPriceDetail::where(['vehicle_model_id' => $modelId, 'type' => 2, 'hours' => $currentHours])->first();
                if($minPrice != '' && $maxPrice != ''){
                    $minPrice = (float)$minPrice->rate;
                    $maxPrice = (float)$maxPrice->rate;
                    if($enteredPriceVal >= $minPrice && $enteredPriceVal <= $maxPrice){
                        $status = true;
                    }
                }else{
                    $status = true;
                }
            }else{
                $status = false;
            }
        }else{
            $status = true;
        }
            
        return response()->json(['minPrice' => $minPrice, 'maxPrice' => $maxPrice,'status' => $status]);
    }

    public function publishVehicle(Request $request){
        $vehicleId = $request->vehicleId;
        $vehicle = Vehicle::find($vehicleId);
        $data['status'] = false;
        $data['message'] = 'Something went Wrong';
        if($vehicle != '' && $vehicle->rental_price > 0){
            $vehicle->publish =  $request->status == 'publish' ? 1 : 0;
            $vehicle->save();
            $data['status'] = true;
            if($request->status == 'publish'){
                $data['message'] = 'Vehicle Published Successfully';
            }else{
                $data['message'] = 'Vehicle Un-Published Successfully';
            }
        }else{
            $data['message'] = 'You can not Publish this Vehicle due to its Rental Price is not added';
        }

        if($request->status == 'publish'){
            logAdminActivity("Vehicle Publish Activity", $vehicle);
        }
        else{
            logAdminActivity("Vehicle Un-Published Activity", $vehicle);
        }

        return response()->json($data);
    }
}
