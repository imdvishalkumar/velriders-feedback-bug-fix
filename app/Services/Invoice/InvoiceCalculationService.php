<?php

namespace App\Services\Invoice;

use App\Models\{RentalBooking, BookingTransaction, Vehicle};
use Illuminate\Support\Facades\Log;

/**
 * InvoiceCalculationService
 * 
 * BUSINESS RULES:
 * 1. Grand Total / Amount Paid is the SINGLE source of truth.
 * 2. Discount applies ONLY to Booking charges.
 * 3. Discount is applied BEFORE GST.
 * 4. Late Return is a separate taxable item (5% GST).
 * 5. Vehicle Service Fee percentage is retrieved from DB (vehicle->commission_percent).
 * 6. Vehicle Service Fee GST is ALWAYS 18%.
 * 7. Convenience Fee is fixed: Base = 83.90, GST @18% = 15.10, Total = 99.00
 * 8. All calculations must use FULL precision internally.
 * 9. ROUND to 2 decimals ONLY at display time.
 * 10. The sum of displayed line items MUST match Grand Total exactly.
 * 11. Do NOT derive GST from stored tax_amt values - calculate fresh.
 */
class InvoiceCalculationService
{
    // GST Rates
    const BOOKING_GST_RATE = 0.05;           // 5% for B2C (no GST number)
    const BOOKING_GST_RATE_B2B = 0.18;       // 18% for B2B (with GST number)
    const VEHICLE_SERVICE_GST_RATE = 0.18;   // 18% always
    const CONVENIENCE_FEE_GST_RATE = 0.18;   // 18% always
    const LATE_RETURN_GST_RATE = 0.05;       // 5% for late return

    // Fixed Convenience Fee values
    const CONVENIENCE_FEE_TOTAL = 99.00;
    const CONVENIENCE_FEE_BASE = 83.8983050847;  // 99 / 1.18 = ~83.90 (keep precision)
    const CONVENIENCE_FEE_GST = 15.1016949153;   // 99 - 83.8983 = ~15.10 (keep precision)

    /**
     * Calculate all invoice line items using reverse calculation from Grand Total
     *
     * @param int $bookingId
     * @return array
     */
    public function calculateInvoiceData(int $bookingId): array
    {
        $booking = RentalBooking::with(['vehicle', 'customer'])->find($bookingId);
        
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        $transactions = BookingTransaction::where('booking_id', $bookingId)->get();
        $vehicleServicePercent = $this->getVehicleServicePercent($booking);
        $bookingGstRate = $this->getBookingGstRate($booking);
        
        // Initialize accumulators with full precision
        $lineItems = [];
        $gstSummary = $this->initializeGstSummary();
        $grandTotalAccumulator = 0.0;
        $amountDueAccumulator = 0.0;

        // Process each transaction type
        foreach ($transactions as $transaction) {
            $result = $this->processTransaction(
                $transaction,
                $vehicleServicePercent,
                $bookingGstRate,
                $booking
            );
            
            if ($result) {
                $lineItems = array_merge_recursive($lineItems, $result['lineItems']);
                $gstSummary = $this->mergeGstSummary($gstSummary, $result['gstSummary']);
                $grandTotalAccumulator += $result['paidTotal'];
                $amountDueAccumulator += $result['dueTotal'];
            }
        }

        // Apply reconciliation to ensure amounts match exactly
        $reconciled = $this->reconcileToGrandTotal(
            $lineItems,
            $gstSummary,
            $grandTotalAccumulator,
            $amountDueAccumulator
        );

        return $reconciled;
    }

    /**
     * Process a single transaction and return calculated line items
     */
    private function processTransaction(
        BookingTransaction $transaction,
        float $vehicleServicePercent,
        float $bookingGstRate,
        RentalBooking $booking
    ): ?array {
        $result = [
            'lineItems' => [],
            'gstSummary' => $this->initializeGstSummary(),
            'paidTotal' => 0.0,
            'dueTotal' => 0.0,
        ];

        switch ($transaction->type) {
            case 'new_booking':
                if ($transaction->paid) {
                    $result = $this->calculateNewBooking($transaction, $vehicleServicePercent, $bookingGstRate);
                }
                break;

            case 'extension':
                if ($transaction->paid) {
                    $result = $this->calculateExtension($transaction, $vehicleServicePercent, $bookingGstRate);
                }
                break;

            case 'completion':
                if ($transaction->paid) {
                    $result = $this->calculateCompletion($transaction, $vehicleServicePercent, $bookingGstRate);
                }
                break;

            case 'penalty':
                $result = $this->calculatePenalty($transaction, $vehicleServicePercent, $bookingGstRate);
                break;
        }

        return $result;
    }

