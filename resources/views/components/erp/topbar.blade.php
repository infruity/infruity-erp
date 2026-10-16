@props(['mobileOnly' => false])

<style>
    .erp-topbar-mobile { position:sticky; top:0; z-index:30; display:flex; height:60px; flex:none; align-items:center; justify-content:space-between; padding:0 16px; border-bottom:1px solid #f3f4f6; background:#fff; box-shadow:none; -webkit-backdrop-filter:none; backdrop-filter:none; }
    .erp-topbar-mobile__button { position:relative; display:flex; width:36px; height:36px; flex:none; align-items:center; justify-content:center; border:0; border-radius:12px; background:transparent; color:#1f2937; cursor:pointer; transition:color .18s ease,background .18s ease,transform .18s ease; -webkit-tap-highlight-color:transparent; }
    .erp-topbar-mobile__button:hover, .erp-topbar-mobile__button:focus-visible { background:rgba(236,253,245,.7); color:#0b595b; }
    .erp-topbar-mobile__button:active { transform:scale(.95); }
    .erp-topbar-mobile__button i { font-size:21px; line-height:1; }
    .erp-topbar-mobile__logo { position:absolute; left:50%; display:flex; max-width:calc(100% - 120px); align-items:center; justify-content:center; transform:translateX(-50%); }
    .erp-topbar-mobile__logo img { display:block; width:auto; max-width:150px; height:30px; object-fit:contain; }
    .erp-topbar-mobile__notify { position:relative; }
    .erp-topbar-mobile__unread { position:absolute; top:4px; right:4px; width:8px; height:8px; border:2px solid white; border-radius:50%; background:#f97316; }
    .erp-topbar-mobile__notification { position:absolute; top:48px; right:0; z-index:50; width:min(20rem, calc(100vw - 2rem)); overflow:hidden; border:1px solid rgba(229,231,235,.9); border-radius:24px; background:rgba(255,255,255,.95); -webkit-backdrop-filter:blur(24px); backdrop-filter:blur(24px); box-shadow:0 8px 32px rgba(0,0,0,.15); color:#1f2937; }
    .erp-topbar-mobile__notification[hidden] { display:none; }
    .erp-topbar-mobile__notification-head { display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #f3f4f6; padding:14px 20px; }
    .erp-topbar-mobile__notification-title { color:#1f2937; font-size:13px; font-weight:700; }
    .erp-topbar-mobile__notification-close { min-height:40px; border:0; background:transparent; color:#6b7280; font-size:11px; font-weight:600; }
    .erp-topbar-mobile__notification-body { padding:18px 20px; color:#6b7280; font-size:12px; }
    @media (min-width:768px) { .erp-topbar-mobile { display:none; } }
</style>

<header class="erp-topbar-mobile">
    @if (request()->routeIs('wholesale.edit'))
        <a href="{{ route('wholesale.index') }}" aria-label="Kembali ke Pengadaan" class="erp-topbar-mobile__button">
            <i class="ph ph-arrow-left" aria-hidden="true"></i>
        </a>
    @else
        <button type="button" data-erp-sidebar-toggle aria-label="Buka navigasi" class="erp-topbar-mobile__button">
            <i class="ph-bold ph-list" aria-hidden="true"></i>
        </button>
    @endif
    <a href="{{ url('/dashboard') }}" class="erp-topbar-mobile__logo" aria-label="Infruity, dashboard">
        <img src="{{ asset('infruity-ui/003. ERP/assets/logo/Infruity Logo - 6.png') }}" alt="Infruity">
    </a>
    <div class="erp-topbar-mobile__notify">
        <button type="button" data-erp-notification-toggle aria-expanded="false" aria-label="Notifikasi" class="erp-topbar-mobile__button">
            <i class="ph-bold ph-bell" aria-hidden="true"></i>
            <span data-erp-unread class="erp-topbar-mobile__unread" aria-label="Ada notifikasi baru"></span>
        </button>
        <div class="erp-topbar-mobile__notification" data-erp-notification-menu hidden>
            <div class="erp-topbar-mobile__notification-head"><h2 class="erp-topbar-mobile__notification-title">Notifikasi</h2><button type="button" data-erp-notification-close class="erp-topbar-mobile__notification-close">Tutup</button></div>
            <p class="erp-topbar-mobile__notification-body">Belum ada notifikasi baru.</p>
        </div>
    </div>
</header>

@unless($mobileOnly)
    <header class="hidden md:flex sticky top-4 sm:top-6 lg:top-5 z-30 mx-4 sm:mx-6 lg:mx-8 mt-4 sm:mt-6 lg:mt-5 items-center justify-between gap-4 rounded-full border border-gray-100 bg-white px-5 py-3 shadow-sm lg:px-6 lg:py-4">
        <div class="flex items-center gap-4 pl-2">
            <button type="button" data-hamburger @click="sidebarOpen = !sidebarOpen" class="-ml-2 flex shrink-0 rounded-full p-2 text-gray-500 transition hover:bg-gray-100 lg:hidden" aria-label="Buka navigasi"><i class="ph ph-list text-2xl"></i></button>
            <div class="hidden sm:block"><h1 class="mb-1.5 text-base font-bold leading-none text-gray-900 sm:text-lg">@yield('page-title', trim($__env->yieldContent('title')) ?: 'Infruity')</h1><div class="flex items-center gap-1 text-[0.65rem] leading-none text-gray-400">@yield('page-breadcrumb', trim($__env->yieldContent('title')) ?: 'Infruity')</div></div>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative" x-data="{ notifOpen: false, hasUnread: true }">
                <button type="button" @click="notifOpen = !notifOpen; hasUnread = false" :aria-expanded="notifOpen" aria-label="Notifikasi" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:bg-gray-50"><span class="relative flex items-center justify-center"><i class="ph ph-bell text-lg"></i><span x-show="hasUnread" class="absolute right-[1.5px] top-[3px] h-2 w-2 rounded-full border border-white bg-strawberry"></span></span></button>
                <div x-show="notifOpen" x-cloak @click.outside="notifOpen = false" @keydown.escape.window="notifOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-y-2 scale-95 opacity-0" x-transition:enter-end="translate-y-0 scale-100 opacity-100" class="absolute right-0 top-12 z-50 w-80 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-soft"><div class="flex items-center justify-between border-b border-gray-100 px-5 py-4"><h2 class="font-semibold text-gray-800">Notifikasi</h2><button type="button" @click="notifOpen = false" class="text-xs text-gray-500">Tutup</button></div><div class="max-h-72 overflow-y-auto overscroll-none"><p class="px-5 py-4 text-sm text-gray-400">Tidak ada notifikasi baru.</p></div></div>
            </div>
            <div class="relative" x-data="{ profileOpen: false }">
                <button type="button" @click="profileOpen = !profileOpen" :aria-expanded="profileOpen" aria-label="Akun" class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border-2 border-gray-200 text-gray-600 transition hover:border-emerald-400"><i class="ph ph-user text-lg"></i></button>
                <div x-show="profileOpen" x-cloak @click.outside="profileOpen = false" @keydown.escape.window="profileOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-y-1 scale-95 opacity-0" x-transition:enter-end="translate-y-0 scale-100 opacity-100" class="absolute right-0 top-12 z-50 w-52 overflow-hidden rounded-2xl border border-gray-100 bg-white py-2 shadow-soft"><div class="border-b border-gray-50 px-4 py-3"><p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name ?? auth()->user()->nm_user ?? 'Pengguna' }}</p></div><a href="{{ route('change-password') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50"><i class="ph ph-lock-key text-lg"></i>Ubah password</a><form method="POST" action="{{ url('/logout') }}">@csrf<button type="submit" class="flex w-full items-center gap-3 border-0 bg-transparent px-4 py-2.5 text-left text-sm text-rose-600 transition hover:bg-rose-50"><i class="ph ph-sign-out text-lg"></i>Keluar</button></form></div>
            </div>
        </div>
    </header>
@endunless

<script>
    (() => {
        const header = document.querySelector('.erp-topbar-mobile');
        if (!header || header.dataset.erpTopbarReady === 'true') return;
        header.dataset.erpTopbarReady = 'true';
        const notificationButton = header.querySelector('[data-erp-notification-toggle]');
        const notificationMenu = header.querySelector('[data-erp-notification-menu]');
        header.addEventListener('click', event => {
            if (event.target.closest('[data-erp-sidebar-toggle]')) {
                window.dispatchEvent(new CustomEvent('erp-mobile-sidebar-toggle'));
            }
            if (event.target.closest('[data-erp-notification-toggle]')) {
                const open = notificationButton.getAttribute('aria-expanded') !== 'true';
                notificationButton.setAttribute('aria-expanded', String(open));
                notificationMenu.hidden = !open;
                header.querySelector('[data-erp-unread]')?.remove();
            } else if (event.target.closest('[data-erp-notification-close]')) {
                notificationButton.setAttribute('aria-expanded', 'false');
                notificationMenu.hidden = true;
            } else if (!event.target.closest('[data-erp-notification-menu]')) {
                notificationButton?.setAttribute('aria-expanded', 'false');
                if (notificationMenu) notificationMenu.hidden = true;
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && notificationMenu) {
                notificationButton?.setAttribute('aria-expanded', 'false');
                notificationMenu.hidden = true;
            }
        });
    })();
</script>
