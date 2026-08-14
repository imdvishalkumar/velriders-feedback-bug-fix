<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\RentalBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function getAllPayment()
    {
        $payment = Payment::orderBy("created_at","desc")->get();

        return  $payment;
    }

    public function paymentsForm(Request $request)
    {
      $RentalBooking = RentalBooking::all();
      
      $bookingDropDown = '';
      foreach ($RentalBooking as $branch) {
        $bookingDropDown .= '<option value="' . $branch->booking_id . '">' . $branch->booking_id . '</option>';
      }
     return '<div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Add Panalty</h3>

            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <form class="card-body" id="payment-form">

            <div class="form-group">
                <label for="booking_id">Booking ID</label>
                <select id="booking_id" class="form-control custom-select" name="booking_id" required>
                    <option selected disabled>Select one</option>
                    ' . $bookingDropDown .'
                </select>
            </div>

            <div class="form-group">
                <label for="color">Add Panalty</label>
                <input type="number" id="panalty" class="form-control" name="panalty" required>
            </div>

            <button type="submit" class="btn btn-primary">Add Panalty</button>
          </form>
        </div>';
    } 

    public function storePayments(Request $request){
      
    DB::beginTransaction();

    try {
        DB::table('rental_bookings')
            ->where('booking_id', $request->booking_id)
            ->update([
                'customer_id' => "1",
                'vehicle_id' => "1",
                'from_branch_id' => "1",
                'to_branch_id' => '1',
                'total_cost' => $request->panalty,
                'updated_at' => now()
            ]);
        
        DB::commit();
        
        return response()->json(['status' => true, 'message' => 'Payment update successfully']);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json(['error' => 'Failed to update records: ' . $e->getMessage()], 500);
    }
      // Optionally, you can return a response indicating success
      return response()->json(['status' => true, 'message' => 'Payment update successfully']);
    }
}
