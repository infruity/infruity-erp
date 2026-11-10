const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const jsPath = path.join(__dirname, '../../Modules/Transaction/Resources/views/production/js-create.blade.php');
const source = fs.readFileSync(jsPath, 'utf8');

function extractMethod(name) {
  const match = new RegExp(`^\\s{12}(?:async\\s+)?${name}\\([^\\n]*\\) \\{`, 'm').exec(source);
  assert.ok(match, `expected ${name} method in productionApp`);

  const start = match.index;
  let depth = 0;
  let end = -1;
  for (let index = source.indexOf('{', start); index < source.length; index += 1) {
    if (source[index] === '{') depth += 1;
    if (source[index] === '}') {
      depth -= 1;
      if (depth === 0) {
        end = index + 1;
        break;
      }
    }
  }
  assert.notEqual(end, -1, `expected ${name} method to be balanced`);
  return new Function(`return ({${source.slice(start, end)}}).${name}`)();
}

function extractFunction(name) {
  const match = new RegExp(`^\\s*async function ${name}\\([^\\n]*\\) \\{`, 'm').exec(source);
  assert.ok(match, `expected async ${name} function`);

  const start = match.index;
  let depth = 0;
  let end = -1;
  for (let index = source.indexOf('{', start); index < source.length; index += 1) {
    if (source[index] === '{') depth += 1;
    if (source[index] === '}') {
      depth -= 1;
      if (depth === 0) {
        end = index + 1;
        break;
      }
    }
  }
  assert.notEqual(end, -1, `expected ${name} function to be balanced`);
  return source.slice(start, end)
    .replaceAll("{{ route('products.get-receipt') }}", '/products/get-receipt')
    .replaceAll("{{ url('production/get-recipe-data') }}", '/production/get-recipe-data');
}

test('create inline quantity updates cost and recipe scaling without changing the submitted ingredient shape', () => {
  const updateIngredientInline = extractMethod('updateIngredientInline');
  const ingredient = { id: 7, name: 'Flour', quantity: 2, hpp: 500, total: 1000 };
  const context = {
    ingredients: [ingredient],
    productionQuantity: 4,
    baseRecipeIngredients: [{ id: 7, base_quantity: 0.5 }],
    parseInlineQuantity: extractMethod('parseInlineQuantity'),
  };

  updateIngredientInline.call(context, 0, '3,5');

  assert.equal(ingredient.quantity, 3.5);
  assert.equal(ingredient.total, 1750);
  assert.equal(context.baseRecipeIngredients[0].base_quantity, 0.875);
  assert.deepEqual(Object.keys(ingredient).sort(), ['hpp', 'id', 'name', 'quantity', 'total']);
});

test('create inline quantity ignores transient invalid input without corrupting existing totals', () => {
  const updateIngredientInline = extractMethod('updateIngredientInline');
  const ingredient = { id: 7, name: 'Flour', quantity: 2, hpp: 500, total: 1000 };
  const context = { ingredients: [ingredient], productionQuantity: 4, baseRecipeIngredients: [], parseInlineQuantity: extractMethod('parseInlineQuantity') };

  updateIngredientInline.call(context, 0, '');

  assert.equal(ingredient.quantity, 2);
  assert.equal(ingredient.total, 1000);
  assert.deepEqual(context.baseRecipeIngredients, []);
});

test('create inline quantity draft survives output scaling when temporarily empty', () => {
  const updateIngredientInline = extractMethod('updateIngredientInline');
  const updateIngredientsFromRecipe = extractMethod('updateIngredientsFromRecipe');
  const normalizeIngredientInline = extractMethod('normalizeIngredientInline');
  const context = {
    ingredients: [{ id: 7, name: 'Flour', quantity: 3, hpp: 500, total: 1500 }],
    productionQuantity: 4,
    baseRecipeIngredients: [{ id: 7, name: 'Flour', base_quantity: 0.75, hpp: 500, unit: 'kg' }],
    inlineQuantityDrafts: {},
    parseInlineQuantity: extractMethod('parseInlineQuantity'),
    updateIngredientInline,
  };
  const input = { value: '' };

  updateIngredientInline.call(context, 0, '');
  context.productionQuantity = 8;
  updateIngredientsFromRecipe.call(context);

  assert.equal(context.inlineQuantityDrafts[7], '');
  assert.equal(context.ingredients[0].quantity, 6);
  assert.equal(context.ingredients[0].total, 3000);

  normalizeIngredientInline.call(context, 0, input);
  assert.equal(input.value, '6');
  assert.equal(context.inlineQuantityDrafts[7], undefined);
});

