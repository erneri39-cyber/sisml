<?php
session_start();

$pageTitle = "Registrar Nueva Compra";
ob_start(); // Inicia el buffer de salida para capturar el contenido de esta página.

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Registrar Nueva Compra</h1>
</div>

<div class="container">
    <div class="row g-4">
        <!-- Columna Izquierda: Formulario de adición -->
        <div class="col-lg-5">
            <h5>1. Añadir Producto a la Compra</h5>
            <div class="card">
                <div class="card-body">
                    <form id="add-item-form">
                        <input type="hidden" id="product-id">
                        <input type="hidden" id="product-name-hidden">
                        <input type="hidden" id="product-code-hidden">
                        
                        <div class="mb-3">
                            <label for="product-name-display" class="form-label">Producto</label>
                            <div class="input-group">
                                <input type="text" id="product-name-display" class="form-control" placeholder="Seleccione un producto..." readonly required>
                                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#searchProductModal" title="Buscar Producto">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <div class="form-text" id="product-info-text"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="batch-number" class="form-label">N° de Lote</label>
                                <input type="text" id="batch-number" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="expiration-date" class="form-label">Vencimiento</label>
                                <input type="date" id="expiration-date" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Cantidad</label>
                                <input type="number" id="quantity" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="purchase-price" class="form-label">Precio Compra ($)</label>
                                <input type="number" id="purchase-price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>

                        <fieldset class="border p-2 rounded mb-3">
                            <legend class="float-none w-auto px-2 fs-6">Precios de Venta</legend>
                            <div class="row">
                                <div class="col-6 col-md-4 mb-2">
                                    <label for="sale-price" class="form-label">P. Público</label>
                                    <input type="number" id="sale-price" class="form-control form-control-sm" step="0.01" min="0">
                                </div>
                                <div class="col-6 col-md-4 mb-2">
                                    <label for="sale-price-b" class="form-label">P. Caja</label>
                                    <input type="number" id="sale-price-b" class="form-control form-control-sm" step="0.01" min="0">
                                </div>
                                <div class="col-6 col-md-4 mb-2">
                                    <label for="sale-price-c" class="form-label">P. Mayoreo</label>
                                    <input type="number" id="sale-price-c" class="form-control form-control-sm" step="0.01" min="0">
                                </div>
                                <div class="col-6 col-md-4 mb-2">
                                    <label for="sale-price-2" class="form-label">P. Blister</label>
                                    <input type="number" id="sale-price-2" class="form-control form-control-sm" step="0.01" min="0">
                                </div>
                                <div class="col-6 col-md-4 mb-2">
                                    <label for="sale-price-3" class="form-label">P. Unidad</label>
                                    <input type="number" id="sale-price-3" class="form-control form-control-sm" step="0.01" min="0">
                                </div>
                            </div>
                        </fieldset>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Agregar a la Compra</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Carrito de Compra -->
        <div class="col-lg-7">
            <h5>2. Resumen de la Compra</h5>
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="purchase-date" class="form-label">Fecha</label>
                            <input type="date" id="purchase-date" class="form-control" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuario</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'N/A'); ?>" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="drogueria-select" class="form-label">Proveedor (Droguería)</label>
                        <select id="drogueria-select" class="form-select" required></select>
                        </div>
                        <div class="col-md-6">
                            <label for="document-number" class="form-label">N° de Factura</label>
                            <input type="text" id="document-number" class="form-control">
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Lote</th>
                                    <th>Cant.</th>
                                    <th>P. Compra</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="purchase-cart-tbody">
                                <!-- Items se añadirán aquí -->
                            </tbody>
                        </table>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-end align-items-center">
                        <h4 class="me-4 mb-0">Total: <span id="total-cost">$0.00</span></h4>
                    </div>
                </div>
                <div class="card-footer text-end">
                     <button id="process-purchase-btn" class="btn btn-primary btn-lg" disabled>
                        <i class="bi bi-check-circle-fill"></i> Finalizar y Guardar Compra
                    </button>
                </div>
            </div>
             <div id="status-message" class="mt-3"></div>
        </div>
    </div>
</div>

