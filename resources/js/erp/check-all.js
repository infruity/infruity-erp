/**
 * Checkbox "pilih semua" untuk tabel/daftar.
 *
 *   <input type="checkbox" data-erp-check-all="#tabel">
 *   <input type="checkbox" data-erp-check>        di dalam #tabel
 *
 * Header otomatis `indeterminate` bila sebagian terpilih. Setelah DataTables
 * menggambar ulang, status header direset.
 */
export function state(checked, total) {
    if (total === 0 || checked === 0) return { checked: false, indeterminate: false };
    if (checked === total) return { checked: true, indeterminate: false };
    return { checked: false, indeterminate: true };
}

function rows(root) {
    return [...root.querySelectorAll('[data-erp-check]')].filter((el) => !el.disabled);
}

function sync(master, root) {
    const items = rows(root);
    const next = state(items.filter((el) => el.checked).length, items.length);
    master.checked = next.checked;
    master.indeterminate = next.indeterminate;
}

export function install(doc = document, win = window) {
    // Saat "pilih semua" mengubah baris satu per satu, sinkronisasi header harus
    // ditunda; kalau tidak, header terbaca ulang di tengah loop (sebagian terpilih)
    // dan nilai yang dipropagasi ke baris berikutnya ikut berubah.
    let bulk = false;

    doc.addEventListener('change', (event) => {
        const el = event.target;
        if (!el || !el.matches) return;

        if (el.matches('[data-erp-check-all]')) {
            const root = doc.querySelector(el.getAttribute('data-erp-check-all'));
            if (!root) return;
            const value = el.checked;
            bulk = true;
            try {
                rows(root).forEach((item) => {
                    item.checked = value;
                    item.dispatchEvent(new Event('change', { bubbles: true }));
                });
            } finally {
                bulk = false;
            }
            el.checked = value;
            el.indeterminate = false;
            return;
        }

        if (!bulk && el.matches('[data-erp-check]')) {
            doc.querySelectorAll('[data-erp-check-all]').forEach((master) => {
                const root = doc.querySelector(master.getAttribute('data-erp-check-all'));
                if (root && root.contains(el)) sync(master, root);
            });
        }
    });

    if (win.jQuery) {
        win.jQuery(doc).on('draw.dt', () => {
            doc.querySelectorAll('[data-erp-check-all]').forEach((master) => {
                const root = doc.querySelector(master.getAttribute('data-erp-check-all'));
                if (root) sync(master, root);
            });
        });
    }
}
