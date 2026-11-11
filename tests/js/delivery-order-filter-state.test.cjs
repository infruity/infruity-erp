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

test('the debounced search only reports the latest query',async()=>{
    const searches=[];
    const search=filters.createDebouncedSearch(query=>searches.push(query),15);

    search.schedule('a');
    search.schedule('ab');
    await new Promise(resolve=>setTimeout(resolve,40));

    assert.deepEqual(searches,['ab']);
});

test('setApplied changes one filter at a time and ignores unknown keys',()=>{
    const state=filters.create({branch:'1'});

    state.setApplied('delivery','received');
    state.setApplied('payment','paid');
    state.setApplied('dateRange','2026-11-01 to 2026-11-07');
    state.setApplied('nope','x');

    assert.deepEqual(state.applied,{branch:'1',delivery:'received',payment:'paid',dateRange:'2026-11-01 to 2026-11-07'});
});

test('reset returns branch, delivery, payment, and date filters to their defaults',()=>{
    const state=filters.create({branch:'3',delivery:'unreceived',payment:'unpaid',dateRange:'2026-10-01 to 2026-10-31'});

    assert.deepEqual(state.reset(),{branch:'3',delivery:'all',payment:'all',dateRange:''});
    assert.deepEqual(state.applied,{branch:'3',delivery:'all',payment:'all',dateRange:''});
});

test('a snapshot cannot be used to edit the applied filters',()=>{
    const state=filters.create();

    state.applied.branch='9';

    assert.equal(state.applied.branch,'all');
});
