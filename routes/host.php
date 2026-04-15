<?php

use Illuminate\Support\Facades\Route;

// HOST APP CONTROLLERS
use App\Http\Controllers\CarhostAppApis\{CarHostController, CarHostVehicleController, CarHostVehicleDetailsController};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('send-carhost-otp', [CarHostController::class, 'sendOtp']);
Route::post('verify-carhost-otp', [CarHostController::class, 'verifyOtpGenerateToken']);
Route::post('verify-carhost-active-account', [CarHostController::class, 'verifyOldNumberOTPAndGenerateToken']);
Route::get('carhost-settings', [CarHostController::class, 'settings']);
Route::get('carhost-static-messages', [CarHostController::class, 'staticMessages']);

Route::middleware('carhost-guard:api-carhost')->group(function () {
    //USER DETAILS
    Route::get('get-profile', [CarHostController::class, 'getProfile']);
    Route::get('get-bank-details', [CarHostController::class, 'getBankDetails']);
    Route::get('get-booking-status-list', [CarHostController::class, 'getBookingStatusList']);
    Route::get('get-booking-time-duration', [CarHostController::class, 'getBookingTimeDuration']);
    Route::post('update-profile', [CarHostController::class, 'updateProfile']);
    Route::post('update-mobile-number', [CarHostController::class, 'updateMobileNumber']);
    Route::post('update-mobile-number/verify-otp', [CarHostController::class, 'updateMobileNumberVerifyOTP']);
    Route::post('update-email-addr', [CarHostController::class, 'updateEmailAddr']);
    Route::post('update-email-addr/verify-otp', [CarHostController::class, 'updateEmailAddrVerifyOTP']);
    Route::post('store-bank-details', [CarHostController::class, 'storeBankDetails']);
    Route::post('store-gstinfo', [CarHostController::class, 'storeGstInfo']);
    Route::post('delete-bank', [CarHostController::class, 'deleteBank']);
    Route::post('store-pan-number', [CarHostController::class, 'storePanNumber']);
    Route::post('delete-user-account', [CarHostController::class, 'deleteAccount']);
    Route::post('logout', [CarHostController::class, 'logout']);
    Route::post('refresh', [CarHostController::class, 'refresh']);

    //GET VEHICLE DETAILS
    Route::get('get-vehicle-info', [CarHostVehicleDetailsController::class, 'getVehicleInfo']);
    Route::get('get-vehicle-features', [CarHostVehicleDetailsController::class, 'getVehicleFeatures']);
    Route::get('get-manufacturers', [CarHostVehicleDetailsController::class, 'getManufacturers']);
    Route::get('get-models', [CarHostVehicleDetailsController::class, 'getModels']);
    Route::get('get-years', [CarHostVehicleDetailsController::class, 'getYears']);
    Route::get('get-kmdriven', [CarHostVehicleDetailsController::class, 'getKmDriven']);
    Route::get('get-parking_type', [CarHostVehicleDetailsController::class, 'getParkingType']);
    Route::get('get-carhost-vehicles', [CarHostVehicleDetailsController::class, 'getCarHostVehicles']);
    Route::get('get-categories', [CarHostVehicleDetailsController::class, 'getCategories']);
    Route::get('get-home-vehicle-statuses', [CarHostVehicleDetailsController::class, 'getHomeVehicleStatuses']);
    Route::get('get-vehicle-pickuplocation-details', [CarHostVehicleDetailsController::class, 'getVehiclePickupLocationDetails']); 
    Route::get('get-fuels-transmissions', [CarHostVehicleDetailsController::class, 'getFuelAndTransmissions']);
    Route::get('get-vehicle-details', [CarHostVehicleDetailsController::class, 'getVehicleDetails']);
    Route::get('get-vehicle-dropdownval', [CarHostVehicleDetailsController::class, 'getVehicleDropdownValues']);
    Route::get('get-vehicle-desc', [CarHostVehicleDetailsController::class, 'getVehicleDesc']);
    Route::get('get-hold-vehicle-dates', [CarHostVehicleDetailsController::class, 'getHoldVehicleDates']);
    Route::get('get-listing-hours', [CarHostVehicleDetailsController::class, 'getListingHours']);
    Route::get('get-pricing-control', [CarHostVehicleDetailsController::class, 'getPricingControl']);
    Route::get('generate-start-otp/{booking_id}', [CarHostVehicleDetailsController::class, 'generateStartOtp']);
    Route::get('generate-end-otp/{booking_id}', [CarHostVehicleDetailsController::class, 'generateEndOtp']);
    Route::get('get-bookings/', [CarHostVehicleDetailsController::class, 'getBookings']);
    Route::get('get-booking-details/{booking_id}', [CarHostVehicleDetailsController::class, 'getBookingDetails']);
    Route::get('get-common-details', [CarHostVehicleDetailsController::class, 'getCommonDetails']);
    Route::get('get-insurance-puc-rc-details', [CarHostVehicleDetailsController::class, 'getInsurancePucRcDetails']);
    Route::get('get-vehicle-steps', [CarHostVehicleDetailsController::class, 'getVehicleSteps']);

    // STORE VEHICLE DETAILS
    Route::get('get-cities', [CarHostVehicleController::class, 'getCities']);
    Route::post('store-vehicle-eligibility', [CarHostVehicleController::class, 'storeVehicleEligibility']);
    Route::post('set-vehicle-properties', [CarHostVehicleController::class, 'setVehicleProperties']);
    Route::post('store-vehicle-images', [CarHostVehicleController::class, 'storeVehicleImages']);
    Route::post('store-vehicle-features', [CarHostVehicleController::class, 'storeVehicleFeatures']);
    Route::post('store-vehicle-desc', [CarHostVehicleController::class, 'storeVehicleDesc']);
    Route::post('store-pickuplocation-details', [CarHostVehicleController::class, 'storePickupLocationDetails']); 
    Route::post('store-hold-vehicle-dates', [CarHostVehicleController::class, 'storeHoldVehicleDates']);
    Route::post('delete-hold-vehicle-date', [CarHostVehicleController::class, 'deleteHoldVehicleDate']);
    Route::post('store-fasttag-details', [CarHostVehicleController::class, 'storeFastTagDetails']);
    Route::post('store-listing-control', [CarHostVehicleController::class, 'storeListingControl']);
    Route::post('store-fuel-transmission', [CarHostVehicleController::class, 'storeFuelTransmission']);
    Route::post('set-vehicle-location', [CarHostVehicleController::class, 'setVehicleLocation']);
    Route::post('delete-pickup-location', [CarHostVehicleController::class, 'deletePickuoLocation']);
    Route::post('update-pricing-details', [CarHostVehicleController::class, 'updatePricingDetails']);
    Route::post('upload-vehicle-journey-images', [CarHostVehicleController::class, 'uploadVehicleJourneyImages']);
    Route::post('publish-vehicle', [CarHostVehicleController::class, 'publishVehicle']);
    Route::post('delete-vehicle', [CarHostVehicleController::class, 'deleteVehicle']);
    Route::post('set-insurance-details', [CarHostVehicleController::class, 'setInsuranceDetails']);
    Route::post('set-puc-details', [CarHostVehicleController::class, 'setPucDetails']); 
    Route::post('store-vehicle-rcdetails', [CarHostVehicleController::class, 'storeVehicleRcDetails']);
    Route::post('store-vehicle-steps', [CarHostVehicleController::class, 'storeVehicleSteps']);
    
});