    /**
     * Calculate new booking line items using reverse calculation
     * 
     * Given: Total paid amount
     * Find: Base amounts that produce the total
     */
    private function calculateNewBooking(
        BookingTransaction $transaction,
        float $vehicleServicePercent,
        float $bookingGstRate
    ): array {
        $result = [
            'lineItems' => ['newBooking' => null, 'newBookingVehicleService' => null, 'convenienceFee' => null],
            'gstSummary' => $this->initializeGstSummary(),
            'paidTotal' => 0.0,
            'dueTotal' => 0.0,
        ];

        // Get values from transaction
        $totalPaid = $transaction->total_amount ?? 0;
        $convenienceFee = $transaction->convenience_fee ?? 0;
        $discount = $transaction->coupon_discount ?? 0;
        $timestamp = $transaction->start_date && $transaction->end_date 
            ? date('d-m-Y H:i', strtotime($transaction->start_date)) . ' - ' . date('d-m-Y H:i', strtotime($transaction->end_date))
            : '';

        // Remove convenience fee from calculation
        $amountExcludingConvenience = $totalPaid - $convenienceFee;

        // Calculate convenience fee components (fixed values)
        if ($convenienceFee > 0) {
            $cfBase = $convenienceFee / (1 + self::CONVENIENCE_FEE_GST_RATE);
            $cfGst = $convenienceFee - $cfBase;
            
            $result['lineItems']['convenienceFee'] = [
                'base' => $cfBase,
                'gst_rate' => self::CONVENIENCE_FEE_GST_RATE * 100,
                'gst_amount' => $cfGst,
                'discount' => 0.0,
                'total' => $convenienceFee,
            ];
            
            $result['gstSummary'][18]['rate'] += $cfBase;
            $result['gstSummary'][18]['tax'] += $cfGst;
        }

        // Solve for Booking Base (B) using reverse calculation
        // Equation: (B - discount) * (1 + bookingGstRate) + (B * vehicleServicePercent/100) * (1 + VEHICLE_SERVICE_GST_RATE) = amountExcludingConvenience
        // Simplify: B * [(1 + bookingGstRate) + (vehicleServicePercent/100 * 1.18)] - discount * (1 + bookingGstRate) = amountExcludingConvenience
        
        $bookingMultiplier = 1 + $bookingGstRate;
        $vehicleServiceMultiplier = ($vehicleServicePercent / 100) * (1 + self::VEHICLE_SERVICE_GST_RATE);
        $totalMultiplier = $bookingMultiplier + $vehicleServiceMultiplier;
        
        // B = (amountExcludingConvenience + discount * bookingMultiplier) / totalMultiplier
        $bookingBase = ($amountExcludingConvenience + ($discount * $bookingMultiplier)) / $totalMultiplier;
        
        // Calculate booking line item
        $bookingTaxable = $bookingBase - $discount;
        $bookingGst = $bookingTaxable * $bookingGstRate;
        $bookingTotal = $bookingTaxable + $bookingGst;
        
        $result['lineItems']['newBooking'] = [
            'base' => $bookingTaxable,  // Rate after discount
            'gst_rate' => $bookingGstRate * 100,
            'gst_amount' => $bookingGst,
            'discount' => $discount,
            'total' => $bookingTotal,
            'timestamp' => $timestamp,
            'original_base' => $bookingBase,  // Base before discount (for reference)
        ];
        
        $gstKey = (int)($bookingGstRate * 100);
        $result['gstSummary'][$gstKey]['rate'] += $bookingTaxable;
        $result['gstSummary'][$gstKey]['tax'] += $bookingGst;

        // Calculate vehicle service fee on booking
        if ($vehicleServicePercent > 0) {
            $vsBase = $bookingBase * ($vehicleServicePercent / 100);
            $vsGst = $vsBase * self::VEHICLE_SERVICE_GST_RATE;
            $vsTotal = $vsBase + $vsGst;
            
            $result['lineItems']['newBookingVehicleService'] = [
                'base' => $vsBase,
                'gst_rate' => self::VEHICLE_SERVICE_GST_RATE * 100,
                'gst_amount' => $vsGst,
                'discount' => 0.0,
                'total' => $vsTotal,
            ];
            
            $result['gstSummary'][18]['vehicle_commission_rate'] += $vsBase;
            $result['gstSummary'][18]['vehicle_commission_tax'] += $vsGst;
        }

        $result['paidTotal'] = $totalPaid;
        
        Log::info("NEW BOOKING - Reverse Calculation:", [
            'total_paid' => $totalPaid,
            'convenience_fee' => $convenienceFee,
            'discount' => $discount,
            'calculated_booking_base' => $bookingBase,
            'booking_taxable' => $bookingTaxable,
            'booking_gst' => $bookingGst,
            'booking_total' => $bookingTotal,
            'vehicle_service_percent' => $vehicleServicePercent,
        ]);

        return $result;
    }

