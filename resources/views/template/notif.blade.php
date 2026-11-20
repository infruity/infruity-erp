{{-- Flash messages float over the page as a toast that goes away by itself, so the layout never shifts.
     Without JavaScript the same messages stay as plain inline notices. --}}
@php
    $flashToasts = [];
    if (session('success')) { $flashToasts[] = ['icon' => 'success', 'text' => session('success'), 'timer' => 3200]; }
    if (session('error')) { $flashToasts[] = ['icon' => 'error', 'text' => session('error'), 'timer' => 5000]; }
    if ($errors->any()) { $flashToasts[] = ['icon' => 'error', 'text' => 'Data belum dapat disimpan. ' . $errors->first(), 'timer' => 6000]; }
@endphp
@if ($flashToasts)
<noscript>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><p class="fw-semibold mb-2">Data belum dapat disimpan</p><ul class="mb-0 ps-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
</noscript>
<script>
    (function () {
        var toasts = @json($flashToasts);
        function show() {
            if (!window.Swal) return;
            toasts.forEach(function (item, index) {
                setTimeout(function () {
                    window.Swal.fire({
                        toast: true, position: 'top-end', icon: item.icon, title: item.text,
                        showConfirmButton: false, timer: item.timer, timerProgressBar: true,
                    });
                }, index * 250);
            });
        }
        if (document.readyState === 'complete') show(); else window.addEventListener('load', show);
    })();
</script>
@endif
