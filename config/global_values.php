<?php

return [

	'api_url' => 'https://velriders.com/api/',
	//'api_url' => 'http://localhost/velriders/public/api/',
	/*'mail_from' => "support@velriders.com",
	'mail_to' => "support@velriders.com",*/
	'mail_from' => "info@velriders.com",
	'mail_to' => "info@velriders.com",

	'mobile_no_array' => ['8238224282','9327083718','9909927077','9909227077','9909727077','9099927077','8000624004','8200678342','7771963496','8320139424','7600309655','9727900542', '9157023249', '9999999999'],
	'vehicle_km_driven' => ['0-25k','25k-50k', '50-100k', '>100k'],
	'vehicle_parking_type' => [ 1 => 'Street',  2 => 'Apartment', 3 => 'House'],
	'os_type' => [1 => 'Android', 2 => 'IOS'],
	'superadmin_permissions' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34], 
	'manager_permissions' => [8, 9, 13, 15, 18, 31], 
	'accountant_permissions' => [18, 34], 
	'admin' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 12, 13, 14, 15, 18, 21, 22, 25, 29, 30, 31, 33, 34],
	'employee' => [15],
	'customer_executive' => [12, 13, 15, 30, 31],
	'sales_executive' => [2, 3, 4, 5, 6, 7, 8, 9, 15, 29],
	//'subadmin_permissions' => [13, 15], 
	'generic_token' => 'velrider_rWzUK5j',
	'environment' => 'live',
	'payment_modes' => ['cash','online','upi','razorpay'],
	'payment_gateway_type' => ['razorpay', 'cashfree', 'icici'],
	'coupon_uses' => [1 => 'Yes', 0 => 'No'],
	'time_duration' => [
		['id' => 'all', 'name' => 'All'],
		['id' => 'last_week', 'name' => 'Last Week'],
		['id' => 'last_month', 'name' => 'Last Month'],
		['id' => 'till_date', 'name' => 'Till Date']
	],

	'cashfree_verification_test_url' => 'https://sandbox.cashfree.com/',
	'cashfree_verification_live_url' => 'https://api.cashfree.com/',
	'govid_types' => [['id' => 'aadhar', 'type' => 'Aadhar Card'], ['id' => 'passport', 'type' => 'Passport'], ['id' => 'election', 'type' => 'Election Card']],
	'booking_statuses' => [
		['id' => 'all', 'status' => 'All'],
	    ['id' => 'completed', 'status' => 'Completed'],
	    ['id' => 'failed', 'status' => 'Failed'],
	    ['id' => 'canceled', 'status' => 'Canceled'],
	    ['id' => 'running', 'status' => 'Running'],
	    ['id' => 'confirmed', 'status' => 'Confirmed'],
	    ['id' => 'no show', 'status' => 'No Show'],
	    ['id' => 'pending', 'status' => 'Pending']
	],
	'booking_duration' => ['past', 'upcoming'],
	'doc_verification_status' => 'yes',
	'reward_types' => [/*1 => 'Fixed',*/ 2 => 'Percent'],
	'govt_types' => ['aadhar' => 'Aadhar Card', 'passport' => 'Passport', 'election' => 'Election Card'],
	'tax_percent' => [5, 12],
	'paid_status' => [1 => 'Paid', 0 => 'Not Paid'], 
	'order_types' => ['asc', 'desc'], 
	'otp_via' => ['sms', 'email'],
	'price_types' => ['min', 'max'],
	'setting_update_flag' => ['app_detail', 'vehicle_show', 'vehicle_booking_gap', 'vehicle_offer', 'payment_gateway', 'refer_earn', 'cust_doc_verif_limits', 'location_km_distance'],
	'booking_info_update_flag' => ['get_customer_bookings', 'get_vehicle_bookings', 'update_start_km', 'update_end_km', 'get_penalty', 'add_penalty', 'start_otp', 'end_otp', 'get_price_summary', 'get_booking_operation'],
	'preview_actions' => ['update_vehicle','start_journey','extend_price_summary','extend_journey', 'get_extend_booking_calculation', 'end_journey','cancel_journey_note','cancel_journey', 'undo_cancel'],
	'vehicle_steps' => ['vehicle_details', 'property_details', 'vehicle_features', 'vehicle_images', 'price_calculation', 'availability_dates'],
	'vehicle_document_types' => ['rc_doc', 'insurance_doc', 'puc_doc'],
	
	'icici_merchant_id' => 'T_03338',
	'icici_secret_key' => 'abc',
	'icici_test_secret_key' => 'abc',

	'icici_initiate_sale_url' => 'https://qa.phicommerce.com/pg/api/v2/initiateSale',
	'icici_test_initiate_sale_url' => 'https://qa.phicommerce.com/pg/api/v2/initiateSale',
	
	'icici_status_check_url' => 'https://qa.phicommerce.com/pg/api/command',
	'icici_test_status_check_url' => 'https://qa.phicommerce.com/pg/api/command',
];