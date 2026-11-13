/*
 * One tint per name for every initials avatar in the ERP (branch, customer, ...): the same name always gets the same
 * colour on every page, different names spread over the ten colours of the reference. Same FNV-1a hash as App\Support\AvatarTone
 * (PHP), so server-rendered and script-rendered avatars agree.
 *     ErpTone.className('Toko GKB')  -> 'erp-tone-3'   (colours live in erp-theme.css)
 *     ErpTone.style('Toko GKB')      -> { background, color, borderColor }   (for Alpine :style)
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.ErpTone = factory();
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    // Order matters: neighbours of the hash (0, 8, 4, 6 ...) are far apart in hue.
    var TONES = [
        { background: '#ecfdf5', color: '#047857', borderColor: '#a7f3d0' }, // emerald
        { background: '#eff6ff', color: '#1d4ed8', borderColor: '#bfdbfe' }, // blue
        { background: '#faf5ff', color: '#7e22ce', borderColor: '#e9d5ff' }, // purple
        { background: '#fffbeb', color: '#b45309', borderColor: '#fde68a' }, // amber
        { background: '#eef2ff', color: '#4338ca', borderColor: '#c7d2fe' }, // indigo
        { background: '#f0f9ff', color: '#0369a1', borderColor: '#bae6fd' }, // sky
        { background: '#fdf4ff', color: '#a21caf', borderColor: '#f5d0fe' }, // fuchsia
        { background: '#f0fdfa', color: '#0f766e', borderColor: '#99f6e4' }, // teal
        { background: '#fff1f2', color: '#be123c', borderColor: '#fecdd3' }, // rose
        { background: '#fff7ed', color: '#c2410c', borderColor: '#fed7aa' }  // orange
    ];
    function index(name) {
        var value = String(name == null ? '' : name).trim().toLowerCase();
        var hash = 2166136261; // FNV-1a over the UTF-16 code units: neighbouring names (GKB / GBA) must not collide
        for (var i = 0; i < value.length; i++) hash = Math.imul(hash ^ value.charCodeAt(i), 16777619) >>> 0;
        return hash % TONES.length;
    }

    return {
        TONES: TONES,
        index: index,
        className: function (name) { return 'erp-tone-' + index(name); },
        style: function (name) { return Object.assign({}, TONES[index(name)]); }
    };
});
