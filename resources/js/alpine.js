import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import anchor from '@alpinejs/anchor';

Alpine.plugin(collapse);
Alpine.plugin(anchor);

// Dropdown menu (x-erp.dropdown). Panel di-teleport ke <body>, jadi tidak terpotong overflow tabel.
Alpine.data('erpDropdown', () => ({
    open: false,
    items() {
        return this.$refs.menu ? [...this.$refs.menu.querySelectorAll('[role="menuitem"]:not([disabled])')] : [];
    },
    toggle() {
        this.open ? this.close() : this.show();
    },
    show() {
        this.open = true;
        this.$nextTick(() => this.items()[0]?.focus());
    },
    close(refocus = false) {
        if (!this.open) return;
        this.open = false;
        if (refocus) this.$refs.trigger?.focus();
    },
    move(step) {
        const items = this.items();
        if (!items.length) return;
        const at = items.indexOf(document.activeElement);
        items[(at + step + items.length) % items.length].focus();
    },
}));

window.Alpine = Alpine;
Alpine.start();
