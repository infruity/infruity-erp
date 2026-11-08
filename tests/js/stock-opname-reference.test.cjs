const {test} = require('node:test');
const assert = require('node:assert/strict');
const ui = require('../../public/assets/js/stock-opname-reference.js');
const fs = require('node:fs');
const vm = require('node:vm');

// Execute the real correction binding, replacing only DOM/network boundaries.
// POST completion and the DataTables refresh are deliberately separate outcomes.
function correctionFixture() {
    const source = fs.readFileSync(require.resolve('../../public/assets/js/stock-opname-reference-page.js'), 'utf8');
    const elements = new Map();
    const get = selector => {
        if (!elements.has(selector)) elements.set(selector, {value:'',props:{},text:'',handlers:{},data:{}});
        return elements.get(selector);
    };
    const $ = selector => {
        const items = selector.split(',').map(s => get(s.trim()));
        return {
            val(value) { if (value === undefined) return items[0].value; items.forEach(e => e.value=value); return this; },
            prop(key,value) { if (value === undefined) return items[0].props[key]; items.forEach(e => e.props[key]=value); return this; },
            text(value) { if (value === undefined) return items[0].text; items.forEach(e => e.text=value); return this; },
            data(key) { return items[0].data[key]; },
            attr() { return 'test-csrf'; },
            on(event,fn) { items.forEach(e => e.handlers[event]=fn); return this; }
        };
    };
    const posts = [], events = new Map();
    let refreshCallback, opens=0;
    const rows = {7:{physical:4,isPending:true,system:3,hpp:10}};
    const dataTable = {
        on(name,fn) { events.set(name,fn); return this; },
        off(name) { events.delete(name); return this; },
        ajax:{reload(callback) { refreshCallback=callback; }}
    };
    $.ajax = options => posts.push(options);
    get('#kt_modal_stock_detail').data.stockOpnameId=7;
    const context = {ui,$,window:{stockOpnameRows:rows},dataTable,openStockDetail() { opens++; },busy:false};
    vm.runInNewContext(source.slice(source.indexOf('    function correction()'),source.indexOf('    const historyFilterEl=')),context);
    return {
        $,rows,posts,get,context,
        draft() { $('#so-credit-plus').val('1'); $('#so-credit-reason').val('recount'); },
        submit() { get('#so-detail-correction').handlers.submit({preventDefault(){}}); },
        committed() { posts[0].success({data:{id:7,real_stock:'5'}}); posts[0].complete(); },
        refresh(ok) {
            for (const [name,fn] of [...events]) if (name.startsWith('xhr')) fn({}, {}, ok ? {data:[]} : null, {status:ok ? 200 : 500});
            if (ok) refreshCallback();
        },
        opens:() => opens
    };
}

test('committed correction consumes its draft and stays locked until delayed refresh completes', () => {
    const f=correctionFixture(); f.draft(); f.submit(); f.committed();
    assert.equal(f.$('#so-credit-plus').val(),'');
    assert.equal(f.$('#so-credit-reason').val(),'');
    assert.equal(f.rows[7].physical,5);
    assert.equal(f.context.busy,true);
    assert.equal(f.$('#so-credit-plus').prop('readonly'),true);
    f.submit(); assert.equal(f.posts.length,1);
    f.refresh(true);
    assert.equal(f.context.busy,false);
    assert.equal(f.$('#so-correction-save').prop('disabled'),true);
    assert.equal(f.opens(),1);
});

test('failed refresh releases busy without replaying the committed correction or retaining its reload callback', () => {
    const f=correctionFixture(); f.draft(); f.submit(); f.committed(); f.refresh(false);
    assert.equal(f.context.busy,false);
    assert.equal(f.$('#so-credit-plus').prop('readonly'),false);
    assert.match(f.$('#so-correction-error').text(),/tersimpan.*gagal/i);
    f.submit(); assert.equal(f.posts.length,1);
    assert.equal(f.rows[7].physical,5);
    // A later unrelated draw must not reopen the detail and erase a new draft.
    f.draft(); f.refresh(true);
    assert.equal(f.opens(),0);
    assert.equal(f.$('#so-credit-reason').val(),'recount');
});

