<!-- resources/views/pos.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS with Laravel + Alpine.js + Bootstrap</title>
    <script defer src="https://unpkg.com/alpinejs@3.17.5/dist/cdn.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light py-5">
    <div x-data="posApp()" class="container">
        <h1 class="text-center mb-5 text-primary"><i class="fas fa-store"></i> Point of Sale</h1>

        <!-- Product List -->
        <div class="mb-5">
            <h4 class="mb-3 border-bottom pb-2"><i class="fas fa-box-open"></i> Daftar Produk</h4>
            <div class="list-group shadow-sm">
                <template x-for="product in products" :key="product.id">
                    <button @click="addToCart(product)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1" x-text="product.name"></h5>
                            <small class="text-muted">Rp <span x-text="product.price.toLocaleString()"></span></small>
                        </div>
                        <span class="badge bg-primary rounded-pill"><i class="fas fa-cart-plus"></i> Tambah</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Cart -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Keranjang</h5>
            </div>
            <div class="card-body">
                <template x-if="cart.length === 0">
                    <p class="text-center text-muted">Belum ada produk di keranjang.</p>
                </template>
                <template x-for="item in cart" :key="item.id">
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong x-text="item.name"></strong>
                            <div class="small text-muted">
                                Harga: Rp <span x-text="item.price.toLocaleString()"></span>
                                | Qty: <input type="number" min="1" class="form-control d-inline-block w-auto ms-1" x-model.number="item.qty">
                                | Diskon: <input type="number" min="0" class="form-control d-inline-block w-auto ms-1" x-model.number="item.discount">
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" @click="removeFromCart(item.id)"><i class="fas fa-trash"></i> Hapus</button>
                    </div>
                </template>
                <div class="text-end mt-3">
                    <h5>Total: Rp <span x-text="totalPrice().toLocaleString()"></span></h5>
                    <button class="btn btn-success mt-2"><i class="fas fa-credit-card"></i> Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function posApp() {
            return {
                products: [],
                cart: [],

                init() {
                    fetch('/ajax/listProduct')
                        .then(res => res.json())
                        .then(data => {
                            this.products = data;
                        });
                },

                addToCart(product) {
                    const existing = this.cart.find(i => i.id === product.id);
                    if (existing) {
                        existing.qty += 1;
                    } else {
                        this.cart.push({ ...product, qty: 1, discount: 0 });
                    }
                },

                removeFromCart(id) {
                    this.cart = this.cart.filter(i => i.id !== id);
                },

                totalPrice() {
                    return this.cart.reduce((sum, item) => {
                        const itemTotal = (item.price * item.qty) - item.discount;
                        return sum + itemTotal;
                    }, 0);
                },
            }
        }
    </script>
</body>
</html>