<?php

use Illuminate\Support\Facades\Route;
// ADMIN PANEL CONTROLLERS
use App\Http\Controllers\AdminApis\V1\{LoginController, VehicleDetailsController, CarHostManagementController, AdminApiController, AdminBookingController, AdminBookingDetailsController, CustomerDataController, VehicleInfoController, CustomerDocumentDataController, AdminApiAccountController};
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

// Below Routes for VELRIDERS ADMIN
Route::post('admin-login', [LoginController::class, 'adminLogin']);
Route::post('forgot-password', [LoginController::class, 'forgotPassword']);
Route::post('forgot-password-verify-otp', [LoginController::class, 'forgotPasswordVerifyOtp']);
Route::post('reset-password', [LoginController::class, 'resetPassword']);
Route::get('booking-invoice/{booking_id}', [LoginController::class, 'bookingInvoiceData']);
Route::get('vehicle-change-invoice/{history_id}', [LoginController::class, 'vehicleChangeInvoiceData']);
Route::get('booking-summary/{booking_id}/{customer_id}', [LoginController::class, 'bookingSummaryData']);
Route::get('customer-aggrement/{booking_id}', [LoginController::class, 'customerAggrement']);

Route::middleware('auth:sanctum')->group(function () {

    // DASHBOARD
    Route::get('get-dashboard-details', [AdminApiController::class, 'getDashboardDetails']);

    // ADMIN
    Route::get('get-all-admins', [AdminApiController::class, 'getAdmins']);
    Route::get('get-all-roles', [AdminApiController::class, 'getAllRoles']);
    Route::post('create-admin', [AdminApiController::class, 'createAdmin']);
    Route::post('update-admin', [AdminApiController::class, 'updateAdmin']);
    Route::post('delete-admin', [AdminApiController::class, 'deleteAdmin']);

    // VEHICLE TYPE
    Route::get('get-all-vehicle-types', [VehicleDetailsController::class, 'getAllVehicleTypes']);
    Route::post('create-update-vehicle-types', [VehicleDetailsController::class, 'createOrUpdateVehicleTypes']);
    Route::post('delete-vehicle-types', [VehicleDetailsController::class, 'deleteVehicleTypes']);

    // VEHICLE CATEGORY
    Route::get('get-vehicle-categories', [VehicleDetailsController::class, 'getVehicleCategories']);
    Route::post('create-update-vehicle-category', [VehicleDetailsController::class, 'createOrUpdateVehicleCategories']);
    Route::post('delete-vehicle-category', [VehicleDetailsController::class, 'deleteVehicleCategory']);

    // VEHICLE FUEL TYPES
    Route::get('get-fuel-types', [VehicleDetailsController::class, 'getAllFuelTypes']);
    Route::post('create-update-fuel-type', [VehicleDetailsController::class, 'createOrUpdateFuelType']);
    Route::post('delete-fuel-type', [VehicleDetailsController::class, 'deleteFuelType']);

    // VEHICLE TRANSMISSION
    Route::get('get-vehicle-transmissions', [VehicleDetailsController::class, 'getVehicleTransmissions']);
    Route::post('create-update-vehicle-transmission', [VehicleDetailsController::class, 'createOrUpdateVehicleTrasmission']);
    Route::post('delete-vehicle-transmission', [VehicleDetailsController::class, 'deleteVehicleTransmission']);

    // VEHICLE FEATURES
    Route::get('get-vehicle-features', [VehicleDetailsController::class, 'getVehicleFeatureList']);
    Route::post('create-update-vehicle-feature', [VehicleDetailsController::class, 'createOrUpdateVehicleFeature']);
    Route::post('delete-vehicle-features', [VehicleDetailsController::class, 'deleteVehicleFeatures']);

    // VEHICLE MANUFACTURERS
    Route::get('get-vehicle-manufacturers', [VehicleDetailsController::class, 'getManufacturerList']);
    Route::post('create-update-vehicle-manufacturer', [VehicleDetailsController::class, 'createOrUpdateVehicleManufacturer']);
    Route::post('delete-vehicle-manufacturer', [VehicleDetailsController::class, 'deleteVehicleManufacturer']);

    // VEHICLE MODELS
    Route::get('get-vehicle-models', [VehicleDetailsController::class, 'getVehicleModels']);
    Route::post('create-update-vehicle-model', [VehicleDetailsController::class, 'createOrUpdateVehicleModel']);
    Route::get('get-price-summary', [VehicleDetailsController::class, 'getPriceSummary']);
    Route::post('delete-vehicle-model', [VehicleDetailsController::class, 'deleteVehicleModel']);

    // VEHICLE  
    Route::get('get-vehicles', [VehicleInfoController::class, 'getVehicles']);
    Route::post('publish-vehicle', [VehicleInfoController::class, 'publishVehicles']);
    Route::get('get-vehicle-page-data', [VehicleInfoController::class, 'getVehiclePageData']);
    Route::get('get-depending-details', [VehicleInfoController::class, 'getDependingDetails']);
    Route::post('add-vehicle', [VehicleInfoController::class, 'addVehicle']); // Un-used
    Route::post('update-vehicle', [VehicleInfoController::class, 'updateVehicle']); // Un-used
    Route::post('delete-vehicle', [VehicleInfoController::class, 'deleteVehicle']);
    Route::post('set-price-status', [VehicleInfoController::class, 'setPriceStatus']);
    // ADD VEHICLE IN STEPS
    Route::post('add-update-vehicle', [VehicleInfoController::class, 'addUpdateVehicles']);

    // CITY
    Route::get('get-cities', [AdminApiController::class, 'getCities']);
    Route::post('create-update-city', [AdminApiController::class, 'createOrUpdateCity']);
    Route::post('delete-city', [AdminApiController::class, 'deleteCity']);

    // BRANCH
    Route::get('get-branch', [AdminApiController::class, 'getBranches']);
    Route::post('create-update-branch', [AdminApiController::class, 'createOrUpdateBranch']);
    Route::post('delete-branch', [AdminApiController::class, 'deleteBranch']);

    // CUSTOMER
    Route::get('get-customers', [CustomerDataController::class, 'getCustomers']);
    Route::post('update-customer', [CustomerDataController::class, 'updateCustomer']);
    Route::post('delete-customer', [CustomerDataController::class, 'deleteCustomer']);
    Route::post('resend-mail', [CustomerDataController::class, 'resendMail']);
    Route::post('block-unblock-user', [CustomerDataController::class, 'blockUnblockUser']);

    // CUSTOMER DOCUMENT
    Route::get('get-customer-documents', [CustomerDocumentDataController::class, 'getCustomerDocuments']);
    Route::get('get-reject-reasons', [CustomerDocumentDataController::class, 'getRejectReasons']);
    Route::post('approve-reject-block-document', [CustomerDocumentDataController::class, 'approveRejectBlockDocument']);
    Route::post('verify-govtid-document', [CustomerDocumentDataController::class, 'verifyGovtIdDocument']);
    Route::post('add-document', [CustomerDocumentDataController::class, 'addDocument']);
    Route::get('get-govtypes', [CustomerDocumentDataController::class, 'getGovTypes']);
    Route::get('get-dropdown-customers', [CustomerDocumentDataController::class, 'getDropdownCustomers']); //Un-used

    // COUPONS
    Route::get('get-coupons', [CustomerDataController::class, 'getCoupons']);
    Route::get('set-coupon-status', [CustomerDataController::class, 'setCouponStatus']);
    Route::post('creare-or-update-coupon', [CustomerDataController::class, 'createOrUpdateCoupon']);
    Route::post('delete-coupon', [CustomerDataController::class, 'deleteCoupon']);

    // CARHOST
    Route::get('get-carhosts', [CarHostManagementController::class, 'getCarHosts']);
    Route::get('get-carhosts-changes', [CarHostManagementController::class, 'getCarHostChanges']);
    Route::get('get-unpublish-vehicles', [CarHostManagementController::class, 'getUnpublishVehices']);
    Route::post('create-or-update-carhost', [CarHostManagementController::class, 'createOrUpdateCarHost']);
    Route::post('block-unblock-carhost', [CarHostManagementController::class, 'blockUnblockCarHost']);
    Route::get('get-carhost-pickuplocation', [CarHostManagementController::class, 'getCarHostPickupLocation']);
    Route::post('add-update-carhost-pickuplocation', [CarHostManagementController::class, 'addUpdateCarHostPickuplocation']);
    Route::post('set-primary-carhost-pickuplocation', [CarHostManagementController::class, 'setPrimaryCarHostPickuplocation']);
    Route::post('delete-pickuplocation', [CarHostManagementController::class, 'deleteHostPickuplocation']);
    Route::post('delete-pickuplocation-image', [CarHostManagementController::class, 'deleteHostPickuplocationImage']);

    // CARHOST BANKS
    Route::get('get-host-bank-details', [CarHostManagementController::class, 'getHostBankDetails']);
    Route::post('store-host-bank-details', [CarHostManagementController::class, 'storeHostBankDetails']);
    Route::post('delete-host-bank', [CarHostManagementController::class, 'deleteHostBank']);

    // GET CARHOST CHANGES
    Route::get('get-host-vehicles', [CarHostManagementController::class, 'getHostVehicles']);
    Route::get('get-host-updated-features', [CarHostManagementController::class, 'getHostUpdatedFeatures']);
    Route::get('get-host-updated-images', [CarHostManagementController::class, 'getHostUpdatedImages']);
    Route::get('get-host-updated-location', [CarHostManagementController::class, 'getHostUpdatedLocation']);
    Route::get('get-host-updated-vehicledetails', [CarHostManagementController::class, 'getHostUpdatedVehicleDetails']);
    Route::get('get-host-updated-vehicledocuments', [CarHostManagementController::class, 'getHostUpdatedVehicleDocuments']);
    Route::get('get-host-updated-vehicleprices', [CarHostManagementController::class, 'getHostUpdatedVehiclePrices']);

    //APPROVE CARHOST UPDATED CHANGES
    Route::post('store-host-updated-features', [CarHostManagementController::class, 'storeHostUpdatedFeatures']);
    Route::post('store-host-updated-images', [CarHostManagementController::class, 'storeHostUpdatedImages']);
    Route::post('store-host-updated-location', [CarHostManagementController::class, 'storeHostUpdatedLocation']);
    Route::post('store-host-updated-vehicle-details', [CarHostManagementController::class, 'storeHostUpdatedVehicleDetails']);
    Route::post('store-host-updated-vehicle-documents', [CarHostManagementController::class, 'storeHostUpdatedVehicleDocuments']);
    Route::post('store-host-updated-vehicle-prices', [CarHostManagementController::class, 'storeHostUpdatedVehiclePrices']);

    // REJECT CARHOST UPDATED CHANGES
    Route::post('reject-host-updated-features', [CarHostManagementController::class, 'rejectHostUpdatedFeatures']);
    Route::post('reject-host-updated-images', [CarHostManagementController::class, 'rejectHostUpdatedImages']);
    Route::post('reject-host-updated-vehicle-details', [CarHostManagementController::class, 'rejectHostUpdatedVehicleDetails']);
    Route::post('reject-host-updated-vehicle-documents', [CarHostManagementController::class, 'rejectHostUpdatedVehicleDocuments']);
    Route::post('reject-host-updated-vehicle-prices', [CarHostManagementController::class, 'rejectHostUpdatedVehiclePrices']);

    // UPDATE HOST CHANGES
    Route::post('set-host-features', [CarHostManagementController::class, 'setHostFeatures']);
    Route::post('set-host-vehicle-images', [CarHostManagementController::class, 'setHostVehicleImages']);
    Route::post('set-host-vehicle-details', [CarHostManagementController::class, 'setHostVehicleDetails']);
    Route::get('get-host-hold-vehicle-dates', [CarHostManagementController::class, 'getHostHoldVehicleDates']);
    Route::post('set-host-vehicle-hold-dates', [CarHostManagementController::class, 'setHostVehicleHoldDates']);
    Route::post('delete-hold-vehicle-date', [CarHostManagementController::class, 'deleteHoldVehicleDate']);
    Route::get('get-vehicle-info', [CarHostManagementController::class, 'getVehicleInfo']);
    Route::get('get-host-vehicle-pricing-details', [CarHostManagementController::class, 'getHostVehiclePricingDetails']);
    Route::post('set-host-vehicle-pricing-details', [CarHostManagementController::class, 'setHostVehiclePricingDetails']);
    Route::post('delete-host-vehicle-image', [CarHostManagementController::class, 'deleteHostVehicleImage']);

    // BOOKING HISTORY
    Route::get('get-booking-dropdown-data', [AdminBookingController::class, 'getBookingDropdownData']);
    Route::get('get-bookings', [AdminBookingController::class, 'getBookings']);
    Route::get('export-bookings', [AdminBookingController::class, 'exportBookings']);
    Route::post('get-or-update-booking-info', [AdminBookingController::class, 'getOrUpdateBookingInfo']);
    Route::get('booking-info-update-flag', [AdminBookingController::class, 'getBookingInfoUpdateFlag']);
    Route::get('get-add-booking-calculation', [AdminBookingController::class, 'getAddBookingCalculation']);
    Route::post('add-booking', [AdminBookingController::class, 'addBooking']);
    Route::get('search-booking', [AdminBookingController::class, 'searchBooking']);
    Route::get('get-penalty-details', [AdminBookingController::class, 'getPenaltyDetails']);

    // BOOKING PREVIEW
    Route::get('get-booking-preview-data', [AdminBookingController::class, 'getBookingPreviewData']);
    Route::get('booking-preview-action-list', [AdminBookingController::class, 'bookingPreviewActionList']);
    Route::post('booking-preview-actions', [AdminBookingController::class, 'bookingPreviewActions']);

    // BOOKING TRANSACTIONS
    Route::get('get-booking-transaction-Details', [AdminBookingDetailsController::class, 'getBookingTransactionsDetails']);
    Route::get('get-booking-transactions', [AdminBookingDetailsController::class, 'getBookingTransactions']);
    Route::get('export-transactions', [AdminBookingDetailsController::class, 'exportTransactions']);

    // REMAINING BOOKING PENALTIES
    Route::get('remaining-booking-penalties', [AdminBookingDetailsController::class, 'remainingBookingPenalties']);
    Route::get('get-completion-penalties', [AdminBookingDetailsController::class, 'getCompletionPenalties']);
    Route::post('store-completion-penalties', [AdminBookingDetailsController::class, 'storeCompletionPenalties']);

    // CUSTOMER CANCELED REFUND
    Route::get('get-customer-canceled-refund', [AdminBookingDetailsController::class, 'getCustomerCanceledRefund']);
    Route::post('canceled-refund-process', [AdminBookingDetailsController::class, 'canceledRefundProcess']);

    // BOOKING CALCULATION LIST
    Route::get('get-booking-calculation-list', [AdminBookingDetailsController::class, 'getBookingCalculationList']);
    Route::get('export-booking-calculation-list', [AdminBookingDetailsController::class, 'exportBookingCalculationList']); // Un-used

    // TRIP AMOUNT CALCULATION
    Route::get('get-trip-amount-calculation-list', [AdminBookingDetailsController::class, 'getTripAmountCalculationList']);
    Route::post('create-update-tripamt-calc', [AdminBookingDetailsController::class, 'createOrUpdateTripAmtCalc']);

    // REWARDS MANAGEMENT
    Route::get('get-rewards', [AdminBookingDetailsController::class, 'getRewards']);
    Route::post('pay-rewards', [AdminBookingDetailsController::class, 'payRewards']);

    // Host Payment Report Details
    Route::get('host-payment-report-details', [AdminApiAccountController::class, 'AdminAccountDetails']);
    Route::get('get-export-sessions', [AdminApiAccountController::class, 'getExportSessions']);
    Route::get('get-export-session-records', [AdminApiAccountController::class, 'getExportSessionRecords']);


    // EMAIL MANAGEMENT
    Route::get('get-notification-customers', [AdminApiController::class, 'getNotificationCustomers']);
    Route::post('send-email', [AdminApiController::class, 'sendEmail']);
    Route::get('get-filtered-customers', [AdminApiController::class, 'getFilteredCustomers']);

    // SEND MOBILE NOTIFICATIONS
    Route::post('send-push-notifications', [AdminApiController::class, 'sendPushNotifications']);

    // POLICY MANAGEMENT
    Route::get('get-policies', [AdminApiController::class, 'getPolicies']);
    Route::post('edit-or-reset-policy', [AdminApiController::class, 'editOrResetPolicy']);

    // FAQs MANAGEMENT
    Route::get('get-faqs', [AdminApiController::class, 'getFaqs']);
    Route::post('create-or-update-faqs', [AdminApiController::class, 'createOrUpdateFaqs']);
    Route::post('delete-faq', [AdminApiController::class, 'deleteFaq']);

    // Image Sliders MANAGEMENT
    Route::get('get-sliders', [AdminApiController::class, 'getSliders']);
    Route::post('create-or-update-sliders', [AdminApiController::class, 'createOrUpdateSliders']);
    Route::post('delete-slider', [AdminApiController::class, 'deleteSlider']);
    Route::post('update-slider-title', [AdminApiController::class, 'updateSliderTitle']);

    // SETTING MANAGEMENT
    Route::get('get-settings-details', [AdminApiController::class, 'getSettingsDetails']);
    Route::post('update-settings-details', [AdminApiController::class, 'updateSettingsDetails']);

    // ADMIN ACTIVITY LOG
    Route::get('get-admin-activitylog', [AdminApiController::class, 'getAdminActivityLog']);
    Route::get('get-host-activitylog', [AdminApiController::class, 'getHostActivityLog']);

    // LOGOUT
    Route::post('logout', [AdminApiController::class, 'logout']);

    // PERMISSTION MODULE
    Route::get('get-permissions', [AdminApiController::class, 'getPermissions']);

});