@extends('layouts.erp-tailwind')

@section('title', 'Produk - Master')
@section('hide-global-header', '1')

@section('page-title', 'Produk')
@section('page-breadcrumb')
    <span>Master</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Produk</span>
@endsection

@section('extra-style')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .product-create-overlay #add_product_form { flex-direction: column !important; padding: 0 !important; }
        .product-create-overlay #add_product_form > div:first-child { width: 100% !important; margin: 0 !important; }
        .product-create-overlay #add_product_form > div:last-child { width: 100% !important; }
        .product-create-overlay #add_product_form .product-form-actions { display: none !important; }
    </style>
@endsection

@section('content')
    <style>
        #products-table.dataTable { border-collapse: collapse !important; border: none !important; table-layout: fixed !important; width: 100% !important; }
        @media (max-width: 767px) {
            .product-list-fullbleed { width: 100vw !important; max-width: 100vw !important; margin-left: calc(50% - 50vw) !important; margin-right: calc(50% - 50vw) !important; }
            #products-table.dataTable,
            #products-table.dataTable tbody,
            #products-table.dataTable tbody tr { display: block !important; width: 100% !important; }
            #products-table.dataTable tbody td:nth-child(1) { display: block !important; width: 100% !important; }
            #products-table.dataTable { table-layout: auto !important; }
        }
        @media (min-width: 768px) {
            .product-list-fullbleed { width: 100% !important; max-width: 100% !important; margin-left: 0 !important; margin-right: 0 !important; }
        }
        #products-table.dataTable tbody td { overflow: hidden; }
        #products-table.dataTable tbody td:nth-child(1) { width: 64% !important; }
        #products-table.dataTable tbody td:nth-child(2) { width: 36% !important; }
        @media (max-width: 767px) {
            #products-table.dataTable tbody td:nth-child(1) { width: 100% !important; }
            #products-table.dataTable tbody td:nth-child(2),
            #products-table.dataTable tbody td:nth-child(3),
            #products-table.dataTable tbody td:nth-child(4) { display: none !important; }
        }
        #products-table.dataTable thead { display: none !important; }
        #products-table.dataTable thead th { border: none !important; }
        #products-table.dataTable tbody td { border: none !important; padding: 0 !important; }
        #products-table.dataTable tbody tr { background: transparent !important; border-bottom: 1px solid #f3f4f6 !important; }
        #products-table.dataTable tbody tr td { background: transparent !important; }
        #products-table_wrapper .dataTables_length,
        #products-table_wrapper .dataTables_filter,
        #products-table_wrapper .dataTables_info,
        #products-table_wrapper .dataTables_paginate { display: none !important; }
    </style>

    <div x-data="{
            filterOpen: false,
            typeExpanded: false,
            mobileSearchOpen: false,
            productCreateOpen: @js($errors->any()),
            isScrolling: false,
            scrollTimer: null,
            branchSearchQuery: '',
            allBranches: @js($branch->map(fn($item) => ['id' => $item->id, 'name' => ucwords($item->name)])->values()),
            allTypes: @js($productTypes->values()),
            defaultBranchId: {{ $branch->first()->id ?? 0 }},
            selectedBranchId: {{ $branch->first()->id ?? 0 }},
            selectedType: [],
            tempSelectedType: [],
            tempSelectedBranchId: {{ $branch->first()->id ?? 0 }},
            hasActiveFilters: false,
            get typeLabel() {
                if (this.selectedType.length === 0) return 'Semua';
                if (this.selectedType.length === 1) return ucwordsJs(this.selectedType[0]);
                return this.selectedType.length + ' Tipe';
            },
            get filteredBranches() {
                const q = this.branchSearchQuery.toLowerCase();
                return this.allBranches.filter(b => b.name.toLowerCase().includes(q));
            },
            syncHiddenInputs() {
                $('#branch-filter-value').val(this.selectedBranchId);
                $('#type-filter-value').val(JSON.stringify(this.selectedType));
            },
            refreshActiveFilters() {
                this.hasActiveFilters = this.selectedBranchId !== this.defaultBranchId || this.selectedType.length > 0;
                Alpine.store('productFilters', { active: this.hasActiveFilters });
            },
            toggleType(t) {
                if (t === 'Semua') {
                    this.selectedType = [];
                } else if (this.selectedType.includes(t)) {
                    this.selectedType = this.selectedType.filter(x => x !== t);
                } else {
                    this.selectedType.push(t);
                }
                this.syncHiddenInputs();
                this.refreshActiveFilters();
                reloadProductFilters();
            },
            toggleTempType(t) {
                if (t === 'Semua') {
                    this.tempSelectedType = [];
                } else if (this.tempSelectedType.includes(t)) {
                    this.tempSelectedType = this.tempSelectedType.filter(x => x !== t);
                } else {
                    this.tempSelectedType.push(t);
                }
            },
            openMobileFilter() {
                this.tempSelectedType = [...this.selectedType];
                this.tempSelectedBranchId = this.selectedBranchId;
                this.branchSearchQuery = '';
                this.filterOpen = true;
            },
            applyMobileFilters() {
                this.selectedType = [...this.tempSelectedType];
                this.selectedBranchId = this.tempSelectedBranchId;
                this.syncHiddenInputs();
                this.refreshActiveFilters();
                reloadProductFilters();
                this.filterOpen = false;
            }
        }"
         x-effect="document.body.classList.toggle('overflow-hidden', productCreateOpen)"
         x-init="syncHiddenInputs(); refreshActiveFilters()"
         @mobile-search-toggle.window="mobileSearchOpen = !mobileSearchOpen; filterOpen = false; if (mobileSearchOpen) $nextTick(() => $refs.mobileSearch.focus())"
         @mobile-filter-toggle.window="if (!filterOpen) { openMobileFilter() } else { filterOpen = false }; mobileSearchOpen = false"
         @mobile-add-toggle.window="productCreateOpen = true; mobileSearchOpen = false; filterOpen = false"
         @keydown.escape.window="productCreateOpen = false"
         class="product-list-fullbleed w-[calc(100%+2rem)] flex-1 flex flex-col min-h-0 -mx-4 md:w-full md:mx-0">
        <!-- Main Content Card -->
        <div class="bg-white rounded-none lg:rounded-2xl shadow-none lg:shadow-sm border-0 lg:border lg:border-gray-100 overflow-hidden flex-1 flex flex-col min-h-0 relative">

            <!-- Mobile floating count pill: shown only while the product list is scrolling -->
            <div x-show="isScrolling && !mobileSearchOpen" x-transition x-cloak class="md:hidden absolute top-2 left-0 right-0 z-30 pointer-events-none flex justify-center px-4">
                <div class="pointer-events-auto bg-white/95 backdrop-blur-md border border-gray-200/90 px-4 py-1.5 rounded-full shadow-lg text-[11px] text-gray-700 font-medium">
                    Menampilkan <strong id="products-count-display-mobile" class="text-gray-900 font-semibold">0</strong> dari <strong id="products-count-total-mobile" class="text-gray-900 font-semibold">0</strong> produk
                </div>
            </div>

            <!-- Mobile search overlay -->
            <div x-show="mobileSearchOpen" x-transition x-cloak class="md:hidden fixed left-0 right-0 z-[80] px-4 flex justify-center" style="bottom: calc(96px + env(safe-area-inset-bottom))">
                <div class="relative w-full max-w-[360px] h-12 bg-white/80 backdrop-blur-xl border border-white/70 rounded-full shadow-lg flex items-center">
                    <i class="ph ph-magnifying-glass absolute left-4 text-[#0b595b]/70"></i>
                    <input x-ref="mobileSearch" type="search" placeholder="Cari produk atau varian..." oninput="$('#search').val(this.value).trigger('keyup')" class="w-full bg-transparent pl-11 pr-10 h-full text-sm font-medium text-gray-800 placeholder-gray-500 outline-none rounded-full">
                    <button type="button" @click="$refs.mobileSearch.value=''; $('#search').val('').trigger('keyup'); $refs.mobileSearch.focus()" class="absolute right-3 text-gray-500"><i class="ph ph-x"></i></button>
                </div>
            </div>

            <!-- ================================ -->
            <!-- MOBILE FILTER BOTTOM SHEET       -->
            <!-- ================================ -->
            <template x-teleport="body">
            <div x-show="filterOpen" class="md:hidden fixed top-0 left-0 right-0 bottom-0 z-[100] overflow-hidden flex flex-col justify-end" x-cloak>
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/70" @click="filterOpen = false"
                     x-show="filterOpen"
                     x-transition:enter="transition-opacity ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-in duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"></div>

                <!-- Drawer Panel -->
                <div x-show="filterOpen"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     x-transition:leave="transition ease-in duration-300 transform"
                     x-transition:leave-start="translate-y-0"
                     x-transition:leave-end="translate-y-full"
                     class="w-full bg-white shadow-2xl flex flex-col max-h-[85vh] overflow-hidden rounded-t-3xl relative z-10 pb-[env(safe-area-inset-bottom)]">

                    <!-- Drag Handle -->
                    <div class="w-full flex justify-center pt-3 pb-1 shrink-0" @click="filterOpen = false" style="cursor: pointer;">
                        <div class="w-16 h-1.5 bg-gray-300 rounded-full"></div>
                    </div>

                    <!-- Header -->
                    <div class="bg-white shrink-0 px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800 text-lg">Filter</h3>
                        <button @click="filterOpen = false" class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-full transition-colors active:scale-95">
                            <i class="ph-bold ph-x"></i>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto overscroll-none p-4 scrollbar-hide">
                        <!-- Cabang -->
                        <div class="mb-6 px-2">
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Cabang</label>
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg flex flex-col overflow-hidden shadow-sm">
                                <!-- Search Box -->
                                <div class="relative border-b border-gray-100 shrink-0">
                                    <i class="ph-bold ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    <input type="text" x-model="branchSearchQuery" placeholder="Cari cabang..."
                                           class="w-full bg-transparent pl-8 pr-3 py-3 text-xs outline-none focus:bg-gray-50 transition-colors">
                                </div>

                                <!-- List -->
                                <div class="relative w-full h-[220px]"
                                     x-data="{
                                         isScrolling: false,
                                         scrollTimeout: null,
                                         scrollPos: 0,
                                         thumbHeight: 20,
                                         updateScroll() {
                                             const el = $refs.scrollArea;
                                             if (!el) return;
                                             if (el.scrollHeight <= el.clientHeight) {
                                                 this.isScrolling = false;
                                                 return;
                                             }
                                             const ratio = el.clientHeight / el.scrollHeight;
                                             this.thumbHeight = Math.max(ratio * el.clientHeight, 20);
                                             this.scrollPos = (el.scrollTop / el.scrollHeight) * el.clientHeight;
                                             this.isScrolling = true;
                                             clearTimeout(this.scrollTimeout);
                                             this.scrollTimeout = setTimeout(() => { this.isScrolling = false; }, 800);
                                         }
                                     }"
                                     x-init="$watch('branchSearchQuery', () => { $nextTick(() => updateScroll()) })">
                                    <div x-ref="scrollArea" @scroll="updateScroll()" class="h-full overflow-y-auto overscroll-none bg-gray-50/30 scrollbar-hide">
                                        <template x-for="b in filteredBranches" :key="b.id">
                                            <label class="flex items-center gap-3 px-3 py-3 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors last:border-b-0">
                                                <div class="relative flex items-center justify-center w-4 h-4 shrink-0">
                                                    <input type="radio" name="cabangFilter" :value="b.id" x-model.number="tempSelectedBranchId" class="peer appearance-none w-4 h-4 border-2 border-gray-300 rounded-full checked:border-emerald-500 transition-colors cursor-pointer">
                                                    <div class="absolute w-2 h-2 bg-emerald-500 rounded-full scale-0 peer-checked:scale-100 transition-transform duration-200"></div>
                                                </div>
                                                <span class="text-xs text-gray-700" :class="tempSelectedBranchId == b.id ? 'font-bold text-gray-900' : 'font-medium'" x-text="b.name"></span>
                                            </label>
                                        </template>
                                        <div x-show="filteredBranches.length === 0" class="text-center py-4 text-xs text-gray-400">
                                            Cabang tidak ditemukan
                                        </div>
                                    </div>
                                    <div class="absolute top-0 right-0.5 w-1 h-full pointer-events-none py-1">
                                        <div class="w-full bg-gray-300 rounded-full transition-opacity duration-300 ease-in-out"
                                             :style="`height: ${thumbHeight}px; transform: translateY(${scrollPos}px);`"
                                             :class="isScrolling ? 'opacity-100' : 'opacity-0'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tipe Produk -->
                        <div class="mb-0 px-2">
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe Produk</label>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button @click="toggleTempType('Semua')"
                                    class="px-3 py-1.5 rounded-full border text-xs font-medium transition-colors"
                                    :class="tempSelectedType.length === 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:border-emerald-300'">
                                    Semua
                                </button>
                                <template x-for="t in allTypes" :key="t">
                                    <button @click="toggleTempType(t)"
                                        class="px-3 py-1.5 rounded-full border text-xs font-medium transition-colors capitalize"
                                        :class="tempSelectedType.includes(t) ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:border-emerald-300'">
                                        <span x-text="t"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="shrink-0 px-4 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100 bg-white flex items-center gap-2">
                        <button @click="filterOpen = false" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button @click="applyMobileFilters()" class="flex-1 h-11 rounded-xl bg-[#0b595b] hover:bg-[#0a4e50] text-white text-sm font-semibold transition-colors shadow-sm">
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>
            </template>

            <!-- Toolbar: Search + Filter + Add (desktop only; mobile uses the bottom nav pill + mobile header) -->
            <div class="hidden md:flex flex-row gap-3 items-center p-4 border-b border-gray-100 bg-white relative z-10">
                <!-- Search -->
                <div class="flex-1 relative w-full">
                    <i class="ph-bold ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" data-kt-ecommerce-product-filter="search" id="search"
                        class="w-full bg-gray-50/50 border border-gray-100 rounded-full pl-10 pr-4 h-11 text-[13px] outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all"
                        placeholder="Cari produk atau varian..." />
                </div>

                <!-- Filter (Tipe Produk) -->
                <div class="relative shrink-0" x-data="{}" @mouseleave="if(!filterOpen) typeExpanded = false">
                    <button type="button" @click="filterOpen = !filterOpen; typeExpanded = true" @mouseenter="typeExpanded = true"
                        class="flex items-center bg-white rounded-full border h-11 p-1 shadow-sm transition-colors duration-300 shrink-0"
                        :class="filterOpen ? 'border-emerald-500' : 'border-gray-200 hover:border-emerald-400'">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center transition-colors duration-300 z-10 shrink-0"
                            :class="typeExpanded ? 'bg-emerald-50 text-emerald-600' : 'bg-white text-gray-600'">
                            <i class="ph ph-funnel text-[20px]"></i>
                        </div>
                        <div class="overflow-hidden transition-all duration-500 ease-out flex items-center"
                             :class="typeExpanded ? 'max-w-[280px] opacity-100 pr-4' : 'max-w-0 opacity-0 pr-0'">
                            <span class="text-[13px] font-medium text-gray-700 whitespace-nowrap pl-2" x-text="typeLabel"></span>
                            <i class="ph-bold ph-caret-down text-[10px] ml-2 transition-transform duration-200 text-gray-400" :class="filterOpen ? 'rotate-180' : ''"></i>
                        </div>
                    </button>

                    <div x-show="filterOpen" x-cloak @click.outside="filterOpen = false; typeExpanded = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 top-14 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50 py-2">
                        <div class="px-4 py-2.5 border-b border-gray-50">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe Produk</p>
                        </div>
                        <button @click="toggleType('Semua')"
                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition flex items-center justify-between"
                            :class="selectedType.length === 0 ? 'text-emerald-700 font-semibold bg-emerald-50/50' : 'text-gray-600'">
                            <span>Semua</span>
                            <i x-show="selectedType.length === 0" class="ph-fill ph-circle text-emerald-500 text-[8px] shrink-0"></i>
                        </button>
                        <template x-for="t in allTypes" :key="t">
                            <button @click="toggleType(t)"
                                class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition flex items-center justify-between capitalize"
                                :class="selectedType.includes(t) ? 'text-emerald-700 font-semibold bg-emerald-50/50' : 'text-gray-600'">
                                <span x-text="t"></span>
                                <i x-show="selectedType.includes(t)" class="ph-fill ph-circle text-emerald-500 text-[8px] shrink-0"></i>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Add Button -->
                @if ($canCreateProduct)
                    <button type="button" @click="productCreateOpen = true"
                        class="flex items-center gap-2 bg-[#0b595b] hover:bg-[#0a4e50] text-white rounded-full h-11 px-5 text-[13px] font-semibold transition-colors shadow-sm shrink-0">
                        <i class="ph-bold ph-plus text-sm"></i>
                        <span>Tambah Produk</span>
                    </button>
                @endif
            </div>

            <!-- Hidden request-contract inputs (read by DataTables ajax.data) -->
            <input type="hidden" id="branch-filter-value" data-kt-ecommerce-product-filter="branch">
            <input type="hidden" id="type-filter-value" data-kt-ecommerce-product-filter="type">
            <input type="hidden" id="category-filter-value" data-kt-ecommerce-product-filter="category" value="all">
            <!-- Product Count (desktop only) -->
            <div class="hidden md:flex sticky top-0 z-20 px-4 py-3 bg-gray-50/50 backdrop-blur-md border-b border-gray-100 items-center justify-between">
                <span class="text-xs text-gray-400 font-medium">Menampilkan <span id="products-count-display" class="text-gray-700 font-semibold">0</span> dari <span id="products-count-total" class="text-gray-700 font-semibold">0</span> produk</span>
            </div>

            <!-- Table -->
            <div class="flex-1 overflow-auto pb-24 md:pb-0" @scroll.passive="isScrolling = true; clearTimeout(scrollTimer); scrollTimer = setTimeout(() => isScrolling = false, 600)">
                <table class="w-full text-sm" id="products-table" width="100%">
                    <thead class="hidden md:table-header-group">
                        <tr class="text-left text-gray-400 font-semibold text-[11px] uppercase tracking-wide">
                            <th class="py-2 px-5 min-w-[200px]">Product</th>
                            <th class="py-2 px-5 text-right min-w-[100px]">Harga</th>
                            <th class="py-2 px-5 text-right min-w-[70px]">Satuan</th>
                            <th class="py-2 px-5 text-right min-w-[70px]"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-gray-700"></tbody>
                </table>
                <div id="products-load-more" class="hidden px-5 py-4">
                    <button type="button" class="w-full h-11 rounded-xl border-2 border-dashed border-gray-200 text-sm font-semibold text-gray-500 hover:border-emerald-400 hover:text-emerald-700 hover:bg-emerald-50 transition-colors flex items-center justify-center gap-2">
                        <i class="ph ph-arrow-circle-down text-base"></i> Muat Produk Lagi
                    </button>
                </div>
            </div>
        </div>
    @if ($canCreateProduct)
        <template x-teleport="body">
            <div x-show="productCreateOpen" x-cloak
                class="fixed inset-0 z-[100] overflow-hidden flex flex-col justify-end lg:flex-row lg:justify-end"
                role="dialog" aria-modal="true" aria-labelledby="product-create-title">
                <div x-show="productCreateOpen" x-transition:enter="transition-opacity ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    @click="productCreateOpen = false" class="absolute inset-0 bg-black/60"></div>

                <section x-show="productCreateOpen" x-init="initializeProductCreateForm($el)"
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full"
                    x-transition:enter-end="translate-y-0 lg:translate-x-0"
                    x-transition:leave="transition ease-in duration-200 transform"
                    x-transition:leave-start="translate-y-0 lg:translate-y-0 lg:translate-x-0"
                    x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full"
                    class="product-create-overlay relative z-10 flex h-[85vh] max-h-[100dvh] w-full flex-col overflow-hidden rounded-t-3xl border-t border-gray-100 bg-white shadow-2xl md:mx-auto md:max-w-lg lg:mx-0 lg:h-full lg:max-w-sm lg:rounded-none lg:border-l lg:border-t-0">
                    <div class="flex shrink-0 justify-center pt-3 pb-1 lg:hidden" @click="productCreateOpen = false" style="cursor: pointer;">
                        <div class="h-1.5 w-16 rounded-full bg-gray-300"></div>
                    </div>
                    <header class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-3.5">
                        <div>
                            <h2 id="product-create-title" class="text-[15px] font-bold text-gray-900">Tambah Produk</h2>
                            <p class="mt-0.5 text-xs text-gray-400">Isi data produk baru</p>
                        </div>
                        <button type="button" @click="productCreateOpen = false"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:bg-gray-200"
                            aria-label="Tutup formulir">
                            <i class="ph-bold ph-x text-base"></i>
                        </button>
                    </header>
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-gray-50/60 px-4 py-4 md:px-5">
                        @include('master::products.partials.create-form')
                    </div>
                    <footer class="flex shrink-0 items-center gap-3 border-t border-gray-100 bg-white px-5 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                        <button type="button" @click="productCreateOpen = false"
                            class="h-11 flex-1 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Batal</button>
                        <button type="submit" form="add_product_form" id="product-create-submit"
                            class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white shadow-sm transition hover:bg-[#0a4e50]">
                            <span>Tambah Produk</span>
                        </button>
                    </footer>
                </section>
            </div>
        </template>
    @endif
    </div>

    @include('master::products.partials.create-script')

    <script type="text/javascript">
        var dataTable;

        function formatNumber(num) {
            if (num === null || num === undefined) return '0';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function ucwordsJs(str) {
            return (str || '').toString().replace(/\w\S*/g, function(txt) {
                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
            });
        }

        function reloadProductFilters() {
            if (typeof dataTable !== 'undefined') {
                dataTable.draw();
            }
        }

        $(document).ready(function() {
            dataTable = $('#products-table').DataTable({
                pageLength: 20,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                order: [],
                columnDefs: [{
                    orderable: false,
                    targets: -1
                }],
                drawCallback: function(settings) {
                    var info = this.api().page.info();
                    $('#products-count-display, #products-count-display-mobile').text(formatNumber(info.end - info.start));
                    $('#products-count-total, #products-count-total-mobile').text(formatNumber(info.recordsDisplay));
                    $('#products-load-more').toggleClass('hidden', info.end >= info.recordsDisplay);
                },
                ajax: {
                    url: "{{ route('products-data') }}",
                    data: function(d) {
                        d.searchValue = $('#search').val();
                        d.url = "{{ request()->segment(1) }}";
                        d.branch_filter = $('[data-kt-ecommerce-product-filter="branch"]').val();
                        d.category_filter = $('[data-kt-ecommerce-product-filter="category"]').val();

                        var rawType = $('[data-kt-ecommerce-product-filter="type"]').val();
                        var typeValue = 'all';
                        if (rawType) {
                            try {
                                var parsed = JSON.parse(rawType);
                                typeValue = (Array.isArray(parsed) && parsed.length) ? parsed : 'all';
                            } catch (e) {
                                typeValue = rawType;
                            }
                        }
                        d.type_filter = typeValue;
                    }
                },
                columns: [
                    {
                        data: 'name',
                        name: 'products.name',
                        className: 'p-0 align-top'
                    },
                    {
                        data: 'price',
                        name: 'price',
                        className: 'p-0 align-top text-right'
                    },
                    {
                        data: 'unit_abbreviation',
                        name: 'product_units.abbreviation',
                        className: 'hidden md:table-cell text-right'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'hidden md:table-cell text-right'
                    },
                ]
            });

            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('#products-load-more button').on('click', function() {
                dataTable.page.len(dataTable.page.len() + 20).draw();
            });
        });

        function reloadDataTable() {
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false);
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
                        url: `/products/${id}`,
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
                                timer: 1500
                            });
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

        $('#products-table').on('click', '.editable-price', function() {
            var $span = $(this);
            var currentValue = $span.data('value');
            var id = $span.data('id');

            var input = $('<input type="number" min="0" step="1" class="w-28 h-9 rounded-lg border border-emerald-500 px-2 text-right text-sm outline-none">')
                .val(currentValue)
                .blur(function() {
                    var newValue = $(this).val();
                    if (newValue != currentValue) {
                        updatePrice(id, newValue);
                    } else {
                        $span.text(currentValue).show();
                        $(this).remove();
                    }
                })
                .keypress(function(e) {
                    if (e.which === 13) {
                        $(this).blur();
                    }
                });

            $span.hide().after(input);
            input.focus().select();

        });

        function updatePrice(id, price) {
            $.ajax({
                url: `/products/${id}/update-price`,
                type: 'PUT',
                data: {
                    price: price,
                    branch_id: $('[data-kt-ecommerce-product-filter="branch"]').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    dataTable.ajax.reload(null, false);
                },
                error: function() {
                    Swal.fire('Gagal', 'Tidak bisa memperbarui harga', 'error');
                    dataTable.ajax.reload(null, false);
                }
            });
        }

    </script>
@endsection
