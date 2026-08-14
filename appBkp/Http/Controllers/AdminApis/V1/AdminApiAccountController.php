<?php

namespace App\Http\Controllers\AdminApis\V1;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\{RentalBooking,BookingTransaction,Payment,Vehicle,CarEligibility,CarHost,CarHostBank,PaymentReportHistory};
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\CarhostAppApis\V1\CarHostController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;

class AdminApiAccountController extends Controller
{
    public function AdminAccountDetails(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('page_size', $request->input('per_page', 10));
        $search = $request->input('search', '');
        $bookingId = $request->input('booking_id', '');
        $startDate = $request->input('from', $request->input('start_date', ''));
        $endDate = $request->input('to', $request->input('end_date', ''));
        
        // Track export if is_downloading is true
        $isDownloading = filter_var($request->input('is_downloading', false), FILTER_VALIDATE_BOOLEAN);
        $sessionId = $request->input('session_id', '');
        $isCompleted = filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN);
        
        // Build base query - use whereHas to check for carHost existence
        $baseQuery = RentalBooking::where('rental_bookings.status', 'completed')
            ->whereHas('pickupLocation', function($query) {
                $query->whereNotNull('car_hosts_id');
            });
        
        // Filter by payment_report_history based on is_downloading, session_id, and is_completed
        if (!$isDownloading) {
            // When NOT downloading: exclude all bookings that exist in payment_report_history
            $baseQuery->whereDoesntHave('paymentReportHistory');
        } else {
            // When downloading: filter based on session_id and is_completed
            if (!empty($sessionId)) {
                // If session_id provided: exclude bookings that are completed in OTHER sessions
                // This allows bookings from the current session to be included (for pagination)
                $baseQuery->whereDoesntHave('paymentReportHistory', function($query) use ($sessionId) {
                    $query->where('session_id', '!=', $sessionId)
                        ->where('is_completed', true);
                });
                
                // If is_completed is true, also exclude bookings already completed in this session
                if ($isCompleted) {
                    $baseQuery->whereDoesntHave('paymentReportHistory', function($query) use ($sessionId) {
                        $query->where('session_id', $sessionId)
                            ->where('is_completed', true);
                    });
                }
            } else {
                // If no session_id: exclude bookings that are completed (already exported)
                $baseQuery->whereDoesntHave('paymentReportHistory', function($query) {
                    $query->where('is_completed', true);
                });
            }
        }
        
        // Apply booking_transactions date filter if provided
        if (!empty($startDate) || !empty($endDate)) {
            try {
                $parsedStartDate = !empty($startDate) ? Carbon::parse($startDate)->startOfDay() : null;
                $parsedEndDate = !empty($endDate) ? Carbon::parse($endDate)->endOfDay() : null;
                
                // Use whereHas to filter bookings with completion transactions in date range
                $baseQuery->whereHas('bookingTransactions', function ($query) use ($parsedStartDate, $parsedEndDate) {
                    $query->where('type', 'completion');
                    
                    if ($parsedStartDate && $parsedEndDate) {
                        $query->whereBetween('timestamp', [$parsedStartDate, $parsedEndDate]);
                    } elseif ($parsedStartDate) {
                        $query->where('timestamp', '>=', $parsedStartDate);
                    } elseif ($parsedEndDate) {
                        $query->where('timestamp', '<=', $parsedEndDate);
                    }
                });
            } catch (\Exception $e) {
                // If date parsing fails, skip the filter
                // Log error if needed: \Log::error('Date filter error: ' . $e->getMessage());
            }
        }
        
        // Apply filters
        if (!empty($bookingId)) {
            $baseQuery->where('rental_bookings.booking_id', $bookingId);
        }
        if (!empty($search)) {
            $baseQuery->where('rental_bookings.booking_id', 'LIKE', "%{$search}%");
        }
        
        // Get total count
        $totalCount = $baseQuery->distinct('rental_bookings.booking_id')->count('rental_bookings.booking_id');
        
