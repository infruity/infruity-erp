const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function setup() {
    const source = fs.readFileSync(path.join(__dirname, '../../Modules/Transaction/Resources/views/pengadaan/js/js-product.blade.php'), 'utf8')
        .replace(/^<script>/, '').replace(/<\/script>\s*$/, '').replace(/@json\([^\n]+\)/g, 'null');
    const alerts = [], requests = [], timers = new Map();
    const fields = { branch: {value:'1'}, date: {value:'2026-10-23'}, invoice: {value:'TEST-PO'} };
    let redirectCount = 0, timerId = 0;
    const $ = () => ({val(){return this}, trigger(){return this}});
    $.ajax = request => requests.push(request);
    const context = {console:{log(){},error(){}}, $, document:{
        getElementById:()=>({}),
        querySelector(selector) {
            if (selector.includes('branch_id')) return fields.branch;
            if (selector.includes('invoice_number')) return fields.invoice;
            if (selector.includes('name="date"')) return fields.date;
            return {getAttribute:()=> 'test-csrf'};
        },
    }, bootstrap:{Modal:{getInstance:()=>({hide(){}})}}, Swal:{fire: x=>alerts.push(x)},
    setTimeout:fn=>{timers.set(++timerId,fn);return timerId}, clearTimeout:id=>timers.delete(id),
    redirectToHome:()=>redirectCount++,
    fetch:async (url, options)=>{requests.push({url,...options});return {ok:true,json:async()=>({success:true,invoice_number:'TEST-PO'})}}};
    vm.createContext(context); vm.runInContext(source, context);
    const app=context.posApp(); app.autoSaveEnabled=true;
    return {app, context, alerts, requests, fields, timers, redirects:()=>redirectCount};
}
function add(app) {
    app.resetAddForm();
    app.selectAddProduct({id:11,name:'Buah',unit:{abbreviation:'Kg'},price:10000,last_sell_price:12500});
    app.addProduct.qty=2.5; app.saveAddToCart();
}
test('add, reset, edit price/quantity including zero, delete item and totals',()=>{
    const {app}=setup(); add(app);
    assert.equal(app.cart.length,1); assert.equal(app.totalHargaKeseluruhan,25000);
    assert.equal(app.addProduct.sell,0);
    app.openEditModal(app.cart[0]); app.formattedEditPrice='20.000';
    assert.equal(app.editTotalFormatted,'50.000');
    app.saveEditToCart(); assert.equal(app.totalHargaKeseluruhan,50000);
    app.openEditModal(app.cart[0]); app.formattedEditPrice='0';
    app.updateTotalFromEditQty(); assert.equal(app.editTotalFormatted,'0');
    app.editQty=0; app.saveEditToCart(); assert.equal(app.cart[0].price,20000);
    app.editQty=1; app.saveEditToCart(); assert.equal(app.cart[0].price,0);
    app.openEditModal(app.cart[0]); app.deleteFromCart(); assert.equal(app.cart.length,0);
});
test('missing product and quantity do not add; margin is calculated',()=>{
    const {app}=setup();app.resetAddForm();app.saveAddToCart();assert.equal(app.cart.length,0);
    app.selectAddProduct({id:1,name:'Buah',unit:null,price:10000,last_sell_price:12500});
    assert.equal(app.addProduct_HPPPercent,'20.00');app.saveAddToCart();assert.equal(app.cart.length,0);
});
test('search ignores older results and supports failure/retry',()=>{
    const {app,requests}=setup();app.loadAddProducts();app.addProductSearch='Buah';app.loadAddProducts();
    requests[1].success([{id:2}]);requests[0].success([{id:1}]);assert.equal(app.addProducts[0].id,2);
    app.loadAddProducts();requests[2].error();assert.equal(app.addProductsError,true);
    app.loadAddProducts();requests[3].success([]);assert.equal(app.addProductsError,false);
});
for (const [method,status] of [['saveTransaction','draft'],['saveToOrderBook','posting']]) {
    test(`${method} sends correct status and redirects only on success`,async()=>{
        const t=setup();add(t.app);let done=0;
        await t.app[method](()=>done++);
        assert.equal(JSON.parse(t.requests[0].body).status,status);
        assert.equal(JSON.parse(t.requests[0].body).total,25000);
        assert.equal(t.redirects(),1);assert.equal(done,1);assert.equal(t.app.isSubmitting,false);
    });
}
test('empty cart or missing branch prevents both saves and resets loading',async()=>{
    const t=setup();let done=0;
    await t.app.saveTransaction(()=>done++);add(t.app);t.fields.branch.value='';
    await t.app.saveToOrderBook(()=>done++);
    assert.equal(t.requests.length,0);assert.equal(done,2);
});
test('duplicate clicks blocked; final save waits for ongoing autosave',async()=>{
    const t=setup();add(t.app);let finish;
    t.app.autoSaveRequest=new Promise(resolve=>finish=resolve);
    const first=t.app.saveTransaction();await t.app.saveToOrderBook();
    assert.equal(t.requests.length,0);finish();await first;assert.equal(t.requests.length,1);
});
test('validation and network errors show one error, preserve cart, allow retry',async()=>{
    const t=setup();add(t.app);
    t.context.fetch=async()=>({ok:false,json:async()=>({errors:{branch_id:['Cabang tidak valid']}})});
    await t.app.saveTransaction();assert.equal(t.alerts.length,1);assert.equal(t.alerts[0].text,'Cabang tidak valid');
    assert.equal(t.redirects(),0);assert.equal(t.app.cart.length,1);assert.equal(t.app.isSubmitting,false);
    t.context.fetch=async()=>{throw Error('Offline')};await t.app.saveToOrderBook();assert.equal(t.alerts.length,2);
});
test('deleting last item autosaves empty temp; edit/detail do not autosave',async()=>{
    const t=setup();add(t.app);t.app.openEditModal(t.app.cart[0]);t.app.deleteFromCart();
    t.app.autoSaveTemp();await [...t.timers.values()].at(-1)();await t.app.autoSaveRequest;
    assert.equal(JSON.parse(t.requests[0].body).items.length,0);
    assert.equal(JSON.parse(t.requests[0].body).status,'temp');
    t.app.autoSaveEnabled=false;t.timers.clear();t.app.autoSaveTemp();assert.equal(t.timers.size,0);
});
test('loading branches preserves an existing draft selection; defaults only if empty',()=>{
    const source=fs.readFileSync(path.join(__dirname,'../../Modules/Transaction/Resources/views/pengadaan/js/js-header.blade.php'),'utf8').replace(/^<script>/,'').replace(/<\/script>\s*$/,'');
    for (const initial of ['2','']) {
        let value=initial, appended=0, success;
        const element={val:()=>value,append(option){appended++;value=option.value;return this},trigger(){return this},select2(){return this},next(){return this},toggleClass(){return this}};
        const $=()=>element;$.ajax=request=>success=request.success;
        const context={$ , window:{location:{pathname:'/wholesale/create'}},Option:function(name,id){this.value=id}};
        vm.createContext(context);vm.runInContext(source,context);
        success([{id:'1',name:'A'},{id:'2',name:'B'}]);
        assert.equal(value,initial||'1');assert.equal(appended,initial?0:1);
    }
});
