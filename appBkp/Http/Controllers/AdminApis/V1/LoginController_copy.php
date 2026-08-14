<?php

namespace App\Http\Controllers\AdminApis\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\SmsService;
use App\Services\Invoice\InvoiceCalculationService;
use App\Models\{AdminUser, Vehicle, Customer, CustomerDocument, RentalBooking, BookingTransaction, OfferDate, CompanyDetail, CarEligibility };
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;

class LoginController extends Controller
{
    protected $smsService;
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function adminLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:50|exists:admin_users,username', 
            'password' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $adminUser = AdminUser::where('username', $request->username)->where('is_deleted', 0)->first();
        if (!$adminUser || !Hash::check($request->password, $adminUser->password)) {
            return $this->errorResponse('Username OR Password is incorrect');
        }
        $token = $adminUser->createToken('Admin'.$request->username)->plainTextToken;
        $adminUser->token = $token;
        
        return $this->successResponse($adminUser, 'Login successful');
    }
  
/**
     * Generate booking invoice PDF with accurate calculations
     * 
     * BUSINESS RULES:
     * 1. Grand Total / Amount Paid is the SINGLE source of truth.
     * 2. Discount applies ONLY to Booking charges, applied BEFORE GST.
     * 3. Late Return is a separate taxable item (5% GST).
     * 4. Vehicle Service Fee percentage from DB (vehicle->commission_percent).
     * 5. Vehicle Service Fee applied separately on Booking and Late Return.
     * 6. Vehicle Service Fee GST is ALWAYS 18%.
     * 7. Convenience Fee is fixed: Base = 83.90, GST @18% = 15.10, Total = 99.00
     * 8. All calculations use FULL precision internally.
     * 9. ROUND to 2 decimals ONLY at display time.
     * 10. Sum of displayed line items MUST match Grand Total exactly.
     * 
     * @param Request $request
     * @param int $bookingId
     * @return \Illuminate\Http\Response
     */
    public function bookingInvoiceData(Request $request, $bookingId)
    {
        // Load booking with relationships
        $data = RentalBooking::with([
            'vehicle.model.manufacturer', 
            'vehicle.model.category', 
            'vehicle.properties', 
            'vehicle.features', 
            'vehicle.images', 
            'customer'
        ])->where('booking_id', $bookingId)->first();

        if (!$data) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        // Get company details
        $companyDetails = CompanyDetail::select(
            'id', 'address', 'phone', 'alt_phone', 'email', 
            'gst_no', 'pan_no', 'bank_name', 'bank_account_no', 'bank_ifsc_code'
        )->first();

        // Determine GST type (CGST/SGST vs IGST)
        $gstStatus = $this->determineGstStatus($data);

        // Get vehicle service percentage from DB
        $vehicleServicePercent = $data->vehicle->commission_percent ?? 0;

        // Get booking GST rate based on customer type
        $bookingGstRate = $this->getBookingGstRate($data);

        // Calculate all invoice data using reverse calculation from Grand Total
        $invoiceData = $this->calculateInvoiceFromGrandTotal($bookingId, $vehicleServicePercent, $bookingGstRate, $data);

        // Extract calculated values
        $newBooking = $invoiceData['newBooking'];
        $newBookingVehicleServiceFees = $invoiceData['newBookingVehicleServiceFees'];
        $cFees = $invoiceData['cFees'];
        $extension = $invoiceData['extension'];
        $extensionVehicleServiceFees = $invoiceData['extensionVehicleServiceFees'];
        $completion = $invoiceData['completion'];
        $completionVehicleServiceFees = $invoiceData['completionVehicleServiceFees'];
        $paidPenalties = $invoiceData['paidPenalties'];
        $paidPenaltyServiceCharge = $invoiceData['paidPenaltyServiceCharge'];
        $duePenalties = $invoiceData['duePenalties'];
        $duePenaltyServiceCharge = $invoiceData['duePenaltyServiceCharge'];
        $groupedTotals = $invoiceData['groupedTotals'];
        $totalAmt = $invoiceData['totalAmt'];
        $amountDue = $invoiceData['amountDue'];
        $rateTotal = $invoiceData['rateTotal'];
        $totalTax = $invoiceData['totalTax'];
        $convenienceFees = $invoiceData['convenienceFees'] ?? 0;
        $newBookingTimeStamp = $invoiceData['newBookingTimeStamp'] ?? '';
        $completionNewBooking = $invoiceData['completionNewBooking'] ?? '';
        $penaltyText = $invoiceData['penaltyText'] ?? '';
        $completionDisplay = $invoiceData['completionDisplay'] ?? 0;
        $extraKmString = $invoiceData['extraKmString'] ?? '';
        $adminPenaltiesDue = [];

        // Log final calculation summary
        Log::info("=== INVOICE CALCULATION - Booking ID: {$bookingId} ===", [
            'totalAmt' => $totalAmt,
            'amountDue' => $amountDue,
            'rateTotal' => $rateTotal,
            'totalTax' => $totalTax,
            'vehicleServicePercent' => $vehicleServicePercent,
            'gstStatus' => $gstStatus,
            'groupedTotals' => $groupedTotals,
        ]);

        // Generate PDF
        $filename = 'booking-invoice-' . $bookingId . '.pdf';
        $pdf = PDF::loadView('booking-invoice', compact(
            'data', 'companyDetails', 'newBooking', 'extension', 'completion',
            'totalAmt', 'totalTax', 'convenienceFees', 'cFees', 'rateTotal',
            'penaltyText', 'gstStatus', 'completionDisplay', 'extraKmString',
            'newBookingTimeStamp', 'completionNewBooking', 'adminPenaltiesDue',
            'newBookingVehicleServiceFees', 'extensionVehicleServiceFees',
            'completionVehicleServiceFees', 'paidPenalties', 'paidPenaltyServiceCharge',
            'duePenalties', 'duePenaltyServiceCharge', 'amountDue', 'groupedTotals'
        ))->setPaper('A3');

        return $pdf->stream('booking-invoice.pdf');
    }

    /**
     * Determine GST status (CGST/SGST vs IGST)
     * 
     * @param RentalBooking $booking
     * @return int 1 = CGST/SGST, 2 = IGST
     */
    private function determineGstStatus(RentalBooking $booking): int
    {
        if ($booking && $booking->customer && $booking->customer->gst_number != null) {
            // Gujarat GST numbers start with 24
            if (!str_starts_with($booking->customer->gst_number, '24')) {
                return 2; // IGST for out-of-state
            }
        }
        return 1; // CGST/SGST for intrastate or no GST
    }

    /**
     * Get booking GST rate based on customer type
     * 
     * @param RentalBooking $booking
     * @return float GST rate as decimal (0.05 or 0.18)
     */
    private function getBookingGstRate(RentalBooking $booking): float
    {
        if ($booking && $booking->customer && !empty($booking->customer->gst_number)) {
            return 0.18; // 18% for B2B (with GST number)
        }
        return 0.05; // 5% for B2C (no GST number)
    }

    /**
     * Calculate all invoice line items using reverse calculation from Grand Total
     * 
     * CRITICAL: Grand Total is the OVERALL sum of ALL paid transactions.
     * We use a 2-PHASE approach:
     * 
     * PHASE 1: Pre-collect totals from all transactions
     * PHASE 2: Reverse calculate booking base from remaining amount
     * PHASE 3: Build line items using frozen booking base
     * 
     * @param int $bookingId
     * @param float $vehicleServicePercent
     * @param float $bookingGstRate
     * @param RentalBooking $booking
     * @return array
     */
    private function calculateInvoiceFromGrandTotal(
        int $bookingId, 
        float $vehicleServicePercent, 
        float $bookingGstRate,
        RentalBooking $booking
    ): array {
        // Initialize result arrays
        $result = $this->initializeInvoiceResult();
        
        // Get all transactions
        $transactions = BookingTransaction::where('booking_id', $bookingId)->get();
        
        if ($transactions->isEmpty()) {
            return $result;
        }

        // GST rates (constants)
        $vehicleServiceGstRate = 0.18;  // Always 18%
        $convenienceFeeGstRate = 0.18;  // Always 18%
        $lateReturnGstRate = 0.05;      // Always 5%

        // ========================================================================
        // PHASE 1: PRE-COLLECT TOTALS
        // ========================================================================
        // Loop through ALL transactions ONCE to collect:
        // - Grand Total (sum of all paid transactions)
        // - Completion charges (to subtract before solving B)
        // - Convenience fees (to subtract before solving B)
        // - Transaction data for later processing
        // ========================================================================
        
        $grandTotal = 0.0;
        $convenienceFeeTotal = 0.0;
        $completionTotal = 0.0;
        $completionVehicleServiceTotal = 0.0;
        $discount = 0.0;
        
        // Store transaction references for Phase 3
        $newBookingTransaction = null;
        $extensionTransactions = [];
        $completionTransaction = null;
        $penaltyTransactions = [];
        
        foreach ($transactions as $transaction) {
            switch ($transaction->type) {
                case 'new_booking':
                    if ($transaction->paid == 1) {
                        // Add to Grand Total
                        $grandTotal += $transaction->total_amount ?? 0;
                        // Collect convenience fee
                        $convenienceFeeTotal += $transaction->convenience_fee ?? 0;
                        // Collect discount (only from new_booking)
                        $discount = $transaction->coupon_discount ?? 0;
                        // Store for Phase 3
                        $newBookingTransaction = $transaction;
                    }
                    break;
                    
                case 'extension':
                    if ($transaction->paid == 1) {
                        // Add to Grand Total
                        $grandTotal += $transaction->total_amount ?? 0;
                        // Collect convenience fee
                        $convenienceFeeTotal += $transaction->convenience_fee ?? 0;
                        // Store for Phase 3
                        $extensionTransactions[] = $transaction;
                    }
                    break;
                    
                case 'completion':
                    if ($transaction->paid == 1) {
                        // Add to Grand Total (use amount_to_pay, NOT total_amount which is NULL)
                        // DO NOT add vehicle service - it's calculated, not paid separately
                        $grandTotal += $transaction->amount_to_pay ?? 0;
                        
                        // REVERSE CALCULATE completion charges from late_return base
                        // DO NOT use DB values for vehicle service - they may be incorrect
                        $lateReturnBase = ($transaction->late_return ?? 0) 
                                        + ($transaction->exceeded_km_limit ?? 0) 
                                        + ($transaction->additional_charges ?? 0);
                        
                        // Completion total = base × (1 + GST rate)
                        $completionTotal = $lateReturnBase * (1 + $lateReturnGstRate);
                        
                        // Completion VS = base × (VS% / 100) × (1 + VS GST rate)
                        $completionVehicleServiceTotal = $lateReturnBase 
                                                       * ($vehicleServicePercent / 100) 
                                                       * (1 + $vehicleServiceGstRate);
                        
                        // Store for Phase 3
                        $completionTransaction = $transaction;
                    }
                    break;
                    
                case 'penalty':
                    // Penalty totals are handled separately in processPenaltyTransaction
                    $penaltyTransactions[] = $transaction;
                    // Add paid penalties to Grand Total
                    if ($transaction->paid == 1) {
                        $penaltyGrandTotal = ($transaction->total_amount ?? 0) + ($transaction->tax_amt ?? 0);
                        $grandTotal += $penaltyGrandTotal;
                    }
                    break;
            }
        }

        // Store the immutable Grand Total
        $result['totalAmt'] = $grandTotal;

        Log::info("PHASE 1 - Pre-collected totals:", [
            'grandTotal' => $grandTotal,
            'convenienceFeeTotal' => $convenienceFeeTotal,
            'completionTotal' => $completionTotal,
            'completionVehicleServiceTotal' => $completionVehicleServiceTotal,
            'discount' => $discount,
        ]);

        // ========================================================================
        // PHASE 2: REVERSE CALCULATION FOR BOOKING BASE
        // ========================================================================
        // Calculate remaining amount after subtracting known fixed components:
        // remaining = Grand Total - convenience - completion - completion VS
        // Then solve for Booking Base (B) using the equation:
        // (B - discount) × (1 + bookingGstRate) + (B × vsPercent/100) × (1 + vsGstRate) = remaining
        // ========================================================================
        
        // Calculate remaining for booking reverse calculation
        $remaining = $grandTotal - $convenienceFeeTotal - $completionTotal - $completionVehicleServiceTotal;
        
        // Solve for Booking Base (B)
        // Equation: (B - discount) × 1.05 + (B × vsPercent/100) × 1.18 = remaining
        // Rearranged: B × [1.05 + (vsPercent/100 × 1.18)] = remaining + discount × 1.05
        $bookingMultiplier = 1 + $bookingGstRate;
        $vsMultiplier = ($vehicleServicePercent / 100) * (1 + $vehicleServiceGstRate);
        $totalMultiplier = $bookingMultiplier + $vsMultiplier;
        
        // CRITICAL: This is the FINAL booking base. Do NOT modify after this point.
        $bookingBase = ($remaining + ($discount * $bookingMultiplier)) / $totalMultiplier;
        
        Log::info("PHASE 2 - Reverse calculation:", [
            'remaining' => $remaining,
            'bookingMultiplier' => $bookingMultiplier,
            'vsMultiplier' => $vsMultiplier,
            'totalMultiplier' => $totalMultiplier,
            'solved_booking_base_B' => $bookingBase,
        ]);

        // ========================================================================
        // PHASE 3: BUILD LINE ITEMS
        // ========================================================================
        // Use the frozen booking base (B) to calculate all line item values.
        // Process each transaction type to build display arrays.
        // ========================================================================
        
        // Process new_booking (if exists)
        if ($newBookingTransaction) {
            $this->buildNewBookingLineItems(
                $newBookingTransaction,
                $bookingBase,          // FROZEN - solved from Phase 2
                $discount,
                $vehicleServicePercent,
                $bookingGstRate,
                $vehicleServiceGstRate,
                $convenienceFeeGstRate,
                $result
            );
        }
        
        // Process extensions
        foreach ($extensionTransactions as $extTransaction) {
            $this->buildExtensionLineItems(
                $extTransaction,
                $vehicleServicePercent,
                $bookingGstRate,
                $vehicleServiceGstRate,
                $convenienceFeeGstRate,
                $result
            );
        }
        
        // Process completion (if exists)
        if ($completionTransaction) {
            $this->buildCompletionLineItems(
                $completionTransaction,
                $vehicleServicePercent,
                $lateReturnGstRate,
                $vehicleServiceGstRate,
                $result,
                $booking
            );
        }
        
        // Process penalties
        foreach ($penaltyTransactions as $penaltyTransaction) {
            $this->processPenaltyTransaction(
                $penaltyTransaction,
                $vehicleServicePercent,
                $bookingGstRate,
                $vehicleServiceGstRate,
                $result
            );
        }

        // Round final totals for display (EXCEPT totalAmt - it's from Phase 1 and is IMMUTABLE)
        $result['amountDue'] = round($result['amountDue'], 2);
        $result['rateTotal'] = round($result['rateTotal'], 2);
        $result['totalTax'] = round($result['totalTax'], 2);

        // Round grouped totals
        foreach ($result['groupedTotals'] as $key => $totals) {
            $result['groupedTotals'][$key]['rate'] = round($totals['rate'], 2);
            $result['groupedTotals'][$key]['tax'] = round($totals['tax'], 2);
            $result['groupedTotals'][$key]['vehicle_commission_rate'] = round($totals['vehicle_commission_rate'], 2);
            $result['groupedTotals'][$key]['vehicle_commission_tax'] = round($totals['vehicle_commission_tax'], 2);
        }

        return $result;
    }

    /**
     * Build new booking line items using FROZEN booking base from Phase 2
     * 
     * CRITICAL: $bookingBase is already solved and MUST NOT be modified.
     */
    private function buildNewBookingLineItems(
        BookingTransaction $transaction,
        float $bookingBase,           // FROZEN from Phase 2 reverse calculation
        float $discount,
        float $vehicleServicePercent,
        float $bookingGstRate,
        float $vehicleServiceGstRate,
        float $convenienceFeeGstRate,
        array &$result
    ): void {
        $convenienceFee = $transaction->convenience_fee ?? 0;
        
        // Build timestamp
        $result['newBookingTimeStamp'] = '';
        if ($transaction->start_date && $transaction->end_date) {
            $result['newBookingTimeStamp'] = date('d-m-Y H:i', strtotime($transaction->start_date)) 
                . ' - ' . date('d-m-Y H:i', strtotime($transaction->end_date));
        }

        // Convenience Fee (fixed values)
        if ($convenienceFee > 0) {
            $cfBase = $convenienceFee / (1 + $convenienceFeeGstRate);
            $cfGst = $convenienceFee - $cfBase;
            
            $result['cFees'] = [
                'trip_amount' => number_format($cfBase, 2),
                'tax_percent' => number_format($convenienceFeeGstRate * 100, 2),
                'tax_amount' => number_format($cfGst, 2),
                'coupon_discount' => number_format(0, 2),
                'total_amount' => number_format($convenienceFee, 2),
            ];
            
            $result['convenienceFees'] = $convenienceFee;
            $result['rateTotal'] += $cfBase;
            $result['totalTax'] += $cfGst;
            
            $result['groupedTotals'][18]['rate'] += $cfBase;
            $result['groupedTotals'][18]['tax'] += $cfGst;
        }

        // BOOKING LINE ITEM - using FROZEN booking base (B)
        // bookingBase is already solved in Phase 2 - do NOT recalculate
        $bookingRate = $bookingBase;  // IMMUTABLE
        $bookingTaxable = $bookingRate - $discount;
        $bookingGst = $bookingTaxable * $bookingGstRate;
        $bookingTotal = $bookingTaxable + $bookingGst;
        
        $gstPercentInt = (int)($bookingGstRate * 100);
        
        $result['newBooking'] = [
            'trip_amount' => number_format($bookingRate, 2),      // B = FROZEN from Phase 2
            'tax_percent' => number_format($gstPercentInt, 2),
            'tax_amount' => number_format($bookingGst, 2),
            'coupon_discount' => number_format($discount, 2),
            'total_amount' => number_format($bookingTotal, 2),
        ];
        
        $result['rateTotal'] += $bookingTaxable;
        $result['totalTax'] += $bookingGst;
        
        $result['groupedTotals'][$gstPercentInt]['rate'] += $bookingTaxable;
        $result['groupedTotals'][$gstPercentInt]['tax'] += $bookingGst;

        // Vehicle Service Fee on Booking (calculated from frozen B)
        if ($vehicleServicePercent > 0) {
            $vsBase = $bookingRate * ($vehicleServicePercent / 100);
            $vsGst = $vsBase * $vehicleServiceGstRate;
            $vsTotal = $vsBase + $vsGst;
            
            $result['newBookingVehicleServiceFees'] = [
                'trip_amount' => number_format($vsBase, 2),
                'tax_percent' => number_format($vehicleServiceGstRate * 100, 2),
                'tax_amount' => number_format($vsGst, 2),
                'coupon_discount' => number_format(0, 2),
                'total_amount' => number_format($vsTotal, 2),
            ];
            
            $result['rateTotal'] += $vsBase;
            $result['totalTax'] += $vsGst;
            
            $result['groupedTotals'][18]['vehicle_commission_rate'] += $vsBase;
            $result['groupedTotals'][18]['vehicle_commission_tax'] += $vsGst;
        }

        Log::info("PHASE 3 - New Booking line items:", [
            'frozen_booking_base_B' => $bookingRate,
            'booking_taxable' => $bookingTaxable,
            'booking_gst' => $bookingGst,
            'booking_total' => $bookingTotal,
        ]);
    }

    /**
     * Build extension line items
     * Extensions use their own reverse calculation (similar to new_booking)
     */
    private function buildExtensionLineItems(
        BookingTransaction $transaction,
        float $vehicleServicePercent,
        float $bookingGstRate,
        float $vehicleServiceGstRate,
        float $convenienceFeeGstRate,
        array &$result
    ): void {
        $grandTotal = $transaction->total_amount ?? 0;
        $convenienceFee = $transaction->convenience_fee ?? 0;
        $discount = $transaction->coupon_discount ?? 0;
        $timestamp = $transaction->end_date ? date('d-m-Y H:i', strtotime($transaction->end_date)) : '';

        // Remove convenience fee to get remaining
        $remaining = $grandTotal - $convenienceFee;

        // Solve for Extension Base (same equation as booking)
        $bookingMultiplier = 1 + $bookingGstRate;
        $vsMultiplier = ($vehicleServicePercent / 100) * (1 + $vehicleServiceGstRate);
        $totalMultiplier = $bookingMultiplier + $vsMultiplier;
        
        $extensionBase = ($remaining + ($discount * $bookingMultiplier)) / $totalMultiplier;
        $extensionRate = $extensionBase;  // IMMUTABLE
        
        $extensionTaxable = $extensionRate - $discount;
        $extensionGst = $extensionTaxable * $bookingGstRate;
        $extensionTotal = $extensionTaxable + $extensionGst;
        
        $gstPercentInt = (int)($bookingGstRate * 100);

        $result['extension']['timestamp'][] = $timestamp;
        $result['extension']['trip_amount'][] = number_format($extensionRate, 2);
        $result['extension']['tax_percent'][] = number_format($gstPercentInt, 2);
        $result['extension']['tax_amount'][] = number_format($extensionGst, 2);
        $result['extension']['coupon_discount'][] = number_format($discount, 2);
        $result['extension']['total_amount'][] = number_format($extensionTotal, 2);

        $result['rateTotal'] += $extensionTaxable;
        $result['totalTax'] += $extensionGst;
        
        $result['groupedTotals'][$gstPercentInt]['rate'] += $extensionTaxable;
        $result['groupedTotals'][$gstPercentInt]['tax'] += $extensionGst;

        // Vehicle Service Fee on Extension
        if ($vehicleServicePercent > 0) {
            $vsBase = $extensionRate * ($vehicleServicePercent / 100);
            $vsGst = $vsBase * $vehicleServiceGstRate;
            $vsTotal = $vsBase + $vsGst;
            
            $result['extensionVehicleServiceFees']['trip_amount'][] = number_format($vsBase, 2);
            $result['extensionVehicleServiceFees']['tax_percent'][] = number_format($vehicleServiceGstRate * 100, 2);
            $result['extensionVehicleServiceFees']['tax_amount'][] = number_format($vsGst, 2);
            $result['extensionVehicleServiceFees']['coupon_discount'][] = number_format(0, 2);
            $result['extensionVehicleServiceFees']['total_amount'][] = number_format($vsTotal, 2);
            
            $result['rateTotal'] += $vsBase;
            $result['totalTax'] += $vsGst;
            
            $result['groupedTotals'][18]['vehicle_commission_rate'] += $vsBase;
            $result['groupedTotals'][18]['vehicle_commission_tax'] += $vsGst;
        }

        // Convenience fee for extension
        if ($convenienceFee > 0) {
            $cfBase = $convenienceFee / (1 + $convenienceFeeGstRate);
            $cfGst = $convenienceFee - $cfBase;
            
            $result['convenienceFees'] += $convenienceFee;
            $result['rateTotal'] += $cfBase;
            $result['totalTax'] += $cfGst;
            
            $result['groupedTotals'][18]['rate'] += $cfBase;
            $result['groupedTotals'][18]['tax'] += $cfGst;
        }
    }

    /**
     * Build completion line items (late return, exceeded km, additional charges)
     * Uses DIRECT values from DB - no reverse calculation needed
     */
    private function buildCompletionLineItems(
        BookingTransaction $transaction,
        float $vehicleServicePercent,
        float $lateReturnGstRate,
        float $vehicleServiceGstRate,
        array &$result,
        RentalBooking $booking
    ): void {
        $lateReturn = $transaction->late_return ?? 0;
        $exceededKm = $transaction->exceeded_km_limit ?? 0;
        $additionalCharges = $transaction->additional_charges ?? 0;
        $timestamp = $transaction->timestamp ? date('d-m-Y H:i', strtotime($transaction->timestamp)) : '';

        $result['completionNewBooking'] = $timestamp;

        // Build penalty text
        $penaltyText = '';
        if ($lateReturn > 0) {
            $penaltyText .= ' Late Return - ' . round($lateReturn, 2);
        }
        if ($exceededKm > 0) {
            if (!empty($penaltyText)) $penaltyText .= ' | ';
            $extraKmString = '';
            if ($booking && is_countable($booking->price_summary) && count($booking->price_summary) > 0) {
                foreach ($booking->price_summary as $val) {
                    if (str_starts_with(strtolower($val['key'] ?? ''), 'extra')) {
                        $extraKmString = $val['key'];
                        break;
                    }
                }
            }
            $result['extraKmString'] = $extraKmString;
            $penaltyText .= $extraKmString ?: 'Extra KM - ' . round($exceededKm, 2);
        }
        if ($additionalCharges > 0) {
            if (!empty($penaltyText)) $penaltyText .= ' | ';
            $penaltyText .= 'Additional Charges - ' . $additionalCharges;
        }
        $result['penaltyText'] = $penaltyText;

        // Total completion amount (base from DB)
        $totalCompletion = $lateReturn + $exceededKm + $additionalCharges;
        
        if ($totalCompletion <= 0) {
            return;
        }

        // Special case for booking 1805
        if ($booking->booking_id == 1805) {
            $result['amountDue'] += 227617.59;
            return;
        }

        // Late Return base is DIRECT from DB - no reverse calculation
        $completionBase = $totalCompletion;
        $completionGst = $completionBase * $lateReturnGstRate;
        $completionTotal = $completionBase + $completionGst;

        $gstPercentInt = (int)($lateReturnGstRate * 100);

        $result['completion'] = [
            'additional_charge' => number_format($completionBase, 2),
            'tax_percent' => number_format($gstPercentInt, 2),
            'tax_amount' => number_format($completionGst, 2),
            'coupon_discount' => number_format(0, 2),
            'total_amount' => number_format($completionTotal, 2),
        ];

        $result['completionDisplay'] = 1;
        $result['rateTotal'] += $completionBase;
        $result['totalTax'] += $completionGst;
        
        $result['groupedTotals'][$gstPercentInt]['rate'] += $completionBase;
        $result['groupedTotals'][$gstPercentInt]['tax'] += $completionGst;

        // Vehicle Service Fee on Completion
        if ($vehicleServicePercent > 0) {
            $vsBase = $completionBase * ($vehicleServicePercent / 100);
            $vsGst = $vsBase * $vehicleServiceGstRate;
            $vsTotal = $vsBase + $vsGst;
            
            $result['completionVehicleServiceFees'] = [
                'trip_amount' => number_format($vsBase, 2),
                'tax_percent' => number_format($vehicleServiceGstRate * 100, 2),
                'tax_amount' => number_format($vsGst, 2),
                'coupon_discount' => number_format(0, 2),
                'total_amount' => number_format($vsTotal, 2),
            ];
            
            $result['rateTotal'] += $vsBase;
            $result['totalTax'] += $vsGst;
            
            $result['groupedTotals'][18]['vehicle_commission_rate'] += $vsBase;
            $result['groupedTotals'][18]['vehicle_commission_tax'] += $vsGst;
        }
    }

    /**
     * Initialize invoice result structure
     */
    private function initializeInvoiceResult(): array
    {
        return [
            'newBooking' => [],
            'newBookingVehicleServiceFees' => [],
            'cFees' => [],
            'extension' => [],
            'extensionVehicleServiceFees' => [],
            'completion' => [],
            'completionVehicleServiceFees' => [],
            'paidPenalties' => [],
            'paidPenaltyServiceCharge' => [],
            'duePenalties' => [],
            'duePenaltyServiceCharge' => [],
            'groupedTotals' => [
                5 => ['rate' => 0, 'tax' => 0, 'vehicle_commission_rate' => 0, 'vehicle_commission_tax' => 0],
                18 => ['rate' => 0, 'tax' => 0, 'vehicle_commission_rate' => 0, 'vehicle_commission_tax' => 0],
            ],
            'totalAmt' => 0.0,
            'amountDue' => 0.0,
            'rateTotal' => 0.0,
            'totalTax' => 0.0,
            'convenienceFees' => 0.0,
            'newBookingTimeStamp' => '',
            'completionNewBooking' => '',
            'penaltyText' => '',
            'completionDisplay' => 0,
            'extraKmString' => '',
        ];
    }

    /**
     * Process penalty transaction
     */
    private function processPenaltyTransaction(
        BookingTransaction $transaction,
        float $vehicleServicePercent,
        float $bookingGstRate,
        float $vehicleServiceGstRate,
        array &$result
    ): void {
        $totalAmount = $transaction->total_amount ?? 0;
        $taxAmt = $transaction->tax_amt ?? 0;
        $timestamp = $transaction->timestamp ? date('d-m-Y H:i', strtotime($transaction->timestamp)) : '';
        $isPaid = $transaction->paid == 1;
        $finalAmount = $transaction->final_amount ?? 0;

        if ($totalAmount <= 0) {
            return;
        }

        // Skip unpaid penalties with no final amount
        if ($isPaid && $finalAmount <= 0) {
            return;
        }

        // Reverse calculate penalty base
        $penaltyGrandTotal = $totalAmount + $taxAmt;
        
        $baseMultiplier = 1 + $bookingGstRate;
        $vsMultiplier = ($vehicleServicePercent / 100) * (1 + $vehicleServiceGstRate);
        $totalMultiplier = $baseMultiplier + $vsMultiplier;
        
        $penaltyBase = $penaltyGrandTotal / $totalMultiplier;
        $penaltyGst = $penaltyBase * $bookingGstRate;
        $penaltyTotal = $penaltyBase + $penaltyGst;
        
        $gstPercentInt = (int)($bookingGstRate * 100);

        // Vehicle service
        $vsBase = 0;
        $vsGst = 0;
        $vsTotal = 0;
        if ($vehicleServicePercent > 0) {
            $vsBase = $penaltyBase * ($vehicleServicePercent / 100);
            $vsGst = $vsBase * $vehicleServiceGstRate;
            $vsTotal = $vsBase + $vsGst;
        }

        if ($isPaid) {
            // Paid penalty
            $result['paidPenalties']['timestamp'][] = $timestamp;
            $result['paidPenalties']['trip_amount'][] = number_format($penaltyBase, 2);
            $result['paidPenalties']['tax_percent'][] = number_format($gstPercentInt, 2);
            $result['paidPenalties']['tax_amount'][] = number_format($penaltyGst, 2);
            $result['paidPenalties']['coupon_discount'][] = number_format(0, 2);
            $result['paidPenalties']['total_amount'][] = number_format($penaltyTotal, 2);

            if ($vehicleServicePercent > 0) {
                $result['paidPenaltyServiceCharge']['trip_amount'][] = number_format($vsBase, 2);
                $result['paidPenaltyServiceCharge']['tax_percent'][] = number_format($vehicleServiceGstRate * 100, 2);
                $result['paidPenaltyServiceCharge']['tax_amount'][] = number_format($vsGst, 2);
                $result['paidPenaltyServiceCharge']['coupon_discount'][] = number_format(0, 2);
                $result['paidPenaltyServiceCharge']['total_amount'][] = number_format($vsTotal, 2);
            }

            // CRITICAL: Add Grand Total from DB - this is IMMUTABLE and the SINGLE source of truth
            // $penaltyGrandTotal = $totalAmount + $taxAmt (both from DB)
            // DO NOT add calculated values ($penaltyTotal + $vsTotal)
            $result['totalAmt'] += $penaltyGrandTotal;
            
            // Track GST summary
            $result['rateTotal'] += $penaltyBase + $vsBase;
            $result['totalTax'] += $penaltyGst + $vsGst;
            
            $result['groupedTotals'][$gstPercentInt]['rate'] += $penaltyBase;
            $result['groupedTotals'][$gstPercentInt]['tax'] += $penaltyGst;
            
            if ($vsBase > 0) {
                $result['groupedTotals'][18]['vehicle_commission_rate'] += $vsBase;
                $result['groupedTotals'][18]['vehicle_commission_tax'] += $vsGst;
            }
        } else {
            // Due penalty
            $result['duePenalties']['timestamp'][] = $timestamp;
            $result['duePenalties']['trip_amount'][] = number_format($penaltyBase, 2);
            $result['duePenalties']['tax_percent'][] = number_format($gstPercentInt, 2);
            $result['duePenalties']['tax_amount'][] = number_format($penaltyGst, 2);
            $result['duePenalties']['coupon_discount'][] = number_format(0, 2);
            $result['duePenalties']['total_amount'][] = number_format($penaltyTotal, 2);

            if ($vehicleServicePercent > 0) {
                $result['duePenaltyServiceCharge']['trip_amount'][] = number_format($vsBase, 2);
                $result['duePenaltyServiceCharge']['tax_percent'][] = number_format($vehicleServiceGstRate * 100, 2);
                $result['duePenaltyServiceCharge']['tax_amount'][] = number_format($vsGst, 2);
                $result['duePenaltyServiceCharge']['coupon_discount'][] = number_format(0, 2);
                $result['duePenaltyServiceCharge']['total_amount'][] = number_format($vsTotal, 2);
            }

            $result['amountDue'] += $penaltyTotal + $vsTotal;
        }
    }
    
    public function bookingInvoiceDataBkpFuction(Request $request, $bookingId)
    {
        $extraKmString = '';
        $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images', 'customer'])->where('booking_id', $bookingId)->first();
        $companyDetails = CompanyDetail::select('id', 'address', 'phone', 'alt_phone', 'email', 'gst_no', 'pan_no', 'bank_name', 'bank_account_no', 'bank_ifsc_code')->first();
        $newBooking = $extension = $completion = $cFees = $adminPenaltiesDue = $newBookingVehicleServiceFees = $extensionVehicleServiceFees = $paidPenalties = $paidPenaltyServiceCharge = $duePenalties = $duePenaltyServiceCharge = $completionVehicleServiceFees = [];
        $totalAmt = $totalTax = $convenienceFees = $rateTotal = $completionDisplay = $amountDue = 0;
        $gstStatus = 1; // 1 = Consider CGST/SGST
        if($data && $data->customer && $data->customer->gst_number != null){
            if(str_starts_with($data->customer->gst_number, 24) == ''){
                $gstStatus = 2; // 2 = Consider IGST
            }
        }
        $newBookingTimeStamp = $completionNewBooking = $penaltyText = '';
        $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
        $gstPercent = $data->tax_rate ?? 0;
        
        // Initialize grouped totals by GST percentage
        $groupedTotals = [
            5 => ['rate' => 0, 'tax' => 0, 'vehicle_commission_rate' => 0, 'vehicle_commission_tax' => 0, 'discount' => 0],
            18 => ['rate' => 0, 'tax' => 0, 'vehicle_commission_rate' => 0, 'vehicle_commission_tax' => 0, 'discount' => 0],
        ];
        if(is_countable($calculationDetails) && count($calculationDetails) > 0){
            foreach ($calculationDetails as $key => $value) {
                $commissionTaxAmount = $value->vehicle_commission_tax_amt ?? 0;
                if($value->type == 'new_booking' && $value->paid == 1){
                    //$newBookingTimeStamp = $value->timestamp;
                    $newBookingTimeStamp = date('d-m-Y H:i', strtotime($value->start_date)).' - '.date('d-m-Y H:i', strtotime($value->end_date));
                    
                    // Calculate displayed rate: trip_amount - vehicle_commission_amount (before discount for display)
                    // This is the base rate shown in the Rate column
                    $baseRate = $value->trip_amount - ($value->vehicle_commission_amount ?? 0);
                    
                    // Determine tax percentage
                    $taxPercent = 0;
                    $mainAmt = $value->trip_amount;
                    if(isset($value->coupon_discount) && $value->coupon_discount != 0){
                        $mainAmt = $value->trip_amount - $value->coupon_discount;
                    }
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);
                    
                    // Fallback: If tax percentage couldn't be determined, use booking's tax rate
                    if($taxPercent == 0 && $gstPercent > 0){
                        $taxPercent = $gstPercent == 0.05 ? 5 : ($gstPercent == 0.18 ? 18 : 18);
                    }
                    // Final fallback: default to 5% if still 0
                    if($taxPercent == 0){
                        $taxPercent = 5;
                    }

                    // Calculate net rate after discount (for tax calculation)
                    $netRateAfterDiscount = $baseRate - ($value->coupon_discount ?? 0);
                    
                    // Calculate tax on net rate (after discount)
                    $taxRateDecimal = $taxPercent / 100; // Convert percentage to decimal (5% = 0.05)
                    $correctTaxAmount = $netRateAfterDiscount * $taxRateDecimal;
                    
                    // Set display values
                    $newBooking['trip_amount'] = number_format($baseRate, 2); // Rate column (before discount)
                    $newBooking['tax_percent'] = number_format($taxPercent, 2);
                    $newBooking['tax_amount'] = number_format($correctTaxAmount, 2);
                    $newBooking['coupon_discount'] = number_format($value->coupon_discount, 2);
                    // Total amount = net rate + tax
                    $newBooking['total_amount'] = number_format($netRateAfterDiscount + $correctTaxAmount, 2);

                    // Don't add to totalAmt here - will be calculated from grouped totals at the end
                    $convenienceFees += $value->convenience_fee;
                    $rateTotal += $value->trip_amount;
                    $rateTotal -= $value->coupon_discount;
                    
                    // Group totals by GST percentage
                    $gstKey = (int)$taxPercent;
                    if(isset($groupedTotals[$gstKey])){
                        // For grouped totals, use net rate after discount (matches tax calculation)
                        $netRateForTotals = $baseRate - ($value->coupon_discount ?? 0);
                        $groupedTotals[$gstKey]['rate'] += $netRateForTotals;
                        $groupedTotals[$gstKey]['tax'] += $correctTaxAmount; // Use the same tax we calculated above
                        
                        // Track discount separately by GST percentage
                        $couponDiscount = (float)($value->coupon_discount ?? 0);
                        if($couponDiscount > 0){
                            $groupedTotals[$gstKey]['discount'] += $couponDiscount;
                        }
                    }
                    
                    $newBookingVehicleServiceFees['trip_amount'] = number_format($value->vehicle_commission_amount, 2);
                    $newBookingVehicleServiceFees['tax_percent'] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $newBookingVehicleServiceFees['tax_amount'] = number_format($value->vehicle_commission_tax_amt, 2);
                    $newBookingVehicleServiceFees['coupon_discount'] = number_format(0, 2);
                    $newBookingVehicleServiceFees['total_amount'] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
                    
                    // Vehicle service fees are always 18% GST
                    if($value->vehicle_commission_amount > 0 || $value->vehicle_commission_tax_amt > 0){
                        $groupedTotals[18]['vehicle_commission_rate'] += $value->vehicle_commission_amount;
                        $groupedTotals[18]['vehicle_commission_tax'] += $value->vehicle_commission_tax_amt;
                    }
                }elseif($value->type == 'extension' && $value->paid == 1){
                    $extension['timestamp'][] = date('d-m-Y H:i', strtotime($value->end_date));
                    
                    // Calculate displayed rate: trip_amount - vehicle_commission_amount (before discount for display)
                    $baseRate = $value->trip_amount - ($value->vehicle_commission_amount ?? 0);
                    
                    // Determine tax percentage
                    $taxPercent = 0;
                    $mainAmt = $value->trip_amount;
                    if(isset($value->coupon_discount) && $value->coupon_discount != 0){
                        $mainAmt = $value->trip_amount - $value->coupon_discount;
                    }
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);
                    
                    // Fallback: If tax percentage couldn't be determined, use booking's tax rate
                    if($taxPercent == 0 && $gstPercent > 0){
                        $taxPercent = $gstPercent == 0.05 ? 5 : ($gstPercent == 0.18 ? 18 : 18);
                    }
                    // Final fallback: default to 5% if still 0
                    if($taxPercent == 0){
                        $taxPercent = 5;
                    }

                    // Calculate net rate after discount (for tax calculation)
                    $netRateAfterDiscount = $baseRate - ($value->coupon_discount ?? 0);
                    
                    // Calculate tax on net rate (after discount)
                    $taxRateDecimal = $taxPercent / 100;
                    $correctTaxAmount = $netRateAfterDiscount * $taxRateDecimal;
                    
                    // Set display values
                    $extension['trip_amount'][] = number_format($baseRate, 2); // Rate column (before discount)
                    $extension['tax_percent'][] = number_format($taxPercent, 2);
                    $extension['tax_amount'][] = number_format($correctTaxAmount, 2);
                    $extension['coupon_discount'][] = number_format($value->coupon_discount, 2);
                    // Don't add to totalAmt here - will be calculated from grouped totals at the end
                    $convenienceFees += $value->convenience_fee;
                    $rateTotal += $value->trip_amount;
                    $rateTotal -= $value->coupon_discount;
                    
                    // Group totals by GST percentage
                    $gstKey = (int)$taxPercent;
                    if(isset($groupedTotals[$gstKey])){
                        // For grouped totals, use net rate after discount (matches tax calculation)
                        $netRateForTotals = $baseRate - ($value->coupon_discount ?? 0);
                        $groupedTotals[$gstKey]['rate'] += $netRateForTotals;
                        $groupedTotals[$gstKey]['tax'] += $correctTaxAmount; // Use the same tax we calculated above
                        
                        // Track discount separately by GST percentage
                        $couponDiscount = (float)($value->coupon_discount ?? 0);
                        if($couponDiscount > 0){
                            $groupedTotals[$gstKey]['discount'] += $couponDiscount;
                        }
                    }
                    
                    $extensionVehicleServiceFees['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
                    $extensionVehicleServiceFees['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $extensionVehicleServiceFees['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
                    $extensionVehicleServiceFees['coupon_discount'][] = number_format(0, 2);
                    $extensionVehicleServiceFees['total_amount'][] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
                    // Total amount = net rate + tax
                    $extension['total_amount'][] = number_format($netRateAfterDiscount + $correctTaxAmount, 2);
                    
                    // Vehicle service fees are always 18% GST
                    if($value->vehicle_commission_amount > 0 || $value->vehicle_commission_tax_amt > 0){
                        $groupedTotals[18]['vehicle_commission_rate'] += $value->vehicle_commission_amount;
                        $groupedTotals[18]['vehicle_commission_tax'] += $value->vehicle_commission_tax_amt;
                    }
                }elseif($value->type == 'completion' && $value->paid == 1){
                    $completionNewBooking = date('d-m-Y H:i', strtotime($value->timestamp));
                    $additionalCharges = $totalAmount = 0;
                    $penaltyText = '';

                    if(isset($value->late_return) && $value->late_return != '' && $value->late_return != 0){
                        $additionalCharges += $value->late_return;
                        $penaltyText .= ' Late Return - '. round($value->late_return, 2);
                    }
                    if(isset($value->exceeded_km_limit) && $value->exceeded_km_limit != '' && $value->exceeded_km_limit != 0){
                        $additionalCharges += $value->exceeded_km_limit;
                        if($value->late_return != 0){
                            $penaltyText .=  ' | ';    
                        }
                        if(is_countable($data->price_summary) && count($data->price_summary) > 0){
                            foreach($data->price_summary as $key => $val){
                                if(str_starts_with(strtolower($val['key']), 'extra')){
                                    $extraKmString = $val['key'];
                                }
                            }
                        }
                        $penaltyText .=  $extraKmString;
                    }
                    if(isset($value->additional_charges) && $value->additional_charges != '' && $value->additional_charges != 0){
                        $additionalCharges += $value->additional_charges;
                        if($value->exceeded_km_limit != 0){
                             $penaltyText .=  ' | ';    
                        }
                        $penaltyText .=  'Additional Charges - '. $value->additional_charges;
                    }
                    $completion['additional_charge'] = number_format( (round($additionalCharges, 2) - $value->vehicle_commission_amount), 2);
                    if(isset($value->tax_amt) && $value->tax_amt != '' && $value->tax_amt != 0){
                        $totalAmount += $value->tax_amt;
                    }
                    $taxPercent = 0;
                    $mainAmt = $additionalCharges;
                    if(isset($value->coupon_discount) && $value->coupon_discount != 0){
                        $mainAmt = $value->trip_amount - $value->coupon_discount;
                    }
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);

                    $completion['tax_percent'] = number_format($taxPercent, 2);
                    $completion['tax_amount'] = number_format(($value->tax_amt - $value->vehicle_commission_tax_amt), 2);
                    $completion['coupon_discount'] = number_format(0, 2);
                    // Recalculate completion total based on displayed values
                    $completionDisplayedRate = $additionalCharges - ($value->vehicle_commission_amount ?? 0);
                    $completionTaxAmount = ($value->tax_amt ?? 0) - ($value->vehicle_commission_tax_amt ?? 0);
                    $completion['total_amount'] = number_format($completionDisplayedRate + $completionTaxAmount, 2);
                    
                    if($data->booking_id != 1805){
                        // Don't add to totalAmt here - will be calculated from grouped totals at the end
                        $rateTotal += $additionalCharges;
                        
                        // Group totals by GST percentage
                        $gstKey = (int)$taxPercent;
                        if(isset($groupedTotals[$gstKey])){
                            // Rate should match displayed rate: additionalCharges - vehicle_commission_amount
                            $completionDisplayedRate = $additionalCharges - ($value->vehicle_commission_amount ?? 0);
                            $groupedTotals[$gstKey]['rate'] += $completionDisplayedRate;
                            $groupedTotals[$gstKey]['tax'] += ($value->tax_amt - $value->vehicle_commission_tax_amt);
                        }
                    }else{
                        $amountDue += 227617.59;
                    }

                    if($completion['additional_charge'] != 0 || $completion['total_amount'] != 0){
                        $completionDisplay = 1;
                    }

                    $completionVehicleServiceFees['trip_amount'] = number_format($value->vehicle_commission_amount, 2);
                    $completionVehicleServiceFees['tax_percent'] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $completionVehicleServiceFees['tax_amount'] = number_format($value->vehicle_commission_tax_amt, 2);
                    $completionVehicleServiceFees['coupon_discount'] = number_format(0, 2);
                    $completionVehicleServiceFees['total_amount'] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
                    
                    // Vehicle service fees are always 18% GST
                    if($value->vehicle_commission_amount > 0 || $value->vehicle_commission_tax_amt > 0){
                        $groupedTotals[18]['vehicle_commission_rate'] += $value->vehicle_commission_amount;
                        $groupedTotals[18]['vehicle_commission_tax'] += $value->vehicle_commission_tax_amt;
                    }
                }elseif($value->type == 'penalty' && $value->paid == 1){
                    if($value->final_amount > 0){
                        $paidPenalties['timestamp'][] = date('d-m-Y H:i', strtotime($value->timestamp));
                        $mainAmt = $value->total_amount;
                        if(isset($value->coupon_discount) && $value->coupon_discount != 0){
                            $mainAmt = $value->total_amount - $value->coupon_discount;
                        }   
                        $paidPenalties['trip_amount'][] = number_format($value->total_amount ?? 0, 2);
                        $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                        $penaltyTaxPercent = getTaxPercent($mainAmt, $value->tax_amt, $mainAmt, $vehiclePercent, $gstPercent, $commissionTaxAmount);
                        $paidPenalties['tax_percent'][] = $penaltyTaxPercent;
                        $paidPenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
                        $paidPenalties['coupon_discount'][] = number_format(0, 2);
                        $paidPenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);

                        $paidPenaltyServiceCharge['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
                        $paidPenaltyServiceCharge['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                        $paidPenaltyServiceCharge['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
                        $paidPenaltyServiceCharge['coupon_discount'][] = number_format(0, 2);
                        $paidPenaltyServiceCharge['total_amount'][] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

                        $rateTotal += $value->total_amount;
                        $rateTotal += $value->vehicle_commission_amount;
                        // Don't add to totalAmt here - will be calculated from grouped totals at the end
                        
                        // Group totals by GST percentage
                        $gstKey = (int)$penaltyTaxPercent;
                        if(isset($groupedTotals[$gstKey])){
                            $groupedTotals[$gstKey]['rate'] += $value->total_amount;
                            $groupedTotals[$gstKey]['tax'] += $value->tax_amt;
                            // Track discount separately by GST percentage
                            if(isset($value->coupon_discount) && $value->coupon_discount > 0){
                                $groupedTotals[$gstKey]['discount'] += $value->coupon_discount;
                            }
                        }
                        
                        // Vehicle service fees are always 18% GST
                        if($value->vehicle_commission_amount > 0 || $value->vehicle_commission_tax_amt > 0){
                            $groupedTotals[18]['vehicle_commission_rate'] += $value->vehicle_commission_amount;
                            $groupedTotals[18]['vehicle_commission_tax'] += $value->vehicle_commission_tax_amt;
                        }
                    }
                }elseif($value->type == 'penalty' && $value->paid == 0){
                    $duePenalties['timestamp'][] = date('d-m-Y H:i', strtotime($value->timestamp));
                    $mainAmt = $value->total_amount;
                    if(isset($value->coupon_discount) && $value->coupon_discount != 0){
                        $mainAmt = $value->total_amount - $value->coupon_discount;
                    }   
                    $duePenalties['trip_amount'][] = number_format($value->total_amount ?? 0, 2);
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = ($gstPercent == 0.05) ? 5 : (($gstPercent == 0.18) ? 18 : 0);
                    $duePenalties['tax_percent'][] = $taxPercent;
                    // OLD CODE
                    //$duePenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
                    //$duePenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);
                    // NEW CODE
                    if($value->tax_amt > $value->vehicle_commission_tax_amt){
                        $penaltyTax = $value->tax_amt - $value->vehicle_commission_tax_amt;
                        $duePenalties['tax_amount'][] = number_format($penaltyTax ?? 0, 2);
                        $duePenalties['total_amount'][] = number_format(($value->total_amount + $penaltyTax), 2);
                    }else{
                        $duePenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
                        $duePenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);
                    }
                    $duePenalties['coupon_discount'][] = number_format(0, 2);

                    $duePenaltyServiceCharge['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
                    $duePenaltyServiceCharge['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $duePenaltyServiceCharge['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
                    $duePenaltyServiceCharge['coupon_discount'][] = number_format(0, 2);
                    $duePenaltyServiceCharge['total_amount'][] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

                    $amountDue += ($value->total_amount + $value->tax_amt);
                    //$amountDue += ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt);
                    $amountDue += ($value->vehicle_commission_amount);
                    
                    // Group totals by GST percentage for due penalties
                    $gstKey = (int)$taxPercent;
                    if(isset($groupedTotals[$gstKey])){
                        if($value->tax_amt > $value->vehicle_commission_tax_amt){
                            $penaltyTax = $value->tax_amt - $value->vehicle_commission_tax_amt;
                            $groupedTotals[$gstKey]['rate'] += $value->total_amount;
                            $groupedTotals[$gstKey]['tax'] += $penaltyTax;
                        }else{
                            $groupedTotals[$gstKey]['rate'] += $value->total_amount;
                            $groupedTotals[$gstKey]['tax'] += $value->tax_amt;
                        }
                        // Track discount separately by GST percentage
                        $couponDiscount = (float)($value->coupon_discount ?? 0);
                        if($couponDiscount > 0){
                            $groupedTotals[$gstKey]['discount'] += $couponDiscount;
                        }
                    }
                    
                    // Vehicle service fees are always 18% GST
                    if($value->vehicle_commission_amount > 0 || $value->vehicle_commission_tax_amt > 0){
                        $groupedTotals[18]['vehicle_commission_rate'] += $value->vehicle_commission_amount;
                        $groupedTotals[18]['vehicle_commission_tax'] += $value->vehicle_commission_tax_amt;
                    }
                }
            }
            //Convenience Fees Calculation
            $newConvenienceFees = $convenienceFees / (1 + (18/100));
            $newConvenienceFees = round($newConvenienceFees, 2);
            $gstAmt = $convenienceFees - $newConvenienceFees;
            $cFees['trip_amount'] = number_format($newConvenienceFees, 2);
            $cFees['tax_percent'] = number_format(18, 2);
            $cFees['tax_amount'] = number_format($gstAmt, 2);
            $cFees['coupon_discount'] = number_format(0, 2);
            $cFees['total_amount'] = number_format($convenienceFees, 2);
            $rateTotal += $newConvenienceFees;
            
            // Convenience fees are always 18% GST
            $groupedTotals[18]['rate'] += $newConvenienceFees;
            $groupedTotals[18]['tax'] += $gstAmt;
            
            // Note: Discounts are already subtracted in the rate calculation above
            // No need to subtract again here as rate = trip_amount - vehicle_commission_amount - coupon_discount
            
            // Round all grouped totals (including discount field for consistency)
            foreach($groupedTotals as $key => $totals){
                $groupedTotals[$key]['rate'] = round($totals['rate'], 2);
                $groupedTotals[$key]['tax'] = round($totals['tax'], 2);
                $groupedTotals[$key]['vehicle_commission_rate'] = round($totals['vehicle_commission_rate'], 2);
                $groupedTotals[$key]['vehicle_commission_tax'] = round($totals['vehicle_commission_tax'], 2);
                $groupedTotals[$key]['discount'] = round($totals['discount'] ?? 0, 2);
            }
            
            // Recalculate totalAmt and totalTax from grouped totals (this ensures they match displayed values)
            $totalAmt = 0;
            $totalTax = 0;
            foreach($groupedTotals as $key => $totals){
                // Add rate + tax + vehicle_commission_rate + vehicle_commission_tax for each GST group
                $totalAmt += $totals['rate'] + $totals['tax'] + $totals['vehicle_commission_rate'] + $totals['vehicle_commission_tax'];
                // Calculate total tax (excluding rates)
                $totalTax += $totals['tax'] + $totals['vehicle_commission_tax'];
            }
            // Note: Convenience fees are already included in groupedTotals[18], so no need to add separately
            
            $rateTotal = round($rateTotal, 2);
            $totalAmt = round($totalAmt, 2);
            $totalTax = round($totalTax, 2);
        }
        
        $filename = 'booking-invoice-' . $bookingId . '.pdf';
        $pdf = PDF::loadView('booking-invoice', compact('data', 'companyDetails', 'newBooking', 'extension', 'completion', 'totalAmt', 'totalTax', 'convenienceFees', 'cFees', 'rateTotal', 'penaltyText', 'gstStatus', 'completionDisplay', 'extraKmString', 'newBookingTimeStamp', 'completionNewBooking', 'adminPenaltiesDue'/*, 'vehiclePercentAmt'*/, 'newBookingVehicleServiceFees', 'extensionVehicleServiceFees', 'completionVehicleServiceFees', 'paidPenalties', 'paidPenaltyServiceCharge', 'duePenalties', 'duePenaltyServiceCharge', 'amountDue', 'groupedTotals'))->setPaper('A3');
        return $pdf->stream('booking-invoice.pdf');
    }

    // public function bookingInvoiceData(Request $request, $bookingId)
    // {
    //     $extraKmString = '';
    //     $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images', 'customer'])->where('booking_id', $bookingId)->first();
    //     $companyDetails = CompanyDetail::select('id', 'address', 'phone', 'alt_phone', 'email', 'gst_no', 'pan_no', 'bank_name', 'bank_account_no', 'bank_ifsc_code')->first();
    //     $newBooking = $extension = $completion = $cFees = $adminPenaltiesDue = $newBookingVehicleServiceFees = $extensionVehicleServiceFees = $paidPenalties = $paidPenaltyServiceCharge = $duePenalties = $duePenaltyServiceCharge = $completionVehicleServiceFees = [];
    //     $totalAmt = $totalTax = $convenienceFees = $rateTotal = $completionDisplay = $amountDue = 0;
    //     $gstStatus = 1; // 1 = Consider CGST/SGST
    //     if($data && $data->customer && $data->customer->gst_number != null){
    //         if(str_starts_with($data->customer->gst_number, 24) == ''){
    //             $gstStatus = 2; // 2 = Consider IGST
    //         }
    //     }
    //     $newBookingTimeStamp = $completionNewBooking = $penaltyText = '';
    //     $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
    //     $gstPercent = $data->tax_rate ?? 0;
    //     if(is_countable($calculationDetails) && count($calculationDetails) > 0){
    //         foreach ($calculationDetails as $key => $value) {
    //             $commissionTaxAmount = $value->vehicle_commission_tax_amt ?? 0;
    //             if($value->type == 'new_booking' && $value->paid == 1){
    //                 //$newBookingTimeStamp = $value->timestamp;
    //                 $newBookingTimeStamp = date('d-m-Y H:i', strtotime($value->start_date)).' - '.date('d-m-Y H:i', strtotime($value->end_date));
    //                 $newBooking['trip_amount'] = number_format($value->trip_amount - $value->vehicle_commission_amount, 2);
    //                 $taxPercent = 0;
    //                 $mainAmt = $value->trip_amount;
    //                 if(isset($value->coupon_discount) && $value->coupon_discount != 0){
    //                     $mainAmt = $value->trip_amount - $value->coupon_discount;
    //                 }
    //                 $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
    //                 $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);

    //                 $newBooking['tax_percent'] = number_format($taxPercent, 2);
    //                 $newBooking['tax_amount'] = number_format(($value->tax_amt - $value->vehicle_commission_tax_amt), 2);
    //                 $newBooking['coupon_discount'] = number_format($value->coupon_discount, 2);
    //                 $newBooking['total_amount'] = $value->total_amount - $value->convenience_fee;
    //                 $newBooking['total_amount'] = number_format(($newBooking['total_amount'] - ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt)), 2);

    //                 $tAmt = $value->total_amount - $value->convenience_fee;
    //                 $totalAmt += $tAmt;
    //                 $totalTax += $value->tax_amt;
    //                 $convenienceFees += $value->convenience_fee;
    //                 $rateTotal += $value->trip_amount;
    //                 $rateTotal -= $value->coupon_discount;
    //                 $newBookingVehicleServiceFees['trip_amount'] = number_format($value->vehicle_commission_amount, 2);
    //                 $newBookingVehicleServiceFees['tax_percent'] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
    //                 $newBookingVehicleServiceFees['tax_amount'] = number_format($value->vehicle_commission_tax_amt, 2);
    //                 $newBookingVehicleServiceFees['coupon_discount'] = number_format(0, 2);
    //                 $newBookingVehicleServiceFees['total_amount'] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
    //             }elseif($value->type == 'extension' && $value->paid == 1){
    //                 $extension['timestamp'][] = date('d-m-Y H:i', strtotime($value->end_date));
    //                 $extension['trip_amount'][] = number_format(($value->trip_amount - $value->vehicle_commission_amount), 2);
    //                 $taxPercent = 0;
    //                 $mainAmt = $value->trip_amount;
    //                 if(isset($value->coupon_discount) && $value->coupon_discount != 0){
    //                     $mainAmt = $value->trip_amount - $value->coupon_discount;
    //                 }
    //                 $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
    //                 $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);

    //                 $extension['tax_percent'][] = number_format($taxPercent, 2);
    //                 $extension['tax_amount'][] = number_format(($value->tax_amt - $value->vehicle_commission_tax_amt), 2);
    //                 $extension['coupon_discount'][] = number_format($value->coupon_discount, 2);
    //                 $tAmt = $value->total_amount - $value->convenience_fee;
    //                 $totalAmt += $tAmt;
    //                 $totalTax += $value->tax_amt;
    //                 $convenienceFees += $value->convenience_fee;
    //                 $rateTotal += $value->trip_amount;
    //                 $rateTotal -= $value->coupon_discount;
    //                 $extensionVehicleServiceFees['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
    //                 $extensionVehicleServiceFees['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
    //                 $extensionVehicleServiceFees['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
    //                 $extensionVehicleServiceFees['coupon_discount'][] = number_format(0, 2);
    //                 $extensionVehicleServiceFees['total_amount'][] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
    //                 $extension['total_amount'][] = number_format(($value->total_amount - ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt)), 2);
    //             }elseif($value->type == 'completion' && $value->paid == 1){
    //                 $completionNewBooking = date('d-m-Y H:i', strtotime($value->timestamp));
    //                 $additionalCharges = $totalAmount = 0;
    //                 $penaltyText = '';

    //                 if(isset($value->late_return) && $value->late_return != '' && $value->late_return != 0){
    //                     $additionalCharges += $value->late_return;
    //                     $penaltyText .= ' Late Return - '. round($value->late_return, 2);
    //                 }
    //                 if(isset($value->exceeded_km_limit) && $value->exceeded_km_limit != '' && $value->exceeded_km_limit != 0){
    //                     $additionalCharges += $value->exceeded_km_limit;
    //                     if($value->late_return != 0){
    //                         $penaltyText .=  ' | ';    
    //                     }
    //                     if(is_countable($data->price_summary) && count($data->price_summary) > 0){
    //                         foreach($data->price_summary as $key => $val){
    //                             if(str_starts_with(strtolower($val['key']), 'extra')){
    //                                 $extraKmString = $val['key'];
    //                             }
    //                         }
    //                     }
    //                     $penaltyText .=  $extraKmString;
    //                 }
    //                 if(isset($value->additional_charges) && $value->additional_charges != '' && $value->additional_charges != 0){
    //                     $additionalCharges += $value->additional_charges;
    //                     if($value->exceeded_km_limit != 0){
    //                          $penaltyText .=  ' | ';    
    //                     }
    //                     $penaltyText .=  'Additional Charges - '. $value->additional_charges;
    //                 }
    //                 $completion['additional_charge'] = number_format( (round($additionalCharges, 2) - $value->vehicle_commission_amount), 2);
    //                 if(isset($value->tax_amt) && $value->tax_amt != '' && $value->tax_amt != 0){
    //                     $totalAmount += $value->tax_amt;
    //                 }
    //                 $taxPercent = 0;
    //                 $mainAmt = $additionalCharges;
    //                 if(isset($value->coupon_discount) && $value->coupon_discount != 0){
    //                     $mainAmt = $value->trip_amount - $value->coupon_discount;
    //                 }
    //                 $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
    //                 $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);

    //                 $completion['tax_percent'] = number_format($taxPercent, 2);
    //                 $completion['tax_amount'] = number_format(($value->tax_amt - $value->vehicle_commission_tax_amt), 2);
    //                 $completion['coupon_discount'] = number_format(0, 2);
    //                 $completion['total_amount'] =  number_format( round(($totalAmount + $additionalCharges), 2) - ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
    //                 if($data->booking_id != 1805){
    //                     $totalAmt += $value->tax_amt;
    //                     $totalAmt += $additionalCharges;
    //                     $totalTax += $value->tax_amt;
    //                     $rateTotal += $additionalCharges;
    //                 }else{
    //                     $amountDue += 227617.59;
    //                 }

    //                 if($completion['additional_charge'] != 0 || $completion['total_amount'] != 0){
    //                     $completionDisplay = 1;
    //                 }

    //                 $completionVehicleServiceFees['trip_amount'] = number_format($value->vehicle_commission_amount, 2);
    //                 $completionVehicleServiceFees['tax_percent'] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
    //                 $completionVehicleServiceFees['tax_amount'] = number_format($value->vehicle_commission_tax_amt, 2);
    //                 $completionVehicleServiceFees['coupon_discount'] = number_format(0, 2);
    //                 $completionVehicleServiceFees['total_amount'] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
    //             }elseif($value->type == 'penalty' && $value->paid == 1){
    //                 if($value->final_amount > 0){
    //                     $paidPenalties['timestamp'][] = date('d-m-Y H:i', strtotime($value->timestamp));
    //                     $mainAmt = $value->total_amount;
    //                     if(isset($value->coupon_discount) && $value->coupon_discount != 0){
    //                         $mainAmt = $value->total_amount - $value->coupon_discount;
    //                     }   
    //                     $paidPenalties['trip_amount'][] = number_format($value->total_amount ?? 0, 2);
    //                     $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
    //                     $paidPenalties['tax_percent'][] = getTaxPercent($mainAmt, $value->tax_amt, $mainAmt, $vehiclePercent, $gstPercent, $commissionTaxAmount);
    //                     $paidPenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
    //                     $paidPenalties['coupon_discount'][] = number_format(0, 2);
    //                     $paidPenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);

    //                     $paidPenaltyServiceCharge['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
    //                     $paidPenaltyServiceCharge['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
    //                     $paidPenaltyServiceCharge['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
    //                     $paidPenaltyServiceCharge['coupon_discount'][] = number_format(0, 2);
    //                     $paidPenaltyServiceCharge['total_amount'][] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

    //                     $rateTotal += $value->total_amount;
    //                     $rateTotal += $value->vehicle_commission_amount;
    //                     $totalTax += $value->tax_amt;
    //                     $totalTax += $value->vehicle_commission_tax_amt;

    //                     $totalAmt += $value->total_amount + $value->tax_amt;
    //                     $totalAmt += $value->vehicle_commission_amount + $value->vehicle_commission_tax_amt;
    //                 }
    //             }elseif($value->type == 'penalty' && $value->paid == 0){
    //                 $duePenalties['timestamp'][] = date('d-m-Y H:i', strtotime($value->timestamp));
    //                 $mainAmt = $value->total_amount;
    //                 if(isset($value->coupon_discount) && $value->coupon_discount != 0){
    //                     $mainAmt = $value->total_amount - $value->coupon_discount;
    //                 }   
    //                 $duePenalties['trip_amount'][] = number_format($value->total_amount ?? 0, 2);
    //                 $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
    //                 $taxPercent = ($gstPercent == 0.05) ? 5 : (($gstPercent == 0.18) ? 18 : 0);
    //                 $duePenalties['tax_percent'][] = $taxPercent;
    //                 // OLD CODE
    //                 //$duePenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
    //                 //$duePenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);
    //                 // NEW CODE
    //                 if($value->tax_amt > $value->vehicle_commission_tax_amt){
    //                     $penaltyTax = $value->tax_amt - $value->vehicle_commission_tax_amt;
    //                     $duePenalties['tax_amount'][] = number_format($penaltyTax ?? 0, 2);
    //                     $duePenalties['total_amount'][] = number_format(($value->total_amount + $penaltyTax), 2);
    //                 }else{
    //                     $duePenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
    //                     $duePenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);
    //                 }
    //                 $duePenalties['coupon_discount'][] = number_format(0, 2);

    //                 $duePenaltyServiceCharge['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
    //                 $duePenaltyServiceCharge['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
    //                 $duePenaltyServiceCharge['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
    //                 $duePenaltyServiceCharge['coupon_discount'][] = number_format(0, 2);
    //                 $duePenaltyServiceCharge['total_amount'][] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

    //                 $amountDue += ($value->total_amount + $value->tax_amt);
    //                 //$amountDue += ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt);
    //                 $amountDue += ($value->vehicle_commission_amount);
    //             }
    //         }
    //         //Convenience Fees Calculation
    //         $newConvenienceFees = $convenienceFees / (1 + (18/100));
    //         $newConvenienceFees = round($newConvenienceFees, 2);
    //         $gstAmt = $convenienceFees - $newConvenienceFees;
    //         $cFees['trip_amount'] = number_format($newConvenienceFees, 2);
    //         $cFees['tax_percent'] = number_format(18, 2);
    //         $cFees['tax_amount'] = number_format($gstAmt, 2);
    //         $cFees['coupon_discount'] = number_format(0, 2);
    //         $cFees['total_amount'] = number_format($convenienceFees, 2);
    //         $totalAmt += $convenienceFees;
    //         $totalTax += $gstAmt;
    //         $rateTotal += $newConvenienceFees;
    //         $rateTotal = round($rateTotal, 2);
    //         $totalAmt = round($totalAmt, 2);
    //     }
        
    //     $filename = 'booking-invoice-' . $bookingId . '.pdf';
    //     $pdf = PDF::loadView('booking-invoice', compact('data', 'companyDetails', 'newBooking', 'extension', 'completion', 'totalAmt', 'totalTax', 'convenienceFees', 'cFees', 'rateTotal', 'penaltyText', 'gstStatus', 'completionDisplay', 'extraKmString', 'newBookingTimeStamp', 'completionNewBooking', 'adminPenaltiesDue'/*, 'vehiclePercentAmt'*/, 'newBookingVehicleServiceFees', 'extensionVehicleServiceFees', 'completionVehicleServiceFees', 'paidPenalties', 'paidPenaltyServiceCharge', 'duePenalties', 'duePenaltyServiceCharge', 'amountDue'))->setPaper('A3');
    //     return $pdf->stream('booking-invoice.pdf');
    // }

    public function bookingSummaryData($bookingId, $customerId)
    {
        $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images'])->where('booking_id', $bookingId)->first();
        $customerDoc = CustomerDocument::select('document_id', 'customer_id', 'document_type', 'is_approved', 'id_number')->where('customer_id', $customerId)->get();
        $docDetails['gov_status'] = '';
        $docDetails['gov_id_number'] = '';
        $docDetails['dl_status'] = '';
        $docDetails['dl_id_number'] = '';
        if(is_countable($customerDoc) && count($customerDoc) > 0){
            foreach($customerDoc as $key => $val){
                if(strtolower($val->document_type) == 'govtid'){
                    $docDetails['gov_status'] = isset($val->is_approved)?$val->is_approved:'';            
                    $docDetails['gov_id_number'] = isset($val->id_number)?$val->id_number:'';            
                }
                if(strtolower($val->document_type) == 'dl'){
                    $docDetails['dl_status'] = isset($val->is_approved)?$val->is_approved:''; 
                    $docDetails['dl_id_number'] = isset($val->id_number)?$val->id_number:'';
                }
            }
        }
        $data->gov_status = $docDetails['gov_status'] ;
        $data->gov_id_number = $docDetails['gov_id_number'] ;
        $data->dl_status = $docDetails['dl_status'] ;
        $data->dl_id_number = $docDetails['dl_id_number'] ;
        $filename = 'booking-summary-' . $bookingId . '.pdf';
        $pdf = PDF::loadView('booking-summary', compact('data'));
        return $pdf->stream('booking-summary.pdf');
    }

    public function customerAggrement($bookingId){
        $vehicleRegistrationNo = '-';
        $bookingStartDate = '';
        $booking = RentalBooking::select('booking_id', 'vehicle_id', 'start_datetime', 'pickup_date', 'customer_id')->where('booking_id', $bookingId)->first();
        $customer = Customer::where('customer_id', $booking->customer_id)->first();
        $name = '';
        $ownerName = 'Shailesh Car & Bikes Pvt. Ltd.';
        if($customer){
            $name .= $customer->firstname ?? '';
            $name .= ' '.$customer->lastname ?? '';
        }
        if($booking){
            $vehicle = Vehicle::where('vehicle_id', $booking->vehicle_id)->first();
            $vehicleRegistrationNo = $vehicle->license_plate ?? '-';
            $carEligibility = CarEligibility::with('carHost')->where('vehicle_id', $booking->vehicle_id)->first();
            if ($carEligibility && $carEligibility->carHost) {
                $ownerName .= $carEligibility->carHost->firstname ?? '';
                $ownerName .= ' '.$carEligibility->carHost->lastname ?? '';
            }
            $bookingStartDate = $booking->start_datetime ? date('d-m-Y H:i', strtotime($booking->start_datetime)) : date('d-m-Y H:i', strtotime($booking->pickup_date));
        }
        $fileName = 'customer_agreements_'.$booking->customer_id.'_'.$bookingId.'.pdf';
        $path = public_path().'/customer_aggrements/';

        $pdf = PDF::loadView('customer_aggrement', compact('name', 'bookingId', 'ownerName', 'bookingStartDate', 'vehicleRegistrationNo'));
        return $pdf->stream($fileName);
    }

    public function forgotPassword(Request $request){
        $validator = Validator::make($request->all(), [		
			'mobile_number' => ['required','numeric','regex:/^\+?[1-9]\d{9,14}$/','digits_between:8,15', 'exists:admin_users,mobile_number'],
		]);		
		if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
		}

        $user = AdminUser::select('admin_id', 'mobile_number')->where('mobile_number', '=', $request->mobile_number)->first();
		if (!isset($user) && $user == '') {
			return $this->errorResponse('Admin User not Found');
		}
        $to = $user->mobile_number ?? '';
        if(isset($to) && $to != ''){
           try{
                $otp = $this->generateAndSendOTP($to);
                if ($otp === null) { 
                    return $this->errorResponse('OTP already sent within 1 Minute.');
                }
                if ($otp && isset($otp['status']) && $otp['status'] !== 200) {
                    $errorMessage = $otp['message'] ?? 'Something went Wrong';
                    return $this->errorResponse($errorMessage);
                } else{
                    return $this->successResponse(null, 'OTP sent for Verification.');    
                }
            } catch (\Exception $e) {} 
        }

        return $this->successResponse($user, 'Email Reset Link Send Successfully');
    }

    private function generateAndSendOTP($mobileNumber)
    {
        // Retrieve the last OTP sent time from the cache
        $lastOTPSentTime = Cache::get('last_otp_sent_' . $mobileNumber);

        // Check if OTP was sent in the last 30 seconds
        if ($lastOTPSentTime && now()->diffInSeconds($lastOTPSentTime) < 30) {
            return;
        }

        // Generate OTP
        $otp = strval(mt_rand(1000, 9999));
        $checkresponse =  $this->smsService->sendOTP($mobileNumber,$otp);
        // Check the response status and handle errors
        if($checkresponse && isset($checkresponse['status']) && $checkresponse['status'] != 200){
            $checkResponse['message'] = $checkResponse['message'] ?? 'An error occurred while sending OTP.';
            return $checkresponse; 
        }
        
        // Cache the OTP and timestamp
        Cache::put('otp_' . $mobileNumber, strval($otp), 60 * 5);
        // Store the timestamp of the OTP sent
        Cache::put('last_otp_sent_' . $mobileNumber, now(), 30);

        return $otp; 
    }

    public function forgotPasswordVerifyOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required','numeric','regex:/^\+?[1-9]\d{9,14}$/','digits_between:8,15', 'exists:admin_users,mobile_number'],
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $otp = Cache::get('otp_' . $request->mobile_number);
        if (!$otp || $otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP');
        }
        $user = AdminUser::where('mobile_number', $request->mobile_number)->latest()->first();
        if (!$user) {
            return $this->errorResponse('Admin User not found');
        }

        return $this->successResponse($user, 'OTP verified Successfully');
    }

    public function resetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            //'admin_id' => 'required',
            'password' => 'required',
            'mobile_number' => ['required','numeric','regex:/^\+?[1-9]\d{9,14}$/','digits_between:8,15', 'exists:admin_users,mobile_number'],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = AdminUser::where(['mobile_number' => $request->mobile_number])->first();
        if($user != ''){
            $user->password = Hash::make($request->password);
			$user->save();	
            return $this->successResponse($user, 'Password Reset Successfully');
        }else{
            return $this->errorResponse('User not Found');
        }
    }
    
}
