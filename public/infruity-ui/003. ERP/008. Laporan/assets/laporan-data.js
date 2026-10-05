/**
 * ============================================================
 *  INFRUITY ERP - SHARED DUMMY DATA
 *  File: 008. Laporan/assets/laporan-data.js
 * ============================================================
 *
 *  Tujuan:
 *  File ini menghasilkan SATU SET DATA TRANSAKSI yang dipakai
 *  BERSAMA oleh semua sub-modul Laporan:
 *    - 001. Pendapatan Penjualan  -> pakai globalReceiptData, globalSalesData
 *    - 005. Keuntungan Penjualan  -> pakai globalReceiptData, globalSalesData (+hppSatuan)
 *    - modul lain yang akan datang
 *
 *  Catatan untuk Developer (Backend Integration):
 *  Di production, gantikan file ini dengan API call ke backend.
 *  Struktur window.LAPORAN_DATA yang diekspos ke halaman:
 *
 *  globalReceiptData[] - Array transaksi individual (nota):
 *  {
 *    receiptNo    : string       - nomor nota, e.g. "INV-001"
 *    date         : string       - tanggal "YYYY-MM-DD"
 *    time         : string       - waktu "Jam HH:MM WIB"
 *    productName  : string       - nama produk
 *    branch       : string       - nama cabang
 *    paymentMethod: string|array - metode pembayaran (array = split payment)
 *    qty          : number       - jumlah satuan
 *    price        : number       - harga jual per satuan (Rp)
 *    hppSatuan    : number       - HPP per satuan dari harga pengadaan (Rp)
 *    diskonProduk : number       - diskon per produk (Rp)
 *    diskonProrata: number       - diskon prorata nota (Rp)
 *  }
 *
 *  globalSalesData[] - Array agregasi per tanggal+cabang+produk+harga:
 *  {
 *    productName  : string
 *    qty          : number  - total qty terjual
 *    unit         : string  - satuan (kg/pcs)
 *    price        : number  - harga jual
 *    branch       : string
 *    date         : string
 *    total        : number  - total pendapatan bersih (setelah diskon)
 *    totalHpp     : number  - total HPP (qty x hppSatuan)
 *  }
 * ============================================================
 */

