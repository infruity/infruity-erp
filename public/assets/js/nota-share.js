(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.NotaShare = factory();
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function normalizePhone(raw) {
        var phone = String(raw || '').replace(/[^0-9]/g, '');
        if (phone.indexOf('0') === 0) return '62' + phone.slice(1);
        if (phone.indexOf('8') === 0) return '62' + phone;
        return phone;
    }

    // Text only: the nota itself travels as the PNG, so no link to a page (a local or private URL would not open for the customer).
    function buildMessage(info) {
        return 'Halo Kak ' + info.customer + ', terima kasih telah berbelanja di in!fruity!\n\n'
            + '*Nota Transaksi:* ' + info.invoice + '\n'
            + '*Total:* ' + info.total;
    }

    function whatsappUrl(phone, text) {
        return 'https://wa.me/' + normalizePhone(phone) + '?text=' + encodeURIComponent(text);
    }

    function toast(doc, text) {
        var box = doc.createElement('div');
        box.setAttribute('role', 'status');
        box.textContent = text;
        box.style.cssText = 'position:fixed;left:16px;right:16px;bottom:96px;z-index:2000;max-width:420px;margin:0 auto;padding:12px 14px;border-radius:12px;background:#1f2937;color:#fff;font:600 12px/1.5 system-ui,sans-serif;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.25)';
        doc.body.appendChild(box);
        setTimeout(function () { box.remove(); }, 6000);
    }

    // Renders the receipt (as the customer sees it: 400px wide, sub-items hidden) to a PNG Blob.
    async function buildReceiptBlob(doc, win) {
        var receipt = doc.getElementById('pos-printable-receipt');
        if (!receipt) return null;
        for (var i = 0; i < 100 && typeof win.html2canvas !== 'function'; i++) await new Promise(function (r) { setTimeout(r, 100); });
        if (typeof win.html2canvas !== 'function') return null;
        if (doc.fonts && doc.fonts.ready) await doc.fonts.ready;
        var wrapper = doc.createElement('div');
        Object.assign(wrapper.style, { position: 'absolute', left: '-9999px', top: '0', width: '400px', background: '#fff' });
        try {
            var clone = receipt.cloneNode(true);
            clone.removeAttribute('id');
            clone.querySelectorAll('.nota-sub').forEach(function (element) { element.remove(); });
            Object.assign(clone.style, { width: '400px', maxWidth: '400px', height: 'auto', minHeight: '0', overflow: 'visible', border: '0', borderRadius: '0', boxShadow: 'none', padding: '28px 24px 24px' });
            var items = clone.querySelector('.nota-items');
            if (items) Object.assign(items.style, { flex: '0 0 auto', minHeight: '0', overflow: 'visible' });
            wrapper.appendChild(clone);
            doc.body.appendChild(wrapper);
            var canvas = await win.html2canvas(wrapper, { scale: 2.5, backgroundColor: '#fff', useCORS: true, logging: false, width: 400, height: Math.ceil(wrapper.offsetHeight), windowWidth: 400 });
            return await new Promise(function (resolve) { canvas.toBlob(resolve, 'image/png'); });
        } finally {
            wrapper.remove();
        }
    }

    // WhatsApp builds the link preview on its own servers: it cannot reach a local or private address.
    function isPublicHost(hostname) {
        var host = String(hostname || '').toLowerCase();
        if (!host || host === 'localhost' || host === '::1' || host === '[::1]') return false;
        if (/\.(local|localhost|test|internal|lan)$/.test(host)) return false;
        if (/^(127|10)\./.test(host) || /^192\.168\./.test(host) || /^172\.(1[6-9]|2[0-9]|3[01])\./.test(host)) return false;
        return true;
    }

    // Stores the PNG on the server so the public nota page can show it as the link preview. Resolves to the public nota URL.
    async function publish(options) {
        var form = new options.win.FormData();
        form.append('image', options.blob, options.filename);
        if (options.paymentUuid) form.append('payment_uuid', options.paymentUuid);
        else if (options.posUuid) form.append('pos_uuid', options.posUuid);
        var response = await options.win.fetch(options.endpoint, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': options.csrf, 'Accept': 'application/json' },
            body: form,
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        var json = await response.json();
        return json && json.url ? json.url : null;
    }

    /*
     * Sends the nota PNG to the customer on WhatsApp.
     * - Customer has a WhatsApp number: the picture is copied (and downloaded as a backup) and the customer's chat opens with the
     *   greeting, so the user only presses paste (Ctrl/Cmd+V) and Send. A web page cannot attach a file to wa.me by itself.
     * - No number (walk-in customer): the system share sheet opens with the picture attached (Web Share) so the user picks the chat;
     *   without Web Share it falls back to the same copy + open flow, with WhatsApp asking for the chat.
     * The blob must already exist: this has to run inside the click for the browser to allow the window and the share sheet.
     */
    async function share(options) {
        var win = options.win, doc = options.doc, nav = win.navigator;
        var blob = options.blob;
        if (!blob) {
            toast(doc, 'Gambar nota belum siap. Coba lagi sebentar lagi.');
            return 'unavailable';
        }
        var phone = normalizePhone(options.phone);
        var hasPhone = phone.length >= 8;
        var chatWindow = null;
        if (hasPhone && typeof options.publish === 'function' && isPublicHost(win.location && win.location.hostname)) {
            // Link route: the PNG is stored and the message carries the nota link, so WhatsApp shows the nota picture as a preview card
            // and the user only presses Send. The window opens inside the click and gets its address once the PNG is stored.
            chatWindow = win.open('', '_blank');
            try {
                var link = await options.publish(blob);
                if (link) {
                    var target = whatsappUrl(phone, options.text + '\n\n' + link);
                    if (chatWindow) chatWindow.location.href = target; else win.open(target, '_blank');
                    return 'link';
                }
            } catch (error) {
                // Stay on the copy + paste route below.
            }
        }
        var file = new win.File([blob], options.filename, { type: 'image/png' });
        if (!hasPhone && typeof nav.share === 'function' && typeof nav.canShare === 'function' && nav.canShare({ files: [file] })) {
            try {
                await nav.share({ files: [file], text: options.text });
                return 'shared';
            } catch (error) {
                if (error && error.name === 'AbortError') return 'cancelled';
                // Any other failure falls through to the copy + open route below.
            }
        }
        var manualUrl = whatsappUrl(hasPhone ? phone : '', options.text);
        var chat = chatWindow || win.open(manualUrl, '_blank');
        if (chatWindow) chatWindow.location.href = manualUrl;
        if (nav.clipboard && win.ClipboardItem) {
            nav.clipboard.write([new win.ClipboardItem({ 'image/png': blob })]).catch(function () { /* the download below remains */ });
        }
        var url = win.URL.createObjectURL(blob);
        var link = doc.createElement('a');
        link.href = url;
        link.download = options.filename;
        doc.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { win.URL.revokeObjectURL(url); }, 8000);
        toast(doc, 'Gambar nota sudah disalin. Di chat WhatsApp, tekan Ctrl/Cmd+V lalu kirim. (Berkasnya juga terunduh.)');
        return chat ? 'manual' : 'manual-blocked';
    }

    return { isPublicHost: isPublicHost, publish: publish, normalizePhone: normalizePhone, buildMessage: buildMessage, whatsappUrl: whatsappUrl, buildReceiptBlob: buildReceiptBlob, share: share, toast: toast };
});
