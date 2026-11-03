const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const viewPath = path.join(__dirname, '../../Modules/Pos/Resources/views/arus-kas/index.blade.php');
const source = fs.readFileSync(viewPath, 'utf8');
const script = source.split("@section('script')")[1].split('</script>')[0].replace(/^\s*<script>/, '');

function createApp(fetchResponse) {
    const context = {
        window: { confirm: () => true, alert: () => {} },
        document: { querySelector: () => ({ content: 'qa-csrf-token' }) },
        fetch: async () => fetchResponse,
    };
    vm.runInNewContext(`${script}\nthis.app = arusKasApp({ baseUrl: '/arus-kas' });`, context);
    const app = context.app;
    const order = { id: '1', canDelete: true };
    app.orders = [order];
    app.selectedOrder = order;
    app.detailModalOpen = true;
    return app;
}

test('delete keeps the list unchanged when a redirected HTML response is not JSON success', async () => {
    const app = createApp({ ok: true, json: async () => { throw new SyntaxError('Unexpected token <'); } });

    await app.deleteSelectedOrder();

    assert.equal(app.orders.length, 1);
    assert.equal(app.selectedOrder.id, '1');
    assert.equal(app.detailModalOpen, true);
});

test('delete removes the row only after a successful JSON response', async () => {
    const app = createApp({ ok: true, json: async () => ({ success: true }) });

    await app.deleteSelectedOrder();

    assert.equal(app.orders.length, 0);
    assert.equal(app.selectedOrder, null);
    assert.equal(app.detailModalOpen, false);
});
