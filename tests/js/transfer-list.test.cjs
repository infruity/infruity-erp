const {test}=require('node:test');
globalThis.ErpTone=require('../../public/assets/js/erp-tone.js');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const fs=require('node:fs');
function setup(){const requests=[];const context={ErpTone:require('../../public/assets/js/erp-tone.js'),URLSearchParams,fetch:async url=>{requests.push(url);return {ok:true,json:async()=>({data:[{id:requests.length}],recordsTotal:50,recordsFiltered:30})}}};vm.createContext(context);vm.runInContext(fs.readFileSync('public/assets/js/transfer-list.js','utf8'),context);const app=context.transferList({endpoint:'/data',side:'transfer-penerima',receiving:true});return {app,context,requests};}
test('list sends side, search, filters and paginates without losing loaded rows',async()=>{const t=setup();t.app.search='Apel';t.app.filters.status='pending';await t.app.load();assert.match(t.requests[0],/url=transfer-penerima/);assert.match(t.requests[0],/search=Apel/);assert.match(t.requests[0],/status_filter=pending/);await t.app.load(true);assert.match(t.requests[1],/start=1/);assert.equal(t.app.rows.length,2);assert.equal(t.app.hasMore,true);});
test('list status and counterpart branch correspond to receiving workflow',()=>{const {app}=setup();assert.equal(app.statusLabel('pending'),'Dalam Perjalanan');assert.equal(app.statusLabel('selesai'),'Sudah Diterima');assert.equal(app.branchName({origin:'GKB',destination:'GBA'}),'GKB');});
test('latest search wins when old request finishes later',async()=>{const t=setup();let a,b;t.context.fetch=()=>new Promise(r=>{if(!a)a=r;else b=r});const first=t.app.load();t.app.search='Naga';const second=t.app.load();b({ok:true,json:async()=>({data:[{id:2}],recordsFiltered:1})});await second;a({ok:true,json:async()=>({data:[{id:1}],recordsFiltered:1})});await first;assert.equal(t.app.rows[0].id,2);assert.equal(t.app.loading,false);});
test('network failure shows error and can be retried',async()=>{const t=setup();t.context.fetch=async()=>{throw new Error('Koneksi putus')};await t.app.load();assert.equal(t.app.error,'Koneksi putus');assert.equal(t.app.loading,false);t.context.fetch=async()=>({ok:true,json:async()=>({data:[],recordsFiltered:0})});await t.app.load();assert.equal(t.app.error,'');assert.equal(t.app.rows.length,0);});
test('browser network failure shows a readable Indonesian message',async()=>{const t=setup();t.context.fetch=async()=>{const e=new Error('Failed to fetch');e.name='TypeError';throw e};await t.app.load();assert.equal(t.app.error,'Periksa koneksi lalu coba lagi.');assert.equal(t.app.loading,false);})
test('document view preserves selected transfer, respects permission and restores detail on back',()=>{
    const t=setup();let focused=0,printed=0;
    t.context.document={getElementById:()=>({focus(){focused++}})};t.context.window={print(){printed++}};t.app.$nextTick=fn=>fn();
    const row={id:9,invoice_number:'TRF9',can_show:false};t.app.openDetail(row);t.app.openDocument();assert.equal(t.app.documentOpen,false);t.app.printDocument();assert.equal(printed,0);
    row.can_show=true;t.app.openDocument();assert.equal(t.app.documentOpen,true);assert.equal(t.app.selected.id,9);t.app.printDocument();assert.equal(printed,1);
    t.app.closeDocument();assert.equal(t.app.documentOpen,false);assert.equal(t.app.selected.id,9);assert.ok(focused>=3);
    t.app.openDocument();t.app.closeDetail();assert.equal(t.app.documentOpen,false);assert.equal(t.app.selected,null);
});
test('opening hands the sheet the applied filters; apply sends the selected branch and status',async()=>{
    const t=setup();const opened=[];t.app.$dispatch=(name,detail)=>opened.push({name,detail});t.app.filters.branch='1';
    t.app.openFilter();
    assert.equal(t.app.filterOpen,true);
    assert.deepEqual(JSON.parse(JSON.stringify(opened)),[{name:'erp-filter-open',detail:{id:'transfer',values:{status:'all',branch:'1',start:'',end:''}}}]);
    t.app.closeFilter();
    assert.equal(t.app.filters.branch,'1');assert.equal(t.requests.length,0);
    t.app.applyFilters({status:'pending',branch:'2',start:'',end:''});
    assert.equal(t.app.activeFilters,true);
    assert.match(t.requests[0],/cabang_filter=2/);assert.match(t.requests[0],/status_filter=pending/);
});
test('reset clears branch, status, dates and search before reloading',()=>{
    const t=setup();t.app.search='apel';t.app.filterOpen=true;t.app.filters={branch:'2',status:'pending',start:'2026-10-01',end:'2026-10-24'};
    t.app.resetFilters();assert.equal(t.app.search,'');assert.equal(t.app.filterOpen,false);assert.equal(t.app.activeFilters,false);
    const query=new URL(t.requests[0],'http://localhost').searchParams;
    assert.equal(query.get('cabang_filter'),'all');assert.equal(query.get('status_filter'),'all');assert.equal(query.get('start_date'),'');assert.equal(query.get('search'),'');
});
test('sender and receiver lists tint a branch the same way, and different branches differently',()=>{
    const createTransferList=require('../../public/assets/js/transfer-list.js');
    const sender=createTransferList({receiving:false});
    const receiver=createTransferList({receiving:true});
    assert.deepEqual(sender.branchTheme('Toko GKB'),receiver.branchTheme('Toko GKB'));
    assert.deepEqual(sender.branchTheme('Toko GKB'),ErpTone.style('Toko GKB'));
    assert.notDeepEqual(sender.branchTheme('Toko GKB'),sender.branchTheme('Toko GBA'));
});
