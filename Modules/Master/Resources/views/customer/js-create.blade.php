@push('scripts')
<script>
(() => {
    const endpoints = {
        province: @js(route('ajax.province')),
        city: @js(route('ajax.city')),
        district: @js(route('ajax.district')),
        village: @js(route('ajax.village')),
    };
    const dependencies = { city: 'province', district: 'city', village: 'district' };
    const children = { province: ['city', 'district', 'village'], city: ['district', 'village'], district: ['village'] };
    const pickers = Object.fromEntries([...document.querySelectorAll('[data-region-picker]')].map(node => [node.dataset.regionPicker, node]));

    function reset(key) {
        const node = pickers[key];
        if (!node) return;
        node.querySelector('input[type="hidden"]').value = '';
        node.querySelector('input[role="combobox"]').value = '';
        node.querySelector('[role="listbox"]').hidden = true;
    }

    async function search(key) {
        const node = pickers[key];
        if (!node) return;
        const parent = dependencies[key];
        const parentId = parent ? pickers[parent]?.querySelector('input[type="hidden"]').value : '';
        const list = node.querySelector('[role="listbox"]');
        if (parent && !parentId) { list.hidden = true; return; }
        const input = node.querySelector('input[role="combobox"]');
        const params = new URLSearchParams({ search: input.value.trim() });
        if (key === 'city') params.set('province_id', parentId);
        if (key === 'district') params.set('city_id', parentId);
        if (key === 'village') params.set('district_id', parentId);
        const query = input.value;
        try {
            const response = await fetch(`${endpoints[key]}?${params}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat wilayah');
            const rows = await response.json();
            if (input.value !== query) return;
            list.replaceChildren();
            for (const row of rows) {
                const option = document.createElement('button');
                option.type = 'button';
                option.setAttribute('role', 'option');
                option.className = 'w-full px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-emerald-50 focus:bg-emerald-50';
                option.textContent = row.name;
                option.addEventListener('mousedown', event => event.preventDefault());
                option.addEventListener('click', () => {
                    node.querySelector('input[type="hidden"]').value = row.id;
                    input.value = row.name;
                    list.hidden = true;
                    input.setAttribute('aria-expanded', 'false');
                    (children[key] || []).forEach(reset);
                });
                list.append(option);
            }
            if (!rows.length) {
                const empty = document.createElement('div');
                empty.className = 'px-4 py-3 text-xs text-gray-400';
                empty.textContent = 'Wilayah tidak ditemukan';
                list.append(empty);
            }
            const openAbove = window.innerHeight - node.getBoundingClientRect().bottom < 230 && node.getBoundingClientRect().top > 230;
            list.classList.toggle('bottom-full', openAbove);
            list.classList.toggle('mb-1', openAbove);
            list.classList.toggle('mt-1', !openAbove);
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        } catch (error) { list.hidden = true; }
    }

    for (const [key, node] of Object.entries(pickers)) {
        const input = node.querySelector('input[role="combobox"]');
        const hidden = node.querySelector('input[type="hidden"]');
        let timer;
        input.addEventListener('focus', () => search(key));
        input.addEventListener('input', () => { hidden.value = ''; (children[key] || []).forEach(reset); clearTimeout(timer); timer = setTimeout(() => search(key), 200); });
        input.addEventListener('blur', () => setTimeout(() => { node.querySelector('[role="listbox"]').hidden = true; input.setAttribute('aria-expanded', 'false'); }, 150));
        input.addEventListener('keydown', event => { if (event.key === 'Escape') { node.querySelector('[role="listbox"]').hidden = true; input.setAttribute('aria-expanded', 'false'); } });
    }
    document.getElementById('customer-form')?.addEventListener('submit', event => {
        const button = event.currentTarget.querySelector('[data-submit]');
        if (button) { button.disabled = true; button.textContent = 'Menyimpan...'; }
    });
})();
</script>
@endpush
