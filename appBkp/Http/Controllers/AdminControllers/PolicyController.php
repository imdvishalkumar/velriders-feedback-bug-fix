<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Policy;

class PolicyController extends Controller
{
    public function getPolicies(Request $request){

        hasPermission('policies-management');
        return view('admin.policies.list');    
    }

    public function getAllPolicies()
    {
        $policies = Policy::get();
        return $policies;
    }
    
    public function editPolicy(Request $request)
    {
        hasPermission('policies-management');
        $policy = Policy::where('policy_id', $request->id)->first();
        return view('admin.policies.edit', compact('policy'));
    }

    public function updatePolicy(Request $request, $id){
        hasPermission('policies-management');
        $policy = Policy::where('policy_id', $id)->first();
        $oldVal = clone $policy;

        if($policy != ''){
            $policy->title = isset($request->policy_title)?$request->policy_title:'';
            $policy->content = isset($request->policy_content)?$request->policy_content:'';
            $policy->save();

            $newVal = $policy;
            $differences = compareArray($oldVal, $newVal);
           
            if(isset($differences) && is_countable($differences) && count($differences) > 0){
                logAdminActivity('Policy Updation', $oldVal, $newVal);
            }
        }

        return redirect()->route('admin.policies')->with('success', 'Policy Updated Successfully!');
    }

    public function resetPolicy(Request $request)
    {
        $policy = Policy::where('policy_id', $request->id)->first();
        if($policy != ''){
            $policy->content = NULL;
            $policy->save();
        }

        return redirect()->route('admin.policies')->with('success', 'Policy reset Successfully');
    }
}