test('create sub-unit inline quantity uses the existing proportional output scaling', () => {
  const updateIngredientInline = extractMethod('updateIngredientInline');
  const updateIngredientsFromRecipe = extractMethod('updateIngredientsFromRecipe');
  const context = {
    ingredients: [{ id: 7, name: 'Flour', quantity: 0.25, hpp: 500, total: 125 }],
    productionQuantity: 4,
    baseRecipeIngredients: [{ id: 7, name: 'Flour', base_quantity: 0.0625, hpp: 500, unit: 'kg' }],
    inlineQuantityDrafts: {},
    parseInlineQuantity: extractMethod('parseInlineQuantity'),
  };

  updateIngredientInline.call(context, 0, '0.5');
  context.productionQuantity = 8;
  updateIngredientsFromRecipe.call(context);

  assert.equal(context.ingredients[0].quantity, 1);
  assert.equal(context.ingredients[0].total, 500);
});

test('create remove clears an unfinished quantity draft before the ingredient can be re-added', () => {
  const removeIngredient = extractMethod('removeIngredient');
  const context = {
    ingredients: [{ id: 7, name: 'Flour', quantity: 2, hpp: 500, total: 1000 }],
    baseRecipeIngredients: [{ id: 7, name: 'Flour', base_quantity: 0.5, hpp: 500, unit: 'kg' }],
    inlineQuantityDrafts: { 7: '' },
    showNotification() {},
  };

  removeIngredient.call(context, 0);
  context.ingredients.push({ id: 7, name: 'Flour', quantity: 4, hpp: 500, total: 2000 });

  assert.equal(context.inlineQuantityDrafts[7], undefined);
  assert.equal(context.inlineQuantityDrafts[7] ?? context.ingredients[0].quantity, 4);
});

test('create inline quantity rejects partial numeric input on input and blur', () => {
  const updateIngredientInline = extractMethod('updateIngredientInline');
  const normalizeIngredientInline = extractMethod('normalizeIngredientInline');
  const context = {
    ingredients: [{ id: 7, name: 'Flour', quantity: 2, hpp: 500, total: 1000 }],
    productionQuantity: 4,
    baseRecipeIngredients: [{ id: 7, name: 'Flour', base_quantity: 0.5, hpp: 500, unit: 'kg' }],
    inlineQuantityDrafts: {},
    parseInlineQuantity: extractMethod('parseInlineQuantity'),
    updateIngredientInline,
  };
  const input = { value: '3abc' };

  updateIngredientInline.call(context, 0, input.value);
  assert.equal(context.ingredients[0].quantity, 2);
  assert.equal(context.ingredients[0].total, 1000);
  assert.equal(context.baseRecipeIngredients[0].base_quantity, 0.5);

  normalizeIngredientInline.call(context, 0, input);
  assert.equal(input.value, '2');
  assert.equal(context.inlineQuantityDrafts[7], undefined);
});

test('create product picker uses the same name-based fruit emoji as product avatars', () => {
  const referenceProductEmoji = extractMethod('referenceProductEmoji');
  const context = {};

  assert.equal(referenceProductEmoji.call(context, 'Jus Stroberi'), '🍓');
  assert.equal(referenceProductEmoji.call(context, 'Jus Alpukat'), '🥑');
  assert.equal(referenceProductEmoji.call(context, 'Jus Mangga Manalagi'), '🥭');
  assert.equal(referenceProductEmoji.call(context, 'Produk Olahan'), '📦');
});