    /**
     * Calculate extension line items
     */
    private function calculateExtension(
        BookingTransaction $transaction,
        float $vehicleServicePercent,
        float $bookingGstRate
    ): array {
        $result = [
            'lineItems' => ['extensions' => [], 'extensionVehicleServices' => []],
            'gstSummary' => $this->initializeGstSummary(),
            'paidTotal' => 0.0,
            'dueTotal' => 0.0,
        ];

        $totalPaid = $transaction->total_amount ?? 0;
        $convenienceFee = $transaction->convenience_fee ?? 0;
        $discount = $transaction->coupon_discount ?? 0;
        $timestamp = $transaction->end_date ? date('d-m-Y H:i', strtotime($transaction->end_date)) : '';

        // Remove convenience fee
        $amountExcludingConvenience = $totalPaid - $convenienceFee;

        // Same reverse calculation as new booking
        $bookingMultiplier = 1 + $bookingGstRate;
        $vehicleServiceMultiplier = ($vehicleServicePercent / 100) * (1 + self::VEHICLE_SERVICE_GST_RATE);
        $totalMultiplier = $bookingMultiplier + $vehicleServiceMultiplier;
        
        $extensionBase = ($amountExcludingConvenience + ($discount * $bookingMultiplier)) / $totalMultiplier;
        
        $extensionTaxable = $extensionBase - $discount;
        $extensionGst = $extensionTaxable * $bookingGstRate;
        $extensionTotal = $extensionTaxable + $extensionGst;

        $result['lineItems']['extensions'][] = [
            'base' => $extensionTaxable,
            'gst_rate' => $bookingGstRate * 100,
            'gst_amount' => $extensionGst,
            'discount' => $discount,
            'total' => $extensionTotal,
            'timestamp' => $timestamp,
        ];

        $gstKey = (int)($bookingGstRate * 100);
        $result['gstSummary'][$gstKey]['rate'] += $extensionTaxable;
        $result['gstSummary'][$gstKey]['tax'] += $extensionGst;

        // Vehicle service fee
        if ($vehicleServicePercent > 0) {
            $vsBase = $extensionBase * ($vehicleServicePercent / 100);
            $vsGst = $vsBase * self::VEHICLE_SERVICE_GST_RATE;
            $vsTotal = $vsBase + $vsGst;
            
            $result['lineItems']['extensionVehicleServices'][] = [
                'base' => $vsBase,
                'gst_rate' => self::VEHICLE_SERVICE_GST_RATE * 100,
                'gst_amount' => $vsGst,
                'discount' => 0.0,
                'total' => $vsTotal,
            ];
            
            $result['gstSummary'][18]['vehicle_commission_rate'] += $vsBase;
            $result['gstSummary'][18]['vehicle_commission_tax'] += $vsGst;
        }

        // Add convenience fee if any
        if ($convenienceFee > 0) {
            $cfBase = $convenienceFee / (1 + self::CONVENIENCE_FEE_GST_RATE);
            $cfGst = $convenienceFee - $cfBase;
            $result['gstSummary'][18]['rate'] += $cfBase;
            $result['gstSummary'][18]['tax'] += $cfGst;
        }

        $result['paidTotal'] = $totalPaid;

        return $result;
    }

