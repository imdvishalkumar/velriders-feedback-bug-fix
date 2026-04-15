@extends('templates.admin')

@section('page-title')
    Vehicles
@endsection
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
@section('content')
<div id="messages"></div>
    <section class="content">
        <div class="row">
          <div class="col-md-12">
            <div class="card">
              <div class="card card-primary">
                <div class="card card-primary">
                    <div class="card-header">
                      <h3 class="card-title">Update Vehicle</h3>
                    </div>
                    @if ($errors->any())
                        <div class="alert alert-danger" id="error-message">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif    
                    <form class="card-body" id="vehicle-form" action="{{ route('admin.vehicle-update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="car_host">Car Host</label> <span class="error">*</span>
                                    <select id="car_host" class="form-control custom-select" name="car_host">
                                        <option selected disabled value="">Select one</option>
                                        @if(is_countable($carHost) && count($carHost))
                                            @foreach($carHost as $key => $val)
                                                <option value="{{$val->id}}" @if(isset($vehicle->vehicleEligibility) && $vehicle->vehicleEligibility->car_hosts_id == $val->id){{'selected'}}@else{{''}}@endif>{{$val->firstname}} {{$val->lastname}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label for="commission_percent">Commission Percentage</label> @haspermission('admins', 'admin_web') <span class="error">*</span> @endhaspermission
                                <input type="number" id="commission_percent" min="0" max="100" name="commission_percent" placeholder="Enter Commision Percent" class="form-control" @haspermission('admins', 'admin_web') @else readonly @endhaspermission @if(isset($vehicle->commission_percent))value="{{$vehicle->commission_percent}}"@else value="" @endif>    
                            </div>
                        </div>
                        <div class="row">
                          <div class="col-md-3">
                            <input type="hidden" name="vehicle_id" value="{{ $vehicle->vehicle_id }}">
                                <div class="form-group">
                                    <label for="branch">Branch</label>
                                    <select id="branch" class="form-control custom-select" name="branch">
                                      <option selected disabled>Select one</option>
                                      {!! $branchDropDown !!}
                                    </select>
                                </div>
                          </div>
                          <div class="col-md-3">
                              <div class="form-group">
                                  <label for="vehicle_type">Vehicle Type</label> <span class="error">*</span>
                                  <select id="vehicle_type" class="form-control custom-select" name="vehicle_type" >
                                      <option selected disabled>Select one</option>
                                      @if(isset($allVehicleTypes) && is_countable($allVehicleTypes) && count($allVehicleTypes) > 0)
                                          @foreach($allVehicleTypes as $val)
                                              <option value="{{$val->type_id}}" @if($vehicle->model->category) @if($val->type_id == $vehicle->model->category->vehicleType->type_id){{'selected'}}@else{{''}}@endif @endif>{{$val->name}}</option>
                                          @endforeach
                                      @endif
                                  </select>
                              </div>
                          </div>
                          <div class="col-md-3">
                            <div class="form-group">
                                <label for="manufacturer">Manufacturer</label> <span class="error">*</span>
                                <select id="manufacturer" class="form-control custom-select" name="manufacturer" >
                                    <option selected disabled>Select one</option>
                                    
                                </select>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="form-group">
                                <label for="model">Model</label> <span class="error">*</span>
                                <input type="hidden" id="dbModel" class="form-control" value="{{$vehicle->model_id}}">
                                <select id="model" class="form-control custom-select" name="model" required>
                                  <option selected disabled>Select one</option>
                                    
                                </select>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="category">Category</label>
                                    <input type="text" id="category" class="form-control" readonly>
                                    <!-- <select id="category" class="form-control custom-select" name="category" required>
                                      <option selected disabled>Select one</option>
                                       
                                    </select> -->
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="year">Manufacture Year</label> <span class="error">*</span>
                                    <input type="text" id="year" class="form-control" value="{{ $vehicle->year }}" name="year" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="color">Color</label> <span class="error">*</span>
                                    <input type="text" id="color" class="form-control" name="color" value="{{ $vehicle->color }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="licence_plate">Registration Number</label> <span class="error">*</span>
                                    <input type="text" id="licence_plate" class="form-control" name="licence_plate" value="{{ $vehicle->license_plate }}" required>
                                </div>
                            </div>
                            {{--<div class="col-md-3">
                                <div class="form-group">
                                    <label for="chassis_no">Chassis Number</label> <span class="error">*</span>
                                    <input type="text" id="chassis_no" class="form-control" name="chassis_no" value="{{ $vehicle->chassis_no }}">
                                    @error('chassis_no')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>--}}
                        </div>
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="description">Description</label> <span class="error">*</span>
                                    <input type="text" id="description" class="form-control" value="{{ $vehicle->description  }}" name="description" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="availability">Is Avaliable</label> <span class="error">*</span>
                                    <select id="availability" class="form-control custom-select" name="availability" value="{{ $vehicle->availability }}" required>
                                        <option selected disabled>Select one</option>
                                        {!! $avaliblityDropdown !!}
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr />
                        <h6 class="text-primary"><b>Properties</b></h6>
                        <hr />
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="vehicle_transmission">Vehicle Transmission</label>
                                    <select id="vehicle_transmission" class="form-control custom-select" name="vehicle_transmission" >
                                        <option selected disabled>Select one</option>
                                        @if(isset($transmission) && is_countable($transmission) && count($transmission) > 0)
                                            @foreach($transmission as $val)
                                                <option value="{{$val->transmission_id}}" @if(isset($vehicle->properties) && $vehicle->properties->transmission_id == $val->transmission_id){{'selected'}}@else{{''}}@endif>{{$val->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                      <label for="engine_cc">Engine CC</label>
                                      <input type="text" id="engine_cc" class="form-control" name="engine_cc" @isset($VehicleProperty->engine_cc) value="{{ $VehicleProperty->engine_cc }}"@endif>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                      <label for="seating_capacity">Seating Capacity</label>
                                      <input type="text" id="seating_capacity" class="form-control" name="seating_capacity" @isset($VehicleProperty->seating_capacity)value="{{ $VehicleProperty->seating_capacity }}"@endisset>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                  <label for="mileage">Mileage</label>
                                  <input type="text" id="mileage" class="form-control" name="mileage" @isset($VehicleProperty->mileage)value="{{ $VehicleProperty->mileage }}"@endisset>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fuel_type">Fuel Type</label>
                                    <select id="fuel_type" class="form-control custom-select" name="fuel_type" >
                                        <option selected disabled>Select one</option>
                                        @if(isset($fuelType) && is_countable($fuelType) && count($fuelType) > 0)
                                            @foreach($fuelType as $val)
                                                <option value="{{$val->fuel_type_id}}" @if(isset($vehicle->properties) && $vehicle->properties->fuel_type_id == $val->fuel_type_id){{'selected'}}@else{{''}}@endif>{{$val->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fuel_capacity">Fuel Capacity</label>
                                    <input type="text" id="fuel_capacity" class="form-control" name="fuel_capacity" @isset($VehicleProperty->fuel_capacity)value="{{ $VehicleProperty->fuel_capacity }}"@endisset>
                                </div>
                            </div>
                        </div>
                        <hr />
                            <h6 class="text-primary"><b>Vehicle Feature Selection</b></h6>
                        <hr />
                        <div class="row ml-2">
                            @if(isset($vehicleFeature) && is_countable($vehicleFeature) && count($vehicleFeature) > 0)
                            <div class="form-group">
                                @foreach($vehicleFeature as $val)                    
                                    <input class="" type="checkbox" id="feature_{{$val->feature_id}}" name="feature[]" value="{{$val->feature_id}}" @if(in_array($val->feature_id, $featureIds)){{'checked'}}@else{{''}}@endif> {{$val->name}} <br/>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <hr />
                            <h6 class="text-primary"><b>Images Upload Section</b></h6>
                        <hr />
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input type="hidden" id="doc_rc_img" @if(isset($rcDoc['image'][0]))value="1"@else value=""@endif>
                                    <label for="document_rc_image">Document RC Image</label> <span class="error">*</span>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="document_rc_image" name="document_rc_image[]" accept="image/*" multiple>
                                        <label class="custom-file-label" for="document_rc_image" id="logoLabel">Choose file</label>
                                        <div class="invalid-feedback">Please select a Images logo.</div>
                                    </div>

                                    <div id="docRcImg">
                                        @if(isset($rcDoc) && is_countable($rcDoc) && count($rcDoc) > 0)
                                            @foreach($rcDoc['id'] as $key => $val)
                                                    @php $imgPath = public_path('images/documents/').$rcDoc['image'][$key]; @endphp
                                                    @if(file_exists($imgPath) && isset($rcDoc['image'][$key]))
                                                    <div class="image-display">
                                                        <img src="{{ asset('images/documents') . '/' . $rcDoc['image'][$key] }}" alt="RC Doc Image" style="width: 250px; height: 175px; border: 1px solid #ccc; border-radius: 5px; padding: 5px;" class="img-thumbnail m-2">
                                                        <a href="javascript:void(0);" class="delete-document" data-document-id="{{ $rcDoc['id'][$key] }}" data-doc-type="rc"><i class="text-danger fas fa-minus-square fa-3x"></i></a>
                                                        <!-- <button type="button" class="btn btn-sm btn-danger delete-document" data-document-id="{{ $rcDoc['id'][$key] }}" data-doc-type="rc">
                                                            <i class="bi bi-x"></i> Delete
                                                        </button> -->
                                                    </div>
                                                    @else
                                                        <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="No Image" class="img-thumbnail m-2">
                                                    @endif
                                            @endforeach
                                        @else
                                            <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="No Image" class="img-thumbnail m-2">
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input type="hidden" id="doc_puc_img" @if(isset($pucDoc) && is_countable($pucDoc) && count($pucDoc) > 0)value="{{$pucDoc['image']}}"@endif>
                                    <label for="document_puc_image">Document PUC Image</label> <span class="error">*</span>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="document_puc_image" name="document_puc_image" accept="image/*">
                                        <label class="custom-file-label" for="document_puc_image" id="logoLabel">Choose file</label>
                                        <div class="invalid-feedback">Please select a Images logo.</div>
                                    </div>
                                    <div id="docPucImg">
                                        @if(isset($pucDoc) && is_countable($pucDoc) && count($pucDoc) > 0)
                                        @php $imgPath = public_path('images/documents/').$pucDoc['image']; @endphp
                                            @if(file_exists($imgPath) && isset($pucDoc['image']))
                                            <div class="image-display">
                                                <img src="{{ asset('images/documents') . '/' . $pucDoc['image'] }}" alt="PUC Doc Image" style="width: 250px; height: 175px; border: 1px solid #ccc; border-radius: 5px; padding: 5px;" class="img-thumbnail m-2">
                                                <a href="javascript:void(0);" class="delete-document" data-document-id="{{ $pucDoc['id'] }}" data-doc-type="puc"><i class="text-danger fas fa-minus-square fa-3x"></i></a>
                                                <!-- <button type="button" class="btn btn-sm btn-danger delete-document" data-document-id="{{ $pucDoc['id'] }}" data-doc-type="puc">
                                                    <i class="bi bi-x"></i> Delete
                                                </button> -->
                                            </div>
                                            @else
                                                <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="No Image" class="img-thumbnail m-2">
                                            @endif
                                        @else
                                            <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="No Image" class="img-thumbnail m-2">
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input type="hidden" id="doc_insurance_img" @if(isset($insuranceDoc) && is_countable($insuranceDoc) && count($insuranceDoc) > 0)value="{{$insuranceDoc['image']}}"@endif>
                                    <label for="document_insurance_image">Document Insurance Image</label> <span class="error">*</span>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="document_insurance_image" name="document_insurance_image" accept="image/*">
                                        <label class="custom-file-label" for="document_insurance_image" id="logoLabel">Choose file</label>
                                        <div class="invalid-feedback">Please select a Images logo.</div>
                                    </div>
                                    <div id="docInsuranceImg">
                                        @if(isset($insuranceDoc) && is_countable($insuranceDoc) && count($insuranceDoc) > 0)
                                        @php $imgPath = public_path('images/documents/').$insuranceDoc['image']; @endphp
                                            @if(file_exists($imgPath) && isset($insuranceDoc['image']))
                                            <div class="image-display">
                                                <img src="{{ asset('images/documents') . '/' . $insuranceDoc['image'] }}" alt="Insurance Doc Image" style="width: 250px; height: 175px; border: 1px solid #ccc; border-radius: 5px; padding: 5px;" class="img-thumbnail m-2">
                                                <a href="javascript:void(0);" class="delete-document" data-document-id="{{ $insuranceDoc['id'] }}" data-doc-type="insurance"><i class="text-danger fas fa-minus-square fa-3x"></i></a>
                                                <!-- <button type="button" class="btn btn-sm btn-danger delete-document" data-document-id="{{ $insuranceDoc['id'] }}" data-doc-type="insurance"><i class="bi bi-x"></i> Delete</button> -->
                                            </div>
                                            @else
                                            <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="No Image" class="img-thumbnail m-2">
                                            @endif
                                        @else
                                            <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="No Image" class="img-thumbnail m-2">
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input type="hidden" id="c_img" @isset($vehicleCutoutImage->image_url)value="{{$vehicleCutoutImage->image_url}}"@endisset> 
                                    <label for="cutout_img">Cutout Image</label> <span class="error">*</span>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="cutout_img" name="cutout_img" accept="image/*">
                                        <label class="custom-file-label" for="cutout_img">Choose file</label>
                                        <div class="invalid-feedback">Please select a Images logo.</div>
                                    </div>
                                    <div id="cutoutImg">
                                        @if(isset($vehicleCutoutImage->image_url))
                                        @php 
                                            $cParsedUrl = parse_url($vehicleCutoutImage->image_url);
                                            $cpath = isset($cParsedUrl['path']) ? $cParsedUrl['path'] : ''; 
                                            $cImgpath = public_path($cpath);
                                        @endphp
                                        @if(file_exists($cImgpath))
                                        <div class="image-display">
                                            <img src="{{ $vehicleCutoutImage->image_url }}" alt="Cutout Image" style="width: 250px; height: 175px; border: 1px solid #ccc; border-radius: 5px; padding: 5px;" class="img-thumbnail m-2">
                                            <a href="javascript:void(0);" class="delete-image" data-image-id="{{ $vehicleCutoutImage->image_id }}" data-image-type="cutout"><i class="text-danger fas fa-minus-square fa-3x"></i></a>
                                            <!-- <button type="button" class="btn btn-sm btn-danger delete-image" data-image-id="{{ $vehicleCutoutImage->image_id }}" data-image-type="cutout">
                                                <i class="bi bi-x"></i> Delete
                                            </button> -->
                                        </div>
                                        @else
                                        <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="Uploaded Image" class="img-thumbnail m-2">
                                        @endif
                                        @else
                                        <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="Uploaded Image" class="img-thumbnail m-2">
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="rc_expiry_date">RC Doc Expiry Date</label> <span class="error">*</span>
                                    <input type="date" class="form-control" name="rc_expiry_date" id="rc_expiry_date" @if(isset($rcDoc) && is_countable($rcDoc) && count($rcDoc) > 0)value="{{$rcDoc['expiryDate'][0]}}"@else{{''}}@endif>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="puc_expiry_date">PUC Doc expiry Date</label> <span class="error">*</span>
                                    <input type="date" class="form-control" name="puc_expiry_date" id="puc_expiry_date" @if(isset($pucDoc) && is_countable($pucDoc) && count($pucDoc) > 0)value="{{$pucDoc['expiryDate']}}"@else{{''}}@endif>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="insurance_expiry_date">Insurance Doc Expiry Date</label> <span class="error">*</span>
                                    <input type="date" class="form-control" name="insurance_expiry_date" id="insurance_expiry_date" @if(isset($insuranceDoc) && is_countable($insuranceDoc) && count($insuranceDoc) > 0)value="{{$insuranceDoc['expiryDate']}}"@else{{''}}@endif>
                                </div>
                            </div>                    
                        </div>
                        <!-- <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="rc_number">RC Number</label> 
                                    <input type="text" class="form-control" name="rc_number" id="rc_number" @if(isset($rcDoc) && is_countable($rcDoc) && count($rcDoc) > 0)value="{{$rcDoc['id_number']}}"@else{{''}}@endif>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="puc_number">PUC Number</label>
                                    <input type="text" class="form-control" name="puc_number" id="puc_number" @if(isset($pucDoc) && is_countable($pucDoc) && count($pucDoc) > 0)value="{{$pucDoc['id_number']}}"@else{{''}}@endif>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="insurance_number">Insurance Doc Number</label>
                                    <input type="text" class="form-control" name="insurance_number" id="insurance_number" @if(isset($insuranceDoc) && is_countable($insuranceDoc) && count($insuranceDoc) > 0)value="{{$insuranceDoc['id_number']}}"@else{{''}}@endif>
                                </div>
                            </div>
                        </div> -->
                        </hr>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="banner_images">Banner Image</label> <span class="error">*</span>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="banner_images" name="banner_images" accept="image/*" required title="Please select Banner Image" multiple>
                                    <label class="custom-file-label" for="banner_images">Choose file</label>
                                    <div class="invalid-feedback">Please select a Image.</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" value="{{count($vehicleBannerImages)}}" id="bannerCnt">
                        <div class="row mb-3" id="bannerImgContainer">
                            @if(isset($vehicleBannerImages) && is_countable($vehicleBannerImages) && count($vehicleBannerImages) > 0)
                            @foreach($vehicleBannerImages as $key => $val)
                                <div class="col-md-4 removeBannerOld_{{$key}}">
                                    @php 
                                        $bParsedUrl = parse_url($val->image_url);
                                        $bpath = isset($bParsedUrl['path']) ? $bParsedUrl['path'] : ''; 
                                        $bImgpath = public_path($bpath);
                                    @endphp
                                    @if(file_exists($bImgpath) && isset($val->image_url))
                                    <img src="{{asset($val->image_url)}}" style="width: 250px; height: 175px;" alt="Uploaded Image" class="img-thumbnail m-2">
                                    @else
                                    <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="Uploaded Image" class="img-thumbnail m-2">
                                    @endif
                                    <a href="javascript:void(0);" class="ml-2" data-id="{{$key}}" id="removeOldBannerImg">
                                        <i class="text-danger fas fa-minus-square fa-3x"></i>
                                    </a>
                                </div>
                                <input type="hidden" class="removeBannerOld_{{$key}}" name="bannerimgsOld[]" value="{{$val->image_url}}">
                            @endforeach
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="regular_images">Regular Image</label> <span class="error">*</span>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="regular_images" name="regular_images" accept="image/*" required title="Please select Regular Image" multiple>
                                    <label class="custom-file-label" for="regular_images">Choose file</label>
                                    <div class="invalid-feedback">Please select a Image.</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" value="{{count($vehicleRegularImages)}}" id="regularCnt">
                        <div class="row mb-3 mt-2" id="regularImgContainer">
                            @if(isset($vehicleRegularImages) && is_countable($vehicleRegularImages) && count($vehicleRegularImages) > 0)
                            @foreach($vehicleRegularImages as $key => $val)
                                <div class="col-md-4 removeRegularOld_{{$key}}">
                                    @php 
                                        $imgPath = public_path($val->image_url); 
                                        $rParsedUrl = parse_url($val->image_url);
                                        $rpath = isset($rParsedUrl['path']) ? $rParsedUrl['path'] : ''; 
                                        $rImgpath = public_path($rpath);
                                    @endphp
                                    @if(file_exists($rImgpath) && isset($val->image_url))
                                    <img src="{{asset($val->image_url)}}" style="width: 250px; height: 175px;" alt="Uploaded Image" class="img-thumbnail m-2">
                                    @else
                                    <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="Uploaded Image" class="img-thumbnail m-2">
                                    @endif
                                    <a href="javascript:void(0);" class="ml-2" data-id="{{$key}}" id="removeOldRegularImg"><i class="text-danger fas fa-minus-square fa-3x"></i></a>
                                </div>
                                <input type="hidden" class="removeRegularOld_{{$key}}" name="regularimgsOld[]" value="{{$val->image_url}}">
                            @endforeach
                            @endif
                        </div>
                        <hr />
                            <input type="hidden" id="min_rental_pirice" value="0">
                            <input type="hidden" id="max_rental_pirice" value="0">
                            <h6 class="text-primary"><b>Price Calculation</b></h6>
                        <hr />
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="rental_price">Rental Price</label> <span class="error">*</span>
                                    <input type="text" id="rental_price" class="form-control" value="{{ $vehicle->rental_price }}" name="rental_price" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="extra_km_rate">Extra Km Rate</label> <span class="error">*</span>
                                    <input type="text" id="extra_km_rate" class="form-control" value="{{ $vehicle->extra_km_rate }}" name="extra_km_rate" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="extra_hour_rate">Extra Hour Rate</label> <span class="error">*</span>
                                    <input type="text" id="extra_hour_rate" class="form-control" name="extra_hour_rate" value="{{ $vehicle->extra_hour_rate }}" required>
                                </div>
                            </div>  
                        </div>
                        <div id="calculation" class="row">
                            @if(is_countable($vehiclePriceDetails) && count($vehiclePriceDetails) > 0)
                                @foreach($vehiclePriceDetails as $vehiclePriceDetail)
                                    @php 
                                        $hours = $vehiclePriceDetail->hours;
                                        $duration = $hours <= 24 ? $hours.'Hours' : ($hours / 24).'Days';
                                        /* $multiplier = $vehiclePriceDetail->multiplier;
                                        $tripAmount = $multiplier * $vehiclePriceDetail->rentalPrice; */
                                    @endphp
                                    <div class="col-md-3">
                                        <b>{{$duration}}:</b> ₹ <input type="text" data-val="{{$hours}}" name="priceCalc[{{$hours}}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" class="priceChange" value="{{$vehiclePriceDetail->rate}}" id="priceval_{{$hours}}">
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @php
                            $cal = '';
                            if(isset($vehicle->availability_calendar) && $vehicle->availability_calendar != '' && $vehicle->availability_calendar != '{}') {
                                $cal = json_decode($vehicle->availability_calendar);
                            }
                        @endphp
                        <hr />
                            <h6 class="text-primary"><b>Availibility Date Calander</b></h6>
                        <hr />
                        @if((is_countable($cal) && count($cal) <= 0) || $cal == '' || $cal == NULL )
                        <div class="row">
                            {{-- <div class="col-md-2">
                                <label for="calender_start_date_0">Select From Date & Time</label>                 
                                <!-- <input type="text" class="form-control calstartdate" name="calender_start_date[]" id="calender_start_date_0" data-id="0"> -->
                                <div class="input-group date" data-target-input="nearest">
                                    <input type="text" id="calender_start_date_0" name="calender_start_date[0]" data-id="0" class="form-control datetimepicker-input calstartdate" data-target="#calender_start_date_0" placeholder="Select From Date Time" autocomplete="off" />
                                    <div class="input-group-append" data-target="#calender_start_date_0" data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                    </div>
                                </div>
                                <span id="cal_start_date_error_0"></span>
                            </div>
                            <div class="col-md-2">
                                <label for="calender_end_date_0">Select To Date & Time</label>                 
                                <!-- <input type="text" class="form-control calenddate" name="calender_end_date[]" id="calender_end_date_0" data-id="0"> -->
                                <div class="input-group date" data-target-input="nearest">
                                    <input type="text" id="calender_end_date_0" name="calender_end_date[0]" data-id="0" class="form-control datetimepicker-input calenddate" data-target="#calender_end_date_0" placeholder="Select To Date Time" autocomplete="off" />
                                    <div class="input-group-append" data-target="#calender_end_date_0" data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                    </div>
                                </div>
                                <span id="cal_end_date_error_0"></span>
                            </div>
                            <div class="col-md-6">`
                                <label for="reason_0">Reason</label>                           
                                <input type="text" class="form-control" name="reason[]" id="reason_0">
                            </div> --}}
                            <div class="col-md-1 mt-2">
                                <a href="javascript:void(0);" id="addCalender"><i class="fas fa-plus-square fa-3x"></i></a> 
                            </div>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-md-12">
                                <div id="calenderAppend">
                                    @if($cal != '')
                                        @foreach($cal as $key => $val)
                                        <!-- [calender_date] => 2024-05-15 [reason] => test 1 -->
                                            {{--@if($val->start_date != '' && $val->end_date != '' && isset($val->reason))--}}
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <label for="calender_start_date_exist_{{$key}}">Select From Date & Time</label>                 
                                                    <!-- <input type="text" class="form-control calstartdate" name="calender_start_date_exist[{{$key}}]" id="calender_start_date_exist_{{$key}}" @isset($val->start_date)value="{{$val->start_date}}"@endisset data-id="{{$key}}" placeholder="Select From Date Time"> -->
                                                    <div class="input-group date" data-target-input="nearest">
                                                        <input type="text" class="form-control datetimepicker-input calstartdate" id="calender_start_date_exist_{{$key}}" name="calender_start_date_exist[{{$key}}]" data-id="{{$key}}" @isset($val->start_date)value="{{$val->start_date}}"@endisset data-target="#calender_start_date_exist_{{$key}}" placeholder="Select From Date Time" autocomplete="off" />
                                                        <div class="input-group-append" data-target="#calender_start_date_exist_{{$key}}" data-toggle="datetimepicker">
                                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                        </div>
                                                    </div>
                                                    <span id="cal_start_date_error_{{$key}}"></span>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="calender_end_date_exist_{{$key}}">Select To Date & Time</label>                 
                                                    <!-- <input type="text" class="form-control calenddate" name="calender_end_date_exist[{{$key}}]" id="calender_end_date_exist_{{$key}}" @isset($val->end_date)value="{{$val->end_date}}"@endisset data-id="{{$key}}" placeholder="Select To Date Time" data-id="0"> -->
                                                    <div class="input-group date" data-target-input="nearest">
                                                        <input type="text" id="calender_end_date_exist_{{$key}}" name="calender_end_date_exist[{{$key}}]" data-id="{{$key}}" class="form-control datetimepicker-input calenddate calexistend" data-target="#calender_end_date_exist_{{$key}}" placeholder="Select To Date Time" @isset($val->end_date)value="{{$val->end_date}}"@endisset autocomplete="off" />
                                                        <div class="input-group-append" data-target="#calender_end_date_exist_{{$key}}" data-toggle="datetimepicker">
                                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                        </div>
                                                    </div>
                                                    <span id="cal_end_date_exist_error_{{$key}}"></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="reason_exist_{{$key}}">Reason</label>                           
                                                    <input type="text" class="form-control" name="reason_exist[{{$key}}]" id="reason_exist_{{$key}}" @isset($val->reason)value="{{$val->reason}}"@endisset>
                                                </div>
                                                @if($key == 0)
                                                <div class="col-md-1 mt-4">
                                                    <a href="javascript:void(0);" id="addCalender"><i class="fas fa-plus-square fa-3x"></i></a>
                                                </div>
                                                <div class="col-md-1 mt-4">
                                                    <a href="javascript:void(0);" class="btn btn-danger" id="clearDates" data-id="{{$key}}">Clear Dates</a> 
                                                </div>
                                                @else
                                                <div class="col-md-1 mt-4">
                                                    <a href="javascript:void(0);" id="deleteCalender"><i class="text-danger fas fa-minus-square fa-3x"></i></a>
                                                </div>
                                                @endif
                                            </div> 
                                            {{--@else
                                                <div class="col-md-1 mt-4">
                                                    <a href="javascript:void(0);" id="addCalender"><i class="fas fa-plus-square fa-3x"></i></a>
                                                </div>
                                            @endif --}}     
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                              <div class="form-group">
                                  {{-- <label for="document_type">Document Type</label>
                                  <select id="document_type" class="form-control custom-select" name="document_type" required>
                                      <option selected disabled>Select one</option>
                                      <option value="adhar_card" {{ optional($vehicleDocuments)->document_type == 'adhar_card' ? 'selected' : '' }}>Adharcard</option>
                                      <option value="driving_licence" {{ optional($vehicleDocuments)->document_type == 'driving_licence' ? 'selected' : '' }}>Driving Licence</option>
                                  </select> --}}
                              </div>
                          </div>
                        
                          <div class="col-md-3">
                              <div class="form-group">
                                  {{-- <label for="id_number">Id Number</label>
                                  <input type="text" class="form-control" name="id_number" value="{{ $vehicleDocuments ? $vehicleDocuments->id_number : '' }}" required> --}}
                              </div>
                          </div>
                        </div>

                        <!-- <div class="row" id="vehicle-images-section">
                          @foreach ($vehicleImage->unique('image_type') as $key => $type)
                              <div class="col-md-3">
                                  <div class="form-group">
                                      <label for="image_type">Image Type</label> <span class="error">*</span>
                                      <select id="image_type" class="form-control custom-select" name="addMoreInputFields[{{$key}}][image_type]" required>
                                          <option selected disabled>Select one</option>
                                          <option value="banner" {{ $type->image_type == 'banner' ? 'selected' : '' }}>Banner</option>
                                          <option value="cutout" {{ $type->image_type == 'cutout' ? 'selected' : '' }}>Cutout</option>
                                          <option value="regular" {{ $type->image_type == 'regular' ? 'selected' : '' }}>Regular</option>
                                      </select>
                                  </div>
                                  <div class="form-group">
                                    <label for="image_url">Images</label> <span class="error">*</span>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image_url" name="addMoreInputFields[{{$key}}][image_url][]" accept="image/*" multiple required>
                                        <label class="custom-file-label" for="image_url" id="logoLabel">Choose file</label>
                                        <div class="invalid-feedback">Please select a Image Url logo.</div>
                                    </div>
                                    <img src="" id="preview" style="max-width: 100px; max-height: 100px; margin-top: 10px; display: none;">
                                </div>

                              </div>
                              <div class="col-md-9">
                                  <div class="row">
                                      @foreach ($vehicleImage as $image)
                                          @if ($image->image_type == $type->image_type)
                                              <div class="col-lg-4 col-md-8 mb-4 mb-lg-0">
                                                  <img src="{{ $image->image_url }}" class="w-100 shadow-1-strong rounded mb-4" alt="Boat on Calm Water"/>
                                                  <button type="button" class="btn btn-sm btn-danger delete-image" data-image-id="{{ $image->image_id }}">
                                                      <i class="bi bi-x"></i> Delete
                                                  </button>
                                              </div>
                                          @endif
                                      @endforeach
                                  </div>
                              </div>
                          @endforeach
                        </div> -->
                       
                      <!-- <div class="card p-2 mt-5">
                        <h6>Insert Unavaliable Dates</h6>
                        <div class="col" id="date-container">
                          {!! $dateContainer !!}
                        </div>
                        <div class="d-flex justify-content-end absolute bottom-0">
                          <button type="button" id="add-more" class="btn btn-primary rounded-circle"><i class="fas fa-plus"></i></button>
                        </div>
                      </div> -->
              
                      <button type="submit" class="btn btn-primary">Update Vehicle</button>
                      <!-- <button type="button" id="addmoreimages" class="btn btn-secondary">Add More Images</button> -->
                      <a href="/admin/vehicles" class="btn btn-danger">Cancel</a>
                    </form>
                </div>
              </div>
            </div>
          </div>
        </div>
</section>
@endsection

@push('scripts')
<script src="{{asset('all_js/admin_js/vehicles.js')}}"></script>
@endpush

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script type="text/javascript">
    var typeId = '{{$vehicle->model->category->vehicleType->type_id ?? ''}}';
    var manufacturerId = '{{$vehicle->model->manufacturer->manufacturer_id}}';
    //var categoryId = '{{$vehicle->model->category_id}}';
    var modelId = '{{$vehicle->model_id}}';
    var bannerCnt = 0;
    var regularCnt = 0;
    var isPriceValid = true; 
    var fuelTypeId = '@isset($vehicle->properties->fuel_type_id){{$vehicle->properties->fuel_type_id}}@endisset';
    var transmissionId = '@isset($vehicle->properties->transmission_id){{$vehicle->properties->transmission_id}}@endisset';
    $(document).ready(function() {
        /*var i = 0;
        $("#addmoreimages").click(function () {
            ++i;
            $("#vehicle-images-section").append(`
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="image_type">Image Type</label>
                            <select id="image_type" class="form-control custom-select" name="addMoreInputFields[${i}][image_type]" required>
                                <option selected disabled>Select one</option>
                                <option value="banner">Banner</option>
                                <option value="cutout">Cutout</option>
                                <option value="regular">Regular</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="image_url">Images</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="addMoreInputFields[${i}][image_url][]" accept="image/*" multiple required>
                                <label class="custom-file-label" for="image_url" id="logoLabel">Choose file</label>
                                <div class="invalid-feedback">Please select a Images logo.</div>
                            </div>
                            <img src="" id="preview" style="max-width: 100px; max-height: 100px; margin-top: 10px; display: none;">
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger remove-input-field">Remove</button>
                </div>
            `);
        });
        $(document).on('click', '.remove-input-field', function () {
            $(this).parent().remove();
        });*/
    });

    $(document).ready(function() {
        $('.delete-image').on('click', function() {
            var imageId = $(this).data('image-id');
            var deleteButton = $(this); // Store reference to this
            var imageType = $(this).data('image-type');
            // AJAX request
            $.ajax({
                type: 'POST',
                url: '{{ route("delete.image") }}',
                data: {
                    imageId: imageId,
                    _token: getCsrfToken() // Include CSRF token
                },
                success: function(response) {
                    console.log('Image deleted successfully');
                    // Remove the image container from the UI
                    //deleteButton.closest('.col-lg-4').remove(); // Use stored reference to deleteButton
                    if(imageType == 'regular'){
                        $('.deleteRegularRow_'+imageId).remove();    
                    }
                    if(imageType == 'banner'){
                        $('.deleteBannerRow_'+imageId).remove();    
                    }
                    if(imageType == 'cutout'){
                        deleteButton.closest('.image-display').remove();
                    }
                    
                    // Display success message
                    displayMessage('success', 'Image deleted successfully');
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting image:', error);
                    // Display error message
                    displayMessage('error', 'Error deleting image. Please try again later.');
                }
            });
        });

        $('#rental_price').on('keyup', function() {
            const rentalPrice = parseFloat($(this).val());
            if (!isNaN(rentalPrice) && rentalPrice > 0 && rentalPrice != '') {
                $('#calculation').empty();
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    var multiplier = {{ $rule->multiplier }};
                    var tripAmount = multiplier * rentalPrice;
                    // Determine if the trip duration is in hours or days
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var calculationItem = $('<div class="col-md-3">').html(
                            `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}"  name="priceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" class="priceChange" value="${tripAmount}" id="priceval_${hours}"><br/><label class="text-danger" id="error_${hours}" hidden></label>`);
                        $('#calculation').append(calculationItem);
                @endforeach
            }else{
                $('#calculation').empty();
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var calculationItem = $('<div class="col-md-3">').html(
                        `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}" class="priceChange" name="priceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" value="0" id="priceval_${hours}"><br/><label class="text-danger" id="error_${hours}" hidden></label>`);
                    $('#calculation').append(calculationItem);
                @endforeach
            }
        });
        //}).trigger('keyup'); // Trigger keyup event once on page load

        $(document).on('keyup', '.priceChange', function(ev){
            var enteredPriceVal = $(this).val();
            var currentHours = $(this).data('val');
            var modelId = $('#model').val();
            $.ajax({
                url: sitePath + "/admin/check-updatedprice",
                method:"GET",
                async: false,
                cache: false,
                data: {
                    "_token":  $('meta[name="csrf-token"]').attr('content'), 
                    "currentHours":currentHours,
                    "enteredPriceVal":enteredPriceVal,
                    "modelId":modelId,
                },
                success: function(response) {
                    var minPrice = response.minPrice;
                    var maxPrice = response.maxPrice;
                    if(response.status == false){
                        $('#error_' + currentHours).css('display', 'block');
                        $('#error_'+currentHours).removeAttr('hidden');
                        $('#error_'+currentHours).text("Rental Price must be between "+minPrice+" and "+maxPrice);
                        isPriceValid = false; 
                    }else{
                        $('#error_' + currentHours).attr('hidden', true);
                        $('#error_'+currentHours).text("");
                        isPriceValid = true; 
                    }
                },
            });
            //if($(this).val() <= 0){
            if($(this).val() != '' && $(this).val() == 0){
                var currentHours = $(this).data('val');
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    if(hours < currentHours){
                        $('#priceval_'+hours).val(0);
                    }
                @endforeach
            }
        });

    });

    $(document).ready(function() {
        $('.delete-document').on('click', function() {
            var documentId = $(this).data('document-id');
            var deleteButton = $(this); // Store reference to this
            var docType = $(this).attr('data-doc-type');
           
            $.ajax({
                type: 'POST',
                url: '{{ route("delete.document") }}',
                data: {
                    documentId: documentId,
                    _token: getCsrfToken() // Include CSRF token
                },
                success: function(response) {
                    deleteButton.closest('.image-display').remove();
                    /*if(docType == 'rc')
                        $('#doc_rc_img').val('');
                    else if(docType == 'puc')
                        $('#doc_puc_img').val('');
                    else if(docType == 'insurance')
                        $('#doc_insurance_img').val('');*/

                    displayMessage('success', 'Document image deleted successfully');
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting image:', error);
                    displayMessage('error', 'Error deleting image. Please try again later.');
                }
            });
        });

    });

    // Function to get CSRF token
    function getCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    // Function to display a message
    function displayMessage(type, message) {
        // Create a message element
        var messageElement = $('<div class="alert alert-' + type + '">' + message + '</div>');
        // Append the message to a container (e.g., a div with id="messages")
        $('#messages').append(messageElement);
        // Fade out the message after a certain duration (e.g., 5 seconds)
        setTimeout(function() {
            messageElement.fadeOut();
        }, 3000);
    }


$( document ).ready(function() {
    setCategoryManufacurer(typeId);
    setModels(manufacturerId);
});
    
</script>