@extends('template.root')
@section('title', 'Stok Opname')

@section('content')
    <div class="pos-index-page stock-opname-page">
        <style>
            .stock-opname-page #transaction-table { table-layout:fixed; }
            .stock-opname-page .so-card { min-width:0; max-width:100%; }
            .stock-opname-page .so-card-actions { row-gap:12px; }
            @media(max-width:767px) { .stock-opname-page .so-card { padding:20px; } }

            .pos-index-page .pos-index-search {
                width: 100%;
                max-width: 320px;
            }

            .pos-index-page .pos-index-search .form-control {
                height: 44px;
                border-radius: 12px;
            }

            .pos-index-page .pos-index-filter-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                min-height: 44px;
                border-radius: 12px;
                white-space: nowrap;
            }

            .pos-index-page #active-branch-button-label {
                display: inline-block;
                max-width: 160px;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: middle;
            }

            @media (max-width: 767.98px) {
                .pos-index-page .pos-index-search {
                    max-width: 100%;
                }

                .pos-index-page .pos-index-filter-toolbar {
                    width: 100%;
                    justify-content: flex-start !important;
                }

                .pos-index-page .pos-index-filter-btn {
                    width: 100%;
                }
            }

            .stock-opname-modal .modal-content {
                border-radius: 28px;
                border: 0;
                overflow: hidden;
            }

            .stock-opname-modal .modal-header {
                border-bottom: 1px solid #eef1f7;
                padding: 1.25rem 1.5rem;
            }

            .stock-opname-modal .modal-title {
                color: #1f2937;
                font-size: 1.45rem;
                letter-spacing: -0.01em;
            }

            .stock-opname-modal .btn-close-soft {
                width: 40px;
                height: 40px;
                border: 0;
                border-radius: 50%;
                background: #eef1f7;
                color: #6b7280;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .stock-opname-modal .modal-body {
                padding: 1.5rem;
                background: #fbfcff;
            }

            .stock-opname-modal .time-card {
                border: 1px solid var(--erp-border);
                border-radius: 14px;
                background: var(--erp-primary-soft);
                padding: 1rem;
                margin-bottom: 1.5rem;
            }

            .stock-opname-modal .time-title {
                color: var(--erp-primary);
                font-weight: 700;
            }

            .stock-opname-modal .time-subtitle {
                color: var(--erp-primary);
                font-size: 0.9rem;
            }

            .stock-opname-modal .stock-box {
                border: 1px solid #d9dde6;
                border-radius: 12px;
                background: #f2f3f7;
                min-height: 56px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.75rem 0.95rem;
                font-weight: 700;
                color: #5f6878;
            }

            .stock-opname-modal .stock-box.stock-input {
                background: #fff;
                border: 2px solid var(--erp-primary);
                color: #111827;
            }

            .stock-opname-modal .stock-box input {
                border: 0;
                background: transparent;
                width: 100%;
                text-align: center;
                font-size: 1.35rem;
                font-weight: 800;
                color: inherit;
                outline: none;
            }

            .stock-opname-modal .summary-card {
                border: 1px solid #f3c5cb;
                border-radius: 12px;
                background: #fff6f7;
                color: #b4232d;
                padding: 0.95rem;
            }

            .stock-opname-modal .summary-card.summary-positive {
                border-color: #b8e6c9;
                background: #f0fdf4;
                color: #067647;
            }

            .stock-opname-modal .summary-value {
                font-size: 1.95rem;
                font-weight: 800;
                line-height: 1;
            }

            .stock-opname-modal .modal-footer {
                border-top: 1px solid #eef1f7;
                background: #fff;
                padding: 1rem 1.5rem 1.35rem;
            }

            .stock-opname-modal .btn-submit-stock {
                width: 100%;
                border-radius: 12px;
                min-height: 52px;
                font-weight: 700;
                font-size: 1.08rem;
            }

            .so-card {
                background: #fff;
                border-radius: 16px;
                padding: 1.5rem;
                margin-bottom: 1.25rem;
                border: 1px solid rgba(229, 231, 235, 0.7);
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.04);
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .so-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            }

            .so-card-title {
                font-size: 1.15rem;
                font-weight: 800;
                color: #111827;
                line-height: 1.3;
                letter-spacing: -0.015em;
            }

            .so-card-code {
                font-size: 11px;
                font-weight: 700;
                background: #f3f4f6;
                color: #4b5563;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                padding: 0.2rem 0.5rem;
                white-space: nowrap;
            }

            .so-meta-item {
                font-size: 12.5px;
                font-weight: 500;
                color: #6b7280;
            }

            .so-time-row {
                font-size: 12.5px;
                font-weight: 500;
                color: #6b7280;
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
            }

            .so-creator-avatar {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid #e5e7eb;
            }

            .so-stock-box-container {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                width: 100%;
            }

            @media (min-width: 992px) {
                .so-stock-box-container {
                    width: auto;
                }
            }

            .so-stock-box {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                flex: 1;
                height: 64px;
                border-radius: 10px;
                border: 1px solid #e5e7eb;
                background: #f9fafb;
                transition: all 0.2s ease;
            }

            @media (min-width: 992px) {
                .so-stock-box {
                    flex: none;
                    width: 78px;
                }
            }

            .so-stock-box.is-active {
                border: 1.5px solid var(--erp-border);
                background: var(--erp-primary-soft);
                cursor: pointer;
            }

            .so-stock-box.is-active:hover {
                border-color: var(--erp-primary);
                background: var(--erp-primary-soft);
                transform: translateY(-1px);
            }

            .so-stock-box.is-surplus {
                background-color: #ecfdf5;
                border-color: #c2f0d5;
            }

            .so-stock-box.is-surplus .so-stock-label,
            .so-stock-box.is-surplus .so-stock-number {
                color: #059669;
            }

            .so-stock-box.is-loss {
                background-color: #fef2f2;
                border-color: #fcd2d2;
            }

            .so-stock-box.is-loss .so-stock-label,
            .so-stock-box.is-loss .so-stock-number {
                color: #dc2626;
            }

            .so-stock-label {
                font-size: 9px;
                font-weight: 700;
                color: #8892a2;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                margin-bottom: 0.15rem;
            }

            .so-stock-label.is-primary {
                color: var(--erp-primary);
            }

            .so-stock-number {
                font-size: 16px;
                font-weight: 800;
                color: #1f2937;
            }

            .so-stock-number.is-primary {
                color: var(--erp-primary);
            }

            .so-match-section {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                min-width: 110px;
            }

            @media (min-width: 992px) {
                .so-match-section {
                    align-items: flex-end;
                }
            }

            .so-match-label {
                font-size: 9px;
                font-weight: 700;
                color: #8892a2;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                margin-bottom: 0.15rem;
            }

            .so-match-value {
                font-size: 16px;
                font-weight: 800;
            }

            .so-mobile-match-bar {
                width: 100%;
                min-height: 48px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.05em;
                padding: 0.75rem 1rem;
            }

            .so-mobile-match-bar.is-surplus {
                background-color: #ecfdf5;
                border: 1px solid #c2f0d5;
                color: #059669;
            }

            .so-mobile-match-bar.is-loss {
                background-color: #fef2f2;
                border: 1px solid #fcd2d2;
                color: #dc2626;
            }

            .so-mobile-match-bar.is-match {
                background-color: #f9fafb;
                border: 1px solid #e5e7eb;
                color: #1f2937;
            }

            .so-mobile-divider {
                width: 100%;
                height: 1px;
                background-color: #f1f3f7;
            }

            .so-divider {
                width: 1px;
                height: 28px;
                background-color: #e5e7eb;
            }

            .so-btn-icon-clean {
                position: relative;
                background: transparent;
                border: 0;
                color: #6b7280;
                width: 36px;
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                transition: all 0.2s ease;
                cursor: pointer;
            }

            .so-btn-icon-clean:hover {
                background-color: #f3f4f6;
                color: #111827;
            }

            .so-btn-icon-clean.is-blue {
                color: var(--erp-primary, #0b595b);
            }

            .so-btn-icon-clean.is-blue:hover {
                background-color: var(--erp-primary-soft);
                color: var(--erp-primary-hover);
            }

            .so-btn-quick-adj {
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                transition: all 0.2s ease;
                cursor: pointer;
                font-size: 10px;
                font-weight: 700;
                gap: 0.25rem;
                padding: 0.5rem 0.65rem;
                border: 1px solid transparent;
                white-space: nowrap;
            }

            .so-btn-quick-adj svg {
                width: 14px;
                height: 14px;
                flex-shrink: 0;
            }

            @media (min-width: 992px) {
                .so-btn-quick-adj {
                    width: 36px;
                    height: 36px;
                    padding: 0;
                }

                .so-btn-quick-adj svg {
                    width: 16px;
                    height: 16px;
                }
            }

            .so-btn-quick-adj.is-up {
                background-color: #ecfdf5;
                border: 1px solid #a7f3d0;
                color: #059669;
            }

            .so-btn-quick-adj.is-up:hover {
                background-color: #d1fae5;
                border-color: #34d399;
                color: #047857;
            }

            .so-btn-quick-adj.is-down {
                background-color: #fef2f2;
                border: 1px solid #fecaca;
                color: #dc2626;
            }

            .so-btn-quick-adj.is-down:hover {
                background-color: #fee2e2;
                border-color: #f87171;
                color: #b91c1c;
            }

            .so-btn-delete-clean {
                background: transparent;
                border: 0;
                color: #9ca3af;
                width: 36px;
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                transition: all 0.2s ease;
                cursor: pointer;
            }

            .so-btn-delete-clean:hover {
                background-color: #fef2f2;
                color: #ef4444;
            }

            #transaction-table tbody tr td {
                border: 0;
                padding: 0;
                background: transparent;
            }

            /* History Timeline Drawer Styles */
            .history-card {
                background: #fff;
                border: 1px solid #f1f3f7;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
                border-radius: 14px;
                padding: 1.15rem;
                margin-bottom: 0.5rem;
                position: relative;
            }

            .last-border-none:last-child {
                border-bottom: none !important;
                padding-bottom: 0 !important;
                margin-bottom: 0 !important;
            }

            .history-action-badge {
                font-size: 10px;
                font-weight: 700;
                border-radius: 6px;
                padding: 0.2rem 0.5rem;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            .history-action-badge.is-initial {
                background-color: var(--erp-info-soft);
                color: var(--erp-info);
            }

            .history-action-badge.is-update {
                background-color: #fff7ed;
                color: #ea580c;
            }

            .history-action-badge.is-kredit,
            .history-qty-badge.is-kredit {
                background-color: #ecfdf5;
                color: #059669;
            }

            .history-action-badge.is-debit,
            .history-qty-badge.is-debit {
                background-color: #fef2f2;
                color: #dc2626;
            }

            .history-time-text {
                font-size: 11.5px;
                color: #8892a2;
                font-weight: 500;
            }

            .history-note-text {
                font-size: 13.5px;
                font-weight: 700;
                color: #1f2937;
                line-height: 1.4;
            }

            .history-qty-badge {
                font-size: 10.5px;
                font-weight: 800;
                background-color: #eff6ff;
                color: #2563eb;
                border-radius: 6px;
                padding: 0.25rem 0.45rem;
                white-space: nowrap;
            }

            @media (min-width: 992px) {
                .history-qty-badge {
                    font-size: 12px;
                    border-radius: 8px;
                    padding: 0.35rem 0.75rem;
                }
            }

            #kt_stock_opname_history {
                border-left: 1px solid rgba(229, 231, 235, 0.5);
                box-shadow: -10px 0 30px rgba(0, 0, 0, 0.03);
            }

            /* Discussion Chat Styles */
            .chat-message-container {
                display: flex;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }

            .chat-message-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid #e5e7eb;
                flex-shrink: 0;
            }

            .chat-message-bubble {
                background-color: #f3f4f6;
                color: #1f2937;
                border-radius: 12px;
                padding: 0.75rem 1rem;
                font-size: 13.5px;
                line-height: 1.4;
                position: relative;
            }

            .chat-message-container.is-self {
                flex-direction: row-reverse;
            }

            .chat-message-container.is-self .chat-message-bubble {
                background-color: var(--erp-primary-soft);
                color: var(--erp-primary);
                border-top-right-radius: 4px;
            }

            .chat-message-container:not(.is-self) .chat-message-bubble {
                border-top-left-radius: 4px;
                background-color: #f3f4f6;
                color: #1f2937;
            }

            .chat-message-header {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 0.15rem;
            }

            .chat-message-author {
                font-size: 11px;
                font-weight: 700;
                color: #4b5563;
            }

            .chat-message-container.is-self .chat-message-author {
                color: var(--erp-primary);
            }

            .chat-message-time {
                font-size: 9.5px;
                color: #9ca3af;
                font-weight: 500;
            }

            #kt_stock_opname_discussion {
                border-left: 1px solid rgba(229, 231, 235, 0.5);
                box-shadow: -10px 0 30px rgba(0, 0, 0, 0.03);
            }

            #kt_stock_opname_discussion_footer {
                background-color: #fbfcff;
                border-top: 1px solid #eef1f7;
            }

            /* Infruity inventory theme: compact, warm-neutral, green-led. */
            .stock-opname-page { font-family:'Plus Jakarta Sans',sans-serif; color:#1f2937; }
            .stock-opname-page > .card { border:1px solid #f1f3f5!important; border-radius:16px; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.04),0 6px 16px rgba(0,0,0,.04); }
            .stock-opname-page .card-header { border-color:#f1f3f5; }
            .stock-opname-page .pos-index-search .form-control { height:44px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; font-family:inherit; font-size:14px; }
            .stock-opname-page .pos-index-filter-btn { min-height:42px; border-radius:12px; font-family:inherit; font-size:12px; font-weight:600; }
            .stock-opname-page .btn-light-primary { border:1px solid #d5e8df; background:#edf7f2; color:#0b595b; }
            .stock-opname-page .btn-light-primary:hover { background:#e1f2e9; color:#0b595b; }
            .stock-opname-page .btn-primary { border-color:#0b595b; background:#0b595b; }
            .stock-opname-page .btn-primary:hover { border-color:#08484a; background:#08484a; }
            .stock-opname-page .stock-opname-add { position:fixed; right:24px; bottom:24px; z-index:1050; display:flex; width:52px; height:52px; align-items:center; justify-content:center; border:0; border-radius:17px; background:#0b595b; box-shadow:0 8px 20px rgba(11,89,91,.24); color:#fff; }
            .stock-opname-page .stock-opname-add:hover { background:#08484a; color:#fff; }
            .stock-opname-page .menu-sub-dropdown { overflow:hidden; border:1px solid #edf0f2; border-radius:16px; box-shadow:0 12px 32px rgba(15,23,42,.12); }
            .stock-opname-page .stock-opname-modal .modal-content { border-radius:24px; }
            .stock-opname-page .stock-opname-modal .modal-header { padding:12px 20px; border-color:#f1f3f5; }
            .stock-opname-page .stock-opname-modal .modal-title { color:#1f2937; font-size:16px; letter-spacing:0; }
            .stock-opname-page .stock-opname-modal .btn-close-soft { width:32px; height:32px; background:#f3f4f6; }
            .stock-opname-page .stock-opname-modal .modal-body { padding:20px; background:#fff; }
            .stock-opname-page .stock-opname-modal .time-card { border-color:#cfe8da; background:#edf7f2; }
            .stock-opname-page .stock-opname-modal .time-title,
            .stock-opname-page .stock-opname-modal .time-subtitle { color:#0b595b; }
            .stock-opname-page .stock-opname-modal .stock-box.stock-input { border-color:#0b595b; box-shadow:0 0 0 3px rgba(11,89,91,.08); }
            .stock-opname-page .so-stock-box.is-active { border-color:#cfe8da; background:#edf7f2; }
            .stock-opname-page .so-stock-box.is-active:hover { border-color:#0b595b; background:#e1f2e9; }
            .stock-opname-page .so-stock-label.is-primary,
            .stock-opname-page .so-stock-number.is-primary { color:#0b595b; }
            .stock-opname-page .so-btn-icon-clean.is-blue { color:#0b595b; }
            .stock-opname-page .so-btn-icon-clean.is-blue:hover { background:#edf7f2; color:#08484a; }
            .stock-opname-page .chat-message-container.is-self .chat-message-bubble { background:#edf7f2; color:#164e42; }
            .stock-opname-page .chat-message-container.is-self .chat-message-author { color:#0b595b; }
            .stock-opname-page #kt_stock_opname_discussion_footer { background:#f9fafb; border-color:#f1f3f5; }
            .stock-opname-page .history-action-badge.is-initial { background:#edf7f2; color:#0b595b; }
            .stock-opname-page .history-qty-badge { border-radius:999px; background:#edf7f2; color:#0b595b; }
            @media (max-width:767.98px) {
                .stock-opname-page { padding-bottom:calc(76px + env(safe-area-inset-bottom)); }
                .stock-opname-page > .card { min-height:100%; border:0!important; border-radius:0; box-shadow:none; }
                .stock-opname-page .card-header { gap:10px!important; padding:12px 16px!important; }
                .stock-opname-page .pos-index-search .form-control { height:44px; font-size:14px; }
                .stock-opname-page .pos-index-filter-toolbar { display:grid!important; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px!important; }
                .stock-opname-page .pos-index-filter-btn { width:auto; min-width:0; min-height:40px; padding:0 8px!important; }
                .stock-opname-page .pos-index-filter-btn i { font-size:16px!important; }
                .stock-opname-page #active-branch-button-label { max-width:90px; }
                .stock-opname-page .stock-opname-add { right:16px; bottom:calc(84px + env(safe-area-inset-bottom)); width:48px; height:48px; border-radius:16px; }
                .stock-opname-page .card-body { padding:0 16px 16px!important; }
                .stock-opname-page .stock-opname-modal .modal-dialog { margin:12px; }
                .stock-opname-page .stock-opname-modal .modal-body { padding:16px; }
            }

            .stock-opname-page .so-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:12px 8px; border-top:1px solid #f1f3f5; cursor:pointer; transition:background .15s ease; }
            .stock-opname-page .so-row:hover, .stock-opname-page .so-row:focus-visible { background:#f3faf7; outline:none; }
            #transaction-table tbody tr:first-child .so-row { border-top:0; }
            .so-row__main { min-width:0; flex:1; }
            .so-row__title { display:flex; align-items:center; gap:8px; min-width:0; }
            .so-row__name { overflow:hidden; color:#111827; font-size:13px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
            .so-row__status { flex:none; border-radius:999px; background:#fffbeb; padding:1px 7px; color:#b45309; font-size:10px; font-weight:700; }
            .so-row__time { display:block; margin-top:2px; overflow:hidden; color:#9ca3af; font-size:11px; text-overflow:ellipsis; white-space:nowrap; font-variant-numeric:tabular-nums; }
            .so-row__side { display:flex; flex:none; flex-direction:column; align-items:flex-end; gap:2px; text-align:right; font-variant-numeric:tabular-nums; }
            .so-row__diff { font-size:13px; font-weight:700; color:#9ca3af; }
            .so-row__diff small { font-size:11px; font-weight:500; opacity:.8; }
            .so-row__diff.is-surplus, .so-row__value.is-surplus { color:#047857; }
            .so-row__diff.is-loss, .so-row__value.is-loss { color:#be123c; }
            .so-row__value { font-size:11px; font-weight:500; }
            .so-detail .modal-content { overflow:hidden; border:0; border-radius:20px; box-shadow:0 24px 72px rgba(11,22,31,.25); }
            .so-detail__head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:20px 22px 8px; }
            .so-detail__heading { min-width:0; }
            .so-detail__title { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
            .so-detail__title h2 { margin:0; color:#1f2937; font-size:17px; font-weight:800; overflow-wrap:anywhere; }
            .so-detail__status { border-radius:999px; background:#f0fdf4; padding:2px 9px; color:#166534; font-size:10px; font-weight:700; }
            .so-detail__status.is-pending { background:#fffbeb; color:#b45309; }
            .so-detail__heading p { margin:4px 0 0; color:#9ca3af; font-size:11px; }
            .so-detail .btn-close-soft { display:inline-flex; align-items:center; justify-content:center; flex:none; border:0; border-radius:50%; background:#f3f4f6; color:#6b7280; width:32px; height:32px; }
            .so-detail__body { display:grid; gap:14px; padding:12px 22px 22px; }
            .so-detail__boxes { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
            .so-detail__box { display:flex; flex-direction:column; gap:2px; border:1px solid #e5e7eb; border-radius:12px; padding:10px; background:#f9fafb; }
            .so-detail__box span { color:#9ca3af; font-size:10px; font-weight:700; text-transform:uppercase; }
            .so-detail__box strong { color:#111827; font-size:13px; font-variant-numeric:tabular-nums; overflow-wrap:anywhere; }
            .so-detail__box.is-primary { border-color:#cfe8da; background:#edf7f2; }
            .so-detail__box.is-primary span, .so-detail__box.is-primary strong { color:#0b595b; }
            .so-detail__box.is-surplus strong { color:#047857; }
            .so-detail__box.is-loss strong { color:#be123c; }
            .so-detail__value { display:flex; align-items:center; justify-content:space-between; gap:12px; border-radius:12px; background:#f9fafb; padding:12px 14px; }
            .so-detail__value span { display:block; color:#374151; font-size:12px; font-weight:700; }
            .so-detail__value small { color:#9ca3af; font-size:11px; }
            .so-detail__value strong { color:#111827; font-size:15px; font-variant-numeric:tabular-nums; }
            .so-detail__value strong.is-surplus { color:#047857; }
            .so-detail__value strong.is-loss { color:#be123c; }
            .so-detail__adjust { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
            .so-detail__btn { display:inline-flex; min-height:44px; align-items:center; justify-content:center; gap:6px; border:1px solid #e5e7eb; border-radius:12px; background:#fff; font-size:13px; font-weight:700; }
            .so-detail__btn.is-up { color:#047857; border-color:#a7f3d0; background:#ecfdf5; }
            .so-detail__btn.is-down { color:#be123c; border-color:#fecdd3; background:#fff1f2; }

            .stock-opname-modal .so-form-label { color:#374151; font-size:12px; font-weight:700; }
            .stock-opname-modal .so-form-hint { color:#9ca3af; font-size:11px; }
            .stock-opname-modal .so-form-panel { display:grid; grid-template-columns:1fr 1fr; gap:16px; border:1px solid #e5e7eb; border-radius:14px; background:#f9fafb; padding:14px 16px; }
            .stock-opname-modal .so-form-panel > div + div { border-left:1px solid #e5e7eb; padding-left:16px; }
            .stock-opname-modal .so-form-panel__label { display:block; margin-bottom:6px; color:#9ca3af; font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
            .stock-opname-modal .so-form-panel__input .form-control { background:#fff; border-radius:10px; font-size:1rem; }
            .stock-opname-modal .so-form-panel__value { display:block; margin-top:8px; color:#0b595b; font-size:1rem; font-weight:800; font-variant-numeric:tabular-nums; }
            .stock-opname-modal .so-form-panel small { display:block; margin-top:6px; color:#9ca3af; font-size:10px; }
            .stock-opname-modal .so-form-physical { min-height:46px; border:1px solid #d1d5db; border-radius:12px; font-size:1.05rem; }
            .stock-opname-modal .so-form-physical:focus { border-color:#0b595b; box-shadow:0 0 0 1px #0b595b; }
            .stock-opname-modal .so-form-diff { border:1px solid #e5e7eb; border-radius:12px; padding:12px 14px; background:#f9fafb; color:#374151; font-variant-numeric:tabular-nums; }
            .stock-opname-modal .so-form-diff__row { display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:12px; }
            .stock-opname-modal .so-form-diff__row strong { font-size:15px; font-weight:800; }
            .stock-opname-modal .so-form-diff__row.is-sub { margin-top:8px; border-top:1px solid #e5e7eb; padding-top:8px; }
            .stock-opname-modal .so-form-diff__row.is-sub strong { font-size:13px; }
            .stock-opname-modal .so-form-diff.is-surplus { border-color:#a7f3d0; background:#ecfdf5; color:#065f46; }
            .stock-opname-modal .so-form-diff.is-loss { border-color:#fecdd3; background:#fff1f2; color:#9f1239; }
            .stock-opname-modal .so-form-diff.is-surplus .is-sub, .stock-opname-modal .so-form-diff.is-loss .is-sub { border-color:currentColor; }
            @media (max-width:575px) { .stock-opname-modal .so-form-panel { grid-template-columns:1fr; } .stock-opname-modal .so-form-panel > div + div { border-left:0; border-top:1px solid #e5e7eb; padding:12px 0 0; } }

            .so-hist-tabs { display:inline-flex; margin-right:8px; border:1px solid #e5e7eb; border-radius:999px; background:#f9fafb; padding:3px; }
            .so-hist-tab { border:0; border-radius:999px; background:transparent; padding:5px 14px; color:#6b7280; font-size:12px; font-weight:700; }
            .so-hist-tab.is-active { background:#0b595b; color:#fff; }
            .so-hist-head { display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #f1f3f5; padding:8px 4px; color:#9ca3af; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
            .so-hist-head__side { display:flex; gap:4px; }
            .so-hist-col { min-width:58px; text-align:right; font-variant-numeric:tabular-nums; }
            .so-hist-reason-head { flex:1; }
            .so-hist-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; border-bottom:1px solid #f1f3f5; padding:12px 4px; }
            .so-hist-main { display:flex; min-width:0; flex-direction:column; gap:2px; }
            .so-hist-kind { color:#374151; font-size:12px; font-weight:700; }
            .so-hist-kind.is-surplus { color:#047857; }
            .so-hist-kind.is-loss { color:#be123c; }
            .so-hist-time, .so-hist-by { color:#9ca3af; font-size:11px; }
            .so-hist-side { display:flex; flex:1; justify-content:flex-end; gap:4px; font-size:13px; }
            .so-hist-side .so-hist-col { color:#374151; }
            .so-hist-side .so-hist-col.is-strong { color:#111827; font-weight:700; }
            .so-hist-side .so-hist-col.is-surplus { color:#047857; font-weight:700; }
            .so-hist-side .so-hist-col.is-loss { color:#be123c; font-weight:700; }
            .so-hist-reason { max-width:240px; color:#4b5563; font-size:12px; line-height:1.5; text-align:right; overflow-wrap:anywhere; }
            .so-hist-details { display:grid; grid-template-columns:1fr auto; gap:2px 10px; margin-top:6px; border-radius:8px; background:#f9fafb; padding:6px 8px; color:#6b7280; font-size:11px; text-align:left; }
            .so-hist-empty { padding:32px 0; color:#9ca3af; text-align:center; font-size:13px; }
        </style>
        <link rel="stylesheet" href="{{ asset('assets/css/stock-opname-reference.css') }}?v={{ filemtime(public_path('assets/css/stock-opname-reference.css')) }}">
        <x-erp.workspace class="so-workspace" title="Stok Opname" subtitle="Cocokkan stok fisik dan stok sistem."><x-slot:toolbar>
            <div class="erp-search so-desktop-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search-desktop" data-so-search placeholder="Cari produk…" aria-label="Cari produk"></div>
            <x-erp.mobile-search class="so-mobile-search"><x-slot:input><input type="search" id="search" data-so-search class="erp-mobile-search__input" placeholder="Cari produk…" aria-label="Cari produk"></x-slot:input><x-slot:clear><button type="button" id="so-search-clear" class="erp-mobile-search__clear" aria-label="Hapus pencarian" hidden><i class="ph ph-x" aria-hidden="true"></i></button></x-slot:clear><x-slot:filter><button type="button" class="erp-mobile-search__filter" data-so-open-filter aria-label="Filter stok opname" aria-haspopup="dialog" aria-controls="so-filter-sheet"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i></button></x-slot:filter></x-erp.mobile-search>
            <button type="button" class="erp-action so-desktop-filter" data-so-open-filter aria-controls="so-filter-sheet"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="active-branch-button-label">Filter</span></button>
            <div hidden><select data-kt-ecommerce-product-filter="cabang"><option value="all">Semua Cabang</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>@endforeach</select><input type="hidden" id="kt_ecommerce_sales_flatpickr"></div>
            @if(check_access('stock-opname.create'))<button type="button" class="erp-action erp-action--primary so-desktop-create" data-bs-toggle="modal" data-bs-target="#kt_modal_add_customer"><i class="ph ph-plus" aria-hidden="true"></i>Tambah stok opname</button>@endif
        </x-slot:toolbar><div class="erp-table-region"><table class="table fs-6" id="transaction-table" width="100%">
                    <thead class="d-none">
                        <tr>
                            <th>Transaksi</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table></div></x-erp.workspace>
    </div>

    <div class="modal fade stock-opname-modal" id="kt_modal_add_customer" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog erp-sheet-dialog modal-lg">
            <!--begin::Modal content-->
            <div class="modal-content" style="border-radius: 20px;">
                <!--begin::Form-->
                <form class="form" action="{{ url(Request::segment(1)) }}" id="kt_modal_add_customer_form"
                    data-kt-redirect="#">
                    @csrf
                    <!--begin::Modal header-->
                    <div class="modal-header border-0 pb-0" id="kt_modal_add_customer_header"
                        style="background-color: #ffffff;">
                        <!--begin::Modal title-->
                        <div>
                            <h2 class="modal-title fw-bold text-dark fs-3">Catat Stock Opname</h2>
                            <span class="text-muted" style="font-size: 0.85rem;">Data awal bersifat final dan tidak dapat
                                diedit langsung.</span>
                        </div>
                        <!--end::Modal title-->
                        <!--begin::Close-->
                        <button type="button" id="kt_modal_add_customer_close" class="btn-close-soft"
                            style="background: transparent; width: 30px; height: 30px; margin-top: -10px;"
                            data-bs-dismiss="modal" aria-label="Close">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                        <!--end::Close-->
                    </div>
                    <!--end::Modal header-->
                    <!--begin::Modal body-->
                    <div class="modal-body py-5" style="background-color: #ffffff;">
                        <!--begin::Scroll-->
                        <div class="scroll-y" id="kt_modal_add_customer_scroll" data-kt-scroll="true"
                            data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_customer_header"
                            data-kt-scroll-wrappers="#kt_modal_add_customer_scroll" data-kt-scroll-offset="220px">

                            <input type="hidden" id="date" name="date" value="{{ date('Y-m-d') }}" />

                            <div class="row g-5 mb-5">
                                <div class="col-md-6 fv-row">
                                    <label class="required form-label so-form-label" for="branch_id">Cabang Pelaksanaan</label>
                                    <select class="form-select form-select-solid" name="branch_id" id="branch_id"
                                        data-placeholder="Pilih Cabang">
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 fv-row">
                                    <label class="required form-label so-form-label" for="product_id">Pilih Produk</label>
                                    <select class="form-select" style="border-color: var(--erp-border);" name="product_id"
                                        id="product_id" data-placeholder="Pilih Produk">
                                        <option value="">Pilih Product</option>
                                    </select>
                                </div>
                            </div>

                            <div class="so-form-panel mb-5">
                                <div>
                                    <span class="so-form-panel__label">Stok Sistem</span>
                                    <div class="input-group so-form-panel__input">
                                        <input type="number" step="0.01" class="form-control text-end fw-bolder border-0"
                                            name="quantity" value="0" readonly aria-label="Stok sistem saat ini" />
                                    </div>
                                    <small>Catatan buku saat ini</small>
                                </div>
                                <div>
                                    <span class="so-form-panel__label">HPP / Satuan</span>
                                    <strong class="so-form-panel__value" id="stock-hpp-value">-</strong>
                                    <small>Dipakai untuk menghitung dampak</small>
                                </div>
                            </div>

                            <div class="fv-row mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="required form-label so-form-label mb-0" for="real_stock">Stok Fisik Terhitung</label>
                                    <span class="so-form-hint">Hasil timbang aktual</span>
                                </div>
                                <input type="number" step="0.01" inputmode="decimal" id="real_stock"
                                    class="form-control text-end fw-bolder so-form-physical" name="real_stock" value="0" />
                            </div>

                            <div class="so-form-diff is-match mb-5" aria-live="polite">
                                <div class="so-form-diff__row">
                                    <span id="stock-difference-label">Stok sesuai</span>
                                    <strong id="stock-difference-text">0 Kg</strong>
                                </div>
                                <div class="so-form-diff__row is-sub">
                                    <span>Dampak Finansial</span>
                                    <strong id="stock-difference-value">Rp 0</strong>
                                </div>
                            </div>

                            <div class="fv-row mb-5">
                                <label class="form-label so-form-label">Detail Quantity</label>
                                <div id="detail-qty-container" class="d-flex flex-column gap-3">
                                </div>
                                <button type="button" class="btn btn-sm btn-light-primary mt-3"
                                    onclick="addDetailRow()">
                                    <i class="bi bi-plus"></i> Tambah Detail
                                </button>
                            </div>

                            <div class="fv-row mb-2">
                                <label class="form-label so-form-label" for="so_note">Catatan Koreksi</label>
                                <textarea class="form-control" id="so_note" style="border: 1px solid #e5e7eb; border-radius: 12px;" name="note"
                                    rows="3" placeholder="Tulis kondisi barang, temuan, atau keterangan lainnya..."></textarea>
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Scroll-->
                    </div>
                    <!--end::Modal body-->
                    <!--begin::Modal footer-->
                    <div class="modal-footer border-0 pt-0 justify-content-end gap-2" style="background-color: #ffffff;">
                        <!--begin::Button-->
                        <button type="button" class="btn btn-light btn-sm fw-bold px-6"
                            style="color: #4b5563; background-color: transparent;" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="kt_modal_add_customer_submit"
                            class="btn btn-primary btn-sm text-white fw-bold px-6" style="border-radius: 8px;">
                            <span class="indicator-label">Simpan Final</span>
                            <span class="indicator-progress">Mohon tunggu...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                        <!--end::Button-->
                    </div>
                    <!--end::Modal footer-->
                </form>
                <!--end::Form-->
            </div>
        </div>
    </div>

    @include('transaction::stock-opname.partials.reference-filters') {{-- so-filter-sheet / so-history-filter --}}

    <!--begin::Stock Adjustment Modal-->
    <div class="modal fade stock-opname-modal so-detail so-reference-sheet" id="kt_modal_stock_detail" tabindex="-1" aria-labelledby="so-detail-title" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="so-sheet-handle" aria-hidden="true"><span></span></div>
            <header class="so-sheet-header"><div><h2 id="so-detail-title" data-so-field="name"></h2><p data-so-field="branch"></p></div><button type="button" class="so-close" data-bs-dismiss="modal" aria-label="Tutup detail"><i class="ph ph-x" aria-hidden="true"></i></button></header>
            <div class="so-sheet-body">
                <div class="so-detail-meta"><div><span><i class="ph ph-clock" aria-hidden="true"></i>Waktu Dibuat</span><strong data-so-field="created"></strong></div><div><span><i class="ph ph-user" aria-hidden="true"></i>Dibuat oleh</span><strong data-so-field="creator"></strong></div></div>
                <div class="so-detail-summary"><div><span>Sistem → Fisik</span><strong><span data-so-field="system"></span><span class="so-summary-arrow"> → </span><b data-so-field="physical"></b></strong></div><div><span>HPP / Satuan</span><strong data-so-field="hpp"></strong></div><div class="so-detail-difference"><strong data-so-field="diff"></strong><strong data-so-field="value"></strong></div></div>
                <form id="so-detail-correction" novalidate>
                    <div class="so-credit-inputs"><label><span class="is-plus">+</span><input type="text" inputmode="decimal" id="so-credit-plus" placeholder="0" aria-label="Kredit lebih" autocomplete="off"><span data-so-field="unit"></span></label><label><span class="is-minus">−</span><input type="text" inputmode="decimal" id="so-credit-minus" placeholder="0" aria-label="Kredit kurang" autocomplete="off"><span data-so-field="unit"></span></label></div>
                    <label class="so-correction-label" for="so-credit-reason">Catatan Koreksi <span>*</span></label><textarea id="so-credit-reason" rows="5" maxlength="1000" placeholder="Tuliskan catatan koreksi…"></textarea>
                    <p id="so-correction-error" class="so-correction-error" role="status" aria-live="polite"></p>
                </form>

            </div>
            <footer class="so-sheet-footer so-detail-footer"><button type="button" class="so-history-button" data-so-action="history" aria-label="Riwayat stok opname"><i class="ph ph-clock-counter-clockwise" aria-hidden="true"></i><span data-so-count="history"></span></button><button type="submit" form="so-detail-correction" id="so-correction-save" disabled>Simpan Koreksi</button>@if(check_access('stock-opname.update'))<button type="button" class="is-primary" data-so-action="finalize">Selesaikan Opname</button>@endif<button type="button" class="is-primary d-none" id="so-detail-done" data-bs-dismiss="modal">Selesaikan opname</button></footer>
        </div></div>
    </div>

    <div class="modal fade stock-opname-modal" id="kt_modal_stock_adjustment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content"
                style="border-radius: 20px; border: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);">
                <form id="kt_modal_stock_adjustment_form" class="form">
                    @csrf
                    <!--begin::Modal header-->
                    <div class="modal-header d-flex align-items-center justify-content-between py-4 px-6 border-0"
                        id="adj-modal-header"
                        style="border-top-left-radius: 20px; border-top-right-radius: 20px; min-height: 60px;">
                        <h2 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="adj-modal-title"
                            style="font-size: 1.25rem;">
                            <!-- Dynamic Arrow Icon and Text -->
                        </h2>
                        <button type="button" class="btn-close-soft" data-bs-dismiss="modal" aria-label="Close"
                            style="width: 32px; height: 32px; background: rgba(0,0,0,0.05); border: 0; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;">
                            <i class="ki-outline ki-cross fs-3 text-gray-700"></i>
                        </button>
                    </div>
                    <!--end::Modal header-->
                    <!--begin::Modal body-->
                    <div class="modal-body p-6" style="background: #fbfcff;">
                        <!-- Current Stock Box -->
                        <div class="d-flex align-items-center justify-content-between p-4 mb-5"
                            style="background-color: #f9fafb; border-radius: 12px; border: 1px solid #f1f3f7;">
                            <span class="text-gray-600 fw-semibold">Stock Fisik Saat Ini:</span>
                            <span class="fs-4 fw-bold text-gray-900" id="adj-current-stock-label">0 Kg</span>
                        </div>

                        <!-- Qty Input Group -->
                        <div class="fv-row mb-5">
                            <label class="required form-label text-gray-700 fw-semibold mb-2">Qty Penyesuaian</label>
                            <div class="input-group input-group-solid rounded"
                                style="border: 1px solid #d9dde6; overflow: hidden; background: #fff;">
                                <span class="input-group-text border-0 fw-bold fs-3 justify-content-center"
                                    id="adj-type-badge" style="width: 46px;">+</span>
                                <input type="number" step="0.01"
                                    class="form-control border-0 fw-bold text-gray-900 px-3 fs-3" id="adj-qty-input"
                                    name="qty" placeholder="1" required min="0.01"
                                    style="background: transparent;">
                            </div>
                            <div class="text-muted fs-7 mt-2 text-end">
                                Menjadi: <strong class="text-gray-900" id="adj-result-stock-label">0 Kg</strong>
                            </div>
                        </div>

                        <!-- Reason Input -->
                        <div class="fv-row mb-2">
                            <label class="required form-label text-gray-700 fw-semibold mb-2">Alasan Penyesuaian</label>
                            <textarea class="form-control form-control-solid rounded-lg px-4 py-3" id="adj-reason-input" name="reason"
                                rows="3" placeholder="Contoh: Salah hitung saat audit awal" required
                                style="background-color: #f9fafb; border: 1px solid #d9dde6;"></textarea>
                        </div>
                    </div>
                    <!--end::Modal body-->
                    <!--begin::Modal footer-->
                    <div class="modal-footer border-0 d-flex justify-content-end gap-3 pt-0 pb-6 px-6"
                        style="background: #fbfcff; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                            style="border-radius: 10px; font-weight: 600; background: #f3f4f6; color: #4b5563;">Batal</button>
                        <button type="submit" class="btn btn-primary" id="adj-submit-btn"
                            style="border-radius: 10px; font-weight: 600; padding: 0.75rem 1.75rem;">Simpan</button>
                    </div>
                    <!--end::Modal footer-->
                </form>
            </div>
        </div>
    </div>
    <!--end::Stock Adjustment Modal-->

    <!--begin::Stock Opname History Drawer-->
    <div id="kt_stock_opname_history" class="so-reference-history bg-body" data-kt-drawer="true" data-kt-drawer-name="stock-opname-history"
        data-kt-drawer-activate="true" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'100%', 'md': '760px'}" data-kt-drawer-direction="end" data-kt-drawer-close="#kt_stock_opname_history_close" role="dialog" aria-modal="true" aria-hidden="true" inert aria-labelledby="so-history-title" tabindex="-1">
        <div class="so-history-page">
            <header class="so-history-topbar"><button type="button" id="kt_stock_opname_history_close" aria-label="Kembali ke detail"><i class="ph ph-caret-left" aria-hidden="true"></i></button><h2 id="so-history-title">Riwayat Stok Opname</h2><span></span></header>
            <div class="so-history-subheader"><div class="so-history-context"><div><p>Riwayat Stok Opname</p><h3 id="history-transaction-code">—</h3></div><div class="so-history-tools"><button type="button" data-so-history-toggle aria-label="Ubah tampilan menjadi Alasan" aria-pressed="false">Kredit</button><button type="button" id="so-history-filter-open" aria-label="Filter riwayat stok opname" aria-haspopup="dialog" aria-controls="so-history-filter"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i></button></div></div><div id="so-history-columns" class="so-hist-head"></div></div>
            <div id="kt_stock_opname_history_scroll" class="so-history-scroll"><div id="history-timeline-items" class="so-hist"></div></div>
        </div>
    </div>
    <!--end::Stock Opname History Drawer-->

    <div id="kt_stock_opname_discussion" class="bg-body" data-kt-drawer="true"
        data-kt-drawer-name="stock-opname-discussion" data-kt-drawer-activate="true" data-kt-drawer-overlay="true"
        data-kt-drawer-width="{default:'100%', 'md': '500px'}" data-kt-drawer-direction="end"
        data-kt-drawer-close="#kt_stock_opname_discussion_close">
        <div class="card w-100 shadow-none border-0 rounded-0 d-flex flex-column" style="height: 100vh;">
            <!--begin::Header-->
            <div class="card-header border-0 pe-5" id="kt_stock_opname_discussion_header" style="min-height: 70px;">
                <div class="card-title d-flex flex-column align-items-start">
                    <h3 class="fw-bold text-gray-900 mb-1 d-flex align-items-center gap-2">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--erp-primary, #0b595b);">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        Ruang Diskusi
                    </h3>
                    <span class="text-muted fs-7 fw-semibold" id="discussion-product-name">-</span>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-icon btn-active-light-primary me-n5"
                        id="kt_stock_opname_discussion_close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
            </div>
            <!--end::Header-->
            <!--begin::Body-->
            <div class="card-body position-relative pt-0 flex-grow-1 overflow-hidden d-flex flex-column"
                id="kt_stock_opname_discussion_body">
                <!--begin::Scroll-->
                <div id="kt_stock_opname_discussion_scroll" class="position-relative scroll-y flex-grow-1 me-n5 pe-5 mb-4"
                    data-kt-scroll="true" data-kt-scroll-height="auto"
                    data-kt-scroll-wrappers="#kt_stock_opname_discussion_body"
                    data-kt-scroll-dependencies="#kt_stock_opname_discussion_header, #kt_stock_opname_discussion_footer"
                    data-kt-scroll-offset="5px">

                    <div class="d-flex flex-column gap-4" id="discussion-items">
                        <!-- Chat messages rendered dynamically -->
                    </div>

                </div>
                <!--end::Scroll-->
            </div>
            <!--end::Body-->
            <!--begin::Footer-->
            <div class="card-footer border-0 p-5" id="kt_stock_opname_discussion_footer">
                <form id="kt_stock_opname_discussion_form" class="d-flex align-items-center gap-2">
                    <input type="hidden" id="discussion-stock-opname-id" name="stock_opname_id">
                    <input type="text" class="form-control form-control-solid rounded-pill px-4"
                        id="discussion-message-input" placeholder="Tulis pesan..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary btn-icon rounded-circle w-40px h-40px flex-shrink-0"
                        style="background-color: var(--erp-primary, #0b595b); border-color: var(--erp-primary, #0b595b);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13" />
                            <polygon points="22 2 15 22 11 13 2 9 22 2" />
                        </svg>
                    </button>
                </form>
            </div>
            <!--end::Footer-->
        </div>
    </div>
    <!--end::Stock Opname Discussion Drawer-->

@section('script')
    <script src="{{ asset('assets/js/stock-opname-reference.js') }}?v={{ filemtime(public_path('assets/js/stock-opname-reference.js')) }}"></script>
    <script type="text/javascript">
        const currentUserId = {{ Auth::user()->id_user }};
        // Data stock kosong dari server
        const emptyStockData = @json($emptyStockData ?? []);

        function showStockAlert(selectedBranchId) {
            let filteredProducts = emptyStockData;
            if (selectedBranchId && selectedBranchId !== 'all') {
                filteredProducts = emptyStockData.filter(p => p.branch_id == selectedBranchId);
            }

            if (filteredProducts.length > 0) {
                const count = filteredProducts.length;
                const badges = filteredProducts.slice(0, 10).map(p => {
                    return `<span class="badge badge-light-danger me-1 mb-1">${p.name} (${p.branch_name})</span>`;
                }).join('');

                const remainingHtml = count > 10 ?
                    `<br><small>...dan ${count - 10} produk lainnya</small>` :
                    '';

                const scopeText = (selectedBranchId && selectedBranchId !== 'all') ? 'pada cabang ini' :
                    'pada seluruh cabang';
                const alertHtml = `Terdapat <strong>${count}</strong> produk dengan stok kosong ${scopeText}.<br><br>` +
                    `<div style="text-align: left; max-height: 200px; overflow-y: auto;">` +
                    badges +
                    remainingHtml +
                    `</div>`;

                Swal.fire({
                    title: 'Peringatan Stok Minus!',
                    html: alertHtml,
                    icon: 'warning',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#dc2626',
                    allowOutsideClick: false
                });
            }
        }

        var dataTable;
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const segment1 = "{{ Request::segment(1) }}";

        function formatRupiah(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value || 0));
        }

        function updateStockSummary(unitPrice = null) {
            const systemStock = Number($('input[name="quantity"]').val() || 0);
            const realStock = Number($('input[name="real_stock"]').val() || 0);
            const difference = realStock - systemStock;
            const price = Number(unitPrice ?? $('#product_id').data('unit-price') ?? 0);
            const unit = $('#product_id').data('unit') || 'Kg';
            const value = difference * price;
            const diffRounded = Math.round(difference * 100) / 100;

            $('#stock-hpp-value').text(price > 0 ? `Rp ${formatRupiah(Math.round(price))}/${unit}` : '-');
            $('#stock-difference-label').text(diffRounded < 0 ? 'Potensi rugi' : (diffRounded > 0 ? 'Surplus stok' : 'Stok sesuai'));
            $('#stock-difference-text').text(`${diffRounded > 0 ? '+' : ''}${diffRounded} ${unit}`);
            $('#stock-difference-value').text(`${value < 0 ? '-' : (value > 0 ? '+' : '')}Rp ${formatRupiah(Math.abs(Math.round(value)))}`);

            $('.so-form-diff')
                .toggleClass('is-surplus', diffRounded > 0)
                .toggleClass('is-loss', diffRounded < 0)
                .toggleClass('is-match', diffRounded === 0);
        }

        function formatLongDateTime(dateValue) {
            return StockOpnameUi.formatLongDateTime(dateValue, @json(config('app.timezone')));
        }

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        // Row data shared with the detail sheet (keyed by stock opname id).
        window.stockOpnameRows = window.stockOpnameRows || {};

        function describeStockOpname(row) {
            const isPending = row?.status === 'pending';
            const stockSystem = isPending ? Number(row?.live_stock || 0) : Number(row?.stock || 0);
            const stockFisik = Number(row?.real_stock || 0);
            // The server rounds the difference and filters on that same value, so render it
            // verbatim; recomputing here in binary floats could disagree at half-cent ties.
            const serverDiff = row?.display_difference;
            const selisih = serverDiff === null || serverDiff === undefined || !Number.isFinite(Number(serverDiff))
                ? Math.round((stockFisik - stockSystem) * 100) / 100
                : Number(serverDiff);
            const hpp = Number(row?.avg_hpp_calc || row?.avg_price || 0);
            return {
                id: row?.id || 0,
                name: row?.product?.name || row?.name || '-',
                code: row?.code || '-',
                branch: row?.branch_name || 'Semua Cabang',
                unit: row?.product?.unit?.abbreviation || 'Kg',
                isPending,
                dateLabel: formatLongDateTime(row?.created_at || row?.date),
                creator: row?.creator_name || '',
                system: stockSystem,
                physical: stockFisik,
                diff: selisih,
                hpp,
                value: Math.floor(selisih * hpp),
                historyCount: Number(row?.history_count || 0),
                discussionsCount: Number(row?.discussions_count || 0)
            };
        }

        function formatStockQty(value) {
            return StockOpnameUi.quantity(value);
        }

        function formatSignedStockQty(value) {
            return (value > 0 ? '+' : '') + formatStockQty(value);
        }

        function formatSignedRupiah(value) {
            return (value < 0 ? '-' : (value > 0 ? '+' : '')) + 'Rp ' + formatRupiah(Math.abs(value));
        }

        function renderStockOpnameCard(row) {
            const info = describeStockOpname(row);
            window.stockOpnameRows[info.id] = info;
            const tone = info.diff > 0 ? 'is-surplus' : (info.diff < 0 ? 'is-loss' : 'is-match');
            const pending = info.isPending ? '<span class="so-row__status">Draf</span>' : '';
            const value = info.diff !== 0
                ? `<span class="so-row__value ${tone}">${escapeHtml(formatSignedRupiah(info.value))}</span>`
                : '';

            return `
                <div class="so-row" role="button" tabindex="0" data-so-id="${info.id}"
                    onclick="openStockDetail(${info.id})"
                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openStockDetail(${info.id}); }">
                    <div class="so-row__main">
                        <div class="so-row__title"><span class="so-row__name">${escapeHtml(info.name)}</span>${pending}</div>
                        <span class="so-row__time">${escapeHtml(info.dateLabel)}</span>
                    </div>
                    <div class="so-row__side">
                        <span class="so-row__diff ${tone}">${escapeHtml(formatSignedStockQty(info.diff))} <small>${escapeHtml(info.unit)}</small></span>
                        ${value}
                    </div>
                </div>
            `;
        }

        function openStockDetail(id) {
            const info = window.stockOpnameRows[id];
            if (!info) return;
            const $sheet = $('#kt_modal_stock_detail');
            const tone = info.diff > 0 ? 'is-surplus' : (info.diff < 0 ? 'is-loss' : 'is-match');
            $sheet.data('stockOpnameId', id);
            $sheet.find('[data-so-field="name"]').text(info.name);
            $sheet.find('[data-so-field="branch"]').text(info.branch);
            $sheet.find('[data-so-field="created"]').text(info.dateLabel);
            $sheet.find('[data-so-field="creator"]').text(info.creator || '—');
            $sheet.find('[data-so-field="system"]').text(formatStockQty(info.system));
            $sheet.find('[data-so-field="physical"]').text(`${formatStockQty(info.physical)} ${info.unit}`);
            $sheet.find('[data-so-field="diff"]').text(`Selisih (${formatSignedStockQty(info.diff)} ${info.unit})`);
            $sheet.find('[data-so-field="value"]').text(formatSignedRupiah(info.value));
            $sheet.find('.so-detail-difference').removeClass('is-surplus is-loss is-match').addClass(tone);
            $sheet.find('[data-so-field="hpp"]').text(`Rp ${formatRupiah(Math.round(Math.abs(info.hpp)))} / ${info.unit}`);
            $sheet.find('[data-so-field="unit"]').text(info.unit);
            $sheet.find('[data-so-action="finalize"]').toggleClass('d-none', !info.isPending);
            const canCorrect = info.isPending && @json(check_access('stock-opname.update'));
            $sheet.find('#so-credit-plus, #so-credit-minus, #so-credit-reason').val('').prop('disabled', !canCorrect);
            $sheet.find('#so-correction-save').prop('disabled', true);
            $sheet.find('#so-detail-done').toggleClass('d-none', canCorrect);
            $sheet.find('#so-correction-error').text(info.isPending && !canCorrect ? 'Tidak memiliki akses koreksi.' : '');
            $sheet.find('[data-so-count="history"]').text(info.historyCount || '');
            bootstrap.Modal.getOrCreateInstance($sheet[0]).show();
        }

        $(document).on('click', '#kt_modal_stock_detail [data-so-action]', function() {
            const $sheet = $('#kt_modal_stock_detail');
            const id = $sheet.data('stockOpnameId');
            const action = this.dataset.soAction;
            if (action === 'finalize' && ($('#so-credit-plus').val() || $('#so-credit-minus').val() || $('#so-credit-reason').val().trim())) {
                $('#so-correction-error').text('Simpan atau kosongkan koreksi terlebih dahulu sebelum menyelesaikan opname.');
                return;
            }
            const run = {
                history: () => showHistory(id),
                finalize: () => finalizeStockOpname(id),
            }[action];
            if (!run) return;
            // Close the sheet first so the next modal/drawer does not stack on its backdrop.
            $sheet.one('hidden.bs.modal', run);
            bootstrap.Modal.getOrCreateInstance($sheet[0]).hide();
        });

        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="cabang"]');
            const $activeBranchButtonLabel = $('#active-branch-button-label');

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            window.exportExcel = function() {
                let cabang = $('[data-kt-ecommerce-product-filter="cabang"]').val() || '';
                let range = $('#kt_ecommerce_sales_flatpickr').val() || '';
                let startDate = '';
                let endDate = '';
                if (range) {
                    let dates = range.split(' to ');
                    startDate = dates[0];
                    endDate = dates[1] ?? dates[0];
                }

                let url = "{{ route('stock-opname.export') }}?cabang_filter=" + cabang + "&start_date=" +
                    startDate + "&end_date=" + endDate;
                window.open(url, '_blank');
            };

            window.previewStock = function() {
                let cabang = $('[data-kt-ecommerce-product-filter="cabang"]').val() || '';
                let range = $('#kt_ecommerce_sales_flatpickr').val() || '';
                let startDate = '';
                let endDate = '';
                if (range) {
                    let dates = range.split(' to ');
                    startDate = dates[0];
                    endDate = dates[1] ?? dates[0];
                }

                let url = "{{ route('stock-opname.preview') }}?cabang_filter=" + cabang + "&start_date=" +
                    startDate + "&end_date=" + endDate;
                window.open(url, '_blank');
            };

            dataTable = $('#transaction-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                processing: true,
                serverSide: true,
                ordering: false,
                ajax: {
                    url: "{{ route('stock-opname.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.cabang_filter = $('[data-kt-ecommerce-product-filter="cabang"]').val();
                        d.status_filter = $('#so-filter-applied-status').val() || 'all';
                        d.result_filter = $('#so-filter-applied-result').val() || 'all';
                        var range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            var dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0];
                        }
                    }
                },
                columns: [{
                    data: null,
                    name: 'name',
                    orderable: false,
                    searchable: true,
                    render: function(data, type, row) {
                        return renderStockOpnameCard(row);
                    }
                }]
            });
            initStockOpnameReference();

            $('#kt_stock_opname_discussion_form').on('submit', function(e) {
                e.preventDefault();

                const id = $('#discussion-stock-opname-id').val();
                const message = $('#discussion-message-input').val();
                if (!message || !id) return;

                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true);

                $.ajax({
                    url: `/stock-opname/${id}/discussion`,
                    type: 'POST',
                    data: {
                        message: message,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#discussion-message-input').val('');

                        // Remove empty message text if present
                        $('#discussion-items').find('.text-muted').remove();

                        // Append the new message
                        const item = response.discussion;
                        const isSelf = String(item.created_by) === String(currentUserId);
                        const selfClass = isSelf ? 'is-self' : '';
                        const avatarNum = ((item.created_by || 1) % 30) + 1;
                        const avatarUrl = `/assets/media/avatars/300-${avatarNum}.jpg`;

                        const itemHtml = `
                            <div class="chat-message-container ${selfClass}">
                                <img src="${avatarUrl}" class="chat-message-avatar" alt="Avatar">
                                <div class="d-flex flex-column align-items-${isSelf ? 'end' : 'start'}">
                                    <div class="chat-message-header">
                                        <span class="chat-message-author">${escapeHtml(item.creator_name || 'User')}</span>
                                        <span class="chat-message-time">${formatLongDateTime(item.created_at)}</span>
                                    </div>
                                    <div class="chat-message-bubble shadow-sm">
                                        ${escapeHtml(item.message || '')}
                                    </div>
                                </div>
                            </div>
                        `;

                        $('#discussion-items').append(itemHtml);

                        // Scroll to bottom
                        const scrollEl = document.getElementById(
                            'kt_stock_opname_discussion_scroll');
                        if (scrollEl) {
                            scrollEl.scrollTop = scrollEl.scrollHeight;
                        }

                        // Update badge count on the card button dynamically
                        const $btn = $(`[data-discussion-btn-id="${id}"]`);
                        if ($btn.length > 0) {
                            let $badge = $btn.find('.badge');
                            if ($badge.length > 0) {
                                const newCount = parseInt($badge.text() || 0) + 1;
                                $badge.text(newCount);
                            } else {
                                $btn.append(
                                    `<span class="position-absolute badge rounded-pill bg-danger text-white" style="top: 2px; right: 2px; font-size: 8px; padding: 0.25em 0.5em; z-index: 1;">1</span>`
                                );
                            }
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Terjadi kesalahan saat mengirim pesan.'
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            $('#kt_modal_stock_adjustment_form').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const id = form.data('id');
                const type = form.data('type');
                const qty = $('#adj-qty-input').val();
                const reason = $('#adj-reason-input').val();

                const submitBtn = $('#adj-submit-btn');
                submitBtn.prop('disabled', true).text('Menyimpan...');

                $.ajax({
                    url: `/stock-opname/${id}/adjust`,
                    type: 'POST',
                    data: {
                        qty: qty,
                        type: type,
                        reason: reason,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message ||
                                'Penyesuaian stok berhasil disimpan.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            const modal = bootstrap.Modal.getInstance(document
                                .getElementById('kt_modal_stock_adjustment'));
                            if (modal) modal.hide();

                            // Refresh DataTable
                            if (typeof dataTable !== 'undefined') {
                                dataTable.ajax.reload(null, false);
                            }
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat memproses penyesuaian.'
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text('Simpan');
                    }
                });
            });

            $('[data-kt-ecommerce-product-filter="cabang"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw();
                showStockAlert($(this).val());
            });

            updateActiveFilterInfo();
            showStockAlert($('[data-kt-ecommerce-product-filter="cabang"]').val());

            const cancelButton = document.getElementById('kt_modal_add_customer_cancel');
            if (cancelButton) {
                cancelButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        text: "Apakah Anda yakin ingin membatalkan?",
                        icon: "warning",
                        showCancelButton: !0,
                        buttonsStyling: !1,
                        confirmButtonText: "Ya, Batalkan!",
                        cancelButtonText: "Tidak, Kembali",
                        customClass: {
                            confirmButton: "btn btn-primary",
                            cancelButton: "btn btn-active-light"
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById(
                                'kt_modal_add_customer'));
                            modal.hide();
                        }
                    });
                });
            }

            $('#kt_modal_add_customer').on('hidden.bs.modal', function() {
                var form = $('#kt_modal_add_customer_form');
                form.trigger('reset');
                form.find('input[name="_method"]').remove();
                $('select[name="product_id"]').val(null).trigger('change');

                var filterBranch = $('[data-kt-ecommerce-product-filter="cabang"]').val();
                var defaultBranch = (filterBranch && filterBranch !== 'all') ? filterBranch : $(
                    'select[name="branch_id"] option:first').val();
                $('select[name="branch_id"]').val(defaultBranch).trigger('change');

                form.attr('action', '{{ url(Request::segment(1)) }}');
                $('#kt_modal_add_customer_header h2').text('Catat Stock Opname');
                $('#kt_modal_add_customer_submit .indicator-label').html(
                    'Buat Transaksi <i class="ki-outline ki-arrow-right text-white fs-4 ms-1"></i>');
                $('#detail-qty-container').empty();
                if (typeof detailRowCount !== 'undefined') {
                    detailRowCount = 0;
                }
                form.find('input, select, textarea, button[type="submit"], button').prop('disabled', false);
                $('input[name="quantity"]').val(0);
                $('input[name="real_stock"]').val(0);
                $('textarea[name="note"]').val('');
                if (typeof updateStockSummary === 'function') {
                    updateStockSummary(0);
                }
            });

            $('#kt_modal_add_customer_form').on('submit', function(e) {
                e.preventDefault();

                if (!$('#product_id').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pilih produk',
                        text: 'Pilih produk yang akan di-opname terlebih dahulu.'
                    });
                    return;
                }

                var form = $(this);
                var url = form.attr('action');
                var submitBtn = $('#kt_modal_add_customer_submit');

                // Show loading
                submitBtn.prop('disabled', true);
                submitBtn.find('.indicator-label').hide();
                submitBtn.find('.indicator-progress').show();

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: form.serialize(), // gunakan FormData(form)[... jika pakai file]
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Data berhasil disimpan.',
                            showConfirmButton: false,
                            timer: 1500 // notifikasi akan hilang otomatis setelah 1.5 detik
                        }).then(() => {
                            // 1. Reset form
                            form.trigger('reset');

                            // 2. Hapus input _method
                            form.find('input[name="_method"]').remove();
                            $('select[name="product_id"]').val(null).trigger(
                                'change'); // Reset select2
                            $('#product_id select').val(null).trigger('change');

                            // 3. Kembalikan action form ke default (untuk create)
                            form.attr('action',
                                `/${segment1}`); // Misal segment1 = 'stock-opname'

                            // 4. Kembalikan judul modal (opsional)
                            $('#kt_modal_add_customer_header h2').text(
                                'Catat Stock Opname');

                            $('#kt_modal_add_customer_submit .indicator-label').html(
                                'Simpan Final');
                            updateStockSummary();

                            // 5. Tutup modal
                            const modal = bootstrap.Modal.getInstance(document
                                .getElementById('kt_modal_add_customer'));
                            if (modal) modal.hide();

                            // 6. Refresh DataTable
                            if (typeof dataTable !== 'undefined') {
                                dataTable.ajax.reload(null, false);
                            }
                        });
                    },
                    error: function(xhr) {
                        var msg = 'Terjadi kesalahan saat menyimpan data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });
                    },
                    complete: function() {
                        // Reset loading state
                        submitBtn.prop('disabled', false);
                        submitBtn.find('.indicator-label').show();
                        submitBtn.find('.indicator-progress').hide();
                    }
                });
            });
        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }

        function deleteProduct(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang dihapus tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/stock-opname/${id}`, // Ganti dengan URL yang sesuai
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil dihapus.',
                                showConfirmButton: false,
                                timer: 1500 // notifikasi akan hilang otomatis setelah 1.5 detik
                            });

                            // Reload DataTable setelah berhasil menghapus data
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menghapus data.'
                            });
                        }
                    });
                }
            });
        }

        function editProduct(id) {
            $.ajax({
                url: `/stock-opname/${id}/edit`,
                type: 'GET',
                success: function(response) {
                    console.log(response);

                    // Prevent old branch change handler from overwriting quantity
                    $('#branch_id').off('change.stockOpname');

                    // Clear and reset product_id select2 first
                    var $productSelect = $('select[name="product_id"]');
                    $productSelect.empty();
                    $productSelect.append(new Option('Pilih Product', '', false, false));
                    $productSelect.append(new Option(response.name, response.product_id, true, true));
                    $productSelect.val(response.product_id).trigger('change');

                    // Set flatpickr date
                    var fp = $('#date')[0]._flatpickr;
                    if (fp) {
                        fp.setDate(response.date);
                    }

                    // Set branch_id BEFORE quantity so re-init won't overwrite
                    $('select[name="branch_id"]').val(response.branch_id).trigger('change');

                    // Set form fields from database values (not live stock)
                    $('input[name="quantity"]').val(response.stock);
                    $('input[name="real_stock"]').val(response.real_stock);
                    $('textarea[name="note"]').val(response.note || '');
                    $('#product_id').data('unit', response.product?.unit?.abbreviation || response.unit || 'Kg');
                    updateStockSummary(response.avg_price || 0);

                    // Ubah action form untuk update
                    var form = $('#kt_modal_add_customer_form');
                    form.attr('action', `/stock-opname/${id}`);
                    form.find('input[name="_method"]').remove();
                    form.append(
                        '<input type="hidden" name="_method" value="PUT">'
                    );

                    // Populate details if any
                    $('#detail-qty-container').empty();
                    detailRowCount = 0;
                    if (response.details) {
                        try {
                            const details = JSON.parse(response.details);
                            if (Array.isArray(details)) {
                                details.forEach(function(detail) {
                                    addDetailRow(detail.name, detail.qty);
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing details JSON', e);
                        }
                    }

                    // --- ENABLE semua input/select/textarea di form untuk edit ---
                    form.find('input, select, textarea, button[type="submit"]').prop('disabled', false);

                    // Ubah judul modal
                    $('#kt_modal_add_customer_header h2').text('Edit Transaksi');
                    $('#kt_modal_add_customer_submit .indicator-label').text('Simpan Perubahan');

                    // Tampilkan modal untuk edit produk
                    var modal = new bootstrap.Modal(document.getElementById('kt_modal_add_customer'));
                    modal.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data produk.'
                    });
                }
            });
        }

        function viewProduct(id) {
            $.ajax({
                url: `/stock-opname/${id}/edit`,
                type: 'GET',
                success: function(response) {
                    console.log(response);

                    // Prevent old branch change handler from overwriting quantity
                    $('#branch_id').off('change.stockOpname');

                    // Clear and reset product_id select2 first
                    var $productSelect = $('select[name="product_id"]');
                    $productSelect.empty();
                    $productSelect.append(new Option('Pilih Product', '', false, false));
                    $productSelect.append(new Option(response.name, response.product_id, true, true));
                    $productSelect.val(response.product_id).trigger('change');

                    // Set flatpickr date
                    var fp = $('#date')[0]._flatpickr;
                    if (fp) {
                        fp.setDate(response.date);
                    }

                    // Set branch_id BEFORE quantity so re-init won't overwrite
                    $('select[name="branch_id"]').val(response.branch_id).trigger('change');

                    // Set form fields from database values (not live stock)
                    $('input[name="quantity"]').val(response.stock);
                    $('input[name="real_stock"]').val(response.real_stock);
                    $('textarea[name="note"]').val(response.note || '');
                    $('#product_id').data('unit', response.product?.unit?.abbreviation || response.unit || 'Kg');
                    updateStockSummary(response.avg_price || 0);

                    // Ubah action form untuk view (no action)
                    var form = $('#kt_modal_add_customer_form');
                    form.attr('action', '#');
                    form.find('input[name="_method"]').remove();

                    // Populate details if any
                    $('#detail-qty-container').empty();
                    detailRowCount = 0;
                    if (response.details) {
                        try {
                            const details = JSON.parse(response.details);
                            if (Array.isArray(details)) {
                                details.forEach(function(detail) {
                                    addDetailRow(detail.name, detail.qty);
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing details JSON', e);
                        }
                    }

                    // --- DISABLE semua input/select/textarea di form supaya read-only ---
                    form.find('input, select, textarea, button').prop('disabled', true);

                    // Ubah judul modal untuk view
                    $('#kt_modal_add_customer_header h2').text('Lihat Transaksi');

                    // Tampilkan modal untuk view produk
                    var modal = new bootstrap.Modal(document.getElementById('kt_modal_add_customer'));
                    modal.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data produk.'
                    });
                }
            });
        }

        // Normalize the complete server history before filtering (never recompute balances on a subset).
        function renderStockOpnameHistory() {
            const state = window.stockOpnameHistory || {entries:[],unit:'Kg',tab:'credit',direction:'all'};
            const entries = StockOpnameUi.filterHistory(StockOpnameUi.history(state.entries), state.direction || 'all');
            const unit = escapeHtml(state.unit);
            const num = value => StockOpnameUi.quantity(value);
            const signed = value => value === null ? '—' : (value > 0 ? '+' : '') + num(value);
            const reasonMode = state.tab === 'reason';
            $('[data-so-history-toggle]').text(reasonMode ? 'Alasan' : 'Kredit').toggleClass('is-reason', reasonMode)
                .attr('aria-pressed', String(reasonMode)).attr('aria-label', reasonMode ? 'Ubah tampilan menjadi Kredit' : 'Ubah tampilan menjadi Alasan');
            $('#so-history-filter-open').toggleClass('is-active', state.direction !== 'all');
            const head = reasonMode ? '<span class="so-hist-reason-head">Alasan Pergerakan</span>'
                : '<div class="so-hist-head__side"><span class="so-hist-col">Awal</span><span class="so-hist-col">Kredit</span><span class="so-hist-col">Saldo</span></div>';
            $('#so-history-columns').html(`<span>Aktivitas</span>${head}`);
            const $list = $('#history-timeline-items').empty();
            if (!entries.length) {
                $list.append('<div class="so-hist-empty">Tidak ada riwayat sesuai filter.</div>');
                return;
            }
            entries.forEach(item => {
                const tone = item.delta > 0 ? 'is-surplus' : item.delta < 0 ? 'is-loss' : '';
                const kind = {initial:'Stok Awal',plus:'Kredit Lebih',minus:'Kredit Kurang',note:'Catatan',unknown:'Perubahan',snapshot:'Snapshot Tersedia'}[item.kind];
                const badge = tone ? `<span class="so-hist-direction ${tone}" aria-label="${item.delta > 0 ? 'Lebih' : 'Kurang'}">${item.delta > 0 ? '↙' : '↗'}</span>` : '';
                let side;
                if (!reasonMode) {
                    const cell = (value,cls='',isSigned=false) => `<span class="so-hist-col ${cls}">${isSigned ? signed(value) : num(value)}${value !== null ? `<small>${unit}</small>` : ''}</span>`;
                    side = cell(item.before) + cell(item.delta,tone,true) + cell(item.after,'is-strong');
                } else {
                    const details = Array.isArray(item.details) && item.details.length
                        ? '<div class="so-hist-details">' + item.details.map(det => `<span>${escapeHtml(det.name)}</span><b>${num(det.qty)} ${unit}</b>`).join('') + '</div>' : '';
                    side = `<div class="so-hist-reason">${escapeHtml(item.note || '—')}${details}</div>`;
                }
                $list.append(`<div class="so-hist-row"><div class="so-hist-main"><span class="so-hist-kind">${kind}${badge}</span><span class="so-hist-by">${escapeHtml(item.creator_name || '—')}</span><span class="so-hist-time">${escapeHtml(formatLongDateTime(item.created_at))}</span></div><div class="so-hist-side ${reasonMode ? 'is-reason' : ''}">${side}</div></div>`);
            });
        }

        $(document).on('click', '[data-so-history-toggle]', function() {
            if (!window.stockOpnameHistory) return;
            window.stockOpnameHistory.tab = window.stockOpnameHistory.tab === 'credit' ? 'reason' : 'credit';
            renderStockOpnameHistory();
        });

        function showHistory(id) {
            $.ajax({url:`/stock-opname/${id}/history`,type:'GET',success:function(response) {
                $('#history-transaction-code').text(response.product_name || window.stockOpnameRows[id]?.name || response.code || '—');
                window.stockOpnameHistory = {id,entries:response.history || [],unit:response.unit || 'Kg',tab:'credit',direction:'all'};
                renderStockOpnameHistory();
                const drawerEl = document.getElementById('kt_stock_opname_history');
                let drawer = KTDrawer.getInstance(drawerEl);
                if (!drawer) drawer = new KTDrawer(drawerEl);
                drawer.show();
                requestAnimationFrame(() => document.getElementById('kt_stock_opname_history_close').focus());
            },error:function() {
                Swal.fire({icon:'error',title:'Gagal',text:'Terjadi kesalahan saat memuat riwayat stok opname.'});
            }});
        }

        function showDiscussion(id) {
            $.ajax({
                url: `/stock-opname/${id}/discussion`,
                type: 'GET',
                success: function(response) {
                    $('#discussion-product-name').text(response.product_name || '-');
                    $('#discussion-stock-opname-id').val(id);
                    $('#discussion-message-input').val('');

                    const $items = $('#discussion-items');
                    $items.empty();

                    if (response.discussions && response.discussions.length > 0) {
                        response.discussions.forEach((item) => {
                            const isSelf = String(item.created_by) === String(currentUserId);
                            const selfClass = isSelf ? 'is-self' : '';
                            const avatarNum = ((item.created_by || 1) % 30) + 1;
                            const avatarUrl = `/assets/media/avatars/300-${avatarNum}.jpg`;

                            const itemHtml = `
                                <div class="chat-message-container ${selfClass}">
                                    <img src="${avatarUrl}" class="chat-message-avatar" alt="Avatar">
                                    <div class="d-flex flex-column align-items-${isSelf ? 'end' : 'start'}">
                                        <div class="chat-message-header">
                                            <span class="chat-message-author">${escapeHtml(item.creator_name || 'User')}</span>
                                            <span class="chat-message-time">${formatLongDateTime(item.created_at)}</span>
                                        </div>
                                        <div class="chat-message-bubble shadow-sm">
                                            ${escapeHtml(item.message || '')}
                                        </div>
                                    </div>
                                </div>
                            `;
                            $items.append(itemHtml);
                        });
                    } else {
                        $items.append(
                            '<div class="text-muted text-center py-20 fs-6">Belum ada diskusi. Mulai percakapan terkait selisih stock ini.</div>'
                        );
                    }

                    // Show Metronic Drawer
                    const drawerEl = document.getElementById('kt_stock_opname_discussion');
                    let drawer = KTDrawer.getInstance(drawerEl);
                    if (!drawer) {
                        drawer = new KTDrawer(drawerEl);
                    }
                    drawer.show();

                    // Scroll to bottom
                    setTimeout(() => {
                        const scrollEl = document.getElementById('kt_stock_opname_discussion_scroll');
                        if (scrollEl) {
                            scrollEl.scrollTop = scrollEl.scrollHeight;
                        }
                    }, 100);
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data diskusi.'
                    });
                }
            });
        }

        function openAdjustmentModal(id, type) {
            $.ajax({
                url: `/stock-opname/${id}/edit`,
                type: 'GET',
                success: function(response) {
                    const unit = response.product?.unit?.abbreviation || 'Kg';
                    const currentStock = Number(response.real_stock || 0);

                    $('#adj-current-stock-label').text(`${currentStock} ${unit}`);
                    $('#adj-qty-input').val('1');
                    $('#adj-reason-input').val('');

                    // Store details on form
                    const $form = $('#kt_modal_stock_adjustment_form');
                    $form.data('id', id);
                    $form.data('type', type);
                    $form.data('unit', unit);
                    $form.data('current-stock', currentStock);

                    const $header = $('#adj-modal-header');
                    const $title = $('#adj-modal-title');
                    const $badge = $('#adj-type-badge');
                    const $submitBtn = $('#adj-submit-btn');

                    if (type === 'up') {
                        // Up styling
                        $header.css({
                            'background-color': '#ecfdf5',
                            'color': '#059669'
                        });
                        $title.css('color', '#059669').html(`
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                <line x1="7" y1="17" x2="17" y2="7"/>
                                <polyline points="7 7 17 7 17 17"/>
                            </svg>
                            Adjustment + Kredit
                        `);
                        $badge.text('+').css({
                            'background-color': '#ecfdf5',
                            'color': '#059669'
                        });
                        $submitBtn.css({
                            'background-color': '#5ec5a5',
                            'border-color': '#5ec5a5',
                            'color': '#fff'
                        });
                        $('#adj-reason-input').attr('placeholder', 'Contoh: Salah hitung saat audit awal');
                    } else {
                        // Down styling
                        $header.css({
                            'background-color': '#fef2f2',
                            'color': '#dc2626'
                        });
                        $title.css('color', '#dc2626').html(`
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                <line x1="7" y1="7" x2="17" y2="17"/>
                                <polyline points="17 7 17 17 7 17"/>
                            </svg>
                            Adjustment - Kredit
                        `);
                        $badge.text('-').css({
                            'background-color': '#fef2f2',
                            'color': '#dc2626'
                        });
                        $submitBtn.css({
                            'background-color': '#f87171',
                            'border-color': '#f87171',
                            'color': '#fff'
                        });
                        $('#adj-reason-input').attr('placeholder',
                            'Contoh: Koreksi buah busuk/penyusutan harian');
                    }

                    function updatePreview() {
                        const qty = parseFloat($('#adj-qty-input').val()) || 0;
                        let result = currentStock;
                        if (type === 'up') {
                            result = currentStock + qty;
                        } else {
                            result = currentStock - qty;
                        }
                        // Format preview
                        const formattedResult = Number(result % 1 === 0 ? result.toString() : result.toFixed(
                            2));
                        $('#adj-result-stock-label').text(`${formattedResult} ${unit}`);
                    }

                    $('#adj-qty-input').off('input change').on('input change', updatePreview);
                    updatePreview();

                    const modal = new bootstrap.Modal(document.getElementById('kt_modal_stock_adjustment'));
                    modal.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data produk.'
                    });
                }
            });
        }

        $('#kt_modal_add_customer').on('shown.bs.modal', function() {
            const $branch = $('#branch_id');
            const $product = $('#product_id');
            const $quantity = $('input[name="quantity"]');
            const $realStock = $('input[name="real_stock"]');
            const $form = $('#kt_modal_add_customer_form');

            if (!$form.find('input[name="_method"]').length) {
                $('#kt_modal_add_customer_header h2').text('Input Stock Fisik & Akumulasi');
                $('#kt_modal_add_customer_submit .indicator-label').html(
                    'Buat Transaksi <i class="ki-outline ki-arrow-right text-white fs-4 ms-1"></i>');
                $('textarea[name="note"]').val('');
                $('#detail-qty-container').empty();
                detailRowCount = 0;
            }

            if (!$realStock.val()) {
                $realStock.val(0);
            }

            if ($product.hasClass('select2-hidden-accessible')) {
                $product.select2('destroy');
            }

            $product.select2({
                placeholder: 'Pilih Produk',
                dropdownParent: $('#kt_modal_add_customer'),
                ajax: {
                    url: '{{ route('ajax.stock-available') }}',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term,
                        branch_id: $branch.val()
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name,
                            stock_available: item.stock_available,
                            unit_price: item.hpp || item.price || item.cost_price ||
                                item.purchase_price || 0
                        }))
                    })
                }
            }).on('select2:select', function(e) {
                const data = e.params.data;
                $quantity.val(data.stock_available || 0);
                $product.data('unit-price', data.unit_price || 0);
                updateStockSummary(data.unit_price || 0);
            });

            if ($branch.hasClass('select2-hidden-accessible')) {
                $branch.select2('destroy');
            }

            $branch.select2({
                placeholder: 'Pilih Cabang',
                dropdownParent: $('#kt_modal_add_customer'),
            });

            $branch.off('change.stockOpname').on('change.stockOpname', function() {
                const branchId = $(this).val();
                const productId = $product.val();

                $quantity.val('');
                updateStockSummary();

                if (!branchId) {
                    $product.val(null).trigger('change');
                    return;
                }

                if (!productId) {
                    return;
                }

                $.ajax({
                    url: '{{ route('ajax.stock-available') }}',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        branch_id: branchId,
                        search: ''
                    },
                    success: function(items) {
                        const selected = (items || []).find(item => String(item.id) === String(
                            productId));

                        if (selected) {
                            $quantity.val(selected.stock_available || 0);
                            $product.data('unit-price', selected.hpp || selected.price ||
                                selected.cost_price ||
                                selected.purchase_price || 0);
                            updateStockSummary();
                            return;
                        }

                        $product.val(null).trigger('change');
                    },
                    error: function() {
                        $product.val(null).trigger('change');
                    }
                });
            });

            $realStock.off('input.stockSummary change.stockSummary').on('input.stockSummary change.stockSummary',
                function() {
                    updateStockSummary();
                });

            updateStockSummary();
        });

        let detailRowCount = 0;

        function addDetailRow(name = '', qty = '') {
            const container = $('#detail-qty-container');
            const rowHtml = `
                <div class="d-flex align-items-center gap-3 detail-row" id="detail-row-${detailRowCount}">
                    <input type="text" class="form-control" name="details[${detailRowCount}][name]" value="${name}" placeholder="Misal: Keranjang A" required>
                    <div class="input-group" style="width: 150px;">
                        <input type="number" step="0.01" class="form-control detail-qty" name="details[${detailRowCount}][qty]" value="${qty}" placeholder="Qty" required oninput="calculateRealStock()">
                        <span class="input-group-text">Kg</span>
                    </div>
                    <button type="button" class="btn btn-icon btn-light-danger" onclick="removeDetailRow(${detailRowCount})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            container.append(rowHtml);
            detailRowCount++;
            calculateRealStock();
        }

        function removeDetailRow(id) {
            $(`#detail-row-${id}`).remove();
            calculateRealStock();
        }

        function calculateRealStock() {
            let total = 0;
            $('.detail-qty').each(function() {
                const val = parseFloat($(this).val());
                if (!isNaN(val)) {
                    total += val;
                }
            });
            $('input[name="real_stock"]').val(total);
            updateStockSummary();
        }

        function finalizeStockOpname(id) {
            Swal.fire({
                title: 'Konfirmasi Finalisasi',
                text: 'Stock saat ini akan dikunci dan selisih akan disimpan. Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Finalisasi!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/stock-opname/${id}/finalize`,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil difinalisasi.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat memproses data.'
                            });
                        }
                    });
                }
            });
        }
    </script>
    <script src="{{ asset('assets/js/stock-opname-reference-page.js') }}?v={{ filemtime(public_path('assets/js/stock-opname-reference-page.js')) }}"></script>
@endsection
@endsection
