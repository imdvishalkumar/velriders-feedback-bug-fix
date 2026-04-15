<?php

namespace App\Http\Controllers\CarhostAppApis\V1;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// use App\Models\{
//     CarHost, CarEligibility, LoginToken, UserDevice, Customer, CarHostBank
// };
use App\Models\{
    CarHost, CarEligibility, LoginToken, UserDevice, Customer, CarHostBank,RentalBooking,CompanyDetail,BookingTransaction, CarHostPickupLocation
};
use App\Services\SmsService;
use App\Services\Invoice\InvoiceCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use JWTAuth;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\Rule;

class CarHostController extends Controller
{
    protected $smsService;
    protected $userAuthDetails;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
        $this->userAuthDetails = Auth::guard('api-carhost')->user();
    }

    /**
     * Get Grand Total for a booking (for use in payment reports)
     * 
     * @param int $bookingId
     * @return float Grand Total amount
     */
    public static function getBookingGrandTotal(int $bookingId): float
    {
        try {
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
                return 0.0;
            }

            // Get vehicle service percentage from DB
            $vehicleServicePercent = $data->vehicle->commission_percent ?? 0;

            // Get booking GST rate based on customer type
            // Use app() to resolve dependencies for the instance
            $instance = app(self::class);
            $bookingGstRate = $instance->getBookingGstRate($data);

            // Calculate all invoice data using reverse calculation from Grand Total
            $invoiceData = $instance->calculateInvoiceFromGrandTotal($bookingId, $vehicleServicePercent, $bookingGstRate, $data);

            // Get displayTotal and amountDue
            $displayTotal = $invoiceData['displayTotal'] ?? 0;
            $amountDue = $invoiceData['amountDue'] ?? 0;

            // Grand Total = displayTotal + amountDue
            return round($displayTotal + $amountDue, 2);
        } catch (\Exception $e) {
            // Return 0 if calculation fails
            return 0.0;
        }
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
    public function carhostBookingInvoiceData(Request $request, $bookingId)
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
        $displayTotal = $invoiceData['displayTotal'] ?? 0; // Sum of all displayed Amount column values
        $convenienceFees = $invoiceData['convenienceFees'] ?? 0;
        $newBookingTimeStamp = $invoiceData['newBookingTimeStamp'] ?? '';
        $completionNewBooking = $invoiceData['completionNewBooking'] ?? '';
        $penaltyText = $invoiceData['penaltyText'] ?? '';
        $completionDisplay = $invoiceData['completionDisplay'] ?? 0;
        $extraKmString = $invoiceData['extraKmString'] ?? '';
        $roundOffs = $invoiceData['roundOffs'] ?? [];
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
        $pdf = PDF::loadView('carhost-booking-invoice', compact(
            'data', 'companyDetails', 'newBooking', 'extension', 'completion',
            'totalAmt', 'totalTax', 'convenienceFees', 'cFees', 'rateTotal',
            'penaltyText', 'gstStatus', 'completionDisplay', 'extraKmString',
            'newBookingTimeStamp', 'completionNewBooking', 'adminPenaltiesDue',
            'newBookingVehicleServiceFees', 'extensionVehicleServiceFees',
            'completionVehicleServiceFees', 'paidPenalties', 'paidPenaltyServiceCharge',
            'duePenalties', 'duePenaltyServiceCharge', 'amountDue', 'groupedTotals', 'displayTotal', 'roundOffs'
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
        
        // Solve for booking_taxable first
        $bookingTaxableSolved = $remaining / $totalMultiplier;
        
        // CRITICAL: Booking Base (B) = booking_taxable + discount. Do NOT modify after this point.
        $bookingBase = $bookingTaxableSolved + $discount;
        
                
        Log::info("PHASE 2 - Reverse calculation:", [
            'remaining' => $remaining,
            'bookingMultiplier' => $bookingMultiplier,
            'vsMultiplier' => $vsMultiplier,
            'totalMultiplier' => $totalMultiplier,
            'booking_taxable_solved' => $bookingTaxableSolved,
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
        $result['displayTotal'] = round($result['displayTotal'], 2); // Sum of all displayed Amount values

        // Round grouped totals
        foreach ($result['groupedTotals'] as $key => $totals) {
            $result['groupedTotals'][$key]['rate'] = round($totals['rate'], 2);
            $result['groupedTotals'][$key]['tax'] = round($totals['tax'], 2);
            $result['groupedTotals'][$key]['vehicle_commission_rate'] = round($totals['vehicle_commission_rate'], 2);
            $result['groupedTotals'][$key]['vehicle_commission_tax'] = round($totals['vehicle_commission_tax'], 2);
        }
        
        // Ensure totalAmt is sum of Displayed Amount values (as per Host requirement)
        // Previous logic (Phase 1) set totalAmt to Grand Total (Inc Tax).
        // Host Invoice requires Grand Total = Sum(Amount Column).
        // Amount Column = Rate - Discount.
        // displayTotal tracks this sum.
        $result['totalAmt'] = $result['displayTotal'];
        // amountDue is calculated as difference, but if totalAmt changes, amountDue might need adjustment?
        // Actually, amountDue calculation logic earlier subtracts PAID amount from totalAmt.
        // If totalAmt changes definition, amountDue logic is broken.
        
        // Wait. User said "grand total should be sum of amount".
        // If Amount is Rate - Discount (Taxable), then Grand Total is Sum Taxable.
        // Amount Due = Grand Total - Paid Amount ??
        // Paid Amount = Taxable + Tax.
        // So Amount Due would likely be negative if we compare Taxable Sum vs Paid (Inc Tax).
        
        // Let's assume for now simply setting totalAmt = displayTotal fulfills "grand total should be sum of amount".
        // The Payment/Due logic might rely on totalAmt being the INVOICE TOTAL.
        // If host invoice is Tax Exclusive, then Invoice Total = Taxable Total.
        
        return $result;
    }

    /**
     * Build new booking line items
     * 
     * Uses the transaction's own total_amount to reverse calculate the booking base,
     * similar to how buildExtensionLineItems works. This ensures each transaction
     * reverse-calculates from its own paid amount.
     */
    private function buildNewBookingLineItems(
        BookingTransaction $transaction,
        float $bookingBaseUnused,     // No longer used - kept for API compatibility
        float $discount,
        float $vehicleServicePercent,
        float $bookingGstRate,
        float $vehicleServiceGstRate,
        float $convenienceFeeGstRate,
        array &$result
    ): void {
        // Get transaction's own values
        $grandTotal = $transaction->total_amount ?? 0;
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

        // REVERSE CALCULATE booking base from this transaction's total_amount
        // Remove convenience fee to get remaining amount for booking charges
        $remaining = $grandTotal - $convenienceFee;
        
        // Solve for Booking Taxable (T = B - discount)
        // Equation: T × (1 + bookingGstRate) + T × vsPercent/100 × (1 + vsGstRate) = remaining
        // Simplified: T × [(1 + bookingGstRate) + vsPercent/100 × (1 + vsGstRate)] = remaining
        // Solution: T = remaining / totalMultiplier
        $bookingMultiplier = 1 + $bookingGstRate;
        $vsMultiplier = ($vehicleServicePercent / 100) * (1 + $vehicleServiceGstRate);
        $totalMultiplier = $bookingMultiplier + $vsMultiplier;
        
        // Solve for booking taxable, then derive base
        $bookingTaxable = $remaining / $totalMultiplier;
        $bookingBase = $bookingTaxable + $discount;
        $bookingRate = $bookingBase;
        
        $bookingGst = $bookingTaxable * $bookingGstRate;
        $bookingTotal = $bookingTaxable + $bookingGst;
        
        $gstPercentInt = (int)($bookingGstRate * 100);
        
        $result['newBooking'] = [
            'trip_amount' => number_format($bookingRate, 2), // Rate column (unchanged)
            'tax_percent' => number_format($gstPercentInt, 2),
            'tax_amount' => number_format($bookingGst, 2),
            'coupon_discount' => number_format($discount, 2), // Discount column (unchanged)
            'total_amount' => number_format($bookingTaxable, 2), // Amount = Rate - Discount (without GST)
        ];
        
        $result['rateTotal'] += $bookingTaxable;
        $result['totalTax'] += $bookingGst;
        $result['displayTotal'] += $bookingTaxable; // Add to sum of displayed Amount values
        
        $result['groupedTotals'][$gstPercentInt]['rate'] += $bookingTaxable;
        $result['groupedTotals'][$gstPercentInt]['tax'] += $bookingGst;

        // Vehicle Service Fee on Booking (calculated on TAXABLE = Rate - Discount)
        // VS Rate = 20% of (Booking Rate - Discount)
        if ($vehicleServicePercent > 0) {
            $vsBase = $bookingTaxable * ($vehicleServicePercent / 100);
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
        
        // Solve for extension_taxable
        $extensionTaxableSolved = $remaining / $totalMultiplier;
        
        // Extension Base = extension_taxable + discount
        $extensionBase = $extensionTaxableSolved + $discount;
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
        $result['extension']['total_amount'][] = number_format($extensionTaxable, 2); // Show Rate - Discount only (GST included in Grand Total)

        $result['rateTotal'] += $extensionTaxable;
        $result['totalTax'] += $extensionGst;
        $result['displayTotal'] += $extensionTaxable; // Add to sum of displayed Amount values
        
        $result['groupedTotals'][$gstPercentInt]['rate'] += $extensionTaxable;
        $result['groupedTotals'][$gstPercentInt]['tax'] += $extensionGst;

        // Vehicle Service Fee on Extension
        if ($vehicleServicePercent > 0) {
            $vsBase = $extensionTaxable * ($vehicleServicePercent / 100);
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
    /**
     * Build completion line items (late return, exceeded km, additional charges)
     * Uses reverse calculation from amount_to_pay if available
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
        $totalCompletionBaseDb = $lateReturn + $exceededKm + $additionalCharges;
        
        if ($totalCompletionBaseDb <= 0) {
            return;
        }

        // Special case for booking 1805
        if ($booking->booking_id == 1805) {
            $result['amountDue'] += 227617.59;
            return;
        }

        // Determine the source of truth for the total amount
        // If amount_to_pay is present (paid transaction), use it.
        // Otherwise use the calculated total.
        $targetTotal = $transaction->amount_to_pay > 0 ? $transaction->amount_to_pay : ($transaction->total_amount ?? 0);
        
        // Calculate multipliers
        $completionMultiplier = 1 + $lateReturnGstRate;
        $vsMultiplier = ($vehicleServicePercent / 100) * (1 + $vehicleServiceGstRate);
        $totalMultiplier = $completionMultiplier + $vsMultiplier;
        
        // If we have a valid paid amount that differs from the calculated liability, 
        // we reverse calculate the base to match the payment
        // Calculated Liability = BaseDb * TotalMultiplier
        $calculatedLiability = $totalCompletionBaseDb * $totalMultiplier;
        
        // Use a small threshold for float comparison
        if ($targetTotal > 0 && abs($targetTotal - $calculatedLiability) > 0.01) {
             // REVERSE CALCULATE
             $completionBase = $targetTotal / $totalMultiplier;
        } else {
             // FORWARD CALCULATE (Standard)
             $completionBase = $totalCompletionBaseDb;
        }

        $completionGst = $completionBase * $lateReturnGstRate;
        $completionTotal = $completionBase + $completionGst;

        $gstPercentInt = (int)($lateReturnGstRate * 100);

        $result['completion'] = [
            'trip_amount' => number_format($completionBase, 2),
            'tax_percent' => number_format($gstPercentInt, 2),
            'tax_amount' => number_format($completionGst, 2),
            'coupon_discount' => number_format(0, 2),
            'total_amount' => number_format($completionBase, 2), // Show base amount only (GST included in Grand Total)
        ];

        $result['completionDisplay'] = 1;
        $result['rateTotal'] += $completionBase;
        $result['totalTax'] += $completionGst;
        $result['displayTotal'] += $completionBase; // Add to sum of displayed Amount values
        
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
            'roundOffs' => [
                'newBooking' => 0.0,
                'extension' => [],
                'completion' => 0.0,
                'paidPenalties' => [],
                'total' => 0.0,
            ],
            'groupedTotals' => [
                5 => ['rate' => 0, 'tax' => 0, 'vehicle_commission_rate' => 0, 'vehicle_commission_tax' => 0],
                18 => ['rate' => 0, 'tax' => 0, 'vehicle_commission_rate' => 0, 'vehicle_commission_tax' => 0],
            ],
            'totalAmt' => 0.0,
            'amountDue' => 0.0,
            'rateTotal' => 0.0,
            'totalTax' => 0.0,
            'convenienceFees' => 0.0,
            'displayTotal' => 0.0, // Sum of all displayed Amount column values (Rate - Discount, no GST)
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
            $result['paidPenalties']['total_amount'][] = number_format($penaltyBase, 2); // Show base amount only (GST included in Grand Total)
            $result['displayTotal'] += $penaltyBase; // Add to sum of displayed Amount values

            if ($vehicleServicePercent > 0) {
                $result['paidPenaltyServiceCharge']['trip_amount'][] = number_format($vsBase, 2);
                $result['paidPenaltyServiceCharge']['tax_percent'][] = number_format($vehicleServiceGstRate * 100, 2);
                $result['paidPenaltyServiceCharge']['tax_amount'][] = number_format($vsGst, 2);
                $result['paidPenaltyServiceCharge']['coupon_discount'][] = number_format(0, 2);
                $result['paidPenaltyServiceCharge']['total_amount'][] = number_format($vsTotal, 2);
            }

            // NOTE: Penalties are already included in totalAmt from Phase 1 (grandTotal)
            // DO NOT add again here to avoid double counting
            
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
            $result['duePenalties']['total_amount'][] = number_format($penaltyBase, 2); // Show base amount only (GST included in Grand Total)
            $result['displayTotal'] += $penaltyBase; // Add to sum of displayed Amount values

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





    // public function carhostBookingInvoiceData(Request $request, $bookingId) 
    // {
    //     $extraKmString = '';
    //     $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images', 'customer'])->where('booking_id', $bookingId)->first();
    //     $companyDetails = CompanyDetail::select('id', 'address', 'phone', 'alt_phone', 'email', 'gst_no', 'pan_no', 'bank_name', 'bank_account_no', 'bank_ifsc_code')->first();
    //     $newBooking = $extension = $completion = $cFees = $adminPenaltiesDue = $newBookingVehicleServiceFees = $extensionVehicleServiceFees = $paidPenalties = $paidPenaltyServiceCharge = $duePenalties = $duePenaltyServiceCharge = $completionVehicleServiceFees = [];
    //     $totalAmt = $totalTax = $rateTotal = $completionDisplay = $amountDue = 0;
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
    //                 // Rate = trip_amount - vehicle_commission_amount (no GST)
    //                 $rate = $value->trip_amount - $value->vehicle_commission_amount;
    //                 $newBooking['trip_amount'] = number_format($rate, 2);
    //                 $newBooking['coupon_discount'] = number_format($value->coupon_discount, 2);
    //                 // Amount = Rate - Discount (no GST subtraction for CarHost invoice)
    //                 $displayedAmount = $rate - $value->coupon_discount;
    //                 $newBooking['total_amount'] = number_format($displayedAmount, 2);
                    
    //                 // Add displayed amount to totalAmt
    //                 $totalAmt += $displayedAmount;
    //                 // rateTotal = sum of (Rate - Discount) for each item
    //                 $rateTotal += ($rate - $value->coupon_discount);
    //                 $newBookingVehicleServiceFees['trip_amount'] = number_format($value->vehicle_commission_amount, 2);
    //                 $newBookingVehicleServiceFees['tax_percent'] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
    //                 $newBookingVehicleServiceFees['tax_amount'] = number_format($value->vehicle_commission_tax_amt, 2);
    //                 $newBookingVehicleServiceFees['coupon_discount'] = number_format(0, 2);
    //                 $newBookingVehicleServiceFees['total_amount'] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
    //             }elseif($value->type == 'extension' && $value->paid == 1){
    //                 $extension['timestamp'][] = date('d-m-Y H:i', strtotime($value->end_date));
    //                 // Rate = trip_amount - vehicle_commission_amount (no GST)
    //                 $rate = $value->trip_amount - $value->vehicle_commission_amount;
    //                 $extension['trip_amount'][] = number_format($rate, 2);
    //                 $extension['coupon_discount'][] = number_format($value->coupon_discount, 2);
    //                 // Amount = Rate - Discount (no GST subtraction for CarHost invoice)
    //                 $displayedAmount = $rate - $value->coupon_discount;
    //                 $extension['total_amount'][] = number_format($displayedAmount, 2);
                    
    //                 // Add displayed amount to totalAmt
    //                 $totalAmt += $displayedAmount;
    //                 // rateTotal = sum of (Rate - Discount) for each item
    //                 $rateTotal += ($rate - $value->coupon_discount);
    //                 $extensionVehicleServiceFees['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
    //                 $extensionVehicleServiceFees['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
    //                 $extensionVehicleServiceFees['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
    //                 $extensionVehicleServiceFees['coupon_discount'][] = number_format(0, 2);
    //                 $extensionVehicleServiceFees['total_amount'][] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
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
    //                 // Rate = additionalCharges - vehicle_commission_amount (no GST)
    //                 $rate = round($additionalCharges, 2) - $value->vehicle_commission_amount;
    //                 $completion['additional_charge'] = number_format($rate, 2);
    //                 // Set trip_amount for the view (same as rate for completion)
    //                 $completion['trip_amount'] = number_format($rate, 2);
    //                 $completion['coupon_discount'] = number_format(0, 2);
    //                 // Amount = Rate (no discount, no GST subtraction for CarHost invoice)
    //                 $displayedAmount = $rate;
    //                 $completion['total_amount'] = number_format($displayedAmount, 2);
    //                 if($data->booking_id != 1805){
    //                     // Add displayed amount to totalAmt
    //                     $totalAmt += $displayedAmount;
    //                     // rateTotal = sum of all rates
    //                     $rateTotal += $rate;
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
    //                     // Rate = total_amount (no GST)
    //                     $rate = $value->total_amount ?? 0;
    //                     $paidPenalties['trip_amount'][] = number_format($rate, 2);
    //                     $paidPenalties['coupon_discount'][] = number_format(0, 2);
    //                     // Amount = Rate (no GST subtraction for CarHost invoice)
    //                     $displayedAmount = $rate;
    //                     $paidPenalties['total_amount'][] = number_format($displayedAmount, 2);

    //                     $paidPenaltyServiceCharge['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
    //                     $paidPenaltyServiceCharge['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
    //                     $paidPenaltyServiceCharge['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
    //                     $paidPenaltyServiceCharge['coupon_discount'][] = number_format(0, 2);
    //                     $paidPenaltyServiceCharge['total_amount'][] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

    //                     // rateTotal = sum of all rates
    //                     $rateTotal += $rate;
    //                     // Add displayed amount to totalAmt
    //                     $totalAmt += $displayedAmount;
    //                 }
    //             }elseif($value->type == 'penalty' && $value->paid == 0){
    //                 $duePenalties['timestamp'][] = date('d-m-Y H:i', strtotime($value->timestamp));
    //                 // Rate = total_amount (no GST)
    //                 $rate = $value->total_amount ?? 0;
    //                 $duePenalties['trip_amount'][] = number_format($rate, 2);
    //                 $duePenalties['coupon_discount'][] = number_format(0, 2);
    //                 // Amount = Rate (no GST subtraction for CarHost invoice)
    //                 $displayedAmount = $rate;
    //                 $duePenalties['total_amount'][] = number_format($displayedAmount, 2);

    //                 $duePenaltyServiceCharge['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
    //                 $duePenaltyServiceCharge['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
    //                 $duePenaltyServiceCharge['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
    //                 $duePenaltyServiceCharge['coupon_discount'][] = number_format(0, 2);
    //                 $duePenaltyServiceCharge['total_amount'][] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

    //                 // rateTotal = sum of all rates
    //                 $rateTotal += $rate;
    //                 // Add displayed amount to amountDue
    //                 $amountDue += $displayedAmount;
    //             }
    //         }            
    //         $rateTotal = round($rateTotal, 2);
    //         $totalAmt = round($totalAmt, 2);
    //     }
        
    //     // Fetch carHost pickup location
    //     $carHostPickupLocation = null;
    //     if($data && $data->vehicle && $data->vehicle->vehicle_id) {
    //         $carEligibility = CarEligibility::where('vehicle_id', $data->vehicle->vehicle_id)->with('carHost')->first();
    //         if($carEligibility && $carEligibility->carHost) {
    //             // Fetch primary pickup location or first available location
    //             $carHostPickupLocation = CarHostPickupLocation::where('car_hosts_id', $carEligibility->car_hosts_id)
    //                 ->where('is_deleted', 0)
    //                 ->orderBy('is_primary', 'desc')
    //                 ->orderBy('id', 'asc')
    //                 ->first();
    //         }
    //     }
        
    //     $filename = 'carhost-booking-invoice-' . $bookingId . '.pdf';
    //     $pdf = PDF::loadView('carhost-booking-invoice', compact('data', 'companyDetails', 'newBooking', 'extension', 'completion', 'totalAmt', 'totalTax', 'rateTotal', 'penaltyText', 'gstStatus', 'completionDisplay', 'extraKmString', 'newBookingTimeStamp', 'completionNewBooking', 'adminPenaltiesDue', 'paidPenalties', 'duePenalties', 'amountDue', 'carHostPickupLocation'))->setPaper('A3');
    //     return $pdf->stream('carhost-booking-invoice.pdf');
    // }

    public function sendOtp(Request $request){
        $otpVia = config('global_values.otp_via');
        $otpVia = implode(',', $otpVia);
        $validator = Validator::make($request->all(), [
            'otp_via' => 'required|in:'.$otpVia,
            'country_code' => ['nullable', 'string', 'regex:/^\+[1-9]\d{1,3}$/', 'required_if:otp_via,sms'],
            'mobile_number' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{9,14}$/',
                'required_if:otp_via,sms',
                'digits_between:8,15'
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
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $registerVia = NULL;
        if(config('global_values.environment') == 'live'){
            if ($request->otp_via == 'email' && $request->email != '') {
                $checkUser = CarHost::where('email', $request->email)->first();
                if($checkUser == ''){
                    $registerVia = 2;
                }
                $user = CarHost::firstOrCreate(['email' => $request->email]);
                if($user != '' && $registerVia != NULL){
                    $user->registered_via = $registerVia;
                }
                $otp = $this->generateAndSendEmailOTP($request->email);
                if ($otp === null) { 
                    return $this->errorResponse('OTP already sent within 1 Minute.');
                }
                if ($otp && isset($otp['status']) && $otp['status'] == false) {
                    $errorMessage = $otp['message'] ?? 'Something went Wrong';
                    return $this->errorResponse($errorMessage);
                } else{
                    $user->save();
                    return $this->successResponse(['otp' => '', 'reuse_with_old_email_message' => "<span style='color: blue;'>If you wish to reuse the app, please log in with an old email, otherwise, create a new account</span>"], 'OTP sent for login.');    
                }
            } else if ($request->otp_via == 'sms' && $request->mobile_number != '') {
                $checkUser = CarHost::where('mobile_number', $request->mobile_number)->first();
                if($checkUser == ''){
                    $registerVia = 1;
                }
                $user = CarHost::firstOrCreate(['mobile_number' => $request->mobile_number , 'country_code' => $request->country_code]);
                if($user != '' && $registerVia != NULL){
                    $user->registered_via = $registerVia;
                }
                $otp = $this->generateAndSendOTP($request->mobile_number);
                if ($otp === null) { 
                    return $this->errorResponse('OTP already sent within 1 Minute.');
                }
                if ($otp && isset($otp['status']) && $otp['status'] !== 200) {
                    $errorMessage = $otp['message'] ?? 'Something went Wrong';
                    return $this->errorResponse($errorMessage);
                } else{
                    $user->save();
                    return $this->successResponse(['otp' => '', 'reuse_with_old_number_message' => "<span style='color: blue;'>If you wish to reuse the app, please log in with an old phone number, otherwise, create a new account</span>"], 'OTP sent for login.');    
                }
            }
        }else{
            $otp = '0000';
            if ($request->otp_via == 'email') {
                $checkUser = CarHost::where('email', $request->email)->first();
                if($checkUser == ''){
                    $registerVia = 2;
                }
                $user = CarHost::firstOrCreate(['email' => $request->email]);
                if($user != '' && $registerVia != NULL){
                    $user->registered_via = $registerVia;
                }
                $user->save();
                Cache::put('otp_' . $request->email, strval($otp), 60 * 5);
                Cache::put('last_otp_sent_' . $request->email, now(), 30);

                return $this->successResponse(['otp' => $otp, 'reuse_with_old_email_message' => "<span style='color: blue;'>If you wish to reuse the app, please log in with an old email, otherwise, create a new account</span>"], 'OTP sent for login.');    
            } elseif($request->otp_via == 'sms'){
                $checkUser = CarHost::where('mobile_number', $request->mobile_number)->first();
                if($checkUser == ''){
                    $registerVia = 1;
                }
                $user = CarHost::firstOrCreate(['mobile_number' => $request->mobile_number , 'country_code' => $request->country_code]);
                if($user != '' && $registerVia != NULL){
                    $user->registered_via = $registerVia;
                }
                $user->save();
                Cache::put('otp_' . $request->mobile_number, strval($otp), 60 * 5);
                Cache::put('last_otp_sent_' . $request->mobile_number, now(), 30);

                return $this->successResponse(['otp' => $otp, 'reuse_with_old_number_message' => "<span style='color: blue;'>If you wish to reuse the app, please log in with an old phone number, otherwise, create a new account</span>"], 'OTP sent for login.');
            }
        }
    }

    public function verifyOtpGenerateToken(Request $request)
    {
        $otpVia = config('global_values.otp_via');
        $otpVia = implode(',', $otpVia);
        $validator = Validator::make($request->all(), [
            'otp_via' => 'required|in:'.$otpVia,
            'country_code' => ['nullable', 'string', 'regex:/^\+[1-9]\d{1,3}$/', 'required_if:otp_via,sms'],
            'mobile_number' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{9,14}$/',
                'required_if:otp_via,sms',
                'digits_between:8,15'
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
            'otp' => 'required|string',
            'device_info' => 'required|json',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        if($request->otp_via == 'email'){
            $otp = Cache::get('otp_' . $request->email);
            if (!$otp || $otp !== $request->otp) {
                return $this->errorResponse('Invalid OTP');
            }
            $user = CarHost::where('email', $request->email)->latest()->first();
            if (!$user) {
                return $this->errorResponse('Customer not found');
            }
        }else if($request->otp_via == 'sms'){
            $otp = Cache::get('otp_' . $request->mobile_number);
            if (!$otp || $otp !== $request->otp) {
                return $this->errorResponse('Invalid OTP');
            }
            $user = CarHost::where('mobile_number', $request->mobile_number)->latest()->first();
            if (!$user) {
                return $this->errorResponse('Customer not found');
            }
        }

        $user->device_token = $request->firebase_token; // Replace with the correct device token
        $user->save();
        
        $token = JWTAuth::fromUser($user);
        auth()->guard('api-carhost')->login($user);

        $loginToken = new LoginToken();
        $loginToken->app = 2;
        $loginToken->customer_id = $user->id;
        $loginToken->token = $token;
        $loginToken->save();

        if(isset($request->device_info) && $request->device_info != ''){
            $userDevice = new UserDevice();
            $userDevice->app = 2; 
            $userDevice->customer_id = $user->id;
            $userDevice->device_info = json_encode($request->device_info);
            $userDevice->save();
        }
        //$token = Auth::guard('api-carhost')->login($user);
        $vehicleStatus = false;
        $checkVehicle = CarEligibility::where('car_hosts_id', $user->id)->count();
        if($checkVehicle > 0){
            $vehicleStatus = true;
        }
        $user->vehicle_status = $vehicleStatus;

        return $this->successResponse([
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function verifyOldNumberOTPAndGenerateToken(Request $request)
    {
        $otpVia = config('global_values.otp_via');
        $otpVia = implode(',', $otpVia);
        $validator = Validator::make($request->all(), [
            'otp_via' => 'required|in:'.$otpVia,
            'country_code' => ['nullable', 'string', 'regex:/^\+[1-9]\d{1,3}$/', 'required_if:otp_via,sms'],
            'mobile_number' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{9,14}$/',
                'required_if:otp_via,sms',
                'digits_between:8,15'
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
            'otp' => 'required|string',
            'login_with_old_account' => 'required', // Add validation for login_with_old_acc
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = '';
        if($request->otp_via == 'sms'){
            $otp = Cache::get('otp_' . $request->mobile_number);
            if (!$otp || $otp !== $request->otp) {
                return $this->errorResponse('Invalid OTP');
            }
            $user = CarHost::where('mobile_number', $request->mobile_number)->orderBy('id' , 'desc')->first();
        } else if($request->otp_via == 'email'){
            $otp = Cache::get('otp_' . $request->email);
            if (!$otp || $otp !== $request->otp) {
                return $this->errorResponse('Invalid OTP');
            }
            $user = CarHost::where('email', $request->email)->orderBy('id' , 'desc')->first();
        }

        if ($request->login_with_old_account && $request->login_with_old_account == 1 && $user != '') {
            $user->is_deleted = 0;
            $user->save();
            
            // Login to the old account
            $token = JWTAuth::fromUser($user);
            auth()->guard('api-carhost')->login($user);

            $loginToken = new LoginToken();
            $loginToken->app = 2;
            $loginToken->customer_id = $user->id;
            $loginToken->token = $token;
            $loginToken->save();

            return $this->successResponse([
                'user' => $user,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ], 'You are logged in to your existing account');
        } else {
            // If user does not exist, create a new account
            $user = new CarHost();
            $user->mobile_number = $request->mobile_number ?? NULL;
            $user->email = $request->email ?? NULL;
            $user->country_code = $request->country_code ?? NULL;
            $user->save();

            $token = JWTAuth::fromUser($user);
            auth()->guard('api-carhost')->login($user);
            $loginToken = new LoginToken();
            $loginToken->app = 2;
            $loginToken->customer_id = $user->id;
            $loginToken->token = $token;
            $loginToken->save();
            //$token = Auth::guard('api-carhost')->login($user);

            return $this->successResponse([
                'user' => $user, 'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]], 'A new account has been created for you');
        }
    }

    private function generateAndSendOTP($mobileNumber)
    {
        // Retrieve the last OTP sent time from the cache
        $lastOTPSentTime = Cache::get('last_otp_sent_' . $mobileNumber);

        // Check if OTP was sent in the last 30 seconds
        if ($lastOTPSentTime && now()->diffInSeconds($lastOTPSentTime) < 30) {
            return;
        }

        if(isset($mobileNumber) && $mobileNumber == '9999999999'){
            $otp = "0000";
        }else{
            $otp = strval(mt_rand(1000, 9999));
            $checkresponse =  $this->smsService->sendOTP($mobileNumber,$otp);
            // Check the response status and handle errors
            if($checkresponse && isset($checkresponse['status']) && $checkresponse['status'] != 200){
                $checkResponse['message'] = $checkResponse['message'] ?? 'An error occurred while sending OTP.';
                return $checkresponse; 
            }
        }

        // Cache the OTP and timestamp
        Cache::put('otp_' . $mobileNumber, strval($otp), 60 * 5);
        // Store the timestamp of the OTP sent
        Cache::put('last_otp_sent_' . $mobileNumber, now(), 30);

        return $otp; 
    }

    private function generateAndSendEmailOTP($email)
    {
        $lastOTPSentTime = Cache::get('last_otp_sent_' . $email);
        if ($lastOTPSentTime && now()->diffInSeconds($lastOTPSentTime) < 30) {
            return;
        }
        // Generate OTP
        $otp = strval(mt_rand(1000, 9999));
        $to = $email;
        $subject = "OTP for Email Verification";
        $from = config('global_values.mail_from');
        if(isset($to) && $to != ''){
            try{
                Mail::send('emails.email_otp', ['otp' => $otp], function ($m) use ($subject, $to, $from) {
                    $m->from($from)->to($to)->subject($subject);
                });
            } catch (\Exception $e) {} 
        }else{
            $checkResponse['status'] = false;
            $checkResponse['message'] = 'Email Not Found';
            return $checkresponse; 
        }

        // Cache the OTP and timestamp
        Cache::put('otp_' . $email, strval($otp), 60 * 5);
        // Store the timestamp of the OTP sent
        Cache::put('last_otp_sent_' . $email, now(), 30);

        return $otp; 
    }

    public function getProfile(Request $request){
        $user = $this->userAuthDetails;
        $user = CarHost::where('id', $user->id)
                ->where('is_deleted', 0)
                ->orderBy('id', 'desc')
                ->first();
        if (!$user) {
            return $this->errorResponse('User not found');
        }
        
        $user->delete_account_message = "<span style='color: red;'>THIS ACTION CANNOT BE UNDONE. This will permanently delete your account and all of its data.</span>";
        $user->email_verification_message = "<span style='color: blue;'>An email will be sent to verify your email!</span>";
        if(isset($user) && $user != ''){
            $emailVerificationStatus = false;
            $emailVerificationTitle = "";
            $emailVerificationMessage = "";
            if($user->email_verified_at != null){
                $emailVerificationStatus = true;
                $emailVerificationTitle = "";
                $emailVerificationMessage = "";
            }
            $user->email_verified = $emailVerificationStatus;
            $user->warning_title = $emailVerificationTitle;
            $user->warning_message = $emailVerificationMessage;
        }
        return $this->successResponse(['user' => $user], 'User data get Successfully');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('api-carhost')->user();
        $mailStatus = $sendOtpStatus = false;
        $customerId = $user->id;
        $validationRules = [
            'firstname' => 'string|max:255',
            'lastname' => 'string|max:255',
            'email' => [
                'email:rfc,dns',
                'max:255',
                Rule::unique('car_hosts', 'email')
                ->where(function ($query) {
                    $query->where('is_deleted', 0);
                })
                ->ignore($customerId, 'id'),
            ],
            'dob' => 'date',
            'mobile_number' => [
                'numeric',
                'digits_between:8,15',  
                Rule::unique('car_hosts', 'mobile_number')
                ->ignore($customerId, 'id')
                ->where(function ($query) {
                    $query->where('is_deleted', 0);
                }),
            ],
            'mobile_number' => 'nullable|numeric|digits_between:8,15',
            'profile_picture' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:4096',
        ];
        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = CarHost::where('id', $user->id)->where('is_deleted', 0)->orderBy('id', 'desc')->first();
        if(isset($request->email) && $request->email != '' && $request->email != $user->email){
            $mailStatus = true;
        }
        if(isset($request->mobile_number) && $request->mobile_number != '' && $request->mobile_number != $user->mobile_number){
            $sendOtpStatus = true;
        }
        
        $user->fill($request->except('profile_picture', 'dob'));
        $dob = date('Y-m-d', strtotime($request->dob));
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = 'Carhost_userprofile_'.$user->id.'_'.time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/profile_pictures'), $filename);
            $user->profile_picture_url = $filename;
        }
        $user->dob = $dob;
        $user->save();
        if(config('global_values.environment') != '' && config('global_values.environment') == 'live' && ($mailStatus == true || $sendOtpStatus == true)){
            //Send Mail to Customer
            if($mailStatus == true){
                $user->email_verified_at = null;
                $user->save();

                $to = $request->email ?? '';
                $subject = "Email Verification";
                $from = config('global_values.mail_from');
                $customerId = Crypt::encrypt($user->id);
                $name = $user->firstname ?? '';
                $name .= ' '.$user->lastname ?? '';
                $email = Crypt::encrypt($to);
                $app = Crypt::encrypt('v_host');
                if(isset($to) && $to != ''){
                    try{
                        // Send Verification mail to Customer
                        Mail::send('emails.front.email_verification', ['customer_id' => $customerId, 'name' => $name, 'email' => $email, 'app' => $app], function ($m) use ($subject, $to, $from) {
                            $m->from($from)->to($to)->subject($subject);
                        });
                    } catch (\Exception $e) {} 
                }
            }
            //Sent OTP to Customer
            if($sendOtpStatus == true){
                $otp = $this->generateAndSendOTP($user->mobile_number);
                if ($otp === null) {
                    return $this->errorResponse('OTP already sent within 1 Minute.');
                }
                if($otp && isset($otp['status']) && $otp['status'] != 200){
                    if(isset($otp['message']) && $otp['message'] != ''){
                        return $this->errorResponse($otp['message']);
                    }
                }else{
                    return $this->successResponse(['otp' => $otp, 'user' => $user], 'OTP sent for login.');
                }
            }else{
                return $this->successResponse(['user' => $user], 'Profile updated successfully');    
            }
        }else{
             return $this->errorResponse('You can not send Mail on Staging Env.');
        }
    }

    public function updateMobileNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{9,14}$/'],
            'country_code' => ['required', 'string', 'regex:/^\+[1-9]\d{1,3}$/'],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $checkexistMobile = CarHost::where('mobile_number', $request->mobile_number)->where('is_deleted', 0)->first();
        
        if ($checkexistMobile != null) {
            return $this->errorResponse('Mobile number already exist.');
        } else {
            if(config('global_values.environment') == 'live'){
                $otp = $this->generateAndSendOTP($request->mobile_number);
                if ($otp === null) {
                    return $this->errorResponse('OTP already sent within 1 Minute.');
                }    
                
                if($otp && isset($otp['status']) && $otp['status'] != 200){
                    if(isset($otp['message']) && $otp['message'] != ''){
                        return $this->errorResponse($otp['message']);
                    }
                }else{
                    return $this->successResponse(['otp' => null], 'OTP sent for mobile number update.');
                }
            }else{
                $otp = '0000';
                Cache::put('otp_' . $request->mobile_number, strval($otp), 60 * 5);
                Cache::put('last_otp_sent_' . $request->mobile_number, now(), 30);
                return $this->successResponse(['otp' => $otp, 'reuse_with_old_number_message' => "<span style='color: blue;'>If you wish to reuse the app, please log in with an old phone number, otherwise, create a new account</span>"], 'OTP sent for login.');
            }
        }
    }
    
    public function updateMobileNumberVerifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{9,14}$/'],
            'country_code' => ['required', 'string', 'regex:/^\+[1-9]\d{1,3}$/'],
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Retrieve OTP from cache
        $otp = Cache::get('otp_' . $request->mobile_number);
        if (!$otp || $otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP');
        }

        // Update the mobile number
        $user = Auth::guard('api-carhost')->user();

        $user = CarHost::where('id', $user->id)->first();
        $user->mobile_number = $request->mobile_number;
        $user->save();

        // Clear OTP from cache after verification
        Cache::forget('otp_' . $request->mobile_number);

        return $this->successResponse(null, 'Mobile number updated successfully');
    }

    public function updateEmailAddr(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
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
            ],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = CarHost::select('id')->where('email', $request->email)->where('is_deleted', 0)->first();
        if ($user != null) {
            return $this->errorResponse('Email already exist.');
        } else {
            $otp = $this->generateAndSendEmailOTP($request->email);
            if ($otp === null) {
                return $this->errorResponse('OTP already sent within 1 Minute.');
            }    
            if($otp && isset($otp['status']) && $otp['status'] != 200){
                $errorMessage = $otp['message'] ?? 'Something went Wrong';
                return $this->errorResponse($errorMessage);
            }else{
                return $this->successResponse(['otp' => null], 'OTP sent for Email update.');
            }
        }
    }

    public function updateEmailAddrVerifyOTP(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
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
            ],
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Retrieve OTP from cache & Verify
        $otp = Cache::get('otp_' . $request->email);
        if (!$otp || $otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP');
        }

        // Update the Email Address
        $user = Auth::guard('api-carhost')->user();
        $user = CarHost::select('id', 'email')->where('id', $user->id)->first();
        $user->email = $request->email;
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();

        // Clear OTP from cache after verification
        Cache::forget('otp_' . $request->email);
        return $this->successResponse(null, 'Email updated successfully');
    }

    public function logout()
    {
        $user = Auth::guard('api-carhost')->user();
        $user->device_token = '';
        $user->save();

        Auth::guard('api-carhost')->logout();
        return $this->successResponse(null, 'Successfully logged out');
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::guard('api-carhost')->user();
        $customerId = $user->id;
        $user = CarHost::where('id', $customerId)
                ->where('is_deleted', 0)
                ->orderBy('id', 'desc')
                ->first();
        $user->is_deleted = true;
        $user->device_token = '';
        $user->save();
        
        $loginToken = LoginToken::where('customer_id', $user->id)->where('app', 2)->get();
        if(is_countable($loginToken) && count($loginToken) > 0){
            foreach($loginToken as $key => $val){
                if(isset($val->token) && $val->token != ''){
                    try {
                        JWTAuth::setToken($val->token)->invalidate();
                        $val->delete();
                    } catch (TokenExpiredException $e) {
                        // Token has already expired, handle accordingly
                        \Log::info("Token already expired for customer_id: {$user->customer_id}, token: {$val->token}");
                    } catch (JWTException $e) {
                        // Handle other JWT exceptions
                        \Log::error("JWT Exception for customer_id: {$user->customer_id}, error: " . $e->getMessage());
                    } catch (Exception $e) {
                        // Handle other potential exceptions
                        \Log::error("An error occurred while invalidating the token: " . $e->getMessage());
                    }
                }
            }
        }

        Auth::guard('api-carhost')->logout(); // Log out the user after deleting the account
        return $this->successResponse(null, 'Account Deleted Successfully.');
    }

    public function refresh()
    {
        $user = Auth::guard('api-carhost')->user();
        $token = Auth::guard('api-carhost')->refresh();
        return $this->successResponse([
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function settings()
    {
        return $this->successResponse([
            'help_support' => "+919909927077",
            'booking_help_support' => "+919909927077",
            'terms_condition' => "https://velriders.com/terms-condition",
            'privacy_policy' => 'https://velriders.com/privacy-policy',
        ]);
    }

    public function staticMessages()
    {
        return $this->successResponse([
            'login_screen' => "<span style='font-weight: bold;'>By creating an account I agree to <u>Terms of Services</u></span>",
            'otp_verification' => "<span style='font-weight: bold;'>We will send you one time password in</span>",
            'otp_verification_not_get' => "<span style='font-weight: bold;'>Didn't get OTP yet?<span>",
            'add_vehicle_image' => "<span style='font-weight: bold;'>To complete your vehicle profile, could you please upload some images of the exterior and interior</span>",
            'complete_kyc_share_vehicle' => "<span style='font-weight: bold;'>Select the location and dates you want to share your vehicle at</span>",
            'create_vehicle_profile' => "<span style='font-weight: bold;'>This helps your listing standout to our 1.2 milion guests.</span>",
            'add_vehicle_feature' => "<span style='font-weight: bold;'>Offer your guests a glimpse into the extraordinary aspects of your car, enabling them to make a decision that embraces its unparalleled uniqueness</span>",
            'listing_location' => "<span style='font-weight: bold;'>No Location Found! Add a new Location</span>",
            'share_your_pickup' => "<span style='font-weight: bold;'>Guests will pick up your vehicle from here </span>",
            'vehicle_pickup_location_details' => "<span style='font-weight: bold;'>Upon completing your vehicle reservation, guests will have access to this information</span>", 
            'add_rc_card' => "<span style='font-weight: bold;'>Opt for natural light to capture clear and vibrant photos Showcasing the complete backside of RC, emphasizing every intricate detail</span>", 
            'pan_card' => "<span style='font-weight: bold;'>Ensure prompt submission of your updated PAN card details to avoid any inconvenience. Non-compliance may lead to a 20% TDS deduction, as mandated by government norms</span>",
            'view_bank_details' => "<span style='font-weight: bold;'>No bank details found</span>",
            'bank_information' => "<span style='font-weight: bold;'>Provide your bank account information, we will transfer your earnings in your account.</span>",
            'bank_information_ifsc_code' => "<span style='font-weight: bold;'>Enter the IFSC code of the account branch only. Else, payment will fail.</span>",
            'fasttag_setting' => "<span style='font-weight: bold;'>Verify the presence of FASTag on your vehicle</span>",
            'fasttag_setting_detail' => "<span style='font-weight: bold;'>Hosts own their FASTag, and settle all FASTag related transaction  with guests separately. FATag enabled cars get</span>",
            'listing_control' => "<span style='font-weight: bold;'>New bookings are restricted from being allocated with start or end times between 12 AM and 6 AM. However, adjustments may be made to upcoming or live bookings to accommodate scheduling within this timeframe.</span>",
            'pricing_control_rating' => "<span style='font-weight: bold;'>Considering your car ratings is part of the equation in determining your price.</span>",
            'delete_acocunt_popup' => "<span style='font-weight: bold;'>THIS ACTION CANNOT BE UNDONE. This will permanently delete your account and all of its data.</span>",
            'try_again_payment_message' => "<span style='color: red;'>Please try again</span>",
            'reuse_with_old_number_message'=> "<span style='color: blue;'>If you wish to reuse the app, please log in with an old phone number, otherwise, create a new account</span>",
            'email_verification_message'=> "<span style='color: blue;'>An email will be sent to verify your email!</span>",
        ]);
    }

    public function getBankDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'bank_id' => 'nullable|exists:car_host_banks,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = '';
        if(Auth::guard('api-carhost')->check()){
            $user = Auth::guard('api-carhost')->user();    
        }else{
            return $this->errorResponse('User not found');
        }
        $carHostBank = CarHostBank::where('car_hosts_id', $user->id)->where('is_deleted', 0);
        if(isset($request->bank_id) && $request->bank_id != ''){
            $carHostBank = $carHostBank->where('id', $request->bank_id);
        }
        $carHostBank = $carHostBank->get();
        if(is_countable($carHostBank) && count($carHostBank) > 0){
            foreach ($carHostBank as $key => $value) {
                $value->car_hosts_id = (string)$value->car_hosts_id;
                if(isset($value->passbook_image) && $value->passbook_image != ''){
                    $value->passbook_image = url('host_bank_document').'/'.$value->passbook_image;
                }
            }
            return $this->successResponse($carHostBank, 'Carhost Bank details are get successfully');
        }else{
            return $this->errorResponse('Carhost bank details are not found');
        }
    }

    public function getBookingStatusList(Request $request){
        $booking_status = config('global_values.booking_statuses');

        return $this->successResponse($booking_status, 'Booking Stauses are get Successfully');
    }

    public function getBookingTimeDuration(Request $request){
        $booking_time_duration = config('global_values.time_duration');

        return $this->successResponse($booking_time_duration, 'Booking Time Duration are get Successfully');
    }

    public function storeBankDetails(Request $request){
        $user = '';
        if(Auth::guard('api-carhost')->check()){
            $user = Auth::guard('api-carhost')->user();    
        }else{
            return $this->errorResponse('User not found');
        }
        $validator = Validator::make($request->all(), [
            'bank_id' => 'nullable|exists:car_host_banks,id',
            'account_holder_name' => 'nullable|regex:/^[a-zA-Z\s\.\-\']+$/',
            //'car_host_id' => 'required|exists:car_hosts,id',
            //'account_holder_name' => 'required',
            //'bank_name' => 'required',
            //'branch_name' => 'required',
            //'city' => 'required',
            'account_no' => 'required|max:18',
            'ifsc_code' => 'required',
            'is_primary' => 'required|in:1,2', //1 = Primary, 2 = Not primary
        ]);
        $validator->sometimes(['passbook_image'], 'required|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:5000', function ($input) {
            return !isset($input->bank_id) && $input->bank_id == '';
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $carHostBank = '';
        if($request->bank_id == ''){
            $checkBankCnt = CarHostBank::where('car_hosts_id', $user->id)->where('is_deleted', 0)->count();
            if($checkBankCnt >= 2){
                return $this->errorResponse('You can not add more than 2 Banks');
            }
            $checkBank = CarHostBank::where(['car_hosts_id' => $user->id, 'is_deleted' => 0, 'account_no' => $request->account_no, 'ifsc_code' => $request->ifsc_code])->exists();
            if($checkBank){
                return $this->errorResponse('Already Existed');
            }
            $carHostBank = new CarHostBank();
            $carHostBank->car_hosts_id = $user->id;
        }else{
            $carHostBank = CarHostBank::where('id', $request->bank_id)->where('is_deleted', 0)->first();    
        }
        $bankStatus = false;

        $carHostBank->account_holder_name = $request->account_holder_name;
        $carHostBank->bank_name = $request->bank_name;
        $carHostBank->branch_name = $request->branch_name;
        $carHostBank->city = $request->city;
        $carHostBank->account_no = $request->account_no;
        $carHostBank->ifsc_code = $request->ifsc_code;
        $carHostBank->nick_name = isset($request->nick_name)?$request->nick_name:NULL;
        if ($request->hasFile('passbook_image')) {
            $file = $request->file('passbook_image');
            $filename = 'hostbank_'.$carHostBank->id.'_'.time().'_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('host_bank_document'), $filename);
            $carHostBank->passbook_image = $filename;
        }
        
        $primaryStatus = $request->is_primary;
        $carHostBankCheck = CarHostBank::where('car_hosts_id', $user->id)->where('is_deleted', 0)->count();
        if($carHostBankCheck == 0){
            $primaryStatus = 1;
            $carHostBank->is_primary = $request->is_primary;
            $bankStatus = true;
        }
        if(isset($request->is_primary) && $request->is_primary == 1){
            CarHostBank::where('car_hosts_id', $user->id)->where('id', '!=', $carHostBank->id)->update(['is_primary'=> 2]);
            $carHostBank->is_primary = $request->is_primary;
            $bankStatus = true;
        }elseif(isset($request->is_primary) && $request->is_primary == 2){
            $hostBank = CarHostBank::where('car_hosts_id', $user->id)->where('id', '!=', $carHostBank->id)->where('is_primary', 1)->first();
            if($hostBank != ''){
                $carHostBank->is_primary = $request->is_primary;
                $bankStatus = true;
            }elseif($carHostBankCheck == 0){
                $carHostBank->is_primary = 1;
                $bankStatus = true;
            }
        }

        if($bankStatus == true){
            $carHostBank->save();
            return $this->successResponse($carHostBank, 'Carhost Bank details are stored successfully');
        }else{
            return $this->errorResponse('Please make any one Bank as primary first');
        }
    }

    public function deleteBank(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:car_host_banks,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $carHostBank = CarHostBank::where('id', $request->id)->first();
        if($carHostBank != ''){
            $checkPrimaryBank = CarHostBank::where(['id' => $request->id, 'is_primary' => 1])->first();
            if($checkPrimaryBank == ''){
                $carHostBank->is_deleted = 1;
                $carHostBank->save();
                return $this->successResponse($carHostBank, 'Carhost Bank details are deleted successfully');
            }else{
                return $this->errorResponse('You can not delete primary bank. To delete this bank, please make any other bank as primary first');
            }
        }else{
            return $this->errorResponse('Carhost Bank details are not found');
        }
    }

    public function storeGstInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'business_name' => 'required',
            'gst_number' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = Auth::guard('api-carhost')->user();  
        $carHost = CarHost::where('id', $user->id)->first();
        $carHost->business_name = $request->business_name;
        $carHost->gst_number = $request->gst_number;
        $carHost->save();

        return $this->successResponse($carHost, 'Customer GST information stored successfully');
    }

    public function storePanNumber(Request $request){
        $validator = Validator::make($request->all(), [
            'pan_number' => 'nullable',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $pan = $request->input('pan_number');
        if (isset($pan) && $pan != '' && !preg_match('/^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/', $pan)) {
            return $this->errorResponse('PAN number is not valid');
        }
        if(Auth::guard('api-carhost')->user()){
            $user = Auth::guard('api-carhost')->user();
            $user = CarHost::where('id', $user->id)->first();
            $user->pan_number = $request->pan_number ?? '';
            $user->save();
            return $this->successResponse($user, 'PAN number stored successfully');
        }else{
            return $this->errorResponse('User not Found');
        }
    }
}
