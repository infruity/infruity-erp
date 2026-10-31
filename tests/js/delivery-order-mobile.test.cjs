const {test}=require('node:test');
const assert=require('node:assert/strict');
const cards=require('../../public/assets/js/delivery-order-mobile.js');

test('mobile delivery card matches the reference customer, amount and status hierarchy',()=>{
    assert.deepEqual(cards.buildViewModel({
        customerName:'Kak Alvi',
        customerPhone:'+62 812-3456-8219',
        total:52000,
        deliveryStatus:'draft',
        paymentStatus:'draft',
        courierName:'Muhammad Chamim'
    }),{
        initials:'KA',
        customerName:'Kak Alvi',
        phoneSuffix:'8219',
        customerLabel:'Kak Alvi (8219)',
        amountLabel:'Rp 52.000',
        deliveryLabel:'Belum Diterima',
        deliveryState:'unreceived',
        paymentLabel:'Belum Lunas',
        paymentState:'unpaid',
        courierName:'Muhammad Chamim',
        avatarTheme:'teal'
    });
});

test('mobile delivery detail shows the reference totals, address and receipt action',()=>{
    const detail=cards.buildDetailViewModel({
        orderId:'42',
        customerName:'Kak Alvi',
        customerPhone:'+62 812-3456-8219',
        address:'Indomaret Kedung sekar',
        total:52000,
        shippingFee:27000,
        deliveryStatus:'unreceived',
        paymentStatus:'unpaid',
        courierType:'Internal'
    });

    assert.deepEqual(detail,{
        orderId:'42',
        customerName:'Kak Alvi',
        customerPhone:'081234568219',
        address:'Indomaret Kedung sekar',
        subtotal:25000,
        shippingFee:27000,
        grandTotal:52000,
        subtotalLabel:'Rp 25.000',
        shippingFeeLabel:'Rp 27.000',
        grandTotalLabel:'Rp 52.000',
        deliveryState:'unreceived',
        actionLabel:'Sudah Diterima',
        actionMethod:'setBayar',
        paymentState:'unpaid',
        courierType:'internal'
    });
});

test('unpaid external deliveries keep the existing payment-entry action',()=>{
    const detail=cards.buildDetailViewModel({
        orderId:'44',
        customerName:'Rara',
        total:52000,
        shippingFee:27000,
        deliveryStatus:'unreceived',
        paymentStatus:'unpaid',
        courierType:'External'
    });

    assert.equal(detail.actionMethod,'setBayar');
});

test('paid undelivered orders use the delivery-only action',()=>{
    const detail=cards.buildDetailViewModel({
        orderId:'45',
        customerName:'Rara',
        total:52000,
        shippingFee:27000,
        deliveryStatus:'unreceived',
        paymentStatus:'paid',
        courierType:'External'
    });

    assert.equal(detail.actionLabel,'Sudah Diterima');
    assert.equal(detail.actionMethod,'setSelesai');
});

test('received delivery detail offers a close action instead of marking it again',()=>{
    const detail=cards.buildDetailViewModel({
        orderId:'43',
        customerName:'Ayu',
        total:180000,
        shippingFee:30000,
        deliveryStatus:'delivered',
        paymentStatus:'paid',
        courierType:'External'
    });

    assert.equal(detail.deliveryState,'received');
    assert.equal(detail.actionLabel,'Tutup');
    assert.equal(detail.actionMethod,'setSelesai');
    assert.equal(detail.courierType,'external');
});

test('mobile delivery card handles completed, paid orders and missing phone digits',()=>{
    const view=cards.buildViewModel({
        customerName:'Ayu',
        customerPhone:'12',
        total:'0',
        shippingFee:'Rp. 7.500',
        deliveryStatus:'delivered',
        paymentStatus:'paid',
        courierName:'Kurir Eksternal'
    });

    assert.equal(view.initials,'AY');
    assert.equal(view.customerLabel,'Ayu');
    assert.equal(view.amountLabel,'Rp 7.500');
    assert.equal(view.deliveryLabel,'Sudah Diterima');
    assert.equal(view.paymentLabel,'Sudah Lunas');
});

test('all unpaid undelivered orders open the in-modal payment receipt step',()=>{
    const internalUnpaid=cards.buildDetailViewModel({
        orderId:'50',
        courierType:'Internal',
        deliveryStatus:'unreceived',
        paymentStatus:'unpaid',
        total:379000,
        shippingFee:7000
    });
    const externalUnpaid=cards.buildDetailViewModel({
        orderId:'51',
        courierType:'External',
        deliveryStatus:'unreceived',
        paymentStatus:'unpaid',
        total:379000,
        shippingFee:7000
    });
    const internalPaid=cards.buildDetailViewModel({
        orderId:'52',
        courierType:'Internal',
        deliveryStatus:'unreceived',
        paymentStatus:'paid',
        total:379000,
        shippingFee:7000
    });
    const internalReceived=cards.buildDetailViewModel({
        orderId:'53',
        courierType:'Internal',
        deliveryStatus:'received',
        paymentStatus:'unpaid',
        total:379000,
        shippingFee:7000
    });

    assert.equal(cards.requiresPaymentReceiptStep(internalUnpaid),true);
    assert.equal(cards.requiresPaymentReceiptStep(externalUnpaid),true);
    assert.equal(cards.requiresPaymentReceiptStep(internalPaid),false);
    assert.equal(cards.requiresPaymentReceiptStep(internalReceived),false);
});

test('payment receipt details keep subtotal, shipping and grand total available as amounts',()=>{
    const detail=cards.buildDetailViewModel({total:379000,shippingFee:7000});

    assert.deepEqual([detail.subtotal,detail.shippingFee,detail.grandTotal],[372000,7000,379000]);
});

test('payment receipt balance shows paid, underpaid and overpaid amounts',()=>{
    assert.deepEqual(cards.buildPaymentBalance(379000,379000),{
        type:'paid',label:'Lunas :',value:'Rp 0'
    });
    assert.deepEqual(cards.buildPaymentBalance(379000,372000),{
        type:'underpaid',label:'Kurang Bayar :',value:'Rp 7.000'
    });
    assert.deepEqual(cards.buildPaymentBalance(379000,380000),{
        type:'overpaid',label:'Lebih Bayar :',value:'Rp 1.000'
    });
});
