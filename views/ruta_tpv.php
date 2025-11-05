<?php
session_start();
// 1. Proteger la página y verificar permisos
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['user_permissions']) || !in_array('use_ruta_tpv', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "TPV de Ruta";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">TPV de Ruta - Vendedor: <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
</div>

<div class="container">
    <div class="card">
        <div class="card-body">
            <!-- 1. Selección de Cliente -->
            <div class="mb-3">
                <label for="client-select" class="form-label fs-5">1. Cliente</label>
                <div class="input-group">
                    <select id="client-select" class="form-select"></select>
                    <button class="btn btn-outline-primary" type="button" id="add-client-btn-tpv" title="Añadir Nuevo Cliente"><i class="bi bi-person-plus"></i></button>
                </div>
            </div>
            <hr>

            <!-- 2. Búsqueda y adición de productos -->
            <div class="mb-3">
                <label class="form-label fs-5">2. Añadir Productos al Pedido</label>
                <div class="card text-center">
                    <div class="card-body">
                        <p class="card-text">Haz clic en el botón para buscar y añadir productos del inventario al pedido.</p>
                        <button id="open-catalog-btn" class="btn btn-primary btn-lg" type="button" data-bs-toggle="modal" data-bs-target="#searchProductModal">
                            <i class="bi bi-search"></i> Buscar en Catálogo
                        </button>
                    </div>
                </div>
            </div>
            <hr>

            <!-- 3. Carrito de Venta -->
            <h5 class="card-title">Pedido Actual</h5>
            <div class="table-responsive" style="max-height: 40vh; overflow-y: auto;">
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
                        <!-- Los items del carrito se agregarán aquí -->
                    </tbody>
                </table>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>Subtotal:</span>
                <span id="cart-subtotal">$0.00</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span>Descuentos:</span>
                <span id="cart-discounts" class="text-danger">-$0.00</span>
            </div>
            <h3 class="d-flex justify-content-between align-items-center fw-bold">
                <span>Total:</span>
                <span id="cart-total">$0.00</span>
            </h3>
            
            <!-- 4. Botón para finalizar -->
            <div class="d-grid mt-3">
                <button id="process-sale-btn" class="btn btn-success btn-lg" disabled>
                    <i class="bi bi-check-circle"></i> Enviar Pedido a Farmacia
                </button>
            </div>
            <div id="status-message" class="mt-3"></div>
        </div>
    </div>
</div>

<!-- Modal para añadir cliente (reutilizado de tpv.php) -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addClientForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientModalLabel">Añadir Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="newClientName" class="form-label">Nombre Completo</label><input type="text" class="form-control" id="newClientName" name="name" required></div>
                        <div class="col-md-6 mb-3"><label for="newClientDocumentNumber" class="form-label">DUI</label><input type="text" class="form-control" id="newClientDocumentNumber" name="document_number" required></div>
                    </div>
                    <div class="mb-3"><label for="newClientAddress" class="form-label">Dirección</label><textarea class="form-control" id="newClientAddress" name="address" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="newClientPhone" class="form-label">Teléfono</label><input type="text" class="form-control" id="newClientPhone" name="phone"></div>
                        <div class="col-md-6 mb-3"><label for="newClientEmail" class="form-label">Email</label><input type="email" class="form-control" id="newClientEmail" name="email"></div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3"><label for="newClientNit" class="form-label">NIT</label><input type="text" class="form-control" id="newClientNit" name="nit"></div>
                        <div class="col-md-6 mb-3"><label for="newClientNrc" class="form-label">NRC</label><input type="text" class="form-control" id="newClientNrc" name="nrc"></div>
                    </div>
                    <div class="mb-3">
                        <label for="newClientGiro" class="form-label">Giro</label>
                        <input type="text" class="form-control" id="newClientGiro" name="giro" placeholder="Ej: Venta al por menor...">
                    </div>
                    <div id="newClientFormStatus" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                </div>
            </form>
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
                    <input type="text" id="product-search-modal-input" class="form-control" placeholder="Buscar producto por nombre o código...">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Lote / Stock</th>
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
        </div>
    </div>
</div>

<script src="https://unpkg.com/imask"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- ESTADO ---
    let cart = [];
    let allProducts = [];

    // --- SELECTORES DOM ---
    const elements = {
        productsTbody: document.getElementById('available-products-tbody'),
        cartTbody: document.getElementById('cart-tbody'),
        cartSubtotal: document.getElementById('cart-subtotal'),
        cartDiscounts: document.getElementById('cart-discounts'),
        cartTotal: document.getElementById('cart-total'),
        processSaleBtn: document.getElementById('process-sale-btn'),
        statusMessage: document.getElementById('status-message'),
        clientSelect: $('#client-select'),
        searchInputModal: document.getElementById('product-search-modal-input'),
        addClientBtn: document.getElementById('add-client-btn-tpv'),
        clientModal: new bootstrap.Modal(document.getElementById('clientModal')),
        addClientForm: document.getElementById('addClientForm'),
        newClientFormStatus: document.getElementById('newClientFormStatus')
    };

    // --- MÁSCARAS Y MODALES ---
    const searchProductModal = new bootstrap.Modal(document.getElementById('searchProductModal'));
    IMask(document.getElementById('newClientDocumentNumber'), { mask: '00000000-0' });
    IMask(document.getElementById('newClientNit'), { mask: '0000-000000-000-0' });

    // --- INICIALIZACIÓN ---
    function initializeSelectors() {
        elements.clientSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione un cliente...',
            ajax: {
                url: '../controllers/clients_controller.php?for_tpv=true',
                dataType: 'json',
                delay: 250,
                processResults: data => ({ results: data.results.map(c => ({ id: c.id_person, text: c.name })) }),
                cache: true
            }
        });
        loadAllProductsForModal();
    }

    // --- LÓGICA DE RENDERIZADO DEL CATÁLOGO EN MODAL ---
    function renderProductsInModal(productsToRender) {
        if (productsToRender.length === 0) {
            elements.productsTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No se encontraron productos.</td></tr>';
            return;
        }
        elements.productsTbody.innerHTML = productsToRender.map(p => {
            const salePrice = parseFloat(p.sale_price) || 0;
            const salePriceB = parseFloat(p.sale_price_b) || 0;
            const salePriceC = parseFloat(p.sale_price_c) || 0;
            const salePrice2 = parseFloat(p.sale_price_2) || 0;
            const salePrice3 = parseFloat(p.sale_price_3) || 0;

            return `
                <tr data-id-batch="${p.id_batch}" class="${p.stock <= 5 ? 'table-warning' : ''}">
                    <td>${p.product_name}<br><small class="text-muted">${p.product_code || ''}</small></td>
                    <td>${p.batch_number}<br><span class="badge bg-secondary">Stock: ${p.stock}</span></td>
                    <td>
                        <select class="form-select form-select-sm price-selector">
                            <option value="${salePrice}" data-price-name="Publico">P. Público: $${salePrice.toFixed(2)}</option>
                            ${salePriceB > 0 ? `<option value="${salePriceB}" data-price-name="Caja">P. Caja: $${salePriceB.toFixed(2)}</option>` : ''}
                            ${salePriceC > 0 ? `<option value="${salePriceC}" data-price-name="Mayoreo">P. Mayoreo: $${salePriceC.toFixed(2)}</option>` : ''}
                            ${salePrice2 > 0 ? `<option value="${salePrice2}" data-price-name="Blister">P. Blister: $${salePrice2.toFixed(2)}</option>` : ''}
                            ${salePrice3 > 0 ? `<option value="${salePrice3}" data-price-name="Unidad" selected>P. Unidad: $${salePrice3.toFixed(2)}</option>` : `<option value="${salePrice}" data-price-name="Publico" selected>P. Público: $${salePrice.toFixed(2)}</option>`}
                        </select>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control quantity-input" value="1" min="1" max="${p.stock}">
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary add-to-cart-btn" 
                                data-id-product="${p.id_product}" 
                                data-id-batch="${p.id_batch}"
                                data-name="${p.product_name}"
                                title="Añadir al carrito">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // --- LÓGICA DE CARGA DE PRODUCTOS PARA EL MODAL ---
    async function loadAllProductsForModal() {
        elements.productsTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Cargando productos...</td></tr>';
        try {
            const response = await fetch('../controllers/get_products_for_tpv.php');
            const result = await response.json();
            if (result.success) {
                allProducts = result.data;
                renderProductsInModal(allProducts);
            } else {
                elements.productsTbody.innerHTML = `<tr><td colspan="5" class="text-danger">${result.message}</td></tr>`;
            }
        } catch (error) {
            console.error('Error al cargar productos para el modal:', error);
            elements.productsTbody.innerHTML = '<tr><td colspan="5" class="text-danger">Error al cargar productos.</td></tr>';
        }
    }

    // --- LÓGICA DEL CARRITO ---
    function addToCart(dataset, quantity, price, priceName) {
        const parsedQuantity = parseInt(quantity);
        if (isNaN(parsedQuantity) || parsedQuantity <= 0) {
            alert('Por favor, ingrese una cantidad válida.');
            return;
        }

        const fullProductData = allProducts.find(p => p.id_batch == dataset.idBatch);
        if (!fullProductData || parsedQuantity > fullProductData.stock) {
            alert(`Stock insuficiente. Disponible: ${fullProductData ? fullProductData.stock : 0}.`);
            return;
        }

        const existingItem = cart.find(item => item.id_batch === dataset.idBatch);
        if (existingItem) {
            existingItem.quantity += parsedQuantity;
        } else {
            cart.push({
                id_product: parseInt(dataset.idProduct),
                id_batch: parseInt(dataset.idBatch),
                name: dataset.name,
                quantity: parsedQuantity,
                sale_price_applied: parseFloat(price),
                price_type_applied: priceName,
                discount: 0,
                stock_available: fullProductData.stock
            });
        }
        renderCart();
    }

    function renderCart() {
        let subtotal = 0;
        let totalDiscounts = 0;

        if (cart.length === 0) {
            elements.cartTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">El pedido está vacío.</td></tr>';
        } else {
            elements.cartTbody.innerHTML = cart.map((item, index) => {
                const itemSubtotal = item.quantity * item.sale_price_applied;
                const itemDiscount = item.discount || 0;
                subtotal += itemSubtotal;
                totalDiscounts += itemDiscount;
                return `
                    <tr>
                        <td>
                            ${item.name} <span class="badge bg-secondary">${item.price_type_applied}</span>
                            <div class="input-group input-group-sm mt-1" style="width: 150px;">
                                <span class="input-group-text">Desc. $</span>
                                <input type="number" class="form-control discount-input" data-index="${index}" value="${itemDiscount.toFixed(2)}" min="0" step="0.01">
                            </div>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm quantity-cart-input" data-index="${index}" value="${item.quantity}" min="1" max="${item.stock_available}">
                        </td>
                        <td>$${(itemSubtotal - itemDiscount).toFixed(2)}</td>
                        <td><button class="btn btn-sm btn-outline-danger remove-item-btn" data-index="${index}" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                    </tr>`;
            }).join('');
        }

        elements.cartSubtotal.textContent = `$${subtotal.toFixed(2)}`;
        elements.cartDiscounts.textContent = `-$${totalDiscounts.toFixed(2)}`;
        elements.cartTotal.textContent = `$${(subtotal - totalDiscounts).toFixed(2)}`;
        elements.processSaleBtn.disabled = cart.length === 0 || !elements.clientSelect.val();
    }

    function updateCartItem(index, newQuantity, newDiscount) {
        const item = cart[index];
        if (!item) return;

        if (newQuantity !== undefined) {
            if (newQuantity <= 0) {
                cart.splice(index, 1);
            } else if (newQuantity > item.stock_available) {
                alert(`Stock insuficiente. Disponible: ${item.stock_available}`);
            } else {
                item.quantity = newQuantity;
            }
        }
        if (newDiscount !== undefined) {
            const itemSubtotal = item.quantity * item.sale_price_applied;
            item.discount = Math.max(0, Math.min(newDiscount, itemSubtotal));
        }
        renderCart();
    }

    // --- MANEJO DE EVENTOS ---
    elements.searchInputModal.addEventListener('keyup', () => {
        const searchTerm = elements.searchInputModal.value.toLowerCase();
        const filteredProducts = allProducts.filter(p => 
            p.product_name.toLowerCase().includes(searchTerm) ||
            (p.product_code && p.product_code.toLowerCase().includes(searchTerm))
        );
        renderProductsInModal(filteredProducts);
    });

    elements.productsTbody.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-cart-btn')) {
            const button = e.target.closest('.add-to-cart-btn');
            const row = button.closest('tr');
            row.classList.add('table-success');
            const quantityInput = row.querySelector('.quantity-input');
            const priceSelector = row.querySelector('.price-selector');
            const selectedPrice = priceSelector.value;
            const selectedPriceName = priceSelector.options[priceSelector.selectedIndex].dataset.priceName;
            
            addToCart(button.dataset, quantityInput.value, selectedPrice, selectedPriceName); 
            
            searchProductModal.hide();
            document.getElementById('open-catalog-btn').focus();
            setTimeout(() => row.classList.remove('table-success'), 500);
        }
    });

    elements.cartTbody.addEventListener('click', e => {
        if (e.target.closest('.remove-item-btn')) {
            updateCartItem(e.target.closest('.remove-item-btn').dataset.index, 0);
        }
    });

    elements.cartTbody.addEventListener('input', e => {
        const index = e.target.dataset.index;
        if (e.target.classList.contains('quantity-cart-input')) {
            updateCartItem(index, parseInt(e.target.value));
        } else if (e.target.classList.contains('discount-input')) {
            updateCartItem(index, undefined, parseFloat(e.target.value));
        }
    });

    elements.addClientBtn.addEventListener('click', () => {
        elements.addClientForm.reset();
        elements.newClientFormStatus.innerHTML = '';
        elements.clientModal.show();
    });

    elements.addClientForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        elements.newClientFormStatus.innerHTML = '<div class="alert alert-info">Guardando...</div>';
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('../controllers/clients_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                elements.newClientFormStatus.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                const newOption = new Option(result.data.name, result.data.id_person, true, true);
                elements.clientSelect.append(newOption).trigger('change');
                setTimeout(() => elements.clientModal.hide(), 1000);
            } else {
                elements.newClientFormStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        } catch (error) {
            elements.newClientFormStatus.innerHTML = `<div class="alert alert-danger">Error de conexión.</div>`;
        }
    });

    elements.clientSelect.on('change', () => renderCart());

    elements.processSaleBtn.addEventListener('click', async function() {
        const totalAmount = parseFloat(elements.cartTotal.textContent.replace('$', ''));
        
        // Estructura de datos PLANA para el backend, evitando el error de `saleHeader`
        const saleData = {
            "id_client": elements.clientSelect.val(),
            "total_amount": totalAmount,
            "payment_method": "Crédito", // Fijo para ventas de ruta
            "is_rutero_sale": true,
            "saleDetails": cart.map(item => ({
                "id_product": item.id_product,
                "id_batch": item.id_batch,
                "quantity": item.quantity,
                // 🛑 CORRECCIÓN CRÍTICA: Se usa 'sale_price_applied' para coincidir con la columna de la BD.
                "sale_price_applied": item.sale_price_applied,
                "discount": item.discount || 0
            }))
        };

        elements.statusMessage.innerHTML = '<div class="alert alert-info">Enviando pedido...</div>';
        this.disabled = true;

        try {
            const response = await fetch('../controllers/sales_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(saleData)
            });
            const result = await response.json();

            if (result.success) {
                elements.statusMessage.innerHTML = `<div class="alert alert-success">Pedido enviado exitosamente.</div>`;
                cart = [];
                renderCart();
                elements.clientSelect.val(null).trigger('change');
                setTimeout(() => elements.statusMessage.innerHTML = '', 3000);
            } else {
                elements.statusMessage.innerHTML = `<div class="alert alert-danger"><b>Error:</b> ${result.message}</div>`;
            }
        } catch (error) {
            elements.statusMessage.innerHTML = `<div class="alert alert-danger"><b>Error de conexión.</b> No se pudo comunicar con el servidor.</div>`;
        } finally {
            this.disabled = false;
            renderCart();
        }
    });

    // Carga inicial
    initializeSelectors();
    renderCart();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>