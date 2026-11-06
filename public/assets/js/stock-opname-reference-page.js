/* Bind the stock opname reference presentation to the existing page contracts. */
function initStockOpnameReference() {
    'use strict';
    const ui = window.StockOpnameUi;
    const $ = window.jQuery;
    const filterEl = document.getElementById('so-filter-sheet');
    const branch = document.querySelector('[data-erp-filter="cabang"]');
    const dateField = document.getElementById('kt_ecommerce_sales_flatpickr');
    const timezone = filterEl.dataset.soTimezone || 'Asia/Jakarta';
    let draft = ui.filterDraft({branch:branch.value});
    let dateSide = 'start';
    let searchTimer;
    let trigger;
    let busy = false;
    dataTable.on('draw', function () {
        $('#transaction-table_wrapper').toggleClass('so-single-page', dataTable.page.info().pages <= 1);
    });
    const showCalendar = open => {
        document.getElementById('so-filter-main').hidden = open;
        document.getElementById('so-filter-calendar').hidden = !open;
        requestAnimationFrame(() => {
            const target = open ? document.getElementById('so-calendar-back') : document.querySelector(`[data-so-date="${dateSide}"]`);
            if (target?.getClientRects().length) target.focus({preventScroll:true});
            target?.scrollIntoView({block:'nearest'});
        });
    };
    const dateLabel = date => date ? new Intl.DateTimeFormat('id-ID',{day:'2-digit',month:'short',year:'numeric',timeZone:'UTC'}).format(new Date(date+'T00:00:00Z')) : 'Pilih Tanggal';
    const calendar = flatpickr('#so-draft-calendar', {
        inline:true,appendTo:document.getElementById('so-calendar-host'),disableMobile:true,
        dateFormat:'Y-m-d',defaultDate:[],
        onChange(dates, text) {
            if (!dates.length) return;
            draft[dateSide] = text;
            if (dateSide === 'end' && !draft.start) draft.start = text;
            if (draft.start && draft.end && draft.start > draft.end) {
                if (dateSide === 'start') draft.end = text;
                else draft.start = text;
            }
            syncDates();
            showCalendar(false);
        }
    });
    function syncDates() {
        $('#so-date-start-label').text(dateLabel(draft.start));
        $('#so-date-end-label').text(dateLabel(draft.end));
        $('[data-so-reset="period"]').prop('hidden',!draft.start && !draft.end);
        $('[data-so-period]').each(function () {
            const period = ui.period(this.dataset.soPeriod,timezone);
            const active = period.start === draft.start && period.end === draft.end;
            this.classList.toggle('is-selected',active);
            this.setAttribute('aria-pressed',String(active));
        });
    }
    function syncBranches() {
        $('[data-so-reset="branch"]').prop('hidden',draft.branch === 'all');
        $('input[name="so-filter-draft-branch"]').each(function () { this.checked = this.value === draft.branch; });
    }
    function searchBranches() {
        const query = $('#so-filter-branch-search').val().toLocaleLowerCase('id-ID').trim();
        const labels = [...filterEl.querySelectorAll('.so-branch-options label')];
        labels.forEach(label => { label.hidden = !label.textContent.toLocaleLowerCase('id-ID').includes(query); });
        filterEl.querySelector('.so-branch-empty').hidden = labels.some(label => !label.hidden);
    }
    function readApplied() {
        const [start='',end=''] = dateField.value.split(' to ');
        return ui.filterDraft({branch:branch.value,start,end:end || start});
    }
    function apply(close = true) {
        const fields = ui.filterRequest(draft);
        branch.value = fields.cabang_filter;
        dateField.value = fields.start_date ? `${fields.start_date} to ${fields.end_date}` : '';
        const active = fields.cabang_filter !== 'all' || Boolean(fields.start_date || fields.end_date);
        $('[data-so-open-filter]').toggleClass('is-active',active);
        $('#active-branch-button-label').text(active ? 'Filter aktif' : 'Filter');
        dataTable.draw();
        if (close !== false) erp.dialog.close(filterEl);
    }
    $('[data-so-open-filter]').on('click',function () {
        trigger = this;
        draft = readApplied();
        syncBranches();syncDates();
        $('#so-filter-branch-search').val('');searchBranches();
        document.getElementById('so-filter-main').hidden = false;
        document.getElementById('so-filter-calendar').hidden = true;
        erp.dialog.open(filterEl,trigger);
    });
    // dialog.js restores focus to the opener; only the aria state is ours.
    $(filterEl).on('erp:dialog-close',function (event) {
        if (event.target === filterEl) $('[data-so-open-filter]').attr('aria-expanded','false');
    }).on('erp:dialog-open',function (event) {
        if (event.target === filterEl) $('[data-so-open-filter]').attr('aria-expanded','true');
    });
    $('#so-filter-branch-search').on('input',searchBranches);
    $('input[name="so-filter-draft-branch"]').on('change',function () { draft.branch = this.value;syncBranches(); });
    $('[data-so-reset="branch"]').on('click',function () {
        draft.branch='all';syncBranches();
        filterEl.querySelector('#so-filter-branch-search').focus();
    });
    $('[data-so-reset="period"]').on('click',function () {
        draft.start='';draft.end='';calendar.clear(false);syncDates();
        filterEl.querySelector('[data-so-date="start"]').focus();
    });
    $('[data-so-period]').on('click',function () { Object.assign(draft,ui.period(this.dataset.soPeriod,timezone));syncDates(); });
    $('[data-so-date]').on('click',function () {
        dateSide = this.dataset.soDate;
        calendar.setDate(draft[dateSide] || [],false);
        showCalendar(true);
    });
    $('#so-calendar-back').on('click',() => showCalendar(false));
    $('#so-filter-apply').on('click',apply);
    $('#so-filter-reset').on('click',function () { draft=ui.filterDraft({branch:'all'});syncBranches();syncDates();apply(false); });

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
    // A pending write must not be dismissed: static dialogs ignore Esc and backdrop clicks, and the close buttons are disabled.
    function lockDetail(locked) {
        $('#kt_modal_stock_detail').attr('data-static',locked ? '' : null);
        $('#kt_modal_stock_detail [data-erp-close]').prop('disabled',locked);
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
        lockDetail(true);
        $('#so-correction-save').prop('disabled',true).text('Menyimpan…');
        $('#kt_modal_stock_detail [data-so-action]').prop('disabled',true);
        $('#so-credit-plus,#so-credit-minus,#so-credit-reason').prop('readonly',true);
        $('#so-correction-error').text('');
        let committed=false;
        function finish() {
            busy=false;
            lockDetail(false);
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

    const historyFilterEl=document.getElementById('so-history-filter');
    let historyDraft='all';
    function syncHistoryDraft() {
        $('[data-so-direction]').each(function () {
            const active=this.dataset.soDirection === historyDraft;
            this.classList.toggle('is-selected',active);this.setAttribute('aria-pressed',String(active));
        });
    }
    $('#so-history-filter-open').on('click',function () {
        historyDraft=window.stockOpnameHistory?.direction || 'all';syncHistoryDraft();erp.dialog.open(historyFilterEl,this);
    });
    $('[data-so-direction]').on('click',function () { historyDraft=this.dataset.soDirection;syncHistoryDraft(); });
    function applyHistory(close = true) {
        if (window.stockOpnameHistory) window.stockOpnameHistory.direction=historyDraft;
        renderStockOpnameHistory();if (close !== false) erp.dialog.close(historyFilterEl);
    }
    $('#so-history-apply').on('click',applyHistory);
    $('#so-history-reset').on('click',function () {historyDraft='all';syncHistoryDraft();applyHistory(false);});
    const historyElement=document.getElementById('kt_stock_opname_history');
    // Closing the history returns to the detail sheet it was opened from.
    $(historyElement).on('erp:dialog-close',function (event) {
        if (event.target !== historyElement) return;
        const id=window.stockOpnameHistory?.id;
        if (id && window.stockOpnameRows[id]) setTimeout(() => openStockDetail(id),250);
    });
}
