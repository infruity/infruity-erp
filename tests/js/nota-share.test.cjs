const test = require('node:test');
const assert = require('node:assert/strict');
const NotaShare = require('../../public/assets/js/nota-share.js');

function makeEnv({ canShare = true, shareError = null, clipboard = true } = {}) {
    const calls = { share: [], open: [], clip: [], clicks: 0, toasts: [] };
    const body = { appendChild: node => { if (node.isToast) calls.toasts.push(node.textContent); } };
    const doc = {
        body,
        createElement: tag => {
            const node = { tag, style: {}, remove() {}, setAttribute() {}, click() { calls.clicks += 1; } };
            if (tag === 'div') node.isToast = true;
            return node;
        },
    };
    const win = {
        File: class { constructor(parts, name, opts) { this.name = name; this.type = opts.type; } },
        ClipboardItem: class { constructor(data) { this.data = data; } },
        URL: { createObjectURL: () => 'blob:x', revokeObjectURL() {} },
        open: url => { calls.open.push(url); return {}; },
        navigator: {
            share: canShare ? async data => { calls.share.push(data); if (shareError) throw shareError; } : undefined,
            canShare: canShare ? () => true : undefined,
            clipboard: clipboard ? { write: async items => { calls.clip.push(items); } } : undefined,
        },
    };
    return { win, doc, calls };
}

const info = { customer: 'Mas Norman', invoice: 'INV-1', total: 'Rp 12.000' };

test('phone numbers are normalised to 62', () => {
    assert.equal(NotaShare.normalizePhone('0812-3456'), '628123456');
    assert.equal(NotaShare.normalizePhone('812 3456'), '628123456');
    assert.equal(NotaShare.normalizePhone('+62 812 3456'), '628123456');
    assert.equal(NotaShare.normalizePhone(''), '');
});

test('the message carries invoice and total but no link', () => {
    const text = NotaShare.buildMessage(info);
    assert.match(text, /INV-1/);
    assert.match(text, /Rp 12\.000/);
    assert.doesNotMatch(text, /https?:\/\//);
});

test('without a customer number the picture goes to the share sheet and WhatsApp is not opened by us', async () => {
    const { win, doc, calls } = makeEnv();
    const result = await NotaShare.share({ win, doc, blob: {}, filename: 'Nota-INV-1.png', text: 'hi', phone: '' });
    assert.equal(result, 'shared');
    assert.equal(calls.share.length, 1);
    assert.equal(calls.share[0].files[0].name, 'Nota-INV-1.png');
    assert.equal(calls.share[0].files[0].type, 'image/png');
    assert.equal(calls.open.length, 0);
});

test('cancelling the share sheet does nothing else', async () => {
    const { win, doc, calls } = makeEnv({ shareError: Object.assign(new Error('x'), { name: 'AbortError' }) });
    const result = await NotaShare.share({ win, doc, blob: {}, filename: 'a.png', text: 'hi', phone: '' });
    assert.equal(result, 'cancelled');
    assert.equal(calls.open.length, 0);
    assert.equal(calls.clicks, 0);
});

test('with a customer number the chat opens, the picture is copied and downloaded, and the share sheet is not used', async () => {
    const { win, doc, calls } = makeEnv();
    const result = await NotaShare.share({ win, doc, blob: {}, filename: 'Nota-INV-1.png', text: 'Halo', phone: '0812345678' });
    assert.equal(result, 'manual');
    assert.equal(calls.share.length, 0);
    assert.equal(calls.open.length, 1);
    assert.match(calls.open[0], /^https:\/\/wa\.me\/62812345678\?text=Halo/);
    assert.equal(calls.clip.length, 1);
    assert.equal(calls.clicks, 1);
    assert.equal(calls.toasts.length, 1);
});

test('a missing picture is reported instead of opening anything', async () => {
    const { win, doc, calls } = makeEnv();
    const result = await NotaShare.share({ win, doc, blob: null, filename: 'a.png', text: 'hi', phone: '0812' });
    assert.equal(result, 'unavailable');
    assert.equal(calls.open.length, 0);
    assert.equal(calls.share.length, 0);
});

test('without a number and without Web Share it opens WhatsApp to pick the chat, with the picture copied', async () => {
    const { win, doc, calls } = makeEnv({ canShare: false });
    const result = await NotaShare.share({ win, doc, blob: {}, filename: 'a.png', text: 'Halo', phone: '' });
    assert.equal(result, 'manual');
    assert.match(calls.open[0], /^https:\/\/wa\.me\/\?text=Halo/);
    assert.equal(calls.clip.length, 1);
});

function publicEnv(options) {
    const env = makeEnv(options);
    const target = { href: '' };
    env.win.location = { hostname: 'app.infruity.com' };
    env.win.open = url => { env.calls.open.push(url); return { location: target }; };
    env.target = target;
    return env;
}

test('public hosts are told apart from local and private ones', () => {
    assert.equal(NotaShare.isPublicHost('app.infruity.com'), true);
    for (const host of ['localhost', '127.0.0.1', '192.168.1.5', '10.0.0.8', '172.20.1.1', 'erp.local', 'erp.test', '']) {
        assert.equal(NotaShare.isPublicHost(host), false, host);
    }
});

test('on a public host with a customer number the chat gets the nota link so WhatsApp shows the picture as a preview', async () => {
    const { win, doc, calls, target } = publicEnv();
    const result = await NotaShare.share({
        win, doc, blob: {}, filename: 'a.png', text: 'Halo', phone: '0812345678',
        publish: async () => 'https://app.infruity.com/cek-nota/abc',
    });
    assert.equal(result, 'link');
    assert.equal(calls.open.length, 1);
    assert.match(target.href, /^https:\/\/wa\.me\/62812345678\?text=/);
    assert.match(decodeURIComponent(target.href), /Halo\n\nhttps:\/\/app\.infruity\.com\/cek-nota\/abc$/);
    assert.equal(calls.share.length, 0);
    assert.equal(calls.clicks, 0);
});

test('if storing the picture fails the copy and paste route takes over in the same window', async () => {
    const { win, doc, calls, target } = publicEnv();
    const result = await NotaShare.share({
        win, doc, blob: {}, filename: 'a.png', text: 'Halo', phone: '0812345678',
        publish: async () => { throw new Error('HTTP 500'); },
    });
    assert.equal(result, 'manual');
    assert.equal(calls.open.length, 1);
    assert.match(target.href, /^https:\/\/wa\.me\/62812345678\?text=Halo/);
    assert.equal(calls.clip.length, 1);
    assert.equal(calls.clicks, 1);
});

test('on a local host the link route is skipped even if a publisher exists', async () => {
    const { win, doc, calls } = makeEnv();
    win.location = { hostname: 'localhost' };
    let published = 0;
    const result = await NotaShare.share({ win, doc, blob: {}, filename: 'a.png', text: 'Halo', phone: '0812345678', publish: async () => { published += 1; return 'x'; } });
    assert.equal(result, 'manual');
    assert.equal(published, 0);
    assert.match(calls.open[0], /^https:\/\/wa\.me\/62812345678/);
});