test('POST failure keeps the submitted draft available for a genuine retry', () => {
    const f=correctionFixture(); f.draft(); f.submit();
    f.posts[0].error({responseJSON:{message:'Rejected'}}); f.posts[0].complete();
    assert.equal(f.context.busy,false);
    assert.equal(f.$('#so-credit-plus').val(),'1');
    assert.equal(f.$('#so-credit-reason').val(),'recount');
    assert.equal(f.rows[7].physical,4);
    assert.equal(f.$('#so-correction-save').prop('disabled'),false);
    f.submit(); assert.equal(f.posts.length,2);
});

test('Indonesian quantity input is strict and never repairs invalid numbers', () => {
    assert.equal(ui.parseQuantity('1.234,125'), 1234.125);
    assert.equal(ui.parseQuantity('0,2'), .2);
    for (const value of ['-2', '1e3', '12abc', '1,2,3']) assert.equal(ui.parseQuantity(value), null);
    assert.equal(ui.quantity(1234.5), '1.234,5');
});

test('history computes previous balances before filtering and preserves note-only entries', () => {
    const rows = ui.history([
        {id: 3, action: 'UPDATE', real_stock: 4, difference: .67, note: 'catatan'},
        {id: 2, action: 'UPDATE', real_stock: 4, difference: .67, note: '<script>x</script>'},
        {id: 1, action: 'INITIAL', real_stock: 3.65, difference: .32}
    ]);
    assert.equal(rows[1].before, 3.65);
    assert.equal(rows[1].delta, .35);
    assert.equal(rows[2].before, 3.33);
    assert.equal(rows[0].kind, 'note');
    assert.deepEqual(ui.filterHistory(rows, 'plus').map(r => r.id), [2]);
    assert.deepEqual(ui.filterHistory(rows, 'initial').map(r => r.id), [1]);
    assert.equal(rows[1].note, '<script>x</script>');
});

test('unknown previous history state is unavailable rather than a fabricated zero movement', () => {
    const row = ui.history([{action:'UPDATE',real_stock:4}])[0];
    assert.equal(row.before, null);
    assert.equal(row.delta, null);
    assert.equal(row.kind, 'unknown');
});

test('history does not infer movement direction from tied timestamps or an unsaved fallback', () => {
    const rows = ui.history([
        {id:3,action:'UPDATE',real_stock:4,created_at:'2026-11-01 17:22:00'},
        {id:2,action:'UPDATE',real_stock:3.85,created_at:'2026-11-01 17:22:00'},
        {id:1,action:'INITIAL',real_stock:3.65,difference:.32,created_at:'2026-11-01 17:08:00'}
    ]);
    assert.equal(rows[0].before,null);
    assert.equal(rows[0].delta,null);
    assert.equal(rows[1].delta,null);
    const fallback=ui.history([{action:'INITIAL',real_stock:4,difference:.67}])[0];
    assert.equal(fallback.kind,'snapshot');
    assert.equal(fallback.delta,null);
    assert.deepEqual(ui.filterHistory([fallback],'initial'),[]);
});

test('correction yields one existing adjust payload and rejects finalized/zero/negative stock', () => {
    assert.deepEqual(ui.correction({plus:'0,3',minus:'0,1',reason:' hitung ulang ',physical:4,pending:true}), {qty:.2,type:'up',reason:'hitung ulang'});
    for (const draft of [
        {plus:'0',minus:'0',reason:'note',physical:4,pending:true},
        {plus:'1',minus:'0',reason:'note',physical:4,pending:false},
        {plus:'0',minus:'5',reason:'note',physical:4,pending:true},
        {plus:'1',minus:'0',reason:'',physical:4,pending:true},
        {plus:'0,001',minus:'0',reason:'note',physical:4,pending:true}
    ]) assert.equal(ui.correction(draft), null);
});

