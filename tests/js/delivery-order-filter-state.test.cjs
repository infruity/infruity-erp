const {test}=require('node:test');
const assert=require('node:assert/strict');
const filters=require('../../public/assets/js/delivery-order-filter-state.js');

test('a reset can cancel a pending debounced search callback', async()=>{
    assert.equal(typeof filters.createDebouncedSearch,'function');
    const searches=[];
    const search=filters.createDebouncedSearch(query=>searches.push(query),20);

    search.schedule('stale-query');
    search.cancel();
    await new Promise(resolve=>setTimeout(resolve,40));

    assert.deepEqual(searches,[]);
});


test('desktop viewport changes dismiss an open mobile filter sheet',()=>{
    assert.equal(typeof filters.bindDesktopDismissal,'function');
    const listeners=new Set();
    const viewport={
        addEventListener:(_,listener)=>listeners.add(listener),
        removeEventListener:(_,listener)=>listeners.delete(listener),
    };
    let isOpen=true;
    let dismissals=0;
    const unbind=filters.bindDesktopDismissal(viewport,()=>isOpen,()=>{isOpen=false;dismissals+=1;});
    const emit=matches=>[...listeners].forEach(listener=>listener({matches}));

    emit(true);
    assert.equal(dismissals,0);
    emit(false);
    assert.equal(dismissals,1);
    isOpen=true;
    emit(false);
    assert.equal(dismissals,2);
    unbind();
    isOpen=true;
    emit(false);
    assert.equal(dismissals,2);
});


test('legacy viewport listeners register, dismiss, and unbind using callback-only signatures',()=>{
    const listeners=new Set();
    const viewport={
        addListener(listener){
            assert.equal(typeof listener,'function','legacy addListener requires a callback');
            assert.equal(arguments.length,1);
            listeners.add(listener);
        },
        removeListener(listener){
            assert.equal(typeof listener,'function','legacy removeListener requires a callback');
            assert.equal(arguments.length,1);
            assert.equal(listeners.has(listener),true);
            listeners.delete(listener);
        },
    };
    let isOpen=true;
    let dismissals=0;
    const unbind=filters.bindDesktopDismissal(viewport,()=>isOpen,()=>{isOpen=false;dismissals+=1;});
    const emit=matches=>[...listeners].forEach(listener=>listener({matches}));

    assert.equal(listeners.size,1);
    emit(true);
    assert.equal(dismissals,0);
    emit(false);
    assert.equal(dismissals,1);
    emit(false);
    assert.equal(dismissals,1);
    isOpen=true;
    emit(false);
    assert.equal(dismissals,2);
    unbind();
    assert.equal(listeners.size,0);
    isOpen=true;
    emit(false);
    assert.equal(dismissals,2);
});


test('mobile filter edits stay pending until apply and close can discard them',()=>{
    const state=filters.create({branch:'1',delivery:'all',payment:'all',dateRange:''});

    state.open();
    state.setPending('branch','2');
    state.setPending('delivery','received');

    assert.deepEqual(state.applied,{branch:'1',delivery:'all',payment:'all',dateRange:''});
    assert.deepEqual(state.pending,{branch:'2',delivery:'received',payment:'all',dateRange:''});

    state.discard();
    assert.deepEqual(state.pending,state.applied);

    state.open();
    state.setPending('payment','paid');
    assert.deepEqual(state.apply(),{branch:'1',delivery:'all',payment:'paid',dateRange:''});
});

test('reset returns branch, delivery, payment, and date filters to their defaults',()=>{
    const state=filters.create({branch:'3',delivery:'unreceived',payment:'unpaid',dateRange:'2026-10-01 to 2026-10-31'});

    assert.deepEqual(state.reset(),{branch:'3',delivery:'all',payment:'all',dateRange:''});
    assert.deepEqual(state.pending,state.applied);
});

test('desktop filter changes become the baseline for a newly opened sheet',()=>{
    const state=filters.create();

    state.setApplied('branch','9');
    state.setApplied('delivery','received');
    assert.deepEqual(state.open(),{branch:'9',delivery:'received',payment:'all',dateRange:''});
});
