/*
 * Scrollbars show only while something scrolls (or the pointer is over it) and fade away after: the scrolling
 * element gets the class erp-scrolling for a moment. The look is in erp-theme.css (thin, transparent when idle).
 */
(function () {
    var HIDE_AFTER = 900;
    var timers = new WeakMap();

    document.addEventListener('scroll', function (event) {
        var el = event.target === document ? document.documentElement : event.target;
        if (!el || !el.classList) return;
        el.classList.add('erp-scrolling');
        clearTimeout(timers.get(el));
        timers.set(el, setTimeout(function () { el.classList.remove('erp-scrolling'); }, HIDE_AFTER));
    }, { capture: true, passive: true });
})();