    /**
     * Calculate completion (late return, exceeded km, additional charges)
     */
    private function calculateCompletion(
        BookingTransaction $transaction,
        float $vehicleServicePercent,
        float $bookingGstRate
    ): array {
        $result = [
            'lineItems' => ['completion' => null, 'completionVehicleService' => null],
            'gstSummary' => $this->initializeGstSummary(),
            'paidTotal' => 0.0,
            'dueTotal' => 0.0,
        ];

        $lateReturn = $transaction->late_return ?? 0;
        $exceededKm = $transaction->exceeded_km_limit ?? 0;
        $additionalCharges = $transaction->additional_charges ?? 0;
        $taxAmt = $transaction->tax_amt ?? 0;
        $timestamp = $transaction->timestamp ? date('d-m-Y H:i', strtotime($transaction->timestamp)) : '';

        // Total additional charges before tax
        $totalAdditional = $lateReturn + $exceededKm + $additionalCharges;
        
        if ($totalAdditional <= 0) {
            return $result;
        }

        // For completion, use stored tax amount to work backwards
        // Total = Base + Base*VSPercent + Tax + VSPercent*VSTax
        // We need to solve for Base given the total amount
        
        $totalPaid = $totalAdditional + $taxAmt;
        
        // Calculate using reverse approach
        // totalPaid = base*(1 + gstRate) + base*vsPercent*(1 + vsGstRate)
        $completionGstRate = self::LATE_RETURN_GST_RATE;  // 5% for late return charges
        
        $baseMultiplier = 1 + $completionGstRate;
        $vsMultiplier = ($vehicleServicePercent / 100) * (1 + self::VEHICLE_SERVICE_GST_RATE);
        $totalMultiplier = $baseMultiplier + $vsMultiplier;
        
        $completionBase = $totalPaid / $totalMultiplier;
        $completionGst = $completionBase * $completionGstRate;
        $completionTotal = $completionBase + $completionGst;

        $result['lineItems']['completion'] = [
            'base' => $completionBase,
            'gst_rate' => $completionGstRate * 100,
            'gst_amount' => $completionGst,
            'discount' => 0.0,
            'total' => $completionTotal,
            'timestamp' => $timestamp,
            'late_return' => $lateReturn,
            'exceeded_km' => $exceededKm,
            'additional_charges' => $additionalCharges,
        ];

        $gstKey = (int)($completionGstRate * 100);
        $result['gstSummary'][$gstKey]['rate'] += $completionBase;
        $result['gstSummary'][$gstKey]['tax'] += $completionGst;

        // Vehicle service on completion
        if ($vehicleServicePercent > 0) {
            $vsBase = $completionBase * ($vehicleServicePercent / 100);
            $vsGst = $vsBase * self::VEHICLE_SERVICE_GST_RATE;
            $vsTotal = $vsBase + $vsGst;
            
            $result['lineItems']['completionVehicleService'] = [
                'base' => $vsBase,
                'gst_rate' => self::VEHICLE_SERVICE_GST_RATE * 100,
                'gst_amount' => $vsGst,
                'discount' => 0.0,
                'total' => $vsTotal,
            ];
            
            $result['gstSummary'][18]['vehicle_commission_rate'] += $vsBase;
            $result['gstSummary'][18]['vehicle_commission_tax'] += $vsGst;
        }

        $result['paidTotal'] = $totalPaid;

        return $result;
    }

