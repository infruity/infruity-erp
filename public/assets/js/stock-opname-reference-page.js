/* Bind the stock opname reference presentation to the existing page contracts. */
function initStockOpnameReference() {
    'use strict';
    const ui = window.StockOpnameUi;
    const $ = window.jQuery;
    const branch = document.querySelector('[data-kt-ecommerce-product-filter="cabang"]');
    const dateField = document.getElementById('kt_ecommerce_sales_flatpickr');
    let searchTimer;
    let busy = false;
    dataTable.on('draw', function () {
        $('#transaction-table_wrapper').toggleClass('so-single-page', dataTable.page.info().pages <= 1);
    });

    // The sheet itself is the shared filter-sheet component (id "so"). This code only opens it with the
    // filters the list is using and writes the chosen ones back to the request fields.
    function readApplied() {
        const [start='',end=''] = dateField.value.split(' to ');
        return ui.filterDraft({
            branch:branch.value,
            start,
            end:end || start,
            status:document.getElementById('so-filter-applied-status').value,
            result:document.getElementById('so-filter-applied-result').value
        });
    }
    function apply(draft) {
        const fields = ui.filterRequest(draft);
        branch.value = fields.cabang_filter;
        dateField.value = fields.start_date ? `${fields.start_date} to ${fields.end_date}` : '';
        document.getElementById('so-filter-applied-status').value = fields.status_filter;
        document.getElementById('so-filter-applied-result').value = fields.result_filter;
        const active = fields.cabang_filter !== 'all' || Boolean(fields.start_date || fields.end_date)
            || fields.status_filter !== 'all' || fields.result_filter !== 'all';
        $('[data-so-open-filter]').toggleClass('is-active',active);
        $('#active-branch-button-label').text(active ? 'Filter aktif' : 'Filter');
        dataTable.draw();
    }
    $('[data-so-open-filter]').on('click',function () {
        $('[data-so-open-filter]').attr('aria-expanded','true');
        window.dispatchEvent(new CustomEvent('erp-filter-open',{detail:{id:'so',values:ui.sheetValues(readApplied())}}));
    });
    window.addEventListener('erp-filter-apply',event => {
        if (event.detail.id === 'so') apply(ui.draftFromSheet(event.detail.values));
    });
    window.addEventListener('erp-filter-close',event => {
        if (event.detail.id === 'so') $('[data-so-open-filter]').attr('aria-expanded','false');
    });

    $('[data-so-search]').on('input',function () {
        const text=this.value;
        $('[data-so-search]').not(this).val(text);
        $('#so-search-clear').prop('hidden',!text);
        clearTimeout(searchTimer);
        searchTimer=setTimeout(() => dataTable.search(text).draw(),250);
    });
    $('#so-search-clear').on('click',function () {
        clearTimeout(searchTimer);$('[data-so-search]').val('');this.hidden=true;
        dataTable.search('').draw();document.getElementById('search').focus();
    });

    function correction() {
        const id=$('#kt_modal_stock_detail').data('stockOpnameId');
        const row=window.stockOpnameRows[id];
        if (!row || $('#so-credit-plus').prop('disabled')) return null;
        return ui.correction({plus:$('#so-credit-plus').val(),minus:$('#so-credit-minus').val(),reason:$('#so-credit-reason').val(),physical:row.physical,pending:row.isPending});
    }
    $('#so-credit-plus,#so-credit-minus,#so-credit-reason').on('input',function () {
        $('#so-correction-save').prop('disabled',busy || !correction());
        $('#kt_modal_stock_detail [data-so-action="finalize"]').prop('disabled',busy);
    });
    $('#so-detail-correction').on('submit',function (event) {
        event.preventDefault();
        const payload=correction();
        if (busy || !payload) return;
        const id=$('#kt_modal_stock_detail').data('stockOpnameId');
        busy=true;
        $('#so-correction-save').prop('disabled',true).text('Menyimpan…');
        $('#kt_modal_stock_detail [data-so-action]').prop('disabled',true);
        $('#so-credit-plus,#so-credit-minus,#so-credit-reason').prop('readonly',true);
        $('#so-correction-error').text('');
        let committed=false;
        function finish() {
            busy=false;
            $('#so-credit-plus,#so-credit-minus,#so-credit-reason').prop('readonly',false);
            $('#so-correction-save').text('Simpan Koreksi').prop('disabled',!correction());
            $('#kt_modal_stock_detail [data-so-action]').prop('disabled',false);
        }
        $.ajax({url:`/stock-opname/${id}/adjust`,type:'POST',data:{...payload,_token:$('meta[name="csrf-token"]').attr('content')},dataType:'json',success(response) {
            // A successful write consumes the draft before any follow-up read.
            committed=true;
            $('#so-credit-plus,#so-credit-minus,#so-credit-reason').val('');
            // Existing endpoint returns the authoritative record, not a success boolean.
            if (!response?.data || String(response.data.id) !== String(id) || response.data.real_stock == null || !Number.isFinite(Number(response.data.real_stock))) {
                $('#so-correction-error').text('Respons koreksi tidak valid. Muat ulang data sebelum membuat koreksi baru.');
                finish();
                return;
            }
            const row=window.stockOpnameRows[id];
            row.physical=Number(response.data.real_stock);
            row.diff=Math.round((row.physical-row.system)*100)/100;
            row.value=Math.floor(row.diff*row.hpp);
            let refreshing=true;
            dataTable.on('xhr.soCorrection',function (event,settings,json) {
                if (json !== null) return;
                refreshing=false;
                dataTable.off('xhr.soCorrection');
                $('#so-correction-error').text('Koreksi tersimpan, tetapi pemuatan ulang gagal. Jangan kirim ulang koreksi yang sama.');
                finish();
                return true; // This failure is reported in the detail, not a DataTables alert.
            });
            dataTable.ajax.reload(function () {
                // DataTables retains reload's draw callback even after a failed read.
                if (!refreshing) return;
                refreshing=false;
                dataTable.off('xhr.soCorrection');
                if (window.stockOpnameRows[id]) openStockDetail(id);
                $('#so-correction-error').text('Koreksi tersimpan.');
                finish();
            },false);
        },error(xhr) {
            $('#so-correction-error').text(xhr.responseJSON?.message || 'Koreksi gagal disimpan. Input tetap dipertahankan.');
        },complete() {
            if (!committed) finish();
        }});
    });
    // Do not dismiss a pending write. The request outcome belongs to this detail state.
    $('#kt_modal_stock_detail').on('hide.bs.modal',event => { if (busy) event.preventDefault(); });

    const historyFilterEl=document.getElementById('so-history-filter');
    const historyFilter=bootstrap.Modal.getOrCreateInstance(historyFilterEl);
    let historyDraft='all';
    function syncHistoryDraft() {
        $('[data-so-direction]').each(function () {
            const active=this.dataset.soDirection === historyDraft;
            this.classList.toggle('is-selected',active);this.setAttribute('aria-pressed',String(active));
        });
    }
    $('#so-history-filter-open').on('click',function () {
        historyDraft=window.stockOpnameHistory?.direction || 'all';syncHistoryDraft();historyFilter.show();
    });
    $(historyFilterEl).on('shown.bs.modal',function () {
        $('.modal-backdrop').last().addClass('so-history-backdrop');
        historyFilterEl.querySelector('.so-close').focus();
    }).on('hidden.bs.modal',function () { document.getElementById('so-history-filter-open').focus(); });
    $('[data-so-direction]').on('click',function () { historyDraft=this.dataset.soDirection;syncHistoryDraft(); });
    function applyHistory(close = true) {
        if (window.stockOpnameHistory) window.stockOpnameHistory.direction=historyDraft;
        renderStockOpnameHistory();if (close !== false) historyFilter.hide();
    }
    $('#so-history-apply').on('click',applyHistory);
    $('#so-history-reset').on('click',function () {historyDraft='all';syncHistoryDraft();applyHistory(false);});
    const historyElement=document.getElementById('kt_stock_opname_history');
    const historyDrawer=KTDrawer.getInstance(historyElement) || new KTDrawer(historyElement);
    historyDrawer.on('kt.drawer.shown',function () {
        historyElement.inert=false;
        historyElement.setAttribute('aria-hidden','false');
    });
    historyDrawer.on('kt.drawer.after.hidden',function () {
        historyElement.inert=true;
        historyElement.setAttribute('aria-hidden','true');
        const id=window.stockOpnameHistory?.id;
        if (id && window.stockOpnameRows[id]) setTimeout(() => openStockDetail(id),250);
    });
    function closeHistory() {
        historyDrawer.hide();
    }
    document.getElementById('kt_stock_opname_history').addEventListener('keydown',function (event) {
        if (document.querySelector('.so-reference-sheet.show')) return;
        if (event.key === 'Escape') {event.preventDefault();closeHistory();return;}
        if (event.key !== 'Tab') return;
        const focusable=[...this.querySelectorAll('button,[tabindex="0"]')].filter(el => !el.disabled && el.getClientRects().length);
        const first=focusable[0],last=focusable[focusable.length-1];
        if (event.shiftKey && document.activeElement===first) {event.preventDefault();last?.focus();}
        else if (!event.shiftKey && document.activeElement===last) {event.preventDefault();first?.focus();}
    });
}
