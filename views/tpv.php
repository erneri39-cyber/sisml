<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Terminal Punto de Venta (TPV)";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Terminal Punto de Venta (TPV) - Sucursal <?php echo htmlspecialchars($_SESSION['id_branch']); ?></h1>
</div>

<div class="container">
    <div class="row g-4">
        <!-- Columna Izquierda: Catálogo de Productos -->
        <div class="col-lg-7">
            <h5>1. Añadir Productos a la Venta</h5>
            <div class="card text-center">
                <div class="card-body">
                    <p class="card-text">Haz clic en el botón para buscar y añadir productos del inventario a la venta actual.</p>
                    <button class="btn btn-primary btn-lg" type="button" data-bs-toggle="modal" data-bs-target="#searchProductModal">
                        <i class="bi bi-search"></i> Buscar en Catálogo
                    </button>
                    <div class="form-text mt-2">
                        El catálogo mostrará todos los lotes con stock disponible en esta sucursal.
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Carrito de Venta -->
        <div class="col-lg-5">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">Detalle de Venta</h5>
                    <div class="row mb-2">
                        <div class="col-6"><label class="form-label">Fecha:</label><input type="text" class="form-control form-control-sm" value="<?php echo date('d/m/Y'); ?>" readonly></div>
                        <div class="col-6"><label class="form-label">Usuario:</label><input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly></div>
                    </div>
                    <div class="input-group mb-3">
                        <select id="client-select" class="form-select"></select>
                        {/* ✅ Botón para añadir nuevo cliente, ya implementado. */}
                        <button class="btn btn-outline-primary" type="button" id="add-client-btn-tpv" title="Añadir Nuevo Cliente"><i class="bi bi-person-plus"></i></button>
                    </div>
                    <div class="table-responsive flex-grow-1" style="min-height: 250px;">
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
                    <div class="d-grid mt-auto">
                        <button id="process-sale-btn" class="btn btn-success btn-lg" disabled>
                            <i class="bi bi-cash-coin"></i> Cobrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="status-message" class="mt-3"></div>
</div>

<!-- Modal de Búsqueda de Productos (como en cotizaciones.php) -->
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

<!-- Modal para Añadir Cliente -->
<div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addClientForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClientModalLabel">Añadir Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="newClientName" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="newClientName" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="newClientDocumentNumber" class="form-label">Número de Documento (DUI)</label>
                            <input type="text" class="form-control" id="newClientDocumentNumber" name="document_number" required>
                            <div id="newClientDuiFeedback" class="form-text"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newClientAddress" class="form-label">Dirección</label>
                        <textarea class="form-control" id="newClientAddress" name="address" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="newClientPhone" class="form-label">Teléfono</label><input type="text" class="form-control" id="newClientPhone" name="phone"></div>
                        <div class="col-md-6 mb-3"><label for="newClientEmail" class="form-label">Email</label><input type="email" class="form-control" id="newClientEmail" name="email"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="newClientNit" class="form-label">NIT</label><input type="text" class="form-control" id="newClientNit" name="nit"><div id="newClientNitFeedback" class="form-text"></div></div>
                        <div class="col-md-6 mb-3"><label for="newClientNrc" class="form-label">NRC (Registro Fiscal)</label><input type="text" class="form-control" id="newClientNrc" name="nrc"></div>
                    </div>
                    <div class="mb-3">
                        <label for="newClientGiro" class="form-label">Giro</label>
                        <input type="text" class="form-control" id="newClientGiro" name="giro" placeholder="Ej: Venta al por menor de productos farmacéuticos">
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

<!-- Modal de Pago -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Procesar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h3 class="text-center mb-3">Total a Pagar: <span id="payment-total" class="fw-bold text-success"></span></h3>
                <div class="mb-3">
                    <label for="payment-method" class="form-label">Método de Pago</label>
                    <select id="payment-method" class="form-select">
                        <option value="Efectivo" selected>Efectivo</option>
                        <option value="Tarjeta">Tarjeta</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Crédito">Crédito</option>
                    </select>
                </div>
                <div id="cash-payment-section">
                    <div class="mb-3">
                        <label for="cash-received" class="form-label">Efectivo Recibido</label>
                        <input type="number" class="form-control" id="cash-received" step="0.01" min="0">
                    </div>
                    <h4 class="text-center">Cambio: <span id="cash-change" class="fw-bold text-primary">$0.00</span></h4>
                </div>
                <div id="payment-status" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="confirm-payment-btn" class="btn btn-primary">Confirmar Venta</button>
            </div>
        </div>
    </div>
