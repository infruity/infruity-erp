<div x-show="confirmModalOpen" class="fixed inset-0 z-[130] flex items-center justify-center p-4" x-cloak id="sortirConfirmModal" @keydown.escape.window="confirmModalOpen = false">
    <!-- Backdrop -->
    <div x-show="confirmModalOpen"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="confirmModalOpen = false"
         class="absolute inset-0 bg-black/50 backdrop-blur-xs"></div>

    <!-- Modal Card -->
    <div x-show="confirmModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-90 translate-y-3"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90 translate-y-3"
         @click.outside="confirmModalOpen = false"
         class="relative z-10 bg-white rounded-3xl shadow-2xl border border-gray-100 w-full max-w-sm overflow-hidden p-6 text-center">
        
        <!-- Judul & Pesan -->
        <h4 class="text-base font-bold text-gray-900 mb-1.5">Selesaikan Transaksi?</h4>
        <p class="text-xs text-gray-500 leading-relaxed mb-4">
            Apakah transaksi sudah sesuai dan<br>tidak ada tambahan lagi?
        </p>

        <!-- Ringkasan Singkat Transaksi -->
        <div class="bg-gray-50 rounded-2xl p-3.5 border border-gray-100 mb-5 text-left text-xs space-y-1.5">
            <div class="flex items-center justify-between">
                <span class="text-gray-500 font-medium">Cabang Tujuan</span>
                <span class="font-bold text-gray-800 truncate max-w-[170px]" x-text="selectedBranchName || '-'"></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500 font-medium">Jumlah Item</span>
                <span class="font-bold text-gray-800" x-text="cart.length"></span>
            </div>
            <div class="flex items-center justify-between pt-1.5 border-t border-gray-200/60">
                <span class="font-semibold text-gray-700">Total HPP / Modal</span>
                <span class="font-extrabold text-[#0b595b] text-sm" x-text="rupiah(total)"></span>
            </div>
        </div>

        <!-- 2 Tombol Aksi: Batal & Selesaikan -->
        <div class="flex items-center gap-2.5">
            <button type="button" @click="confirmModalOpen = false"
                    class="flex-1 h-10 rounded-xl border border-gray-200 text-xs font-bold text-gray-700 hover:bg-gray-50 active:scale-95 transition cursor-pointer">
                Batal
            </button>
            <button type="button" @click="executeComplete()" :disabled="saving"
                    class="flex-1 h-10 rounded-xl bg-[#0b595b] hover:bg-[#08484a] text-white text-xs font-bold shadow-md shadow-[#0b595b]/20 active:scale-95 transition flex items-center justify-center cursor-pointer">
                <span x-text="saving ? 'Menyimpan...' : 'Selesaikan'"></span>
            </button>
        </div>
    </div>
</div>
