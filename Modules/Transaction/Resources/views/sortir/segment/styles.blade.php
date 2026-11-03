<style>
    [x-cloak] { display:none!important; }
    body:has(.sortir-form) main { padding:0!important; overflow:hidden; }
    body:has(.sortir-form) main > .animate-fade-in-up { padding:0!important; animation:none!important; }
    #kt_content { padding:0!important; overflow:hidden; }
    #kt_post, #kt_content_container { display:flex; min-height:0; flex:1; flex-direction:column; max-width:none; padding:0!important; }
    .erp-mobile-nav { display:none!important; }
    .sortir-form { --sortir-teal:#0b595b; display:flex; flex:1; min-height:0; flex-direction:column; color:#1f2937; font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; }
    .sortir-form *, .sortir-form *::before, .sortir-form *::after { box-sizing:border-box; }
    .sortir-form button, .sortir-form input { font:inherit; }
    .sortir-form button { cursor:pointer; -webkit-tap-highlight-color:transparent; }
    .sortir-form button:disabled { opacity:.55; cursor:default; }
    .sortir-form button:focus-visible, .sortir-form input:not(.sortir-product-search):focus-visible { outline:2px solid #0b595b; outline-offset:2px; }
    .sortir-canvas { width:100%; max-width:944px; margin:0 auto; padding:20px 16px 24px; display:flex; flex:1; min-height:0; flex-direction:column; gap:24px; overflow-y:auto; }
    .sortir-branch-card, .sortir-items-card { background:#fff; border:1px solid rgba(229,231,235,.9); border-radius:16px; box-shadow:0 2px 10px rgba(0,0,0,.02); }
    .sortir-branch-card { flex:none; padding:16px; position:relative; z-index:3; }
    .sortir-field-label { display:block; font-size:12px; color:#374151; font-weight:700; margin-bottom:7px; }
    .sortir-required { color:#ef4444; margin-left:3px; }
    .sortir-branch-picker { position:relative; }
    .sortir-branch-trigger { display:flex; justify-content:space-between; gap:12px; align-items:center; width:100%; min-height:44px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; padding:10px 16px; text-align:left; color:#1f2937; font-size:14px!important; font-weight:600!important; }
    .sortir-branch-trigger:disabled { opacity:1!important; }
    .sortir-branch-trigger span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .sortir-branch-trigger i { color:#9ca3af; font-size:16px; transition:transform .18s; }
    .sortir-branch-trigger i.is-open { transform:rotate(180deg); }
    .sortir-branch-menu { position:absolute; top:calc(100% + 6px); left:0; width:100%; max-height:224px; overflow-y:auto; background:#fff; border:1px solid #f3f4f6; border-radius:12px; padding:4px 0; box-shadow:0 10px 20px rgba(0,0,0,.1); z-index:10; }
    .sortir-branch-menu button { display:flex; justify-content:space-between; align-items:center; gap:12px; width:100%; min-height:44px; border:0; background:#fff; padding:10px 14px; text-align:left; color:#374151; }
    .sortir-branch-menu button:hover, .sortir-branch-menu button.is-selected { background:#f9fafb; }
    .sortir-branch-menu button.is-selected { color:#059669; font-weight:600; }
    .sortir-branch-dot { width:7px; height:7px; border-radius:50%; background:#10b981; flex:none; }
    .sortir-metadata { margin-top:12px; font-size:11px; }
    .sortir-metadata summary { display:flex; align-items:center; gap:8px; cursor:pointer; color:#6b7280; list-style:none; min-height:24px; }
    .sortir-metadata summary::-webkit-details-marker { display:none; }
    .sortir-metadata summary span { margin-left:auto; color:#6b7280; font-size:10px; }
    .sortir-metadata-fields { display:grid; grid-template-columns:1fr 1fr; gap:12px; padding-top:10px; }
    .sortir-metadata-fields label { display:block; margin-bottom:6px; color:#6b7280; }
    .sortir-metadata-fields input { width:100%; min-width:0; padding:9px; min-height:40px; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb; color:#374151; }
    .sortir-items-card { display:flex; flex:1; min-height:280px; flex-direction:column; overflow:hidden; }
    .sortir-items-header { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px 20px; border-bottom:1px solid #f3f4f6; flex:none; }
    .sortir-items-header h1 { margin:0; font-size:14px; font-weight:800; color:#111827; }
    .sortir-add-button { border:1px solid #a7f3d0; border-radius:8px; background:#ecfdf5; color:#0b595b; padding:8px 12px; font-size:12px!important; font-weight:700!important; min-height:36px; white-space:nowrap; }
    .sortir-items-body { padding:16px; overflow-y:auto; min-height:0; flex:1; display:flex; flex-direction:column; }
    .sortir-empty { display:flex; flex:1; min-height:180px; align-items:center; justify-content:center; flex-direction:column; text-align:center; padding:24px 8px; }
    .sortir-empty h2 { font-size:14px; font-weight:700; margin:0; line-height:1.5; }
    .sortir-empty p { font-size:12px; line-height:1.7; color:#6b7280; margin:6px 0 0; }
    .sortir-empty button { background:none; padding:0; border:0; color:#0b595b; font-weight:600; }
    .sortir-cart-item { width:100%; display:flex; flex-direction:column; gap:7px; border:0; border-bottom:1px solid #f3f4f6; border-radius:8px; background:#fff; text-align:left; padding:13px 8px; }
    .sortir-cart-item:hover { background:#f9fafb; }
    .sortir-cart-item:last-child { border-bottom:0; }
    .sortir-cart-line { display:flex; justify-content:space-between; align-items:baseline; gap:12px; }
    .sortir-cart-line strong { color:#111827; font-size:14px; overflow-wrap:anywhere; }
    .sortir-cart-line b { flex:none; color:#0b595b; font-size:13px; }
    .sortir-cart-line small { color:#6b7280; font-size:12px; }
    .sortir-cart-line small:last-child { flex:none; font-weight:600; }
    .sortir-summary { flex:none; background:#fff; border-top:1px solid #f3f4f6; padding:14px 16px calc(16px + env(safe-area-inset-bottom, 0px)); box-shadow:0 -4px 20px rgba(0,0,0,.03); }
    .sortir-summary-inner { max-width:896px; width:100%; margin:auto; }
    .sortir-summary-total { display:flex; align-items:center; justify-content:space-between; gap:12px; padding-bottom:14px; border-bottom:1px solid #f3f4f6; }
    .sortir-summary-total > div { display:flex; align-items:center; gap:8px; }
    .sortir-summary-total h2 { margin:0; font-size:16px; font-weight:800; color:#111827; line-height:1.4; }
    .sortir-count { border-radius:20px; background:#d1fae5; color:#065f46; font-size:12px; font-weight:800; padding:3px 9px; }
    .sortir-summary-total strong { color:#0b595b; font-size:17px; font-weight:800; white-space:nowrap; }
    .sortir-summary-actions { display:flex; gap:12px; padding-top:14px; }
    .sortir-action { display:flex; align-items:center; justify-content:center; flex:1; min-height:44px; border:1px solid #e5e7eb; border-radius:12px; padding:10px 12px; font-size:12px!important; font-weight:700!important; background:#fff; color:#1f2937; text-align:center; text-decoration:none; }
    .sortir-action-primary { color:#fff; border-color:#0b595b; background:#0b595b; box-shadow:0 4px 10px rgba(11,89,91,.15); }
    .sortir-action-primary:hover { color:#fff; background:#08484a; }
    .sortir-action-danger { color:#dc2626; background:#fef2f2; border-color:#fecaca; flex:0 0 auto; }
    .sortir-error { font-size:12px; line-height:1.6; color:#b91c1c; margin:10px 0 0; }
    dialog.sortir-modal { position:fixed; inset:0; width:calc(100% - 32px); max-width:512px; max-height:calc(100dvh - 32px); margin:auto; border:0; padding:0; background:transparent; overflow:visible; color:#1f2937; }
    dialog.sortir-modal::backdrop { background:rgba(0,0,0,.4); }
    .sortir-modal .modal-dialog { width:100%; margin:0; display:flex; align-items:center; }
    .sortir-modal .modal-content { width:100%; margin:0; border:1px solid #e5e7eb; border-radius:24px; background:#fff; box-shadow:0 20px 45px rgba(0,0,0,.2); max-height:calc(100dvh - 32px); overflow:hidden; display:flex; flex-direction:column; }
    .sortir-modal .modal-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:20px 20px 16px; border-bottom:1px solid #f3f4f6; background:#f9fafb; flex:none; }
    .sortir-modal .modal-header h2 { margin:0; color:#111827; font-size:17px; line-height:1.4; font-weight:700; }
    .sortir-modal .modal-header p { color:#6b7280; margin:4px 0 0; font-size:12px; line-height:1.5; }
    .sortir-close { width:36px; height:36px; flex:none; border:0; border-radius:50%; color:#6b7280; background:#fff; font-size:17px!important; }
    .sortir-modal .modal-body { overflow-y:auto; padding:18px 16px; }
    .sortir-modal-label { display:block; margin:0 0 7px; color:#6b7280; font-size:11px; font-weight:700; letter-spacing:.025em; text-transform:uppercase; }
    .sortir-product-picker { overflow:hidden; border:1px solid #e5e7eb; border-radius:12px; background:#fff; }
    .sortir-search-wrap { display:flex; align-items:center; padding-left:12px; border-bottom:1px solid #f3f4f6; }
    .sortir-search-wrap i { font-size:14px; color:#9ca3af; flex:none; }
    .sortir-product-search, .sortir-product-search:focus, .sortir-product-search:active, .sortir-product-search:focus-visible { appearance:none; width:100%; padding:12px 10px!important; font-size:13px!important; border:0!important; outline:0!important; box-shadow:none!important; background:transparent!important; color:#1f2937; min-width:0; }
    .sortir-product-search::placeholder { color:#6b7280; }
    .sortir-product-options { height:132px; overflow-y:auto; overscroll-behavior:contain; scrollbar-width:thin; scrollbar-color:#d1d5db transparent; }
    .sortir-product-option { display:flex; align-items:center; gap:12px; padding:10px 14px; min-height:44px; border-bottom:1px solid #f3f4f6; cursor:pointer; margin:0; }
    .sortir-product-option:hover, .sortir-product-option.is-selected { background:#f9fafb; }
    .sortir-product-option:last-child { border:0; }
    .sortir-product-option input[type=radio] { appearance:none; width:12px; height:12px; min-width:12px; border:1.5px solid #9ca3af; border-radius:50%; margin:0; padding:0; background:#fff; flex:none; cursor:pointer; }
    .sortir-product-option input[type=radio]:checked { border:3.5px solid #10b981; }
    .sortir-product-option span { font-size:12px; color:#1f2937; line-height:1.5; }
    .sortir-products-state { min-height:130px; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:8px; text-align:center; color:#6b7280; font-size:12px; padding:16px; }
    .sortir-products-state button { border:0; background:none; color:#0b595b; text-decoration:underline; }
    .sortir-modal-fields { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:12px; margin-top:18px; }
    .sortir-modal-input { width:100%; min-width:0; height:44px; padding:10px 14px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; color:#1f2937; font-size:14px!important; font-weight:600!important; }
    .sortir-stock { color:#6b7280; font-size:11px; margin:7px 0 0; }
    .sortir-modal-total { display:flex; justify-content:space-between; align-items:center; gap:12px; background:#f0fdf8; border:1px solid #a7f3d0; border-radius:12px; padding:14px 12px; color:#065f46; font-size:12px; font-weight:600; margin-top:16px; }
    .sortir-modal-total strong { font-size:15px; color:#0b595b; }
    .sortir-modal .modal-footer { display:flex; flex-wrap:nowrap; gap:12px; padding:16px; border-top:1px solid #f3f4f6; background:#f9fafb; flex:none; }
    .sortir-modal .modal-footer > * { margin:0; }
    @media(min-width:640px) { .sortir-canvas { padding:24px; } .sortir-branch-card { padding:20px; } .sortir-items-header { padding:16px 24px; } .sortir-items-body { padding:20px 24px; } .sortir-summary { padding:16px 24px 20px; } .sortir-summary-total h2, .sortir-summary-total strong { font-size:18px; } .sortir-action { font-size:13px!important; } .sortir-modal .modal-body { padding:20px; } }
    @media(max-width:359px) { .sortir-canvas { padding:16px 12px; gap:16px; } .sortir-summary { padding-left:12px; padding-right:12px; } .sortir-summary-total h2 { font-size:14px; } .sortir-summary-actions { gap:8px; } .sortir-action { padding:10px 7px; font-size:11px!important; } .sortir-items-header { padding:14px; } }
    @media(prefers-reduced-motion:reduce) { .sortir-form *, .sortir-modal * { transition:none!important; } }
</style>
