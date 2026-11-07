const test = require('node:test');
const assert = require('node:assert/strict');
const ProductionListUi = require('../../public/assets/js/production-list-reference.js');

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
}

function fakeElement(tagName, attributes = {}, children = [], text = '') {
  const values = new Map(Object.entries(attributes));
  const node = {
    nodeType: 1,
    tagName: tagName.toUpperCase(),
    children: [],
    get childNodes() { return [...(text ? [{ nodeType: 3, nodeValue: text }] : []), ...this.children]; },
    parentNode: null,
    _text: text,
    dataset: new Proxy({}, { set(_target, key, value) { values.set(`data-${String(key).replace(/[A-Z]/g, letter => `-${letter.toLowerCase()}`)}`, String(value)); return true; } }),
    get attributes() { return [...values].map(([name, value]) => ({ name, value })); },
    get textContent() { return text + this.children.map(child => child.textContent).join(''); },
    get outerHTML() {
      const attrs = [...values].map(([name, value]) => ` ${name}="${escapeHtml(value)}"`).join('');
      if (['BR'].includes(this.tagName)) return `<${this.tagName.toLowerCase()}${attrs}>`;
      return `<${this.tagName.toLowerCase()}${attrs}>${escapeHtml(text)}${this.children.map(child => child.outerHTML).join('')}</${this.tagName.toLowerCase()}>`;
    },
    getAttribute(name) { return values.has(name) ? values.get(name) : null; },
    setAttribute(name, value) { values.set(name, String(value)); },
    removeAttribute(name) { values.delete(name); },
    remove() {
      if (this.parentNode) this.parentNode.children = this.parentNode.children.filter(child => child !== this);
      this.parentNode = null;
    },
    querySelector(selector) {
      const find = parent => {
        for (const child of parent.children) {
          if (selector.startsWith('#') && child.getAttribute('id') === selector.slice(1)) return child;
          const nested = find(child);
          if (nested) return nested;
        }
        return null;
      };
      return find(this);
    },
    querySelectorAll(selector) {
      if (selector === '[id]' || selector === '[aria-labelledby]') {
        const attribute = selector.slice(1, -1);
        const matches = [];
        const visit = parent => parent.children.forEach(child => {
          if (child.getAttribute(attribute) !== null) matches.push(child);
          visit(child);
        });
        visit(this);
        return matches;
      }
      if (selector !== '.production-detail__actions a[href]') return [];
      const matches = [];
      const visit = parent => parent.children.forEach(child => {
        let ancestor = child.parentNode;
        let inActions = false;
        while (ancestor) {
          if ((ancestor.getAttribute('class') || '').split(/\s+/).includes('production-detail__actions')) inActions = true;
          ancestor = ancestor.parentNode;
        }
        if (child.tagName === 'A' && child.getAttribute('href') && inActions) matches.push(child);
        visit(child);
      });
      visit(this);
      return matches;
    },
  };
  node.children = children;
  children.forEach(child => { child.parentNode = node; });
  return node;
}

function cloneElement(node) {
  const attributes = Object.fromEntries(node.attributes.map(({ name, value }) => [name, value]));
  return fakeElement(node.tagName.toLowerCase(), attributes, node.children.map(cloneElement), node._text);
}

