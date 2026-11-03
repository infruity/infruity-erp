const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function setup(transaction = null, details = []) {
    const script = fs.readFileSync(path.join(__dirname, '../../Modules/Transaction/Resources/views/sortir/js/js-product.blade.php'), 'utf8').replace(/^<script>/, '').replace(/<\/script>\s*$/, '');
    const requests = [], navigations = [];
    const context = {URLSearchParams, console, window: {confirm: () => true, addEventListener(){}, removeEventListener(){}, location:{assign: value => navigations.push(value)}},
        document:{addEventListener(){}, removeEventListener(){}, querySelector:() => ({content:'csrf-test'})}, bootstrap:{Modal:{getOrCreateInstance:()=>({show(){}}),getInstance:()=>({hide(){}})}},
        fetch: async(url, options = {}) => {requests.push({url, ...options}); return {ok:true,json:async()=> options.method === 'POST' ? {success:true,transaksi_id:3,invoice_number:'SOR-TEST'} : [{id:1,name:'Buah',unit:{abbreviation:'Kg'},hpp:1234.567,stock_available:10}]};}};
    vm.createContext(context); vm.runInContext(script, context);
    const app = context.sortirForm({branches:[{id:1,name:'GKB'},{id:2,name:'GBA'}],today:'2026-10-23',invoiceNumber:'SOR-TEST',transaction,details,saveUrl:'/sortir/save-transaction',productsUrl:'/sortir/products',indexUrl:'/sortir'});
    app.$nextTick = fn => fn(); app.$refs = {itemModal:{showModal(){},close(){}}}; app.init();
    return {app,context,requests,navigations};
}
function add(app, quantity = 2.5) {
    app.openAddModal(); app.selectProduct({id:1,name:'Buah',unit:{abbreviation:'Kg'},hpp:10000,stock_available:10});
    app.modalItem.qty = quantity; app.saveModalItem();
}
test('add/edit/delete and totals preserve decimal quantities and zero HPP',()=>{
    const {app}=setup();add(app);assert.equal(app.cart.length,1);assert.equal(app.total,25000);assert.equal(app.dirty,true);
    app.openEditModal(app.cart[0]);app.modalItem.price=0;app.modalItem.qty=1.25;app.saveModalItem();assert.equal(app.total,0);assert.equal(app.cart[0].qty,1.25);
    app.openEditModal(app.cart[0]);app.deleteModalItem();assert.equal(app.cart.length,0);
});
test('modal validates missing product, quantity, duplicate and nonfinite HPP',()=>{
    const {app}=setup();app.saveModalItem();assert.match(app.modalError,/Pilih produk/);add(app,0);assert.equal(app.cart.length,0);
    add(app);app.openAddModal();app.selectProduct({id:1,name:'Buah',hpp:10});app.modalItem.qty=1;app.saveModalItem();assert.equal(app.cart.length,1);assert.match(app.modalError,/sudah ada/);
    app.modalItem.id=2;app.modalItem.price=Infinity;app.saveModalItem();assert.equal(app.cart.length,1);
});
test('localized quantity supports comma and dot decimal input; HPP rounds server average',()=>{
    const {app}=setup();app.updateQuantity({target:{value:'2,75'}});assert.equal(app.modalItem.qty,2.75);
    app.updateQuantity({target:{value:'1.25'}});assert.equal(app.modalItem.qty,1.25);
    app.updateQuantity({target:{value:'3,999'}});assert.equal(app.modalItem.qty,3.99);
    app.selectProduct({id:1,name:'Buah',hpp:1234.567});assert.equal(app.modalItem.price,1234.57);
    app.updatePrice({target:{value:'Rp 20.000'}});assert.equal(app.modalItem.price,20000);
    app.updatePrice({target:{value:'1.234,57'}});assert.equal(app.modalItem.price,1234.57);
});
test('switching branch confirms clearing items and invalidates stale products',()=>{
    const {app,context}=setup();add(app);context.window.confirm=()=>false;app.changeBranch(2);assert.equal(app.branchId,1);assert.equal(app.cart.length,1);
    context.window.confirm=()=>true;app.changeBranch(2);assert.equal(app.branchId,2);assert.equal(app.cart.length,0);assert.equal(app.selectedBranchName,'GBA');
});
test('product requests include authorized branch and edit credit id; stale results ignored',async()=>{
    const t=setup({id:5,branch_id:2,date:'2026-10-21',status:'paid'},[]);let first,second;
    t.context.fetch= url=>new Promise(resolve=>{if(!first)first=resolve;else second=resolve});
    const a=t.app.loadProducts();t.app.productSearch='Apel';const b=t.app.loadProducts();
    second({ok:true,json:async()=>[{id:2,name:'New'}]});await b;first({ok:true,json:async()=>[{id:1,name:'Old'}]});await a;assert.equal(t.app.products[0].id,2);
    t.context.fetch=async url=>{assert.match(url,/branch_id=2/);assert.match(url,/sortir_id=5/);return {ok:true,json:async()=>[]}};await t.app.loadProducts();assert.equal(t.app.loadingProducts,false);
});
test('product error offers retry and does not masquerade as empty results',async()=>{
    const t=setup();t.context.fetch=async()=>{throw new Error('offline')};await t.app.loadProducts();assert.equal(t.app.productsError,'offline');assert.equal(t.app.loadingProducts,false);
    t.context.fetch=async()=>({ok:true,json:async()=>[]});await t.app.loadProducts();assert.equal(t.app.productsError,'');
});
for (const status of ['draft','paid']) test(`save ${status} sends calculated payload and redirects only after success`,async()=>{
    const t=setup();add(t.app);t.requests.length=0;await t.app.saveTransaction(status);const payload=JSON.parse(t.requests[0].body);
    assert.equal(payload.status,status);assert.equal(payload.sortir_id,null);assert.equal(payload.total,25000);assert.equal(payload.items[0].discount,0);assert.equal(payload.branch_id,1);assert.equal(t.navigations.length,1);assert.equal(t.app.dirty,false);
});
test('failed validation/network save retains cart, dirty state, and no navigation',async()=>{
    const t=setup();await t.app.saveTransaction('draft');assert.equal(t.requests.length,0);assert.match(t.app.saveError,/kosong/);add(t.app);t.requests.length=0;
    t.context.fetch=async()=>({ok:false,json:async()=>({message:'Stok tidak cukup',errors:{'items.0.qty':['Stok sudah berubah']}})});
    await t.app.saveTransaction('paid');assert.match(t.app.saveError,/Stok sudah berubah/);assert.equal(t.app.cart.length,1);assert.equal(t.app.dirty,true);assert.equal(t.app.saving,false);assert.equal(t.navigations.length,0);
});
test('duplicate save clicks send only one mutation',async()=>{
    const t=setup();add(t.app);let complete;t.requests.length=0;t.context.fetch=async(url,options)=>{t.requests.push({url,...options});return await new Promise(resolve=>complete=resolve)};
    const first=t.app.saveTransaction('draft');await t.app.saveTransaction('paid');assert.equal(t.requests.length,1);complete({ok:true,json:async()=>({success:true})});await first;
});
test('loaded finalized transaction cannot accidentally revert to draft',async()=>{
    const t=setup({id:7,invoice_number:'SOR-OLD',branch_id:2,date:'2026-10-20',status:'paid'},[{product_id:4,quantity:'1.25',price:'20000.5',product:{name:'Apel',unit:{abbreviation:'Kg'}}}]);
    assert.equal(t.app.cart[0].qty,1.25);assert.equal(t.app.total,25000.63);await t.app.saveTransaction('draft');assert.equal(JSON.parse(t.requests[0].body).status,'paid');assert.equal(JSON.parse(t.requests[0].body).sortir_id,7);assert.equal(t.app.dirty,false);
});
test('completion cancellation does not save; confirmed completion does',async()=>{
    const t=setup();add(t.app);t.requests.length=0;t.context.window.Swal={};t.context.Swal={fire:async()=>({isConfirmed:false})};await t.app.confirmComplete();assert.equal(t.requests.length,0);
    t.context.Swal.fire=async()=>({isConfirmed:true});await t.app.confirmComplete();assert.equal(JSON.parse(t.requests[0].body).status,'paid');
});