        // Early return if no data found
        if (!empty($startDate) && !empty($endDate) && !empty($bookingId) && $totalCount == 0) {
            return $this->errorResponse('No data found');
        }
        
        // Calculate pagination
        $totalPage = ceil($totalCount / $perPage);
        $offset = ($page - 1) * $perPage;
        $from = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $to = min($page * $perPage, $totalCount);
        
        // Get paginated bookings
        $bookings = $baseQuery
            ->select('rental_bookings.*')
            ->groupBy('rental_bookings.booking_id')
            ->offset($offset)
            ->limit($perPage)
            ->get();
        
        // Pre-load all related data in batches to avoid N+1 queries
        $vehicleIds = $bookings->pluck('vehicle_id')->unique()->filter();
        $bookingIds = $bookings->pluck('booking_id')->unique()->filter();
        
        // Batch load all related data
        $vehicles = Vehicle::whereIn('vehicle_id', $vehicleIds)->get()->keyBy('vehicle_id');
        $allPayments = Payment::whereIn('booking_id', $bookingIds)->get()->groupBy('booking_id');
        $allBookingTransactions = BookingTransaction::whereIn('booking_id', $bookingIds)->get()->groupBy('booking_id');
        $carEligibilities = CarEligibility::whereIn('vehicle_id', $vehicleIds)
            ->with('carHost')
            ->get()
            ->keyBy('vehicle_id');
        
        // Load CarHostBanks separately
        $carHostIds = $carEligibilities->pluck('car_hosts_id')->unique()->filter();
        $carHostBanks = CarHostBank::whereIn('car_hosts_id', $carHostIds)
            ->where('is_deleted', 0)
            ->get()
            ->groupBy('car_hosts_id')
            ->map(function($banks) {
                return $banks->first(); // Get first bank for each car host
            });
        
        // Helper functions (defined once outside loop)
        $removeSpecialChars = function($text) {
            return empty($text) ? null : preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
        };
        
        $truncate = function($text, $maxLength) {
            return empty($text) ? null : mb_substr($text, 0, $maxLength);
        };
        
        // Process bookings
        $result = [];
        $labels = [
            "PYMT_PROD_TYPE_CODE", "PYMT_MODE", "DEBIT_ACC_NO", "BNF_NAME", "BENE_ACC_NO",
            "BENE_IFSC", "AMOUNT", "DEBIT_NARR", "CREDIT_NARR", "MOBILE_NUM", "EMAIL_ID",
            "REMARK", "PYMT_DATE", "REF_NO", "ADDL_INFO1", "ADDL_INFO2", "ADDL_INFO3",
            "ADDL_INFO4", "ADDL_INFO5"
        ];
        
