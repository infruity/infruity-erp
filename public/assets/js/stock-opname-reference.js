/* Presentation helpers only. Laravel remains authoritative for stock and money. */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.StockOpnameUi = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';
    const round = value => Math.round((Number(value) + Number.EPSILON) * 1000) / 1000;
    function parseQuantity(value) {
        const text = String(value ?? '').trim();
        if (!text) return 0;
        if (!/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{0,3})?$/.test(text)) return null;
        const number = Number(text.replace(/\./g, '').replace(',', '.'));
        return Number.isFinite(number) ? number : null;
    }
    function quantity(value) {
        return value === null || value === undefined || !Number.isFinite(Number(value))
            ? '—' : Number(value).toLocaleString('id-ID', {maximumFractionDigits:3});
    }
    function history(entries) {
        const timestamps = new Map();
        entries.forEach(entry => {
            if (entry.created_at) timestamps.set(entry.created_at, (timestamps.get(entry.created_at) || 0) + 1);
        });
        return entries.map((entry, index) => {
            const after = entry.real_stock == null ? null : Number(entry.real_stock);
            const initial = entry.action === 'INITIAL';
            const synthetic = initial && entry.id == null;
            const previous = entries[index + 1];
            // The endpoint has no tie-breaker for equal timestamps. Neither update
            // in that group has a provable predecessor; don't invent a direction.
            const tied = timestamps.get(entry.created_at) > 1 || timestamps.get(previous?.created_at) > 1;
            const before = synthetic ? null : initial && after !== null && entry.difference != null
                ? round(after - Number(entry.difference))
                : !tied && previous?.real_stock != null ? Number(previous.real_stock) : null;
            const delta = after !== null && before !== null ? round(after - before) : null;
            return {...entry, before, after, delta,
                kind: synthetic ? 'snapshot' : initial ? 'initial' : delta === null ? 'unknown' : delta > 0 ? 'plus' : delta < 0 ? 'minus' : 'note'};
        });
    }
    function filterHistory(entries, direction) {
        return direction === 'all' ? entries : entries.filter(entry => entry.kind === direction);
    }
    function correction({plus, minus, reason, physical, pending}) {
        const up = parseQuantity(plus), down = parseQuantity(minus);
        const note = String(reason ?? '').trim();
        if (!pending || up === null || down === null || !note || note.length > 1000) return null;
        const delta = round(up - down);
        if (Math.abs(delta) < .01 || !Number.isFinite(Number(physical)) || Number(physical) + delta < 0) return null;
        return {qty:Math.abs(delta),type:delta > 0 ? 'up' : 'down',reason:note};
    }
    function formatLongDateTime(value, timezone) {
        if (!value) return '—';
        const text=String(value).trim();
        const fields=/^(\d{4}-\d{2}-\d{2})(?:[ T](\d{2}:\d{2}:\d{2})(\.\d{1,6})?(Z|[+-]\d{2}:?\d{2})?)?$/.exec(text);
        if (!fields) return '—';
        const clock=fields[2] || '00:00:00';
        const carrier=new Date(`${fields[1]}T${clock}${fields[3] || ''}Z`);
        // Date parsing silently normalizes February 30 and 24:00; neither is a
        // valid recorded clock value. Never return arbitrary raw input as HTML.
        if (!Number.isFinite(carrier.getTime()) || carrier.toISOString().slice(0,19) !== `${fields[1]}T${clock}`) return '—';
        const wall=!fields[4];
        // Database values already contain application-local clock fields. Use
        // UTC only as a timezone-neutral carrier, never as their actual zone.
        const date=wall ? carrier : new Date(`${fields[1]}T${clock}${fields[3] || ''}${fields[4]}`);
        if (!Number.isFinite(date.getTime())) return '—';
        const parts=Object.fromEntries(new Intl.DateTimeFormat('en-GB', {
            timeZone:wall ? 'UTC' : timezone,day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',hourCycle:'h23'
        }).formatToParts(date).map(part => [part.type,part.value]));
        const month={May:'Mei',Aug:'Agu',Oct:'Okt',Dec:'Des'}[parts.month] || parts.month;
        return `${parts.day} ${month} ${parts.year}, ${parts.hour}:${parts.minute}`;
    }
    function period(preset, timezone, now = new Date()) {
        const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
            timeZone:timezone,year:'numeric',month:'2-digit',day:'2-digit'
        }).formatToParts(now).map(part => [part.type,part.value]));
        const start = new Date(Date.UTC(Number(parts.year),Number(parts.month)-1,Number(parts.day)));
        if (preset === 'week') start.setUTCDate(start.getUTCDate() - (start.getUTCDay() || 7) + 1);
        const end = new Date(start);
        if (preset === 'week') end.setUTCDate(end.getUTCDate()+6);
        return {start:start.toISOString().slice(0,10),end:end.toISOString().slice(0,10)};
    }
    function filterDraft(applied) {
        return {
            branch:String(applied.branch || 'all'),
            start:applied.start || '',
            end:applied.end || '',
            status:String(applied.status || 'all'),
            result:String(applied.result || 'all')
        };
    }
    function filterRequest(draft) {
        return {
            cabang_filter:draft.branch,
            start_date:draft.start,
            end_date:draft.end || draft.start,
            status_filter:draft.status,
            result_filter:draft.result
        };
    }
    return {parseQuantity,quantity,history,filterHistory,correction,formatLongDateTime,period,filterDraft,filterRequest};
});
