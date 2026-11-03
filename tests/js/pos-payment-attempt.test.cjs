const test = require('node:test');
const assert = require('node:assert/strict');
const create = require('../../public/assets/js/pos-payment-attempt.js');
const storage = () => { const values = new Map(); return { getItem: k => values.get(k), setItem: (k,v) => values.set(k,v), removeItem: k => values.delete(k) }; };
const payload = { transaction_id: 1, total_payment: 100000, payments: [{ payment_id: 1, amount: '100.000' }] };
const response = (status, result) => ({ status, ok: status < 300, json: async () => result });
test('double tap while pending sends once and releases after success', async () => {
    let finish, calls = 0;
    const attempt = create({storage: storage(),storageKey:'payment',uuid:()=> 'key-one',send:()=>{calls++;return new Promise(r=>finish=r)}});
    const first = attempt.submit(payload);
    assert.equal(attempt.busy,true);
    assert.deepEqual(await attempt.submit(payload),{ignored:true});
    finish(response(200,{success:true,payment:{id:9}}));
    await first;
    assert.equal(calls,1);assert.equal(attempt.busy,false);assert.equal(attempt.pending,null);
});
test('lost response and reload retain the same key and immutable payload', async () => {
    const store = storage();const sent=[];
    const attempt=create({storage:store,storageKey:'payment',uuid:()=> 'original-key',send:async(p,key)=>{sent.push({p,key});throw new Error('offline')}});
    await assert.rejects(attempt.submit(payload),/offline/);
    const reloaded=create({storage:store,storageKey:'payment',uuid:()=> 'must-not-be-used',send:async(p,key)=>{sent.push({p,key});return response(200,{success:true,payment:{id:9}})}});
    await reloaded.submit({...payload,total_payment:200000});
    assert.deepEqual(sent[0],sent[1]);assert.equal(reloaded.pending,null);
});
test('5xx retains attempt, definitive validation permits a new intent', async () => {
    let status=500,sequence=0;const keys=[];
    const attempt=create({storage:storage(),storageKey:'payment',uuid:()=> 'key-'+(++sequence),send:async(p,key)=>{keys.push(key);return response(status,{success:false,message:'fixture'})}});
    await assert.rejects(attempt.submit(payload));status=422;
    await assert.rejects(attempt.submit(payload));assert.equal(attempt.pending,null);status=200;
    await assert.rejects(attempt.submit(payload));
    assert.deepEqual(keys,['key-1','key-1','key-2']);
});
