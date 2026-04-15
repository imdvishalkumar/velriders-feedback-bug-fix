<table class="table table-bordered table-striped table-responsive">
    <thead>
        <tr>
            <th>Customer Name</th>
            <th>Documents</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data['customerDocumentsGrouped'] as $customer_id => $documents)
            <tr>
                <td>{{ $documents->first()->customer->firstname }} {{ $documents->first()->customer->lastname }} <br> {{ $documents->first()->customer->email }} <br> {{ $documents->first()->customer->country_code }} {{ $documents->first()->customer->mobile_number }}</td>
                <td>
                    <table id="example1" class="table table-bordered table-striped table-responsive">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Document Type</th>
                                <th>Document Number</th>
                                <th>Document Front Image</th>
                                <th>Document Back Image</th>
                                <th>Expire Date</th>
                                <th>Approved By</th>
                                <th>Rejection Message</th>
                                <th>Vehicle Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $document)
                                <tr>
                                    <td>{{ $document->document_id }}</td>
                                    <td>
                                        @if ($document->document_type === 'dl')
                                            Driving License
                                        @elseif($document->document_type === 'govtid')
                                            Government ID
                                        @endif
                                    </td>
                                    <td>{{ $document->id_number }}</td>

                                    <td>
                                        <a href="#" data-toggle="modal"
                                            data-target="#documentFrontModal{{ $document->document_id }}">
                                            <img src="{{ $document->document_image_url }}" alt="Front Image"
                                                style="width: 100px; height: 100px" loading="lazy">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" data-toggle="modal"
                                            data-target="#documentBackModal{{ $document->document_id }}">
                                            <img src="{{ $document->document_back_image_url }}" alt="Back Image"
                                                style="width: 100px; height: 100px" loading="lazy">
                                        </a>
                                    </td>
                                    <td>{{ $document->expiry_date }}</td>
                                    <td>{{ $document->approvedBy->username ?? 'N/A' }}</td>
                                    <td>{{ $document->message }}</td>
                                    <td>
                                        @if ($document->vehicle_type == 'car')
                                            Car
                                        @elseif ($document->vehicle_type == 'bike')
                                            Bike
                                        @elseif ($document->vehicle_type == 'car/bike')
                                            Car & Bike
                                        @else
                                            Unknown
                                        @endif
                                    </td>
                                    <td>
                                        @if ($document->is_approved === 'awaiting_approval')
                                            <span class="badge badge-warning"
                                                style="font-size: 1.1em; padding: 8px 12px;">Awaiting Approval</span>
                                        @elseif($document->is_approved === 'approved')
                                            <span class="badge badge-success"
                                                style="font-size: 1.1em; padding: 8px 12px;">Approved</span>
                                        @elseif($document->is_approved === 'rejected')
                                            <span class="badge badge-danger"
                                                style="font-size: 1.1em; padding: 8px 12px;">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- @if ($document->is_approved === 'awaiting_approval') --}}
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#approveDocumentModal{{ $document->document_id }}"> Approve</button>
                                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectDocumentModal{{ $document->document_id }}">Reject</button>
                                        @php 
                                            $blockStatus = '';
                                            $blockText = 'Block';
                                            if($document->is_blocked && $document->is_blocked != 0){
                                                $blockStatus = 'checked';
                                                $blockText = 'Un-Block';
                                            }
                                        @endphp
                                        <div class=""><input type="checkbox" class="blockToggle" data-id="{{$document->document_id}}" {{$blockStatus}}><label class="">{{$blockText}}</label></div>

                                        {{-- @endif --}}
                                    </td>
                                </tr>

                                <!-- Front Image Modal -->
                                <div class="modal fade" id="documentFrontModal{{ $document->document_id }}" tabindex="-1"
                                    role="dialog" aria-labelledby="documentFrontModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="documentFrontModalLabel">Front Image</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <img src="{{ $document->document_image_url }}" class="img-fluid"
                                                    alt="Front Image">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Front Image Modal -->

                                <!-- Back Image Modal -->
                                <div class="modal fade" id="documentBackModal{{ $document->document_id }}" tabindex="-1"
                                    role="dialog" aria-labelledby="documentBackModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="documentBackModalLabel">Back Image</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <img src="{{ $document->document_back_image_url }}" class="img-fluid"
                                                    alt="Back Image">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Back Image Modal -->
                                <!-- Approve Document Modal -->
                                <div class="modal fade" id="approveDocumentModal{{ $document->document_id }}" tabindex="-1" role="dialog" aria-labelledby="approveDocumentModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="approveDocumentModalLabel">Approve Document</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- {{ route('admin.customer_documents.approve', $document->document_id) }} -->
                                                <form action="" method="POST" class="approve-form" data-document-id="{{ $document->document_id }}" data-document-type="{{ $document->document_type }}">
                                                    @csrf
                                                    <input type="hidden" id="approveUrl_{{$document->document_id}}" value="{{route('admin.customer_documents.approve', ['id' => $document->document_id])}}"> 
                                                    <input type="hidden" id="rejectUrl_{{$document->document_id}}" value="{{route('admin.customer_documents.reject', ['id' => $document->document_id])}}"> 
                                                    <div class="form-group">
                                                        @if ($document->document_type !== 'govtid')
                                                        <label for="vehicleType">Vehicle Type:</label><br>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="checkbox" id="carCheckbox{{ $document->document_id }}" name="vehicle_type[]" value="car">
                                                            <label class="form-check-label" for="carCheckbox{{ $document->document_id }}">Car</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="checkbox" id="bikeCheckbox{{ $document->document_id }}" name="vehicle_type[]" value="bike">
                                                            <label class="form-check-label" for="bikeCheckbox{{ $document->document_id }}">Bike</label>
                                                        </div>
                                                        <div id="vehicleTypeError{{ $document->document_id }}" class="text-danger" style="display: none;">Please select at least one vehicle type.</div>                                                                            
                                                        @endif
                                                    </div>
                                                    <button type="submit" class="btn btn-primary approveBtn" data-document-id="{{ $document->document_id }}" data-document-type="{{ $document->document_type }}">Approve</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- End Approve Document Modal -->

                                <!-- Reject Document Modal -->
                                <div class="modal fade" id="rejectDocumentModal{{ $document->document_id }}"
                                    tabindex="-1" role="dialog" aria-labelledby="rejectDocumentModalLabel{{ $document->document_id }}"
                                    aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="rejectDocumentModalLabel{{ $document->document_id }}">Reject Document</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- {{ route('admin.customer_documents.reject', $document->document_id) }} -->
                                                <form action="" method="POST">
                                                    @csrf
                                                    <div class="form-group">
                                                        <label for="rejectionType{{ $document->document_id }}">Rejection Type:</label><br>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input messageType" type="radio"
                                                                name="rejection_type{{ $document->document_id }}" id="customRejection{{ $document->document_id }}"
                                                                value="custom" checked>
                                                            <label class="form-check-label" for="customRejection{{ $document->document_id }}">Custom Message</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input messageType" type="radio"
                                                                name="rejection_type{{ $document->document_id }}" id="templateRejection{{ $document->document_id }}"
                                                                value="template">
                                                            <label class="form-check-label" for="templateRejection{{ $document->document_id }}">Template</label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group" id="customMessageGroup{{ $document->document_id }}">
                                                        <label for="rejectionMessage{{ $document->document_id }}">Custom Rejection Message:</label>
                                                        <textarea class="form-control" id="custom_rejection_message{{ $document->document_id }}" name="custom_rejection_message" rows="3"></textarea>
                                                    </div>
                                                    <div class="form-group" id="templateSelectGroup{{ $document->document_id }}"
                                                        style="display: none;">
                                                        <label for="rejectionTemplate{{ $document->document_id }}">Select Rejection
                                                            Template:</label>
                                                        <select name="rejection_reason_id" id="rejection_message_id{{ $document->document_id }}" class="form-control">
                                                            <option value="">Select a rejection message</option>
                                                            @foreach ($rejectionReasons as $rejectionMessage)
                                                                <option value="{{ $rejectionMessage->id }}">
                                                                    {{ $rejectionMessage->reason }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-danger rejectBtn" data-document-id="{{ $document->document_id }}" data-document-type="{{ $document->document_type }}">Reject</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Reject Document Modal -->
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="row align-items-center m-3">
    @if( $data['total'] > 0)
        <div class="col-sm-3">
            <h4 class="card-title m-0 font-size-16 text-dark font-weight-semibold">
                Showing {{ $data['from'] }} to {{ $data['to'] }} of {{ $data['total'] }} entries
            </h4>
        </div>
        <div class="col-sm-9">
            <div class="overflow-auto">
                <nav>
                    <ul class="pagination justify-content-end mb-0 line-hight-normal">
                        <!-- Previous Button -->
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0);" 
                               @if($data['pageno'] != '1') 
                                   onclick="load_page_details( {{ $data['pageno'] - 1}} )"  
                               @endif>
                                <i class="fa fa-angle-double-left" aria-hidden="true"></i>
                            </a>
                        </li>
                        <!-- Pagination Logic -->
                        @php
                            $currentPage = $data['pageno'];
                            $totalPages = $data['total_pages'];
                            $start = max($currentPage - 2, 1);
                            $end = min($currentPage + 2, $totalPages);
                        @endphp
                        <!-- First page always -->
                        @if ($start > 1)
                            <li class="page-item">
                                <a class="page-link" href="javascript:void(0);" onclick="load_page_details(1)">1</a>
                            </li>
                            @if($start > 2)
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            @endif
                        @endif
                        <!-- Page numbers between -->
                        @for($i = $start; $i <= $end; $i++)
                            <li class="page-item @if($i == $currentPage) active @endif ">
                                <a class="page-link" href="javascript:void(0);" onclick="load_page_details({{ $i }})">
                                    {{ $i }}
                                </a>
                            </li>
                        @endfor
                        <!-- Last page always -->
                        @if ($end < $totalPages)
                            @if($end < $totalPages - 1)
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            @endif
                            <li class="page-item">
                                <a class="page-link" href="javascript:void(0);" onclick="load_page_details({{ $totalPages }})">{{ $totalPages }}</a>
                            </li>
                        @endif
                        <!-- Next Button -->
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0);" 
                               @if($currentPage < $totalPages) 
                                   onclick="load_page_details( {{ $currentPage + 1 }} )"  
                               @endif>
                                <i class="fa fa-angle-double-right" aria-hidden="true"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    @endif
