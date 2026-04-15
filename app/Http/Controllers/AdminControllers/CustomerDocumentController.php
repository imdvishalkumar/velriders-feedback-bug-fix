<?php

namespace App\Http\Controllers\AdminControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{CustomerDocument, RejectionReason, Customer};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CustomerDocumentController extends Controller
{
    public function fetchDocuments()
    {
        $documents = CustomerDocument::with('customer', 'approvedBy')->get();
        return response()->json([
            'data' => $documents,
            'status' => true,
        ]);
    }

    public function index()
    {
        hasPermission('customer-documents');
        /*$customerDocumentsGrouped = CustomerDocument::with('customer', 'approvedBy', 'rejectionReason')
        ->orderBy('created_at', 'desc') // Order by the latest created_at
        ->get()
        ->groupBy('customer_id');
        $rejectionReasons = RejectionReason::all();*/
/*-------------*/
        /*$customerDocumentsGrouped = CustomerDocument::with(['customer' => function($query){
            $query->select('customer_id', 'firstname', 'lastname', 'email', 'country_code', 'mobile_number');
        }])
        ->with(['approvedBy' => function($query){
            $query->select('admin_id', 'username');
        }])
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('customer_id');*/
/*-------------*/
        /*$grouped = [];
        CustomerDocument::with([
            'customer' => function($query){
                $query->select('customer_id', 'firstname', 'lastname', 'email', 'country_code', 'mobile_number');
            },
            'approvedBy' => function($query){
                $query->select('admin_id', 'username');
            }
        ])
        ->orderBy('created_at', 'desc')
        ->chunk(10, function ($customerDocuments) use (&$grouped) {
            foreach ($customerDocuments as $document) {
                $grouped[$document->customer_id][] = $document;
            }
        });
        $rejectionReasons = RejectionReason::select('id', 'reason')->get();
        return view('admin.customer_documents.index', compact('grouped', 'rejectionReasons'));*/
        
        //$rejectionReasons = RejectionReason::select('id', 'reason')->get();
        //return view('admin.customer_documents.index', compact('rejectionReasons', 'data'));
        $customers = Customer::select('customer_id', 'firstname', 'lastname')->whereHas('customerDocs')->where('is_deleted', 0)->get();

        return view('admin.customer_documents.index', compact('customers'));
    }

    public function customerDocumentAjax(Request $request){
        hasPermission('customer-documents');

        $pageno = $request->pageno ?? 1;
        $no_of_records_per_page = 10;
        $offset = ($pageno - 1) * $no_of_records_per_page;

        // Get total rows and group them
        $total_rows = CustomerDocument::select('customer_id');
        if(isset($request->customerId) && $request->customerId != ''){
            $total_rows = $total_rows->where('customer_id', $request->customerId);
        }
        $total_rows = $total_rows->groupBy('customer_id')->get()->count();

        $total_pages = ceil($total_rows / $no_of_records_per_page);
        /*$total_rows_array = CustomerDocument::with(['customer:customer_id,firstname,lastname,email,country_code,mobile_number', 'approvedBy:admin_id,username'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('customer_id');
        $total_rows = $total_rows_array->count();
        $total_pages = ceil($total_rows / $no_of_records_per_page);*/
        /*$total_rows_array = CustomerDocument::with(['customer' => function($query){
            $query->select('customer_id', 'firstname', 'lastname', 'email', 'country_code', 'mobile_number');
        }])->with(['approvedBy' => function($query){
            $query->select('admin_id', 'username');
        }])->orderBy('created_at', 'desc')->get()->groupBy('customer_id');
        $total_rows = count($total_rows_array);
        $total_pages = ceil($total_rows / $no_of_records_per_page);*/

        // Paginate records
        $customerDocumentsGrouped = CustomerDocument::with(['customer:customer_id,firstname,lastname,email,country_code,mobile_number', 'approvedBy:admin_id,username']);
        if(isset($request->customerId) && $request->customerId != ''){
            $customerDocumentsGrouped = $customerDocumentsGrouped->where('customer_id', $request->customerId);
        }
        $customerDocumentsGrouped = $customerDocumentsGrouped->orderBy('created_at', 'desc')
                                    ->offset($offset)
                                    ->limit($no_of_records_per_page)
                                    ->get()
                                    ->groupBy('customer_id');
        /*$customerDocumentsGrouped = CustomerDocument::with(['customer' => function($query){
            $query->select('customer_id', 'firstname', 'lastname', 'email', 'country_code', 'mobile_number');
        }])
        ->with(['approvedBy' => function($query){
            $query->select('admin_id', 'username');
        }])
        ->orderBy('created_at', 'desc')
        ->offset($offset)
        ->limit($no_of_records_per_page)
        ->get()
        ->groupBy('customer_id');*/

        // Prepare pagination details
        $data = [
            'pageno' => $pageno,
            'customerDocumentsGrouped' => $customerDocumentsGrouped,
            'total_pages' => $total_pages,
            'from' => $offset + 1,
            'to' => min($no_of_records_per_page * $pageno, $total_rows),
            'total' => $total_rows
        ];

        /*$data['pageno'] = $pageno;
        $data['customerDocumentsGrouped'] = $customerDocumentsGrouped;
        $data['total_pages'] = $total_pages;
        $data['from'] = ($offset + 1);
        if ($no_of_records_per_page * $pageno > $total_rows) {
            $data['to'] = $total_rows;
        } else {
            $data['to'] = $no_of_records_per_page * $pageno;
        }
        $data['total'] = $total_rows;*/

        $rejectionReasons = RejectionReason::select('id', 'reason')->get();
        return view('admin.customer_documents.ajax-index', compact('rejectionReasons', 'data'));
    }

