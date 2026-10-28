@php
    $editing = isset($data) && $data !== null;
    $selectedName = old('staff_name', $data->name ?? '');
    $selectedDate = old('date_in', $data->date_in ?? now()->toDateString());
    $selectedPosition = (string) old('position', $data->position_id ?? '');
    $selectedBranch = (string) old('branch_id', $data->branch_id ?? '');
    $selectedRoleIds = array_map('strval', (array) old('role_ids', []));
@endphp

<form id="staff-form" action="{{ $editing ? route('staff.update', $data->id) : route('staff.store') }}" method="POST" class="flex min-h-0 flex-1 flex-col" data-staff-form>
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
                <label for="staff_name" class="mb-1.5 block text-xs font-semibold text-gray-600">Nama Karyawan <span class="text-red-400">*</span></label>
                <input id="staff_name" name="staff_name" value="{{ $selectedName }}" type="text" required maxlength="255" placeholder="Misal: John Doe" class="m-0 h-11 w-full rounded-xl border {{ $errors->has('staff_name') ? 'border-red-400' : 'border-gray-200' }} bg-white px-4 text-sm outline-none transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                @error('staff_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="date_in" class="mb-1.5 block text-xs font-semibold text-gray-600">Tanggal Masuk</label>
                <input id="date_in" name="date_in" value="{{ $selectedDate }}" type="date" class="m-0 h-11 w-full rounded-xl border {{ $errors->has('date_in') ? 'border-red-400' : 'border-gray-200' }} bg-white px-4 text-sm outline-none transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
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
                            <label x-show='!pickerSearch || @js(mb_strtolower($position->name)).includes(pickerSearch.trim().toLocaleLowerCase("id"))' class="flex cursor-pointer items-center gap-3 border-b border-gray-100 px-3.5 py-3 transition-colors last:border-b-0 hover:bg-gray-50">
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
                            <label x-show='!pickerSearch || @js(mb_strtolower($branch->name)).includes(pickerSearch.trim().toLocaleLowerCase("id"))' class="flex cursor-pointer items-center gap-3 border-b border-gray-100 px-3.5 py-3 transition-colors last:border-b-0 hover:bg-gray-50">
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

            @if ($editing && $data->account)
                <section class="border-t border-gray-200 pt-5" aria-labelledby="staff-account-heading">
                    <h3 id="staff-account-heading" class="text-sm font-bold text-gray-800">Akun Pengguna</h3>
                    <p class="mt-1 text-xs text-gray-500">Akun tertaut: <span class="font-semibold text-gray-700">{{ $data->account->email }}</span> · {{ $data->account->is_aktif ? 'Aktif' : 'Nonaktif' }}</p>
                    <p class="mt-1 text-xs text-gray-400">Nama akun mengikuti nama karyawan. Pengelolaan tautan tersedia di halaman Akun Pengguna.</p>
                    @if (check_access('user.index'))<a href="{{ route('user.index') }}" class="mt-3 inline-flex h-9 items-center rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-600">Kelola tautan akun</a>@endif
                </section>
            @elseif (($canCreateAccount ?? false) && !$editing)
                <details class="border-t border-gray-200 pt-5" @if(old('create_account')) open @endif>
                    <summary class="cursor-pointer list-none text-sm font-bold text-gray-800">Akun Pengguna <span class="ml-1 text-xs font-normal text-gray-400">Opsional</span></summary>
                    <p class="mt-1 text-xs text-gray-400">Pengaturan akun terpisah dari data karyawan dan akan langsung ditautkan.</p>
                    <label for="create-account" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-gray-700"><input id="create-account" name="create_account" type="checkbox" value="1" @checked(old('create_account')) class="rounded accent-[#0b595b]"> Buat akun pengguna</label>
                    @error('create_account')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <div id="account-creation-fields" class="mt-4 space-y-4" @if(!old('create_account')) hidden @endif>
                        <fieldset id="account-creation-fieldset" class="space-y-4" @disabled(!old('create_account'))>
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3"><p class="text-[11px] font-medium text-emerald-800">Nama akun akan mengikuti nama karyawan.</p><p id="account-name-preview" class="mt-0.5 text-sm font-semibold text-emerald-900">{{ $selectedName ?: 'Isi nama karyawan di atas' }}</p></div>
                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-3"><label for="account_email" class="text-xs font-semibold text-gray-600">Alamat Email Akun <span class="text-red-400">*</span></label><div class="flex rounded-lg bg-gray-100 p-0.5" role="radiogroup" aria-label="Jenis email akun"><label class="cursor-pointer rounded-md px-2.5 py-1 text-[10px] font-medium"><input type="radio" name="account_email_type" value="auto" @checked(old('account_email_type', 'auto') === 'auto') class="sr-only">Bawaan</label><label class="cursor-pointer rounded-md px-2.5 py-1 text-[10px] font-medium"><input type="radio" name="account_email_type" value="custom" @checked(old('account_email_type') === 'custom') class="sr-only">Kustom</label></div></div>
                                <input id="account_email" name="account_email" value="{{ old('account_email', $nextAutoEmail ?? '') }}" @readonly(old('account_email_type', 'auto') === 'auto') required type="email" placeholder="Misal: nama@infruity.com" class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('account_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div><label for="account_password" class="mb-1.5 block text-xs font-semibold text-gray-600">Kata Sandi <span class="text-red-400">*</span></label><input id="account_password" name="account_password" required type="password" autocomplete="new-password" placeholder="Masukkan kata sandi" class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('account_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label for="account-role-ids" class="mb-1.5 block text-xs font-semibold text-gray-600">Hak Akses <span class="text-red-400">*</span></label><select id="account-role-ids" name="role_ids[]" multiple required size="4" class="min-h-[7rem] w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm outline-none focus:border-emerald-500">@foreach($accountRoles as $accountRole)<option value="{{ $accountRole->id_role }}" @selected(in_array((string) $accountRole->id_role, $selectedRoleIds, true))>{{ $accountRole->nm_role }}</option>@endforeach</select><p class="mt-1.5 text-[11px] text-gray-400">Pilih satu atau lebih role untuk akun ini.</p>@error('role_ids')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </fieldset>
                    </div>
                </details>
            @endif
        </div>
    </div>

    <div class="shrink-0 border-t border-gray-100 bg-white px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
        <div class="flex items-center gap-3">
            @if ($insideOverlay ?? false)
                <button type="button" @click="closeDrawer()" class="flex h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button>
            @else
                <a href="{{ route('staff.index') }}" class="flex h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</a>
            @endif
            <button type="submit" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white" data-submit>{{ $editing ? 'Simpan Perubahan' : 'Tambah Karyawan' }}</button>
        </div>
    </div>
</form>