test('database wall time and zoned ISO format identically in application timezone regardless of host timezone', () => {
    const {execFileSync}=require('node:child_process');
    const helper=require.resolve('../../public/assets/js/stock-opname-reference.js');
    for (const TZ of ['UTC','America/Los_Angeles','Asia/Jakarta','Pacific/Auckland']) {
        const output=execFileSync(process.execPath,['-e',`
            const ui=require(${JSON.stringify(helper)});
            console.log(JSON.stringify([
                ui.formatLongDateTime('2026-11-01 10:22:00','Asia/Jakarta'),
                ui.formatLongDateTime('2026-11-01T03:22:00Z','Asia/Jakarta'),
                ui.formatLongDateTime('2026-11-01T10:22:00+07:00','Asia/Jakarta')
            ]));
        `],{env:{...process.env,TZ},encoding:'utf8'});
        assert.deepEqual(JSON.parse(output),Array(3).fill('01 Nov 2026, 10:22'),TZ);
    }
    const view=fs.readFileSync(require.resolve('../../Modules/Transaction/Resources/views/stock-opname/index.blade.php'),'utf8');
    const inline=view.slice(view.indexOf('        function formatLongDateTime('),view.indexOf('        function escapeHtml('));
    const context={StockOpnameUi:ui};
    vm.runInNewContext(inline.replace(/@json\(config\('app.timezone'\)\)/g,JSON.stringify('Asia/Jakarta')),context);
    assert.equal(context.formatLongDateTime('2026-11-01 10:22:00'),'01 Nov 2026, 10:22');
});

test('date formatter rejects invalid and normalized dates with a safe unavailable fallback', () => {
    for (const value of [null,undefined,'','not-a-date','<img src=x onerror=alert(1)>','2026-02-30 10:22:00','2026-11-01 24:00:00','2026-02-30T03:22:00Z','11/01/2026 10:22','123']) {
        assert.equal(ui.formatLongDateTime(value,'Asia/Jakarta'),'—',String(value));
    }
    assert.equal(ui.formatLongDateTime('2026-11-01','Asia/Jakarta'),'01 Nov 2026, 00:00');
});

test('date presets use application timezone and Monday-Sunday across month boundary', () => {
    assert.deepEqual(ui.period('today','Asia/Jakarta',new Date('2026-11-01T20:00:00Z')), {start:'2026-11-02',end:'2026-11-02'});
    assert.deepEqual(ui.period('week','Asia/Jakarta',new Date('2026-11-01T12:00:00Z')), {start:'2026-10-26',end:'2026-11-01'});
});

test('filter draft preserves branch, date, result and status choices and submits server filters', () => {
    const applied={branch:'7',start:'2026-11-01',end:'2026-11-04',status:'draft',result:'kurang'};
    const draft=ui.filterDraft(applied);
    draft.branch='all';
    draft.status='selesai';
    assert.deepEqual(applied,{branch:'7',start:'2026-11-01',end:'2026-11-04',status:'draft',result:'kurang'});
    assert.deepEqual(ui.filterRequest(draft),{
        cabang_filter:'all',start_date:'2026-11-01',end_date:'2026-11-04',status_filter:'selesai',result_filter:'kurang'
    });
});

test('filter draft defaults result and status to all and keeps the branch/date contract', () => {
    const draft=ui.filterDraft({branch:'all'});
    assert.deepEqual(draft,{branch:'all',start:'',end:'',status:'all',result:'all'});
    assert.deepEqual(ui.filterRequest(draft),{
        cabang_filter:'all',start_date:'',end_date:'',status_filter:'all',result_filter:'all'
    });
});

