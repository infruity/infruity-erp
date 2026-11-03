/* One payment intent keeps its key and payload until the server has definitively
   accepted or rejected it. A network failure must never create a fresh intent. */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else root.createPaymentAttempt = factory;
})(typeof window !== 'undefined' ? window : globalThis, function ({ storage, storageKey, send, uuid }) {
    let busy = false;
    let attempt = null;
    try { attempt = JSON.parse(storage?.getItem(storageKey) || 'null'); } catch (_) { /* Storage may be unavailable. */ }
    if (!attempt?.key || !attempt?.payload) attempt = null;
    const persist = () => { try { storage?.setItem(storageKey, JSON.stringify(attempt)); } catch (_) {} };
    const clear = () => { attempt = null; try { storage?.removeItem(storageKey); } catch (_) {} };
    return {
        get busy() { return busy; },
        get pending() { return attempt ? JSON.parse(JSON.stringify(attempt.payload)) : null; },
        async submit(payload) {
            if (busy) return { ignored: true };
            busy = true;
            try {
                if (!attempt) {
                    attempt = { key: uuid(), payload: JSON.parse(JSON.stringify(payload)) };
                    persist();
                }
                const response = await send(attempt.payload, attempt.key);
                let result;
                try { result = await response.json(); } catch (_) { throw new Error('Status pembayaran belum dapat dipastikan. Coba ulang pembayaran yang sama.'); }
                if (response.ok && result.success) { clear(); return { result }; }
                // Validation ran before the transaction and is a definitive rejection.
                if (response.status === 422) clear();
                const error = new Error(result.message || 'Status pembayaran belum dapat dipastikan. Coba ulang pembayaran yang sama.');
                error.status = response.status;
                throw error;
            } finally { busy = false; }
        },
    };
});
