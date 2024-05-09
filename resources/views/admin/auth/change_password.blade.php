@extends('admin.layouts.app')
@section('content')
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <style>
            body {
                background-image: url('assets/media/auth/bg10.jpeg');
            }

            [data-theme="dark"] body {
                background-image: url('assets/media/auth/bg10-dark.jpeg');
            }
        </style>
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <div class="d-flex flex-lg-row-fluid">
                <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
                    <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20"
                        src="{{ url('admin/') }}/assets/media/auth/agency.png" alt="" />
                    <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20"
                        src="{{ url('admin/') }}/assets/media/auth/agency-dark.png" alt="" />
                    <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">Fast, Efficient and Productive</h1>
                </div>
            </div>
            <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
                <div class="bg-body d-flex flex-center rounded-4 w-md-600px p-10">
                    <div class="w-md-400px">
                        <form class="form w-100" action="">
                            @csrf
                            <input type="hidden" name="loginType" value="admin" />
                            <div class="text-center mb-11">
                                <h1 class="text-dark fw-bolder mb-3">Update Passowrd</h1>
                            </div>
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if (session('status'))
                            <div class="alert alert-danger dark" role="alert">
                                <p>{{ session('status') }}</p>
                            </div>
                        @endif
                        @if (session('success'))
                            <div class="alert alert-success dark" role="alert">
                                <p>{{ session('success') }}</p>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger dark" role="alert">
                                <p>{{ session('error') }}</p>
                            </div>
                        @endif
                        @if (count($errors) > 0)
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                            <div class="fv-row mb-3">
                                <input type="password" placeholder="Current Password" name="current_password" autocomplete="off"
                                    class="form-control bg-transparent" value="" />
                            </div>
                            <div class="fv-row mb-3">
                                <input type="password" placeholder="New Password" name="new_password" autocomplete="off"
                                    class="form-control bg-transparent" value="" />
                            </div>
                            
                            <div class="fv-row mb-3">
                                <input type="password" placeholder="Confirm Password" name="confirm_password" autocomplete="off"
                                    class="form-control bg-transparent" value="" />
                            </div>
                            <div class="d-grid mb-10">
                                <div class="flex">
                                    <a href="/admin/dashboard" id="kt_sign_in_submit" class="btn btn-info">
                                        <span class="indicator-label">Back to Dashboard</span>
                                    </a>
                                    <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                                        <span class="indicator-label">Update Password</span>
                                        <span class="indicator-progress">Please wait...
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                    
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