        foreach ($bookings as $booking) {
            $vehicle = $vehicles->get($booking->vehicle_id);
            $payments = $allPayments->get($booking->booking_id, collect());
            
            if (!$vehicle || $payments->isEmpty()) {
                continue;
            }
            
            $carEligibility = $carEligibilities->get($booking->vehicle_id);
            $carHost = $carEligibility?->carHost;
            $carHostBank = $carHost ? $carHostBanks->get($carHost->id) : null;
            
            if (!$carHost) {
                continue;
            }
            
            $payment = $payments->first();
            
            // Build beneficiary name
            $beneficiaryName = strtoupper(trim($carHostBank->account_holder_name ?? ''));
            
            // Determine payment mode
            $paymentMode = 'NEFT';
            if ($carHostBank && !empty($carHostBank->ifsc_code)) {
                $ifscCode = strtoupper(trim($carHostBank->ifsc_code));
                if (str_contains($ifscCode, 'ICIC')) {
                    $paymentMode = 'FT';
                }
            }
            
            // Format narration
            $licensePlate = $removeSpecialChars($vehicle->license_plate ?? '');
            $narration = $truncate(trim($booking->booking_id . $licensePlate), 30);
            
            // Calculate amount using the same logic as host invoice (carhostBookingInvoiceData)
            $totalAmount = 0;
            $bookingTransactions = $allBookingTransactions->get($booking->booking_id, collect());
            
            foreach ($bookingTransactions as $transaction) {
                if ($transaction->type == 'new_booking' && $transaction->paid == 1) {
                    // Rate = trip_amount - vehicle_commission_amount
                    $rate = ($transaction->trip_amount ?? 0) - ($transaction->vehicle_commission_amount ?? 0);
                    // Amount = Rate - Discount
                    $displayedAmount = $rate - ($transaction->coupon_discount ?? 0);
                    $totalAmount += $displayedAmount;
                } elseif ($transaction->type == 'extension' && $transaction->paid == 1) {
                    // Rate = trip_amount - vehicle_commission_amount
                    $rate = ($transaction->trip_amount ?? 0) - ($transaction->vehicle_commission_amount ?? 0);
                    // Amount = Rate - Discount
                    $displayedAmount = $rate - ($transaction->coupon_discount ?? 0);
                    $totalAmount += $displayedAmount;
                } elseif ($transaction->type == 'completion' && $transaction->paid == 1) {
                    // Calculate additional charges
                    $additionalCharges = 0;
                    $additionalCharges += $transaction->late_return ?? 0;
                    $additionalCharges += $transaction->exceeded_km_limit ?? 0;
                    $additionalCharges += $transaction->additional_charges ?? 0;
                    // Rate = additionalCharges - vehicle_commission_amount
                    $rate = round($additionalCharges, 2) - ($transaction->vehicle_commission_amount ?? 0);
                    // Amount = Rate (no discount for completion)
                    $displayedAmount = $rate;
                    $totalAmount += $displayedAmount;
                } elseif ($transaction->type == 'penalty' && $transaction->paid == 1) {
                    // Only include if final_amount > 0 (matching host invoice logic)
                    if (($transaction->final_amount ?? 0) > 0) {
                        // Rate = total_amount
                        $rate = $transaction->total_amount ?? 0;
                        // Amount = Rate
                        $displayedAmount = $rate;
                        $totalAmount += $displayedAmount;
                    }
                }
            }
            
            // $totalAmount = round($totalAmount, 2);
            // $amount = $totalAmount > 0 ? number_format((float)$totalAmount, 2, '.', '') : null;
            $grandTotal = CarHostController::getBookingGrandTotal($booking->booking_id);
            $amount = $grandTotal > 0 ? number_format((float)$grandTotal, 2, '.', '') : null;
            
            // Format beneficiary account number
            $beneAccNo = null;
            if ($carHostBank && !empty($carHostBank->account_no)) {
                $beneAccNo = $truncate(preg_replace('/[^0-9]/', '', $carHostBank->account_no), 32);
            }
            
            // Format IFSC
            $ifsc = null;
            if ($carHostBank && !empty($carHostBank->ifsc_code)) {
                $ifsc = $truncate($removeSpecialChars($carHostBank->ifsc_code), 11);
            }
            
            // Format ADDL_INFO1
            $addlInfo1 = $truncate($removeSpecialChars($payment->razorpay_payment_id ?? ''), 500);
            
            $result[] = [
                "PYMT_PROD_TYPE_CODE" => "PAB_VENDOR",
                "PYMT_MODE" => $paymentMode,
                "DEBIT_ACC_NO" => '777705177771',
                "BNF_NAME" => $beneficiaryName,
                "BENE_ACC_NO" => $beneAccNo,
                "BENE_IFSC" => $ifsc,
                "AMOUNT" => $amount,
                "DEBIT_NARR" => $narration,
                "CREDIT_NARR" => $narration,
                "MOBILE_NUM" => $carHost->mobile_number ?? null,
                "EMAIL_ID" => $carHost->email ?? null,
                "REMARK" => '',
                "PYMT_DATE" => now()->format('d-m-Y'),
                "REF_NO" => '',
                "ADDL_INFO1" => '',
                "ADDL_INFO2" => '',
                "ADDL_INFO3" => '',
                "ADDL_INFO4" => '',
                "ADDL_INFO5" => '',
            ];
            
            // Collect booking data during export process (store in cache)
            // Data will only be saved to DB when is_completed = true
            if ($isDownloading && !empty($sessionId)) {
                $cacheKey = "payment_export_{$sessionId}";
                
                // Get existing cached data for this session
                $cachedData = Cache::get($cacheKey, []);
                
                // Check if this booking already exists in cached data to avoid duplicates
                $bookingExists = false;
                foreach ($cachedData as $cachedBooking) {
                    if ($cachedBooking['booking_id'] == $booking->booking_id) {
                        $bookingExists = true;
                        break;
                    }
                }
                
                // Add booking data to cache if not already present
                if (!$bookingExists) {
                    $cachedData[] = [
                        'booking_id' => $booking->booking_id,
                        'export_data' => [
                            'debit_acc_no' => '777705177771',
                            'beneficiary_name' => $beneficiaryName,
                            'beneficiary_account_no' => $beneAccNo,
                            'beneficiary_ifsc' => $ifsc,
                            'amount' => $amount,
                            'debit_narr' => $narration,
                            'credit_narr' => $narration,
                            'mobile_number' => $carHost->mobile_number ?? null,
                            'email_id' => $carHost->email ?? null,
                            'remark' => '',
                        ],
                    ];
                    
                    // Store in cache for 24 hours (enough time for export to complete)
                    Cache::put($cacheKey, $cachedData, now()->addHours(24));
                }
            }
        }
        
