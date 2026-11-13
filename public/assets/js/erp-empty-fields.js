/*
 * One grey for everything that still shows a prompt: empty selects ("Pilih ...") and empty date/time fields get the
 * class erp-empty (grey, like an input placeholder) until a value is chosen. The CSS is in erp-theme.css.
 * Native date inputs cannot be styled by value in CSS, so the class is kept by a few listeners.
 */
(function () {
    var PICKERS = ['date', 'time', 'datetime-local', 'month', 'week'];

    function sync(el) {
        if (el.tagName === 'SELECT') {
            var option = el.selectedOptions && el.selectedOptions[0];
            el.classList.toggle('erp-empty', !!option && option.value === '' && /^[\s\-\u2013]*pilih/i.test(option.textContent.trim()));
        } else if (el.tagName === 'INPUT' && PICKERS.indexOf(el.type) !== -1) {
            el.classList.toggle('erp-empty', !el.value);
        }
    }

    function scan(root) {
        if (root.nodeType !== 1 && root.nodeType !== 9) return;
        if (root.nodeType === 1) sync(root);
        root.querySelectorAll('select, input[type="date"], input[type="time"], input[type="datetime-local"], input[type="month"], input[type="week"]').forEach(sync);
    }

    ['input', 'change', 'focusout'].forEach(function (name) {
        document.addEventListener(name, function (event) { sync(event.target); }, true);
    });
    document.addEventListener('reset', function () { setTimeout(function () { scan(document); }, 0); }, true);

    function start() {
        scan(document);
        // values set by scripts (defaults, select2, resets) fire no event: keep the marks honest
        setInterval(function () { if (!document.hidden) scan(document); }, 500);
        new MutationObserver(function (records) {
            records.forEach(function (record) { record.addedNodes.forEach(scan); });
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