    /**
     * Calculate penalty line items
     */
    private function calculatePenalty(
        BookingTransaction $transaction,
        float $vehicleServicePercent,
        float $bookingGstRate
    ): array {
        $result = [
            'lineItems' => ['paidPenalties' => [], 'paidPenaltyServices' => [], 'duePenalties' => [], 'duePenaltyServices' => []],
            'gstSummary' => $this->initializeGstSummary(),
            'paidTotal' => 0.0,
            'dueTotal' => 0.0,
        ];

        $totalAmount = $transaction->total_amount ?? 0;
        $taxAmt = $transaction->tax_amt ?? 0;
        $timestamp = $transaction->timestamp ? date('d-m-Y H:i', strtotime($transaction->timestamp)) : '';
        $isPaid = $transaction->paid == 1;
        
        if ($totalAmount <= 0) {
            return $result;
        }

        // Penalty total = totalAmount + taxAmt
        $penaltyGrandTotal = $totalAmount + $taxAmt;
        
        // Reverse calculate
        $penaltyGstRate = $bookingGstRate;  // Use same rate as booking
        
        $baseMultiplier = 1 + $penaltyGstRate;
        $vsMultiplier = ($vehicleServicePercent / 100) * (1 + self::VEHICLE_SERVICE_GST_RATE);
        $totalMultiplier = $baseMultiplier + $vsMultiplier;
        
        $penaltyBase = $penaltyGrandTotal / $totalMultiplier;
        $penaltyGst = $penaltyBase * $penaltyGstRate;
        $penaltyTotal = $penaltyBase + $penaltyGst;

        $penaltyItem = [
            'base' => $penaltyBase,
            'gst_rate' => $penaltyGstRate * 100,
            'gst_amount' => $penaltyGst,
            'discount' => 0.0,
            'total' => $penaltyTotal,
            'timestamp' => $timestamp,
        ];

        $gstKey = (int)($penaltyGstRate * 100);

        // Vehicle service on penalty
        $vsItem = null;
        if ($vehicleServicePercent > 0) {
            $vsBase = $penaltyBase * ($vehicleServicePercent / 100);
            $vsGst = $vsBase * self::VEHICLE_SERVICE_GST_RATE;
            $vsTotal = $vsBase + $vsGst;
            
            $vsItem = [
                'base' => $vsBase,
                'gst_rate' => self::VEHICLE_SERVICE_GST_RATE * 100,
                'gst_amount' => $vsGst,
                'discount' => 0.0,
                'total' => $vsTotal,
            ];
        }

        if ($isPaid) {
            $result['lineItems']['paidPenalties'][] = $penaltyItem;
            if ($vsItem) {
                $result['lineItems']['paidPenaltyServices'][] = $vsItem;
                $result['gstSummary'][18]['vehicle_commission_rate'] += $vsItem['base'];
                $result['gstSummary'][18]['vehicle_commission_tax'] += $vsItem['gst_amount'];
            }
            $result['gstSummary'][$gstKey]['rate'] += $penaltyBase;
            $result['gstSummary'][$gstKey]['tax'] += $penaltyGst;
            $result['paidTotal'] = $penaltyGrandTotal;
        } else {
            $result['lineItems']['duePenalties'][] = $penaltyItem;
            if ($vsItem) {
                $result['lineItems']['duePenaltyServices'][] = $vsItem;
            }
            $result['dueTotal'] = $penaltyGrandTotal;
        }

        return $result;
    }

    /**
     * Get vehicle service percentage from database
     */
    public function getVehicleServicePercent(RentalBooking $booking): float
    {
        $vehicle = $booking->vehicle;
        if ($vehicle) {
            return (float)($vehicle->commission_percent ?? 0);
        }
        return 0.0;
    }

    /**
     * Get booking GST rate based on customer type
     */
    public function getBookingGstRate(RentalBooking $booking): float
    {
        $customer = $booking->customer;
        if ($customer && !empty($customer->gst_number)) {
            return self::BOOKING_GST_RATE_B2B;  // 18% for B2B
        }
        return self::BOOKING_GST_RATE;  // 5% for B2C
    }

    /**
     * Initialize GST summary structure
     */
    private function initializeGstSummary(): array
    {
        return [
            5 => ['rate' => 0.0, 'tax' => 0.0, 'vehicle_commission_rate' => 0.0, 'vehicle_commission_tax' => 0.0],
            18 => ['rate' => 0.0, 'tax' => 0.0, 'vehicle_commission_rate' => 0.0, 'vehicle_commission_tax' => 0.0],
        ];
    }

    /**
     * Merge two GST summaries
     */
    private function mergeGstSummary(array $summary1, array $summary2): array
    {
        foreach ([5, 18] as $key) {
            $summary1[$key]['rate'] += $summary2[$key]['rate'];
            $summary1[$key]['tax'] += $summary2[$key]['tax'];
            $summary1[$key]['vehicle_commission_rate'] += $summary2[$key]['vehicle_commission_rate'];
            $summary1[$key]['vehicle_commission_tax'] += $summary2[$key]['vehicle_commission_tax'];
        }
        return $summary1;
    }

