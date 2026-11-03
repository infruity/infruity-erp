@if(session('success'))
<div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger" role="alert"><p class="fw-semibold mb-2">Data belum dapat disimpan</p><ul class="mb-0 ps-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