</div>

{{--<div class="row align-items-center m-3">
    @if( $data['total'] > 0)
        <div class="col-sm-6">
            <h4 class="card-title m-0 font-size-16 text-dark font-weight-semibold"> Showing {{ $data['from'] }} to {{ $data['to'] }} of 
                {{ $data['total'] }} entries
            </h4>
        </div>
        <div class="col-sm-6">
            <nav>
                <ul class="pagination justify-content-end mb-0 line-hight-normal">
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0);" @if($data['pageno'] != '1') onclick="load_page_details( {{ $data['pageno'] - 1}} )"   @endif>
                            <i class="fa fa-angle-double-left" aria-hidden="true"  >
                            </i>
                        </a>
                    </li>
                    @for($i = 1;$i<= $data['total_pages']; $i++)
                        <li class="page-item @if($i == $data['pageno']) active @endif ">
                            <a class="page-link" href="javascript:void(0);" onclick="load_page_details({{ $i }} )">
                                {{ $i }}
                            </a>
                        </li>
                    @endfor
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0);" @if($data['pageno'] < $data['total_pages']) onclick="load_page_details( {{ $data['pageno'] + 1 }} )"  @endif>
                            <i class="fa fa-angle-double-right" aria-hidden="true" ></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    @endif
</div>--}}