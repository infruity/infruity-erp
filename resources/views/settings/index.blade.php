@extends('template.root')

@section('title', 'Pengaturan')

@section('content')
    @php
        $displayName = $user->nm_user ?? $user->name ?? $user->username ?? 'Pengguna';
        $email = $user->email ?? 'Email belum diatur';
        $initials = collect(preg_split('/\s+/', trim($displayName)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    @endphp

    <style>
        .erp-settings-page {
            --settings-ink: #202b3b;
            --settings-muted: #9aa3b2;
            --settings-line: #e7eaf0;
            display: flex;
            width: 100%;
            min-height: calc(100dvh - 58px);
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 24px 9.2% 20px;
            background: #f7f8fa;
            color: var(--settings-ink);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .erp-settings-card {
            width: min(100%, 768px);
            overflow: hidden;
            border: 1px solid #f0f1f4;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(28, 39, 56, .035);
        }
        .erp-settings-profile {
            display: flex;
            min-height: 77px;
            align-items: center;
            gap: 16px;
            padding: 14px 16px;
        }
        .erp-settings-avatar {
            display: grid;
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            place-items: center;
            border: 2px solid #fff;
            border-radius: 50%;
            background: #7757e8;
            box-shadow: 0 0 0 1px #eadfff, 0 2px 6px rgba(82, 54, 166, .15);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
        }
        .erp-settings-avatar img { width: 100%; height: 100%; border-radius: inherit; object-fit: cover; }
        .erp-settings-profile-copy { min-width: 0; flex: 1; }
        .erp-settings-name {
            overflow: hidden;
            margin: 0;
            color: #141d2c;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -.035em;
            line-height: 1.35;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .erp-settings-email {
            overflow: hidden;
            margin: 2px 0 0;
            color: #70798a;
            font-size: 13px;
            line-height: 1.45;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .erp-settings-chevron { color: #9aa3b2; font-size: 19px; }
        .erp-settings-details { padding: 8px 16px; }
        .erp-settings-detail { padding: 16px 0 13px; }
        .erp-settings-detail + .erp-settings-detail { border-top: 1px solid var(--settings-line); }
        .erp-settings-label {
            margin: 0 0 6px;
            color: var(--settings-muted);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .045em;
            line-height: 1.4;
            text-transform: uppercase;
        }
        .erp-settings-value {
            margin: 0;
            color: #273142;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -.025em;
            line-height: 1.45;
        }
        .erp-settings-options { padding: 0; }
        .erp-settings-option {
            display: flex;
            min-height: 49px;
            align-items: center;
            gap: 14px;
            padding: 0 16px;
            color: #273142;
            text-decoration: none;
        }
        .erp-settings-option + .erp-settings-option { border-top: 1px solid var(--settings-line); }
        .erp-settings-option-icon {
            display: grid;
            width: 20px;
            flex: 0 0 20px;
            place-items: center;
            color: #737d8d;
            font-size: 18px;
        }
        .erp-settings-option-label { flex: 1; font-size: 14px; font-weight: 600; }
        .erp-settings-option-value { color: #9aa3b2; font-size: 13px; }
        .erp-settings-language-mark {
            display: block;
            width: 21px;
            height: 21px;
            overflow: hidden;
            border: 1px solid #e3e5e9;
            border-radius: 50%;
            object-fit: cover;
        }
        .erp-settings-logout-form {
            display: block;
            width: min(100%, 768px);
            margin-top: auto;
        }
        .erp-settings-logout {
            display: flex;
            width: 100%;
            min-height: 52px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 0;
            border-radius: 17px;
            background: #e81748;
            color: #fff;
            font: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }
        .erp-settings-logout:hover, .erp-settings-logout:focus-visible { background: #d91241; color: #fff; }
        .erp-settings-logout i { font-size: 19px; }
        body:has(.erp-settings-page) .erp-mobile-nav { display: none; }
        body:has(.erp-settings-page) #kt_content { scrollbar-width: none; }
        body:has(.erp-settings-page) #kt_content::-webkit-scrollbar { display: none; }
        @media (min-width: 768px) {
            .erp-settings-page { min-height: calc(100dvh - 48px); gap: 18px; padding: 32px 24px 12px; }
            .erp-settings-profile { min-height: 96px; gap: 20px; padding: 20px 24px; }
            .erp-settings-avatar { width: 60px; height: 60px; flex-basis: 60px; font-size: 18px; }
            .erp-settings-name { font-size: 20px; }
            .erp-settings-email { font-size: 16px; }
            .erp-settings-details { padding: 12px 24px; }
            .erp-settings-detail { padding: 20px 0 18px; }
            .erp-settings-label { font-size: 14px; }
            .erp-settings-value { font-size: 18px; }
            .erp-settings-option { min-height: 62px; padding: 0 24px; }
            .erp-settings-option-label { font-size: 17px; }
            .erp-settings-option-value { font-size: 15px; }
            .erp-settings-logout { min-height: 64px; font-size: 18px; }
        }
    </style>

    <section class="erp-settings-page" aria-label="Pengaturan akun">
        <div class="erp-settings-card erp-settings-profile">
            <div class="erp-settings-avatar" aria-hidden="true">
                @if ($staff?->image)
                    <img src="{{ asset('storage/' . $staff->image) }}" alt="">
                @else
                    {{ $initials ?: 'I' }}
                @endif
            </div>
            <div class="erp-settings-profile-copy">
                <h1 class="erp-settings-name">{{ $displayName }}</h1>
                <p class="erp-settings-email">{{ $email }}</p>
            </div>
            <i class="ph ph-caret-right erp-settings-chevron" aria-hidden="true"></i>
        </div>

        <div class="erp-settings-card erp-settings-details">
            <div class="erp-settings-detail">
                <p class="erp-settings-label">Tanggal Bergabung</p>
                <p class="erp-settings-value">{{ $joinedDateLabel }}</p>
            </div>
            <div class="erp-settings-detail">
                <p class="erp-settings-label">Lama Bekerja</p>
                <p class="erp-settings-value">{{ $tenureLabel }}</p>
            </div>
            <div class="erp-settings-detail">
                <p class="erp-settings-label">Jabatan</p>
                <p class="erp-settings-value">{{ $jobTitleLabel }}</p>
            </div>
            <div class="erp-settings-detail">
                <p class="erp-settings-label">Penempatan</p>
                <p class="erp-settings-value">{{ $branchLabel }}</p>
            </div>
        </div>

        <div class="erp-settings-card erp-settings-options">
            <a href="{{ route('change-password') }}" class="erp-settings-option">
                <i class="ph ph-lock-key erp-settings-option-icon" aria-hidden="true"></i>
                <span class="erp-settings-option-label">Ganti Kata Sandi</span>
                <i class="ph ph-caret-right erp-settings-chevron" aria-hidden="true"></i>
            </a>
            <div class="erp-settings-option">
                <img class="erp-settings-language-mark" src="{{ asset('assets/media/flags/indonesia.svg') }}" alt="">
                <span class="erp-settings-option-label">Bahasa</span>
                <span class="erp-settings-option-value">Indonesia</span>
                <i class="ph ph-caret-right erp-settings-chevron" aria-hidden="true"></i>
            </div>
        </div>

        <form method="POST" action="{{ url('/logout') }}" class="erp-settings-logout-form">
            @csrf
            <button type="submit" class="erp-settings-logout">
                <i class="ph ph-sign-out" aria-hidden="true"></i>
                <span>Keluar</span>
            </button>
        </form>
    </section>
@endsection
