 <!--begin::Header-->
 <div id="kt_header" class="header align-items-stretch d-none d-md-flex">
     <!--begin::Brand-->
     <div class="header-brand">
         <!--begin::Logo-->
         <a href="{{ url('/') }}">
             {{-- <img alt="Logo" src="{{ asset('images/logo-infruity.png') }}" class="h-25px h-lg-25px" /> --}}
             <h1 class="text-white fw-bold fs-1">in!fruity</h1>
         </a>
         <!--end::Logo-->
         <!--begin::Aside minimize-->
         <div id="kt_aside_toggle" class="btn btn-icon w-auto px-0 btn-active-color-primary aside-minimize"
             data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
             data-kt-toggle-name="aside-minimize">
             <i class="ki-duotone ki-entrance-right fs-1 me-n1 minimize-default">
                 <span class="path1"></span>
                 <span class="path2"></span>
             </i>
             <i class="ki-duotone ki-entrance-left fs-1 minimize-active">
                 <span class="path1"></span>
                 <span class="path2"></span>
             </i>
         </div>
         <!--end::Aside minimize-->
         <!--begin::Aside toggle-->
         <div class="d-flex align-items-center d-lg-none me-n2" title="Show aside menu">
             <div class="btn btn-icon btn-active-color-primary w-30px h-30px" id="kt_aside_mobile_toggle">
                 <i class="ki-duotone ki-abstract-14 fs-1">
                     <span class="path1"></span>
                     <span class="path2"></span>
                 </i>
             </div>
         </div>
         <!--end::Aside toggle-->
     </div>
     <!--end::Brand-->
     {{-- @if (Request::segment(1) != 'pos')
         @include('template.toolbar')
     @endif --}}
 </div>
 <!--end::Header-->
 <x-erp.topbar mobile-only />
