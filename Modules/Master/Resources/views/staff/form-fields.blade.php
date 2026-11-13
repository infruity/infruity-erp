@php
    $editing = isset($data) && $data !== null;
    $fieldIdPrefix = $editing ? 'staff_edit' : 'staff';
    $selectedName = old('staff_name', $data->name ?? '');
    $selectedDate = old('date_in', $data->date_in ?? ($editing ? '' : now()->toDateString()));
    $selectedPosition = (string) old('position', $data->position_id ?? '');
    $selectedBranch = (string) old('branch_id', $data->branch_id ?? '');
    $selectedRoleIds = collect(old('role_ids', $editing ? $data->pendingRoles->pluck('id_role')->all() : []))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

<form id="{{ $editing ? 'staff-edit-form' : 'staff-form' }}" action="{{ $editing ? route('staff.update', $data->id) : route('staff.store') }}" method="POST" class="flex min-h-0 flex-1 flex-col" data-staff-form>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain bg-gray-50/60 px-5 py-5 pb-8">
        <div class="flex flex-col gap-5">
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">Periksa kembali data yang ditandai di bawah.</div>
            @endif
            @if (session('error'))
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">{{ session('error') }}</div>
            @endif

            <div>
                <label for="{{ $fieldIdPrefix }}_name" class="mb-1.5 block text-xs font-semibold text-gray-600">Nama Karyawan <span class="text-red-400">*</span></label>
                <input id="{{ $fieldIdPrefix }}_name" name="staff_name" value="{{ $selectedName }}" type="text" required maxlength="255" placeholder="Misal: John Doe" class="m-0 h-11 w-full rounded-xl border {{ $errors->has('staff_name') ? 'border-red-400' : 'border-gray-200' }} bg-white px-4 text-sm outline-none transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                @error('staff_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="{{ $fieldIdPrefix }}_date_in" class="mb-1.5 block text-xs font-semibold text-gray-600">Tanggal Masuk</label>
                <x-erp.date-picker id="{{ $fieldIdPrefix }}_date_in" name="date_in" :value="$selectedDate" />
                @error('date_in')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div x-data="{ pickerSearch: '' }" class="w-full">
                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Jabatan <span class="text-red-400">*</span></label>
                <div class="flex w-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="relative shrink-0 border-b border-gray-100 bg-gray-50/50">
                        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                        <input type="search" x-model="pickerSearch" placeholder="Cari jabatan..." aria-label="Cari jabatan" class="w-full bg-transparent py-3 pl-9 pr-4 text-sm outline-none transition-colors">
                    </div>
                    <div class="max-h-48 overflow-y-auto overscroll-none bg-white">
                        @forelse ($positions as $position)
                            <label x-show="!pickerSearch || @js(mb_strtolower($position->name)).includes(pickerSearch.trim().toLocaleLowerCase('id'))" class="flex cursor-pointer items-center gap-3 border-b border-gray-100 px-3.5 py-3 transition-colors last:border-b-0 hover:bg-gray-50">
                                <input type="radio" name="position" value="{{ $position->id }}" @checked($selectedPosition === (string) $position->id) required class="h-4 w-4 shrink-0 accent-emerald-600">
                                <span class="flex-1 text-[13px] font-medium text-gray-700">{{ $position->name }}</span>
                            </label>
                        @empty
                            <p class="px-3.5 py-5 text-center text-[13px] text-gray-400">Belum ada jabatan</p>
                        @endforelse
                    </div>
                </div>
                @error('position')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div x-data="{ pickerSearch: '' }" class="w-full">
                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Cabang <span class="text-red-400">*</span></label>
                <div class="flex w-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="relative shrink-0 border-b border-gray-100 bg-gray-50/50">
                        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                        <input type="search" x-model="pickerSearch" placeholder="Cari cabang..." aria-label="Cari cabang" class="w-full bg-transparent py-3 pl-9 pr-4 text-sm outline-none transition-colors">
                    </div>
                    <div class="max-h-48 overflow-y-auto overscroll-none bg-white">
                        @forelse ($branches as $branch)
                            <label x-show="!pickerSearch || @js(mb_strtolower($branch->name)).includes(pickerSearch.trim().toLocaleLowerCase('id'))" class="flex cursor-pointer items-center gap-3 border-b border-gray-100 px-3.5 py-3 transition-colors last:border-b-0 hover:bg-gray-50">
                                <input type="radio" name="branch_id" value="{{ $branch->id }}" @checked($selectedBranch === (string) $branch->id) required class="h-4 w-4 shrink-0 accent-emerald-600">
                                <span class="flex-1 text-[13px] font-medium text-gray-700">{{ $branch->name }}</span>
                            </label>
                        @empty
                            <p class="px-3.5 py-5 text-center text-[13px] text-gray-400">Belum ada cabang</p>
                        @endforelse
                    </div>
                </div>
                @error('branch_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <fieldset>
                <legend class="mb-0.5 block text-xs font-semibold text-gray-600">Template Akses <span class="text-red-400">*</span></legend>
                <p class="mb-1.5 text-[11px] text-gray-400">Izin dasar akun. Sesuaikan per akun di Akun Pengguna.</p>
                <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white">
                    @forelse ($roles as $role)
                        <label class="flex cursor-pointer items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                            <input type="checkbox" name="role_ids[]" value="{{ $role->id_role }}" @checked(in_array((int) $role->id_role, $selectedRoleIds, true)) class="rounded accent-[#0b595b]">
                            <span>{{ $role->nm_role }}</span>
                        </label>
                    @empty
                        <p class="px-4 py-3 text-sm text-gray-400">Belum ada role</p>
                    @endforelse
                </div>
                <p class="mt-1.5 text-[11px] text-gray-400">Pilih satu atau lebih hak akses. Hak akses ini akan diterapkan saat akun ditautkan.</p>
                @error('role_ids')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('role_ids.*')<p class="mt-1 text-xs text-red-600">Role yang dipilih tidak valid.</p>@enderror
            </fieldset>

        </div>
    </div>

    <div class="shrink-0 border-t border-gray-100 bg-white px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
        <div class="flex items-center gap-3">
            @if ($insideOverlay ?? false)
                <button type="button" @click="{{ $cancelAction ?? 'closeDrawer()' }}" class="flex h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button>
            @else
                <a href="{{ route('staff.index') }}" class="flex h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</a>
            @endif
            <button type="submit" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white" data-submit>{{ $editing ? 'Simpan Perubahan' : 'Tambah Karyawan' }}</button>
        </div>
    </div>
</form>
