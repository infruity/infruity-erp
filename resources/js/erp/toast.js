/**
 * Toast singkat di atas SweetAlert2 (menggantikan toastr).
 *   erp.toast({ type: 'success', message: 'Tersimpan' })
 * type: success | error | warning | info
 */
const ICONS = new Set(['success', 'error', 'warning', 'info']);

export function toast({ type = 'info', message = '', duration = 3000 } = {}, Swal = window.Swal) {
    if (!Swal) return null;
    return Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: duration,
        timerProgressBar: true,
    }).fire({ icon: ICONS.has(type) ? type : 'info', title: message });
}