        // When is_completed = true, save all collected data from cache to database
        if ($isDownloading && !empty($sessionId) && $isCompleted) {
            try {
                $cacheKey = "payment_export_{$sessionId}";
                $cachedData = Cache::get($cacheKey, []);
                
                if (!empty($cachedData)) {
                    // Prepare data for bulk insert
                    $insertData = [];
                    $now = now();
                    
                    foreach ($cachedData as $bookingData) {
                        // Check if this booking already exists in database for this session
                        $existingRecord = PaymentReportHistory::where('booking_id', $bookingData['booking_id'])
                            ->where('session_id', $sessionId)
                            ->first();
                        
                        if (!$existingRecord) {
                            $insertData[] = [
                                'booking_id' => $bookingData['booking_id'],
                                'session_id' => $sessionId,
                                'export_data' => json_encode($bookingData['export_data']), // Manual JSON encoding for bulk insert
                                'is_completed' => true,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                    
                    // Bulk insert all records at once
                    if (!empty($insertData)) {
                        $exportedAt = now();
                        $exportFilters = [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'booking_id' => $bookingId,
                            'search' => $search,
                        ];
                        
                        // Add exported_at and export_filters to each record
                        foreach ($insertData as &$data) {
                            $data['exported_at'] = $exportedAt;
                            $data['export_filters'] = json_encode($exportFilters);
                        }
                        
                        PaymentReportHistory::insert($insertData);
                    }
                    
                    // Clear cache after successful save
                    Cache::forget($cacheKey);
                }
            } catch(\Exception $e) {
                // Log error but don't break the API response
            }
        }
        
        return $this->successResponse([
            'data' => $result,
            'labels' => $labels,
            'pagination' => [
                'total_page' => $totalPage,
                'per_page' => (int)$perPage,
                'from' => $from,
                'to' => $to,
                'current_page' => (int)$page,
                'total_records' => $totalCount,
            ]
        ], 'Bookings fetched successfully');
    }
    
    public function getExportSessions(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('page_size', $request->input('per_page', 10));
        $startDate = $request->input('start_date', '');
        $endDate = $request->input('end_date', '');
        
        // Get all unique export sessions (grouped by session_id where is_completed = true)
        $sessionsQuery = PaymentReportHistory::where('is_completed', true);
        
        // Apply exported_at filter if provided
        if (!empty($startDate) && !empty($endDate)) {
            // Both dates provided - use whereBetween for date range
            try {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $sessionsQuery->whereBetween('exported_at', [$start, $end]);
            } catch(\Exception $e) {}
        } elseif (!empty($startDate)) {
            // Only start_date provided - filter for single date
            try {
                $sessionsQuery->whereDate('exported_at', '=', Carbon::parse($startDate)->format('Y-m-d'));
            } catch(\Exception $e) {}
        } elseif (!empty($endDate)) {
            // Only end_date provided - filter for single date
            try {
                $sessionsQuery->whereDate('exported_at', '=', Carbon::parse($endDate)->format('Y-m-d'));
            } catch(\Exception $e) {}
        }
        
        $sessionsQuery->select('session_id')
            ->selectRaw('MIN(exported_at) as exported_at')
            ->selectRaw('MIN(export_filters) as export_filters')
            ->selectRaw('COUNT(*) as record_count')
            ->groupBy('session_id')
            ->orderByRaw('MIN(exported_at) DESC');
        
        // Clone query for count
        $countQuery = clone $sessionsQuery;
        $totalCount = $countQuery->get()->count();
        
        // Calculate pagination
        $totalPage = ceil($totalCount / $perPage);
        $offset = ($page - 1) * $perPage;
        $from = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $to = min($page * $perPage, $totalCount);
        
        // Get paginated sessions
        $sessionsData = $sessionsQuery->offset($offset)->limit($perPage)->get();
        
        // Format response
        $result = $sessionsData->map(function($session) {
            $exportFilters = is_string($session->export_filters) 
                ? json_decode($session->export_filters, true) 
                : ($session->export_filters ?? []);
            
            $exportedAt = $session->exported_at ? Carbon::parse($session->exported_at) : null;
            
            return [
                'session_id' => $session->session_id,
                'exported_at' => $exportedAt ? $exportedAt->format('Y-m-d H:i:s') : null,
                'record_count' => (int)$session->record_count,
                'export_filters' => $exportFilters,
                'date_range' => [
                    'start_date' => $exportFilters['start_date'] ?? null,
                    'end_date' => $exportFilters['end_date'] ?? null,
                ],
            ];
        });
        
        return $this->successResponse([
            'data' => $result,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'last_page' => $totalPage,
                'from' => $from,
                'to' => $to,
            ]
        ], 'Export sessions fetched successfully');
    }
    
    public function getExportSessionRecords(Request $request)
    {
        $sessionId = $request->input('session_id', '');
        $page = $request->input('page', 1);
        $perPage = $request->input('page_size', $request->input('per_page', 10));
        $bookingId = $request->input('booking_id', '');
        
        if (empty($sessionId)) {
            return $this->errorResponse('session_id is required');
        }
        
        // Build query for specific session
        $query = PaymentReportHistory::where('session_id', $sessionId)
            ->where('is_completed', true);
        
        // Filter by booking_id if provided
        if (!empty($bookingId)) {
            $query->where('booking_id', $bookingId);
        }
        
        // Get total count
        $totalCount = $query->count();
        
        // Calculate pagination
        $totalPage = ceil($totalCount / $perPage);
        $offset = ($page - 1) * $perPage;
        $from = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $to = min($page * $perPage, $totalCount);
        
        // Get session info (first record to get export details)
        $sessionInfo = PaymentReportHistory::where('session_id', $sessionId)
            ->where('is_completed', true)
            ->first();
        
        // Get paginated records with booking relationship
        $records = $query->with('booking')
            ->orderBy('booking_id', 'asc')
            ->offset($offset)
            ->limit($perPage)
            ->get();
        
        // Get booking IDs to fetch payments for ADDL_INFO1
        $bookingIds = $records->pluck('booking_id')->unique()->filter();
        $allPayments = Payment::whereIn('booking_id', $bookingIds)->get()->keyBy('booking_id');
        
        // Helper functions
        $removeSpecialChars = function($text) {
            return empty($text) ? null : preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
        };
        
        $truncate = function($text, $maxLength) {
            return empty($text) ? null : mb_substr($text, 0, $maxLength);
        };
        
        // Labels matching AdminAccountDetails format
        $labels = [
            "PYMT_PROD_TYPE_CODE", "PYMT_MODE", "DEBIT_ACC_NO", "BNF_NAME", "BENE_ACC_NO",
            "BENE_IFSC", "AMOUNT", "DEBIT_NARR", "CREDIT_NARR", "MOBILE_NUM", "EMAIL_ID",
            "REMARK", "PYMT_DATE", "REF_NO", "ADDL_INFO1", "ADDL_INFO2", "ADDL_INFO3",
            "ADDL_INFO4", "ADDL_INFO5"
        ];
        
        // Format response data to match AdminAccountDetails format
        $result = [];
        foreach ($records as $record) {
            $exportData = $record->export_data ?? [];
            $payment = $allPayments->get($record->booking_id);
            
            // Determine payment mode from IFSC
            $paymentMode = 'NEFT';
            $ifsc = $exportData['beneficiary_ifsc'] ?? null;
            if ($ifsc) {
                $ifscCode = strtoupper(trim($ifsc));
                if (str_contains($ifscCode, 'ICIC')) {
                    $paymentMode = 'FT';
                }
            }
            
            // Format ADDL_INFO1 from payment
            $addlInfo1 = '';
            if ($payment && !empty($payment->razorpay_payment_id)) {
                $addlInfo1 = $truncate($removeSpecialChars($payment->razorpay_payment_id), 500);
            }
            
            // Get payment date from exported_at or use current date
            $paymentDate = now()->format('d-m-Y');
            if ($record->exported_at) {
                try {
                    $paymentDate = Carbon::parse($record->exported_at)->format('d-m-Y');
                } catch(\Exception $e) {}
            }
            
            $result[] = [
                "PYMT_PROD_TYPE_CODE" => "PAB_VENDOR",
                "PYMT_MODE" => $paymentMode,
                "DEBIT_ACC_NO" => $exportData['debit_acc_no'] ?? '777705177771',
                "BNF_NAME" => strtoupper(trim($exportData['beneficiary_name'] ?? '')),
                "BENE_ACC_NO" => $exportData['beneficiary_account_no'] ?? null,
                "BENE_IFSC" => $exportData['beneficiary_ifsc'] ?? null,
                "AMOUNT" => $exportData['amount'] ?? null,
                "DEBIT_NARR" => $exportData['debit_narr'] ?? null,
                "CREDIT_NARR" => $exportData['credit_narr'] ?? null,
                "MOBILE_NUM" => $exportData['mobile_number'] ?? null,
                "EMAIL_ID" => $exportData['email_id'] ?? null,
                "REMARK" => null,
                "PYMT_DATE" => $paymentDate,
                "REF_NO" => '',
                "ADDL_INFO1" => '',
                "ADDL_INFO2" => '',
                "ADDL_INFO3" => '',
                "ADDL_INFO4" => '',
                "ADDL_INFO5" => '',
            ];
        }
        
        return $this->successResponse([
            'session_info' => $sessionInfo ? [
                'session_id' => $sessionInfo->session_id,
                'exported_at' => $sessionInfo->exported_at ? $sessionInfo->exported_at->format('Y-m-d H:i:s') : null,
                'export_filters' => $sessionInfo->export_filters,
                'total_records' => $totalCount,
            ] : null,
            'data' => $result,
            'labels' => $labels,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'last_page' => $totalPage,
                'from' => $from,
                'to' => $to,
            ]
        ], 'Export session records fetched successfully');
    }
  
}