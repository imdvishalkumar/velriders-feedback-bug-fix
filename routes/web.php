<?php

use App\Http\Controllers\AdminControllers\AdminController;
use App\Http\Controllers\AdminControllers\CustomerController;
use App\Http\Controllers\AdminControllers\BranchController;
use App\Http\Controllers\AdminControllers\CustomerDocumentController;
use App\Http\Controllers\AdminControllers\ManufacturerController;
use App\Http\Controllers\AdminControllers\VehicleController;
use App\Http\Controllers\AdminControllers\VehicleModelController;
use App\Http\Controllers\AdminControllers\PaymentController;
use App\Http\Controllers\AdminControllers\RentalBookingController;
use App\Http\Controllers\AdminControllers\CouponController;
use App\Http\Controllers\AdminControllers\EmailController;
use App\Http\Controllers\AdminControllers\VehicleTypeController;
use App\Http\Controllers\AdminControllers\VehicleFeatureController;
use App\Http\Controllers\AdminControllers\FuelTypeController;
use App\Http\Controllers\AdminControllers\VehicleCategoryController;
use App\Http\Controllers\AdminControllers\VehicleTransmissionController;
use App\Http\Controllers\AdminControllers\CalculationListController;
use App\Http\Controllers\AdminControllers\PolicyController;
use App\Http\Controllers\AdminControllers\NotificationController;
use App\Http\Controllers\AdminControllers\SettingController;
use App\Http\Controllers\AdminControllers\CityController;
use App\Http\Controllers\AdminControllers\RewardController;
use App\Http\Controllers\AdminControllers\CarHostController;
use App\Http\Controllers\FrontControllers\CmsController;
use App\Http\Controllers\FrontControllers\LoginController;
use App\Http\Controllers\FrontControllers\SocialLoginController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontAppApis\V1\RentalBookingController as RentalBooking;
 
Route::get('test', [AdminController::class, 'testCode']);
Route::post('test1', [AdminController::class, 'testCode1']);


    
/*Route::get('testsubdomain', function () {
    return 'First sub domain';
})->domain('api.velriders.com');*/

/*Route::domain('api.velriders.com')->group(function () {
    Route::get('testsubdomain', function () {
        return 'Second subdomain landing page';
    });
});*/

//Front Route Start

Route::name("front.")->group(function ($router) {
    Route::middleware('generic.token')->group(function () {
        Route::get('front/icici-payment/{booking_id}/{payment_id}', [RentalBooking::class, 'iciciPayment']);
    });
    Route::post('front/icici-payment-callback', [RentalBooking::class, 'iciciPaymentCallback']);

    // CMS Pages
   /* Route::get('/', [CmsController::class, 'home'])->name('home');

    Route::get('about-us', [CmsController::class, 'aboutUs'])->name('about-us');
    Route::get('terms-condition', [CmsController::class, 'termsCondition'])->name('terms-condition');
    Route::get('contact-us', [CmsController::class, 'contactUs'])->name('contact-us');
    Route::get('privacy-policy', [CmsController::class, 'privacyPolicy'])->name('privacy-policy');
    Route::get('refund-policy', [CmsController::class, 'refundPolicy'])->name('refund-policy');
    Route::get('pricing-policy', [CmsController::class, 'pricingPolicy'])->name('pricing-policy');

    Route::get('/about_us', [CmsController::class, 'aboutUsNew'])->name('about_us');
    Route::get('/contact_us', [CmsController::class, 'contactUsNew'])->name('contact_us');
    Route::post('/post-contact_us', [CmsController::class, 'storeContactUs'])->name('store-contact_us');
    Route::post('subscribe-form', [CmsController::class, 'subscribeForm'])->name('subscribe-form');

    // Login
    Route::get('login', [LoginController::class, 'Login'])->name('login');
    Route::get('/verify-login', [LoginController::class, 'LoginPost'])->name('verify-login');
    Route::post('/verify-login-otp', [LoginController::class, 'verifyLoginOtp'])->name('verify-login-otp');
    Route::get('/confirm-details', [LoginController::class, 'getConfirmDetails'])->name('confirm-details');
    Route::post('/store-confirm-details', [LoginController::class, 'storeConfirmDetails'])->name('store-confirm-details');

    //Login with Google
    Route::get('auth/google', [SocialLoginController::class, 'signInwithGoogle']);
   // Route::get('callback/google', [SocialLoginController::class, 'callbackToGoogle']);*/

    // Delete Customer through Mobile Verificvation
    Route::get('delete-account', [CmsController::class, 'deleteAccountThroughWeb'])->name('delete-account');
    Route::post('send-otp', [CmsController::class, 'sendOtp'])->name('send-otp');
    Route::post('verify-send-otp', [CmsController::class, 'verifySendOtp'])->name('verify-send-otp');

    Route::get('verify/customer-email/{customer_id}/{email}/{app}', [LoginController::class, 'verifyCustomerEmail'])->name('verify-customer-email');
    Route::get('/verify-email-success/{status}', [LoginController::class, 'verifyEmailSuccess'])->name('verify-email-success'); 


    // Delete Car Host through Mobile Verificvation
    Route::get('delete-host-account', [CmsController::class, 'deleteHostAccountThroughWeb'])->name('delete-host-account');
    Route::post('send-host-otp', [CmsController::class, 'sendHostOtp'])->name('send-host-otp');
    Route::post('verify-host-send-otp', [CmsController::class, 'verifyHostSendOtp'])->name('verify-host-send-otp');
   
});

