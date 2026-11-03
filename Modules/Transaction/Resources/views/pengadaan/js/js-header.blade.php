<script>
    // STEP 1: Ambil data pertama dulu
    // Detect jika URL mengandung kata "create"
    if (window.location.pathname.includes("create")) {

        // STEP 1: Ambil data pertama dulu
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

                $('#branch_id').select2({
                    placeholder: 'Pilih Cabang',
                    dropdownCssClass: 'wholesale-branch-dropdown',
                    minimumResultsForSearch: Infinity,
                    ajax: {
                        url: '/ajax/getBranch',
                        dataType: 'json',
                        delay: 250,
                        processResults: data => ({
                            results: data.map(item => ({
                                id: item.id,
                                text: item.name,
                            }))
                        })
                    }
                });
                $('#branch_id').next('.select2-container')
                    .toggleClass('has-branch-choices', data.length > 1);

            }
        });

    } else {
        // Jika bukan halaman create → Select2 biasa saja
        $('#branch_id').select2({
            placeholder: 'Pilih Cabang',
            dropdownCssClass: 'wholesale-branch-dropdown',
            minimumResultsForSearch: Infinity,
            ajax: {
                url: '/ajax/getBranch',
                dataType: 'json',
                delay: 250,
                processResults: data => ({
                    results: data.map(item => ({
                        id: item.id,
                        text: item.name,
                    }))
                })
            }
        });
    }


    $('#payment_id').select2({
        placeholder: 'Pilih Tipe Pembayaran',
        ajax: {
            url: '/ajax/getPaymentMethod', // ganti sesuai route
            dataType: 'json',
            delay: 250,
            processResults: data => ({
                results: data.map(item => ({
                    id: item.id,
                    text: item.name,
                }))
            })
        }
    })
</script>