test('create product picker loads searchable options from the existing endpoint contract', async (t) => {
  const loadReferenceProducts = extractMethod('loadReferenceProducts');
  const originalDocument = global.document;
  const originalWindow = global.window;
  const originalFetch = global.fetch;
  const requestUrls = [];
  global.document = { querySelector: () => ({ dataset: { referenceProductUrl: '/products/get-receipt' } }) };
  global.window = { location: { href: 'http://127.0.0.1:8000/production/create' } };
  global.fetch = async (url, options) => {
    requestUrls.push({ url: new URL(url), options });
    return {
      ok: true,
      json: async () => [
        { product_id: 42, products: { name: 'Jus Alpukat' } },
        { product_id: 99, products: null },
      ],
    };
  };
  t.after(() => {
    global.document = originalDocument;
    global.window = originalWindow;
    global.fetch = originalFetch;
  });

  const context = {
    referenceProductRequest: 0,
    referenceProductSearch: 'Alpukat',
    referenceProducts: [],
    referenceProductsLoading: false,
    referenceProductError: '',
  };
  await loadReferenceProducts.call(context);

  assert.equal(requestUrls.length, 1);
  assert.equal(requestUrls[0].url.pathname, '/products/get-receipt');
  assert.equal(requestUrls[0].url.searchParams.get('search'), 'Alpukat');
  assert.equal(requestUrls[0].options.headers.Accept, 'application/json');
  assert.deepEqual(context.referenceProducts, [{ id: 42, text: 'Jus Alpukat', subtitle: '' }]);
  assert.equal(context.referenceProductsLoading, false);
  assert.equal(context.referenceProductError, '');
});

test('create product picker writes the selected id through the existing select2 event path', () => {
  const selectReferenceProduct = extractMethod('selectReferenceProduct');
  const originalDocument = global.document;
  const originalWindow = global.window;
  const originalOption = global.Option;
  const events = [];
  const select = {
    options: [{ value: '' }],
    value: '',
    add(option) { this.options.push(option); },
  };
  global.document = { getElementById: id => id === 'product_id' ? select : null };
  global.Option = function (text, value, defaultSelected, selected) {
    return { text, value, defaultSelected, selected };
  };
  global.window = {
    jQuery: element => ({
      val(value) { element.value = value; return this; },
      data() { return { select2: true }; },
      trigger(event) { events.push(event); return this; },
    }),
  };

  const context = {
    referenceProductId: '17',
    referenceRecipeRequest: 0,
    referenceSelectedProductName: 'Produk Lama',
    ingredients: [{ id: 7, name: 'Bahan Lama', quantity: 2, hpp: 500, total: 1000 }],
    baseRecipeIngredients: [{ id: 7, name: 'Bahan Lama', base_quantity: 0.5, hpp: 500, unit: 'kg' }],
    productionQuantity: 3,
    sellPrice: 8400,
    inlineQuantityDrafts: { 7: '' },
    closeReferenceProductPicker() { this.closed = true; },
  };
  selectReferenceProduct.call(context, { id: 42, text: 'Jus Alpukat' });
  global.document = originalDocument;
  global.window = originalWindow;
  global.Option = originalOption;

  assert.equal(select.value, '42');
  assert.equal(select.options[1].text, 'Jus Alpukat');
  assert.equal(context.referenceProductId, '42');
  assert.equal(context.referenceRecipeRequest, 1);
  assert.equal(context.referenceSelectedProductName, 'Jus Alpukat');
  assert.deepEqual(context.inlineQuantityDrafts, {});
  assert.deepEqual(context.ingredients, []);
  assert.deepEqual(context.baseRecipeIngredients, []);
  assert.equal(context.productionQuantity, 1);
  assert.equal(context.sellPrice, 0);
  assert.equal(context.closed, true);
  assert.equal(events[0], 'change');
  assert.equal(events[1].type, 'select2:select');
  assert.deepEqual(events[1].params.data, { id: 42, text: 'Jus Alpukat' });
});

