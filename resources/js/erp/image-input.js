/**
 * Pemilih gambar dengan pratinjau (pengganti KTImageInput). Markup: components/erp/image-input.blade.php.
 *
 *   pilih file  -> pratinjau file, tombol Batal muncul
 *   Batal       -> kembali ke gambar awal, file dikosongkan
 *   Hapus       -> pratinjau placeholder, file dikosongkan, flag `*_remove` = 1
 *   form reset  -> kembali ke gambar awal
 *
 * `set(root, url)` mengganti gambar awal (mis. saat mengisi form ubah).
 */
const ROOT = '[data-erp-image-input]';

const parts = (root) => ({
    preview: root.querySelector('[data-image-preview]'),
    file: root.querySelector('[data-image-file]'),
    flag: root.querySelector('[data-image-remove-flag]'),
    cancel: root.querySelector('[data-image-cancel]'),
});

function show(root, url) {
    const { preview } = parts(root);
    if (preview) preview.style.backgroundImage = `url("${url}")`;
}

function restore(root) {
    const { file, flag, cancel } = parts(root);
    if (file) file.value = '';
    if (flag) flag.value = '';
    if (cancel) cancel.hidden = true;
    show(root, root.dataset.original || root.dataset.placeholder);
}

export function set(root, url) {
    root.dataset.original = url || root.dataset.placeholder;
    restore(root);
}

export function install(doc = document, win = window) {
    doc.addEventListener('change', (event) => {
        const input = event.target;
        if (!input || !input.matches || !input.matches('[data-image-file]')) return;
        const root = input.closest(ROOT);
        const file = input.files && input.files[0];
        if (!root || !file) return;
        const { flag, cancel } = parts(root);
        if (flag) flag.value = '';
        if (cancel) cancel.hidden = false;
        show(root, win.URL.createObjectURL(file));
    });

    doc.addEventListener('click', (event) => {
        const target = event.target;
        if (!target || !target.closest) return;
        const remove = target.closest('[data-image-remove]');
        const cancel = target.closest('[data-image-cancel]');
        const button = remove || cancel;
        if (!button) return;
        const root = button.closest(ROOT);
        if (!root) return;
        if (cancel) return restore(root);
        const { file, flag, cancel: cancelButton } = parts(root);
        if (file) file.value = '';
        if (flag) flag.value = '1';
        if (cancelButton) cancelButton.hidden = false;
        show(root, root.dataset.placeholder);
    });

    // Reset form mengembalikan semua pemilih gambar di dalamnya.
    doc.addEventListener('reset', (event) => {
        const form = event.target;
        if (!form || !form.querySelectorAll) return;
        form.querySelectorAll(ROOT).forEach(restore);
    });

    return { set };
}
