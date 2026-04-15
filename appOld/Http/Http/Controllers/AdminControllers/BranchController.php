<?php

namespace App\Http\Controllers\AdminControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{Branch, Vehicle, City};

class BranchController extends Controller
{
    public function getAllBranchList(){
        hasPermission('branches');

        $cities = City::where('is_deleted', 0)->get();
        return view('admin.branches', compact('cities'));
    }

    public function getAllBranchs(Request $request)
    {
        $branches = Branch::with('city')->where('is_deleted', 0)->get();
        return response()->json([
            'data' => $branches,
            'status' => true,
        ]);
    }

    public function getBranch(Request $request)
    {
        $branch = Branch::find($request->id);
        return response()->json([
            'data' => $branch,
            'status' => true,
        ]);
    }

    public function createBranch(Request $request)
    {
        $branch = new Branch();
        $branch->name = $request->name;
        $branch->city_id = isset($request->city_name)?$request->city_name:'';
        $branch->manager_name = isset($request->manager_name)?$request->manager_name:'';
        $branch->address = $request->address;
        $branch->phone = isset($request->phone)?$request->phone:'';
        $branch->email = isset($request->email)?$request->email:'';
        $branch->latitude = $request->latitude;
        $branch->longitude = $request->longitude;
        $branch->opening_hours = isset($request->opening_hours)?$request->opening_hours:'';
        $branch->is_head_branch = isset($request->is_head_branch)?$request->is_head_branch:0;
        $branch->save();
        logAdminActivity("Branch Creation", $branch);

        return response()->json([
            'data' => $branch,
            'status' => true,
            'message' => 'Branch created successfully.',
        ]);
    }

    public function updateBranch(Request $request)
    {   
        $branch = Branch::find($request->id);
        $oldVal = clone $branch;

        $branch->name = $request->name;
        $branch->city_id = isset($request->city_name)?$request->city_name:'';
        $branch->manager_name = $request->manager_name;
        $branch->address = $request->address;
        $branch->phone = $request->phone;
        $branch->email = $request->email;
        $branch->latitude = $request->latitude;
        $branch->longitude = $request->longitude;
        $branch->opening_hours = $request->opening_hours;
        $branch->is_head_branch = isset($request->is_head_branch)?$request->is_head_branch:0;
        $branch->save();

        if($request->is_head_branch == 1){
            $checkBranch = Branch::where('branch_id', '!=', $request->id)->where('city_id', $request->city_name)->where('is_head_branch', 1)->get();
            if(is_countable($checkBranch) && count($checkBranch) > 0){
                foreach($checkBranch as $k => $v){
                    $v->is_head_branch = 0;
                    $v->save();
                }
            }
        }
            
        $newVal = $branch;
        $differences = compareArray($oldVal, $newVal);
        if(isset($differences) && is_countable($differences) && count($differences) > 0){
            logAdminActivity('Branch Updation', $oldVal, $newVal);
        }

        return response()->json([
            'data' => $branch,
            'status' => true,
            'message' => 'Branch updated successfully.',
        ]);
    }

    public function deleteBranch(Request $request)
    {
        $message = 'Something went Wrong';
        $status = false;
        $branch = Branch::find($request->id);
        $branchCnt = Vehicle::where('branch_id', $request->id)->count();
        if($branchCnt > 0){
            $message = 'You can not delete this Branch due to its associated with any vehicle.';
            $status = false;
        }else{
            $branch->is_deleted = 1;
            $branch->save();
            $message = 'Branch deleted successfully.';
            $status = true;
            logAdminActivity("Branch Deletion", $branch);
        }

        return response()->json([
            'data' => $branch,
            'message' => $message,
            'status' => $status,
        ]);

    }
}