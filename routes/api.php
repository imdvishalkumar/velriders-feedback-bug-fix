<?php

use Illuminate\Support\Facades\Route;
// FRONT APP CONTROLLERS
use App\Http\Controllers\FrontAppApis\{BranchController, CategoryController, CustomerController, CustomerDocumentController, HomeController, RentalBookingController, VehicleController};
// HOST APP CONTROLLERS
//use App\Http\Controllers\CarhostAppApis\{CarHostController, CarHostVehicleController, CarHostVehicleDetailsController};
// ADMIN PANEL CONTROLLERS
// use App\Http\Controllers\AdminApis\{LoginController, VehicleDetailsController, CarHostManagementController, AdminApiController, AdminBookingController, CustomerDataController, VehicleInfoController, AdminBookingDetailsController};

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

 Route::get('testapi', [CustomerController::class, 'testApis']);
 Route::get('rental-booking/invoice/{booking_id}', [RentalBookingController::class, 'invoiceData']);
 Route::get('rental-booking/summary/{booking_id}', [RentalBookingController::class, 'summaryData']);
// Below Routes for Velriders
//Route::middleware('generic.token')->group(function () {

    Route::post('send-otp', [CustomerController::class, 'sendOTP']);
    Route::post('verify-otp', [CustomerController::class, 'verifyOTPAndGenerateToken']);
    Route::post('verify-active-account', [CustomerController::class, 'verifyOldNumberAndOTPAndGenerateToken']);
    Route::get('unauthenticated', [CustomerController::class, 'unauthenticated'])->name('unauthenticated');
    Route::get('app-status', [CustomerController::class, 'appStatus'])->name('app-status');
    Route::get('settings', [CustomerController::class, 'settings']);
    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::get('vehicle-details', [VehicleController::class, 'vehicleDetails']);
    /*Route::get('branches', [BranchController::class, 'index']);
    Route::get('branches-nearest', [BranchController::class, 'getNearestBranch']);*/
    Route::get('vehicle-types', [CategoryController::class, 'vehicleTypes']);

    Route::get('get-common-details', [CategoryController::class, 'getCommonDetails']);
    Route::get('available-vehicles-stories', [HomeController::class, 'HomescreenAvailableVehStories']);
    Route::get('get-vehicle-details-pricing-showcase', [HomeController::class, 'vehicleDetailsAndPricingShowcase']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('homescreen', [HomeController::class, 'homescreen']);
    Route::get('manufacturers', [VehicleController::class, 'manufacturers']);
    Route::get('models', [VehicleController::class, 'models']);
    Route::get('transmissions', [VehicleController::class, 'transmissions']);
    Route::get('fuelTypes', [VehicleController::class, 'fuelTypes']);
    Route::get('get-price-details', [RentalBookingController::class, 'getPriceDetails']);
    Route::get('get-pricing-showcase', [RentalBookingController::class, 'getPricingShowCase']);
    Route::get('get-coupons', [RentalBookingController::class, 'getCoupons']);
    Route::get('get-cities', [BranchController::class, 'getCities']);
    Route::get('city-nearest', [BranchController::class, 'getNearestCity']);
    Route::post('store-user-location-details', [RentalBookingController::class, 'storeUserLocationDetails']);
    Route::get('get-user-location-details', [RentalBookingController::class, 'getUserLocationDetails']);
    Route::post('store-guest-user', [CustomerController::class, 'storeGuestUser']);
    Route::get('get-guest-notifications', [CustomerController::class, 'getGuestNotifications']);
    Route::get('get-all-policies', [CustomerController::class, 'getAllPolicies']);
    Route::post('store-contact-us', [CustomerController::class, 'storeContactDetails']);
    Route::post('user-subscribes', [CustomerController::class, 'storeUserSubscribe']);
    Route::get('get-city-details', [CustomerController::class, 'getCityDetails']);
    Route::get('get-available-vehicles', [VehicleController::class, 'getAvailableVehicles']);
    Route::get('get-velrider-stories', [VehicleController::class, 'getVelriderStories']);

    Route::middleware('auth:api')->group(function () {
        Route::get('get-profile', [CustomerController::class, 'getProfile']);
        
        Route::get('filterData', [VehicleController::class, 'filterData']);
        Route::post('update-profile', [CustomerController::class, 'updateProfile']);
        Route::post('update-mobile-number', [CustomerController::class, 'updateMobileNumber']);
        Route::post('update-mobile-number/verify-otp', [CustomerController::class, 'updateMobileNumberVerifyOTP']);
        
        Route::post('update-email-address', [CustomerController::class, 'updateEmailAddress']);
        Route::post('update-email-address/verify-otp', [CustomerController::class, 'updateEmailAddressVerifyOTP']);

        Route::post('refresh', [CustomerController::class, 'refresh']);
        Route::post('delete-account', [CustomerController::class, 'deleteAccount']);
        Route::post('logout', [CustomerController::class, 'logout']);
        
        Route::post('upload-document', [CustomerDocumentController::class, 'uploadDocument']);
        Route::post('verify-govtid-document', [CustomerDocumentController::class, 'verifyGovtIdDocument']);
        Route::get('check-approval-status/{documentType}', [CustomerDocumentController::class, 'checkApprovalStatus']);
        Route::get('get-extend-price-details', [RentalBookingController::class, 'getExtendOrderPriceDetails']);
        Route::post('create-order', [RentalBookingController::class, 'store']);
        Route::post('extend-order', [RentalBookingController::class, 'extendOrder']);
        Route::post('cancel-order', [RentalBookingController::class, 'cancelOrder']);
        //Route::get('rental-bookings/history/{booking_id?}', [RentalBookingController::class, 'history']);
        Route::get('rental-bookings/history/', [RentalBookingController::class, 'history']);
        Route::get('rental-booking/details/{booking_id}', [RentalBookingController::class, 'bookingDetails']);
        Route::post('rental-booking/{booking_id}/upload-images', [RentalBookingController::class, 'uploadImages']);
        Route::get('rental-booking/{booking_id}/images', [RentalBookingController::class, 'getRentalBookingImages']);

        Route::post('rental-booking/{booking_id}/verify-start-journey', [RentalBookingController::class, 'verifyStartJourney']);
        Route::post('rental-booking/{booking_id}/verify-end-journey', [RentalBookingController::class, 'verifyEndJourney']);
        Route::get('rental-booking/{booking_id}/end-journey-details', [RentalBookingController::class, 'endJourneyDetails']);
        Route::post('rental-booking/{booking_id}/deduct-payment', [RentalBookingController::class, 'deductPayment']);
        Route::post('rental-booking/{booking_id}/admin-penalty-payment', [RentalBookingController::class, 'adminPenaltyPayment']);
        
        Route::post('rental-review-create', [RentalBookingController::class, 'rentalReview']);
        Route::post('success-payment-test/{booking_id}', [RentalBookingController::class, 'successPayment']);
        // Route::get('rental-booking/invoice/{booking_id}', [RentalBookingController::class, 'invoiceData']);
        // Route::get('rental-booking/summary/{booking_id}', [RentalBookingController::class, 'summaryData']);

        Route::post('verify-order', [RentalBookingController::class, 'verifyOrder']);
        Route::get('get-notifications', [RentalBookingController::class, 'getNotifications']);
        Route::get('get-referral_history', [RentalBookingController::class, 'getReferralHistory']);

        Route::post('store-bank-info', [RentalBookingController::class, 'storeBankInfo']);

    });

//});


/*---------------------------------------------------------------------------------------------------------------------------------------------*/

// Below Routes for VELRIDERS HOST
// Route::post('send-carhost-otp', [CarHostController::class, 'sendOtp']);
// Route::post('verify-carhost-otp', [CarHostController::class, 'verifyOtpGenerateToken']);
// Route::post('verify-carhost-active-account', [CarHostController::class, 'verifyOldNumberOTPAndGenerateToken']);
// Route::get('carhost-settings', [CarHostController::class, 'settings']);
// Route::get('carhost-static-messages', [CarHostController::class, 'staticMessages']);

// Route::prefix('carhost')->middleware('carhost-guard:api-carhost')->group(function () {
//     //USER DETAILS
//     Route::get('get-profile', [CarHostController::class, 'getProfile']);
//     Route::get('get-bank-details', [CarHostController::class, 'getBankDetails']);
//     Route::get('get-booking-status-list', [CarHostController::class, 'getBookingStatusList']);
//     Route::get('get-booking-time-duration', [CarHostController::class, 'getBookingTimeDuration']);
//     Route::post('update-profile', [CarHostController::class, 'updateProfile']);
//     Route::post('update-mobile-number', [CarHostController::class, 'updateMobileNumber']);
//     Route::post('update-mobile-number/verify-otp', [CarHostController::class, 'updateMobileNumberVerifyOTP']);
//     Route::post('update-email-addr', [CarHostController::class, 'updateEmailAddr']);
//     Route::post('update-email-addr/verify-otp', [CarHostController::class, 'updateEmailAddrVerifyOTP']);
//     Route::post('store-bank-details', [CarHostController::class, 'storeBankDetails']);
//     Route::post('store-gstinfo', [CarHostController::class, 'storeGstInfo']);
//     Route::post('delete-bank', [CarHostController::class, 'deleteBank']);
//     Route::post('store-pan-number', [CarHostController::class, 'storePanNumber']);
//     Route::post('delete-user-account', [CarHostController::class, 'deleteAccount']);
//     Route::post('logout', [CarHostController::class, 'logout']);
//     Route::post('refresh', [CarHostController::class, 'refresh']);

//     //GET VEHICLE DETAILS
//     Route::get('get-vehicle-info', [CarHostVehicleDetailsController::class, 'getVehicleInfo']);
//     Route::get('get-vehicle-features', [CarHostVehicleDetailsController::class, 'getVehicleFeatures']);
//     Route::get('get-manufacturers', [CarHostVehicleDetailsController::class, 'getManufacturers']);
//     Route::get('get-models', [CarHostVehicleDetailsController::class, 'getModels']);
//     Route::get('get-years', [CarHostVehicleDetailsController::class, 'getYears']);
//     Route::get('get-kmdriven', [CarHostVehicleDetailsController::class, 'getKmDriven']);
//     Route::get('get-parking_type', [CarHostVehicleDetailsController::class, 'getParkingType']);
//     Route::get('get-carhost-vehicles', [CarHostVehicleDetailsController::class, 'getCarHostVehicles']);
//     Route::get('get-categories', [CarHostVehicleDetailsController::class, 'getCategories']);
//     Route::get('get-home-vehicle-statuses', [CarHostVehicleDetailsController::class, 'getHomeVehicleStatuses']);
//     Route::get('get-vehicle-pickuplocation-details', [CarHostVehicleDetailsController::class, 'getVehiclePickupLocationDetails']); 
//     Route::get('get-fuels-transmissions', [CarHostVehicleDetailsController::class, 'getFuelAndTransmissions']);
//     Route::get('get-vehicle-details', [CarHostVehicleDetailsController::class, 'getVehicleDetails']);
//     Route::get('get-vehicle-dropdownval', [CarHostVehicleDetailsController::class, 'getVehicleDropdownValues']);
//     Route::get('get-vehicle-desc', [CarHostVehicleDetailsController::class, 'getVehicleDesc']);
//     Route::get('get-hold-vehicle-dates', [CarHostVehicleDetailsController::class, 'getHoldVehicleDates']);
//     Route::get('get-listing-hours', [CarHostVehicleDetailsController::class, 'getListingHours']);
//     Route::get('get-pricing-control', [CarHostVehicleDetailsController::class, 'getPricingControl']);
//     Route::get('generate-start-otp/{booking_id}', [CarHostVehicleDetailsController::class, 'generateStartOtp']);
//     Route::get('generate-end-otp/{booking_id}', [CarHostVehicleDetailsController::class, 'generateEndOtp']);
//     Route::get('get-bookings/', [CarHostVehicleDetailsController::class, 'getBookings']);
//     Route::get('get-booking-details/{booking_id}', [CarHostVehicleDetailsController::class, 'getBookingDetails']);
//     Route::get('get-common-details', [CarHostVehicleDetailsController::class, 'getCommonDetails']);
//     Route::get('get-insurance-puc-rc-details', [CarHostVehicleDetailsController::class, 'getInsurancePucRcDetails']);
//     Route::get('get-vehicle-steps', [CarHostVehicleDetailsController::class, 'getVehicleSteps']);

//     // STORE VEHICLE DETAILS
//     Route::get('get-cities', [CarHostVehicleController::class, 'getCities']);
//     Route::post('store-vehicle-eligibility', [CarHostVehicleController::class, 'storeVehicleEligibility']);
//     Route::post('set-vehicle-properties', [CarHostVehicleController::class, 'setVehicleProperties']);
//     Route::post('store-vehicle-images', [CarHostVehicleController::class, 'storeVehicleImages']);
//     Route::post('store-vehicle-features', [CarHostVehicleController::class, 'storeVehicleFeatures']);
//     Route::post('store-vehicle-desc', [CarHostVehicleController::class, 'storeVehicleDesc']);
//     Route::post('store-pickuplocation-details', [CarHostVehicleController::class, 'storePickupLocationDetails']); 
//     Route::post('store-hold-vehicle-dates', [CarHostVehicleController::class, 'storeHoldVehicleDates']);
//     Route::post('delete-hold-vehicle-date', [CarHostVehicleController::class, 'deleteHoldVehicleDate']);
//     Route::post('store-fasttag-details', [CarHostVehicleController::class, 'storeFastTagDetails']);
//     Route::post('store-listing-control', [CarHostVehicleController::class, 'storeListingControl']);
//     Route::post('store-fuel-transmission', [CarHostVehicleController::class, 'storeFuelTransmission']);
//     Route::post('set-vehicle-location', [CarHostVehicleController::class, 'setVehicleLocation']);
//     Route::post('delete-pickup-location', [CarHostVehicleController::class, 'deletePickuoLocation']);
//     Route::post('update-pricing-details', [CarHostVehicleController::class, 'updatePricingDetails']);
//     Route::post('upload-vehicle-journey-images', [CarHostVehicleController::class, 'uploadVehicleJourneyImages']);
//     Route::post('publish-vehicle', [CarHostVehicleController::class, 'publishVehicle']);
//     Route::post('delete-vehicle', [CarHostVehicleController::class, 'deleteVehicle']);
//     Route::post('set-insurance-details', [CarHostVehicleController::class, 'setInsuranceDetails']);
//     Route::post('set-puc-details', [CarHostVehicleController::class, 'setPucDetails']); 
//     Route::post('store-vehicle-rcdetails', [CarHostVehicleController::class, 'storeVehicleRcDetails']);
//     Route::post('store-vehicle-steps', [CarHostVehicleController::class, 'storeVehicleSteps']);
    
// });

// Route::post('admin-login', [LoginController::class, 'adminLogin']);
// Route::get('booking-invoice/{booking_id}', [LoginController::class, 'bookingInvoiceData']);
// Route::get('booking-summary/{booking_id}/{customer_id}', [LoginController::class, 'bookingSummaryData']);
// Route::get('customer-aggrement/{booking_id}', [LoginController::class, 'customerAggrement']);
// Route::post('forgot-password', [LoginController::class, 'forgotPassword']); 
// Route::post('forgot-password-verify-otp', [LoginController::class, 'forgotPasswordVerifyOtp']);
// Route::post('reset-password', [LoginController::class, 'resetPassword']);
// Route::prefix('admin')->middleware('auth:sanctum')->group( function () {  

//     // DASHBOARD
//     Route::get('get-dashboard-details', [AdminApiController::class, 'getDashboardDetails']);

//     // ADMIN
//     Route::get('get-all-admins', [AdminApiController::class, 'getAdmins']);
//     Route::get('get-all-roles', [AdminApiController::class, 'getAllRoles']);
//     Route::post('create-admin', [AdminApiController::class, 'createAdmin']);
//     Route::post('update-admin', [AdminApiController::class, 'updateAdmin']);
//     Route::post('delete-admin', [AdminApiController::class, 'deleteAdmin']);
   
//     // VEHICLE TYPE
//     Route::get('get-all-vehicle-types', [VehicleDetailsController::class, 'getAllVehicleTypes']);
//     Route::post('create-update-vehicle-types', [VehicleDetailsController::class, 'createOrUpdateVehicleTypes']);
//     Route::post('delete-vehicle-types', [VehicleDetailsController::class, 'deleteVehicleTypes']);

//     // VEHICLE CATEGORY
//     Route::get('get-vehicle-categories', [VehicleDetailsController::class, 'getVehicleCategories']);
//     Route::post('create-update-vehicle-category', [VehicleDetailsController::class, 'createOrUpdateVehicleCategories']);
//     Route::post('delete-vehicle-category', [VehicleDetailsController::class, 'deleteVehicleCategory']);

//     // VEHICLE FUEL TYPES
//     Route::get('get-fuel-types', [VehicleDetailsController::class, 'getAllFuelTypes']);
//     Route::post('create-update-fuel-type', [VehicleDetailsController::class, 'createOrUpdateFuelType']);
//     Route::post('delete-fuel-type', [VehicleDetailsController::class, 'deleteFuelType']);

//     // VEHICLE TRANSMISSION
//     Route::get('get-vehicle-transmissions', [VehicleDetailsController::class, 'getVehicleTransmissions']);
//     Route::post('create-update-vehicle-transmission', [VehicleDetailsController::class, 'createOrUpdateVehicleTrasmission']);
//     Route::post('delete-vehicle-transmission', [VehicleDetailsController::class, 'deleteVehicleTransmission']);

//     // VEHICLE FEATURES
//     Route::get('get-vehicle-features', [VehicleDetailsController::class, 'getVehicleFeatureList']);
//     Route::post('create-update-vehicle-feature', [VehicleDetailsController::class, 'createOrUpdateVehicleFeature']);
//     Route::post('delete-vehicle-features', [VehicleDetailsController::class, 'deleteVehicleFeatures']);

//     // VEHICLE MANUFACTURERS
//     Route::get('get-vehicle-manufacturers', [VehicleDetailsController::class, 'getManufacturerList']);
//     Route::post('create-update-vehicle-manufacturer', [VehicleDetailsController::class, 'createOrUpdateVehicleManufacturer']);
//     Route::post('delete-vehicle-manufacturer', [VehicleDetailsController::class, 'deleteVehicleManufacturer']);

//     // VEHICLE MODELS
//     Route::get('get-vehicle-models', [VehicleDetailsController::class, 'getVehicleModels']);
//     Route::post('create-update-vehicle-model', [VehicleDetailsController::class, 'createOrUpdateVehicleModel']);
//     Route::get('get-price-summary', [VehicleDetailsController::class, 'getPriceSummary']);
//     Route::post('delete-vehicle-model', [VehicleDetailsController::class, 'deleteVehicleModel']);

//     // VEHICLE  
//     Route::get('get-vehicles', [VehicleInfoController::class, 'getVehicles']);
//     Route::post('publish-vehicle', [VehicleInfoController::class, 'publishVehicles']);
//     Route::get('get-vehicle-page-data', [VehicleInfoController::class, 'getVehiclePageData']);
//     Route::get('get-depending-details', [VehicleInfoController::class, 'getDependingDetails']);
//     Route::post('add-vehicle', [VehicleInfoController::class, 'addVehicle']); // Un-used
//     Route::post('update-vehicle', [VehicleInfoController::class, 'updateVehicle']); // Un-used
//     Route::post('delete-vehicle', [VehicleInfoController::class, 'deleteVehicle']);
//     Route::post('set-price-status', [VehicleInfoController::class, 'setPriceStatus']);
//     // ADD VEHICLE IN STEPS
//     Route::post('add-update-vehicle', [VehicleInfoController::class, 'addUpdateVehicles']);

//     // CITY
//     Route::get('get-cities', [AdminApiController::class, 'getCities']);
//     Route::post('create-update-city', [AdminApiController::class, 'createOrUpdateCity']);
//     Route::post('delete-city', [AdminApiController::class, 'deleteCity']);

//     // BRANCH
//     Route::get('get-branch', [AdminApiController::class, 'getBranches']);
//     Route::post('create-update-branch', [AdminApiController::class, 'createOrUpdateBranch']);
//     Route::post('delete-branch', [AdminApiController::class, 'deleteBranch']);

//     // CUSTOMER
//     Route::get('get-customers', [CustomerDataController::class, 'getCustomers']);
//     Route::post('update-customer', [CustomerDataController::class, 'updateCustomer']);
//     Route::post('delete-customer', [CustomerDataController::class, 'deleteCustomer']);
//     Route::post('resend-mail', [CustomerDataController::class, 'resendMail']);
//     Route::post('block-unblock-user', [CustomerDataController::class, 'blockUnblockUser']);

//     // CUSTOMER DOCUMENT
//     Route::get('get-customer-documents', [CustomerDocumentDataController::class, 'getCustomerDocuments']);
//     Route::get('get-reject-reasons', [CustomerDocumentDataController::class, 'getRejectReasons']);
//     Route::post('approve-reject-block-document', [CustomerDocumentDataController::class, 'approveRejectBlockDocument']);
//     Route::post('verify-govtid-document', [CustomerDocumentDataController::class, 'verifyGovtIdDocument']);
//     Route::post('add-document', [CustomerDocumentDataController::class, 'addDocument']);
//     Route::get('get-govtypes', [CustomerDocumentDataController::class, 'getGovTypes']);
//     Route::get('get-dropdown-customers', [CustomerDocumentDataController::class, 'getDropdownCustomers']); //Un-used

//     // COUPONS
//     Route::get('get-coupons', [CustomerDataController::class, 'getCoupons']);
//     Route::get('set-coupon-status', [CustomerDataController::class, 'setCouponStatus']); 
//     Route::post('creare-or-update-coupon', [CustomerDataController::class, 'createOrUpdateCoupon']);
//     Route::post('delete-coupon', [CustomerDataController::class, 'deleteCoupon']);

//     // CARHOST
//     Route::get('get-carhosts', [CarHostManagementController::class, 'getCarHosts']);
//     Route::get('get-carhosts-changes', [CarHostManagementController::class, 'getCarHostChanges']);
//     Route::get('get-unpublish-vehicles', [CarHostManagementController::class, 'getUnpublishVehices']);
//     Route::post('create-or-update-carhost', [CarHostManagementController::class, 'createOrUpdateCarHost']);
//     Route::post('block-unblock-carhost', [CarHostManagementController::class, 'blockUnblockCarHost']);
//     Route::get('get-carhost-pickuplocation', [CarHostManagementController::class, 'getCarHostPickupLocation']);
//     Route::post('add-update-carhost-pickuplocation', [CarHostManagementController::class, 'addUpdateCarHostPickuplocation']);
//     Route::post('set-primary-carhost-pickuplocation', [CarHostManagementController::class, 'setPrimaryCarHostPickuplocation']);
//     Route::post('delete-pickuplocation', [CarHostManagementController::class, 'deleteHostPickuplocation']);
//     Route::post('delete-pickuplocation-image', [CarHostManagementController::class, 'deleteHostPickuplocationImage']);

//     // CARHOST BANKS
//     Route::get('get-host-bank-details', [CarHostManagementController::class, 'getHostBankDetails']);
//     Route::post('store-host-bank-details', [CarHostManagementController::class, 'storeHostBankDetails']);
//     Route::post('delete-host-bank', [CarHostManagementController::class, 'deleteHostBank']);

//     // GET CARHOST CHANGES
//     Route::get('get-host-vehicles', [CarHostManagementController::class, 'getHostVehicles']);
//     Route::get('get-host-updated-features', [CarHostManagementController::class, 'getHostUpdatedFeatures']);
//     Route::get('get-host-updated-images', [CarHostManagementController::class, 'getHostUpdatedImages']);
//     Route::get('get-host-updated-location', [CarHostManagementController::class, 'getHostUpdatedLocation']);
//     Route::get('get-host-updated-vehicledetails', [CarHostManagementController::class, 'getHostUpdatedVehicleDetails']);
//     Route::get('get-host-updated-vehicledocuments', [CarHostManagementController::class, 'getHostUpdatedVehicleDocuments']);
//     Route::get('get-host-updated-vehicleprices', [CarHostManagementController::class, 'getHostUpdatedVehiclePrices']);

//     //APPROVE CARHOST UPDATED CHANGES
//     Route::post('store-host-updated-features', [CarHostManagementController::class, 'storeHostUpdatedFeatures']);
//     Route::post('store-host-updated-images', [CarHostManagementController::class, 'storeHostUpdatedImages']);
//     Route::post('store-host-updated-location', [CarHostManagementController::class, 'storeHostUpdatedLocation']);
//     Route::post('store-host-updated-vehicle-details', [CarHostManagementController::class, 'storeHostUpdatedVehicleDetails']);
//     Route::post('store-host-updated-vehicle-documents', [CarHostManagementController::class, 'storeHostUpdatedVehicleDocuments']);
//     Route::post('store-host-updated-vehicle-prices', [CarHostManagementController::class, 'storeHostUpdatedVehiclePrices']);

//     // UPDATE HOST CHANGES
//     Route::post('set-host-features', [CarHostManagementController::class, 'setHostFeatures']);
//     Route::post('set-host-vehicle-images', [CarHostManagementController::class, 'setHostVehicleImages']);
//     Route::post('set-host-vehicle-details', [CarHostManagementController::class, 'setHostVehicleDetails']);
//     Route::get('get-host-hold-vehicle-dates', [CarHostManagementController::class, 'getHostHoldVehicleDates']);
//     Route::post('set-host-vehicle-hold-dates', [CarHostManagementController::class, 'setHostVehicleHoldDates']);
//     Route::post('delete-hold-vehicle-date', [CarHostManagementController::class, 'deleteHoldVehicleDate']);
//     Route::get('get-vehicle-info', [CarHostManagementController::class, 'getVehicleInfo']);
//     Route::get('get-host-vehicle-pricing-details', [CarHostManagementController::class, 'getHostVehiclePricingDetails']);
//     Route::post('set-host-vehicle-pricing-details', [CarHostManagementController::class, 'setHostVehiclePricingDetails']);
//     Route::post('delete-host-vehicle-image', [CarHostManagementController::class, 'deleteHostVehicleImage']);

//     // BOOKING HISTORY
//     Route::get('get-booking-dropdown-data', [AdminBookingController::class, 'getBookingDropdownData']);
//     Route::get('get-bookings', [AdminBookingController::class, 'getBookings']);
//     Route::get('export-bookings', [AdminBookingController::class, 'exportBookings']);
//     Route::post('get-or-update-booking-info', [AdminBookingController::class, 'getOrUpdateBookingInfo']);
//     Route::get('booking-info-update-flag', [AdminBookingController::class, 'getBookingInfoUpdateFlag']);
//     Route::get('get-add-booking-calculation', [AdminBookingController::class, 'getAddBookingCalculation']);
//     Route::post('add-booking', [AdminBookingController::class, 'addBooking']);
//     Route::get('search-booking', [AdminBookingController::class, 'searchBooking']);
//     Route::get('get-penalty-details', [AdminBookingController::class, 'getPenaltyDetails']);
    
//     // BOOKING PREVIEW
//     Route::get('get-booking-preview-data', [AdminBookingController::class, 'getBookingPreviewData']);
//     Route::get('booking-preview-action-list', [AdminBookingController::class, 'bookingPreviewActionList']);
//     Route::post('booking-preview-actions', [AdminBookingController::class, 'bookingPreviewActions']);

//     // BOOKING TRANSACTIONS
//     Route::get('get-booking-transaction-Details', [AdminBookingDetailsController::class, 'getBookingTransactionsDetails']);
//     Route::get('get-booking-transactions', [AdminBookingDetailsController::class, 'getBookingTransactions']);
//     Route::get('export-transactions', [AdminBookingDetailsController::class, 'exportTransactions']);

//     // REMAINING BOOKING PENALTIES
//     Route::get('remaining-booking-penalties', [AdminBookingDetailsController::class, 'remainingBookingPenalties']);
//     Route::get('get-completion-penalties', [AdminBookingDetailsController::class, 'getCompletionPenalties']);
//     Route::post('store-completion-penalties', [AdminBookingDetailsController::class, 'storeCompletionPenalties']);    

//     // CUSTOMER CANCELED REFUND
//     Route::get('get-customer-canceled-refund', [AdminBookingDetailsController::class, 'getCustomerCanceledRefund']);
//     Route::post('canceled-refund-process', [AdminBookingDetailsController::class, 'canceledRefundProcess']);

//     // BOOKING CALCULATION LIST
//     Route::get('get-booking-calculation-list', [AdminBookingDetailsController::class, 'getBookingCalculationList']);
//     Route::get('export-booking-calculation-list', [AdminBookingDetailsController::class, 'exportBookingCalculationList']); // Un-used

//     // TRIP AMOUNT CALCULATION
//     Route::get('get-trip-amount-calculation-list', [AdminBookingDetailsController::class, 'getTripAmountCalculationList']);
//     Route::post('create-update-tripamt-calc', [AdminBookingDetailsController::class, 'createOrUpdateTripAmtCalc']);

//     // REWARDS MANAGEMENT
//     Route::get('get-rewards', [AdminBookingDetailsController::class, 'getRewards']);
//     Route::post('pay-rewards', [AdminBookingDetailsController::class, 'payRewards']);

//     // EMAIL MANAGEMENT
//     Route::get('get-notification-customers', [AdminApiController::class, 'getNotificationCustomers']);
//     Route::post('send-email', [AdminApiController::class, 'sendEmail']);
//     Route::get('get-filtered-customers', [AdminApiController::class, 'getFilteredCustomers']);

//     // SEND MOBILE NOTIFICATIONS
//     Route::post('send-push-notifications', [AdminApiController::class, 'sendPushNotifications']);

//     // POLICY MANAGEMENT
//     Route::get('get-policies', [AdminApiController::class, 'getPolicies']);
//     Route::post('edit-or-reset-policy', [AdminApiController::class, 'editOrResetPolicy']);
    
//     // SETTING MANAGEMENT
//     Route::get('get-settings-details', [AdminApiController::class, 'getSettingsDetails']);
//     Route::post('update-settings-details', [AdminApiController::class, 'updateSettingsDetails']);

//     // ADMIN ACTIVITY LOG
//     Route::get('get-admin-activitylog', [AdminApiController::class, 'getAdminActivityLog']);

//     // LOGOUT
//     Route::post('logout', [AdminApiController::class, 'logout']);

//     // PERMISSTION MODULE
//     Route::get('get-permissions', [AdminApiController::class, 'getPermissions']);

// });