function createHarness({ hasDetail = true, detailNode, fetcher: fetchOverride, initialInert = false } = {}) {
  const calls = { requests: [], history: [], listeners: {}, appendedStyles: [], assigned: [], replaced: [], timers: [], cancelledTimers: [], replacedHistory: [], back: 0, focus: 0 };
  const timers = new Map();
  let timerSequence = 0;
  const setTimer = callback => { const id = ++timerSequence; timers.set(id, callback); calls.timers.push(id); return id; };
  const clearTimer = id => { timers.delete(id); calls.cancelledTimers.push(id); };
  calls.runTimers = () => { for (const [id, callback] of [...timers]) { timers.delete(id); callback(); } };
  const contentClasses = new Set();
  const content = { inert: initialInert, classList: {
    add(value) { contentClasses.add(value); },
    remove(value) { contentClasses.delete(value); },
    contains(value) { return contentClasses.has(value); },
  } };
  const previousFocus = { isConnected: true, focus() { calls.restoredFocus = true; } };
  const closeButton = { focus() { calls.focus += 1; } };
  const detail = detailNode || fakeElement('div', { class: 'production-detail' }, [
    fakeElement('h1', { id: 'production-detail-title' }, [], 'Detail Produksi #PRO42'),
    fakeElement('span', { class: 'detail-value' }, [], 'server detail'),
  ]);
  const style = { textContent: '.production-detail { display:flex; }' };
  const document = {
    activeElement: previousFocus,
    head: {
      querySelector() { return null; },
      appendChild(node) { calls.appendedStyles.push(node); },
    },
    createElement(tag) { return { tagName: tag, dataset: {}, textContent: '' }; },
    getElementById(id) { return id === 'kt_content' ? content : null; },
    querySelector(selector) {
      if (selector === '.production-detail-dialog [aria-label="Tutup detail produksi"]') return closeButton;
      return null;
    },
    addEventListener(name, callback) { calls.listeners[`document:${name}`] = callback; },
    removeEventListener(name) { delete calls.listeners[`document:${name}`]; },
  };
  class DOMParserStub {
    parseFromString() {
      const parsedDetail = cloneElement(detail);
      return {
        querySelector(selector) { return selector === '.production-detail' && hasDetail ? parsedDetail : null; },
        querySelectorAll(selector) { return selector === 'style' ? [style] : []; },
      };
    }
  }
  const window = {
    location: {
      href: 'http://127.0.0.1:8000/production?status=all',
      origin: 'http://127.0.0.1:8000',
      assign(url) { calls.assigned.push(url); },
      replace(url) { calls.replaced.push(url); },
    },
    history: {
      state: { list: 'preserved' },
      pushState(state, _title, url) { calls.history.push({ state, url }); this.state = state; window.location.href = url; },
      replaceState(state, _title, url) { calls.replacedHistory.push({ state, url }); this.state = state; window.location.href = url; },
      back() { calls.back += 1; },
    },
    addEventListener(name, callback) { calls.listeners[`window:${name}`] = callback; },
    removeEventListener(name) { delete calls.listeners[`window:${name}`]; },
  };
  const fetch = fetchOverride || (async (url, options) => {
    calls.requests.push({ url, options });
    return { ok: true, text: async () => '<html>rendered detail</html>' };
  });
  return {
    calls,
    content,
    contentClasses,
    document,
    window,
    previousFocus,
    controller: ProductionListUi.createDetailModal('/production', {
      document,
      window,
      fetch,
      DOMParser: DOMParserStub,
      requestAnimationFrame: callback => callback(),
      setTimeout: setTimer,
      clearTimeout: clearTimer,
    }),
  };
}