// Execute the real filter sheet wiring, replacing only DOM/DataTables boundaries.
function filterFixture() {
    const source = fs.readFileSync(require.resolve('../../public/assets/js/stock-opname-reference-page.js'), 'utf8');
    const start = source.indexOf('    function syncChoices()');
    const end = source.indexOf('    $(\'[data-so-reset="branch"]\').on(\'click\'');
    assert.ok(start > 0 && end > start, 'filter wiring block not found');

    const button = (key, value) => {
        const el = {dataset:{[key]:value}, classes:new Set(), attrs:{}};
        el.classList = {toggle(name,on) { on ? el.classes.add(name) : el.classes.delete(name); }};
        el.setAttribute = (name,val) => { el.attrs[name] = val; };
        return el;
    };
    const results = ['all','lebih','kurang','sesuai'].map(v => button('soResult',v));
    const statuses = ['all','selesai','draft'].map(v => button('soStatus',v));
    const hidden = {'so-filter-applied-status':{value:'all'}, 'so-filter-applied-result':{value:'all'}};
    const opener = {classes:new Set()};
    const label = {value:''};
    const $ = selector => {
        if (selector === '[data-so-result]') return {each(fn) { results.forEach(b => fn.call(b)); }};
        if (selector === '[data-so-status]') return {each(fn) { statuses.forEach(b => fn.call(b)); }};
        if (selector === '[data-so-open-filter]') return {toggleClass(n,on) { on ? opener.classes.add(n) : opener.classes.delete(n); }};
        if (selector === '#active-branch-button-label') return {text(v) { label.value = v; }};
        return {each() {}, toggleClass() {}, text() {}, val() { return ''; }};
    };
    const handlers = new Map();
    const bind = selector => {
        const base = $(selector);
        base.on = (event, fn) => { handlers.set(selector + ':' + event, fn); return base; };
        return base;
    };
    let draws = 0, hides = 0;
    const context = {
        ui, $: bind, branch:{value:'all'}, dateField:{value:''},
        filterEl:{querySelector:() => null},
        bootstrap:{Modal:{getOrCreateInstance:() => ({show() {}, hide() {}})}},
        searchBranches() {},
        draft: ui.filterDraft({branch:'all'}),
        dataTable:{draw() { draws++; }},
        filter:{hide() { hides++; }},
        document:{getElementById:id => hidden[id] || null}
    };
    vm.runInNewContext(source.slice(start, end), context);
    return {context, hidden, results, statuses, opener, label, handlers, draws:() => draws, hides:() => hides};
}

test('choosing a chip only edits the draft and never redraws the list', () => {
    const f = filterFixture();
    const click = selector => f.handlers.get(selector + ':click');
    assert.equal(typeof click('[data-so-result]'), 'function');
    assert.equal(typeof click('[data-so-status]'), 'function');

    click('[data-so-result]').call(f.results.find(b => b.dataset.soResult === 'kurang'));
    click('[data-so-status]').call(f.statuses.find(b => b.dataset.soStatus === 'draft'));

    assert.equal(f.context.draft.result, 'kurang');
    assert.equal(f.context.draft.status, 'draft');
    assert.equal(f.draws(), 0, 'draft/apply semantics: no draw before Terapkan Filter');
    assert.equal(f.hidden['so-filter-applied-result'].value, 'all');
    assert.equal(f.hidden['so-filter-applied-status'].value, 'all');
    assert.deepEqual(f.results.filter(b => b.classes.has('is-selected')).map(b => b.dataset.soResult), ['kurang']);
});

test('applying the sheet writes the chosen result and status for the list request', () => {
    const f = filterFixture();
    f.context.draft.status = 'draft';
    f.context.draft.result = 'kurang';
    f.context.apply(false);

    assert.equal(f.hidden['so-filter-applied-status'].value, 'draft');
    assert.equal(f.hidden['so-filter-applied-result'].value, 'kurang');
    assert.equal(f.draws(), 1);
    assert.equal(f.hides(), 0, 'apply(false) must keep the sheet open');
    assert.equal(f.opener.classes.has('is-active'), true);
    assert.equal(f.label.value, 'Filter aktif');
});

test('opening the sheet reads the applied result and status back into the draft', () => {
    const f = filterFixture();
    f.hidden['so-filter-applied-status'].value = 'selesai';
    f.hidden['so-filter-applied-result'].value = 'sesuai';

    assert.deepEqual(f.context.readApplied(), {
        branch:'all', start:'', end:'', status:'selesai', result:'sesuai'
    });
});

test('choice buttons reflect the draft and keep aria-pressed in sync', () => {
    const f = filterFixture();
    f.context.draft.status = 'draft';
    f.context.draft.result = 'lebih';
    f.context.syncChoices();

    const pressed = list => list.filter(b => b.attrs['aria-pressed'] === 'true').map(b => b.dataset.soResult || b.dataset.soStatus);
    assert.deepEqual(pressed(f.results), ['lebih']);
    assert.deepEqual(pressed(f.statuses), ['draft']);
    assert.deepEqual(f.results.filter(b => b.classes.has('is-selected')).map(b => b.dataset.soResult), ['lebih']);
});
