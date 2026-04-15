<?php

namespace App\Http\Controllers\AdminApis;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\{AdminUser, Vehicle, City, Branch, CarHostPickupLocation, Customer, AdminRentalBooking, Payment, Policy, Setting, OfferDate, AppStatus, LoginToken, AdminActivityLog};
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Jobs\SendAdminEmailNotification;

class AdminApiController extends Controller
{
    protected $cashfreeClientId;
    protected $cashfreeClientSecret;
    protected $cashfreeAadharApiUrl;
    protected $cashfreeAadharVerifyApiUrl;
    protected $cashfreePassportApiUrl;
    protected $cashfreeElectionApiUrl;

    public function __construct()
    {
        $this->cashfreeClientId = get_env_variable('CASHFREE_CLIENTID');
        $this->cashfreeClientSecret = get_env_variable('CASHFREE_CLIENTSECRET');
        $this->cashfreeDlApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/driving-license';
        $this->cashfreeAadharApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/offline-aadhaar/otp';
        $this->cashfreeAadharVerifyApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/offline-aadhaar/verify';
        $this->cashfreePassportApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/passport';
        $this->cashfreeElectionApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/voter-id';

        // $this->cashfreeClientId = get_env_variable('CASHFREE_TEST_CLIENTID');
        // $this->cashfreeClientSecret = get_env_variable('CASHFREE_TEST_CLIENTSECRET');
        // $this->cashfreeDlApiUrl = config('global_values.cashfree_verification_test_url').'verification/driving-license';
        // $this->cashfreeAadharApiUrl = config('global_values.cashfree_verification_test_url').'verification/offline-aadhaar/otp';
        // $this->cashfreeAadharVerifyApiUrl = config('global_values.cashfree_verification_test_url').'verification/offline-aadhaar/verify';
        // $this->cashfreePassportApiUrl = config('global_values.cashfree_verification_test_url').'verification/passport';
        // $this->cashfreeElectionApiUrl = config('global_values.cashfree_verification_test_url').'verification/voter-id';
    }

    public function getDashboardDetails(Request $request)
    {
        $currentDate = Carbon::now()->toDateString();
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $todayCashEntry = Payment::where('payment_mode', 'cash')
            ->where('status', 'captured')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->sum('amount');

        $cancelBookingCount = AdminRentalBooking::where('status', 'canceled')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->count();

        $runningBookingCount = AdminRentalBooking::where('status', 'running')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->count();

        $returnDueBookingCount = AdminRentalBooking::where('status', 'running')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->whereDate('return_date', '<', $currentDate)
            ->count();

        // Get canceled bookings with customer data
        $canceledBookingDetails = AdminRentalBooking::with(['customer' => function ($query) {
                $query->select('customer_id','country_code','mobile_number','email','firstname','lastname','dob', 'profile_picture_url');
            }])->with('vehicle')
            ->select('booking_id', 'customer_id', 'vehicle_id','pickup_date', 'return_date', 'total_cost', 'rental_type', 'status')
            ->where('status', 'canceled')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->get();
        if (!empty($canceledBookingDetails) && is_iterable($canceledBookingDetails)) {
            collect($canceledBookingDetails)->each(function ($item) {
                $item->vehicle->makeHidden(['branch_id','rental_price','extra_km_rate','extra_hour_rate','availability_calendar','commission_percent','publish','chassis_no']);
            });
        }

        //$returnDueBookingDetails = ReturnDueBookingDetails

        // Get running bookings with customer data
        $runningBookingDetails = AdminRentalBooking::with(['customer' => function ($query) {
                $query->select('customer_id','country_code','mobile_number','email','firstname','lastname','dob', 'profile_picture_url');
            },'vehicle' => function ($query) {
                $query->select('vehicle_id','model_id','year','description','color','license_plate','availability');
            }
        ])->select('booking_id','customer_id','vehicle_id','pickup_date','return_date','total_cost','rental_type','status')->where('status', 'running')->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->get();

        // Get return bookings with customer data
        $returnBookingDetails = AdminRentalBooking::with(['customer' => function ($query) {
                $query->select('customer_id','country_code','mobile_number','email','firstname','lastname','dob', 'profile_picture_url');
            },'vehicle' => function ($query) {
                $query->select('vehicle_id','model_id','year','description','color','license_plate','availability');
            }
        ])->select('booking_id','customer_id','vehicle_id','pickup_date','return_date','total_cost','rental_type','status')->where('status', 'running')->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->whereDate('return_date', '<', $currentDate)->get();

        $paymentDetails = Payment::with(['booking.customer' => function ($query) {
            $query->select('customer_id','country_code','mobile_number','email','firstname','lastname','dob');
        }])
        ->select('payment_id', 'booking_id', 'amount', 'payment_date', 'status', 'payment_type')
        ->with('booking.vehicle')
        ->whereDate('created_at', '>=', $startDate)
        ->whereDate('created_at', '<=', $endDate)
        ->get();
        if (!empty($paymentDetails) && is_iterable($paymentDetails)) {
            collect($paymentDetails)->each(function ($item) {
                $item->booking->vehicle->makeHidden(['branch_id','rental_price','extra_km_rate','extra_hour_rate','availability_calendar','commission_percent','publish','chassis_no']);
            });
        }

        $detailArr = [
            'cash_entry_sum' => (float) $todayCashEntry,
            'canceled_booking_count' => $cancelBookingCount,
            'running_booking_count' => $runningBookingCount,
            'return_due_booking_count' => $returnDueBookingCount,
            'canceled_booking_details' => $canceledBookingDetails,
            'running_booking_details' => $runningBookingDetails,
            'return_due_booking_details' => $returnBookingDetails,
            'payment_details' => $paymentDetails,
        ];

        return $this->successResponse($detailArr, 'Details retrieved successfully');
    }

