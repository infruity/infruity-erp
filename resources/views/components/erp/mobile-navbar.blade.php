@php($action = \App\Support\ErpMobileAction::resolve(request()))

<style>
    .erp-mobile-nav { position:fixed; z-index:40; bottom:calc(1rem + env(safe-area-inset-bottom)); left:50%; width:calc(100% - 2.5rem); padding:0; transform:translateX(-50%); transition:width .45s cubic-bezier(.22,1,.36,1); will-change:width; }
    .erp-mobile-nav.is-scrolled { width:min(340px, calc(100% - 2.5rem)); transition-duration:.3s; }
    @media (prefers-reduced-motion:reduce) { .erp-mobile-nav, .erp-mobile-nav.is-scrolled { transition:none; } }
    .erp-mobile-nav__dock { display:flex; height:64px; align-items:center; justify-content:space-around; border:1px solid rgba(255,255,255,.75); border-radius:999px; padding:8px; background:rgba(255,255,255,.72); box-shadow:0 16px 40px -8px rgba(15,92,69,.12), 0 4px 16px rgba(0,0,0,.06), inset 0 1px 1.5px rgba(255,255,255,.95); -webkit-backdrop-filter:blur(24px) saturate(190%); backdrop-filter:blur(24px) saturate(190%); }
    .erp-mobile-nav__item { display:flex; width:48px; height:48px; flex:none; align-items:center; justify-content:center; border:0; border-radius:999px; background:transparent; color:#6b7280; text-decoration:none; transition:color .16s ease, transform .16s ease, background .16s ease; -webkit-tap-highlight-color:transparent; }
    .erp-mobile-nav__item:hover, .erp-mobile-nav__item:focus-visible, .erp-mobile-nav__item.is-active { color:#0b595b; }
    .erp-mobile-nav__item:active { transform:scale(.95); }
    .erp-mobile-nav__item svg { display:block; width:24px; height:24px; }
    .erp-mobile-nav__item[aria-label="Kasir / POS"] svg { width:25px; height:25px; }
    .erp-mobile-nav .icon-rounded { font-family:'Material Symbols Rounded'; font-size:28px; font-weight:300; line-height:1; }
    .erp-mobile-nav__add { display:flex; width:44px; height:44px; flex:none; align-items:center; justify-content:center; border:0; border-radius:13px; background:#0b595b; box-shadow:0 4px 12px rgba(11,89,91,.3); color:white; text-decoration:none; transition:background .16s ease, transform .16s ease; -webkit-tap-highlight-color:transparent; }
    .erp-mobile-nav__add:hover, .erp-mobile-nav__add:focus-visible { background:#08484a; color:white; }
    .erp-mobile-nav__add:active { transform:scale(.95); }
    .erp-mobile-nav__account { position:relative; }
    .erp-mobile-nav__menu { position:absolute; right:0; bottom:calc(100% + 1rem); z-index:45; width:240px; overflow:hidden; border:1px solid #f3f4f6; border-radius:18px; background:white; padding:8px 0; color:#1f2937; box-shadow:0 16px 40px -12px rgba(0,0,0,.18); }
    .erp-mobile-nav__menu[hidden] { display:none; }
    .erp-mobile-nav__account-summary { border-bottom:1px solid #f3f4f6; padding:12px 16px; }
    .erp-mobile-nav__account-name { overflow:hidden; color:#111827; font-size:13px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
    .erp-mobile-nav__account-subtitle { margin-top:3px; color:#6b7280; font-size:11px; }
    .erp-mobile-nav__menu-link { display:flex; min-height:44px; align-items:center; gap:10px; padding:0 16px; color:#4b5563; font-size:13px; text-decoration:none; }
    .erp-mobile-nav__menu-link:hover, .erp-mobile-nav__menu-link:focus-visible { background:#f9fafb; color:#0b595b; }
    .erp-mobile-nav__menu-link.is-danger { color:#e11d48; }
    @media (min-width:768px) { .erp-mobile-nav { display:none; } }
</style>

<div class="erp-mobile-nav">
    <nav class="erp-mobile-nav__dock" aria-label="Navigasi utama">
        <a href="{{ url('/dashboard') }}" @if(request()->is('dashboard')) aria-current="page" @endif aria-label="Dashboard" title="Dashboard" class="erp-mobile-nav__item {{ request()->is('dashboard') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect width="8" height="10" x="13" y="11" rx="1" fill="currentColor"/><rect width="8" height="6" x="3" y="15" rx="1" fill="currentColor"/><rect width="8" height="6" x="13" y="3" rx="1" fill="currentColor"/><rect width="8" height="10" x="3" y="3" rx="1" fill="currentColor"/></svg>
        </a>
        <a href="{{ route('reports.hub') }}" @if(request()->routeIs('reports.hub', 'report-*')) aria-current="page" @endif aria-label="Laporan" title="Laporan" class="erp-mobile-nav__item {{ request()->routeIs('reports.hub', 'report-*') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="12" width="4" height="9" rx="1"/><rect x="10" y="3" width="4" height="18" rx="1"/><rect x="17" y="8" width="4" height="13" rx="1"/></svg>
        </a>

        @if (request()->is('dashboard') || (! $action['url'] && ! $action['canCreate']))
            <button type="button" @click="sidebarOpen = true" aria-label="Buka navigasi" title="Navigasi" class="erp-mobile-nav__add">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 6h14M5 12h14M5 18h14"/></svg>
            </button>
        @elseif ($action['url'])
            <a href="{{ $action['url'] }}" aria-label="Tambah" title="Tambah" class="erp-mobile-nav__add">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            </a>
        @else
            <button type="button" @if($action['opensModal']) data-erp-modal-create @else @click="$dispatch('mobile-add-toggle')" @endif data-erp-mobile-add aria-label="Tambah" title="Tambah" class="erp-mobile-nav__add">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            </button>
        @endif

        @if ($action['showPosShortcut'])
            <a href="{{ route('pos.create') }}" aria-label="Kasir / POS" title="Kasir / POS" @if(request()->routeIs('pos.create')) aria-current="page" @endif class="erp-mobile-nav__item {{ request()->routeIs('pos.create') ? 'is-active' : '' }}">
                <svg viewBox="0 0 640 640" aria-hidden="true"><path d="M94.7 136.3c6.9-23.9 28.8-40.3 53.7-40.3h344c24.9 0 46.8 16.4 53.8 40.3l23.4 80.2c12.8 43.7-20.1 87.5-65.6 87.5c-26.3 0-49.4-14.9-60.8-37.1c-11.6 21.9-34.6 37.1-61.4 37.1c-26.6 0-49.7-15-61.3-37c-11.6 22-34.7 37-61.3 37c-26.8 0-49.8-15.1-61.4-37.1C186.4 289 163.3 304 137 304c-45.6 0-78.4-43.7-65.6-87.5zM160.4 416h320v-66.4c7.6 1.6 15.5 2.4 23.5 2.4c14.3 0 28-2.6 40.5-7.2V496c0 26.5-21.5 48-48 48h-352c-26.5 0-48-21.5-48-48V344.8c12.5 4.6 26.1 7.2 40.5 7.2z" fill="currentColor"/></svg>
            </a>
        @else
            <button type="button" data-erp-mobile-search aria-label="Cari" aria-expanded="false" title="Cari" class="erp-mobile-nav__item">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" aria-hidden="true"><path d="m11.25 11.25 3 3"/><circle cx="7.5" cy="7.5" r="4.75"/></svg>
            </button>
        @endif

        <div class="erp-mobile-nav__account">
            <button type="button" data-erp-profile-toggle aria-expanded="false" aria-label="Akun" title="Akun" class="erp-mobile-nav__item">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" fill-rule="evenodd" d="M8 7a4 4 0 1 1 8 0a4 4 0 0 1-8 0m0 6a5 5 0 0 0-5 5a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3a5 5 0 0 0-5-5z" clip-rule="evenodd"/></svg>
            </button>
            <div class="erp-mobile-nav__menu" data-erp-profile-menu hidden>
                <div class="erp-mobile-nav__account-summary">
                    <p class="erp-mobile-nav__account-name">{{ auth()->user()->nm_user ?? auth()->user()->username ?? auth()->user()->name ?? 'Pengguna' }}</p>
                    <p class="erp-mobile-nav__account-subtitle">Akun Infruity</p>
                </div>
                <a href="{{ route('settings.index') }}" class="erp-mobile-nav__menu-link"><i class="ph ph-user-circle text-lg" aria-hidden="true"></i>Pengaturan</a>
                <a href="{{ route('change-password') }}" class="erp-mobile-nav__menu-link"><i class="ph ph-lock-key text-lg" aria-hidden="true"></i>Ubah password</a>
                <form method="POST" action="{{ url('/logout') }}">@csrf<button type="submit" class="erp-mobile-nav__menu-link is-danger w-full border-0 bg-transparent text-left"><i class="ph ph-sign-out text-lg" aria-hidden="true"></i>Keluar</button></form>
            </div>
        </div>
    </nav>
</div>

<script>
    (() => {
        const nav = document.currentScript?.previousElementSibling;
        if (!nav || nav.dataset.erpNavReady === 'true') return;
        nav.dataset.erpNavReady = 'true';
        nav.addEventListener('click', event => {
            if (!event.target.closest('[data-erp-modal-create]')) return;
            const target = document.getElementById('kt_modal_add_customer');
            if (target && window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(target).show();
        });
        const profileButton = nav.querySelector('[data-erp-profile-toggle]');
        const profileMenu = nav.querySelector('[data-erp-profile-menu]');
        nav.addEventListener('click', event => {
            const searchButton = event.target.closest('[data-erp-mobile-search]');
            if (searchButton) {
                const active = searchButton.getAttribute('aria-expanded') !== 'true';
                searchButton.setAttribute('aria-expanded', String(active));
                searchButton.classList.toggle('is-active', active);
                window.dispatchEvent(new CustomEvent('mobile-search-toggle'));
            }
            if (event.target.closest('[data-erp-profile-toggle]')) {
                const open = profileButton.getAttribute('aria-expanded') !== 'true';
                profileButton.setAttribute('aria-expanded', String(open));
                profileMenu.hidden = !open;
            } else if (!event.target.closest('[data-erp-profile-menu]') && profileMenu) {
                profileButton?.setAttribute('aria-expanded', 'false');
                profileMenu.hidden = true;
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && profileMenu) {
                profileButton?.setAttribute('aria-expanded', 'false');
                profileMenu.hidden = true;
            }
        });

        let scrollActivityTimer;
        const handleScrollActivity = () => {
            nav.classList.add('is-scrolled');
            window.clearTimeout(scrollActivityTimer);
            scrollActivityTimer = window.setTimeout(() => nav.classList.remove('is-scrolled'), 600);
        };
        // Scroll tidak bubbling; capture menangkap scroll dari container manapun (window, #kt_content, daftar internal).
        window.addEventListener('scroll', event => {
            if (nav.contains(event.target)) return;
            handleScrollActivity();
        }, { passive: true, capture: true });
    })();
</script>