<script>
    // Cabang dan tipe pembayaran: pemilih ErpPicker. `erp-picker.js` dimuat defer, jadi pemasangan menunggu DOM siap.
    $(function() {
        const branchPicker = () => ErpPicker.attach('#branch_id', {
            title: 'Pilih Cabang',
            placeholder: 'Cari cabang...',
            placeholderTrigger: 'Pilih Cabang',
            emptyText: 'Cabang tidak ditemukan',
            url: '/ajax/getBranch',
            map: item => ({ id: item.id, text: item.name })
        });

        $.ajax({
            url: '/ajax/getBranch',
            dataType: 'json',
            success: function(data) {
                if (data.length > 0 && !$('#branch_id').val()) {
                    const first = data[0];
                    $('#branch_id').append(new Option(first.name, first.id, true, true)).trigger('change');
                }
                branchPicker();
            }
        });

        ErpPicker.attach('#payment_id', {
            title: 'Pilih Tipe Pembayaran',
            placeholder: 'Cari tipe...',
            placeholderTrigger: 'Pilih Tipe',
            emptyText: 'Tipe tidak ditemukan',
            url: '/ajax/getPaymentMethod',
            map: item => ({ id: item.id, text: item.name })
        });
    });
</script>
