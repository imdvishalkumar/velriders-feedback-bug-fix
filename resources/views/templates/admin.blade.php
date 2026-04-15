<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>

    <title>@yield('page-title')</title>

    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}" type="image/x-icon">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <!-- Notyf -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />

    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.css" integrity="sha512-bYPO5jmStZ9WI2602V2zaivdAnbAhtfzmxnEGh9RwtlI00I9s8ulGe4oBa5XxiC6tCITJH/QG70jswBhbLkxPw==" crossorigin="anonymous" referrerpolicy="no-referrer" /> 
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet"> -->
    <link href="{{ asset('plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}" rel="stylesheet"> 

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.css" integrity="sha512-n1PBkhxQLVIma0hnm731gu/40gByOeBjlm5Z/PgwNxhJnyW1wYG8v7gPJDT6jpk0cMHfL8vUGUVjz3t4gXyZYQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" integrity="sha512-nMNlpuaDPrqlEls3IX/Q56H36qvBASwb3ipuo3MxeWbsQB1881ox0cRv7UPTgBlriqoynt35KjEwgGUeUXIPnw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">


    @stack('styles')
    <style>
        .error{
            color:red;
            font-size: 15px;
        }
        .img-thumbnail {
            padding: .25rem;
            .
            .
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: .25rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .075);
            max-width: 100%;
        }
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
        }

        .dataTables_wrapper {
          /* Make the table wrapper responsive */
          width: 100%;
          overflow-x: auto; /* Enable horizontal scrolling if needed */
        }
        .dataTables_paginate {
          /* Adjust pagination styles for smaller screens */
          text-align: center;
          margin-top: 10px;
        }
        /* Optional: Add styles for active pagination button */
        .dataTables_paginate .paginate_button.current {
          background-color: #007bff; /* Blue background for active button */
          color: white;
        }
        /* Adjust table header styles */
        .dataTables_scrollHeadInner {
          width: 100%;
        }
        .dataTables_scrollHeadInner table th {
          /* Style table headers */
          padding: 5px 10px;
          text-align: left;
        }
        /* Adjust table body styles */
        .dataTables_scrollBody {
          /* Style table body */
          width: 100%;
          height: 300px; /* Adjust height as needed */
          overflow-y: auto; /* Enable vertical scrolling */
        }
        .dataTables_scrollBody table td {
          /* Style table cells */
          padding: 5px 10px;
          text-align: left;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <!-- <li class="nav-item d-none d-sm-inline-block">
                    <a href="index3.html" class="nav-link">Home</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="#" class="nav-link">Contact</a>
                </li> -->
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                        <i class="fas fa-search"></i>
                    </a>
                    <div class="navbar-search-block">
                    <form class="form-inline">
                        <div class="input-group input-group-sm">
                            <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                            <div class="input-group-append">
                                <button class="btn btn-navbar" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-comments"></i>
                        <span class="badge badge-danger navbar-badge">3</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <a href="#" class="dropdown-item">
                            <div class="media">
                                <img src="dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                                <div class="media-body">
                                    <h3 class="dropdown-item-title">
                                    Brad Diesel
                                    <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                                    </h3>
                                    <p class="text-sm">Call me whenever you can...</p>
                                    <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                        <div class="media">
                            <img src="dist/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                            <div class="media-body">
                            <h3 class="dropdown-item-title">
                            John Pierce
                                <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                            </h3>
                            <p class="text-sm">I got your message bro</p>
                            <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                        </div>
                    </div>
                    </a>
                    <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <div class="media">
                                <img src="dist/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                                <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    Nora Silvester
                                    <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                                </h3>
                                <p class="text-sm">The subject goes here</p>
                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge">15</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header">15 Notifications</span>
                        <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">
                            <i class="fas fa-envelope mr-2"></i> 4 new messages
                            <span class="float-right text-muted text-sm">3 mins</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">
                            <i class="fas fa-users mr-2"></i> 8 friend requests
                            <span class="float-right text-muted text-sm">12 hours</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">
                            <i class="fas fa-file mr-2"></i> 3 new reports
                            <span class="float-right text-muted text-sm">2 days</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-widget="control-sidebar" data-controlsidebar-slide="true" href="#" role="button">
                    <i class="fas fa-th-large"></i>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="{{ route('admin.users') }}" class="brand-link">
                <img src="{{ asset('images/logo.png') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
                    style="opacity: .8">
                <span class="brand-text font-weight-light">VelRiders</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user (optional) -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="info">
                        <a class="d-block">{{ ucwords(auth()->guard('admin_web')->user()->username) }}</a>
                    </div>
                </div>
                @php
                   $authUser = auth('admin_web')->user();
                @endphp
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a href="{{route('admin.dashboard')}}" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>
                                    Dashboard
                                </p>
                            </a>
                        </li>
                        @haspermission('admins', 'admin_web')
                        <!-- Users -->
                        <li class="nav-item">
                            <a href="{{ route('admin.users') }}" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>
                                    Admins
                                </p>
                            </a>
                        </li>
                        @endhaspermission 
                        @php $permissionArr = ['vehicle-types', 'vehicle-categories', 'vehicle-fuel-types', 'vehicle-transmissions', 'vehicle-features', 'vehicle-manufacturers', 'vehicle-models', 'vehicle']; @endphp
                        @canany($permissionArr ,$authUser)
                        <!-- Vehicle Management -->
                        <li class="nav-item has-treeview {{ Request::is('admin/vehicle-*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-car"></i>
                                <p>
                                    Vehicle Management
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @haspermission('vehicle-types', 'admin_web')
                                <li class="nav-item">
                                    <a href="{{ route('admin.vehicle-types') }}" class="nav-link">
                                        <i class="nav-icon fas fa-road"></i>
                                        <p>Types</p>
                                    </a>
                                </li>
                                @endhaspermission 
                                @haspermission('vehicle-categories', 'admin_web')
                                <li class="nav-item">
                                    <a href="{{ route('admin.vehicle-categories') }}" class="nav-link">
                                        <i class="nav-icon fas fa-road"></i>
                                        <p>Categories</p>
                                    </a>
                                </li>
                                @endhaspermission 
                                @haspermission('vehicle-fuel-types', 'admin_web')
                                <li class="nav-item">
                                    <a href="{{ route('admin.fuel-types') }}" class="nav-link">
                                        <i class="nav-icon fas fa-road"></i>
                                        <p>Fuel Types</p>
                                    </a>
                                </li>
                                @endhaspermission 
                                @haspermission('vehicle-transmissions', 'admin_web')
                                <li class="nav-item">
                                    <a href="{{ route('admin.vehicle-transmission') }}" class="nav-link">
                                        <i class="nav-icon fas fa-road"></i>
                                        <p>Transmissions</p>
                                    </a>
                                </li>
                                @endhaspermission 
                                @haspermission('vehicle-features', 'admin_web')
                                <li class="nav-item">
                                    <a href="{{ route('admin.vehicle-features') }}" class="nav-link">
                                        <i class="nav-icon fas fa-swatchbook"></i>
                                        <p>Features</p>
                                    </a>
                                </li>
                                @endhaspermission 
                                @haspermission('vehicle-manufacturers', 'admin_web')
                                <li class="nav-item">
                                    <a href="{{ route('admin.manufacturers') }}" class="nav-link">
                                        <i class="nav-icon fas fa-truck-loading"></i>
                                        <p>Manufacturers</p>
                                    </a>
                                </li>
                                @endhaspermission 
                                @haspermission('vehicle-models', 'admin_web')
                                <li class="nav-item">
                                    <a href="{{ route('admin.vehicle-models') }}" class="nav-link">
                                        <i class="nav-icon fas fa-cubes"></i>
                                        <p>Models</p>
                                    </a>
                                </li>
                                @endhaspermission 
                                @haspermission('vehicle', 'admin_web')
                                <li class="nav-item">
                                    <a href="{{ route('admin.vehicles') }}" class="nav-link">
                                        <i class="nav-icon fas fa-car"></i>
                                        <p>Vehicles</p>
                                    </a>
                                </li>  
                                @endhaspermission  
                            </ul>
                        </li>
                        @endcanany
                        @haspermission('cities', 'admin_web')
                        <!-- Cities -->
                        <li class="nav-item">
                            <a href="{{ route('admin.cities') }}" class="nav-link">
                                <i class="nav-icon fas fa-city"></i>
                                <p>
                                    Cities
                                </p>
                            </a>
                        </li>
                        @endhaspermission
                        @haspermission('branches', 'admin_web')
                        <!-- Branches -->
                        <li class="nav-item">
                            <a href="{{ route('admin.branches') }}" class="nav-link">
                                <i class="nav-icon fas fa-code-branch"></i>
                                <p>
                                    Branches
                                </p>
                            </a>
                        </li>
                        @endhaspermission  
                        @haspermission('customers', 'admin_web')
                        <!-- Customers -->
                        <li class="nav-item">
                            <a href="{{ route('admin.customers.index') }}" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    Customers
                                </p>
                            </a>
                        </li>
                        @endhaspermission  
                        @haspermission('customer-documents', 'admin_web')
                        <!-- Customers Documents -->
                        <li class="nav-item">
                            <a href="{{ route('admin.customer_documents.index') }}" class="nav-link">
                                <i class="nav-icon fas fa-print"></i>
                                <p>
                                    Customers Documents
                                </p>
                            </a>
                        </li>
                        @endhaspermission
                        @haspermission('coupon-codes', 'admin_web')
                        <!-- Coupons -->
                        <li class="nav-item">
                            <a href="{{ route('admin.coupon.coupons') }}" class="nav-link">
                                <i class="nav-icon fas fa-tag"></i>
                                <p>Coupon Codes</p>
                            </a>
                        </li>
                        @endhaspermission

                        @haspermission('car-host-management', 'admin_web')
                        <!-- Coupons -->
                        <li class="nav-item">
                            <a href="{{ route('admin.carhost-mgt') }}" class="nav-link">
                                <i class="fa fa-car fa-lg" aria-hidden="true"></i> <p>Car Host Management</p>
                            </a>
                        </li>
                        @endhaspermission

                        @haspermission('booking-history', 'admin_web')
                        <!-- Booking History -->
                        <li class="nav-item">
                            <a href="{{ route('admin.bookings') }}" class="nav-link">
                                <i class="nav-icon fa fa-book" aria-hidden="true"></i>
                                <p>Booking History</p>
                            </a>
                        </li>
                        @endhaspermission
                        @haspermission('booking-transaction-history', 'admin_web')
                        <!-- Booking Transaction History -->
                        <li class="nav-item">
                            <a href="{{ route('admin.booking-transactions') }}" class="nav-link">
                                <i class="nav-icon fa fa-list" aria-hidden="true"></i>
                                <p>Booking Transaction History</p>
                            </a>
                        </li>
                        @endhaspermission

                        @haspermission('remaining-booking-penalties', 'admin_web')
                        <!-- Remaining Booking Penalties -->
                        <li class="nav-item">
                            <a href="{{ route('admin.remaining-booking-penalties') }}" class="nav-link">
                                <i class="nav-icon fa fa-rupee-sign"></i>
                                <p>Remaining Booking Penalties</p>
                            </a>
                        </li>
                        @endhaspermission

                        @haspermission('customer-refund', 'admin_web')
                        <!-- Refund Amount -->
                        <li class="nav-item">
                            <a href="{{ route('admin.customer.refund.list') }}" class="nav-link">
                                <i class="nav-icon fas fa-money-bill-alt"></i>
                                <p> Customer Refund</p>
                            </a>
                        </li>
                        @endhaspermission
                        @haspermission('customer-canceled-refund', 'admin_web')
                        <!-- Canceled Refund Amount -->
                        <li class="nav-item">
                            <a href="{{ route('admin.customer.canceled.refund') }}" class="nav-link">
                                <i class="nav-icon fas fa-money-bill-alt"></i>
                                <p> Customer Canceled Refund</p>
                            </a>
                        </li>
                        @endhaspermission
                        @haspermission('booking-calculation-list', 'admin_web')
                      <!-- Booking Calculation list -->
                        <li class="nav-item">
                            <a href="{{ route('admin.booking.calculation') }}" class="nav-link">
                                <i class="fa fa-calculator" aria-hidden="true"></i>
                                <p> Booking Calculation List</p>
                            </a>
                        </li>
                        @endhaspermission
                        @haspermission('trip-amount-calculation-list', 'admin_web')
                        <!-- Trip Amount Calculation Rules -->
                        <li class="nav-item">
                            <a href="{{ route('admin.trip.calculation') }}" class="nav-link">
                                <i class="fa fa-calculator" aria-hidden="true"></i>
                                <p>Trip Amount Calculation Rules</p>
                            </a>
                        </li>      
                        @endhaspermission

                        @haspermission('reward-list', 'admin_web')
                        <!-- Rewards Management -->
                        <li class="nav-item">
                            <a href="{{ route('admin.reward.list') }}" class="nav-link">
                                <i class="fa fa-solid fa-money-bill"></i>
                                <p>Rewards Management</p>
                            </a>
                        </li>
                        @endhaspermission

                        @haspermission('send-emails', 'admin_web')
                        <!-- Send Emails -->
                        <li class="nav-item">
                            <a href="{{ route('admin.email.emails') }}" class="nav-link">
                                <i class="nav-icon fa fa-envelope"></i>
                                <p>Send Emails</p>
                            </a>
                        </li>
                        @endhaspermission
                        @haspermission('send-mobile-notification', 'admin_web')
                        <!-- Send Notification -->
                        <li class="nav-item">
                            <a href="{{ route('admin.push.notification') }}" class="nav-link">
                                <i class="fa fa-bell" aria-hidden="true"></i>
                                <p>Send Mobile Notification</p>
                            </a>
                        </li>
                        @endhaspermission
                        @haspermission('policies-management', 'admin_web')
                        <!-- CMS Module -->
                        <li class="nav-item">
                            <a href="{{ route('admin.policies') }}" class="nav-link">
                                <i class="nav-icon fa fa-envelope"></i>
                                <p>Policies Management</p>
                            </a>
                        </li>
                        @endhaspermission
                        @haspermission('setting', 'admin_web')
                        <!-- Settings -->
                        <li class="nav-item">
                            <a href="{{ route('admin.settings') }}" class="nav-link">
                                <i class="nav-icon fa fa-envelope"></i>
                                <p>Settings</p>
                            </a>
                        </li>
                        @endhaspermission

                        @haspermission('admin-activity-log', 'admin_web')
                        <!-- Admin Activity Log -->
                        <li class="nav-item">
                            <a href="{{ route('admin.activity-log') }}" class="nav-link">
                                <i class="nav-icon fa fa-envelope"></i>
                                <p>Admin Activity Log</p>
                            </a>
                        </li>
                        @endhaspermission

                        <!-- Logout -->
                        <li class="nav-item">
                            <a href="{{ route('admin.logout') }}" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>@yield('page-title')</h1>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <b>Version</b> 1.0.0
            </div>
            <strong>Copyright &copy; 2020-2024 <a href="https://www.velriders.com/">VelRiders</a>.</strong> All rights
            reserved.
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Below for Inline Editing on datatable -->
    <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/jquery-editable/css/jquery-editable.css" rel="stylesheet"/>
    <script>$.fn.poshytip={defaults:null}</script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/jquery-editable/js/jquery-editable-poshytip.min.js"></script>

    <!-- DataTables  & Plugins -->
    <script src="{{ asset('plugins/datatables/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('dist/js/adminlte.min.js') }}"></script>
    <!-- Notyf -->
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script src="https://code.jquery.com/ui/1.11.0/jquery-ui.js"></script>

    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"></script> -->
    <script src="{{ asset('plugins/jquery/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('plugins/jquery/additional-methods.min.js') }}"></script>

    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js" integrity="sha512-AIOTidJAcHBH2G/oZv9viEGXRqDNmfdPVPYOYKGy3fti0xIplnlgMHUGfuNRzC6FkzIo0iIxgFnr9RikFxK+sw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> -->
   
    <!-- <script src="https://cdn.ckeditor.com/4.22.1/basic/ckeditor.js"></script> -->
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.js" integrity="sha512-FbWDiO6LEOsPMMxeEvwrJPNzc0cinzzC0cB/+I2NFlfBPFlZJ3JHSYJBtdK7PhMn0VQlCY1qxflEG+rplMwGUg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js" integrity="sha512-2ImtlRlf2VVmiGZsjm9bEyhjGW4dU7B6TNwh/hx/iSByxNENtj3WVE6o/9Lj4TJeVXPi4bnOIMXFIJJAeufa0A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>


    <!-- Page specific script -->
    <script>
        var sitePath = "{{url('/')}}";
         setTimeout(function() {$('#success-message').fadeOut('slow');}, 4000);
         setTimeout(function() {$('#error-message').fadeOut('slow');}, 4000);
    </script>
    @stack('scripts')
    <script>
        $(document).ready(function() {
            var url = window.location;
            $('ul.nav a').filter(function() {
                return this.href == url;
            }).addClass('active').parents('.nav-item').addClass('menu-open');
        });
    </script>
</body>

</html>
