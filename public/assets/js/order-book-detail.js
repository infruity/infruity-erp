/* Detail is a presentation of the current DataTables row, never a second API. */
(() => {
    'use strict';
    const text = value => typeof value === 'string' ? value.trim() : '';

    function whatsappUrl(value) {
        const phone = text(value);
        if (!phone || !/^\+?[\d\s().-]+$/.test(phone)) return null;
        let digits = phone.replace(/\D/g, '');
        if (digits.startsWith('0')) digits = '62' + digits.replace(/^0+/, '');
        else if (!phone.startsWith('+') && digits.startsWith('8')) digits = '62' + digits;
        return /^[1-9]\d{7,14}$/.test(digits) ? 'https://wa.me/' + digits : null;
    }

    function normalize(data = {}) {
        const phone = text(data.customer_phone);
        const suffix = phone.replace(/\D/g, '').slice(-4);
        const name = text(data.customer_name) || 'Pelanggan Umum';
        return {
            customerDisplay: suffix.length === 4 && !name.endsWith('(' + suffix + ')') ? `${name} (${suffix})` : name,
            phone: phone || 'Tidak ada nomor telepon',
            whatsappUrl: whatsappUrl(phone),
            branch: text(data.branch_name) || '-',
            createdAt: text(data.created_at) || '-',
            createdBy: 'Dibuat oleh ' + (text(data.created_by_name) || '-'),
            editedAt: text(data.updated_at) || '-',
            editedBy: data.updated_at ? (text(data.updated_by_name) ? 'Diedit oleh ' + text(data.updated_by_name) : 'Waktu Pesanan Diedit') : 'Diedit',
            locked: data.locked === true
        };
    }

    function fromRow(row, data = {}, timezone = 'Asia/Jakarta') {
        const monthNames = { May: 'Mei', Aug: 'Agu', Oct: 'Okt', Dec: 'Des' };
        const created = text(row.cells[1]?.querySelector('.text-muted')?.textContent)
            .replace(/\b(May|Aug|Oct|Dec)\b/g, month => monthNames[month])
            .replace(/,?\s+(\d{2}:\d{2})$/, ', $1');
        let edited = '';
        if (typeof data.updated_at === 'string' && /^\d{4}-\d{2}-\d{2}T/.test(data.updated_at)) {
            const date = new Date(data.updated_at);
            if (!Number.isNaN(date.getTime())) {
                const parts = new Intl.DateTimeFormat('id-ID', {
                    timeZone: timezone, day: 'numeric', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
                }).formatToParts(date).reduce((result, part) => { result[part.type] = part.value; return result; }, {});
                edited = `${parts.day} ${parts.month} ${parts.year}, ${parts.hour}:${parts.minute}`;
            }
        }
        return normalize({
            customer_name: text(row.cells[0]?.querySelector('a.text-gray-800')?.textContent) || text(data.customer?.name),
            customer_phone: text(data.customer?.whatsapp),
            branch_name: text(row.cells[0]?.querySelector('.badge-light-primary')?.textContent),
            created_at: created,
            created_by_name: text(data.created_by_name),
            updated_at: edited,
            locked: /\bLocked\b/i.test(row.cells[row.cells.length - 1]?.textContent || '')
        });
    }

    function actionFor(original, posLink, origin, posIndexUrl = '') {
        const isDelete = /^deleteProduct\(\d+\);?$/.test(original.getAttribute('onclick') || '');
        if (isDelete) return { isDelete, label: 'Hapus Pesanan' };
        const href = original.getAttribute('href') || '';
        if (!href || href === '#' || href.startsWith('//')) return null;
        let url;
        try { url = new URL(href, origin + '/'); } catch { return null; }
        if (!['http:', 'https:'].includes(url.protocol) || url.origin !== origin) return null;
        const isEdit = /\/edit\/?$/.test(url.pathname);
        const isPos = original === posLink;
        if (isPos && posIndexUrl) {
            const posId = url.pathname.match(/\/pos\/show\/([1-9]\d*)\/?$/)?.[1];
            if (!posId) return null;
            try { url = new URL(posIndexUrl, origin + '/'); } catch { return null; }
            if (!['http:', 'https:'].includes(url.protocol) || url.origin !== origin) return null;
            url.searchParams.set('detail', posId);
        }
        return { href: url.href, isDelete: false, isEdit, isPos, label: isEdit ? 'Edit' : isPos ? 'Lihat POS' : 'Proses Pesanan' };
    }

    function create() {
        const recordRows = new WeakMap();
        let observer;
        let clickHandler;
        return {
            detailOpen: false,
            selected: normalize(),
            trigger: null,
            sourceRow: null,
            timezone: 'Asia/Jakarta',
            posIndexUrl: '',
            previousBodyOverflow: '',
            previousMainOverflow: '',

            init() {
                this.timezone = this.$el.dataset.orderBookTimezone || 'Asia/Jakarta';
                this.posIndexUrl = this.$el.dataset.posIndexUrl || '';
                const table = document.getElementById('pos-table');
                if (!table) return;
                const collection = document.querySelector('[data-erp-collection="pos-table"]');
                const sync = () => {
                    if (this.detailOpen && !this.sourceRow?.isConnected) this.closeDetail();
                    const rows = [...table.tBodies].flatMap(body => [...body.rows])
                        .filter(row => !row.classList.contains('child') && row.cells.length > 1 && row.style.display !== 'none');
                    collection?.querySelectorAll('.erp-record-list > article.erp-record').forEach((record, index) => {
                        const row = rows[index];
                        const name = row?.cells[0]?.querySelector('a.text-gray-800');
                        if (!row || text(name?.textContent) !== text(record.querySelector('.erp-record__title')?.textContent)) return;
                        // Bind once to the actual DOM row. A stale record must never resolve to a newer row at the same index.
                        if (!recordRows.has(record)) recordRows.set(record, row);
                        const head = record.querySelector('.erp-record__head');
                        if (head) {
                            head.setAttribute('aria-haspopup', 'dialog');
                            head.setAttribute('aria-controls', 'order-book-detail-dialog');
                        }
                    });
                    rows.forEach(row => {
                        const name = row.cells[0]?.querySelector('a.text-gray-800');
                        if (!name) return;
                        name.setAttribute('aria-haspopup', 'dialog');
                        name.setAttribute('aria-controls', 'order-book-detail-dialog');
                        name.setAttribute('aria-label', 'Detail pesanan ' + text(name.textContent));
                    });
                };
                clickHandler = event => {
                    if (event.button !== 0 || !(event.target instanceof Element)) return;
                    const head = event.target.closest('[data-erp-collection="pos-table"] .erp-record__head');
                    const name = event.target.closest('#pos-table tbody a.text-gray-800');
                    if (!head && !name) return;
                    if (!head && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) return;
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    sync();
                    const row = head ? recordRows.get(head.closest('article.erp-record')) : name.closest('tr');
                    if (!row?.isConnected || !table.contains(row)) return;
                    const api = window.jQuery?.fn?.dataTable?.isDataTable(table) ? window.jQuery(table).DataTable() : null;
                    this.openDetail({ row, data: api?.row(row).data() || {}, trigger: head || name });
                };
                document.addEventListener('click', clickHandler, true);
                observer = new MutationObserver(sync);
                observer.observe(collection || table.parentElement, { childList: true, subtree: true });
                sync();
            },

            destroy() {
                observer?.disconnect();
                if (clickHandler) document.removeEventListener('click', clickHandler, true);
                this.closeDetail(false);
            },

            openDetail({ data, row, trigger } = {}) {
                if (!row?.isConnected || this.detailOpen) return;
                this.selected = fromRow(row, data, this.timezone);
                this.sourceRow = row;
                this.trigger = trigger;
                this.renderActions(row);
                this.previousBodyOverflow = document.body.style.overflow;
                const main = document.querySelector('.erp-main');
                this.previousMainOverflow = main?.style.overflow || '';
                document.body.style.overflow = 'hidden';
                if (main) main.style.overflow = 'hidden';
                this.detailOpen = true;
                trigger?.setAttribute('aria-expanded', 'true');
                this.$nextTick(() => requestAnimationFrame(() => {
                    if (this.detailOpen) this.$refs.detailDialog.querySelector('button')?.focus();
                }));
            },

            renderActions(row) {
                const host = this.$refs.detailActions;
                host.replaceChildren();
                const originals = [...row.cells[row.cells.length - 1].querySelectorAll('.dropdown-menu a')];
                const posLink = row.cells[0].querySelector('a[title="Lihat POS"]');
                if (posLink) originals.push(posLink);
                originals.map(original => ({ original, action: actionFor(original, posLink, window.location.origin, this.posIndexUrl) }))
                    .filter(item => item.action)
                    .sort((a, b) => Number(b.action.isDelete) - Number(a.action.isDelete))
                    .forEach(({ original, action }) => {
                        const copy = document.createElement(action.isDelete ? 'button' : 'a');
                        const primary = !action.isDelete && !action.isEdit;
                        const pending = primary && /^(draft|temp|pending)$/i.test(text(row.cells[1].querySelector('.badge')?.textContent));
                        copy.className = 'order-book-detail__action' + (action.isDelete ? ' order-book-detail__action--delete' : primary ? ' order-book-detail__action--primary' : '') + (pending ? ' order-book-detail__action--pending' : '');
                        copy.textContent = action.label;
                        if (action.isDelete) copy.type = 'button';
                        else copy.href = action.href;
                        copy.addEventListener('click', event => {
                            if (action.isPos) {
                                if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                                this.closeDetail(false);
                                return;
                            }
                            if (!action.isDelete && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) return;
                            event.preventDefault();
                            this.closeDetail(false);
                            original.click();
                        });
                        host.append(copy);
                    });
                if (!host.childElementCount) {
                    const close = document.createElement('button');
                    close.type = 'button';
                    close.className = 'order-book-detail__action order-book-detail__action--primary';
                    close.textContent = 'Tutup';
                    close.addEventListener('click', () => this.closeDetail());
                    host.append(close);
                }
            },

            closeDetail(restoreFocus = true) {
                if (!this.detailOpen) return;
                this.detailOpen = false;
                document.body.style.overflow = this.previousBodyOverflow;
                const main = document.querySelector('.erp-main');
                if (main) main.style.overflow = this.previousMainOverflow;
                const trigger = this.trigger;
                trigger?.setAttribute('aria-expanded', 'false');
                if (restoreFocus) this.$nextTick(() => {
                    if (trigger?.isConnected && trigger.getClientRects().length) trigger.focus();
                    else [...document.querySelectorAll('[data-kt-ecommerce-product-filter="search"]')]
                        .find(input => input.getClientRects().length)?.focus();
                });
            },

            trapDetailFocus(event) {
                if (!this.detailOpen) return;
                const dialog = this.$refs.detailDialog;
                const controls = [...dialog.querySelectorAll('button, a[href], [tabindex="0"]')]
                    .filter(element => !element.disabled && element.getClientRects().length > 0);
                const first = controls[0];
                const last = controls[controls.length - 1];
                if (!first) return;
                if (!dialog.contains(document.activeElement) || (event.shiftKey && document.activeElement === first)) {
                    event.preventDefault(); (event.shiftKey ? last : first).focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault(); first.focus();
                }
            }
        };
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = { normalize, whatsappUrl, fromRow, actionFor, create };
    if (typeof window !== 'undefined') window.orderBookDetailDialog = create;
})();
