<x-erp.filter-sheet
    id="so"
    title="Filter Stok Opname"
    aria-label="Filter stok opname"
    subtitle="Sesuaikan hasil opname, cabang, status, dan periode"
    branch-label="Cabang"
    branch-all-label="Semua Cabang"
    status-label="Status Opname"
    :branches="$branches"
    :statuses="['all' => 'Semua Status', 'selesai' => 'Selesai', 'draft' => 'Draf']"
    :groups="[['key' => 'result', 'label' => 'Hasil Opname', 'options' => ['all' => 'Semua', 'lebih' => 'Lebih', 'kurang' => 'Kurang', 'sesuai' => 'Sesuai']]]"
    :timezone="config('app.timezone')"
    desktop="modal"
    reset-applies
    reset-label="Reset Filter"
    apply-label="Terapkan Filter"
/>
<input type="hidden" id="so-filter-applied-result" value="all">
<input type="hidden" id="so-filter-applied-status" value="all">

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
