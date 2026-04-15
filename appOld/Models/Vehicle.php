<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{RentalBooking, CarEligibility};
use Carbon\Carbon;

class Vehicle extends Model
{
    protected $table = 'vehicles';

    protected $primaryKey = 'vehicle_id';

    protected $fillable = [
        'model_id',
        //'category_id',
        'year',
        'description',
        'color',
        'license_plate',
        'availability',
        'is_deleted',
        'rental_price',
        'availability_calendar',
        'type_id',
        'manufacturer_id',
        'branch_id',
        'commission_percent',
        'chassis_no',
        'publish',
        'vehicle_created_by',
        'temp_city_id',
        'apply_for_publish',
        'nick_name',
        'deposit_amount',
        'is_deposit_amount_show',
        'step_cnt',
        'updated_temp_city_id',
        'updated_model_id',
        'updated_year',
        'is_host_updated',
        'updated_extra_km_rate',
        'updated_deposit_amount',
        'updated_is_deposit_amount_show',
    ];

    protected $hidden = [
        'category',
        'images',
        'created_at',
        'updated_at',
    ];
    protected $appends = ['vehicle_name', 'category_name', /*'price_pr_hour',*/ 'cutout_image', 'cutout_optimize_image', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'location', 'city_name', 'city_id'];

    public function model()
    {
        return $this->belongsTo(VehicleModel::class, 'model_id', 'model_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    // public function carHostPickupLocation()
    // {
    //     return $this->hasOne(CarHostPickupLocation::class, 'vehicles_id', 'vehicle_id');
    // }

    public function properties()
    {
        return $this->hasOne(VehicleProperty::class, 'vehicle_id', 'vehicle_id');
    }

    public function rentalBookings()
    {
        return $this->hasMany(RentalBooking::class, 'vehicle_id', 'vehicle_id');
    }

    public function features()
    {
        return $this->belongsToMany(VehicleFeature::class, 'vehicle_feature_mappings', 'vehicle_id', 'feature_id');
    }

    public function featuresMapping()
    {
        return $this->hasMany(VehicleFeatureMapping::class, 'vehicle_id', 'vehicle_id');
    }

    public function images()
    {
        return $this->hasMany(VehicleImage::class, 'vehicle_id', 'vehicle_id');
    }

    public function pricingDetails()
    {
        return $this->hasMany(VehiclePriceDetail::class, 'vehicle_id', 'vehicle_id');
    }

    public function hostVehicleImages()
    {
        return $this->hasMany(CarHostVehicleImage::class, 'vehicles_id', 'vehicle_id');
    }

    public function getCityNameAttribute()
    {
        $cityName = '';
        if ($this->branch_id != NULL) {
            $branch = Branch::where('branch_id', $this->branch_id)->first();
            if ($branch->city) {
                $cityName = $branch->city->name;
            }
        } else {
            $carEligibility = CarEligibility::where('vehicle_id', $this->vehicle_id)->with('vehiclePickupLocation')->first();
            if ($carEligibility != '' && $carEligibility->vehiclePickupLocation) {
                if ($carEligibility->vehiclePickupLocation->city) {
                    $cityName = $carEligibility->vehiclePickupLocation->city->name;
                }
            }
        }

        return $cityName;
    }

    public function getCityIdAttribute()
    {
        $cityId = '';
        if ($this->branch_id != NULL) {
            $branch = Branch::where('branch_id', $this->branch_id)->first();
            if ($branch->city) {
                $cityId = $branch->city->id;
            }
        } else {
            $carEligibility = CarEligibility::where('vehicle_id', $this->vehicle_id)->with('vehiclePickupLocation')->first();
            if ($carEligibility != '' && $carEligibility->vehiclePickupLocation) {
                if ($carEligibility->vehiclePickupLocation->city) {
                    $cityId = $carEligibility->vehiclePickupLocation->city->id;
                }
            }
        }

        return $cityId;
    }

    public function getRatingAttribute()
    {
        $totalRating = RentalReview::where('vehicle_id', $this->vehicle_id)->avg('rating');
        return round($totalRating, 2);
    }

    public function getTotalRatingAttribute()
    {
        $count = RentalReview::where('vehicle_id', $this->vehicle_id)
            ->count();

        return $count;

    }

    public function getTripCountAttribute()
    {
        $count = RentalBooking::where('vehicle_id', $this->vehicle_id)->where('status', 'completed')
            ->count();

        return $count;

    }

    public function getBannerImageAttribute()
    {
        $bannerImage = $this->images()->where('image_type', 'banner')->first();
        return $bannerImage ? $bannerImage->image_url : null;
    }

    public function getCutoutImageAttribute()
    {
        $bannerImage = $this->images()->where('image_type', 'cutout')->first();
        if (isset($bannerImage) && $bannerImage != '') {
            return $bannerImage ? $bannerImage->image_url : null;
        } else {
            $modelImage = $this->model ? $this->model->model_image : null;
            return $modelImage;
        }
    }

    public function getCutoutOptimizeImageAttribute()
    {
        $originalUrl = $this->cutout_image;
        if (!$originalUrl)
            return null;

        $filename = basename($originalUrl);
        // Determine if it's from models or vehicle_images
        $isModel = strpos($originalUrl, 'vehicle_models') !== false;
        $folder = $isModel ? 'vehicle_models' : 'vehicle_images';

        $optimizedFolder = public_path('images/' . $folder . '_optimized');
        $optimizedPath = $optimizedFolder . '/' . $filename;
        $optimizedUrl = asset('images/' . $folder . '_optimized/' . $filename);

        if (file_exists($optimizedPath)) {
            return $optimizedUrl;
        }

        // Try to generate it if GD is available
        $sourcePath = public_path('images/' . $folder . '/' . $filename);
        if (file_exists($sourcePath)) {
            try {
                if (!file_exists($optimizedFolder)) {
                    mkdir($optimizedFolder, 0755, true);
                }

                $imageInfo = getimagesize($sourcePath);
                if ($imageInfo) {
                    $mime = $imageInfo['mime'];
                    $image = null;
                    if ($mime == 'image/jpeg') {
                        $image = imagecreatefromjpeg($sourcePath);
                    } elseif ($mime == 'image/png') {
                        $image = imagecreatefrompng($sourcePath);
                    } elseif ($mime == 'image/webp') {
                        $image = @imagecreatefromwebp($sourcePath);
                    }

                    if ($image) {
                        imagejpeg($image, $optimizedPath, 60); // Optimize to ~60% quality to stay under 100KB
                        imagedestroy($image);
                        return $optimizedUrl;
                    }
                }
            } catch (\Exception $e) {
                // Log::error("Image optimization failed: " . $e->getMessage());
            }
        }

        return $originalUrl; // Fallback to original if optimization fails or isn't possible
    }

    public function getBannerImagesAttribute()
    {
        $hostImages = $this->host_banner_images;
        if ($hostImages && $hostImages->isNotEmpty()) {
            return $hostImages;
        }
        return $this->images()->where('image_type', 'banner')->pluck('image_url');
    }
    public function getHostBannerImagesAttribute()
    {
        return $this->hostVehicleImages()->where('image_type', 3)->pluck('vehicle_img'); // Exterier Images
    }

    public function getRegularImagesAttribute()
    {
        $hostImages = $this->host_regular_images;
        if ($hostImages && $hostImages->isNotEmpty()) {
            return $hostImages;
        }
        return $this->images()->where('image_type', 'regular')->pluck('image_url');
    }
    public function getHostRegularImagesAttribute()
    {
        return $this->hostVehicleImages()->where('image_type', 2)->pluck('vehicle_img'); // Interier Images
    }


    public function getVehicleNameAttribute()
    {
        $manufacturerName = explode(' ', $this->model->manufacturer->name)[0]; // Get the first word of the manufacturer's name
        $modelName = $this->model->name;
        return $manufacturerName . ' ' . $modelName;
    }

    public function vehicleEligibility()
    {
        return $this->belongsTo(CarEligibility::class, 'vehicle_id', 'vehicle_id');
    }

    public function city()
    {
        return $this->hasOne(City::class, 'id', 'temp_city_id');
    }

    public function getCategoryNameAttribute()
    {
        $catName = '';
        if ($this->model->category) {
            $catName = $this->model->category->name;
            return $catName;
        } else {
            return $catName;
        }
    }

    public function carHostVehicleImages()
    {
        return $this->hasMany(CarHostVehicleImage::class, 'vehicles_id', 'vehicle_id');
    }
    public function carhostFeatures()
    {
        return $this->belongsToMany(VehicleFeature::class, 'car_host_vehicle_features', 'vehicles_id', 'feature_id');
    }
    public function CarHostVehicleFeatures()
    {
        return $this->hasMany(CarHostVehicleFeature::class, 'vehicles_id', 'vehicle_id');
    }

    public function getLocationAttribute()
    {
        $location = null;
        if ($this->branch_id != '') {
            $branch = Branch::where('branch_id', $this->branch_id)->first();
            if ($branch != '') {
                $location['name'] = $branch->name ?? '';
                $location['manager_name'] = $branch->manager_name ?? '';
                $location['address'] = $branch->address ?? '';
                $location['latitude'] = (double) $branch->latitude ?? 0.00;
                $location['longitude'] = (double) $branch->longitude ?? 0.00;
                $location['phone'] = $branch->phone ?? '';
            }
        } else if ($this->branch_id == '') {
            // $branch = CarHostPickupLocation::with('carEligibility', 'carEligibility.carHost')->where('vehicles_id', $this->vehicle_id)->where('is_primary', 1)->first();
            $carEligibility = CarEligibility::where('vehicle_id', $this->vehicle_id)->with('vehiclePickupLocation')->first();
            $managerName = $phone = '';
            if ($carEligibility != '' && $carEligibility->vehiclePickupLocation) {
                if ($carEligibility && $carEligibility->carHost) {
                    $managerName .= $carEligibility->carHost->firstname ?? '';
                    $managerName .= ' ' . $carEligibility->carHost->lastname ?? '';
                    $phone = $carEligibility->carHost->mobile_number ?? '';
                }
                $location['name'] = $carEligibility->vehiclePickupLocation->name ?? '';
                $location['manager_name'] = $managerName;
                $location['address'] = $carEligibility->vehiclePickupLocation->location ?? '';
                $location['latitude'] = (double) $carEligibility->vehiclePickupLocation->latitude ?? 0.00;
                $location['longitude'] = (double) $carEligibility->vehiclePickupLocation->longitude ?? 0.00;
                $location['phone'] = $phone;
            }
        }

        return $location;
    }

    public function runningOrConfirmedBookings()
    {
        return $this->hasMany(RentalBooking::class, 'vehicle_id')->whereIn('status', ['running', 'confirmed']);
    }

    public function vehicleDocuments()
    {
        return $this->hasMany(VehicleDocument::class, 'vehicle_id', 'vehicle_id');
    }

}