    /**
     * Reconcile calculated totals to match Grand Total exactly
     * Applies rounding adjustment to the largest line item
     */
    private function reconcileToGrandTotal(
        array $lineItems,
        array $gstSummary,
        float $grandTotal,
        float $amountDue
    ): array {
        // Calculate sum of all line item totals
        $calculatedTotal = $this->sumAllLineItems($lineItems);
        
        // Calculate rounding difference
        $difference = round($grandTotal, 2) - round($calculatedTotal, 2);
        
        // If difference exists, apply to booking total (largest item typically)
        if (abs($difference) > 0.001 && isset($lineItems['newBooking'])) {
            $lineItems['newBooking']['total'] += $difference;
            // Recalculate GST to maintain consistency
            // Adjustment goes to the base amount
            $gstRate = $lineItems['newBooking']['gst_rate'] / 100;
            $adjustment = $difference / (1 + $gstRate);
            $lineItems['newBooking']['base'] += $adjustment;
            $lineItems['newBooking']['gst_amount'] += ($difference - $adjustment);
        }

        return [
            'lineItems' => $lineItems,
            'gstSummary' => $gstSummary,
            'grandTotal' => $grandTotal,
            'amountDue' => $amountDue,
            'calculatedTotal' => $this->sumAllLineItems($lineItems),
        ];
    }

    /**
     * Sum all line item totals
     */
    private function sumAllLineItems(array $lineItems): float
    {
        $total = 0.0;
        
        // Single items
        $singleItems = ['newBooking', 'newBookingVehicleService', 'convenienceFee', 'completion', 'completionVehicleService'];
        foreach ($singleItems as $key) {
            if (isset($lineItems[$key]) && is_array($lineItems[$key]) && isset($lineItems[$key]['total'])) {
                $total += $lineItems[$key]['total'];
            }
        }
        
        // Array items
        $arrayItems = ['extensions', 'extensionVehicleServices', 'paidPenalties', 'paidPenaltyServices'];
        foreach ($arrayItems as $key) {
            if (isset($lineItems[$key]) && is_array($lineItems[$key])) {
                foreach ($lineItems[$key] as $item) {
                    if (isset($item['total'])) {
                        $total += $item['total'];
                    }
                }
            }
        }
        
        return $total;
    }

    /**
     * Format a number for display (2 decimal places)
     */
    public static function formatForDisplay(float $value): string
    {
        return number_format(round($value, 2), 2);
    }