</div>

<!-- Incluir imask.js desde CDN para el modal de cliente -->
<script src="https://unpkg.com/imask"></script>

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
        cartSubtotal: document.getElementById('cart-subtotal'),
        cartDiscounts: document.getElementById('cart-discounts'),
        cartTotal: document.getElementById('cart-total'),
        processSaleBtn: document.getElementById('process-sale-btn'),
        statusMessage: document.getElementById('status-message'),
        searchInput: document.getElementById('product-search'), // Input de búsqueda DENTRO del modal
        isRuteroCheck: document.getElementById('is-rutero-sale-check'),
        clientSelect: $('#client-select'), // jQuery object for Select2
        paymentModal: new bootstrap.Modal(document.getElementById('paymentModal')),
        paymentTotal: document.getElementById('payment-total'),
        paymentMethod: document.getElementById('payment-method'),
        cashPaymentSection: document.getElementById('cash-payment-section'),
        cashReceived: document.getElementById('cash-received'),
        cashChange: document.getElementById('cash-change'),
        confirmPaymentBtn: document.getElementById('confirm-payment-btn'),
        paymentStatus: document.getElementById('payment-status'),
        searchProductModal: new bootstrap.Modal(document.getElementById('searchProductModal')),
        addClientBtnTpv: document.getElementById('add-client-btn-tpv'),
        addClientModal: new bootstrap.Modal(document.getElementById('addClientModal')),
        addClientForm: document.getElementById('addClientForm'),
        newClientName: document.getElementById('newClientName'),
        newClientDocumentNumber: document.getElementById('newClientDocumentNumber'),
        newClientDuiFeedback: document.getElementById('newClientDuiFeedback'),
        newClientNit: document.getElementById('newClientNit'),
        newClientNitFeedback: document.getElementById('newClientNitFeedback'),
        newClientFormStatus: document.getElementById('newClientFormStatus')
    };

    // --- MÁSCARAS PARA CAMPOS DE CLIENTE ---
    const newClientDuiMask = IMask(elements.newClientDocumentNumber, { mask: '00000000-0' });
    const newClientNitMask = IMask(elements.newClientNit, { mask: '0000-000000-000-0' });


    // --- LÓGICA DE DATOS ---
    async function loadInitialData() {
        try {
            const productsRes = await fetch('../controllers/get_products_for_tpv.php');
            const productsResult = await productsRes.json();
            if (productsResult.success) {
                allProducts = productsResult.data;
                renderProducts(allProducts);
            }

            initializeClientSelector();
            await handleQuoteConversion();
        } catch (error) {
            console.error("Error al cargar datos iniciales:", error);
            elements.statusMessage.innerHTML = `<div class="alert alert-danger">Error al cargar datos.</div>`;
        }
    }

    async function handleQuoteConversion() {
        const urlParams = new URLSearchParams(window.location.search);
        const quoteId = urlParams.get('from_quote');
        if (!quoteId) return;

        originQuoteId = quoteId;
        elements.statusMessage.innerHTML = `<div class="alert alert-info">Cargando datos de la cotización #${quoteId}...</div>`;

        try {
            const [detailsRes, headerRes] = await Promise.all([
                fetch(`../controllers/quote_details_controller.php?id=${quoteId}`),
                fetch(`../controllers/list_quotes_controller.php?id=${quoteId}`)
            ]);
            const detailsResult = await detailsRes.json();
            const headerResult = await headerRes.json();

            if (detailsResult.success && headerResult.success) {
                const stockCheck = checkStockForQuote(detailsResult.data);
                if (!stockCheck.sufficient) {
                    const issuesHtml = stockCheck.issues.map(i => `<li><b>${i.name}</b>: Se requieren ${i.required}, disponible ${i.available}.</li>`).join('');
                    elements.statusMessage.innerHTML = `<div class="alert alert-danger"><strong>Stock insuficiente para convertir cotización.</strong><ul>${issuesHtml}</ul></div>`;
                    elements.processSaleBtn.disabled = true;
                    return;
                }

                const client = headerResult.data[0];
                const newOption = new Option(client.client_name, client.id_client, true, true);
                elements.clientSelect.append(newOption).trigger('change');

                cart = detailsResult.data.map(item => ({
                    id_product: parseInt(item.id_product),
                    id_batch: parseInt(item.id_batch),
                    name: item.product_name,
                    quantity: parseInt(item.quantity),
                    sale_price_applied: parseFloat(item.price_quoted),
                    price_type_applied: 'Cotizado',
                    discount: 0
                }));
                renderCart();
            }
        } catch (error) {
            console.error("Error al cargar cotización:", error);
        } finally {
            elements.statusMessage.innerHTML = '';
        }
    }

    function checkStockForQuote(quoteItems) {
        const issues = [];
        for (const item of quoteItems) {
            const productInStock = allProducts.find(p => p.id_batch == item.id_batch);
            if (!productInStock || productInStock.stock < item.quantity) {
                issues.push({
                    name: item.product_name,
                    required: item.quantity,
                    available: productInStock ? productInStock.stock : 0
                });
            }
        }
        return { sufficient: issues.length === 0, issues: issues };
    }

    // --- LÓGICA DE RENDERIZADO ---
    function renderProducts(productsToRender) {
        elements.productsTbody.innerHTML = productsToRender.map(p => {
            // CORRECCIÓN: Asegurarse de que todos los precios sean números antes de usar toFixed()
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
                        <select class="form-select form-select-sm price-selector" 
                                data-id-product="${p.id_product}" 
                                data-id-batch="${p.id_batch}">
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
                            <button class="btn btn-sm btn-primary add-to-cart-btn" 
                                    data-id-product="${p.id_product}" 
                                    data-id-batch="${p.id_batch}"
                                    data-name="${p.product_name}"
                                    title="Añadir al carrito">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
        `}).join('');
    }

    function renderCart() {
        let subtotal = 0;
        let totalDiscounts = 0;

        if (cart.length === 0) {
            elements.cartTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">El carrito está vacío.</td></tr>';
        } else {
            elements.cartTbody.innerHTML = cart.map((item, index) => {
                const itemSubtotal = item.quantity * item.sale_price_applied;
                const itemDiscount = item.discount || 0;
                subtotal += itemSubtotal;
                totalDiscounts += itemDiscount;
                return `
                    <tr>
                        <td>
                            ${item.name}
                            <div class="input-group input-group-sm mt-1" style="width: 150px;">
                                <span class="input-group-text">Desc. $</span>
                                <input type="number" class="form-control discount-input" data-index="${index}" value="${itemDiscount.toFixed(2)}" min="0" step="0.01">
                            </div>
                        </td>
                        <td>${item.quantity}</td>
                        <td>$${(itemSubtotal - itemDiscount).toFixed(2)}</td>
                        <td><button class="btn btn-sm btn-outline-danger remove-item-btn" data-index="${index}" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                    </tr>`;
            }).join('');
        }

        elements.cartSubtotal.textContent = `$${subtotal.toFixed(2)}`;
        elements.cartDiscounts.textContent = `-$${totalDiscounts.toFixed(2)}`;
        elements.cartTotal.textContent = `$${(subtotal - totalDiscounts).toFixed(2)}`;
        elements.processSaleBtn.disabled = cart.length === 0;
    }

    // --- LÓGICA DE NEGOCIO ---
    function addToCart(productData, quantity, price, priceName) {
        // CORRECCIÓN: La cantidad ahora viene como parámetro.
        const parsedQuantity = parseInt(quantity);
        const parsedPrice = parseFloat(price);

        if (isNaN(parsedQuantity) || parsedQuantity <= 0) {
            alert('Por favor, ingrese una cantidad válida.');
            return;
        }

        const availableProduct = allProducts.find(p => p.id_batch == productData.idBatch);
        if (availableProduct && quantity > availableProduct.stock) {
            alert(`Stock insuficiente para este lote. Disponible: ${availableProduct.stock}, Solicitado: ${quantity}.`);
            return; // Detener si no hay stock
        }

        const existingCartItem = cart.find(item => item.id_batch == productData.idBatch);

        if (existingCartItem) {
            const newQuantity = existingCartItem.quantity + parsedQuantity;
            if (newQuantity > availableProduct.stock) {
                alert(`Stock insuficiente. Ya tiene ${existingCartItem.quantity} en el carrito. Disponible: ${availableProduct.stock}.`);
                return;
            }
            existingCartItem.quantity = newQuantity;
        } else {
            cart.push({
                id_product: parseInt(productData.idProduct),
                id_batch: parseInt(productData.idBatch),
                name: productData.name,
                quantity: parsedQuantity,
                sale_price_applied: parsedPrice,
                price_type_applied: priceName,
                discount: 0
            });
        }

        // Resetear el input de cantidad en la tabla
        const row = document.querySelector(`tr[data-id-batch="${productData.idBatch}"]`);
        if (row) row.querySelector('.quantity-input').value = 1;

        renderCart();
    }

    // --- MANEJO DE EVENTOS ---
    elements.searchInput.addEventListener('keyup', () => {
        const searchTerm = elements.searchInput.value.toLowerCase();
        const filteredProducts = allProducts.filter(p => 
            p.product_name.toLowerCase().includes(searchTerm) || 
            (p.product_code && p.product_code.toLowerCase().includes(searchTerm))
        );
        renderProducts(filteredProducts);
    });

    elements.productsTbody.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-cart-btn')) {
            const button = e.target.closest('.add-to-cart-btn');
            // Encontrar el selector de precio en la misma fila que el botón
            const row = button.closest('tr');
            row.classList.add('table-success'); // Resaltar fila brevemente
            const quantityInput = row.querySelector('.quantity-input');
            const priceSelector = row.querySelector('.price-selector');
            const selectedPrice = priceSelector.value;
            const selectedPriceName = priceSelector.options[priceSelector.selectedIndex].dataset.priceName;
            addToCart(button.dataset, quantityInput.value, selectedPrice, selectedPriceName);
            elements.searchProductModal.hide(); // Cerrar el modal después de añadir
            // CORRECCIÓN DE ACCESIBILIDAD: Devolver el foco al botón que abrió el modal.
            // Se usa un selector de atributo porque el botón no tiene ID.
            document.querySelector('[data-bs-target="#searchProductModal"]').focus();
            setTimeout(() => row.classList.remove('table-success'), 500); // Quitar resaltado
        }
    });

    elements.cartTbody.addEventListener('input', function(e) {
        const discountInput = e.target.closest('.discount-input');
        if (discountInput) {
            const index = discountInput.dataset.index;
            let newDiscount = parseFloat(discountInput.value) || 0;
            const itemSubtotal = cart[index].quantity * cart[index].sale_price_applied;
            if (newDiscount > itemSubtotal) newDiscount = itemSubtotal;

            cart[index].discount = newDiscount;
            renderCart(); // Re-renderizar para actualizar totales
        }
    });

    elements.cartTbody.addEventListener('click', function(e) {
        const discountInput = e.target.closest('.discount-input');
        if (discountInput) {
            const index = discountInput.dataset.index;
            let newDiscount = parseFloat(discountInput.value) || 0;
            const itemSubtotal = cart[index].quantity * cart[index].sale_price_applied;
            if (newDiscount > itemSubtotal) newDiscount = itemSubtotal;
            
            cart[index].discount = newDiscount;
            renderCart();
        }
    });

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    elements.processSaleBtn.addEventListener('click', function() {
        if (cart.length === 0) {
            alert('El carrito está vacío.');
            return;
        }
        if (!elements.clientSelect.val()) {
            alert('Por favor, seleccione un cliente.');
            return;
        }
        elements.paymentTotal.textContent = elements.cartTotal.textContent;
        elements.cashReceived.value = '';
        elements.cashChange.textContent = '$0.00';
        elements.paymentStatus.innerHTML = '';
        elements.paymentModal.show();
    });

    elements.paymentMethod.addEventListener('change', function() {
        elements.cashPaymentSection.style.display = this.value === 'Efectivo' ? 'block' : 'none';
    });

    elements.cashReceived.addEventListener('input', function() {
        const total = parseFloat(elements.cartTotal.textContent.replace('$', ''));
        const received = parseFloat(this.value) || 0;
        const change = received - total;
        elements.cashChange.textContent = `$${Math.max(0, change).toFixed(2)}`;
    });

    elements.confirmPaymentBtn.addEventListener('click', async function() {
        const total = cart.reduce((sum, item) => sum + (item.quantity * item.sale_price_applied - (item.discount || 0)), 0);
        const saleData = {
            "saleHeader": {
                "id_client": elements.clientSelect.val(),
                "total_amount": total,
                "payment_method": elements.paymentMethod.value,
                "cash_received": parseFloat(elements.cashReceived.value) || 0,
                "cash_change": parseFloat(elements.cashChange.textContent.replace('$', '')) || 0,
                "is_rutero_sale": false,
                "id_quotation": originQuoteId
            },
            "saleDetails": cart.map(({ name, ...rest }) => rest)
        };

        elements.paymentStatus.innerHTML = '<div class="alert alert-info">Procesando...</div>';
        this.disabled = true;

        try {
            const response = await fetch('../controllers/sales_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(saleData)
            });
            const result = await response.json();
            if (result.success) {
                elements.paymentStatus.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                setTimeout(() => {
                    elements.paymentModal.hide();
                    cart = [];
                    renderCart();
                    originQuoteId = null;
                    elements.clientSelect.val('1').trigger('change');
                }, 2000);
            } else {
                elements.paymentStatus.innerHTML = `<div class="alert alert-danger">Error: ${result.message}</div>`;
            }
        } catch (error) {
            elements.paymentStatus.innerHTML = `<div class="alert alert-danger">Error de conexión.</div>`;
        } finally {
            this.disabled = false;
        }
    });

    // --- LÓGICA PARA AÑADIR NUEVO CLIENTE (MODAL) ---
    elements.addClientBtnTpv.addEventListener('click', () => {
        elements.addClientForm.reset();
        elements.newClientFormStatus.innerHTML = '';
        elements.newClientDocumentNumber.classList.remove('is-invalid', 'is-valid');
        elements.newClientNit.classList.remove('is-invalid', 'is-valid');
        elements.newClientDuiFeedback.innerHTML = '';
        elements.newClientNitFeedback.innerHTML = '';
        elements.addClientModal.show();
    });

    elements.addClientForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        elements.newClientFormStatus.innerHTML = '<div class="alert alert-info">Guardando cliente...</div>';
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
                setTimeout(() => elements.addClientModal.hide(), 1000);
            } else {
                elements.newClientFormStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        } catch (error) {
            elements.newClientFormStatus.innerHTML = `<div class="alert alert-danger">Error de conexión al guardar cliente.</div>`;
        }
    });

    // Validación de unicidad para DUI y NIT en el modal de nuevo cliente
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    async function validateClientField(inputEl, feedbackEl, fieldName) {
        const value = inputEl.value.trim();
        if (value === '') {
            feedbackEl.innerHTML = '';
            inputEl.classList.remove('is-invalid', 'is-valid');
            return;
        }
        feedbackEl.textContent = 'Verificando...';
        feedbackEl.className = 'form-text text-muted';
        inputEl.classList.remove('is-invalid', 'is-valid');

        try {
            const response = await fetch(`../controllers/clients_controller.php?action=check_uniqueness&field=${fieldName}&value=${encodeURIComponent(value)}`);
            const result = await response.json();
            if (result.exists) {
                feedbackEl.textContent = `Este ${fieldName === 'document_number' ? 'DUI' : 'NIT'} ya está registrado.`;
                feedbackEl.className = 'form-text text-danger';
                inputEl.classList.add('is-invalid');
            } else {
                feedbackEl.textContent = `${fieldName === 'document_number' ? 'DUI' : 'NIT'} disponible.`;
                feedbackEl.className = 'form-text text-success';
                inputEl.classList.add('is-valid');
            }
        } catch (error) {
            feedbackEl.textContent = 'No se pudo verificar.';
            feedbackEl.className = 'form-text text-warning';
        }
    }

    elements.newClientDocumentNumber.addEventListener('input', debounce(() => validateClientField(elements.newClientDocumentNumber, elements.newClientDuiFeedback, 'document_number'), 500));
    elements.newClientNit.addEventListener('input', debounce(() => validateClientField(elements.newClientNit, elements.newClientNitFeedback, 'NIT'), 500));

    // --- INICIALIZACIÓN ---
    function initializeClientSelector() {
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
        // Setear "Consumidor Final" por defecto
        const defaultOption = new Option('Consumidor Final', '1', true, true);
        elements.clientSelect.append(defaultOption).trigger('change');
    }

    loadInitialData();
    renderCart();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>