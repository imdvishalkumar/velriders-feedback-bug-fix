@extends('templates.admin')

@section('page-title')
    Admin Activity Log
    @if (session('success'))
        <div id="success-message" class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
@endsection
<style>
.pagination-container {
    overflow-x: auto;
    white-space: nowrap;
    width: 100%;
    display: flex;
    justify-content: center;
}
.pagination {
    display: inline-flex;
    flex-wrap: nowrap;
}
</style>
@section('content')
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="table-responsive">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Log Id</th>
                                <th>Admin Details</th>
                                <th>Activity Description</th>
                                <th>Old Value</th>
                                <th>New Value</th>
                                <th>Created Date</th>
                                <th>Updated Date</th>
                            </tr>
                        </thead>
                        <tbody class="table-data">
                            @if(is_countable($adminActivityLog) && count($adminActivityLog) > 0)
                                @foreach($adminActivityLog as $k => $v)
                                  @php $adminDetails = ''; @endphp
                                   <tr>
                                        @php
                                            if($v['admin_id'] != null){
                                                $adminDetails .= ' <b>Admin ID - </b>'.$v['admin_id'].'<br/>';
                                            }
                                            if($v['adminDetails']['username'] != null){
                                                $adminDetails .= ' <b>User Name - </b>'.$v['adminDetails']['username'].'<br/>';
                                            }
                                            if($v['adminDetails']['role'] != null){
                                                $roleName = '-';
                                                if($v['adminDetails']['role'] == 1){
                                                    $roleName = 'Super Admin';
                                                }else if($v['adminDetails']['role'] == 2){
                                                    $roleName = 'Manager';
                                                }else if($v['adminDetails']['role'] == 3){
                                                    $roleName = 'Accountant';
                                                }
                                                $adminDetails .= ' <b>Role - </b>'.$roleName.'<br/>';
                                            }
                                        @endphp
                                        <td>{{$v->log_id}}</td>
                                        <td>{!! $adminDetails !!}</td>
                                        <td>{{$v->activity_description ?? '-'}}</td>
                                        <td><a href="javascript:void(0);" class="viewOldVal font-weight-bold" data-log-id="{{$v['log_id']}}">View Old Values</a></td>
                                        <td><a href="javascript:void(0);" class="viewNewVal font-weight-bold text-info" data-log-id="{{$v['log_id']}}">View New Values</a></td>
                                        <td>{{date('d-m-Y H:i', strtotime($v['created_at']))}}</td>
                                        <td>{{date('d-m-Y H:i', strtotime($v['updated_at']))}}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table> 
                </div>
                <div class="row m-3">
                    <div class="col-12 d-flex justify-content-center justify-content-md-end">
                        <div class="pagination-container">
                            {{ $adminActivityLog->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Log Values Display Modal -->
<div class="modal fade modal-ld" id="logValuesModal" tabindex="-1" role="dialog" aria-labelledby="logValuesModalTitle" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><label id="valueType"></label> Log Values</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="row" id="logValue">
                    
                
            </div>
          </div>
      </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('all_js/admin_js/settings.js') }}"></script>
@endpush
