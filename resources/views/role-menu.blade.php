@extends('layouts.erp')
@section('title', 'Daftar Permission')
@section('content')
<x-erp.workspace title="Daftar Permission" subtitle="Tinjau akses yang diberikan kepada setiap role.">
    <x-slot:toolbar><p class="text-sm text-gray-400 mb-0">Ubah akses melalui editor Hak Akses pada role terkait.</p>@if(check_access('roles.index'))<x-erp.button variant="primary" icon="shield-check" :href="route('roles.index')">Hak Akses</x-erp.button>@endif</x-slot:toolbar>
    <x-erp.collection table="role-permission-table"><table id="role-permission-table" class="erp-table"><thead><tr><th>Role</th><th>Permission</th><th>Aksi</th></tr></thead><tbody>@forelse($data as $item)<tr><td>{{ $item->role->nm_role ?? 'Role tidak tersedia' }}</td><td>{{ $item->permission }}</td><td>@if(check_access('roles.show'))<x-erp.button :href="route('roles.show', $item->id_role)">Lihat hak akses</x-erp.button>@endif</td></tr>@empty<tr><td colspan="3">Belum ada permission.</td></tr>@endforelse</tbody></table></x-erp.collection>
    <x-slot:footer>{{ $data->links() }}</x-slot:footer>
</x-erp.workspace>
@endsection