    public function approve(Request $request, $id)
    {
        // Validate the request data
        /*$request->validate([
            'vehicle_type' => $request->input('document_type') === 'dl' ? 'required' : '', // Vehicle type is required if document type is 'dl'
        ]);*/
    
        $document = CustomerDocument::findOrFail($id);

        $doc = DB::table('customer_documents')->select('is_approved', 'rejection_message_id', 'custom_rejection_message', 'approved_by', 'vehicle_type')->where('document_id',$id)->first();
        $oldVal = clone $doc;
        //$vehicleType = $request->input('vehicle_type');

        // If no vehicle type is provided or it's an empty array, set it to null
        $typeArr = [];
        if($request->car_status != '' && $request->car_status == 'car'){
            $typeArr [] = $request->car_status;
        }
        if($request->bike_status != '' && $request->bike_status == 'bike'){
            $typeArr [] = $request->bike_status;
        }
        $vehicleType = empty($typeArr) ? null : implode('/', $typeArr);
        
        // Create an array for the fields to update
        $updateFields = [
            'is_approved' => 'approved',
            'rejection_message_id' => null,
            'custom_rejection_message' => null,
            'approved_by' => auth()->guard('admin_web')->id(),
            'is_approved_datetime' => date('Y-m-d H:i:s'),
        ];
    
        // Only include vehicle_type in the update if it's not null
        if ($vehicleType !== null) {
            $updateFields['vehicle_type'] = $vehicleType;
        }
        $newVal = $updateFields;
        
        $document->update($updateFields);
        $oldVal->document_id = $id;
        $newVal['document_id'] = $id;
        logAdminActivity('Customer Document Approval', $oldVal, $newVal);

        return response()->json('Document approved successfully');
    }
            
    public function reject(Request $request, $id)
    {
        $document = CustomerDocument::findOrFail($id);
        $doc = DB::table('customer_documents')->select('is_approved', 'rejection_message_id', 'custom_rejection_message', 'approved_by')->where('document_id',$id)->first();
        $oldVal = clone $doc;

        /*$rejectionReasonId = $request->input('rejection_reason_id');
        $customRejectionReason = $request->input('custom_rejection_message');*/
        $updateFields = [
            'is_approved' => 'rejected',
            'approved_by' => auth()->guard('admin_web')->id(),
            'is_approved_datetime' => date('Y-m-d H:i:s'),
        ];
        if(isset($request->type) && $request->type == 'custom'){
            $updateFields['custom_rejection_message'] = $request->value;
        }elseif(isset($request->type) && $request->type == 'template'){
            $updateFields['rejection_message_id'] = $request->value;
        }
        /*'rejection_message_id' => $rejectionReasonId ? $rejectionReasonId : null,
        'custom_rejection_message' => $rejectionReasonId ? null : $customRejectionReason,*/
        
        $newVal = $updateFields;
        $document->update($updateFields);
        $oldVal->document_id = $id;
        $newVal['document_id'] = $id;
        logAdminActivity('Customer Document Rejection', $oldVal, $newVal);

        return response()->json('Document rejected successfully');
    }

