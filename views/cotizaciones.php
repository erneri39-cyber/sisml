<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Crear Cotización";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Crear Cotización - Sucursal <?php echo htmlspecialchars($_SESSION['id_branch']); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="lista_cotizaciones.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-list-ul"></i>
            Ver Listado de Cotizaciones
        </a>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <!-- Columna Izquierda: Catálogo de Productos -->
        <div class="col-lg-7">
            <h5>1. Añadir Productos a la Cotización</h5>
            <div class="card text-center">
                <div class="card-body">
                    <p class="card-text">Haz clic en el botón para buscar y añadir productos del catálogo a la cotización.</p>
                    <button class="btn btn-primary btn-lg" type="button" data-bs-toggle="modal" data-bs-target="#searchProductModal">
                        <i class="bi bi-search"></i> Buscar en Catálogo
                    </button>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Carrito de Cotización -->
        <div class="col-lg-5">
            <h5>2. Detalle de Cotización</h5>
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha</label>
                            <input type="text" class="form-control" value="<?php echo date('d/m/Y'); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuario</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'N/A'); ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="client-select" class="form-label">Cliente</label>
                        <select id="client-select" class="form-select" required>
                            <!-- Clientes se cargarán dinámicamente -->
                        </select>
                    </div>
                    <hr>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cant.</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cart-tbody">
                                <!-- Items del carrito -->
                            </tbody>
                        </table>
                    </div>
                     <hr>
                    <div class="d-flex justify-content-end align-items-center">
                        <h4 class="me-4 mb-0">Total: <span id="cart-total">$0.00</span></h4>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button id="process-quote-btn" class="btn btn-primary btn-lg" disabled>
                        <i class="bi bi-file-earmark-text-fill"></i> Generar Cotización
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
                <h5 class="modal-title" id="searchProductModalLabel"><i class="bi bi-box-seam"></i> Catálogo de Productos Disponibles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="product-search" class="form-control" placeholder="Buscar producto por nombre o código...">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th style="width: 150px;">Cantidad</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="available-products-tbody">
                            <!-- Los productos se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- ESTADO ---
    let allProducts = [];
    let cart = [];
    let originQuoteId = null;

    // --- SELECTORES DOM ---
    const elements = {
        productsTbody: document.getElementById('available-products-tbody'),
        cartTbody: document.getElementById('cart-tbody'),
        cartTotalSpan: document.getElementById('cart-total'),
        processQuoteBtn: document.getElementById('process-quote-btn'),
        statusMessage: document.getElementById('status-message'),
        searchInput: document.getElementById('product-search'),
        clientSelect: document.getElementById('client-select')
    };

    // --- LÓGICA DE DATOS ---
    async function loadInitialData() {
        try {
            const [productsRes, clientsRes] = await Promise.all([
                fetch('../controllers/get_products_for_tpv.php'),
                fetch('../controllers/clients_controller.php')
            ]);
            const productsResult = await productsRes.json();
            const clientsResult = await clientsRes.json();

            if (productsResult.success) {
                allProducts = productsResult.data;
                renderProducts(allProducts); // Precargar la tabla dentro del modal
            }

            if (clientsResult.success) {
                elements.clientSelect.innerHTML = '<option value="" selected disabled>Seleccione un cliente</option>';
                clientsResult.data.forEach(client => {
                    elements.clientSelect.innerHTML += `<option value="${client.id_person}">${client.name}</option>`;
                });
            }

            await handleRequote();
        } catch (error) {
            console.error("Error al cargar datos iniciales:", error);
            elements.statusMessage.innerHTML = `<div class="alert alert-danger">Error al cargar datos iniciales.</div>`;
        }
    }

    // --- LÓGICA DE RENDERIZADO ---
    function renderProducts(productsToRender) {
        if (productsToRender.length === 0) {
            elements.productsTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No se encontraron productos con ese criterio.</td></tr>';
            return;
        }
        elements.productsTbody.innerHTML = productsToRender.map(p => `
                <tr>
                    <td>${p.product_name}</td>
                    <td>${p.stock}</td>
                    <td>
                        <select class="form-select form-select-sm price-selector" 
                                data-id-product="${p.id_product}" 
                                data-id-batch="${p.id_batch}">
                            <option value="${p.sale_price}" data-price-name="Detalle" selected>P. Detalle: $${parseFloat(p.sale_price).toFixed(2)}</option>
                            ${p.sale_price_b > 0 ? `<option value="${p.sale_price_b}" data-price-name="Caja">P. Caja: $${parseFloat(p.sale_price_b).toFixed(2)}</option>` : ''}
                            ${p.sale_price_c > 0 ? `<option value="${p.sale_price_c}" data-price-name="Mayoreo">P. Mayoreo: $${parseFloat(p.sale_price_c).toFixed(2)}</option>` : ''}
                            ${p.sale_price_2 > 0 ? `<option value="${p.sale_price_2}" data-price-name="Blister">P. Blister: $${parseFloat(p.sale_price_2).toFixed(2)}</option>` : ''}
                            ${p.sale_price_3 > 0 ? `<option value="${p.sale_price_3}" data-price-name="Unidad">P. Unidad: $${parseFloat(p.sale_price_3).toFixed(2)}</option>` : ''}
                        </select>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control quantity-input" value="1" min="1" max="${p.stock}">
                            <button class="btn btn-sm btn-outline-primary add-to-cart-btn" 
                                    data-id-product="${p.id_product}"
                                    data-id-batch="${p.id_batch}"
                                    title="Añadir al carrito">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
        `).join('');
    }

    function renderCart() {
        elements.cartTbody.innerHTML = '';
        let total = 0;
        if (cart.length === 0) {
            elements.cartTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">El carrito está vacío.</td></tr>';
        } else {
            cart.forEach((item, index) => {
                const itemTotal = item.quantity * item.sale_price_applied;
                total += itemTotal;
                elements.cartTbody.innerHTML += `
                    <tr>
                        <td>${item.name}<br><small class="text-muted">${item.price_type_applied} @ $${item.sale_price_applied.toFixed(2)}</small></td>
                        <td>${item.quantity}</td>
                        <td>$${itemTotal.toFixed(2)}</td>
                        <td><button class="btn btn-sm btn-outline-danger remove-item-btn" data-index="${index}" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                    </tr>`;
            });
        }
        elements.cartTotalSpan.textContent = `$${total.toFixed(2)}`;
        elements.processQuoteBtn.disabled = cart.length === 0;
    }

    // --- LÓGICA DE NEGOCIO ---
    function addToCart(productId, batchId, quantity) {
        const product = allProducts.find(p => p.id_product == productId && p.id_batch == batchId);
        if (!product) return;

        const priceSelector = elements.productsTbody.querySelector(`.price-selector[data-id-batch="${batchId}"]`);
        const selectedPrice = parseFloat(priceSelector.value);
        const selectedPriceName = priceSelector.options[priceSelector.selectedIndex].dataset.priceName;
        
        cart.push({
            id_product: parseInt(productId),
            id_batch: parseInt(batchId),
            name: product.product_name,
            quantity: quantity,
            sale_price_applied: selectedPrice,
            price_type_applied: selectedPriceName
        });
        renderCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    async function handleRequote() {
        const urlParams = new URLSearchParams(window.location.search);
        const requoteId = urlParams.get('requote_id');
        if (!requoteId) return;

        elements.statusMessage.innerHTML = `<div class="alert alert-info">Cargando datos de cotización expirada #${requoteId}...</div>`;
        try {
            const [detailsRes, headerRes] = await Promise.all([
                fetch(`../controllers/quote_details_controller.php?id=${requoteId}`),
                fetch(`../controllers/list_quotes_controller.php?id=${requoteId}`)
            ]);
            const detailsResult = await detailsRes.json();
            const headerResult = await headerRes.json();

            if (detailsResult.success && headerResult.success) {
                elements.clientSelect.value = headerResult.data[0].id_client;
                const unavailableItems = [];
                detailsResult.data.forEach(oldItem => {
                    const currentProduct = allProducts.find(p => p.id_product == oldItem.id_product);
                    if (currentProduct) {
                        addToCart(currentProduct.id_product, currentProduct.id_batch, parseInt(oldItem.quantity));
                    } else {
                        unavailableItems.push(oldItem.product_name);
                    }
                });
                if (unavailableItems.length > 0) {
                    alert(`Los siguientes productos ya no están disponibles y no se han añadido:\n- ${unavailableItems.join('\n- ')}`);
                }
            }
        } catch (error) {
            console.error("Error al re-cotizar:", error);
        } finally {
            elements.statusMessage.innerHTML = '';
        }
    }

    // --- MANEJO DE EVENTOS ---
    elements.searchInput.addEventListener('keyup', () => {
        const searchTerm = elements.searchInput.value.toLowerCase();
        const filteredProducts = allProducts.filter(p => 
            p.product_name.toLowerCase().includes(searchTerm) || 
            p.product_code.toLowerCase().includes(searchTerm)
        );
        renderProducts(filteredProducts);
    });

    elements.productsTbody.addEventListener('click', function(e) {
        const button = e.target.closest('.add-to-cart-btn');
        if (button) {
            const row = button.closest('tr');
            const quantityInput = row.querySelector('.quantity-input');
            const quantity = parseInt(quantityInput.value);
            if (quantity > 0) {
                addToCart(button.dataset.idProduct, button.dataset.idBatch, quantity);
                quantityInput.value = 1; // Reset quantity input
            }
        }
    });

    elements.cartTbody.addEventListener('click', function(e) {
        const button = e.target.closest('.remove-item-btn');
        if (button) {
            removeFromCart(button.dataset.index);
        }
    });

    elements.processQuoteBtn.addEventListener('click', async function() {
        if (cart.length === 0) {
            alert('La cotización está vacía.');
            return;
        }
        if (!elements.clientSelect.value) {
            alert('Por favor, seleccione un cliente.');
            return;
        }
        const totalAmount = cart.reduce((sum, item) => sum + (item.quantity * item.sale_price_applied), 0);
        const quoteData = {
            "quoteHeader": {
                "id_client": elements.clientSelect.value,
                "total_amount": totalAmount
            },
            "quoteDetails": cart.map(({ name, ...rest }) => rest) // Excluir el nombre del producto, ya no se guarda
        };

        elements.statusMessage.innerHTML = '<div class="alert alert-info">Procesando...</div>';
        try {
            const response = await fetch('../controllers/quotes_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(quoteData)
            });
            const data = await response.json();
            if (data.success) {
                elements.statusMessage.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                cart = [];
                renderCart();
                setTimeout(() => elements.statusMessage.innerHTML = '', 3000);
            } else {
                elements.statusMessage.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
            }
        } catch (error) {
            elements.statusMessage.innerHTML = `<div class="alert alert-danger">Error de conexión.</div>`;
        }
    });

    // --- INICIALIZACIÓN ---
    loadInitialData();
    renderCart();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>