    public function getAllRoles(Request $request){
        $roles = getRoles();
        if(isset($roles) && is_countable($roles) && count($roles) > 0){
            return $this->successResponse($roles, 'Roles are get Successfully');
        }else{
            return $this->errorResponse('Roles are not Found');
        }
    }

    public function getAdmins(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:admin_users,admin_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $adminsQuery = AdminUser::select(
            'admin_users.admin_id as id',
            'admin_users.username',
            'roles.name as rolename',
            'roles.id as roleid',
            'admin_users.created_at',
            'admin_users.is_deleted',
            'admin_users.mobile_number',
        )/*->where('is_deleted', 0)*/->leftJoin('roles', 'roles.id', '=', 'admin_users.role')->whereNotIn('admin_id', [1]);
        
        if (!empty($request->id)) {
            $admin = $adminsQuery->where('admin_id', $request->id)->first();
            return $admin ? $this->successResponse($admin, 'Admin details fetched successfully') : $this->errorResponse('Admin user not found');
        }
        
        if(isset($search) && $search != ''){
            $adminsQuery = $adminsQuery->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(admin_users.username) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(roles.name) LIKE LOWER(?)', ["%$search%"]);
            });
        }

        if($orderColumn != '' && $orderType != ''){
            $adminsQuery = $adminsQuery->orderBy($orderColumn, $orderType);
        }
        if ($page !== null && $pageSize !== null) {
            $admins = $adminsQuery->paginate($pageSize, ['*'], 'page', $page);
            $decodedAdmins = json_decode(json_encode($admins->getCollection()->values()), FALSE);

            return $this->successResponse([
                'admins' => $decodedAdmins,
                'pagination' => [
                    'total' => $admins->total(),
                    'per_page' => $admins->perPage(),
                    'current_page' => $admins->currentPage(),
                    'last_page' => $admins->lastPage(),
                    'from' => ($admins->currentPage() - 1) * $admins->perPage() + 1,
                    'to' => min($admins->currentPage() * $admins->perPage(), $admins->total()),
                ]], 'Admins fetched successfully');
        }else{
            $admins = [
                'admins' => $adminsQuery->get(),
            ];
            if(isset($admins) && is_countable($admins) && count($admins) > 0){
                return $this->successResponse($admins, 'Admins fetched successfully');
            }else{
                return $this->errorResponse('Admin users not found');
            }
        }
    }

    public function createAdmin(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required|min:6',
            'role' => 'required|exists:roles,id',
            'mobile_number' => ['required','numeric','regex:/^\+?[1-9]\d{9,14}$/','digits_between:8,15', Rule::unique('admin_users', 'mobile_number')
                ->where(function ($query) {
                    $query->where('is_deleted', 0);
                })],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $admin = new AdminUser();
        $admin->username = $request->input('username');
        $admin->password = bcrypt($request->input('password'));
        $admin->role = $request->input('role');
        $admin->mobile_number = $request->mobile_number ?? null;
        $admin->save();

        if(isset($request->role) && $request->role != ''){
            if($request->role == 2){
                $permission_moduleids = config('global_values.manager_permissions');
                $admin->syncPermissions($permission_moduleids);     
            }elseif($request->role == 3){
                $permission_moduleids = config('global_values.accountant_permissions');
                $admin->syncPermissions($permission_moduleids);     
            }elseif($request->role == 4){
                $permission_moduleids = config('global_values.admin');
                $admin->syncPermissions($permission_moduleids);     
            }elseif($request->role == 5){
                $permission_moduleids = config('global_values.employee');
                $admin->syncPermissions($permission_moduleids);     
            }
        }

        logAdminActivities("Admin Creation", $admin, null);
        return $this->successResponse($admin, 'Admin created Successfully');
    }

    public function updateAdmin(Request $request){
        $adminId = $request->id;
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:admin_users,admin_id',
            'username' => 'required',
            'role' => 'required',
            'mobile_number' => ['required','string','regex:/^\+?[1-9]\d{9,14}$/','digits_between:8,15', Rule::unique('admin_users', 'mobile_number')
                ->where(function ($query) {
                    $query->where('is_deleted', 0);
                })->ignore($adminId, 'admin_id')],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $admin = AdminUser::where('admin_id', $adminId)->first();
        $oldAdmin = $admin;
        if($admin != ''){
            $admin->username = $request->input('username');
            $admin->role = $request->input('role');
            $admin->mobile_number = $request->mobile_number ?? null;
            $admin->save();
            if(isset($request->role) && $request->role != ''){
                if(isset($request->role) && $request->role != ''){
                    if($request->role == 2){
                        $permission_moduleids = config('global_values.manager_permissions');
                        $admin->syncPermissions($permission_moduleids);     
                    }elseif($request->role == 3){
                        $permission_moduleids = config('global_values.accountant_permissions');
                        $admin->syncPermissions($permission_moduleids);     
                    }elseif($request->role == 4){
                        $permission_moduleids = config('global_values.admin');
                        $admin->syncPermissions($permission_moduleids);     
                    }elseif($request->role == 5){
                        $permission_moduleids = config('global_values.employee');
                        $admin->syncPermissions($permission_moduleids);     
                    }
                }
            }
            logAdminActivities("Admin Updation", $oldAdmin, $admin, null);

            return $this->successResponse($admin, 'Admin updated Successfully');
        }else{
            return $this->errorResponse('Admin not Found');
        }
    }

    public function deleteAdmin(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:admin_users,admin_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $admin = AdminUser::find($request->input('id'));
        if(isset($admin->role) && $admin->role != ''){
            if(isset($request->role) && $request->role != ''){
                if($request->role == 2){
                    $permission_moduleids = config('global_values.manager_permissions');
                    $admin->syncPermissions($permission_moduleids);     
                }elseif($request->role == 3){
                    $permission_moduleids = config('global_values.accountant_permissions');
                    $admin->syncPermissions($permission_moduleids);     
                }elseif($request->role == 4){
                    $permission_moduleids = config('global_values.admin');
                    $admin->syncPermissions($permission_moduleids);     
                }elseif($request->role == 5){
                    $permission_moduleids = config('global_values.employee');
                    $admin->syncPermissions($permission_moduleids);     
                }
            }
        }
        $admin->is_deleted = 1;
        $admin->save();

        logAdminActivities("Admin Deletion", $admin, null);
        return $this->successResponse($admin, 'Admin deleted Successfully');
    }

    public function getCities(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';

        $validator = Validator::make($request->all(), [
            'city_id' => 'nullable|exists:cities,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $cities = City::select('id', 'name', 'latitude', 'longitude')->where('is_deleted', 0);
        if(isset($request->city_id) && $request->city_id != NULL){
            $cities = $cities->where('id', $request->city_id)->first();
            return $cities ? $this->successResponse($cities, 'City get Successfully') : $this->errorResponse('City are not Found');
        }
        if(isset($search) && $search != ''){
            $checkCity = City::where('id', (int)$search)->exists();
            if($checkCity){
                $cities = $cities->where('id', $search);
            }
            else{
                $cities = $cities->where(function ($query) use ($search) {
                    $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%$search%"]);
                });
            }
        }
        if($orderColumn != '' && $orderType != ''){
            $cities = $cities->orderBy($orderColumn, $orderType);
        }

        if ($page !== null && $pageSize !== null) {
            $cityDetails = $cities->paginate($pageSize, ['*'], 'page', $page);
            $decodedCity = json_decode(json_encode($cityDetails->getCollection()->values()), FALSE);
            return $this->successResponse([
                'cities' => $decodedCity,
                'pagination' => [
                    'total' => $cityDetails->total(),
                    'per_page' => $cityDetails->perPage(),
                    'current_page' => $cityDetails->currentPage(),
                    'last_page' => $cityDetails->lastPage(),
                    'from' => ($cityDetails->currentPage() - 1) * $cityDetails->perPage() + 1,
                    'to' => min($cityDetails->currentPage() * $cityDetails->perPage(), $cityDetails->total()),
                ]], 'Cities are get successfully');
        }else{
            $cities = [
                'cities' => $cities->get(),
            ];
            if(isset($cities) && is_countable($cities) && count($cities) > 0){
                return $this->successResponse($cities, 'Cities are get successfully');
            }else{
                return $this->errorResponse('Cities are not found');
            }
        }
    }

    public function createOrUpdateCity(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'city_id' => 'nullable|exists:cities,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldVal = $newVal = '';
        if(isset($request->city_id) && $request->city_id != ''){
            $city = City::where('id', $request->city_id)->where('is_deleted', 0)->first();
            if($city != ''){
                $oldVal = clone $city;
            }
        }else{
            $city = new City();
        }

        $city->name = $request->name;
        $city->latitude = $request->latitude;
        $city->longitude = $request->longitude;
        $city->save();

        if(!isset($request->city_id) && $request->city_id == ''){
            logAdminActivities("City Creation", $city);
        }else{
            $newVal = $city;
            $differences = compareArray($oldVal, $newVal);
            if(isset($differences) && is_countable($differences) && count($differences) > 0){
                logAdminActivities('City Updation', $oldVal, $newVal);
            }
        }

        return $this->successResponse($city, 'City details are set Successfully');
    }

    public function deleteCity(Request $request){
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $cityId = $request->city_id;
        $city = City::where('id', $cityId)->first();
        $branch = Branch::where('city_id', $cityId)->count();
        $carHostPickupLocation = CarHostPickupLocation::where('city_id', $cityId)->count();
        if($branch > 0 || $carHostPickupLocation > 0){
            return $this->errorResponse('You can not delete this City due to its associated with any Branch OR Car Host Pickup Location');
        }else if($city != ''){
            $city->is_deleted = 1;
            $city->save();    

            logAdminActivities('City Deletion', $city, NULL);
            return $this->successResponse($city, 'City deleted Successfully');
        }
    }

    public function getBranches(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';

        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,branch_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $branches = Branch::select('branch_id', 'city_id', 'name', 'manager_name', 'address', 'latitude', 'longitude', 'phone', 'email', 'opening_hours', 'is_head_branch')
        ->with('city', function($q){
            $q->select('id', 'name', 'latitude', 'longitude');
        })
        ->where('is_deleted', 0);
        if(isset($request->branch_id) && $request->branch_id != NULL){
            $branches = $branches->where('branch_id', $request->branch_id)->first();
            return $branches ? $this->successResponse($branches, 'Branch get Successfully') : $this->errorResponse('Branch are not Found');
        }
        if(isset($search) && $search != ''){
            $branches = $branches->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%$search%"]);
                $query->OrWhereRaw('LOWER(manager_name) LIKE LOWER(?)', ["%$search%"]);
                $query->OrWhereRaw('LOWER(address) LIKE LOWER(?)', ["%$search%"]);
                $query->OrWhereRaw('LOWER(latitude) LIKE LOWER(?)', ["%$search%"]);
                $query->OrWhereRaw('LOWER(longitude) LIKE LOWER(?)', ["%$search%"]);
                $query->OrWhereRaw('LOWER(phone) LIKE LOWER(?)', ["%$search%"]);
                $query->OrWhereRaw('LOWER(email) LIKE LOWER(?)', ["%$search%"]);
                $query->OrWhereRaw('LOWER(opening_hours) LIKE LOWER(?)', ["%$search%"]);
            });
        }
        if($orderColumn != '' && $orderType != ''){
            $branches = $branches->orderBy($orderColumn, $orderType);
        }

        if ($page !== null && $pageSize !== null) {
            $branchDetails = $branches->paginate($pageSize, ['*'], 'page', $page);
            $decodedBranch = json_decode(json_encode($branchDetails->getCollection()->values()), FALSE);
            return $this->successResponse([
                'branches' => $decodedBranch,
                'pagination' => [
                    'total' => $branchDetails->total(),
                    'per_page' => $branchDetails->perPage(),
                    'current_page' => $branchDetails->currentPage(),
                    'last_page' => $branchDetails->lastPage(),
                    'from' => ($branchDetails->currentPage() - 1) * $branchDetails->perPage() + 1,
                    'to' => min($branchDetails->currentPage() * $branchDetails->perPage(), $branchDetails->total()),
                ]], 'Branches are get successfully');
        }else{
            $branches = [
                'branches' => $branches->get(),
            ];
            if(isset($branches) && is_countable($branches) && count($branches) > 0){
                return $this->successResponse($branches, 'Branches are get successfully');
            }else{
                return $this->errorResponse('Branches are not found');
            }
        }
    }

    public function createOrUpdateBranch(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'manager_name' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'phone' => 'required',
            'mobile_number' => [
                'numeric',
                'digits_between:8,15',
            ],
            'email' => [
                'nullable',
                'email',
                'email:rfc,dns',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Check if '@' exists in the value before exploding
                    if (!str_contains($value, '@')) {
                        $fail('The :attribute must be a valid email address.');
                        return;
                    }
                    // Split the email into local part (before @) and domain part (after @)
                    [$localPart, $domain] = explode('@', $value, 2); // Limit to 2 parts to avoid errors
                    // Allow only a-z, 0-9, ., _, - in the local part
                    if (!preg_match('/^[a-z0-9._-]+$/', $localPart)) {
                        $fail('The :attribute can only contain lowercase letters, numbers, dots (.), underscores (_), and dashes (-).');
                    }
                    // Ensure the local part contains at least one letter (a-z)
                    if (!preg_match('/[a-z]/', $localPart)) {
                        $fail('The :attribute must contain at least one letter.');
                    }
                },
                'required_if:otp_via,email' // Email is required if otp_via is 'email'
            ],
            'opening_hours' => 'required',
            'is_head_branch' => 'required|in:0,1',
            'city_id' => 'nullable|exists:cities,id',
            'branch_id' => 'nullable|exists:branches,branch_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldVal = $newVal = '';
        if(isset($request->branch_id) && $request->branch_id != ''){
            $branch = Branch::where('branch_id', $request->branch_id)->where('is_deleted', 0)->first();
            if($branch != ''){
                $oldVal = clone $branch;
            }
        }else{
            $branch = new Branch();
        }

        if($request->is_head_branch != '' && $request->is_head_branch == 1){
            $branchCheck = Branch::where('is_deleted', 0)->get();
            if(isset($branchCheck) && is_countable($branchCheck) && count($branchCheck) > 0){
                foreach($branchCheck as $k => $v){
                    $v->is_head_branch = 0;
                    $v->save();
                }
            }
        }
        $branch->city_id = $request->city_id ?? NULL;
        $branch->name = $request->name;
        $branch->manager_name = $request->manager_name ?? NULL;
        $branch->address = $request->address;
        $branch->latitude = $request->latitude;
        $branch->longitude = $request->longitude;
        $branch->phone = $request->phone ?? NULL;
        $branch->email = $request->email ?? NULL;
        $branch->opening_hours = $request->opening_hours ?? NULL;
        $branch->is_head_branch = $request->is_head_branch;
        $branch->save();

        if(!isset($request->branch_id) && $request->branch_id == ''){
            logAdminActivities("Branch Creation", $branch);
        }else{
            $newVal = $branch;
            $differences = compareArray($oldVal, $newVal);
            if(isset($differences) && is_countable($differences) && count($differences) > 0){
                logAdminActivities('Branch Updation', $oldVal, $newVal);
            }
        }

        return $this->successResponse($branch, 'Branch details are set Successfully');
    }

    public function deleteBranch(Request $request){
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,branch_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $branchId = $request->branch_id;
        $branch = Branch::where('branch_id', $branchId)->first();
        $checkBranch = Vehicle::where('branch_id', $branchId)->count();
        if($checkBranch > 0){
            return $this->errorResponse('You can not delete this Branch due to its associated with any Vehicle');
        }else if($branch != ''){
            if(isset($branch->is_head_branch) && $branch->is_head_branch == 1){
                return $this->errorResponse("You can't delete this branch as it is head branch. Please make another branch as a head branch to delete this");
            }
            $branch->is_deleted = 1;
            $branch->save();    

            logAdminActivities('Branch Deletion', $branch, NULL);
            return $this->successResponse($branch, 'Branch deleted Successfully');
        }
    }

    private function getCustomerStatus($customers){
        $cStatus = '';
        if($customers != ''){
            if($customers->is_blocked == 1)
                $cStatus = 'Bloked';
            else
                $cStatus = 'Active';
        }

        return $cStatus;
    }

    public function getNotificationCustomers(Request $request){
        $details = [];
        $emailCustomers = Customer::select('customer_id', 'email', 'mobile_number')->where(['is_deleted' => 0, 'is_blocked' => 0, 'is_test_user' => 0])->where('email', '!=', '')->get();
        $mobileCustomers = Customer::select('customer_id', 'email', 'mobile_number')->where(['is_deleted' => 0, 'is_blocked' => 0, 'is_test_user' => 0])
        ->whereHas('customerDeviceToken', function ($query) {
            $query->where('is_deleted', 0)->where('is_error', 0);
        })->where('mobile_number', '!=', '')->get();
        $details['email_customers'] = $emailCustomers;
        $details['mobile_customers'] = $mobileCustomers;
        if(isset($details) && is_countable($details) && count($details) > 0){
            return $this->successResponse($details, 'customers Successfully');         
        }else{
            return $this->errorResponse('Details are not Found');
        }
    }

    public function sendEmail(Request $request){
        $validator = Validator::make($request->all(), [
            'to' => 'required',
            'subject' => 'required',
            'content' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
    
        $to = $request->to;
        $subject = $request->input('subject');
        $content = $request->input('content');
        $env = config('global_values.environment');
        if($env != '' && $env == 'live'){
            $to = str_replace(', ', ',', $to);
            $to = explode(',', $to);
            try{
                $adminId = auth()->guard('admin')->user()->admin_id ? auth()->guard('admin')->user()->admin_id : '';
                SendAdminEmailNotification::dispatch($to, $subject, $content,'email', $adminId)->onQueue('emails');
            } catch (\Exception $e) {}
            return $this->successResponse($to, 'Email has been sent successfully!');        
        }else{
            return $this->errorResponse("You can't send mail on Staging Env.");
        }
    }

    public function getFilteredCustomers(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'from_date' => 'nullable',
            'to_date' => 'nullable|after_or_equal:from_date',
            'call_from' => 'required|in:1,2',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
    
        $startDate = Carbon::parse($request->from_date)->startOfDay();
        $endDate = Carbon::parse($request->to_date)->endOfDay();
        $customers = Customer::select('customer_id', 'email', 'mobile_number', 'created_at', 'country_code', 'firstname', 'lastname', 'dob', 'profile_picture_url', 'is_deleted', 'is_blocked')->where(['is_deleted' => 0, 'is_blocked' => 0]);
        if(isset($request->from_date) && $request->from_date != '' && isset($request->to_date) && $request->to_date != ''){
            $customers = $customers->whereBetween('created_at', [$startDate, $endDate]);
        }

        if(isset($request->call_from) && $request->call_from == 1){
            $customers = $customers->where('email', '!=', '');
        }elseif(isset($request->call_from) && $request->call_from == 2){
            $customers = $customers->where('mobile_number', '!=', '')
            ->whereHas('customerDeviceToken', function ($query) {
                $query->where('is_deleted', 0)->where('is_error', 0);
            });
        }

        if(isset($search) && $search != ''){
            $checkCust = Customer::where('customer_id', (int)$search)->exists();
            if($checkCust){
                $customers = $customers->where('customer_id', $search);
            }
            else{
                $customers = $customers->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(country_code) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(mobile_number) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(firstname) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(lastname) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('CONCAT(firstname, " ", lastname) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('CONCAT(lastname, " ", firstname) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(billing_address) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(device_token) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(device_id) LIKE LOWER(?)', ["%$search%"]);
                    // Check if search input is a valid date format (DD-MM-YYYY)
                    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $search)) {
                        try {
                            $dobFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $search)->format('Y-m-d');
                            $query->orWhereDate('dob', $dobFormatted);
                        } catch (\Exception $e) {}
                    }
                    if(strtolower($search) == 'bloked'){
                        $query->orWhere('is_blocked', 1);
                    }elseif(strtolower($search) == 'active'){
                        $query->orWhere('is_blocked', 0);
                    }
                });
            }
        }

        if ($page !== null && $pageSize !== null) {
            $customers = $customers->paginate($pageSize, ['*'], 'page', $page);
            if(isset($customers) && is_countable($customers) && count($customers) > 0){
                foreach($customers as $k => $v){
                    $v->creation_date = date('d-m-Y H:i', strtotime($v->created_at));
                }
                $customers->each(function ($customer) {
                    $customer->makeHidden(['created_at']);
                });
            }
            $decodedAdmins = json_decode(json_encode($customers->getCollection()->values()), FALSE);
            return $this->successResponse([
                'customers' => $decodedAdmins,
                'pagination' => [
                    'total' => $customers->total(),
                    'per_page' => $customers->perPage(),
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'from' => ($customers->currentPage() - 1) * $customers->perPage() + 1,
                    'to' => min($customers->currentPage() * $customers->perPage(), $customers->total()),
                ]], 'Customers fetched successfully');
        }else{
            $customers = $customers->get();
            if(isset($customers) && is_countable($customers) && count($customers) > 0){
                foreach($customers as $k => $v){
                    $v->creation_date = date('d-m-Y H:i', strtotime($v->created_at));
                }
                $customers->each(function ($customer) {
                    $customer->makeHidden(['created_at']);
                });
                $customers = [
                        'customers' => $customers,
                    ];
                return $this->successResponse($customers, 'Customers get successfully!');        
            }else{
                return $this->errorResponse("Customers are not Found");
            }
        }
    }

    public function getPolicies(Request $request){
        $validator = Validator::make($request->all(), [
            'policy_id' => 'nullable|exists:policies,policy_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $policies = Policy::Query();
        if(isset($request->policy_id) && $request->policy_id != ''){
            $policies = $policies->where('policy_id', $request->policy_id)->first();
            if(isset($policies) && $policies != ''){
                return $this->successResponse($policies, 'Policies are get successfully!');        
            }else{
                return $this->errorResponse("Policies are not Found");
            }
        }else{
            $policies = $policies->get();
            if(isset($policies) && is_countable($policies) && count($policies) > 0){
                return $this->successResponse($policies, 'Policies are get successfully!');        
            }else{
                return $this->errorResponse("Policies are not Found");
            }
        }
    }

    public function editOrResetPolicy(Request $request){
        $validator = Validator::make($request->all(), [
            'policy_id' => 'required|exists:policies,policy_id',
            'action' => 'required|in:0,1', // 0 MEANS EDIT 1 MEANS RESET
            'policy_title' => 'nullable',
            'policy_content' => 'nullable'
        ]);
        $validator->sometimes('policy_title', 'required', function ($input) {
            return $input->action == 0;
        });
        $validator->sometimes('policy_content', 'required', function ($input) {
            return $input->action == 0;
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        if($request->action == 0){
            $policy = Policy::where('policy_id', $request->policy_id)->first();
            $oldVal = clone $policy;

            if($policy != ''){
                $policy->title = isset($request->policy_title)?$request->policy_title:'';
                $policy->content = isset($request->policy_content)?$request->policy_content:'';
                $policy->save();

                $newVal = $policy;
                $differences = compareArray($oldVal, $newVal);
            
                if(isset($differences) && is_countable($differences) && count($differences) > 0){
                    logAdminActivities('Policy Updation', $oldVal, $newVal);
                }

                return $this->successResponse($policy, 'Policies are set successfully!');    
            }
        }elseif($request->action == 1){
            $policy = Policy::where('policy_id', $request->policy_id)->first();
            if($policy != ''){
                $policy->content = NULL;
                $policy->save();

                return $this->successResponse($policy, 'Policies are Re-set successfully!');    
            }
        }
    }

    public function getSettingsDetails(Request $request){
        $details = [];
        $setting = Setting::first();
        $currentTime = Carbon::now();
        $checkSetting = Setting::whereTime('payment_gateway_alter_start_time', '<=', $currentTime)
            ->where('payment_gateway_alter_end_time', '>=', $currentTime)
            ->get();
        $offerDates = OfferDate::get();
        $vehicles = Vehicle::where(['availability' => 1, 'is_deleted' => 0])->get();

        $vehicles->each(function ($vehicle) {
            $vehicle->makeHidden(['branch_id', 'model_id', 'year', 'description', 'color', 'availability', 'rental_price', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'chassis_no', 'cutout_image', 'banner_image', 'banner_images', 'regular_images', 'location', 'rating', 'total_rating', 'trip_count']);
        });
        $appStatusDetails = AppStatus::get();
        if(isset($appStatusDetails) && is_countable($appStatusDetails) && count($appStatusDetails) > 0){
            foreach($appStatusDetails as $key => $val){
                $osName = '';
                if($val->os_type == 1){
                    $osName = 'Android';
                }elseif($val->os_type == 2){
                    $osName = 'IOS';
                }
                $val->os_name = $osName;
            }
        }

        $rewardTypes = config('global_values.reward_types');
        $rewardArr = [];
        if(isset($rewardTypes) && is_countable($rewardTypes) && count($rewardTypes) > 0){
            foreach($rewardTypes as $key => $val){
                $rewardArr[] = [
                    'key' => $key,
                    'name' => $val,
                ];
            }
        }
        $setting->reward_types = $rewardArr;
        $settingUpdateDetails = config('global_values.setting_update_flag');
        $details['appStatusDetails'] = $appStatusDetails;
        $details['setting'] = $setting;
        $details['checkSetting'] = $checkSetting;
        $details['offerDates'] = $offerDates;
        $details['vehicles'] = $vehicles;
        $details['settingUpdateDetails'] = $settingUpdateDetails;

        return $this->successResponse($details, 'Setting details are get successfully!');    
    }

    public function updateSettingsDetails(Request $request){
        $osType = array_keys(config('global_values.os_type'));
        $settingUpdateDetails = config('global_values.setting_update_flag');
        $settingUpdateDetails = implode(',', $settingUpdateDetails);
        $paymentGatewayType = config('global_values.payment_gateway_type');
        $paymentGatewayType = implode(',', $paymentGatewayType);
        $rewardType = array_keys(config('global_values.reward_types'));
        $validator = Validator::make($request->all(), [
            'os_type' => 'nullable|in:' . implode(',', $osType),
            'version' => 'nullable',
            'maintenance' => 'nullable',
            'alert_title' => 'nullable|max:100',
            'alert_message' => 'nullable|max:100',
            'show_all_vehicle' => 'nullable|in:0,1',
            'booking_gap' => 'nullable|numeric',
            'offer_details' => 'nullable',
            'payment_gateway_alter_start_time' => 'nullable',
            'payment_gateway_alter_end_time' => 'nullable',
            'payment_gateway_type' => 'nullable|in:'.$paymentGatewayType,
            'reward_type' => 'nullable|in:'.implode(',', $rewardType),
            'reward_val' => 'nullable',
            'reward_max_discount_amount' => 'nullable',
            'reward_html' => 'nullable|max:1000',
            'update_flag' => 'required|in:'.$settingUpdateDetails,
            'cust_doc_verif_limits' => 'nullable|numeric',
            'location_km_distance_val' => 'nullable|numeric',
        ]);
        $validator->sometimes(['os_type','version','alert_title','alert_message'], 'required', function ($input) {
            return $input->update_flag == 'app_detail';
        });
        $validator->sometimes('show_all_vehicle', 'required', function ($input) {
            return $input->update_flag == 'vehicle_show';
        });
        $validator->sometimes('booking_gap', 'required', function ($input) {
            return $input->update_flag == 'vehicle_booking_gap';
        });
        $validator->sometimes('offer_details', 'required', function ($input) {
            return $input->update_flag == 'vehicle_offer';
        });
        $validator->sometimes(['payment_gateway_alter_start_time', 'payment_gateway_alter_end_time'], 'required', function ($input) {
            return $input->update_flag == 'payment_gateway';
        });
        $validator->sometimes(['reward_type', 'reward_val', 'reward_max_discount_amount', 'reward_html'], 'required', function ($input) {
            return $input->update_flag == 'refer_earn';
        });
        $validator->sometimes(['reward_type', 'reward_val', 'reward_max_discount_amount', 'reward_html'], 'required', function ($input) {
            return $input->update_flag == 'refer_earn';
        });
        $validator->sometimes('cust_doc_verif_limits', 'required', function ($input) {
            return $input->update_flag == 'cust_doc_verif_limits';
        });
        $validator->sometimes('location_km_distance_val', 'required', function ($input) {
            return $input->update_flag == 'location_km_distance';
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $updateFlag = $request->update_flag;
        if($updateFlag == 'app_detail'){
            $appStatus = AppStatus::where('id', $request->os_type)->first();   
            $oldVal = clone $appStatus;

            if(isset($request->version) && $appStatus != '' && ($appStatus->version != $request->version)){
                $loginToken = LoginToken::get();
                if(is_countable($loginToken) && count($loginToken) > 0){
                    foreach ($loginToken as $key => $value) {
                        $value->delete();
                    }
                }
            }
            //$appStatus->os_type = $request->input('os_type');
            $appStatus->version = $request->input('version') != '' ? $request->input('version') : NULL;
            $appStatus->maintenance = $request->input('maintenance') != '' ? $request->input('maintenance') : 0;
            $appStatus->alert_title = $request->input('alert_title') != '' ? $request->input('alert_title') : NULL;
            $appStatus->alert_message = $request->input('alert_message') != '' ? $request->input('alert_message') : NULL;
            $appStatus->save();

            $newVal = $appStatus;
            $differences = compareArray($oldVal, $newVal);
            if(isset($differences) && is_countable($differences) && count($differences) > 0){
                logAdminActivities('App Status Updation', $oldVal, $newVal);
            }

            return $this->successResponse($appStatus, 'App Status Set successfully!');   
        }elseif($updateFlag == 'vehicle_show'){
            $setting = Setting::first();
            if($setting == ''){
                $setting = new Setting();
            }
            $message = '';
            if($request->show_all_vehicle == 1){
                $setting->show_all_vehicle = 1;    
                $message = 'Show all Flag On Successfully';
            }else{
                $setting->show_all_vehicle = 0;
                $message = 'Show all Flag Off Successfully';
            }
            $setting->save();

            return $this->successResponse($setting, $message);   
        }elseif($updateFlag == 'vehicle_booking_gap'){
            $setting = Setting::first();
            if($request->booking_gap != '' && $request->booking_gap != 0){
                if($setting == ''){
                    $setting = new Setting();
                }
                $setting->booking_gap = isset($request->booking_gap)?$request->booking_gap:NULL;    
                $setting->save();
                $message = 'Booking Gap minutes set Successfully';
            }   
            else{
                $message = 'Please enter proper value for Booking Gap';
            }
            return $this->successResponse($setting, $message);  
        }elseif($updateFlag == 'vehicle_offer'){
            $offerDetail = $request->offer_details;
            $offerDetail = is_array($offerDetail) ? $offerDetail : json_decode($offerDetail);
            if (isset($offerDetail) && is_countable($offerDetail) && count($offerDetail) > 0) {
                OfferDate::truncate();
                foreach ($offerDetail as $key => $value) {
                    $offerDate = new OfferDate();
                    $offerDate->vehicle_id = $value['vehicle_id'];
                    $offerDate->vehicle_offer_start_date = $value['vehicle_offer_start_date'] ? date('Y-m-d H:i', strtotime($value['vehicle_offer_start_date'])) : NULL ;
                    $offerDate->vehicle_offer_end_date = $value['vehicle_offer_end_date'] ? date('Y-m-d H:i', strtotime($value['vehicle_offer_end_date'])) : NULL;
                    $offerDate->vehicle_offer_price = $value['vehicle_offer_price'] ?? NULL;
                    $offerDate->save();
                }
            }
            return $this->successResponse($offerDate, 'Offer Dates set Successfully');  
        }elseif($updateFlag == 'payment_gateway'){
            $setting = Setting::first();
            if($setting == '') {
                $setting = new Setting();
            }
            $setting->payment_gateway_alter_start_time = $request->payment_gateway_alter_start_time ?? NULL;
            $setting->payment_gateway_alter_end_time = $request->payment_gateway_alter_end_time ?? NULL;
            if($request->payment_gateway_type != ''){
                $setting->payment_gateway_type = $request->payment_gateway_type;
            }
            $setting->save();
            return $this->successResponse($setting, 'Payment Details are stored successfully');  
        }elseif($updateFlag == 'refer_earn'){
            $message = 'Something went Wrong';
            if($request->reward_type != '' && $request->reward_val != ''){
                $setting = Setting::first();
                if($setting == ''){
                    $setting = new Setting();
                }
                $setting->reward_type = $request->reward_type ?? '';    
                $setting->reward_val = $request->reward_val ?? '';    
                $setting->reward_html = $request->reward_html ?? '';
                $setting->reward_max_discount_amount = $request->reward_max_discount_amount ?? 0;
                $setting->save();
                $message = 'Refer & Earn Details are set Successfully';
            }   
            else{
                $message = 'Reward Type & Reward Value are required';
            }
            return $this->successResponse($setting, $message);  
        }elseif($updateFlag == 'cust_doc_verif_limits'){
            $message = 'Something went Wrong';
            $setting = Setting::first();
            if($setting == ''){
                $setting = new Setting();
            }
            $setting->cust_doc_verif_limits = $request->cust_doc_verif_limits ?? 3;
            $setting->save();
            $message = 'Customer Document Verify Limit set Successfully';
            
            return $this->successResponse($setting, $message);  
        }elseif($updateFlag == 'location_km_distance'){
            $message = 'Something went Wrong';
            $setting = Setting::first();
            if($setting == ''){
                $setting = new Setting();
            }
            $setting->location_km_distance_val = $request->location_km_distance_val ?? 50;
            $setting->save();
            $message = 'Host Location Km. Distance Value set Successfully';
            
            return $this->successResponse($setting, $message); 
        }
    }

    public function getAdminActivityLog(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'admin_id' => 'nullable|exists:admin_users,admin_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $adminActivityLog = AdminActivityLog::select('admin_activity_log.log_id', 'admin_activity_log.activity_description', 'admin_activity_log.old_value', 'admin_activity_log.new_value', 'admin_users.admin_id', 'admin_users.username', 'roles.name as rolename', 'admin_users.created_at', 'admin_users.updated_at')
        ->leftJoin('admin_users', 'admin_users.admin_id', '=', 'admin_activity_log.admin_id')
        ->leftJoin('roles', 'roles.id', '=', 'admin_users.role')
        ->whereNotIn('admin_users.admin_id', [1])
        ->with(['adminDetails' => function($q) {
            $q->select('admin_id', 'username', 'role', 'created_at', 'updated_at');
        }]);

        if (!empty($request->admin_id)) {
            $adminLog = $adminActivityLog->where('admin_id', $request->admin_id)->first();
            $adminLog->old_value = json_decode($adminLog->old_value);
            $adminLog->new_value = json_decode($adminLog->new_value);
            return $adminLog ? $this->successResponse($adminLog, 'Admin activity log details fetched successfully') : $this->errorResponse('Admin activity log details are not found');
        }
        if($orderColumn != '' && $orderType != ''){
            $adminActivityLog = $adminActivityLog->orderBy($orderColumn, $orderType);
        }

        if ($page !== null && $pageSize !== null) {
            $admins = $adminActivityLog->paginate($pageSize, ['*'], 'page', $page);
            if(isset($admins) && is_countable($admins) && count($admins) > 0){
                $admins->each(function ($log) {
                    $log->old_value = json_decode($log->old_value);
                    $log->new_value = json_decode($log->new_value);
                });
            }
            $decodedAdmins = json_decode(json_encode($admins->getCollection()->values()), FALSE);
            return $this->successResponse([
                'admins' => $decodedAdmins,
                'pagination' => [
                    'total' => $admins->total(),
                    'per_page' => $admins->perPage(),
                    'current_page' => $admins->currentPage(),
                    'last_page' => $admins->lastPage(),
                    'from' => ($admins->currentPage() - 1) * $admins->perPage() + 1,
                    'to' => min($admins->currentPage() * $admins->perPage(), $admins->total()),
                ]], 'Admin activity Log fetched successfully');
        }else{
            $adminActivityLog = $adminActivityLog->get();
            $adminActivityLog->each(function ($log) {
                $log->old_value = json_decode($log->old_value);
                $log->new_value = json_decode($log->new_value);
            });
            $adminActivityLog = [
                'adminActivityLog' => $adminActivityLog
            ];
            if(isset($adminActivityLog) && is_countable($adminActivityLog) && count($adminActivityLog) > 0){
                return $this->successResponse($adminActivityLog, 'Admin activity Log fetched successfully');
            }else{
                return $this->errorResponse('Admin activity Log not found');
            }
        }
    }

    public function sendPushNotifications(Request $request){
        $tokens = $request->to;
        $mobileNos = is_string($request->to) ? explode(',', $request->to) : $request->to;
        $validator = Validator::make($request->all(), [
            'select_all' => 'required|in:0,1',
            'show_guest' => 'required|in:0,1',
            'to' => 'required',
            'title' => 'required|max:1000',
            'content' => 'required|max:10000'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $showStatus = 0;
        $showAllStatus = 0;
        if($request->select_all == 1 && $request->show_guest == 1){
            $showStatus = 1;
        }
        if($request->select_all == 1){
            $showAllStatus = 1;
        }
        
        $title = $request->input('title');
        $content = $request->input('content');
        $env = config('global_values.environment');
        if(isset($mobileNos) && is_countable($mobileNos) && $mobileNos[0] == 0){
            $mobileNos = 0;
        }
        if($env != '' && $env == 'live'){
            try{
                $adminId = auth()->guard('admin')->user()->admin_id ? auth()->guard('admin')->user()->admin_id : '';
                SendAdminEmailNotification::dispatch($mobileNos, $title, $content,'push_notification', $adminId, $showStatus, $showAllStatus)->onQueue('emails');
            } catch (\Exception $e) {}
            return $this->successResponse(null, 'Notification has been sent successfully!');
        }else{
            return $this->successResponse(null, 'You can not send notification on staging Environment');
        }
    }

    public function getPermissions(Request $request){
        $data = [];
        $adminUser = auth()->guard('admin')->user();
        $permissions = $adminUser->getAllPermissions();
        $permissions->makeHidden(['guard_name', 'module_id', 'created_at', 'updated_at', 'pivot']);
        $data['permissions'] = $permissions;
        $data['bookingHistoryPermission'] = [];
        
        $addBooking = $this->checkIfPermissionExisted($permissions, 'add-booking');
        $bookingHistoryInDetails = $this->checkIfPermissionExisted($permissions, 'booking-history-indetails');
        $bookingHistoryOperations = $this->checkIfPermissionExisted($permissions, 'booking-history-operations');
        $data['bookingHistoryPermission'] = [
            'add-booking' => $addBooking ? 1 : 0,
            'update-vehicle' => $bookingHistoryInDetails ? 1 : 0,
            'start-journey' => $bookingHistoryInDetails ? 1 : 0,
            'extend-booking' => $bookingHistoryInDetails ? 1 : 0,
            'end-journey' => $bookingHistoryInDetails ? 1 : 0,
            'cancel-booking' => $bookingHistoryInDetails ? 1 : 0,
            'start-kilometer' => $bookingHistoryOperations ? 1 : 0,
            'end-kilometer' => $bookingHistoryOperations ? 1 : 0,
            'action' => $bookingHistoryOperations ? 1 : 0,
            'penalty' => $bookingHistoryOperations ? 1 : 0
        ];
         
        if(isset($data) && is_countable($data) && count($data) > 0){
            return $this->successResponse($data, 'Permissions details are get Successfully');
        }else{
            return $this->errorResponse('Permissions details are not Found');
        }
    }

    protected function checkIfPermissionExisted($permissions, $permissionToCheck){
        $exists = false;
        foreach ($permissions as $p) {
            if ($p['name'] === $permissionToCheck) {
                $exists = true;
                break;
            }
        }
        return $exists;
    }

    public function logout() {
        auth()->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'Successfully logged out');
    }
}