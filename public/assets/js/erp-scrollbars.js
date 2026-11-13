/*
 * Overlay scrollbars. Native scrollbars take layout space (the content ends up off-centre and jumps when a bar
 * appears), so they are switched off in erp-theme.css and a thin thumb is drawn on top instead: position: fixed,
 * outside the layout, shown while the element scrolls and faded out about a second later.
 * Elements that hide their bar on purpose (scrollbar-hide, own thumb) or style a native one are left alone.
 */
(function () {
    var HIDE_AFTER = 900;
    var state = new WeakMap();

    function thumb(el, axis) {
        var entry = state.get(el);
        if (!entry) { entry = { timer: null }; state.set(el, entry); }
        if (!entry[axis]) {
            var node = document.createElement('div');
            node.className = 'erp-thumb erp-thumb--' + axis;
            node.setAttribute('aria-hidden', 'true');
            document.body.appendChild(node);
            entry[axis] = node;
        }
        return entry[axis];
    }

    function skip(el) {
        if (el.classList.contains('scrollbar-hide')) return true;
        return getComputedStyle(el).scrollbarWidth !== 'none'; // a page styles its own native bar
    }

    function draw(el) {
        var root = el === document.documentElement;
        var box = root ? { top: 0, left: 0, right: window.innerWidth, bottom: window.innerHeight } : el.getBoundingClientRect();
        var viewH = root ? window.innerHeight : el.clientHeight;
        var viewW = root ? window.innerWidth : el.clientWidth;
        var overflowY = el.scrollHeight - viewH;
        var overflowX = el.scrollWidth - viewW;

        var v = thumb(el, 'y');
        if (overflowY > 1) {
            var trackH = viewH - 8;
            var h = Math.max(trackH * (viewH / el.scrollHeight), 28);
            v.style.height = h + 'px';
            v.style.top = (box.top + 4 + (el.scrollTop / overflowY) * (trackH - h)) + 'px';
            v.style.left = (box.right - 6) + 'px';
            v.classList.add('is-on');
        } else v.classList.remove('is-on');

        var x = thumb(el, 'x');
        if (overflowX > 1) {
            var trackW = viewW - 8;
            var w = Math.max(trackW * (viewW / el.scrollWidth), 28);
            x.style.width = w + 'px';
            x.style.left = (box.left + 4 + (el.scrollLeft / overflowX) * (trackW - w)) + 'px';
            x.style.top = (box.bottom - 6) + 'px';
            x.classList.add('is-on');
        } else x.classList.remove('is-on');
    }

    document.addEventListener('scroll', function (event) {
        var el = event.target === document ? document.documentElement : event.target;
        if (!el || el.nodeType !== 1 || skip(el)) return;
        draw(el);
        var entry = state.get(el);
        clearTimeout(entry.timer);
        entry.timer = setTimeout(function () {
            ['y', 'x'].forEach(function (axis) { if (entry[axis]) entry[axis].classList.remove('is-on'); });
        }, HIDE_AFTER);
    }, { capture: true, passive: true });
})();
