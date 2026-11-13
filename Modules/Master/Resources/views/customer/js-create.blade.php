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

    window.initCustomerForms = (root = document) => {
        const pickers = Object.fromEntries([...root.querySelectorAll('[data-region-picker]')].map(node => [node.dataset.regionPicker, node]));

        // A level stays locked until the level above it has a chosen value (province -> city -> district -> village).
        function syncLocks() {
            for (const [key, parent] of Object.entries(dependencies)) {
                const node = pickers[key];
                if (!node) continue;
                const locked = !pickers[parent]?.querySelector('input[type="hidden"]').value;
                const input = node.querySelector('input[role="combobox"]');
                input.disabled = locked;
                // like the master drawer: a locked level keeps what it shows (reset() already emptied it when a parent changed)
                if (locked) node.querySelector('[role="listbox"]').hidden = true;
            }
        }

        function reset(key) {
            const node = pickers[key];
            if (!node) return;
            node.querySelector('input[type="hidden"]').value = '';
            node.querySelector('input[role="combobox"]').value = '';
            node.querySelector('[role="listbox"]').hidden = true;
            syncLocks();
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
                    const selected = String(node.querySelector('input[type="hidden"]').value) === String(row.id);
                    option.className = 'w-full flex items-center gap-3 px-3 py-2.5 border-b border-gray-100 text-left hover:bg-gray-50 transition-colors last:border-b-0';
                    option.setAttribute('aria-selected', selected ? 'true' : 'false');
                    const circle = document.createElement('span');
                    circle.className = 'w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors ' + (selected ? 'border-emerald-500' : 'border-gray-300');
                    const dot = document.createElement('span');
                    dot.className = 'w-2 h-2 rounded-full bg-emerald-500 transition-transform duration-200 ' + (selected ? 'scale-100' : 'scale-0');
                    circle.append(dot);
                    const label = document.createElement('span');
                    label.className = 'text-xs ' + (selected ? 'font-bold text-gray-900' : 'font-medium text-gray-700');
                    label.textContent = row.name;
                    option.append(circle, label);
                    option.addEventListener('mousedown', event => event.preventDefault());
                    option.addEventListener('click', () => {
                        node.querySelector('input[type="hidden"]').value = row.id;
                        input.value = row.name;
                        list.hidden = true;
                        input.setAttribute('aria-expanded', 'false');
                        (children[key] || []).forEach(reset);
                        syncLocks();
                    });
                    list.append(option);
                }
                if (!rows.length) {
                    const empty = document.createElement('div');
                    empty.className = 'px-4 py-3 text-xs text-gray-400';
                    empty.textContent = 'Wilayah tidak ditemukan';
                    list.append(empty);
                }
                const rect = node.getBoundingClientRect();
                const openAbove = window.innerHeight - rect.bottom < 230 && rect.top > 230;
                list.classList.toggle('bottom-full', openAbove);
                list.classList.toggle('mb-1', openAbove);
                list.classList.toggle('mt-1', !openAbove);
                list.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            } catch (error) { list.hidden = true; }
        }

        for (const [key, node] of Object.entries(pickers)) {
            if (node.dataset.regionPickerInitialized === 'true') continue;
            node.dataset.regionPickerInitialized = 'true';
            const input = node.querySelector('input[role="combobox"]');
            const hidden = node.querySelector('input[type="hidden"]');
            let timer;
            input.addEventListener('focus', () => search(key));
            input.addEventListener('input', () => { hidden.value = ''; (children[key] || []).forEach(reset); syncLocks(); clearTimeout(timer); timer = setTimeout(() => search(key), 200); });
            input.addEventListener('blur', () => setTimeout(() => { node.querySelector('[role="listbox"]').hidden = true; input.setAttribute('aria-expanded', 'false'); }, 150));
            input.addEventListener('keydown', event => { if (event.key === 'Escape') { node.querySelector('[role="listbox"]').hidden = true; input.setAttribute('aria-expanded', 'false'); } });
        }

        syncLocks();

        for (const form of root.querySelectorAll('form#customer-form')) {
            if (form.dataset.submitBound === 'true') continue;
            form.dataset.submitBound = 'true';
            form.addEventListener('submit', event => {
                const button = event.currentTarget.querySelector('[data-submit]');
                if (button) { button.disabled = true; button.textContent = 'Menyimpan...'; }
            });
        }
    };

    const initialize = () => window.initCustomerForms(document);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();

    const observer = new MutationObserver(records => {
        const hasNewCustomerForm = records.some(record => [...record.addedNodes].some(node => node.nodeType === Node.ELEMENT_NODE && (node.matches('[data-region-picker], form#customer-form') || node.querySelector('[data-region-picker], form#customer-form'))));
        if (hasNewCustomerForm) initialize();
    });
    if (document.body) observer.observe(document.body, { childList: true, subtree: true });
})();
</script>
@endpush