test('late recipe responses cannot restore a previous product after a no-recipe, empty-recipe, error, or successful switch', async () => {
  const loaderSource = extractFunction('loadRecipeIngredients');

  for (const outcome of ['no-receipt', 'empty', 'error', 'success']) {
    const app = {
      referenceProductId: '17',
      referenceRecipeRequest: 1,
      isCurrentReferenceRecipeRequest: extractMethod('isCurrentReferenceRecipeRequest'),
      ingredients: [],
      baseRecipeIngredients: [],
      inlineQuantityDrafts: {},
      productionQuantity: 1,
      sellPrice: 0,
      setLoadingIngredients(value) { this.isLoadingIngredients = value; },
    };
    const componentElement = { _x_dataStack: [app] };
    const receipts = outcome === 'no-receipt'
      ? [{ id: 117, product_id: 17 }]
      : [{ id: 117, product_id: 17 }, { id: 142, product_id: 42 }];
    const pendingOldRecipe = new Map();
    const documentStub = {
      querySelector(selector) {
        if (selector === '.production-create-reference') return {};
        if (selector === '[x-data*="productionApp"]' || selector === '[x-data]') return componentElement;
        return null;
      },
      getElementById() { return null; },
    };
    const fetchStub = async url => {
      const pathname = new URL(url, 'http://fixture.test').pathname;
      if (pathname === '/products/get-receipt') {
        return { ok: true, json: async () => receipts };
      }
      if (pathname.endsWith('/117')) {
        return {
          ok: true,
          json: () => new Promise(resolve => pendingOldRecipe.set('117', resolve)),
        };
      }
      if (pathname.endsWith('/142')) {
        if (outcome === 'error') return { ok: false, status: 500 };
        return {
          ok: true,
          json: async () => outcome === 'success'
            ? { success: true, ingredients: [{ id: 22, name: 'Bahan Baru', quantity: 1, hpp: 300, unit: 'kg' }] }
            : { success: true, ingredients: [] },
        };
      }
      throw new Error(`Unexpected fixture request: ${pathname}`);
    };
    const loadRecipeIngredients = new Function(
      'document', 'window', 'fetch', 'showLoadingNotification', 'showInfoNotification',
      'showSuccessNotification', 'showErrorNotification', 'loadRecipeIngredientsWithoutAlpine',
      `return ${loaderSource};`
    )(
      documentStub,
      { Alpine: null },
      fetchStub,
      () => {},
      () => {},
      () => {},
      () => {},
      async () => { throw new Error('Alpine fixture should be available'); },
    );

    const oldLoad = loadRecipeIngredients('17');
    for (let attempt = 0; attempt < 20 && !pendingOldRecipe.has('117'); attempt += 1) {
      await new Promise(resolve => setImmediate(resolve));
    }
    assert.ok(pendingOldRecipe.has('117'), `old recipe request should be pending for ${outcome}`);

    app.referenceProductId = '42';
    app.referenceRecipeRequest = 2;
    app.ingredients = [];
    app.baseRecipeIngredients = [];
    app.inlineQuantityDrafts = {};
    app.productionQuantity = 1;
    app.sellPrice = 0;
    const newLoad = loadRecipeIngredients('42');
    await newLoad;

    pendingOldRecipe.get('117')({
      success: true,
      ingredients: [{ id: 11, name: 'Bahan Lama', quantity: 2, hpp: 100, unit: 'kg' }],
      yield_quantity: 2,
    });
    await oldLoad;

    if (outcome === 'success') {
      assert.deepEqual(app.ingredients.map(ingredient => ingredient.id), [22]);
      assert.deepEqual(app.baseRecipeIngredients.map(ingredient => ingredient.id), [22]);
    } else {
      assert.deepEqual(app.ingredients, [], `old recipe must not be restored for ${outcome}`);
      assert.deepEqual(app.baseRecipeIngredients, [], `old recipe base must not be restored for ${outcome}`);
    }
    assert.equal(app.referenceProductId, '42');
    assert.equal(app.isLoadingIngredients, false);
  }
});