test('production detail opens as a server-rendered popup and pushes a reversible detail URL', async () => {
  const harness = createHarness();
  await harness.controller.openProductionDetail('/production/42/detail');

  assert.equal(harness.calls.requests.length, 1);
  assert.equal(harness.calls.requests[0].url, 'http://127.0.0.1:8000/production/42/detail');
  assert.equal(harness.calls.requests[0].options.credentials, 'same-origin');
  assert.equal(harness.calls.requests[0].options.headers['X-Requested-With'], 'XMLHttpRequest');
  assert.equal(harness.controller.productionDetailOpen, true);
  assert.match(harness.controller.productionDetailHtml, /class="production-detail"/);
  assert.match(harness.controller.productionDetailHtml, /Detail Produksi #PRO42/);
  assert.match(harness.controller.productionDetailHtml, /server detail/);
  assert.equal(harness.controller.productionDetailTitle, 'Detail Produksi #PRO42');
  assert.deepEqual(harness.calls.history[0].state, {
    list: 'preserved',
    infruityProductionDetailModal: true,
    infruityProductionDetailUrl: 'http://127.0.0.1:8000/production/42/detail',
    infruityProductionDetailEntry: 1,
  });
  assert.equal(harness.calls.appendedStyles.length, 0);
  assert.equal(harness.calls.focus, 1);
  assert.equal(harness.contentClasses.has('production-detail-open'), true);
  assert.equal(harness.content.inert, true);

  harness.controller.closeProductionDetail();
  assert.equal(harness.calls.back, 1);
  harness.window.history.state = { list: 'preserved' };
  harness.window.location.href = 'http://127.0.0.1:8000/production?status=all';
  await harness.controller.handleProductionDetailPopState({ state: null });
  assert.equal(harness.controller.productionDetailOpen, false);
  assert.equal(harness.contentClasses.has('production-detail-open'), false);
  assert.equal(harness.content.inert, false);
  assert.equal(harness.calls.restoredFocus, true);
});

test('production detail preserves full-page navigation when the fetched route is not a detail page', async () => {
  const harness = createHarness({ hasDetail: false });
  await harness.controller.openProductionDetail('/production/42/detail');

  assert.equal(harness.controller.productionDetailOpen, false);
  assert.equal(harness.calls.history.length, 1);
  assert.deepEqual(harness.calls.replaced, ['http://127.0.0.1:8000/production/42/detail']);
  assert.deepEqual(harness.calls.assigned, []);
});

test('production detail intercepts only unmodified production links and exposes the row opener', () => {
  const harness = createHarness();
  let openedUrl = null;
  harness.controller.openProductionDetail = url => { openedUrl = url; };
  harness.controller.init();

  const link = {
    href: 'http://127.0.0.1:8000/production/42/detail',
    target: '',
    hasAttribute: () => false,
  };
  const makeEvent = overrides => ({
    button: 0,
    metaKey: false,
    ctrlKey: false,
    shiftKey: false,
    altKey: false,
    prevented: false,
    target: { closest: selector => selector === 'a.production-copy' ? link : null },
    preventDefault() { this.prevented = true; },
    ...overrides,
  });
  const click = harness.calls.listeners['document:click'];
  const normalClick = makeEvent();
  click(normalClick);
  assert.equal(normalClick.prevented, true);
  assert.equal(openedUrl, link.href);
  assert.equal(typeof harness.window.openProductionDetail, 'function');

  openedUrl = null;
  const modifiedClick = makeEvent({ ctrlKey: true });
  click(modifiedClick);
  assert.equal(modifiedClick.prevented, false);
  assert.equal(openedUrl, null);
  harness.controller.destroy();
  assert.equal(harness.window.openProductionDetail, undefined);
});

test('server-rendered detail is sanitized before x-html insertion with scoped IDs and allowlisted presentation', async () => {
  const detailNode = fakeElement('div', { id: 'production-detail-modal-title', class: 'production-detail hostile-root', onclick: 'alert(1)' }, [
    fakeElement('h1', { id: 'production-detail-title' }, [], 'Detail Produksi #PRO42'),
    fakeElement('span', { class: 'detail-value hidden', onmouseover: 'alert(2)' }, [], 'Safe text'),
    fakeElement('span', { class: 'production-detail__note' }, [], '<img src=x onerror=alert(9)>'),
    fakeElement('section', { id: 'production-extra-title', 'aria-labelledby': 'production-extra-title', class: 'production-detail__card position-fixed' }, [
      fakeElement('h2', { id: 'production-ingredients-title', class: 'production-detail__section-title position-fixed', role: 'alert', 'aria-label': 'attacker' }, [], 'Ingredients'),
      fakeElement('h2', { id: 'production-ingredients-title', class: 'production-detail__section-title' }, [], 'Duplicate ID'),
    ]),
    fakeElement('p', { 'aria-labelledby': 'production-ingredients-title' }, [], 'Ambiguous label'),
    fakeElement('img', { src: 'data:text/html,bad', onerror: 'alert(3)' }),
    fakeElement('script', {}, [], 'window.pwned = true'),
    fakeElement('div', { class: 'production-detail__actions' }, [
      fakeElement('a', { class: 'btn', href: 'javascript:alert(4)', onclick: 'alert(4)' }, [], 'Unsafe action'),
      fakeElement('a', { class: 'btn', href: '//evil.example/production' }, [], 'Protocol-relative external action'),
      fakeElement('a', { class: 'btn', href: '/production' }, [], 'Kembali'),
    ]),
  ]);
  const harness = createHarness({ detailNode });
  await harness.controller.openProductionDetail('/production/42/detail');

  const html = harness.controller.productionDetailHtml;
  assert.equal(html.includes('<img'), false);
  assert.equal(html.includes('<script'), false);
  assert.equal(html.includes('onmouseover='), false);
  assert.equal(html.includes('onclick='), false);
  assert.equal(html.includes('javascript:'), false);
  assert.equal(html.includes('evil.example'), false);
  assert.equal(html.includes('hostile-root'), false);
  assert.equal(html.includes('position-fixed'), false);
  assert.equal(html.includes('hidden'), false);
  assert.equal(html.includes('role='), false);
  assert.equal(html.includes('aria-label="attacker"'), false);
  assert.equal(html.includes('&lt;img src=x onerror=alert(9)&gt;'), true);
  assert.match(html, /Safe text/);
  assert.match(html, /class="production-detail"/);
  assert.match(html, /href="http:\/\/127\.0\.0\.1:8000\/production"/);
  assert.match(html, /data-production-detail-back="true"/);
  assert.match(html, /id="production-detail-modal-fragment-production-detail-title"/);
  assert.match(html, /id="production-detail-modal-fragment-production-extra-title"/);
  assert.match(html, /aria-labelledby="production-detail-modal-fragment-production-extra-title"/);
  assert.equal(html.includes('production-ingredients-title'), false);
  assert.doesNotMatch(html, /aria-labelledby="production-detail-modal-title"/);
});

test('network errors preserve the full-page detail fallback', async () => {
  const harness = createHarness({ fetcher: async () => { throw new Error('network unavailable'); } });
  await harness.controller.openProductionDetail('/production/42/detail');

  assert.equal(harness.controller.productionDetailOpen, false);
  assert.equal(harness.calls.history.length, 1);
  assert.deepEqual(harness.calls.replaced, ['http://127.0.0.1:8000/production/42/detail']);
  assert.deepEqual(harness.calls.assigned, []);
});

test('late detail responses cannot replace the most recently opened production', async () => {
  const pending = [];
  const harness = createHarness({ fetcher: url => new Promise(resolve => pending.push({ url, resolve })) });
  const first = harness.controller.openProductionDetail('/production/41/detail');
  const second = harness.controller.openProductionDetail('/production/42/detail');
  pending[0].resolve({ ok: true, text: async () => '<html>first</html>' });
  pending[1].resolve({ ok: true, text: async () => '<html>second</html>' });
  await Promise.all([first, second]);

  assert.equal(harness.controller.productionDetailOpen, true);
  assert.equal(harness.calls.history.length, 2);
  assert.equal(harness.calls.history[0].url, 'http://127.0.0.1:8000/production/41/detail');
  assert.equal(harness.calls.history[1].url, 'http://127.0.0.1:8000/production/42/detail');
});

test('a stale modal marker on the prior list history entry does not reopen a detail popup', async () => {
  const harness = createHarness();
  const staleState = {
    infruityProductionDetailModal: true,
    infruityProductionDetailUrl: 'http://127.0.0.1:8000/production/42/detail',
  };
  await harness.controller.handleProductionDetailPopState({ state: staleState });

  assert.equal(harness.controller.productionDetailOpen, false);
  assert.equal(harness.calls.requests.length, 0);
});

test('closing a loading detail invalidates its pending response before browser history changes', async () => {
  let resolveFetch;
  const harness = createHarness({ fetcher: () => new Promise(resolve => { resolveFetch = resolve; }) });
  const opening = harness.controller.openProductionDetail('/production/42/detail');

  assert.equal(harness.controller.productionDetailOpen, true);
  assert.equal(harness.controller.productionDetailLoading, true);
  assert.equal(harness.calls.history.length, 1);
  harness.controller.closeProductionDetail();
  assert.equal(harness.calls.back, 1);

  const listState = { list: 'preserved' };
  harness.window.history.state = listState;
  harness.window.location.href = 'http://127.0.0.1:8000/production?status=all';
  await harness.controller.handleProductionDetailPopState({ state: listState });
  resolveFetch({ ok: true, text: async () => '<html>late detail</html>' });
  await opening;

  assert.equal(harness.controller.productionDetailOpen, false);
  assert.equal(harness.controller.productionDetailLoading, false);
  assert.equal(harness.controller.productionDetailHtml, '');
  assert.deepEqual(harness.calls.assigned, []);
  assert.deepEqual(harness.calls.replaced, []);
});

test('close falls back to the prior list entry when history back does not emit popstate', async () => {
  const harness = createHarness();
  const listUrl = 'http://127.0.0.1:8000/production?status=all';
  await harness.controller.openProductionDetail('/production/42/detail');
  harness.controller.closeProductionDetail();
  assert.equal(harness.controller.productionDetailOpen, true);

  harness.calls.runTimers();

  assert.equal(harness.controller.productionDetailOpen, false);
  assert.deepEqual(harness.calls.replacedHistory, [{ state: { list: 'preserved' }, url: listUrl }]);
  assert.equal(harness.window.location.href, listUrl);
  assert.equal(harness.content.inert, false);
  assert.equal(harness.contentClasses.has('production-detail-open'), false);
});
test('destroying an open modal restores list scrolling, original inert state, and focus', async () => {
  const harness = createHarness({ initialInert: true });
  await harness.controller.openProductionDetail('/production/42/detail');
  harness.controller.destroy();

  assert.equal(harness.controller.productionDetailOpen, false);
  assert.equal(harness.content.inert, true);
  assert.equal(harness.contentClasses.has('production-detail-open'), false);
  assert.equal(harness.calls.restoredFocus, true);
});

test('browser back and forward switches between multiple opened detail URLs without duplicate entries', async () => {
  const harness = createHarness();
  await harness.controller.openProductionDetail('/production/41/detail');
  const firstState = harness.calls.history[0].state;
  await harness.controller.openProductionDetail('/production/42/detail');
  const listUrl = 'http://127.0.0.1:8000/production?status=all';

  harness.window.location.href = 'http://127.0.0.1:8000/production/41/detail';
  await harness.controller.handleProductionDetailPopState({ state: firstState });
  assert.equal(harness.controller.productionDetailOpen, true);
  assert.equal(harness.calls.history.length, 2);

  harness.window.location.href = listUrl;
  await harness.controller.handleProductionDetailPopState({ state: { list: 'preserved' } });
  assert.equal(harness.controller.productionDetailOpen, false);
  assert.equal(harness.contentClasses.has('production-detail-open'), false);
});

test('production detail forward navigation reopens the modal without pushing duplicate history', async () => {
  const harness = createHarness();
  harness.window.location.href = 'http://127.0.0.1:8000/production/42/detail';
  const state = {
    infruityProductionDetailModal: true,
    infruityProductionDetailUrl: 'http://127.0.0.1:8000/production/42/detail',
  };
  await harness.controller.handleProductionDetailPopState({ state });

  assert.equal(harness.controller.productionDetailOpen, true);
  assert.equal(harness.calls.requests.length, 1);
  assert.deepEqual(harness.calls.history, []);
});
