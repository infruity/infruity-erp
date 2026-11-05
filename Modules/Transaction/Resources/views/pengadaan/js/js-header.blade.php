<script>
    // Cabang: pemilih ErpPicker. `erp-picker.js` dimuat defer, jadi pemasangan menunggu DOM siap.
    $(function() {
        const branchPicker = () => ErpPicker.attach('#branch_id', {
            title: 'Pilih Cabang',
            placeholder: 'Cari cabang...',
            placeholderTrigger: 'Pilih Cabang',
            emptyText: 'Cabang tidak ditemukan',
            url: '/ajax/getBranch',
            map: item => ({ id: item.id, text: item.name })
        });

        // Halaman create: pilih cabang pertama bila belum ada pilihan (draft yang dimuat tidak ditimpa).
        if (window.location.pathname.includes('create')) {
            $.ajax({
                url: '/ajax/getBranch',
                dataType: 'json',
                success: function(data) {
                    if (data.length > 0 && !$('#branch_id').val()) {
                        let first = data[0];
                        $('#branch_id')
                            .append(new Option(first.name, first.id, true, true))
                            .trigger('change');
                    }
                    branchPicker();
                }
            });
        } else {
            branchPicker();
        }
    });
</script>
