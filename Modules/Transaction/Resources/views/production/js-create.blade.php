<script>
    // Alpine.js Production App
    function productionApp() {
        return {
            // Data properties
            ingredients: [],
            availableIngredients: [],
            availableRecipes: [],
            selectedIngredient: null,
            selectedRecipe: null,
            selectedRecipeId: '',
            ingredientQuantity: 0,
            recipeMultiplier: 1,
            baseRecipeIngredients: [], // Store original recipe quantities
            searchIngredient: '',
            ingredientSearchTerm: '',
            productionQuantity: 1,
            serviceCost: 0,
            sellPrice: 0,
            notes: '',
            status: 'temp',
            isLoadingIngredients: false,
            showAddIngredientModal: false,
            showSummaryDetails: false,

            // Edit modal properties
            editingIngredient: null,
            editingIndex: null,
            editQuantity: 0,

            // Computed properties
            get totalHpp() {
                const ingredientsCost = this.ingredients.reduce((total, ingredient) => {
                    return total + (ingredient.hpp * ingredient.quantity);
                }, 0);
                const serviceCost = this.serviceCost * this.productionQuantity;
                return ingredientsCost + serviceCost;
            },

            get hppPerUnit() {
                return this.productionQuantity > 0 ? this.totalHpp / this.productionQuantity : 0;
            },

            get totalServiceCost() {
                return this.serviceCost * this.productionQuantity;
            },

            get recipeTotal() {
                if (!this.selectedRecipe?.ingredients) return 0;
                return this.selectedRecipe.ingredients.reduce((total, ingredient) => {
                    return total + (ingredient.hpp * ingredient.quantity);
                }, 0);
            },

            // Computed property for filtered ingredients
            get filteredIngredients() {
                if (!this.ingredientSearchTerm || this.ingredientSearchTerm.trim() === '') {
                    return this.availableIngredients;
                }

                const searchTerm = this.ingredientSearchTerm.toLowerCase();
                const filtered = this.availableIngredients.filter(ingredient =>
                    ingredient.name.toLowerCase().includes(searchTerm) ||
                    (ingredient.code && ingredient.code.toLowerCase().includes(searchTerm))
                );

                return filtered;
            },

            // Initialize
            init() {
                this.loadAvailableIngredients();
                this.loadAvailableRecipes();
                this.loadExistingData();

                // Initialize quantity from HTML input or from edit mode
                const quantityInput = document.getElementById('quantity');
                @php
                if (isset($edit_quantity)) {
                    echo "this.productionQuantity = " . $edit_quantity . ";";
                    echo "if (quantityInput) quantityInput.value = " . $edit_quantity . ";";
                } else {
                    echo "if (quantityInput && quantityInput.value) { this.productionQuantity = parseFloat(quantityInput.value) || 1; }";
                }
                @endphp

                // Initialize sellPrice from edit mode
                @php
                if (isset($edit_sell_price)) {
                    echo "this.sellPrice = " . $edit_sell_price . ";";
                }
                @endphp

                // Initialize serviceCost from edit mode
                @php
                if (isset($edit_service_cost)) {
                    echo "this.serviceCost = " . $edit_service_cost . ";";
                }
                @endphp

                // Cost fields are initialized in loadExistingData()

                // Watch for production quantity changes
                this.$watch('productionQuantity', (value) => {
                    document.getElementById('quantity').value = value;
                    // Update ingredients quantities based on new production quantity
                    this.updateIngredientsFromRecipe();
                });

                // Watch for service cost changes
                this.$watch('serviceCost', (value) => {
                    // Parse formatted number to numeric value
                    const numericValue = parseFloat(value.toString().replace(/[^\d]/g, '')) || 0;
                    this.serviceCost = numericValue;
                });

                // Watch for sell price changes
                this.$watch('sellPrice', (value) => {
                    // Parse formatted number to numeric value
                    const numericValue = parseFloat(value.toString().replace(/[^\d]/g, '')) || 0;
                    this.sellPrice = numericValue;
                });
            },

            // Load existing data if editing
            loadExistingData() {
                @if (isset($data) && isset($production_detail))
                    // Load existing ingredients data
                    @php
                        $ingredientsData = $production_detail->map(function ($item) {
                            return [
                                'id' => $item->product_id,
                                'name' => $item->products->name,
                                'quantity' => $item->quantity,
                                'hpp' => $item->products->hpp,
                                'unit' => $item->products->unit->abbreviation ?? 'pcs',
                                'total' => $item->products->hpp * $item->quantity,
                            ];
                        });
                    @endphp
                    // Load existing ingredients - assume they are all from recipe initially
                    this.ingredients = @json($ingredientsData).map(ingredient => ({
                        ...ingredient,
                        is_manual: false // Existing ingredients are treated as recipe ingredients initially
                    }));

                    // Extract base recipe ingredients from existing data for editing
                    if (this.ingredients.length > 0 && this.productionQuantity > 0) {
                        this.baseRecipeIngredients = this.ingredients.map(ingredient => ({
                            id: ingredient.id,
                            name: ingredient.name,
                            base_quantity: ingredient.quantity / this.productionQuantity, // Calculate base quantity
                            hpp: ingredient.hpp,
                            unit: ingredient.unit,
                            from_recipe: true // Mark as recipe ingredient for editing
                        }));
                        console.log('Loaded base recipe ingredients from existing data:', this.baseRecipeIngredients);
                    } else {
                        this.baseRecipeIngredients = [];
                    }

                    // Note: sellPrice will be loaded from product price when product is selected
                @endif
            },

            // Update ingredients quantities based on production quantity
            updateIngredientsFromRecipe() {
                if (this.baseRecipeIngredients.length === 0) return;

                console.log('Updating recipe ingredients for production quantity:', this.productionQuantity);

                let updatedIngredients = [];
                const processedRecipeIds = new Set();

                // Preserve the order of existing ingredients and calculate new quantity
                this.ingredients.forEach(ing => {
                    const baseIng = this.baseRecipeIngredients.find(b => b.id === ing.id);
                    if (baseIng) {
                        const calculatedQuantity = baseIng.base_quantity * this.productionQuantity;
                        
                        updatedIngredients.push({
                            ...ing,
                            quantity: calculatedQuantity,
                            total: calculatedQuantity * ing.hpp
                        });
                        processedRecipeIds.add(ing.id);
                    } else {
                        // Fallback just in case an ingredient isn't in baseRecipeIngredients
                        updatedIngredients.push(ing);
                    }
                });

                // Add any ingredients from baseRecipeIngredients that aren't in the list yet
                this.baseRecipeIngredients.forEach(recipeIngredient => {
                    if (!processedRecipeIds.has(recipeIngredient.id)) {
                        const calculatedQuantity = recipeIngredient.base_quantity * this.productionQuantity;
                        updatedIngredients.push({
                            id: recipeIngredient.id,
                            name: recipeIngredient.name,
                            quantity: calculatedQuantity,
                            hpp: recipeIngredient.hpp,
                            unit: recipeIngredient.unit,
                            total: calculatedQuantity * recipeIngredient.hpp,
                            is_manual: false
                        });
                    }
                });

                this.ingredients = updatedIngredients;
                console.log('Updated ingredients:', this.ingredients);
            },

            // Load available ingredients
            async loadAvailableIngredients() {
                try {
                    const response = await fetch('{{ route('ajax.getProduct') }}');
                    const data = await response.json();
                    console.log('API Response:', data); // Temporary debug
                    this.availableIngredients = data.map(item => ({
                        id: item.id,
                        name: item.name,
                        code: item.code || item.sku || '',
                        stock: item.stock || 0,
                        hpp: item.hpp || 0,
                        unit: item.unit?.abbreviation || 'pcs',
                        category: item.category?.name || 'Umum'
                    }));
                    console.log('Mapped ingredients:', this.availableIngredients); // Temporary debug
                } catch (error) {
                    console.error('Error loading ingredients:', error);
                    this.showNotification('Error loading ingredients', 'error');
                }
            },

            // Load available recipes
            async loadAvailableRecipes() {
                try {
                    const response = await fetch('{{ route('products.get-receipt') }}');
                    const data = await response.json();
                    this.availableRecipes = data;
                } catch (error) {
                    console.error('Error loading recipes:', error);
                }
            },

            // Search ingredients
            searchIngredients() {
                // This will be handled by the computed filteredIngredients property
            },

            // Format number to Rupiah
            toRupiah(amount) {
                if (!amount) return 'Rp 0';
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
            },

            // Show notification
            showNotification(message, type = 'info') {
                // You can implement this with your preferred notification library
                // For now, just use console.log
                console.log(`${type.toUpperCase()}: ${message}`);

                // If you have a notification system like toastr, use it here
                if (typeof toastr !== 'undefined') {
                    toastr[type](message);
                }
            },

            // Open modals
            openAddIngredientModal() {
                this.showAddIngredientModal = true;
                this.loadAvailableIngredients();
                this.ingredientSearchTerm = '';
                erp.dialog.open('modal-add-ingredient');
            },

            // Open edit ingredient modal
            openEditIngredientModal(ingredient, index) {
                this.editingIngredient = {
                    ...ingredient
                };
                this.editingIndex = index;
                this.editQuantity = ingredient.quantity;

                erp.dialog.open('modal-edit-ingredient');

                // Focus on input after modal is shown
                setTimeout(() => {
                    const input = document.getElementById('edit-quantity-input');
                    if (input) {
                        input.focus();
                        input.select();
                    }
                }, 300);
            },

            // Update ingredient quantity from edit modal
            updateIngredientQuantity() {
                if (!this.editQuantity || this.editQuantity <= 0) {
                    this.showNotification('Jumlah harus lebih dari 0', 'error');
                    return;
                }

                if (this.editingIndex !== null && this.editingIndex >= 0) {
                    const newQty = parseFloat(this.editQuantity);
                    const ingId = this.ingredients[this.editingIndex].id;
                    
                    // Update the ingredient in the array
                    this.ingredients[this.editingIndex].quantity = newQty;
                    this.ingredients[this.editingIndex].total = this.ingredients[this.editingIndex].hpp * newQty;
                    
                    // Update baseRecipeIngredients to scale with new production quantity later
                    let baseIng = this.baseRecipeIngredients.find(b => b.id === ingId);
                    if (baseIng) {
                        baseIng.base_quantity = newQty / this.productionQuantity;
                    } else {
                        this.baseRecipeIngredients.push({
                            id: ingId,
                            name: this.ingredients[this.editingIndex].name,
                            base_quantity: newQty / this.productionQuantity,
                            hpp: this.ingredients[this.editingIndex].hpp,
                            unit: this.ingredients[this.editingIndex].unit,
                            from_recipe: false
                        });
                    }

                    // Close modal and reset
                    erp.dialog.close('modal-edit-ingredient');
                    this.editingIngredient = null;
                    this.editingIndex = null;
                    this.editQuantity = 0;

                    this.showNotification('Jumlah bahan berhasil diperbarui', 'success');
                }
            },

            // Remove ingredient from edit modal
            removeIngredientFromEdit() {
                if (this.editingIndex !== null && this.editingIndex >= 0) {
                    const ingredientName = this.ingredients[this.editingIndex].name;
                    const ingredientId = this.ingredients[this.editingIndex].id;

                    // Remove the ingredient
                    this.ingredients.splice(this.editingIndex, 1);
                    this.baseRecipeIngredients = this.baseRecipeIngredients.filter(b => b.id !== ingredientId);

                    // Close modal and reset
                    erp.dialog.close('modal-edit-ingredient');
                    this.editingIngredient = null;
                    this.editingIndex = null;
                    this.editQuantity = 0;

                    this.showNotification(`${ingredientName} berhasil dihapus`, 'info');
                }
            },

            // Format number to Rupiah
            toRupiah(amount) {
                if (!amount) return 'Rp 0';
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
            },

            // Show notification
            showNotification(message, type = 'info') {
                // You can implement this with your preferred notification library
                // For now, just use console.log
                console.log(`${type.toUpperCase()}: ${message}`);

                // If you have a notification system like toastr, use it here
                if (typeof toastr !== 'undefined') {
                    toastr[type](message);
                }
            },

            openParcelModal() {
                erp.dialog.open('modal-load-recipe');
            },

            loadFromRecipe() {
                erp.dialog.open('modal-load-recipe');
            },

            // Select ingredient
            selectIngredient(ingredient) {
                this.selectedIngredient = ingredient;
                this.ingredientQuantity = 0;

                // Close ingredient modal and open quantity modal
                erp.dialog.close('modal-add-ingredient');
                erp.dialog.open('modal-input-quantity');
            },

            // Add ingredient to cart
            addIngredientToCart(ingredient = null) {
                // If ingredient is passed directly (from new modal), use it
                if (ingredient) {
                    // Check if ingredient already exists
                    const existingIndex = this.ingredients.findIndex(item => item.id === ingredient.id);

                    if (existingIndex >= 0) {
                        // Update existing ingredient quantity by 1
                        this.ingredients[existingIndex].quantity += 1;
                        this.ingredients[existingIndex].total = this.ingredients[existingIndex].hpp * this.ingredients[
                            existingIndex].quantity;
                        
                        let baseIng = this.baseRecipeIngredients.find(b => b.id === ingredient.id);
                        if (baseIng) {
                            baseIng.base_quantity = this.ingredients[existingIndex].quantity / this.productionQuantity;
                        } else {
                            this.baseRecipeIngredients.push({
                                id: ingredient.id,
                                name: ingredient.name,
                                base_quantity: this.ingredients[existingIndex].quantity / this.productionQuantity,
                                hpp: ingredient.hpp,
                                unit: ingredient.unit,
                                from_recipe: false
                            });
                        }
                    } else {
                        // Add new ingredient with quantity 1 (manual ingredient)
                        this.ingredients.push({
                            id: ingredient.id,
                            name: ingredient.name,
                            quantity: 1,
                            hpp: ingredient.hpp,
                            unit: ingredient.unit,
                            total: ingredient.hpp * 1,
                            is_manual: false
                        });
                        
                        this.baseRecipeIngredients.push({
                            id: ingredient.id,
                            name: ingredient.name,
                            base_quantity: 1 / this.productionQuantity,
                            hpp: ingredient.hpp,
                            unit: ingredient.unit,
                            from_recipe: false
                        });
                    }

                    this.showNotification('Ingredient added successfully', 'success');
                    return;
                }

                // Original method for quantity modal
                if (!this.selectedIngredient || !this.ingredientQuantity || this.ingredientQuantity <= 0) {
                    this.showNotification('Please enter valid quantity', 'error');
                    return;
                }

                // Check if ingredient already exists
                const existingIndex = this.ingredients.findIndex(item => item.id === this.selectedIngredient.id);

                if (existingIndex >= 0) {
                    const newQty = parseFloat(this.ingredientQuantity);
                    
                    // Update existing ingredient
                    this.ingredients[existingIndex].quantity = newQty;
                    this.ingredients[existingIndex].total = this.ingredients[existingIndex].hpp * newQty;
                    
                    let baseIng = this.baseRecipeIngredients.find(b => b.id === this.selectedIngredient.id);
                    if (baseIng) {
                        baseIng.base_quantity = newQty / this.productionQuantity;
                    } else {
                        this.baseRecipeIngredients.push({
                            id: this.selectedIngredient.id,
                            name: this.selectedIngredient.name,
                            base_quantity: newQty / this.productionQuantity,
                            hpp: this.selectedIngredient.hpp,
                            unit: this.selectedIngredient.unit,
                            from_recipe: false
                        });
                    }
                } else {
                    const newQty = parseFloat(this.ingredientQuantity);
                    // Add new ingredient (manual ingredient)
                    this.ingredients.push({
                        id: this.selectedIngredient.id,
                        name: this.selectedIngredient.name,
                        quantity: newQty,
                        hpp: this.selectedIngredient.hpp,
                        unit: this.selectedIngredient.unit,
                        total: this.selectedIngredient.hpp * newQty,
                        is_manual: false
                    });
                    
                    this.baseRecipeIngredients.push({
                        id: this.selectedIngredient.id,
                        name: this.selectedIngredient.name,
                        base_quantity: newQty / this.productionQuantity,
                        hpp: this.selectedIngredient.hpp,
                        unit: this.selectedIngredient.unit,
                        from_recipe: false
                    });
                }

                // Close modal and reset
                erp.dialog.close('modal-input-quantity');
                this.selectedIngredient = null;
                this.ingredientQuantity = 0;

                this.showNotification('Ingredient added successfully', 'success');
            },

            // Update ingredient quantity
            updateQuantity(index, newQuantity) {
                if (newQuantity < 0) return;

                this.ingredients[index].quantity = parseFloat(newQuantity) || 0;
                this.ingredients[index].total = this.ingredients[index].hpp * this.ingredients[index].quantity;
            },

            // Remove ingredient
            removeIngredient(index) {
                if (index >= 0 && index < this.ingredients.length) {
                    const ingredientId = this.ingredients[index].id;
                    this.ingredients.splice(index, 1);
                    this.baseRecipeIngredients = this.baseRecipeIngredients.filter(b => b.id !== ingredientId);
                    this.showNotification('Ingredient removed', 'info');
                }
            },

            // Clear all ingredients
            clearIngredients() {
                this.ingredients = [];
                this.showNotification('All ingredients cleared', 'info');
            },

            // Set loading state
            setLoadingIngredients(loading) {
                this.isLoadingIngredients = loading;
            },

            // Load recipe details
            async loadRecipeDetails() {
                if (!this.selectedRecipeId) {
                    this.selectedRecipe = null;
                    return;
                }

                try {
                    const response = await fetch(`{{ url('production/get-receipt') }}/${this.selectedRecipeId}`);
                    const data = await response.json();
                    this.selectedRecipe = data;
                } catch (error) {
                    console.error('Error loading recipe details:', error);
                    this.showNotification('Error loading recipe details', 'error');
                }
            },

            // Load recipe to production
            loadRecipeToProduction() {
                if (!this.selectedRecipe || !this.recipeMultiplier || this.recipeMultiplier <= 0) {
                    this.showNotification('Please select recipe and enter valid multiplier', 'error');
                    return;
                }

                // Clear existing ingredients
                this.ingredients = [];

                // Add recipe ingredients with multiplier
                this.selectedRecipe.ingredients.forEach(ingredient => {
                    this.ingredients.push({
                        id: ingredient.id,
                        name: ingredient.name,
                        quantity: ingredient.quantity * this.recipeMultiplier,
                        hpp: ingredient.hpp,
                        unit: ingredient.unit,
                        total: ingredient.hpp * (ingredient.quantity * this.recipeMultiplier)
                    });
                });

                // Close modal and reset
                erp.dialog.close('modal-load-recipe');
                this.selectedRecipe = null;
                this.selectedRecipeId = '';
                this.recipeMultiplier = 1;

                this.showNotification('Recipe loaded successfully', 'success');
            },

            // Refresh product list
            refreshProduct() {
                this.loadAvailableIngredients();
                this.showNotification('Product list refreshed', 'info');
            },

            // Format currency
            formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(amount || 0);
            },

            // Get status text
            getStatusText(status) {
                const statusMap = {
                    'temp': 'Draft Sementara',
                    'draft': 'Siap Produksi',
                    'posting': 'Selesai Produksi'
                };
                return statusMap[status] || 'Unknown';
            },

            // Show notification
            showNotification(message, type = 'info') {
                // You can implement this with your preferred notification library
                // For now, just use console.log
                console.log(`${type.toUpperCase()}: ${message}`);

                // If you have a notification system like toastr, use it here
                if (typeof toastr !== 'undefined') {
                    toastr[type](message);
                }
            },

            // Save production function (similar to POS saveTransaction)
            async saveProduction(submitType, callback) {
                try {
                    // Validate required fields
                    if (!this.productionQuantity || this.productionQuantity <= 0) {
                        this.showNotification('Jumlah produksi harus lebih dari 0', 'error');
                        if (callback) callback();
                        return;
                    }

                    if (this.ingredients.length === 0) {
                        this.showNotification('Minimal harus ada 1 bahan baku', 'error');
                        if (callback) callback();
                        return;
                    }

                    const productSelect = document.getElementById('product_id');
                    if (!productSelect.value) {
                        this.showNotification('Pilih produk terlebih dahulu', 'error');
                        if (callback) callback();
                        return;
                    }

                    // Prepare form data
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content'));
                    formData.append('production_number', document.querySelector('input[name="production_number"]')
                        .value);
                    formData.append('production_date', document.querySelector('input[name="production_date"]')
                        .value);
                    formData.append('sell_price', this.sellPrice || 0);
                    formData.append('service_cost', this.serviceCost || 0);
                    formData.append('product_id', productSelect.value);
                    formData.append('quantity', this.productionQuantity);
                    formData.append('submit_type', submitType);
                    formData.append('notes', this.notes || '');

                    // Add ID if in edit mode
                    const productionIdInput = document.querySelector('input[name="id"]');
                    if (productionIdInput && productionIdInput.value) {
                        formData.append('id', productionIdInput.value);
                    }

                    const branchSelect = document.getElementById('branch_id');
                    if (branchSelect.value) {
                        formData.append('branch_id', branchSelect.value);
                    }

                    // Add ingredients
                    this.ingredients.forEach((ingredient, index) => {
                        formData.append(`ingredients[${index}][id]`, ingredient.id);
                        formData.append(`ingredients[${index}][quantity]`, ingredient.quantity);
                        formData.append(`ingredients[${index}][hpp]`, ingredient.hpp);
                    });

                    // Debug: Log what we're sending
                    console.log('Sending production data:', {
                        production_number: formData.get('production_number'),
                        product_id: formData.get('product_id'),
                        quantity: formData.get('quantity'),
                        service_cost: formData.get('service_cost'),
                        submit_type: formData.get('submit_type'),
                        ingredients_count: this.ingredients.length,
                        csrf_token: formData.get('_token') ? 'present' : 'missing'
                    });

                    // Submit form
                    const response = await fetch('{{ route('production.store') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    console.log('Response status:', response.status);
                    console.log('Response headers:', Object.fromEntries(response.headers.entries()));

                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        console.error('Response is not JSON, content-type:', contentType);
                        const text = await response.text();
                        console.error('Response text:', text.substring(0, 500));
                        throw new Error('Server returned HTML instead of JSON. Check server logs for errors.');
                    }

                    const result = await response.json();
                    console.log('Response data:', result);

                    if (response.ok && result.success) {
                        this.showNotification(result.message || 'Produksi berhasil disimpan', 'success');

                        // Always redirect to production index after success (regardless of submit type)
                        setTimeout(() => {
                            window.location.href = '{{ route('production.index') }}';
                        }, 1500);
                    } else {
                        // Handle error responses - tampilkan Swal.fire seperti di PosController
                        Swal.fire({
                            icon: 'error',
                            title: 'Produksi Gagal',
                            text: result.message || 'Gagal menyimpan produksi',
                            confirmButtonText: 'OK'
                        });
                        if (result.errors) {
                            console.error('Validation errors:', result.errors);
                        }
                        if (callback) callback();
                    }

                } catch (error) {
                    console.error('Error saving production:', error);
                    this.showNotification('Terjadi kesalahan: ' + error.message, 'error');
                    if (callback) callback();
                }
            },
        }
    }

    // Set submit type function
    function setSubmitType(type) {
        document.getElementById('submit_type').value = type;
    }

    // Document ready
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Alpine.js to be ready
        document.addEventListener('alpine:init', function() {
            console.log('Alpine.js initialized');
        });

        // Initialize Select2 for product selection with a small delay to ensure Alpine is ready
        setTimeout(function() {
            // Pemilihan produk datang dari ErpPicker (event select2:select / select2:clear dikirim picker).
            $('#product_id').on('select2:select', function(e) {
                // Auto load ingredients when product is selected
                const selectedProductId = e.params.data.id;
                const unitLabel = document.getElementById('production-unit');
                if (unitLabel && e.params.data.unit !== undefined) unitLabel.textContent = String(e.params.data.unit).toUpperCase();
                if (selectedProductId) {
                    // Add a small delay to ensure Alpine is ready
                    setTimeout(() => {
                        loadRecipeIngredients(selectedProductId);
                    }, 100);
                }
            }).on('select2:clear', function(e) {
                // Clear ingredients when product is cleared
                const unitLabel = document.getElementById('production-unit');
                if (unitLabel) unitLabel.textContent = '';
                try {
                    const alpineElement = document.querySelector('[x-data*="productionApp"]') ||
                        document.querySelector('[x-data]');
                    let productionApp = null;

                    if (alpineElement && alpineElement._x_dataStack && alpineElement
                        ._x_dataStack[0]) {
                        productionApp = alpineElement._x_dataStack[0];
                    } else if (alpineElement && alpineElement.__x && alpineElement.__x.$data) {
                        productionApp = alpineElement.__x.$data;
                    }

                    if (productionApp) {
                        productionApp.ingredients = [];
                        productionApp.productionQuantity = 1;
                        const quantityInput = document.getElementById('quantity');
                        if (quantityInput) {
                            quantityInput.value = 1;
                        }
                    } else {
                        // Fallback: clear table directly
                        const tbody = document.getElementById(
                            'kt_ecommerce_edit_order_selected_products_body');
                        if (tbody) {
                            tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center text-gray-400 py-3">
                                    <i class="ph ph-info fs-2x text-gray-300 mb-1.5">
                                        
                                        
                                        
                                    </i>
                                    <div>Belum ada bahan baku yang dipilih</div>
                                    <small class="text-gray-400">Pilih produk untuk memuat resep atau klik tombol + untuk menambah bahan baku</small>
                                </td>
                            </tr>
                        `;
                        }
                        const quantityInput = document.getElementById('quantity');
                        if (quantityInput) {
                            quantityInput.value = 1;
                        }
                    }
                } catch (error) {
                    console.error('Error clearing ingredients:', error);
                 }
             });

              // Load product and sell price for editing if in edit mode
              @if(isset($edit_product_id) && isset($edit_product_name))
                  // Isi produk terpilih pada select (ErpPicker membaca label dari option)
                  var editProductOption = new Option('{{ $edit_product_name }}', '{{ $edit_product_id }}', true, true);
                  $('#product_id').append(editProductOption).trigger('change');

                  // Set Alpine.js sellPrice
                  const alpineElement = document.querySelector('[x-data*="productionApp"]') || document.querySelector('[x-data]');
                  if (alpineElement && alpineElement._x_dataStack && alpineElement._x_dataStack[0]) {
                      alpineElement._x_dataStack[0].sellPrice = {{ $edit_sell_price ?? 0 }};
                  }

                  // Set sell price input value
                  const sellPriceInput = document.getElementById('sell_price');
                  if (sellPriceInput) {
                      sellPriceInput.value = '{{ number_format($edit_sell_price ?? 0, 0, ',', '.') }}';
                  }
              @endif
         }, 500); // Wait 500ms for Alpine to initialize

        // Pemilih pencarian (ErpPicker); select, id, dan handler select2:* tetap menjadi kontrak halaman.
        ErpPicker.attach('#product_id', {
            title: 'Pilih Produk Olahan',
            subtitle: 'Produk yang akan diproduksi',
            placeholder: 'Cari nama produk...',
            placeholderTrigger: 'Pilih produk olahan...',
            emptyText: 'Produk olahan tidak ditemukan',
            clearable: true,
            url: '{{ route('products.get-receipt') }}',
            actionLabels: ['Pilih', 'Ganti'],
            map: item => ({ id: item.product_id, text: item.products?.name || 'Unknown Product', data: { unit: item.products?.unit?.abbreviation || '' } })
        });
        ErpPicker.attach('#branch_id', {
            title: 'Pilih Cabang / Dapur',
            placeholder: 'Cari cabang...',
            placeholderTrigger: 'Pilih cabang...',
            emptyText: 'Cabang tidak ditemukan',
            clearable: true,
            url: '{{ route('ajax.getBranch') }}',
            map: item => ({ id: item.id, text: item.name })
        });

        // Set branch_id for editing
        @if(isset($data) && $data->branch_id && isset($data->branch))
            setTimeout(() => {
                const branchOption = new Option('{{ $data->branch->name }}', '{{ $data->branch_id }}', true, true);
                $('#branch_id').append(branchOption).trigger('change');
            }, 600);
        @endif

        // Form submission handler
        $('#kt_ecommerce_edit_order_form').on('submit', function(e) {
            const submitButton = $(this).find('[type="submit"]:focus');
            submitButton.find('.indicator-label').hide();
            submitButton.find('.indicator-progress').show();
            submitButton.prop('disabled', true);
        });
    });

    // Function to load recipe ingredients
    async function loadRecipeIngredients(productId) {
        console.log('Loading recipe ingredients for product ID:', productId);

        try {
            // Get Alpine.js component instance using a more reliable method
            let productionApp = null;

            // Try multiple ways to get the Alpine component
            const alpineElement = document.querySelector('[x-data*="productionApp"]') || document.querySelector(
                '[x-data]');

            if (alpineElement && alpineElement._x_dataStack && alpineElement._x_dataStack[0]) {
                productionApp = alpineElement._x_dataStack[0];
            } else if (alpineElement && alpineElement.__x && alpineElement.__x.$data) {
                productionApp = alpineElement.__x.$data;
            } else if (window.Alpine && window.Alpine.store) {
                // Fallback: try to access through Alpine store if available
                console.log('Using fallback method to access Alpine data');
            }

            if (!productionApp) {
                console.error('Alpine.js component not found, using fallback approach');
                // Use a fallback approach without Alpine
                await loadRecipeIngredientsWithoutAlpine(productId);
                return;
            }

            // Set loading state
            if (typeof productionApp.setLoadingIngredients === 'function') {
                productionApp.setLoadingIngredients(true);
            } else {
                productionApp.isLoadingIngredients = true;
            }

            showLoadingNotification('Memuat bahan dari resep...');

            // First, find the receipt ID for this product
            console.log('Fetching receipts...');
            const receiptResponse = await fetch(`{{ route('products.get-receipt') }}`);

            if (!receiptResponse.ok) {
                throw new Error(`Failed to fetch receipts: ${receiptResponse.status}`);
            }

            const receipts = await receiptResponse.json();
            console.log('Available receipts:', receipts);

            // Find receipt for this product
            const receipt = receipts.find(r => r.product_id == productId);
            console.log('Found receipt for product:', receipt);

            if (!receipt) {
                showInfoNotification('Produk ini tidak memiliki resep');
                return;
            }

            // Now fetch the recipe data
            console.log('Fetching recipe data for receipt ID:', receipt.id);
            const response = await fetch(`{{ url('production/get-recipe-data') }}/${receipt.id}`);

            if (!response.ok) {
                throw new Error(`Failed to fetch recipe data: ${response.status}`);
            }

            const recipeData = await response.json();
            console.log('Recipe data received:', recipeData);

            if (recipeData.success && recipeData.ingredients && recipeData.ingredients.length > 0) {
                // Clear existing ingredients
                productionApp.ingredients = [];

                // Store base recipe ingredients (only for recipe-based ingredients)
                productionApp.baseRecipeIngredients = recipeData.ingredients.map(ingredient => ({
                    id: ingredient.product_id || ingredient.id,
                    name: ingredient.name,
                    base_quantity: parseFloat(ingredient.quantity || 0),
                    hpp: parseFloat(ingredient.hpp || 0),
                    unit: ingredient.unit || 'pcs',
                    from_recipe: true // Mark as recipe ingredient
                }));

                // Calculate and add recipe ingredients
                const manualIngredients = productionApp.ingredients.filter(ing => ing.is_manual);
                productionApp.ingredients = manualIngredients; // Start with manual ingredients only

                // Add recipe ingredients with calculated quantities
                productionApp.baseRecipeIngredients.forEach(recipeIngredient => {
                    const calculatedQuantity = recipeIngredient.base_quantity * productionApp.productionQuantity;
                    productionApp.ingredients.push({
                        id: recipeIngredient.id,
                        name: recipeIngredient.name,
                        quantity: calculatedQuantity,
                        hpp: recipeIngredient.hpp,
                        unit: recipeIngredient.unit,
                        total: calculatedQuantity * recipeIngredient.hpp,
                        is_manual: false // Mark as recipe ingredient
                    });
                });

                console.log('Loaded recipe ingredients:', productionApp.ingredients);

                 // Update production quantity if available
                 if (recipeData.yield_quantity) {
                     productionApp.productionQuantity = parseFloat(recipeData.yield_quantity);
                     const quantityInput = document.getElementById('quantity');
                     if (quantityInput) {
                         quantityInput.value = recipeData.yield_quantity;
                     }
                 }

                 // Set sell price from product price
                 if (receipt && receipt.products && receipt.products.price) {
                     productionApp.sellPrice = parseFloat(receipt.products.price);
                     const sellPriceInput = document.getElementById('sell_price');
                     if (sellPriceInput) {
                         sellPriceInput.value = new Intl.NumberFormat('id-ID').format(receipt.products.price);
                     }
                 }

                 showSuccessNotification(`Berhasil memuat ${recipeData.ingredients.length} bahan dari resep`);
            } else {
                const message = recipeData.message || 'Produk ini tidak memiliki resep atau resep kosong';
                console.log('No ingredients found:', message);
                showInfoNotification(message);
            }

        } catch (error) {
            console.error('Error loading recipe ingredients:', error);
            showErrorNotification('Gagal memuat bahan dari resep: ' + error.message);
        } finally {
            // Always clear loading state
            try {
                const alpineElement = document.querySelector('[x-data*="productionApp"]') || document.querySelector(
                    '[x-data]');
                let productionApp = null;

                if (alpineElement && alpineElement._x_dataStack && alpineElement._x_dataStack[0]) {
                    productionApp = alpineElement._x_dataStack[0];
                } else if (alpineElement && alpineElement.__x && alpineElement.__x.$data) {
                    productionApp = alpineElement.__x.$data;
                }

                if (productionApp) {
                    if (typeof productionApp.setLoadingIngredients === 'function') {
                        productionApp.setLoadingIngredients(false);
                    } else {
                        productionApp.isLoadingIngredients = false;
                    }
                }
            } catch (e) {
                console.log('Could not clear loading state:', e);
            }
        }
    }

    // Fallback function that works without Alpine.js
    async function loadRecipeIngredientsWithoutAlpine(productId) {
        try {
            showLoadingNotification('Memuat bahan dari resep...');

            // Fetch receipts
            const receiptResponse = await fetch(`{{ route('products.get-receipt') }}`);
            const receipts = await receiptResponse.json();

            // Find receipt for this product
            const receipt = receipts.find(r => r.product_id == productId);

            if (!receipt) {
                showInfoNotification('Produk ini tidak memiliki resep');
                return;
            }

            // Fetch recipe data
            const response = await fetch(`{{ url('production/get-recipe-data') }}/${receipt.id}`);
            const recipeData = await response.json();

            if (recipeData.success && recipeData.ingredients && recipeData.ingredients.length > 0) {
                // Update the ingredients table directly
                updateIngredientsTableDirectly(recipeData.ingredients);

                // Update production quantity
                if (recipeData.yield_quantity) {
                    const quantityInput = document.getElementById('quantity');
                    if (quantityInput) {
                        quantityInput.value = recipeData.yield_quantity;
                    }
                }

                // Set sell price from product price
                if (receipt && receipt.products && receipt.products.price) {
                    const sellPriceInput = document.getElementById('sell_price');
                    if (sellPriceInput) {
                        sellPriceInput.value = new Intl.NumberFormat('id-ID').format(receipt.products.price);
                    }
                }

                showSuccessNotification(`Berhasil memuat ${recipeData.ingredients.length} bahan dari resep`);
            } else {
                showInfoNotification('Produk ini tidak memiliki resep atau resep kosong');
            }

        } catch (error) {
            console.error('Error in fallback method:', error);
            showErrorNotification('Gagal memuat bahan dari resep: ' + error.message);
        }
    }

    // Function to update ingredients table directly (fallback)
    function updateIngredientsTableDirectly(ingredients) {
        const tbody = document.getElementById('kt_ecommerce_edit_order_selected_products_body');
        if (!tbody) return;

        // Clear existing rows
        tbody.innerHTML = '';

        if (ingredients.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-gray-400 py-3">
                    <i class="ph ph-info fs-2x text-gray-300 mb-1.5">
                        
                        
                        
                    </i>
                    <div>Belum ada bahan baku yang dipilih</div>
                </td>
            </tr>
        `;
            return;
        }

        // Add ingredient rows
        ingredients.forEach((ingredient, index) => {
            console.log('tester');
            const total = parseFloat(ingredient.quantity || 0) * parseFloat(ingredient.hpp || 0);
            const row = `
            <tr class="border-b border-gray-200">
                <td>
                    <div class="flex items-center">
                        <div class="symbol symbol-50px me-2.5">
                            <div class="symbol-label bg-teal/10">
                                <i class="ph ph-package fs-2x text-teal">
                                    
                                    
                                    
                                </i>
                            </div>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-800">${ingredient.name}</div>
                            <div class="text-gray-400 text-xs">${ingredient.unit}</div>
                        </div>
                    </div>
                </td>
                <td class="text-center">
                    <div class="input-group input-group-sm" style="width: 120px;">
                        <button class="erp-action" type="button">
                            <i class="ph ph-minus"></i>
                        </button>
                        <input type="number" class="erp-input text-center" style="width:100px"
                            value="${ingredient.quantity}" step="0.01" min="0">
                        <button class="erp-action" type="button">
                            <i class="ph ph-plus"></i>
                        </button>
                    </div>
                </td>
                <td class="text-center">
                    <span class="font-semibold text-gray-800">Rp ${new Intl.NumberFormat('id-ID').format(ingredient.hpp)}</span>
                </td>
                <td class="text-center">
                    <span class="font-semibold text-teal">Rp ${new Intl.NumberFormat('id-ID').format(total)}</span>
                </td>
                <td class="text-end">
                    <button type="button" class="erp-action erp-action--danger" title="Hapus">
                        <i class="ph ph-trash text-[15px]">
                            
                            
                            
                            
                            
                        </i>
                    </button>
                </td>
            </tr>
            <input type="hidden" name="ingredients[${index}][id]" value="${ingredient.id}">
            <input type="hidden" name="ingredients[${index}][quantity]" value="${ingredient.quantity}">
            <input type="hidden" name="ingredients[${index}][hpp]" value="${ingredient.hpp}">
        `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    // Notification functions
    function showLoadingNotification(message) {
        // You can integrate with your notification system here
        console.log('LOADING: ' + message);

        // Simple implementation - replace with your notification system
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Memuat...',
                text: message,
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    }

    function showSuccessNotification(message) {
        console.log('SUCCESS: ' + message);

        if (typeof Swal !== 'undefined') {
            Swal.close(); // Close loading
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            alert('Success: ' + message);
        }
    }

    function showInfoNotification(message) {
        console.log('INFO: ' + message);

        if (typeof Swal !== 'undefined') {
            Swal.close(); // Close loading
            Swal.fire({
                icon: 'info',
                title: 'Informasi',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert('Info: ' + message);
        }
    }

    function showErrorNotification(message) {
        console.log('ERROR: ' + message);

        if (typeof Swal !== 'undefined') {
            Swal.close(); // Close loading
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message
            });
        } else {
            alert('Error: ' + message);
        }
    }

    // Format number input handling
    document.addEventListener('DOMContentLoaded', function() {
        // Handle format-number class inputs with Alpine.js compatibility
        document.querySelectorAll('.format-number').forEach(function(input) {
            input.addEventListener('blur', function(e) {
                // Re-apply formatting on blur
                let value = e.target.value.replace(/[^\d]/g, '');
                if (value) {
                    value = new Intl.NumberFormat('id-ID').format(value);
                    e.target.value = value;
                }
            });
        });
    });
</script>