/*----------------------------------------------------------------------------------------------------------------------------------------------------*/

// Admin Routes Start
/*Route::middleware('admin-guard')->get('/admin-login', function () {
    return view('admin.admin-login');
})->name('admin.login');*/

/*Route::get('/admin-login', function () {
    return view('admin.admin-login');
})->name('admin.get.login');
Route::middleware('admin-guard')->post('/admin-login', [AdminController::class, 'login'])->name('admin.login');*/

Route::get('admin-login', [AdminController::class, 'getLogin'])->name('admin.get.login');
Route::post('admin-login', [AdminController::class, 'postLogin'])->name('admin.post-login');

Route::prefix('admin')->middleware('admin-guard')->group(function () {

    Route::get('/dashboard', [AdminController::class, 'getAdminDashboard'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'getAdminList'])->name('admin.users');

    Route::get('/get-all-users', [AdminController::class, 'getAdmins'])->name('admin.get-all-users');
    Route::get('/get-user', [AdminController::class, 'getAdmin'])->name('admin.get-user');
    Route::put('/update-user', [AdminController::class, 'updateAdmin'])->name('admin.update-user');
    Route::post('/create-user', [AdminController::class, 'createAdmin'])->name('admin.add-user');
    Route::delete('/delete-user', [AdminController::class, 'deleteAdmin'])->name('admin.delete-user');

    Route::get('/customers', [CustomerController::class, 'index'])->name('admin.customers.index');
    Route::get('/customer-documents', [CustomerDocumentController::class, 'index'])->name('admin.customer_documents.index');
    Route::get('/customer-documents-ajax', [CustomerDocumentController::class, 'customerDocumentAjax'])->name('admin.customer_documents.ajax');
    Route::get('/add-document', [CustomerDocumentController::class, 'addDocument'])->name('admin.add-document');
    Route::post('/store-document/{type}', [CustomerDocumentController::class, 'storeUserDocument'])->name('admin.store-document');

    Route::post('/admin/customer-documents/{id}/approve', [CustomerDocumentController::class, 'approve'])->name('admin.customer_documents.approve');
    Route::post('/admin/customer-documents/{id}/reject', [CustomerDocumentController::class, 'reject'])->name('admin.customer_documents.reject');
    Route::post('/delete-customer', [CustomerController::class, 'deleteCustomer'])->name('delete.customer');
    Route::post('/block-customer', [CustomerController::class, 'blockCustomer'])->name('block.customer');  
    
    Route::get('/customer/edit/{id}', [CustomerController::class, 'edit']);
    Route::post('/customer/update', [CustomerController::class, 'update']); 

    Route::get('/customer/sendmail/{id}', [CustomerController::class, 'customerSendMail']);


    Route::get('/manufacturers', [ManufacturerController::class, 'index'])->name('admin.manufacturers');
    Route::post('/submit-manufacturer', [ManufacturerController::class, 'store'])->name('submit.manufacturer');
    Route::get('/get-all-manufacturer', [ManufacturerController::class, 'getAllManufacturers'])->name('admin.get-all-manufacturer');
    Route::get('/get-manufacturer', [ManufacturerController::class, 'getManufacturer'])->name('admin.get-manufacturer');
    Route::delete('/delete-vehicle-manufacturer', [ManufacturerController::class, 'deleteVehicleManufacturer'])->name('admin.delete-manufacturer');

    Route::get('/vehicle-types', [VehicleTypeController::class, 'getAllTypesList'])->name('admin.vehicle-types');
    Route::get('/get-all-vehicle-types', [VehicleTypeController::class, 'getAllTypes'])->name('admin.get-all-vehicle-types');
    Route::get('/get-vehicle-types', [VehicleTypeController::class, 'getVehicleTypes'])->name('admin.get-vehicle-types');
    Route::put('/update-vehicle-types', [VehicleTypeController::class, 'updateVehicleTypes'])->name('admin.update-types');
    Route::post('/create-vehicle-types', [VehicleTypeController::class, 'createVehicleTypes'])->name('admin.create-types');
    Route::delete('/delete-vehicle-types', [VehicleTypeController::class, 'deleteVehicleTypes'])->name('admin.delete-types');
    Route::post('/check-vehicle-types', [VehicleTypeController::class, 'checkVehicleTypes'])->name('admin.check.vehicle.types');

    //Vehicle Features
    Route::get('/vehicle-features', [VehicleFeatureController::class, 'getVehicleFeatureList'])->name('admin.vehicle-features');
    Route::post('/submit-feature', [VehicleFeatureController::class, 'store'])->name('submit.feature');
    Route::get('/get-all-feature', [VehicleFeatureController::class, 'getFeatures'])->name('admin.get-all-feature');
    Route::get('/get-vehicle-features', [VehicleFeatureController::class, 'getVehicleFeatures'])->name('admin.get-vehicle-features');
    Route::delete('/delete-vehicle-features', [VehicleFeatureController::class, 'deleteVehicleFeatures'])->name('admin.delete-features');

    //Vehicle Fuel Types
    Route::get('/fuel-types', [FuelTypeController::class, 'index'])->name('admin.fuel-types');
    Route::get('/get-all-fuel-types', [FuelTypeController::class, 'getAllFuelTypes'])->name('admin.get-all-fuel-types');
    Route::post('/submit-fuel-type', [FuelTypeController::class, 'store'])->name('submit.fuel-type');
    Route::get('/get-fuel-type', [FuelTypeController::class, 'getFuelType'])->name('admin.get-fuel-type');
    Route::delete('/delete-fuel-types', [FuelTypeController::class, 'deleteFuelTypes'])->name('admin.delete-fuel-types');

    //Vehicle Categories
    Route::get('/vehicle-categories', [VehicleCategoryController::class, 'index'])->name('admin.vehicle-categories');
    Route::get('/get-all-vehicle-categories', [VehicleCategoryController::class, 'getAllVehicleCategories'])->name('admin.get-all-vehicle-categories');
    Route::post('/submit-vehicle-category', [VehicleCategoryController::class, 'store'])->name('submit.vehicle-category');
    Route::get('/get-vehicle-category', [VehicleCategoryController::class, 'getVehicleCategory'])->name('admin.get-vehicle-category');
    Route::delete('/delete-vehicle-category', [VehicleCategoryController::class, 'deleteVehicleCategory'])->name('admin.delete-vehicle-category');
    
    //Vehicle Transmission
    Route::get('/vehicle-transmission', [VehicleTransmissionController::class, 'index'])->name('admin.vehicle-transmission');
    Route::get('/get-all-vehicle-transmissions', [VehicleTransmissionController::class, 'getAllVehicleTransmission'])->name('admin.get-all-vehicle-transmissions');
    Route::post('/submit-vehicle-transmission', [VehicleTransmissionController::class, 'store'])->name('submit.vehicle-transmission');
    Route::get('/get-vehicle-transmission', [VehicleTransmissionController::class, 'getVehicleTransmission'])->name('admin.get-vehicle-transmission');
    Route::delete('/delete-vehicle-transmission', [VehicleTransmissionController::class, 'deleteVehicleTransmission'])->name('admin.delete-transmission');

    Route::get('/vehicle-models', [VehicleModelController::class, 'getAllModelsList'])->name('admin.vehicle-models');
    Route::get('/get-all-models', [VehicleModelController::class, 'getAllModels'])->name('admin.get-all-vehicle-models');
    Route::get('/vehicle-model/edit/{id}', [VehicleModelController::class, 'editModel'])->name('admin.vehicle-model-edit');
    Route::post('/vehicle-model/update/{id}', [VehicleModelController::class, 'updateModel'])->name('admin.vehicle-model-update');
    Route::delete('/delete-vehicle-models', [VehicleModelController::class, 'deleteVehicleModels'])->name('admin.delete-vehicle-models');

    Route::get('/cities', [CityController::class, 'getAllCityList'])->name('admin.cities');
    Route::get('/get-all-cities', [CityController::class, 'getAllCities'])->name('admin.get-all-cities');
    Route::post('/create-city', [CityController::class, 'createCity'])->name('admin.add-city');
    Route::get('/get-city', [CityController::class, 'getCity'])->name('admin.get-city');
    Route::put('/update-city', [CityController::class, 'updateCity'])->name('admin.update-city');
    Route::delete('/delete-city', [CityController::class, 'deleteCity'])->name('admin.delete-city');

    Route::get('/branches', [BranchController::class, 'getAllBranchList'])->name('admin.branches');
    Route::get('/get-all-branches', [BranchController::class, 'getAllBranchs'])->name('admin.get-all-branches');
    Route::get('/get-branch', [BranchController::class, 'getBranch'])->name('admin.get-branch');
    Route::put('/update-branch', [BranchController::class, 'updateBranch'])->name('admin.update-branch');
    Route::post('/create-branch', [BranchController::class, 'createBranch'])->name('admin.add-branch');
    Route::delete('/delete-branch', [BranchController::class, 'deleteBranch'])->name('admin.delete-branch');

    Route::get('/customers-documents', function () {
        return view('admin.customers-documents');
    })->name('admin.customers-documents');
    Route::get('/get-all-documents', [CustomerDocumentController::class, 'fetchDocuments'])->name('admin.get-all-documents');
    Route::post('/toggle-document-status', [CustomerDocumentController::class, 'toggleDcoumentStatus'])->name('admin.toggle-document-status');
    Route::post('/block-customer-document', [CustomerDocumentController::class, 'blockCustomerDocument'])->name('admin.block-customer-document'); 

    Route::get('/vehicles', [VehicleController::class, 'getAllVehicleList'])->name('admin.vehicles');
    Route::get('/vehicle/create', [VehicleController::class, 'getInsertForm'])->name('admin.vehicle.create');
    Route::get('/vehicleModel/create', [VehicleController::class, 'insertVehicleModel'])->name('admin.vehicleModel.create');
    Route::get('/get-all-vehicles', [VehicleController::class, 'getAllVehicles'])->name('admin.get-all-vehicles');
    Route::get('/vehicle/edit/{vehicle_id}', [VehicleController::class, 'getUpdateForm'])->name('admin.vehicle.edit');
    Route::post('/vehicle/update', [VehicleController::class, 'updateVehicle'])->name('admin.vehicle-update');
    Route::post('/vehicle/insert', [VehicleController::class, 'insertVehicle'])->name('admin.vehicle-insert');
    Route::post('/vehicleModel/insert', [VehicleController::class, 'vehicleModel'])->name('admin.vehicleModel-insert');
    Route::delete('/delete-vehicle', [VehicleController::class, 'deleteVehicle'])->name('admin.vehicle-delete');
    Route::post('/delete-image', [VehicleController::class, 'deleteImage'])->name('delete.image');
    Route::post('/delete.document', [VehicleController::class, 'deleteDocument'])->name('delete.document');
    Route::get('/get-category-manufacturer', [VehicleController::class, 'getCategoryManufacturer'])->name('admin.get-category-manufacturer');
    Route::get('/get-models', [VehicleController::class, 'getModels'])->name('admin.get-models');
    Route::post('/delete-cutout-img', [VehicleController::class, 'deleteCutoutImg'])->name('delete.cutout.img');
    Route::post('/validate-rc', [VehicleController::class, 'validateRcNumber'])->name('admin.validate-rc');
    Route::post('/validate-end-date', [VehicleController::class, 'validateEndDate'])->name('admin.validate-end-date');
    Route::get('/get-category', [VehicleController::class, 'getCategory'])->name('admin.get-category');
    Route::get('/get-minmax-rentalprice', [VehicleController::class, 'getMinMaxRentalPrice'])->name('admin.get-minmax-rentalprice');
    Route::get('/check-updatedprice', [VehicleController::class, 'checkUpdatedPrice'])->name('admin.check-updatedprice');
    Route::post('/publish-vehicle', [VehicleController::class, 'publishVehicle'])->name('publish.vehicle');  

    Route::get('/bookings', [RentalBookingController::class, 'getBookingList'])->name('admin.bookings');
    Route::post('/bookings-ajaxdata', [RentalBookingController::class, 'bookingsAjax'])->name('admin.bookings.ajax');
    Route::get('/booking-transactions', [RentalBookingController::class, 'getBookingTransaction'])->name('admin.booking-transactions');
    Route::post('/booking-transactions-ajaxdata', [RentalBookingController::class, 'bookingTransactionAjax'])->name('admin.booking-transactions.ajax');

    Route::get('export-booking-transaction/{type}', [RentalBookingController::class, 'exportBookingTransaction'])->name('admin.export.booking.transaction');

    Route::get('/remaining-booking-penalties', [RentalBookingController::class, 'getRemainingBookingPenalties'])->name('admin.remaining-booking-penalties');
    Route::post('/get-completion-penalties', [RentalBookingController::class, 'getCompletionPenalties'])->name('admin.get-completion-penalties');
    Route::post('/store-completion-penalties', [RentalBookingController::class, 'storeCompletionPenalties'])->name('admin.store-completion-penalties');


    Route::get('/get-customer-bookings/{customer_id}', [RentalBookingController::class, 'getCustomerBookings'])->name('admin.customer.bookings');
    Route::get('/get-vehicle-bookings/{vehicle_id}', [RentalBookingController::class, 'getVehicleBookings'])->name('admin.vehicle.bookings');

    Route::get('/get-all-booking/{from?}', [RentalBookingController::class, 'index'])->name('admin.get-all-booking');
    Route::post('/reset-booking', [RentalBookingController::class, 'resetBooking'])->name('admin.reset.booking');
    Route::get('/booking-priview/{booking_id}', [RentalBookingController::class, 'preview'])->name('admin.booking-priview');
    Route::post('/update-vehicle', [RentalBookingController::class, 'updateVehicle'])->name('admin.update-vehicle');
    Route::post('/get-payment-history', [RentalBookingController::class, 'getPaymentHistory'])->name('admin.get.payment.history');
    Route::post('/booking-update-start-Otp/{booking_id}', [RentalBookingController::class, 'updateStartOtp'])->name('admin.booking-updateOtp');
    Route::post('/booking-update-end-otp/{booking_id}', [RentalBookingController::class, 'updateEndOtp'])->name('admin.booking-end-otp');
    Route::post('/get-completion-price-summary', [RentalBookingController::class, 'getCompletionPriceSummary'])->name('admin.get-completion-price-summary');

    Route::post('/undo-cancel', [RentalBookingController::class, 'undoCancelled'])->name('admin.undo-cancel');
    Route::post('/get-cancel-details', [RentalBookingController::class, 'getCancelDetails'])->name('admin.get-cancel-details');
    Route::post('/cancel-booking', [RentalBookingController::class, 'cancelBooking'])->name('admin.cancel-booking');

    Route::get('/rental-booking/invoice/{customer_id}/{booking_id}', [RentalBookingController::class, 'invoiceData'])->name('admin.rental-booking.invoice');
    Route::get('/rental-booking/summary/{customer_id}/{booking_id}', [RentalBookingController::class, 'summaryData'])->name('admin.rental-booking.summary');
    Route::post('/get-penalty', [RentalBookingController::class, 'getPenalty'])->name('admin.get.penalty');
    Route::post('/store-penalty', [RentalBookingController::class, 'storePenalty'])->name('admin.store.penalty');
    Route::get('/customer-refund', [RentalBookingController::class, 'customerRefundList'])->name('admin.customer.refund.list');
    Route::post('/refund-process', [RentalBookingController::class, 'customerRefundProcess'])->name('admin.customer.refund.process');
    Route::get('/add-booking', [RentalBookingController::class, 'addBooking'])->name('admin.add-booking');

    Route::post('/get-price-summary', [RentalBookingController::class, 'getPriceSummary'])->name('admin.get-price-summary');
    Route::post('/get-extend-price-summary', [RentalBookingController::class, 'getExtendPriceSummary'])->name('admin.get-extend-price-summary');
    Route::post('/extend-booking', [RentalBookingController::class, 'getExtendBooking'])->name('admin.extend-booking');

    Route::post('/store-start-journey-details', [RentalBookingController::class, 'storeStartJourneyDetails'])->name('admin.store.start-journey-details');
    Route::post('/store-end-journey-details', [RentalBookingController::class, 'storeEndJourneyDetails'])->name('admin.store.end-journey-details');
    Route::post('/get-penalty-details', [RentalBookingController::class, 'getPenaltyDetails'])->name('admin.get-penalty-details');
    

    Route::post('/check-vehicle', [RentalBookingController::class, 'checkVehicle'])->name('admin.check-vehicle');
    Route::post('/check-customer', [RentalBookingController::class, 'checkCustomer'])->name('admin.check-customer');
    Route::post('/insert-booking', [RentalBookingController::class, 'insertBooking'])->name('admin.booking-insert');
    Route::get('/get-pending-booking', [RentalBookingController::class, 'getPendingBooking'])->name('admin.get-pending-booking');
    
    Route::get('/customer-canceled-refund', [RentalBookingController::class, 'customerCanceledRefund'])->name('admin.customer.canceled.refund');
    Route::post('/canceled-refund-process', [RentalBookingController::class, 'customerCanceledRefundProcess'])->name('admin.cenceled.refund.process');

    Route::post('km-update', [RentalBookingController::class, 'KmUpdate'])->name('admin.km.update');
    Route::get('export-bookings/{type}', [RentalBookingController::class, 'exportBookings'])->name('admin.export.bookings');

    /*Route::get('/payment-history', function () {
        return view('admin.payments');
    })->name('admin.payments');*/
    Route::get('/get-payments-insert-form', [PaymentController::class, 'paymentsForm'])->name('admin.get-payments-insert-form');
    Route::post('/get-payments-store-form', [PaymentController::class, 'storePayments'])->name('admin.get-payments-store-form');
    Route::get('/get-all-payment', [PaymentController::class, 'getAllPayment'])->name('admin.get-all-payment');

    Route::get('/coupons', [CouponController::class, 'getAllCouponList'])->name('admin.coupon.coupons');
    Route::get('/get-all-coupons', [CouponController::class, 'getAllCoupons'])->name('admin.get-all-coupons');
    Route::get('/coupon/create', [CouponController::class, 'createCoupon'])->name('admin.coupon.create');
    Route::post('/coupon/store', [CouponController::class, 'store'])->name('admin.store-coupon');
    Route::get('/coupon/edit/{id}', [CouponController::class, 'editCoupon'])->name('admin.coupon.edit');
    Route::post('/coupon/update/{id}', [CouponController::class, 'updateCoupon'])->name('admin.update-coupon');
    Route::post('/coupon/delete', [CouponController::class, 'destroyCoupon'])->name('admin.delete-coupon');
    Route::post('/check-coupon-code', [CouponController::class, 'checkCouponCode'])->name('admin.check.coupon.code');
Route::post('/validate-todate', [CouponController::class, 'validateToDate'])->name('admin.validate.todate');

    Route::get('/carhost-mgt', [CarHostController::class, 'getCarHostList'])->name('admin.carhost-mgt');
    Route::get('/get-all-carhost', [CarHostController::class, 'getAllCarHost'])->name('admin.get-all-carhost');
    Route::get('/carhost-create', [CarHostController::class, 'getCarHostCreate'])->name('admin.carhost.create');
    Route::post('/carhost/store', [CarHostController::class, 'storeCarHost'])->name('admin.store-carhost');
    Route::get('/carhost-edit/{host_id}', [CarHostController::class, 'getCarHostEdit'])->name('admin.carhost.edit');
    Route::post('/carhost-update/{host_id}', [CarHostController::class, 'getCarHostUpdate'])->name('admin.carhost.update');

    Route::post('/toggle/coupon', [CouponController::class, 'toggleCoupon'])->name('admin.toggle-coupon');
    Route::post('/toggle/show-coupon', [CouponController::class, 'toggleShowCoupon'])->name('admin.toggle-show-coupon');

    Route::get('/email-form', [EmailController::class, 'showForm'])->name('admin.email.emails');
    Route::post('/send-email', [EmailController::class, 'sendEmail'])->name('send-email.send');
    Route::get('/filter-data', [EmailController::class, 'filterData'])->name('admin.email.filter-data');

    Route::get('/reward-list', [RewardController::class, 'rewardList'])->name('admin.reward.list');
    Route::get('/get-rewards', [RewardController::class, 'getAllRewards'])->name('admin.get-rewards');
    Route::post('/store-paystatus', [RewardController::class, 'storePayStatus'])->name('admin.store-paystatus');
    Route::post('/get-bank-details', [RewardController::class, 'getBankDetails'])->name('admin.get-bank-details');

    // Send Push Notification
    Route::get('/push-notification', [NotificationController::class, 'showForm'])->name('admin.push.notification');
    Route::post('/send-push-notification', [NotificationController::class, 'sendPushNotifications'])->name('admin.send-push-notification');

    Route::get('/booking-calculation', [CalculationListController::class, 'getBookingCalculationList'])->name('admin.booking.calculation');
    Route::get('/get-bookings', [CalculationListController::class, 'getBookings'])->name('admin.get-bookings');

    /*Route::get('/trip-calculation', function () {
        return view('admin.tripamount-calculation-rules');
    })->name('admin.trip.calculation');*/

    Route::get('/trip-calculation', [CalculationListController::class, 'getTripCalculationList'])->name('admin.trip.calculation');
    Route::get('/get-trip-calculations', [CalculationListController::class, 'getTripCalculations'])->name('admin.get-trip-calculations');
    Route::post('/create-trip-calculation', [CalculationListController::class, 'createTripCalculation'])->name('admin.create-trip-calculation');
    Route::put('/update-trip-calculation', [CalculationListController::class, 'updateTripCalculation'])->name('admin.update-trip-calculation');

    Route::get('/get-policies', [PolicyController::class, 'getPolicies'])->name('admin.policies');
    Route::get('/get-all-policies', [PolicyController::class, 'getAllPolicies'])->name('admin.get-all-policies');
    Route::get('/policy/edit/{id}', [PolicyController::class, 'editPolicy'])->name('admin.policy.edit');
    Route::post('/policy/update/{id}', [PolicyController::class, 'updatePolicy'])->name('admin.update-policy');
    Route::get('/policy/reset/{id}', [PolicyController::class, 'resetPolicy'])->name('admin.policy.reset');

    Route::get('/settings', [SettingController::class, 'getSettings'])->name('admin.settings');
    Route::post('/submit-app-details', [SettingController::class, 'storeAppDetails'])->name('submit.app-details');
    Route::post('/submit-payment-details', [SettingController::class, 'storePaymentDetails'])->name('submit.payment-details');
    Route::post('/submit-referearn-details', [SettingController::class, 'storeReferEarnDetails'])->name('submit.referearn-details');
    Route::get('/get-app-details', [SettingController::class, 'getAppDetails'])->name('admin.get-app-details');
    Route::get('/get-app-detail', [SettingController::class, 'getAppDetail'])->name('admin.get-app-detail');
    Route::get('/set-showall-flag', [SettingController::class, 'setShowAllFlag'])->name('admin.set-showall-flag');
    Route::get('/set-booking-gap', [SettingController::class, 'setBookingGap'])->name('admin.set-booking-gap');
    Route::get('/set-vehicle-offer-price', [SettingController::class, 'setVehicleOfferPrice'])->name('admin.set-vehicle-offer-price');
    Route::get('/get-activity-log', [SettingController::class, 'getActivityLog'])->name('admin.activity-log');
    Route::post('/get-log-details', [SettingController::class, 'getLogDetails'])->name('admin.get-log-details');
    Route::post('/store-offer-dates', [SettingController::class, 'storeOfferDates'])->name('admin.store.offers-dates');

    Route::get('/logout', function () {
        auth()->guard('admin_web')->logout();
        return redirect()->route('admin.get.login');
    })->name('admin.logout');

    Route::get('/', function () {
        return redirect()->route('admin.users');
    });

});

Route::get('/{any}', function () {
    //return file_get_contents(public_path('vue/index.html'));
    return file_get_contents(public_path('index.html'));
})->where('any', '^(?!front|admin|api).*');