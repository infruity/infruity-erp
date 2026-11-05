/**
 * Bawaan DataTables yang sebelumnya datang dari bundle Metronic, ditulis ulang agar
 * terbaca dan tanpa JSZip/pdfmake/ekstensi. Tujuannya: markup tabel yang dihasilkan
 * DataTables sama persis dengan sebelumnya, sehingga halaman yang sudah ada tidak berubah.
 *
 * Urutan di bundle lama (yang menang adalah yang terakhir):
 *   1. bawaan bahasa Metronic
 *   2. integrasi Bootstrap 4 (dom, class, renderer paginasi `ul.pagination > li.page-item`)
 *
 * Ini sengaja masih memakai class Bootstrap (`page-item`, `form-select-solid`, ...); gayanya
 * datang dari skin `resources/css/datatables.css` dan adapter `erp-*.css`. Bila sebuah halaman
 * dipindah ke komponen baru, ganti `dom`/`classes` per tabel; hapus modul ini di B6.
 */
export function install($, doc = document) {
    const DataTable = $.fn.dataTable;

    // 1. Bawaan bahasa Metronic.
    $.extend(true, DataTable.defaults, {
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ records',
            infoEmpty: 'Showing no records',
            lengthMenu: '_MENU_',
            processing: '<span class="spinner-border w-15px h-15px text-muted align-middle me-2"></span> <span class="text-gray-600">Loading...</span>',
            paginate: {
                first: '<i class="first"></i>',
                last: '<i class="last"></i>',
                next: '<i class="next"></i>',
                previous: '<i class="previous"></i>',
            },
        },
    });

    // 2. Integrasi Bootstrap 4.
    $.extend(true, DataTable.defaults, {
        dom: "<'table-responsive'tr>"
            + "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'li>"
            + "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
        renderer: 'bootstrap',
    });

    $.extend(DataTable.ext.classes, {
        sWrapper: 'dataTables_wrapper dt-bootstrap4',
        sFilterInput: 'form-control form-control-sm form-control-solid',
        sLengthSelect: 'form-select form-select-sm form-select-solid',
        sProcessing: 'dataTables_processing',
        sPageButton: 'paginate_button page-item',
    });

    DataTable.ext.renderer.pageButton.bootstrap = function (settings, host, idx, buttons, page, pages) {
        const api = new DataTable.Api(settings);
        const classes = settings.oClasses;
        const lang = settings.oLanguage.oPaginate;
        const aria = settings.oLanguage.oAria.paginate || {};
        let counter = 0;

        const onClick = (event) => {
            event.preventDefault();
            if (!$(event.currentTarget).hasClass('disabled') && api.page() !== event.data.action) {
                api.page(event.data.action).draw('page');
            }
        };

        const attach = (container, items) => {
            items.forEach((button) => {
                if (Array.isArray(button)) {
                    attach(container, button);
                    return;
                }

                let label = '';
                let state = '';
                switch (button) {
                    case 'ellipsis':
                        label = '&#x2026;';
                        state = 'disabled';
                        break;
                    case 'first':
                        label = lang.sFirst;
                        state = button + (page > 0 ? '' : ' disabled');
                        break;
                    case 'previous':
                        label = lang.sPrevious;
                        state = button + (page > 0 ? '' : ' disabled');
                        break;
                    case 'next':
                        label = lang.sNext;
                        state = button + (page < pages - 1 ? '' : ' disabled');
                        break;
                    case 'last':
                        label = lang.sLast;
                        state = button + (page < pages - 1 ? '' : ' disabled');
                        break;
                    default:
                        label = button + 1;
                        state = page === button ? 'active' : '';
                }

                if (!label) return;

                const node = $('<li>', {
                    class: classes.sPageButton + ' ' + state,
                    id: idx === 0 && typeof button === 'string' ? settings.sTableId + '_' + button : null,
                })
                    .append($('<a>', {
                        href: '#',
                        'aria-controls': settings.sTableId,
                        'aria-label': aria[button],
                        'data-dt-idx': counter,
                        tabindex: settings.iTabIndex,
                        class: 'page-link',
                    }).html(label))
                    .appendTo(container);

                settings.oApi._fnBindAction(node, { action: button }, onClick);
                counter += 1;
            });
        };

        let focused;
        try {
            focused = $(host).find(doc.activeElement).data('dt-idx');
        } catch (e) { /* tidak ada elemen aktif di dalam host */ }

        attach($(host).empty().html('<ul class="pagination"/>').children('ul'), buttons);

        if (focused !== undefined) $(host).find('[data-dt-idx=' + focused + ']').trigger('focus');
    };
}
