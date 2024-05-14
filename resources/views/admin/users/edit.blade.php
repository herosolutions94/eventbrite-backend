@extends('admin.layouts.app')
@section('content')
    @include('admin.partials.header')
    @include('admin.partials.sidebar')

    <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
        <!--begin::Content wrapper-->
        <div class="d-flex flex-column flex-column-fluid">
            <!--begin::Toolbar-->
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                <!--begin::Toolbar container-->
                <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                    <!--begin::Page title-->
                    <div class="page-title d-flex flex-row justify-content-between flex-wrap me-3 w-100">
                        <!--begin::Title-->
                        <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Update
                            {{$user->role}}</h1>
                        
                    <!--begin::Actions-->
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        @if($user->role=='player')
                        <a href="{{ route('admin.players.index') }}" class="btn btn-sm fw-bold btn-light btn-outline">Back</a>
                        @else
                        <a href="{{ url('/admin/organizers/index') }}" class="btn btn-sm fw-bold btn-light btn-outline">Back</a>
                        @endif
                        <!--end::Primary button-->
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Toolbar container-->
            </div>
        </div>
            <!--end::Toolbar-->
            <!--begin::Content-->
            <div id="kt_app_content" class="app-content flex-column-fluid">
                <!--begin::Content container-->
                <div id="kt_app_content_container" class="app-container container-xxl">
                    <form action="{{url('/admin/organizers/update/'.$user->id)}}" method="POST"  enctype="multipart/form-data" id="kt_ecommerce_add_category_form" class="form d-flex flex-column flex-lg-row">
                         @csrf
                        <!--begin::Aside column-->
                        <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                            <!--begin::Thumbnail settings-->
                            <div class="card card-flush py-4">
                                <!--begin::Card header-->
                                <div class="card-header">
                                    <!--begin::Card title-->
                                    <div class="card-title">
                                        <h2>Thumbnail</h2>
                                    </div>
                                    <!--end::Card title-->
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body text-center pt-0">
                                    <!--begin::Image input-->
                                    <!--begin::Image input placeholder-->
                                    <style>
                                        .image-input-placeholder {
                                            background-image: url('assets/media/svg/files/blank-image.svg');
                                        }

                                        [data-theme="dark"] .image-input-placeholder {
                                            background-image: url('assets/media/svg/files/blank-image-dark.svg');
                                        }
                                    </style>
                                    <!--end::Image input placeholder-->
                                    <!--begin::Image input-->
                                    <div class="image-input image-input-empty image-input-outline image-input-placeholder mb-3"
                                        data-kt-image-input="true">
                                        <!--begin::Preview existing avatar-->
                                        <div class="image-input-wrapper w-150px h-150px" style="background-image:url('{{ get_site_image_src('uploads', !empty($user) ? $user->user_image : '') }}')"></div>
                                        <!--end::Preview existing avatar-->
                                        <!--begin::Label-->
                                        <label
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                            title="Change avatar">
                                            <!--begin::Icon-->
                                            <i class="bi bi-pencil-fill fs-7"></i>
                                            <!--end::Icon-->
                                            <!--begin::Inputs-->
                                            <input type="file" name="user_image" accept=".png, .jpg, .jpeg" />
                                            <!--end::Inputs-->
                                        </label>
                                        <!--end::Label-->
                                        <!--begin::Cancel-->
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                            title="Cancel avatar">
                                            <i class="bi bi-x fs-2"></i>
                                        </span>
                                        <!--end::Cancel-->
                                        <!--begin::Remove-->
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                            title="Remove avatar">
                                            <i class="bi bi-x fs-2"></i>
                                        </span>
                                        <!--end::Remove-->
                                    </div>
                                    <!--end::Image input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Set the category thumbnail image. Only *.png, *.jpg and
                                        *.jpeg image files are accepted</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Card body-->
                            </div>
                            <div class="card card-flush py-4">
                                <!--begin::Card header-->
                                <div class="card-header">
                                    <!--begin::Card title-->
                                    <div class="card-title">
                                        <h2>Cover Photo</h2>
                                    </div>
                                    <!--end::Card title-->
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body text-center pt-0">
                                    <!--begin::Image input-->
                                    <!--begin::Image input placeholder-->
                                    <style>
                                        .image-input-placeholder {
                                            background-image: url('assets/media/svg/files/blank-image.svg');
                                        }

                                        [data-theme="dark"] .image-input-placeholder {
                                            background-image: url('assets/media/svg/files/blank-image-dark.svg');
                                        }
                                    </style>
                                    <!--end::Image input placeholder-->
                                    <!--begin::Image input-->
                                    <div class="image-input image-input-empty image-input-outline image-input-placeholder mb-3"
                                        data-kt-image-input="true">
                                        <!--begin::Preview existing avatar-->
                                        <div class="image-input-wrapper w-150px h-150px" style="background-image:url('{{ get_site_image_src('uploads', !empty($user) ? $user->user_cover : '') }}')"></div>
                                        <!--end::Preview existing avatar-->
                                        <!--begin::Label-->
                                        <label
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                            title="Change avatar">
                                            <!--begin::Icon-->
                                            <i class="bi bi-pencil-fill fs-7"></i>
                                            <!--end::Icon-->
                                            <!--begin::Inputs-->
                                            <input type="file" name="user_cover" accept=".png, .jpg, .jpeg" />
                                            <!--end::Inputs-->
                                        </label>
                                        <!--end::Label-->
                                        <!--begin::Cancel-->
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                            title="Cancel avatar">
                                            <i class="bi bi-x fs-2"></i>
                                        </span>
                                        <!--end::Cancel-->
                                        <!--begin::Remove-->
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                            title="Remove avatar">
                                            <i class="bi bi-x fs-2"></i>
                                        </span>
                                        <!--end::Remove-->
                                    </div>
                                    <!--end::Image input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Set the category thumbnail image. Only *.png, *.jpg and
                                        *.jpeg image files are accepted</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Thumbnail settings-->
                            <!--begin::Status-->
                            <div class="card card-flush py-4">
                                <!--begin::Card header-->
                                <div class="card-header">
                                    <!--begin::Card title-->
                                    <div class="card-title">
                                        <h2>Status</h2>
                                    </div>
                                    <!--end::Card title-->
                                    <!--begin::Card toolbar-->
                                    <div class="card-toolbar">
                                        <div class="rounded-circle bg-success w-15px h-15px"
                                            id="kt_ecommerce_add_category_status"></div>
                                    </div>
                                    <!--begin::Card toolbar-->
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    <!--begin::Select2-->
                                    <select class="form-select mb-2" data-control="select2" data-hide-search="true"
                                        data-placeholder="Select an option" id="kt_ecommerce_add_category_status_select" name="status">
                                        <option></option>
                                        <option value="active" selected="selected">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Status-->
                          
                        </div>
                        <!--end::Aside column-->
                        <!--begin::Main column-->
                        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                            <!--begin::General options-->
                            <div class="card card-flush py-4">
                                <!--begin::Card header-->
                                <div class="card-header">
                                    <div class="card-title">
                                        <h2>Personal information</h2>
                                    </div>
                                </div>
                                <div class="row"> 
                                <div class="col-md-6" style="margin-left:30px"> 
                                 @if(count($errors) > 0)
                                        <div class="alert alert-danger">
                                          <strong>Whoops!</strong> There were some problems with your input.<br><br>
                                          <ul>
                                             @foreach ($errors->all() as $error)
                                               <li>{{ $error }}</li>
                                             @endforeach
                                          </ul>
                                        </div>
                                    @endif
                                </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    
                                    
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    <!--begin::Input group-->
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            <div class="col">
                                                <label class="required form-label" for="firstname">First Name</label>
                                                <input type="text" name="firstname" class="form-control mb-2" placeholder="" value="{{$user->firstname}}" />
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="lastname">Last Name</label>
                                                <input type="text" name="lastname" class="form-control mb-2" placeholder="" value="{{$user->lastname}}" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            <div class="col">
                                                <label class="required form-label" for="phone_number">Phone#</label>
                                                <input type="text" name="phone_number" class="form-control mb-2" placeholder="" value="{{$user->phone_number}}" />
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="email">Email</label>
                                                <input type="text" name="email" class="form-control mb-2" placeholder="" value="{{$user->email}}" />
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                                <!--end::Card header-->
                            </div>
                            <!--end::General options-->
                            <div class="card card-flush py-4">
                                <!--begin::Card header-->
                                <div class="card-header">
                                    <div class="card-title">
                                        <h2>Credits information</h2>
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            <div class="col">
                                                <label class="required form-label" for="total_credits">Credits</label>
                                                <input type="number" name="total_credits" class="form-control mb-2" placeholder="" value="{{$user->total_credits}}" />
                                            </div>
                                        </div>
                                    </div>
                                   
                                </div>
                                <!--end::Card header-->
                            </div>
                            <!--begin::Meta options-->
                            <div class="card card-flush py-4">
                                <!--begin::Card header-->
                                <div class="card-header">
                                    <div class="card-title">
                                        <h2>Organization information</h2>
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            <div class="col">
                                                <label class="required form-label" for="org_name">Organization Name</label>
                                                <input type="text" name="org_name" class="form-control mb-2" placeholder="" value="{{$user->org_name}}" />
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="org_website">Organization Website</label>
                                                <input type="text" name="org_website" class="form-control mb-2" placeholder="" value="{{$user->org_website}}" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            <div class="col">
                                                <label class="required form-label" for="org_mailing_address">Organization Mailing Address</label>
                                                <input type="text" name="org_mailing_address" class="form-control mb-2" placeholder="" value="{{$user->org_mailing_address}}" />
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="email">Communication Method</label>
                                                <select class="form-select mb-2" data-control="select2" data-hide-search="true"
                                                    data-placeholder="Select an option" id="org_communication_method" name="org_communication_method">
                                                    <option></option>
                                                    <option value="phone" selected="selected">Phone</option>
                                                    <option value="email">Email</option>
                                                    <option value="messaging app">Messaging App</option>
                                                </select>
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="org_timezone">Organization Timezone</label>
                                                <input type="text" name="org_timezone" class="form-control mb-2" placeholder="" value="{{$user->org_timezone}}" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Card header-->
                            </div>

                            <!--end::Meta options-->
                            <div class="card card-flush py-4">
                                <!--begin::Card header-->
                                <div class="card-header">
                                    <div class="card-title">
                                        <h2>Secondary contact information</h2>
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            <div class="col">
                                                <label class="required form-label" for="secondary_phone">Phone#</label>
                                                <input type="text" name="secondary_phone" class="form-control mb-2" placeholder="" value="{{$user->secondary_phone}}" />
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="secondary_email">Email</label>
                                                <input type="text" name="secondary_email" class="form-control mb-2" placeholder="" value="{{$user->secondary_email}}" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Card header-->
                            </div>
                            
                            <div class="card card-flush py-4">
                                <!--begin::Card header-->
                                <div class="card-header">
                                    <div class="card-title">
                                        <h2>Address information</h2>
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            <div class="col">
                                                <label class="required form-label" for="country">Country</label>
                                                <select class="form-select mb-2" data-control="select2" data-hide-search="true"
                                                    data-placeholder="Select an option" id="country" name="country">
                                                    <option></option>
                                                    @foreach($user->countries as $country)
                                                        <option value="{{$country->id}}" {{$user->country==$country->id ? 'selected' : ""}}>{{$country->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="secondary_email">State</label>
                                                <select class="form-select mb-2" id="state" name="state">
                                                    <option></option>
                                                    @foreach($user->states as $state)
                                                        <option value="{{$state->id}}"{{$user->state==$state->id ? 'selected' : ""}}>{{$state->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="city">City</label>
                                                <input type="text" name="city" class="form-control mb-2" placeholder="" value="{{$user->city}}" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            
                                            <div class="col">
                                                <label class="required form-label" for="postal_code">Postal Code</label>
                                                <input type="text" name="postal_code" class="form-control mb-2" placeholder="" value="{{$user->postal_code}}" />
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="address">Address</label>
                                                <input type="text" name="address" class="form-control mb-2" placeholder="" value="{{$user->address}}" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Card header-->
                            </div>
                            <!--end::Meta options-->
                            <div class="card card-flush py-4">
                                <!--begin::Card header-->
                                <div class="card-header">
                                    <div class="card-title">
                                        <h2>Social Media Handles</h2>
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            <div class="col">
                                                <label class="required form-label" for="facebook">Facebook</label>
                                                <input type="text" name="facebook" class="form-control mb-2" placeholder="" value="{{$user->facebook}}" />
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="twitter">Twitter</label>
                                                <input type="text" name="twitter" class="form-control mb-2" placeholder="" value="{{$user->twitter}}" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-10 fv-row">
                                        <div class="row">
                                            <div class="col">
                                                <label class="required form-label" for="instagram">Instagram</label>
                                                <input type="text" name="instagram" class="form-control mb-2" placeholder="" value="{{$user->instagram}}" />
                                            </div>
                                            <div class="col">
                                                <label class="required form-label" for="linkedIn">linkedIn</label>
                                                <input type="text" name="linkedIn" class="form-control mb-2" placeholder="" value="{{$user->linkedIn}}" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Card header-->
                            </div>
                            <div class="d-flex justify-content-end">
                                <!--begin::Button-->
                                <a href="../../demo1/dist/apps/ecommerce/catalog/products.html"
                                    id="kt_ecommerce_add_product_cancel" class="btn btn-light me-5">Cancel</a>
                                <!--end::Button-->
                                <!--begin::Button-->
                                <button type="submit" id="kt_ecommerce_add_category_submit" class="btn btn-primary">
                                    <span class="indicator-label">Save Changes</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                                <!--end::Button-->
                            </div>
                        </div>
                        <!--end::Main column-->
                    </form>
                </div>
                <!--end::Content container-->
            </div>
            <!--end::Content-->
        </div>
    </div>
@endsection
