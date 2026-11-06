{{--
    Halaman master sederhana: DataTables server-side + dialog tambah/ubah.
    Dipakai location, handling, department.
    Parameter: resource (segmen URL & awalan id), title, noun (huruf kecil), subtitle, icon,
    dataRoute (nama route data), columns [[title, data]], fields [[name, label, required?, sumber-respons?]].
    Elemen ke-4 field dipakai bila nama kunci JSON `edit` berbeda dari nama input.
    Menu baris (Ubah/Hapus) dirender controller lewat App\Support\DataTableCells.
--}}
@php
    $dialogId = $resource.'-dialog';
    $formId = $resource.'-form';
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/erp-operations.css') }}?v={{ filemtime(public_path('assets/css/erp-operations.css')) }}">
<x-erp.workspace :title="$title" :subtitle="$subtitle" :icon="$icon">
    <x-slot:toolbar>
        <label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" aria-label="Cari {{ $noun }}" placeholder="Cari {{ $noun }}..."></label>
        <div class="erp-operations-toolbar-actions"><x-erp.button variant="primary" icon="plus" :data-erp-open="'#' . $dialogId">Tambah {{ $title }}</x-erp.button></div>
    </x-slot:toolbar>
    <div class="erp-operations-collection" data-erp-collection="{{ $resource }}-table" data-erp-empty="Belum ada {{ $noun }}" data-erp-empty-description="Data yang tersimpan akan muncul di sini.">
        <table class="erp-table" id="{{ $resource }}-table">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th>{{ $column[0] }}</th>
                    @endforeach
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</x-erp.workspace>

<x-erp.dialog :id="$dialogId" :title="'Tambah '.$title" variant="sheet" size="md" data-erp-create>
    <form id="{{ $formId }}" action="{{ url($resource) }}" class="space-y-4">
        @csrf
        @foreach ($fields as $field)
            <div>
                <label for="{{ $resource }}-{{ $field[0] }}" class="erp-label {{ ($field[2] ?? false) ? 'erp-label--required' : '' }}">{{ $field[1] }}</label>
                <input type="text" id="{{ $resource }}-{{ $field[0] }}" name="{{ $field[0] }}" class="erp-input" autocomplete="off"@isset($field[3]) data-source="{{ $field[3] }}"@endisset>
            </div>
        @endforeach
    </form>
    <x-slot:footer>
        <div class="ml-auto flex gap-3">
            <x-erp.button type="reset" :form="$formId" :id="$resource . '-cancel'">Batal</x-erp.button>
            <x-erp.button type="submit" variant="primary" :form="$formId" :id="$resource . '-submit'">
                <span data-label>Simpan</span>
                <span data-progress hidden>Menyimpan...</span>
            </x-erp.button>
        </div>
    </x-slot:footer>
</x-erp.dialog>

@push('scripts')
<script>
    (function () {
        const resource = @js($resource);
        const title = @js($title);
        const dialogId = resource + '-dialog';
        const form = $('#' + resource + '-form');
        const submitBtn = $('#' + resource + '-submit');
        const defaultAction = form.attr('action');
        let dataTable;

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        function resetForm() {
            form[0].reset();
            form.find('input[name="_method"]').remove();
            form.attr('action', defaultAction);
            $('#' + dialogId + '-title').text('Tambah ' + title);
        }

        function reloadDataTable() {
            if (dataTable) dataTable.ajax.reload(null, false);
        }

        $(document).ready(function () {
            dataTable = $('#' + resource + '-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: @js(route($dataRoute)),
                    data: function (d) { d.url = resource; }
                },
                columns: [
                    @foreach ($columns as $column)
                        { data: @js($column[1]), name: @js($column[1]) },
                    @endforeach
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });

            $('#search').on('keyup', function () { dataTable.search(this.value).draw(); });

            // Menutup dialog (Esc, tombol X, atau Batal) mengembalikan form ke mode tambah.
            document.getElementById(dialogId).addEventListener('erp:dialog-close', resetForm);

            document.getElementById(resource + '-cancel').addEventListener('click', function (e) {
                e.preventDefault();
                Swal.fire({
                    text: 'Apakah Anda yakin ingin membatalkan?',
                    icon: 'warning',
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: 'Ya, Batalkan!',
                    cancelButtonText: 'Tidak, Kembali',
                    customClass: { confirmButton: 'erp-action erp-action--primary', cancelButton: 'erp-action ml-3' }
                }).then(function (result) {
                    if (result.isConfirmed) erp.dialog.close(dialogId);
                });
            });

            form.on('submit', function (e) {
                e.preventDefault();
                submitBtn.prop('disabled', true);
                submitBtn.find('[data-label]').prop('hidden', true);
                submitBtn.find('[data-progress]').prop('hidden', false);

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: form.serialize(),
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Data berhasil disimpan.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            erp.dialog.close(dialogId);
                            reloadDataTable();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan saat menyimpan data.'
                        });
                    },
                    complete: function () {
                        submitBtn.prop('disabled', false);
                        submitBtn.find('[data-label]').prop('hidden', false);
                        submitBtn.find('[data-progress]').prop('hidden', true);
                    }
                });
            });
        });

        window.deleteProduct = function (id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang dihapus tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: { confirmButton: 'erp-action erp-action--danger', cancelButton: 'erp-action ml-3' },
                buttonsStyling: false
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: '/' + resource + '/' + id,
                    type: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message || 'Data berhasil dihapus.', showConfirmButton: false, timer: 1500 });
                        reloadDataTable();
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan saat menghapus data.' });
                    }
                });
            });
        };

        window.editProduct = function (id) {
            $.ajax({
                url: '/' + resource + '/' + id + '/edit',
                type: 'GET',
                success: function (response) {
                    form.find('input[type="text"]').each(function () { $(this).val(response[this.dataset.source || this.name] ?? ''); });
                    form.attr('action', '/' + resource + '/' + id);
                    form.find('input[name="_method"]').remove();
                    form.append('<input type="hidden" name="_method" value="PUT">');
                    erp.dialog.open(dialogId);
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan saat memuat data.' });
                }
            });
        };
    })();
</script>
@endpush