<!-- Modal de Búsqueda de Productos -->
<div class="modal fade" id="searchProductModal" tabindex="-1" aria-labelledby="searchProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchProductModalLabel"><i class="bi bi-box-seam"></i> Buscar y Seleccionar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="product-search-input" class="form-control" placeholder="Escriba código o nombre del producto para buscar..." autofocus>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-sm">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>Código</th>
                                <th>Nombre del Producto</th>
                                <th>Laboratorio</th>
                                <th>Medida</th>
                                <th>Categoría</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="product-search-results">
                            <tr>
                                <td colspan="6" class="text-center text-muted">Empiece a escribir para buscar productos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- ESTADO ---
    let purchaseItems = [];
    let allProducts = []; // NUEVO: Almacenará todos los productos precargados
    let searchTimeout;

    // --- SELECTORES DOM ---
    const elements = {
        addItemForm: document.getElementById('add-item-form'),
        cartTbody: document.getElementById('purchase-cart-tbody'),
        totalCostSpan: document.getElementById('total-cost'),
        processPurchaseBtn: document.getElementById('process-purchase-btn'),
        statusMessage: document.getElementById('status-message'),
        productId: document.getElementById('product-id'),
        productNameDisplay: document.getElementById('product-name-display'), // Campo de texto principal
        productInfoText: document.getElementById('product-info-text'),
        productNameHidden: document.getElementById('product-name-hidden'),
        productCodeHidden: document.getElementById('product-code-hidden'),
        batchNumber: document.getElementById('batch-number'),
        searchModal: new bootstrap.Modal(document.getElementById('searchProductModal')),
        searchInput: document.getElementById('product-search-input'),
        searchResultsBody: document.getElementById('product-search-results')
    };

    // --- LÓGICA DEL CARRITO ---
    function renderCart() {
        elements.cartTbody.innerHTML = '';
        let totalCost = 0;

        if (purchaseItems.length === 0) {
            elements.cartTbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">El carrito de compra está vacío.</td></tr>';
        } else {
            purchaseItems.forEach((item, index) => {
                const subtotal = item.quantity * item.purchase_price;
                totalCost += subtotal;
                const row = `
                    <tr>
                        <td>${item.product_name}<br><small class="text-muted">${item.product_code}</small></td>
                        <td>${item.batch_number}</td>
                        <td>${item.quantity}</td>
                        <td>$${item.purchase_price.toFixed(2)}</td>
                        <td>$${subtotal.toFixed(2)}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-index="${index}" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                    </tr>
                `;
                elements.cartTbody.innerHTML += row;
            });
        }

        elements.totalCostSpan.textContent = `$${totalCost.toFixed(2)}`;
        elements.processPurchaseBtn.disabled = purchaseItems.length === 0;
    }

    function addItemToCart(item) {
        purchaseItems.push(item);
        renderCart();
        resetItemForm();
    }

    function removeFromCart(index) {
        purchaseItems.splice(index, 1);
        renderCart();
    }

    function resetItemForm() {
        elements.addItemForm.reset();
        elements.productId.value = '';
        elements.productNameDisplay.value = '';
        elements.productInfoText.textContent = '';
    }

    // --- LÓGICA DE BÚSQUEDA (MODAL) ---
    async function preloadAllProducts() {
        elements.searchResultsBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Precargando productos...</td></tr>';
        try {
            const response = await fetch(`../controllers/get_products_for_purchase.php`);
            const result = await response.json();
            if (result.success && result.data.length > 0) {
                allProducts = result.data; // Guardar todos los productos
                renderSearchResults(allProducts); // Renderizar la lista completa
            } else {
                elements.searchResultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-warning">No se encontraron productos en el catálogo.</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching products:', error);
            elements.searchResultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al precargar los productos.</td></tr>';
        }
    }

    function filterAndRenderProducts(searchTerm) {
        const lowerCaseSearchTerm = searchTerm.toLowerCase();
        const filteredProducts = allProducts.filter(product => 
            product.product_name.toLowerCase().includes(lowerCaseSearchTerm) ||
            (product.product_code && product.product_code.toLowerCase().includes(lowerCaseSearchTerm))
        );
        renderSearchResults(filteredProducts);
    }
    
    function renderSearchResults(products) {
        const html = products.map(product => {
            // CORRECCIÓN: Codificar el JSON para que sea seguro en un atributo HTML
            const productJson = JSON.stringify(product);
            return `
                <tr class="product-row" data-product="${encodeURIComponent(productJson)}">
                    <td>${product.product_code || 'N/A'}</td>
                    <td>${product.product_name}</td>
                    <td>${product.laboratorio_name || 'N/A'}</td>
                    <td>${product.medida || 'N/A'}</td>
                    <td>${product.category_name || 'N/A'}</td>
                    <td><button type="button" class="btn btn-sm btn-success select-product-btn">Seleccionar</button></td>
                </tr>
            `;
        }).join('');
        elements.searchResultsBody.innerHTML = html;
    }

    function selectProduct(product) {
        elements.productId.value = product.id_product;
        elements.productNameDisplay.value = `[${product.product_code || 'S/C'}] ${product.product_name}`;
        elements.productNameHidden.value = product.product_name;
        elements.productCodeHidden.value = product.product_code;
        elements.productInfoText.textContent = `Lab: ${product.laboratorio_name || 'N/A'} | Medida: ${product.medida || 'N/A'}`;
        
        // Cerrar el modal
        elements.searchModal.hide();
        
        // Mostrar un mensaje de éxito temporal
        elements.statusMessage.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
            Producto "<strong>${product.product_name}</strong>" seleccionado. Por favor, complete los detalles del lote.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;

        // Opcional: hacer que el mensaje desaparezca después de unos segundos
        setTimeout(() => {
            const alert = elements.statusMessage.querySelector('.alert');
            if (alert) new bootstrap.Alert(alert).close();
        }, 5000);
        
        // Poner el foco en el siguiente campo para agilizar el proceso
        elements.batchNumber.focus();
    }

    // --- MANEJO DE EVENTOS ---

    // Evento para añadir un item desde el formulario
    elements.addItemForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const item = {
            id_product: elements.productId.value,
            product_name: elements.productNameHidden.value,
            product_code: elements.productCodeHidden.value,
            batch_number: elements.batchNumber.value,
            expiration_date: document.getElementById('expiration-date').value, // Se obtiene directo
            quantity: parseInt(document.getElementById('quantity').value),
            purchase_price: parseFloat(document.getElementById('purchase-price').value),
            sale_price: parseFloat(document.getElementById('sale-price').value) || 0,
            sale_price_b: parseFloat(document.getElementById('sale-price-b').value) || 0,
            sale_price_c: parseFloat(document.getElementById('sale-price-c').value) || 0,
            sale_price_2: parseFloat(document.getElementById('sale-price-2').value) || 0,
            sale_price_3: parseFloat(document.getElementById('sale-price-3').value) || 0,
        };

        if (!item.id_product || !item.quantity || item.quantity <= 0 || !item.purchase_price || item.purchase_price <= 0 || !item.batch_number || !item.expiration_date) {
            alert('Por favor, complete todos los campos requeridos del producto.');
            return;
        }
        addItemToCart(item);
    });

    // Evento para eliminar un item del carrito (usando delegación)
    elements.cartTbody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item-btn')) {
            const index = e.target.closest('.remove-item-btn').dataset.index;
            removeFromCart(index);
        }
    });

    // Evento para la búsqueda en el modal (con debounce)
    elements.searchInput.addEventListener('input', () => {
        const searchTerm = elements.searchInput.value;
        filterAndRenderProducts(searchTerm); // Filtrado local, es instantáneo
    });

    // Evento para seleccionar un producto del modal (usando delegación)
    elements.searchResultsBody.addEventListener('click', function(e) {
        // Se busca el botón más cercano al elemento clickeado
        const selectBtn = e.target.closest('.select-product-btn');
        // Si se encontró un botón (es decir, el clic fue en un botón "Seleccionar" o dentro de él)
        if (selectBtn) {
            const productRow = selectBtn.closest('.product-row');
            // CORRECCIÓN: Decodificar y parsear el JSON de forma segura
            const encodedProductData = productRow.dataset.product;
            const productData = JSON.parse(decodeURIComponent(encodedProductData));
            selectProduct(productData);
        }
    });

    // Evento para procesar la compra final
    elements.processPurchaseBtn.addEventListener('click', async function() {
        const drogueriaSelect = $('#drogueria-select');
        if (!drogueriaSelect.val()) {
            alert('Debe seleccionar un proveedor (Droguería).');
            return;
        }

        const totalCost = purchaseItems.reduce((sum, item) => sum + (item.quantity * item.purchase_price), 0);
        const purchaseData = {
            header: {
                id_supplier: drogueriaSelect.val(), // El modelo espera 'id_supplier'
                document_number: document.getElementById('document-number').value,
                total_cost: totalCost,
                purchase_date: document.getElementById('purchase-date').value // Añadir la fecha
            },
            // CORRECCIÓN: Asegurarse de enviar todos los campos necesarios al backend.
            // Se seleccionan explícitamente los campos que el modelo 'Purchase.php' necesita.
            details: purchaseItems.map(item => ({
                id_product: item.id_product,
                batch_number: item.batch_number,
                expiration_date: item.expiration_date,
                quantity: item.quantity,
                purchase_price: item.purchase_price,
                sale_price: item.sale_price,
                sale_price_b: item.sale_price_b,
                sale_price_c: item.sale_price_c,
                sale_price_2: item.sale_price_2,
                sale_price_3: item.sale_price_3
            }))
        };

        elements.statusMessage.innerHTML = '<div class="alert alert-info">Procesando compra...</div>';
        elements.processPurchaseBtn.disabled = true;

        try {
            const response = await fetch('../controllers/purchases_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(purchaseData)
            });
            const result = await response.json();

            if (result.success) {
                elements.statusMessage.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                setTimeout(() => window.location.href = 'purchases.php', 2000);
            } else {
                elements.statusMessage.innerHTML = `<div class="alert alert-danger">Error: ${result.message}</div>`;
                elements.processPurchaseBtn.disabled = false;
            }
        } catch (err) {
            elements.statusMessage.innerHTML = `<div class="alert alert-danger">Error de conexión al servidor.</div>`;
            elements.processPurchaseBtn.disabled = false;
        }
    });

    // --- INICIALIZACIÓN ---
    $('#drogueria-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Busca un proveedor...',
        minimumInputLength: 2,
        language: "es",
        ajax: {
            url: '../controllers/droguerias_controller.php',
            dataType: 'json',
            delay: 250,
            data: params => ({ term: params.term }),
            processResults: data => ({
                results: data.data.map(d => ({ id: d.id_drogueria, text: d.nombre }))
            }),
            cache: true
        }
    });

    preloadAllProducts(); // Precargar productos al iniciar la página
    renderCart(); // Renderizar carrito vacío
});
</script>

<?php
$content = ob_get_clean(); // Captura el contenido de la página en la variable $content.
include 'template.php'; // Incluye la plantilla principal, que ahora usará $content.
?>