    public function toggleDcoumentStatus(Request $request)
    {
        $document = CustomerDocument::find($request->document_id);
        $document->is_approved = $request->status == 1 ? 0 : 1;
        if ($request->status == 1) {
            $document->approved_by = null;
        } else {
            $document->approved_by = auth()->guard('admin_web')->user()->admin_id;
        }
        $document->save();
        return response()->json([
            'message' => 'Document status updated successfully',
            'status' => true,
        ]);
    }

    public function blockCustomerDocument(Request $request)
    {   
        $docId = $request->docId;
        $customerDoc = CustomerDocument::find($docId);
        $customerDoc->is_blocked =  $request->status == 'blocked' ? 1 : 0;
        $customerDoc->save();

        if($request->status == 'blocked'){
            logAdminActivity("Customer Document Block Activity", $customerDoc);
        }
        else{
            logAdminActivity("Customer Document Un-Block Activity", $customerDoc);
        }
    }

    public function addDocument(Request $request){
        hasPermission('customer-documents');
        $customers = Customer::select('customer_id', 'firstname', 'lastname', 'email', 'mobile_number')->where(['is_deleted' => 0, 'is_blocked' => 0, 'is_test_user' => 0])->get();

        return view('admin.add-documents', compact('customers'));
    }

    public function storeUserDocument(Request $request){
       if($request->doc_type == 'govtid' && $request->govtid_cid != ''){
            if ($request->hasFile('gtdoc_front_img')) {
                $documentImage = $request->file('gtdoc_front_img');
                $frontImageUrl = 'govt_'.$request->govtid_cid.'_'.time() . '_front.' . $documentImage->getClientOriginalExtension();
                $documentImage->move(public_path('images/customer_documents'), $frontImageUrl);
            }
            $backImageUrl = null;
            if ($request->hasFile('gtdoc_back_img')) {
                $documentBackImage = $request->file('gtdoc_back_img');
                $backImageUrl = 'govt_'.$request->govtid_cid.'_'.time() . '_back.' . $documentBackImage->getClientOriginalExtension();
                $documentBackImage->move(public_path('images/customer_documents'), $backImageUrl);
            }
            $customerDocument = new CustomerDocument();
            $customerDocument->customer_id = $request->govtid_cid;
            $customerDocument->document_type = $request->doc_type;
            $customerDocument->id_number = $request->gtdoc_number;
            $customerDocument->is_approved = 1;
            $customerDocument->is_approved_datetime = date('Y-m-d H:i:s');
            $customerDocument->approved_by = Auth::guard('admin_web')->user()->admin_id;
            $customerDocument->document_image_url = $frontImageUrl;
            $customerDocument->document_back_image_url = $backImageUrl;
            $customerDocument->save();

            return redirect()->route('admin.customer_documents.index')->with('success', 'Document details are stored successfully!');

       }elseif($request->doc_type == 'dl' && $request->dl_cid != ''){
            $licenseVal = '';
            if(isset($request->license) && is_countable($request->license) && count($request->license) > 0){
                $licenseVal = implode('/',$request->license);
            }

            $customerDocument = new CustomerDocument();
            if ($request->hasFile('dldoc_front_img')) {
                $documentImage = $request->file('dldoc_front_img');
                $frontImageUrl = 'dl'.$request->dl_cid.'_'.time() . '_front.' . $documentImage->getClientOriginalExtension();
                $documentImage->move(public_path('images/customer_documents'), $frontImageUrl);
            }
            $backImageUrl = null;
            if ($request->hasFile('dldoc_back_img')) {
                $documentBackImage = $request->file('dldoc_back_img');
                $backImageUrl = 'dl'.$request->dl_cid.'_'.time() . '_back.' . $documentBackImage->getClientOriginalExtension();
                $documentBackImage->move(public_path('images/customer_documents'), $backImageUrl);
            }
            $customerDocument = new CustomerDocument();
            $customerDocument->customer_id = $request->dl_cid;
            $customerDocument->document_type = $request->doc_type;
            $customerDocument->id_number = $request->dldoc_number;
            $customerDocument->is_approved = 1;
            $customerDocument->is_approved_datetime = date('Y-m-d H:i:s');
            $customerDocument->approved_by = Auth::guard('admin_web')->user()->admin_id;
            $customerDocument->document_image_url = $frontImageUrl;
            $customerDocument->document_back_image_url = $backImageUrl;
            $customerDocument->vehicle_type = $licenseVal;
            $customerDocument->save();

            return redirect()->route('admin.customer_documents.index')->with('success', 'Document details are stored successfully!');
       }else{
            return redirect()->back()->with('error', 'Something went Wrong');
       }
    }

}