(function () {

    // Seeded Random - agar angka KONSISTEN setiap refresh
    function makeRand(seed) {
        let s = seed;
        return function () {
            s = (s * 1664525 + 1013904223) & 0xffffffff;
            return (s >>> 0) / 0xffffffff;
        };
    }
    const rand = makeRand(20250811);

    const todayStr = new Date().toISOString().split('T')[0];
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const yesterdayStr = yesterday.toISOString().split('T')[0];

    const productTemplates = [
        { name: 'Alpukat Mentega A',         unit: 'kg',  price: 40000  },
        { name: 'Alpukad B',                 unit: 'kg',  price: 30000  },
        { name: 'Alpukat Ab',                unit: 'kg',  price: 40000  },
        { name: 'Anggur Aussy',              unit: 'kg',  price: 80000  },
        { name: 'Anggur Merah China Jumbo',  unit: 'kg',  price: 80000  },
        { name: 'Anggur Shine Muscat',       unit: 'kg',  price: 90000  },
        { name: 'Apel Fuji',                 unit: 'kg',  price: 40000  },
        { name: 'Apel Fuji',                 unit: 'kg',  price: 45000  },
        { name: 'Cavendish Sunpride',        unit: 'kg',  price: 20000  },
        { name: 'Fuji Blush Jumbo',          unit: 'kg',  price: 60000  },
        { name: 'Jeruk Medan',               unit: 'kg',  price: 30000  },
        { name: 'Mangga Harum Manis',        unit: 'kg',  price: 20000  },
        { name: 'Strawberry Mencir (Super)', unit: 'kg',  price: 70000  },
        { name: 'Jus Alpukat',              unit: 'pcs', price: 10000  },
        { name: 'Jus Mangga',               unit: 'pcs', price: 10000  },
        { name: 'Salad Buah Besar',          unit: 'pcs', price: 40000  },
        { name: 'Keripik Apel',             unit: 'pcs', price: 20000  },
        { name: 'Parcel Buah Premium',       unit: 'pcs', price: 150000 },
    ];

    const gresikBranches = [
        'Gresik / Gresik / Sidokumpul - 001',
        'Gresik / Gresik / Sidokumpul - 002',
        'Gresik / Kebomas / Randuagung - 001',
        'Gresik / Manyar / Yosowilangun - 001',
        'Gresik / Menganti / Pelemwatu - 001',
        'Gresik / Driyorejo / Petiken - 001',
    ];

    const basePaymentMethods = ['Bank Jatim','Bank Mandiri','BCA','BNI','BRI','GoPay','Qris','ShopeePay','Tunai'];

    function getPaymentMethod() {
        if (rand() > 0.3) return basePaymentMethods[Math.floor(rand() * basePaymentMethods.length)];
        const count = Math.floor(rand() * 3) + 2;
        return [...basePaymentMethods].sort(() => rand() - 0.5).slice(0, count);
    }

    const receiptData  = [];
    const salesDataMap = new Map();
    let receiptCounter = 1;

    for (let dayOffset = 0; dayOffset <= 1095; dayOffset++) {
        const d = new Date();
        d.setDate(d.getDate() - dayOffset);
        const pad = n => String(n).padStart(2, '0');
        const dateStr = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());

        gresikBranches.forEach(branch => {
            const numReceipts = Math.floor(rand() * 15) + 10;
            for (let i = 0; i < numReceipts; i++) {
                const prod         = productTemplates[Math.floor(rand() * productTemplates.length)];
                const qty          = Math.floor(rand() * 150) + 50; // qty 50-200 agar total pendapatan ratusan juta
                const timeStr      = `Jam ${String(Math.floor(rand()*10)+8).padStart(2,'0')}:${String(Math.floor(rand()*60)).padStart(2,'0')} WIB`;
                const currentPrice = rand() > 0.85 ? prod.price + 10000 : prod.price;
                const hppSatuan    = Math.round(currentPrice * (0.45 + rand() * 0.33));
                let diskonProduk   = rand() > 0.6 ? (Math.floor(rand() * 2) + 1) * 10000 : 0;
                let diskonProrata  = rand() > 0.7 ? 10000 : 0;
                const grossTotal   = qty * currentPrice;
                if ((diskonProduk + diskonProrata) >= grossTotal) { diskonProduk = 0; diskonProrata = 0; }

                receiptData.push({ receiptNo: 'INV-' + String(receiptCounter++).padStart(3,'0'), date: dateStr, time: timeStr, productName: prod.name, branch, paymentMethod: getPaymentMethod(), qty, price: currentPrice, hppSatuan, diskonProduk, diskonProrata });

                const mapKey = `${dateStr}_${branch}_${prod.name}_${currentPrice}`;
                if (!salesDataMap.has(mapKey)) salesDataMap.set(mapKey, { productName: prod.name, qty: 0, unit: prod.unit, price: currentPrice, branch, date: dateStr, total: 0, totalHpp: 0 });
                const agg = salesDataMap.get(mapKey);
                agg.qty += qty; agg.total += (grossTotal - diskonProduk - diskonProrata); agg.totalHpp += (qty * hppSatuan);
            }
        });
    }

    // Demo data hari ini - selalu ada 2 harga berbeda untuk demo
    const demoProduct = productTemplates[0]; const branchForDemo = gresikBranches[0];
    const p1 = demoProduct.price; const hpp1 = Math.round(p1 * 0.62);
    receiptData.push({ receiptNo: 'INV-DEMO-1', date: todayStr, time: 'Jam 08:30 WIB', productName: demoProduct.name, branch: branchForDemo, paymentMethod: ['Tunai','GoPay','ShopeePay','BCA'], qty: 2, price: p1, hppSatuan: hpp1, diskonProduk: 0, diskonProrata: 0 });
    const k1 = `${todayStr}_${branchForDemo}_${demoProduct.name}_${p1}`;
    if (!salesDataMap.has(k1)) salesDataMap.set(k1, { productName: demoProduct.name, qty: 0, unit: demoProduct.unit, price: p1, branch: branchForDemo, date: todayStr, total: 0, totalHpp: 0 });
    const it1 = salesDataMap.get(k1); it1.qty += 2; it1.total += (2*p1); it1.totalHpp += (2*hpp1);

    const p2 = demoProduct.price + 10000; const hpp2 = Math.round(p2 * 0.55);
    receiptData.push({ receiptNo: 'INV-DEMO-2', date: todayStr, time: 'Jam 13:45 WIB', productName: demoProduct.name, branch: branchForDemo, paymentMethod: 'Tunai', qty: 3, price: p2, hppSatuan: hpp2, diskonProduk: 0, diskonProrata: 0 });
    const k2 = `${todayStr}_${branchForDemo}_${demoProduct.name}_${p2}`;
    if (!salesDataMap.has(k2)) salesDataMap.set(k2, { productName: demoProduct.name, qty: 0, unit: demoProduct.unit, price: p2, branch: branchForDemo, date: todayStr, total: 0, totalHpp: 0 });
    const it2 = salesDataMap.get(k2); it2.qty += 3; it2.total += (3*p2); it2.totalHpp += (3*hpp2);

    receiptData.sort((a, b) => a.date > b.date ? -1 : (a.date < b.date ? 1 : 0));

    // ============================================================
    //  DUMMY STOK OPNAME
    //  Catatan developer: Di production, gantikan dengan API call.
    //
    //  globalOpnameData[] — array koreksi stok per hari per cabang:
    //  {
    //    date        : "YYYY-MM-DD"
    //    branch      : string
    //    productName : string
    //    unit        : string
    //    selisihQty  : number   — negatif = susut/hilang, positif = koreksi masuk
    //    hargaSatuan : number   — harga jual produk (Rp)
    //    selisihRp   : number   — selisihQty × hargaSatuan (sudah jadi Rp)
    //    keterangan  : string   — "Susut Alami" | "Busuk" | "Hilang" | "Koreksi Masuk"
    //  }
    // ============================================================
    const opnameData = [];
    const keteranganNegatif = ['Susut Alami', 'Busuk', 'Hilang'];

    for (let dayOffset = 0; dayOffset <= 1095; dayOffset++) {
        const d = new Date();
        d.setDate(d.getDate() - dayOffset);
        const pad = n => String(n).padStart(2, '0');
        const dateStr = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());

        // ~70% hari dilakukan opname
        if (rand() > 0.3) {
            gresikBranches.forEach(branch => {
                // 1-4 produk yang dikoreksi per cabang per hari
                const numItems = Math.floor(rand() * 4) + 1;
                for (let i = 0; i < numItems; i++) {
                    const prod = productTemplates[Math.floor(rand() * productTemplates.length)];
                    const isNegative = rand() < 0.8; // 80% susut/hilang
                    const selisihQty = (Math.floor(rand() * 5) + 1) * (isNegative ? -1 : 1);
                    const hargaSatuan = prod.price;
                    const selisihRp   = selisihQty * hargaSatuan;
                    const keterangan  = isNegative
                        ? keteranganNegatif[Math.floor(rand() * keteranganNegatif.length)]
                        : 'Koreksi Masuk';

                    opnameData.push({
                        date: dateStr,
                        branch,
                        productName: prod.name,
                        unit:        prod.unit,
                        selisihQty,
                        hargaSatuan,
                        selisihRp,
                        keterangan,
                    });
                }
            });
        }
    }

    // Demo opname hari ini — pastikan selalu ada data hari ini
    gresikBranches.forEach((branch, idx) => {
        const prod = productTemplates[idx % productTemplates.length];
        opnameData.push({
            date: todayStr, branch,
            productName: prod.name, unit: prod.unit,
            selisihQty: -(Math.floor(rand() * 3) + 1),
            hargaSatuan: prod.price,
            selisihRp:   -(Math.floor(rand() * 3) + 1) * prod.price,
            keterangan:  'Busuk',
        });
    });

    window.LAPORAN_DATA = {
        globalReceiptData: receiptData,
        globalSalesData:   Array.from(salesDataMap.values()),
        globalOpnameData:  opnameData,
        productTemplates,
        gresikBranches,
        todayStr,
        yesterdayStr,
    };

})();