test('an older recipe response resolving before the active response cannot replace ingredients or clear loading', async () => {
  const loaderSource = extractFunction('loadRecipeIngredients');
  const app = {
    referenceProductId: '17',
    referenceRecipeRequest: 1,
    isCurrentReferenceRecipeRequest: extractMethod('isCurrentReferenceRecipeRequest'),
    ingredients: [],
    baseRecipeIngredients: [],
    inlineQuantityDrafts: {},
    productionQuantity: 1,
    sellPrice: 0,
    setLoadingIngredients(value) { this.isLoadingIngredients = value; },
  };
  const componentElement = { _x_dataStack: [app] };
  const pendingRecipes = new Map();
  const documentStub = {
    querySelector(selector) {
      if (selector === '.production-create-reference') return {};
      if (selector === '[x-data*="productionApp"]' || selector === '[x-data]') return componentElement;
      return null;
    },
    getElementById() { return null; },
  };
  const fetchStub = async url => {
    const pathname = new URL(url, 'http://fixture.test').pathname;
    if (pathname === '/products/get-receipt') {
      return { ok: true, json: async () => [{ id: 117, product_id: 17 }, { id: 142, product_id: 42 }] };
    }
    const receiptId = pathname.endsWith('/117') ? '117' : pathname.endsWith('/142') ? '142' : null;
    if (!receiptId) throw new Error(`Unexpected fixture request: ${pathname}`);
    return { ok: true, json: () => new Promise(resolve => pendingRecipes.set(receiptId, resolve)) };
  };
  const loadRecipeIngredients = new Function(
    'document', 'window', 'fetch', 'showLoadingNotification', 'showInfoNotification',
    'showSuccessNotification', 'showErrorNotification', 'loadRecipeIngredientsWithoutAlpine',
    `return ${loaderSource};`
  )(
    documentStub,
    { Alpine: null },
    fetchStub,
    () => {},
    () => {},
    () => {},
    () => {},
    async () => { throw new Error('Alpine fixture should be available'); },
  );

  const oldLoad = loadRecipeIngredients('17');
  for (let attempt = 0; attempt < 20 && !pendingRecipes.has('117'); attempt += 1) {
    await new Promise(resolve => setImmediate(resolve));
  }
  app.referenceProductId = '42';
  app.referenceRecipeRequest = 2;
  const activeLoad = loadRecipeIngredients('42');
  for (let attempt = 0; attempt < 20 && !pendingRecipes.has('142'); attempt += 1) {
    await new Promise(resolve => setImmediate(resolve));
  }
  assert.ok(pendingRecipes.has('117'));
  assert.ok(pendingRecipes.has('142'));

  pendingRecipes.get('117')({
    success: true,
    ingredients: [{ id: 11, name: 'Bahan Lama', quantity: 2, hpp: 100, unit: 'kg' }],
  });
  await oldLoad;
  assert.deepEqual(app.ingredients, []);
  assert.equal(app.isLoadingIngredients, true);

  pendingRecipes.get('142')({
    success: true,
    ingredients: [{ id: 22, name: 'Bahan Baru', quantity: 1, hpp: 300, unit: 'kg' }],
  });
  await activeLoad;
  assert.deepEqual(app.ingredients.map(ingredient => ingredient.id), [22]);
  assert.equal(app.isLoadingIngredients, false);
});

test('create product picker loads the recipe when the select2 handler is not ready yet', (t) => {
  const selectReferenceProduct = extractMethod('selectReferenceProduct');
  const originalDocument = global.document;
  const originalWindow = global.window;
  const originalOption = global.Option;
  const events = [];
  let loadedRecipeId = null;
  const select = {
    options: [{ value: '' }],
    value: '',
    add(option) { this.options.push(option); },
  };
  global.document = { getElementById: id => id === 'product_id' ? select : null };
  global.Option = function (text, value, defaultSelected, selected) {
    return { text, value, defaultSelected, selected };
  };
  global.window = {
    loadRecipeIngredients: id => { loadedRecipeId = id; },
    jQuery: element => ({
      val(value) { element.value = value; return this; },
      data() { return undefined; },
      trigger(event) { events.push(event); return this; },
    }),
  };
  t.after(() => {
    global.document = originalDocument;
    global.window = originalWindow;
    global.Option = originalOption;
  });

  const context = {
    closeReferenceProductPicker() { this.closed = true; },
  };
  selectReferenceProduct.call(context, { id: 42, text: 'Jus Alpukat' });

  assert.equal(select.value, '42');
  assert.equal(loadedRecipeId, 42);
  assert.equal(context.closed, true);
  assert.deepEqual(events, ['change']);
});
