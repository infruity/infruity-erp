<div class="modal fade so-reference-sheet" id="so-filter-sheet" tabindex="-1" aria-labelledby="so-filter-title" aria-hidden="true" data-so-timezone="{{ config('app.timezone') }}">
    <div class="modal-dialog"><div class="modal-content">
        <div class="so-sheet-handle" aria-hidden="true"><span></span></div>
        <header class="so-sheet-header"><div><h2 id="so-filter-title">Filter Stok Opname</h2><p>Sesuaikan hasil opname, cabang, status, dan periode</p></div><button type="button" class="so-close" data-bs-dismiss="modal" aria-label="Tutup filter"><i class="ph ph-x" aria-hidden="true"></i></button></header>
        <div class="so-sheet-body">
            <div id="so-filter-main">
                <fieldset class="so-filter-section" disabled aria-describedby="so-filter-limit"><legend>Hasil Opname</legend><div class="so-filter-grid so-filter-grid--four"><button type="button" class="is-selected">Semua</button><button type="button"><i class="ph-bold ph-arrow-down-left" aria-hidden="true"></i>Lebih</button><button type="button"><i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i>Kurang</button><button type="button">Sesuai</button></div></fieldset>
                <fieldset class="so-filter-section" disabled aria-describedby="so-filter-limit"><legend>Status Opname</legend><div class="so-filter-chips"><button type="button" class="is-selected">Semua Status</button><button type="button">Selesai</button><button type="button">Draf</button></div></fieldset>
                <p id="so-filter-limit" class="so-filter-limit">Filter hasil dan status belum didukung endpoint saat ini.</p>
                <fieldset class="so-filter-section"><legend class="so-filter-heading"><span>Cabang</span><button type="button" data-so-reset="branch" aria-label="Reset filter cabang" hidden>Reset</button></legend><div class="so-branch-picker"><label class="so-branch-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="so-filter-branch-search" placeholder="Cari cabang…" aria-label="Cari cabang"></label><div class="so-branch-options">
                    <label><input type="radio" name="so-filter-draft-branch" value="all"><span>Semua Cabang</span></label>
                    @foreach($branches as $branch)
                        <label><input type="radio" name="so-filter-draft-branch" value="{{ $branch->id }}"><span>{{ ucwords($branch->name) }}</span></label>
                    @endforeach
                    <p class="so-branch-empty" hidden>Cabang tidak ditemukan</p>
                </div></div></fieldset>
                <fieldset class="so-filter-section"><legend class="so-filter-heading"><span>Periode Tanggal</span><button type="button" data-so-reset="period" aria-label="Reset periode tanggal" hidden>Reset</button></legend><div class="so-filter-dates"><button type="button" data-so-date="start"><span>DARI</span><strong id="so-date-start-label">Pilih Tanggal</strong></button><button type="button" data-so-date="end"><span>KE</span><strong id="so-date-end-label">Pilih Tanggal</strong></button></div><div class="so-filter-grid so-period-presets"><button type="button" data-so-period="week">Minggu Ini</button><button type="button" data-so-period="today">Hari Ini</button></div></fieldset>
            </div>
            <div id="so-filter-calendar" hidden><button type="button" id="so-calendar-back" class="so-calendar-back"><i class="ph ph-caret-left" aria-hidden="true"></i>Kembali ke Filter</button><label class="visually-hidden" for="so-draft-calendar">Pilih rentang tanggal</label><input id="so-draft-calendar" type="text" hidden><div id="so-calendar-host"></div></div>
        </div>
        <footer class="so-sheet-footer"><button type="button" id="so-filter-reset">Reset Filter</button><button type="button" class="is-primary" id="so-filter-apply">Terapkan Filter</button></footer>
    </div></div>
</div>

<div class="modal fade so-reference-sheet" id="so-history-filter" tabindex="-1" aria-labelledby="so-history-filter-title" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="so-sheet-handle" aria-hidden="true"><span></span></div>
        <header class="so-sheet-header"><div><h2 id="so-history-filter-title">Filter Riwayat Stok Opname</h2><p>Filter berdasarkan jenis aktivitas dan arah koreksi</p></div><button type="button" class="so-close" data-bs-dismiss="modal" aria-label="Tutup filter riwayat"><i class="ph ph-x" aria-hidden="true"></i></button></header>
        <div class="so-sheet-body"><fieldset class="so-filter-section"><legend>Aktivitas</legend><div class="so-filter-grid" id="so-history-directions">
            <button type="button" data-so-direction="all">Semua</button><button type="button" data-so-direction="plus">↙ Kredit Lebih</button><button type="button" data-so-direction="minus">↗ Kredit Kurang</button><button type="button" data-so-direction="initial">Stok Awal</button>
        </div></fieldset></div>
        <footer class="so-sheet-footer"><button type="button" id="so-history-reset">Reset Filter</button><button type="button" id="so-history-apply" class="is-primary">Terapkan Filter</button></footer>
    </div></div>
</div>
