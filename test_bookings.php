<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CarHost;
use App\Models\CarEligibility;
use App\Models\RentalBooking;

// Get a host with a token
$carHost = CarHost::whereNotNull('api_token')->where('api_token', '!=', '')->first();
if (!$carHost) {
    echo "No host with token found\n";
    exit;
}
echo "Host ID: " . $carHost->id . "\n";
echo "Host Name: " . $carHost->firstname . " " . $carHost->lastname . "\n";
echo "Token: " . substr($carHost->api_token, 0, 30) . "...\n\n";

// Get vehicle IDs
$carEligibilityIds = CarEligibility::where('car_hosts_id', $carHost->id)->pluck('vehicle_id')->toArray();
echo "Vehicle IDs: " . implode(',', $carEligibilityIds) . "\n\n";

// Count bookings by status
$counts = RentalBooking::whereIn('vehicle_id', $carEligibilityIds)
    ->selectRaw('status, count(*) as cnt')
    ->groupBy('status')
    ->get();

echo "Bookings by status:\n";
foreach ($counts as $c) {
    echo "  " . $c->status . ": " . $c->cnt . "\n";
}

// Now simulate the getBookings query for "all" (default/history)
$currentDateTime = \Carbon\Carbon::now()->setTimezone('Asia/Kolkata');
$next24Hours = \Carbon\Carbon::now()->setTimezone('Asia/Kolkata')->addHours(24);

$query = RentalBooking::whereIn('vehicle_id', $carEligibilityIds)
    ->where('status', '!=', 'pending')
    ->where(function ($q) use ($currentDateTime, $next24Hours) {
        $q->where(function ($inner) use ($currentDateTime, $next24Hours) {
            $inner->where('status', 'confirmed')
                ->whereBetween('pickup_date', [$currentDateTime, $next24Hours]);
        })
            ->orWhere('status', '!=', 'confirmed');
    });

echo "\nWith fixed query (all statuses, confirmed limited to 24h):\n";
echo "  Total: " . $query->count() . "\n";

// Also show what happens with specific status filters
foreach (['completed', 'running', 'canceled', 'failed', 'confirmed'] as $status) {
    $statusQuery = RentalBooking::whereIn('vehicle_id', $carEligibilityIds)
        ->where('status', '!=', 'pending')
        ->where(function ($q) use ($currentDateTime, $next24Hours) {
            $q->where(function ($inner) use ($currentDateTime, $next24Hours) {
                $inner->where('status', 'confirmed')
                    ->whereBetween('pickup_date', [$currentDateTime, $next24Hours]);
            })
                ->orWhere('status', '!=', 'confirmed');
        })
        ->where('status', $status);

    echo "  Status=$status: " . $statusQuery->count() . "\n";
}

echo "\nDone!\n";
