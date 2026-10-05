@push('scripts')
<script>
(() => {
    const endpoints = { department: @js(route('ajax.department')), position: @js(route('ajax.position')) };
    const pickers = Object.fromEntries([...document.querySelectorAll('[data-staff-picker]')].map(node => [node.dataset.staffPicker, node]));
    async function search(key) {
        const node = pickers[key];
        if (!node) return;
        const input = node.querySelector('input[role="combobox"]');
        const list = node.querySelector('[role="listbox"]');
        const departmentId = pickers.department.querySelector('input[type="hidden"]').value;
        if (key === 'position' && !departmentId) { list.hidden = true; return; }
        const params = new URLSearchParams({ search: input.value.trim() });
        if (key === 'position') params.set('department_id', departmentId);
        const query = input.value;
        try {
            const response = await fetch(`${endpoints[key]}?${params}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat data');
            let rows = await response.json();
            if (input.value !== query) return;
            rows = rows.filter(row => row.name.toLocaleLowerCase('id').includes(query.toLocaleLowerCase('id')));
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
                    if (key === 'department') {
                        pickers.position.querySelector('input[type="hidden"]').value = '';
                        pickers.position.querySelector('input[role="combobox"]').value = '';
                    }
                });
                list.append(option);
            }
            if (!rows.length) { const empty = document.createElement('div'); empty.className = 'px-4 py-3 text-xs text-gray-400'; empty.textContent = 'Data tidak ditemukan'; list.append(empty); }
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
        input.addEventListener('input', () => { hidden.value = ''; if (key === 'department') { pickers.position.querySelector('input[type="hidden"]').value = ''; pickers.position.querySelector('input[role="combobox"]').value = ''; } clearTimeout(timer); timer = setTimeout(() => search(key), 200); });
        input.addEventListener('blur', () => setTimeout(() => { node.querySelector('[role="listbox"]').hidden = true; input.setAttribute('aria-expanded', 'false'); }, 150));
        input.addEventListener('keydown', event => { if (event.key === 'Escape') { node.querySelector('[role="listbox"]').hidden = true; input.setAttribute('aria-expanded', 'false'); } });
    }
    document.getElementById('avatar')?.addEventListener('change', event => {
        const file = event.currentTarget.files?.[0];
        if (!file) return;
        const preview = document.getElementById('staff-avatar-preview');
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        document.getElementById('staff-avatar-placeholder')?.classList.add('hidden');
        const remove = document.querySelector('input[name="avatar_remove"]');
        if (remove) remove.checked = false;
    });
    document.getElementById('staff-form')?.addEventListener('submit', event => {
        const button = event.currentTarget.querySelector('[data-submit]');
        if (button) { button.disabled = true; button.textContent = 'Menyimpan...'; }
    });
})();
</script>
@endpush