    /**
     * Format line items for invoice display
     * Applies display rounding and returns formatted arrays
     */
    public function formatForInvoice(array $calculatedData): array
    {
        $formatted = [
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
            'groupedTotals' => [],
            'totalAmt' => 0,
            'amountDue' => 0,
            'rateTotal' => 0,
            'totalTax' => 0,
        ];

        $lineItems = $calculatedData['lineItems'] ?? [];
        $gstSummary = $calculatedData['gstSummary'] ?? $this->initializeGstSummary();

        // Format new booking
        if (!empty($lineItems['newBooking'])) {
            $item = $lineItems['newBooking'];
            $formatted['newBooking'] = [
                'trip_amount' => self::formatForDisplay($item['base']),
                'tax_percent' => self::formatForDisplay($item['gst_rate']),
                'tax_amount' => self::formatForDisplay($item['gst_amount']),
                'coupon_discount' => self::formatForDisplay($item['discount']),
                'total_amount' => self::formatForDisplay($item['total']),
            ];
            $formatted['newBookingTimeStamp'] = $item['timestamp'] ?? '';
            $formatted['totalAmt'] += $item['total'];
            $formatted['rateTotal'] += $item['base'];
            $formatted['totalTax'] += $item['gst_amount'];
        }

        // Format new booking vehicle service
        if (!empty($lineItems['newBookingVehicleService'])) {
            $item = $lineItems['newBookingVehicleService'];
            $formatted['newBookingVehicleServiceFees'] = [
                'trip_amount' => self::formatForDisplay($item['base']),
                'tax_percent' => self::formatForDisplay($item['gst_rate']),
                'tax_amount' => self::formatForDisplay($item['gst_amount']),
                'coupon_discount' => self::formatForDisplay(0),
                'total_amount' => self::formatForDisplay($item['total']),
            ];
            $formatted['totalAmt'] += $item['total'];
            $formatted['rateTotal'] += $item['base'];
            $formatted['totalTax'] += $item['gst_amount'];
        }

        // Format convenience fee
        if (!empty($lineItems['convenienceFee'])) {
            $item = $lineItems['convenienceFee'];
            $formatted['cFees'] = [
                'trip_amount' => self::formatForDisplay($item['base']),
                'tax_percent' => self::formatForDisplay($item['gst_rate']),
                'tax_amount' => self::formatForDisplay($item['gst_amount']),
                'coupon_discount' => self::formatForDisplay(0),
                'total_amount' => self::formatForDisplay($item['total']),
            ];
            $formatted['totalAmt'] += $item['total'];
            $formatted['rateTotal'] += $item['base'];
            $formatted['totalTax'] += $item['gst_amount'];
            $formatted['convenienceFees'] = $item['total'];
        }

        // Format extensions
        if (!empty($lineItems['extensions'])) {
            foreach ($lineItems['extensions'] as $item) {
                $formatted['extension']['trip_amount'][] = self::formatForDisplay($item['base']);
                $formatted['extension']['tax_percent'][] = self::formatForDisplay($item['gst_rate']);
                $formatted['extension']['tax_amount'][] = self::formatForDisplay($item['gst_amount']);
                $formatted['extension']['coupon_discount'][] = self::formatForDisplay($item['discount']);
                $formatted['extension']['total_amount'][] = self::formatForDisplay($item['total']);
                $formatted['extension']['timestamp'][] = $item['timestamp'] ?? '';
                $formatted['totalAmt'] += $item['total'];
                $formatted['rateTotal'] += $item['base'];
                $formatted['totalTax'] += $item['gst_amount'];
            }
        }

        // Format extension vehicle services
        if (!empty($lineItems['extensionVehicleServices'])) {
            foreach ($lineItems['extensionVehicleServices'] as $item) {
                $formatted['extensionVehicleServiceFees']['trip_amount'][] = self::formatForDisplay($item['base']);
                $formatted['extensionVehicleServiceFees']['tax_percent'][] = self::formatForDisplay($item['gst_rate']);
                $formatted['extensionVehicleServiceFees']['tax_amount'][] = self::formatForDisplay($item['gst_amount']);
                $formatted['extensionVehicleServiceFees']['coupon_discount'][] = self::formatForDisplay(0);
                $formatted['extensionVehicleServiceFees']['total_amount'][] = self::formatForDisplay($item['total']);
                $formatted['totalAmt'] += $item['total'];
                $formatted['rateTotal'] += $item['base'];
                $formatted['totalTax'] += $item['gst_amount'];
            }
        }

        // Format completion
        if (!empty($lineItems['completion'])) {
            $item = $lineItems['completion'];
            $formatted['completion'] = [
                'additional_charge' => self::formatForDisplay($item['base']),
                'tax_percent' => self::formatForDisplay($item['gst_rate']),
                'tax_amount' => self::formatForDisplay($item['gst_amount']),
                'coupon_discount' => self::formatForDisplay(0),
                'total_amount' => self::formatForDisplay($item['total']),
            ];
            $formatted['completionNewBooking'] = $item['timestamp'] ?? '';
            $formatted['totalAmt'] += $item['total'];
            $formatted['rateTotal'] += $item['base'];
            $formatted['totalTax'] += $item['gst_amount'];
        }

        // Format completion vehicle service
        if (!empty($lineItems['completionVehicleService'])) {
            $item = $lineItems['completionVehicleService'];
            $formatted['completionVehicleServiceFees'] = [
                'trip_amount' => self::formatForDisplay($item['base']),
                'tax_percent' => self::formatForDisplay($item['gst_rate']),
                'tax_amount' => self::formatForDisplay($item['gst_amount']),
                'coupon_discount' => self::formatForDisplay(0),
                'total_amount' => self::formatForDisplay($item['total']),
            ];
            $formatted['totalAmt'] += $item['total'];
            $formatted['rateTotal'] += $item['base'];
            $formatted['totalTax'] += $item['gst_amount'];
        }

        // Format paid penalties
        if (!empty($lineItems['paidPenalties'])) {
            foreach ($lineItems['paidPenalties'] as $item) {
                $formatted['paidPenalties']['trip_amount'][] = self::formatForDisplay($item['base']);
                $formatted['paidPenalties']['tax_percent'][] = self::formatForDisplay($item['gst_rate']);
                $formatted['paidPenalties']['tax_amount'][] = self::formatForDisplay($item['gst_amount']);
                $formatted['paidPenalties']['coupon_discount'][] = self::formatForDisplay(0);
                $formatted['paidPenalties']['total_amount'][] = self::formatForDisplay($item['total']);
                $formatted['paidPenalties']['timestamp'][] = $item['timestamp'] ?? '';
                $formatted['totalAmt'] += $item['total'];
                $formatted['rateTotal'] += $item['base'];
                $formatted['totalTax'] += $item['gst_amount'];
            }
        }

        // Format paid penalty services
        if (!empty($lineItems['paidPenaltyServices'])) {
            foreach ($lineItems['paidPenaltyServices'] as $item) {
                $formatted['paidPenaltyServiceCharge']['trip_amount'][] = self::formatForDisplay($item['base']);
                $formatted['paidPenaltyServiceCharge']['tax_percent'][] = self::formatForDisplay($item['gst_rate']);
                $formatted['paidPenaltyServiceCharge']['tax_amount'][] = self::formatForDisplay($item['gst_amount']);
                $formatted['paidPenaltyServiceCharge']['coupon_discount'][] = self::formatForDisplay(0);
                $formatted['paidPenaltyServiceCharge']['total_amount'][] = self::formatForDisplay($item['total']);
                $formatted['totalAmt'] += $item['total'];
                $formatted['rateTotal'] += $item['base'];
                $formatted['totalTax'] += $item['gst_amount'];
            }
        }

        // Format due penalties
        if (!empty($lineItems['duePenalties'])) {
            foreach ($lineItems['duePenalties'] as $item) {
                $formatted['duePenalties']['trip_amount'][] = self::formatForDisplay($item['base']);
                $formatted['duePenalties']['tax_percent'][] = self::formatForDisplay($item['gst_rate']);
                $formatted['duePenalties']['tax_amount'][] = self::formatForDisplay($item['gst_amount']);
                $formatted['duePenalties']['coupon_discount'][] = self::formatForDisplay(0);
                $formatted['duePenalties']['total_amount'][] = self::formatForDisplay($item['total']);
                $formatted['duePenalties']['timestamp'][] = $item['timestamp'] ?? '';
                $formatted['amountDue'] += $item['total'];
            }
        }

        // Format due penalty services
        if (!empty($lineItems['duePenaltyServices'])) {
            foreach ($lineItems['duePenaltyServices'] as $item) {
                $formatted['duePenaltyServiceCharge']['trip_amount'][] = self::formatForDisplay($item['base']);
                $formatted['duePenaltyServiceCharge']['tax_percent'][] = self::formatForDisplay($item['gst_rate']);
                $formatted['duePenaltyServiceCharge']['tax_amount'][] = self::formatForDisplay($item['gst_amount']);
                $formatted['duePenaltyServiceCharge']['coupon_discount'][] = self::formatForDisplay(0);
                $formatted['duePenaltyServiceCharge']['total_amount'][] = self::formatForDisplay($item['total']);
                $formatted['amountDue'] += $item['total'];
            }
        }

        // Format GST summary with rounding
        foreach ($gstSummary as $percent => $totals) {
            $formatted['groupedTotals'][$percent] = [
                'rate' => round($totals['rate'], 2),
                'tax' => round($totals['tax'], 2),
                'vehicle_commission_rate' => round($totals['vehicle_commission_rate'], 2),
                'vehicle_commission_tax' => round($totals['vehicle_commission_tax'], 2),
            ];
        }

        // Round final totals
        $formatted['totalAmt'] = round($formatted['totalAmt'], 2);
        $formatted['amountDue'] = round($formatted['amountDue'], 2);
        $formatted['rateTotal'] = round($formatted['rateTotal'], 2);
        $formatted['totalTax'] = round($formatted['totalTax'], 2);

        return $formatted;
    }

    /**
     * Build penalty text from completion data
     */
    public function buildPenaltyText(array $completion): string
    {
        $penaltyText = '';
        
        if (!empty($completion['late_return']) && $completion['late_return'] > 0) {
            $penaltyText .= ' Late Return - ' . round($completion['late_return'], 2);
        }
        
        if (!empty($completion['exceeded_km']) && $completion['exceeded_km'] > 0) {
            if (!empty($penaltyText)) {
                $penaltyText .= ' | ';
            }
            $penaltyText .= 'Extra KM - ' . round($completion['exceeded_km'], 2);
        }
        
        if (!empty($completion['additional_charges']) && $completion['additional_charges'] > 0) {
            if (!empty($penaltyText)) {
                $penaltyText .= ' | ';
            }
            $penaltyText .= 'Additional Charges - ' . round($completion['additional_charges'], 2);
        }
        
        return $penaltyText;
    }
}

