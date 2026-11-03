const {test} = require('node:test');
const assert = require('node:assert/strict');
const createList = require('../../public/assets/js/sortir-list.js');
const response = data => ({ok:true,json:async()=>data});
const fixture = {id:1,invoice_number:'SOR-1',branch_name:'Gresik / Driyorejo',date:'2026-10-23 13:00:00',status:'paid',total:25000,can_delete:true,delete_url:'/sortir/1'};
function app() { const value = createList({endpoint:'/sortir/data',csrf:'test'});value.$nextTick=()=>{};return value; }
test('list requests server search, filters, and pagination then appends next page', async()=>{
    const a=app(), requests=[];
    global.fetch=async url=>{requests.push(url);return response({data:[fixture],recordsFiltered:2,recordsTotal:3})};
    a.search='buah';a.filters.branch='2';await a.load();assert.equal(a.hasMore,true);
    let params=new URL(requests[0],'http://localhost').searchParams;
    assert.equal(params.get('mobile'),'1');assert.equal(params.get('search'),'buah');assert.equal(params.get('cabang_filter'),'2');
    await a.load(true);params=new URL(requests[1],'http://localhost').searchParams;
    assert.equal(params.get('start'),'1');assert.equal(a.rows.length,2);assert.equal(a.hasMore,false);
});
test('older search responses cannot replace the latest search', async()=>{
    const a=app(),pending=[];
    global.fetch=()=>new Promise(resolve=>pending.push(resolve));
    const old=a.load();a.search='new';const next=a.load();
    pending[1](response({data:[{id:2}],recordsFiltered:1}));await next;
    pending[0](response({data:[{id:1}],recordsFiltered:1}));await old;assert.equal(a.rows[0].id,2);
});
test('failed request shows retry state and resets loading',async()=>{
    const a=app();global.fetch=async()=>{throw Error('Offline')};await a.load();assert.equal(a.error,'Offline');assert.equal(a.loading,false);
    global.fetch=async()=>response({data:[],recordsFiltered:0});await a.load();assert.equal(a.error,'');
});
test('filters apply only on confirmation; reversed date range is rejected',()=>{
    const a=app();let loaded=0;a.load=()=>loaded++;
    a.openFilter();a.pending.status='paid';assert.equal(a.filters.status,'all');
    a.pending.start='2026-10-24';a.pending.end='2026-10-23';a.applyFilter();assert.equal(loaded,0);
    a.pending.end='2026-10-25';a.applyFilter();assert.equal(loaded,1);assert.equal(a.filters.status,'paid');
    a.resetFilters();assert.equal(a.activeFilters,false);assert.equal(a.filters.branch,'all');
});
test('delete cancellation and permission guard never send a request',async()=>{
    const a=app();let sent=0;global.fetch=async()=>{sent++};global.Swal={fire:async()=>({isConfirmed:false})};
    a.selected=fixture;await a.deleteSelected();assert.equal(sent,0);
    a.selected={...fixture,can_delete:false};await a.deleteSelected();assert.equal(sent,0);
});
test('successful delete refreshes list; failed delete retains detail and permits retry',async()=>{
    const a=app();let reloaded=0;a.load=async()=>reloaded++;a.selected=fixture;
    global.Swal={fire:async()=>({isConfirmed:true})};let sent;
    global.fetch=async(url,options)=>{sent={url,...options};return response({success:true})};
    await a.deleteSelected();assert.equal(sent.method,'DELETE');assert.equal(sent.headers['X-CSRF-TOKEN'],'test');assert.equal(reloaded,1);assert.equal(a.selected,null);
    a.selected=fixture;global.fetch=async()=>({ok:false,json:async()=>({message:'Tidak diizinkan'})});
    await a.deleteSelected();assert.equal(a.selected.id,1);assert.equal(a.deleting,false);assert.equal(reloaded,1);
});
test('status and currency match inventory write-off values',()=>{
    const a=app();assert.equal(a.statusLabel('debt'),'Selesai');assert.equal(a.statusLabel('draft'),'Draf');assert.equal(a.money(25000),'Rp 25.000');assert.equal(a.money(25000.65),'Rp 25.000,65');assert.equal(a.statusLabel('canceled'),'Dibatalkan');assert.equal(a.initials('Gresik / Driyorejo'),'GR');
});
