<?php
// --- Controlador Embebido ---
session_start();

// 1. Proteger la página: Redirigir si no hay sesión de usuario.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Incluir dependencias
require_once dirname(__DIR__) . '/db_connect.php'; // Desde views, subir dos niveles para db_connect.php
require_once dirname(__DIR__) . '/models/Product.php'; // Desde views, subir dos niveles para models/Product.php

// 3. Lógica de la página
$productModel = new Product($pdo);
$products = $productModel->getAllByBranch($_SESSION['id_branch']); // Filtrado por sucursal

$pageTitle = "Gestión de Productos";
ob_start();
?>

<!-- --- Vista --- -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Productos (Sucursal: <?php echo htmlspecialchars($_SESSION['id_branch']); ?>)</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-lg"></i>
            Añadir Producto
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm" id="products-table"> <!-- ID añadido -->
        <thead>
            <tr>
                <th scope="col">Imagen</th>
                <th scope="col">Código</th>
                <th scope="col">Nombre</th>
                <th scope="col">Medida</th>
                <th scope="col">Categoría</th>
                <th scope="col">Laboratorio</th>
                <th scope="col">Ubicación</th>
                <th scope="col">Stock Total (Sucursal)</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody id="products-tbody"> <!-- ID añadido para ser más específico -->
            <?php foreach ($products as $product): ?>
            <tr data-product-id="<?php echo $product['id_product']; ?>">
                <td><img src="../<?php echo htmlspecialchars($product['primary_image'] ?? 'assets/img/placeholder.png'); ?>" alt="Producto" style="width: 40px; height: 40px; object-fit: cover;"></td>
                <td><?php echo htmlspecialchars($product['code']); ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['medida']); ?></td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td><?php echo htmlspecialchars($product['laboratorio']); ?></td>
                <td><?php echo htmlspecialchars($product['location']); ?></td>
                <td><?php echo htmlspecialchars($product['total_stock']); ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-success manage-images-btn" title="Gestionar Imágenes"><i class="bi bi-images"></i></button>
                    <button class="btn btn-sm btn-outline-info view-batches-btn" title="Ver Lotes"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-outline-secondary edit-product-btn" title="Editar Producto"><i class="bi bi-pencil-square"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Añadir Producto -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Añadir Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="addProductForm">
                <div class="modal-body">
                    <div id="addProductStatus"></div>

                    <ul class="nav nav-tabs" id="productTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab-add" data-bs-toggle="tab" data-bs-target="#general-pane-add" type="button" role="tab" aria-controls="general-pane-add" aria-selected="true">
                                <i class="bi bi-info-circle"></i> General
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content border border-top-0 p-3 rounded-bottom" id="productTabContentAdd">
                        
                        <div class="tab-pane fade show active" id="general-pane-add" role="tabpanel" aria-labelledby="general-tab-add">
                            
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="productName" class="form-label">Nombre del Producto</label>
                                    <input type="text" class="form-control" id="productName" name="name" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="productCode" class="form-label">Código/EAN</label>
                                    <input type="text" class="form-control" id="productCode" name="code" required>
                                    <div id="codeFeedbackAdd" class="form-text"></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="productCategory" class="form-label">Categoría</label>
                                    <select class="form-select" id="productCategory" name="id_category" required></select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="productLaboratorio" class="form-label">Laboratorio</label>
                                    <select class="form-select" id="productLaboratorio" name="id_laboratorio"></select>
                                </div>
                                <div class="col-md-6">
                                    <label for="productMedida" class="form-label">Unidad de Medida</label>
                                    <select class="form-select" id="productMedida" name="id_medida"></select>
                                </div>
                                <div class="col-md-6">
                                    <label for="productLocation" class="form-label">Ubicación (Estante/Sección)</label>
                                    <input type="text" class="form-control" id="productLocation" name="location">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="productDescription" class="form-label">Descripción</label>
                                <textarea class="form-control" id="productDescription" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Producto -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editProductForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editProductId" name="id_product">
                    <div id="editProductStatus"></div>

                    <!-- Pestañas para Editar -->
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="general-pane-edit" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="editProductName" class="form-label">Nombre del Producto</label>
                                    <input type="text" class="form-control" id="editProductName" name="name" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="editProductCode" class="form-label">Código/EAN</label>
                                    <input type="text" class="form-control" id="editProductCode" name="code" required>
                                    <div id="codeFeedbackEdit" class="form-text"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="editProductCategory" class="form-label">Categoría</label>
                                    <select class="form-select" id="editProductCategory" name="id_category" required></select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="editProductLaboratorio" class="form-label">Laboratorio</label>
                                    <select class="form-select" id="editProductLaboratorio" name="id_laboratorio"></select>
                                </div>
                                <div class="col-md-6">
                                    <label for="editProductMedida" class="form-label">Unidad de Medida</label>
                                    <select class="form-select" id="editProductMedida" name="id_medida"></select>
                                </div>
                                <div class="col-md-6">
                                    <label for="editProductLocation" class="form-label">Ubicación (Estante/Sección)</label>
                                    <input type="text" class="form-control" id="editProductLocation" name="location">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editProductDescription" class="form-label">Descripción</label>
                                <textarea class="form-control" id="editProductDescription" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Gestionar Imágenes -->
<div class="modal fade" id="imageManagementModal" tabindex="-1" aria-labelledby="imageManagementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageManagementModalLabel">Gestionar Imágenes para: <span id="imageModalProductTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6>Subir Nueva Imagen</h6>
                    <form id="uploadImageForm">
                        <input type="hidden" id="imageProductId" name="id_product">
                        <div class="input-group">
                            <input type="file" class="form-control" name="image" id="imageUploadInput" accept="image/*" required>
                            <button class="btn btn-primary" type="submit">Subir</button>
                        </div>
                    </form>
                </div>
                <hr>
                <h6>Imágenes Actuales</h6>
                <div id="image-gallery" class="row g-2">
                    <!-- Las imágenes se cargarán aquí -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Gestionar Lotes -->
<div class="modal fade" id="batchManagementModal" tabindex="-1" aria-labelledby="batchManagementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchManagementModalLabel">Gestionar Lotes para: <span id="batchModalProductTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Lista de Lotes -->
                <h6>Lotes Existentes</h6>
                <div class="table-responsive mb-4" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Lote</th>
                                <th>Stock</th>
                                <th>P. Compra</th>
                                <th>P. Detalle</th>
                                <th>P. Blister</th>
                                <th>P. Caja</th>
                                <th>P. Mayoreo</th>
                                <th>Vencimiento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="batchListTbody">
                            <!-- Lotes se cargarán aquí -->
                        </tbody>
                    </table>
                </div>

                <!-- Formulario para Añadir/Editar Lote -->
                <hr>
                <h6 id="batchFormTitle">Añadir Nuevo Lote</h6>
                <form id="batchForm">
                    <input type="hidden" id="batchId" name="id_batch">
                    <input type="hidden" id="batchProductId" name="id_product">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="batchNumber" class="form-label">Número de Lote</label>
                            <input type="text" class="form-control" id="batchNumber" name="batch_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="batchExpiration" class="form-label">Fecha de Vencimiento</label>
                            <input type="date" class="form-control" id="batchExpiration" name="expiration_date" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="batchStock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="batchStock" name="stock" required min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="batchPurchasePrice" class="form-label">Precio Compra</label>
                            <input type="number" class="form-control" id="batchPurchasePrice" name="purchase_price" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="batchSalePrice" class="form-label">P. Detalle</label>
                            <input type="number" class="form-control" id="batchSalePrice" name="sale_price" required step="0.01" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="batchSalePrice2" class="form-label">P. Blister</label>
                            <input type="number" class="form-control" id="batchSalePrice2" name="sale_price_2" step="0.01" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="batchSalePrice3" class="form-label">P. Caja</label>
                            <input type="number" class="form-control" id="batchSalePrice3" name="sale_price_3" step="0.01" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="batchSalePrice4" class="form-label">P. Mayoreo</label>
                            <input type="number" class="form-control" id="batchSalePrice4" name="sale_price_4" step="0.01" min="0">
                        </div>
                    </div>
                    <div id="batchFormStatus" class="mt-2"></div>
                    <button type="submit" class="btn btn-primary">Guardar Lote</button>
                    <button type="button" id="clearBatchFormBtn" class="btn btn-secondary">Limpiar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addMedidaSelect = document.getElementById('productMedida');
    const editMedidaSelect = document.getElementById('editProductMedida');
    const addCategorySelect = document.getElementById('productCategory');
    const editCategorySelect = document.getElementById('editProductCategory');
    const productCodeInputAdd = document.getElementById('productCode');
    const productCodeInputEdit = document.getElementById('editProductCode');
    const imageModal = new bootstrap.Modal(document.getElementById('imageManagementModal'));
    const imageModalTitle = document.getElementById('imageModalProductTitle');
    const imageProductIdInput = document.getElementById('imageProductId');
    const uploadImageForm = document.getElementById('uploadImageForm');

    // --- Lógica para Foco Automático en Modal de Añadir ---
    const addProductModalEl = document.getElementById('addProductModal');
    const productCodeInput = document.getElementById('productCode');

    addProductModalEl.addEventListener('shown.bs.modal', function () {
        productCodeInput.focus();
    });

    // --- Lógica para Cargar Selects (Laboratorios y Medidas) ---
    const addLaboratorioSelect = document.getElementById('productLaboratorio');
    const editLaboratorioSelect = document.getElementById('editProductLaboratorio');


    // Función genérica para poblar un select
    function populateSelect(selectElement, items, valueField, textField, defaultOptionText) {
        selectElement.innerHTML = `<option value="">${defaultOptionText}</option>`; // Limpiar y añadir opción por defecto
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = item[textField];
            selectElement.appendChild(option);
        });
    }

    // Cargar Laboratorios
    fetch('../controllers/laboratorios_controller.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                populateSelect(addLaboratorioSelect, result.data, 'id_laboratorio', 'nombre', 'Seleccione un laboratorio');
                populateSelect(editLaboratorioSelect, result.data, 'id_laboratorio', 'nombre', 'Seleccione un laboratorio');
            }
        });

    // Cargar Categorías
    fetch('../controllers/categories_controller.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                populateSelect(addCategorySelect, result.data, 'id_category', 'name', 'Seleccione una categoría');
                populateSelect(editCategorySelect, result.data, 'id_category', 'name', 'Seleccione una categoría');
            }
        });

    // Cargar Medidas
    fetch('../controllers/medidas_controller.php')
        .then(response => response.json())
        .then(result => {
            populateSelect(addMedidaSelect, result.data, 'id_medida', 'descripcion', 'Seleccione una medida (Opcional)');
            populateSelect(editMedidaSelect, result.data, 'id_medida', 'descripcion', 'Seleccione una medida (Opcional)');
        });

    // --- Lógica para Añadir Producto ---
    const addProductForm = document.getElementById('addProductForm');
    const addStatusDiv = document.getElementById('addProductStatus');
    const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));

    addProductForm.addEventListener('submit', function(e) {
        e.preventDefault();
        addStatusDiv.innerHTML = '<div class="alert alert-info">Guardando...</div>';

        const formData = new FormData(addProductForm);
        const data = Object.fromEntries(formData.entries());

        // Asegurarse de que los valores opcionales se envíen como null si están vacíos
        if (data.id_medida === '') {
            data.id_medida = null;
        }

        fetch('../controllers/products_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                addStatusDiv.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                
                // --- Lógica para añadir fila dinámicamente ---
                const newProduct = result.data;
                const tableBody = document.querySelector('.table tbody');
                const newRow = document.createElement('tr');
                newRow.setAttribute('data-product-id', newProduct.id_product);
                newRow.innerHTML = `
                    <td><img src="../assets/img/placeholder.png" alt="Producto" style="width: 40px; height: 40px; object-fit: cover;"></td>
                    <td>${newProduct.code}</td> 
                    <td>${newProduct.name}</td> 
                    <td>${newProduct.medida || ''}</td>
                    <td>${newProduct.category_name || ''}</td>
                    <td>${newProduct.laboratorio || ''}</td>
                    <td>0</td> <!-- Stock inicial es 0 ya que no tiene lotes -->
                    <td>
                        <button class="btn btn-sm btn-outline-info" title="Ver Lotes"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-success manage-images-btn" title="Gestionar Imágenes"><i class="bi bi-images"></i></button> 
                        <button class="btn btn-sm btn-outline-secondary edit-product-btn" title="Editar Producto"><i class="bi bi-pencil-square"></i></button> 
                    </td>
                `;
                tableBody.prepend(newRow); // Añadir la nueva fila al principio de la tabla

                addProductForm.reset();
                setTimeout(() => {
                    addProductModal.hide();
                    addStatusDiv.innerHTML = ''; // Limpiar el mensaje de estado
                }, 1500);
            } else {
                addStatusDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            addStatusDiv.innerHTML = '<div class="alert alert-danger">No se pudo conectar con el servidor.</div>';
        });
    });

    // --- Lógica para Editar Producto ---
    const editProductModal = new bootstrap.Modal(document.getElementById('editProductModal'));
    const editProductForm = document.getElementById('editProductForm');
    const editStatusDiv = document.getElementById('editProductStatus');
    const editProductIdInput = document.getElementById('editProductId'); 
    const mainTableBody = document.getElementById('products-tbody'); // Corregido para usar el ID específico

    // 2. Enviar formulario de edición
    editProductForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const productId = editProductIdInput.value;
        editStatusDiv.innerHTML = '<div class="alert alert-info">Actualizando...</div>';

        const formData = new FormData(editProductForm);
        const data = Object.fromEntries(formData.entries());
        
        // Asegurarse de que los valores opcionales se envíen como null si están vacíos
        if (data.id_medida === '') {
            data.id_medida = null;
        }
        delete data.id_product; // No es necesario en el body del PUT, ya va en la URL

        fetch(`../controllers/products_controller.php?id=${productId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                editStatusDiv.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                
                // Para actualizar la fila dinámicamente, necesitaríamos los nombres de laboratorio y medida,
                // no solo los IDs. Por simplicidad, recargamos la página para ver todos los cambios.
                // const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                // if (row) {
                //     row.cells[0].textContent = data.code;
                //     row.cells[1].textContent = data.name;
                //     // Para actualizar medida y laboratorio, necesitaríamos un fetch extra o tener los datos a mano.
                // }

                setTimeout(() => {
                    editProductModal.hide();
                    location.reload(); // Recargar para ver los cambios reflejados en la tabla principal
                }, 1500);
            } else {
                editStatusDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        })
        .catch(error => console.error('Error al actualizar:', error));
    });

    // --- Lógica para Validación de Código en Tiempo Real ---

    // Función de "debounce" para no llamar al servidor en cada pulsación
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    async function checkCodeUniqueness(code, feedbackEl, inputEl, ignoreId = null) {
        if (code.trim() === '') {
            feedbackEl.innerHTML = '';
            inputEl.classList.remove('is-invalid', 'is-valid');
            return;
        }

        feedbackEl.textContent = 'Verificando...';
        feedbackEl.className = 'form-text text-muted';
        inputEl.classList.remove('is-invalid', 'is-valid');

        let url = `../controllers/products_controller.php?action=check_code&code=${encodeURIComponent(code)}`;
        if (ignoreId) {
            url += `&ignore_id=${ignoreId}`;
        }

        try {
            const response = await fetch(url);
            const result = await response.json();

            if (result.exists) {
                feedbackEl.textContent = 'Este código ya está en uso.';
                feedbackEl.className = 'form-text text-danger';
                inputEl.classList.add('is-invalid');
            } else {
                feedbackEl.textContent = 'Código disponible.';
                feedbackEl.className = 'form-text text-success';
                inputEl.classList.add('is-valid');
            }
        } catch (error) {
            feedbackEl.textContent = 'No se pudo verificar.';
            feedbackEl.className = 'form-text text-warning';
        }
    }

    // Aplicar la validación a los campos de código con un retardo de 500ms
    productCodeInputAdd.addEventListener('input', debounce((e) => {
        checkCodeUniqueness(e.target.value, document.getElementById('codeFeedbackAdd'), e.target);
    }, 500));

    productCodeInputEdit.addEventListener('input', debounce((e) => {
        const productId = document.getElementById('editProductId').value;
        checkCodeUniqueness(e.target.value, document.getElementById('codeFeedbackEdit'), e.target, productId);
    }, 500));

    // --- Lógica para botones de acción en la tabla (CORREGIDO Y UNIFICADO) ---
    mainTableBody.addEventListener('click', function(e) {
        const imageBtn = e.target.closest('.manage-images-btn');
        const viewBtn = e.target.closest('.view-batches-btn');
        const editBtn = e.target.closest('.edit-product-btn');

        if (imageBtn) {
            e.preventDefault();
            const row = imageBtn.closest('tr');
            openImageModal(row.dataset.productId, row.cells[2].textContent);
            return;
        }

        if (viewBtn) {
            e.preventDefault();
            const row = viewBtn.closest('tr');
            openBatchModal(row.dataset.productId, row.cells[2].textContent);
            return;
        }

        if (editBtn) {
            e.preventDefault();
            const row = editBtn.closest('tr');
            openEditModal(row.dataset.productId);
            return;
        }
    });

    // --- Funciones para Modales ---

    async function openImageModal(productId, productName) {
        imageModalTitle.textContent = productName;
        imageProductIdInput.value = productId;
        await loadProductImages(productId);
        imageModal.show();
    }

    async function loadProductImages(productId) {
        const gallery = document.getElementById('image-gallery');
        gallery.innerHTML = '<p>Cargando imágenes...</p>';
        const response = await fetch(`../controllers/products_controller.php?action=get_images&id=${productId}`);
        const result = await response.json();
        gallery.innerHTML = '';
        if (result.success && result.data.length > 0) {
            result.data.forEach(img => {
                const primaryClass = img.is_primary == 1 ? 'border-primary border-3' : 'border-light';
                gallery.innerHTML += `
                    <div class="col-md-3">
                        <div class="card">
                            <img src="../${img.image_path}" class="card-img-top ${primaryClass}" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2 text-center">
                                <button class="btn btn-sm btn-outline-success set-primary-btn" data-image-id="${img.id_product_image}" ${img.is_primary == 1 ? 'disabled' : ''}>Principal</button>
                                <button class="btn btn-sm btn-outline-danger delete-image-btn" data-image-id="${img.id_product_image}"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            gallery.innerHTML = '<p class="text-muted">Este producto no tiene imágenes.</p>';
        }
    }

    uploadImageForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const response = await fetch('../controllers/products_controller.php?action=upload_image', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            await loadProductImages(imageProductIdInput.value);
            this.reset();
        } else {
            alert('Error al subir imagen: ' + result.message);
        }
    });

    document.getElementById('image-gallery').addEventListener('click', async function(e) {
        const productId = imageProductIdInput.value;
        if (e.target.classList.contains('set-primary-btn')) {
            const imageId = e.target.dataset.imageId;
            await fetch('../controllers/products_controller.php?action=set_primary', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_product: productId, id_product_image: imageId })
            });
            await loadProductImages(productId);
            location.reload(); // Recargar para ver el cambio en la tabla principal
        }
        if (e.target.closest('.delete-image-btn')) {
            const imageId = e.target.closest('.delete-image-btn').dataset.imageId;
            if (confirm('¿Seguro que desea eliminar esta imagen?')) {
                await fetch(`../controllers/products_controller.php?action=delete_image&id_image=${imageId}`, { method: 'DELETE' });
                await loadProductImages(productId);
            }
        }
    });

    function renderBatchRow(batch) {
        const row = document.createElement('tr');
        row.dataset.batchId = batch.id_batch;
        row.innerHTML = `
            <td>${batch.batch_number}</td>
            <td>${batch.stock}</td>
            <td>$${parseFloat(batch.purchase_price || 0).toFixed(2)}</td>
            <td>$${parseFloat(batch.sale_price || 0).toFixed(2)}</td>
            <td>$${parseFloat(batch.sale_price_b || 0).toFixed(2)}</td>
            <td>$${parseFloat(batch.sale_price_c || 0).toFixed(2)}</td>
            <td>${batch.expiration_date}</td>
            <td>
                <button class="btn btn-sm btn-outline-secondary edit-batch-btn" title="Editar"><i class="bi bi-pencil-square"></i></button>
                <button class="btn btn-sm btn-outline-danger delete-batch-btn" title="Eliminar"><i class="bi bi-trash"></i></button>
            </td>
        `;
        batchListTbody.appendChild(row);
    }

    function clearBatchForm() {
        batchForm.reset();
        batchIdInput.value = '';
        batchFormTitle.textContent = 'Añadir Nuevo Lote';
        batchFormStatus.innerHTML = '';
    }

    async function loadBatches(productId) {
        // Ajustar el colspan a 9 para las nuevas columnas
        batchListTbody.innerHTML = '<tr><td colspan="8">Cargando...</td></tr>'; // Corregido a 8 columnas
        const response = await fetch(`${BATCH_API_URL}?product_id=${productId}`);
        const result = await response.json();
        batchListTbody.innerHTML = '';
        if (result.success && result.data.length > 0) {
            result.data.forEach(batch => renderBatchRow(batch));
        } else {
            batchListTbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay lotes para este producto.</td></tr>'; // Corregido a 8 columnas
        }
    }

    async function openBatchModal(productId, productName) {
        batchModalTitle.textContent = productName;
        batchProductIdInput.value = productId;
        clearBatchForm();
        await loadBatches(productId);
        batchModal.show();
    }

    clearBatchFormBtn.addEventListener('click', clearBatchForm);

    // Enviar formulario de lote (Crear/Actualizar)
    batchForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        batchFormStatus.innerHTML = '<div class="alert alert-info">Guardando...</div>';

        const formData = new FormData(batchForm);
        const data = Object.fromEntries(formData.entries());
        const id = data.id_batch;

        const url = id ? `${BATCH_API_URL}?id=${id}` : BATCH_API_URL;
        const method = id ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            batchFormStatus.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
            // Actualizar UI
            await loadBatches(data.id_product);
            updateTotalStockInMainTable(data.id_product, result.total_stock);
            clearBatchForm();
        } else {
            batchFormStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    });

    // Editar y Eliminar Lote
    batchListTbody.addEventListener('click', async function(e) {
        const editBtn = e.target.closest('.edit-batch-btn');
        const deleteBtn = e.target.closest('.delete-batch-btn');
        if (!editBtn && !deleteBtn) return;

        const row = e.target.closest('tr');
        const batchId = row.dataset.batchId;

        if (editBtn) {
            const response = await fetch(`${BATCH_API_URL}?id=${batchId}`);
            const result = await response.json();
            if (result.success) {
                const batch = result.data;
                batchIdInput.value = batch.id_batch;
                document.getElementById('batchNumber').value = batch.batch_number;
                document.getElementById('batchStock').value = batch.stock;
                document.getElementById('batchPurchasePrice').value = batch.purchase_price;
                document.getElementById('batchSalePrice').value = batch.sale_price;
                document.getElementById('batchSalePrice2').value = batch.sale_price_2;
                document.getElementById('batchSalePrice3').value = batch.sale_price_3;
                document.getElementById('batchSalePrice4').value = batch.sale_price_4;
                document.getElementById('batchExpiration').value = batch.expiration_date;
                batchFormTitle.textContent = 'Editar Lote';
                document.getElementById('batchNumber').focus();
            }
        }

        if (deleteBtn) {
            if (confirm('¿Está seguro de que desea eliminar este lote? Solo se puede eliminar si el stock es 0.')) {
                const response = await fetch(`${BATCH_API_URL}?id=${batchId}`, { method: 'DELETE' });
                const result = await response.json();
                if (result.success) {
                    row.remove();
                    updateTotalStockInMainTable(batchProductIdInput.value, result.total_stock);
                    alert(result.message);
                } else {
                    alert('Error al eliminar: ' + result.message);
                }
            }
        }
    });

    // Función para abrir el modal de edición (refactorizada)
    async function openEditModal(productId) {
        document.getElementById('codeFeedbackEdit').innerHTML = '';
        document.getElementById('editProductCode').classList.remove('is-invalid');
        editStatusDiv.innerHTML = '';

        try {
            const response = await fetch(`../controllers/products_controller.php?id=${productId}`);
            const result = await response.json();
            if (result.success) {
                const product = result.data;
                editProductIdInput.value = product.id_product;
                document.getElementById('editProductName').value = product.name;
                document.getElementById('editProductCode').value = product.code;
                document.getElementById('editProductMedida').value = product.id_medida || '';
                document.getElementById('editProductLaboratorio').value = product.id_laboratorio || '';
                document.getElementById('editProductCategory').value = product.id_category || '';
                document.getElementById('editProductLocation').value = product.location || ''; // Añadido
            }
            // Abrir el modal DESPUÉS de cargar los datos
            editProductModal.show();
        } catch (error) {
            alert('Error al cargar los datos del producto.');
        }
    }

    function updateTotalStockInMainTable(productId, newTotalStock) {
        const productRow = mainTableBody.querySelector(`tr[data-product-id="${productId}"]`);
        if (productRow) {
            // La celda de stock es la 5ta (índice 5)
            productRow.cells[5].textContent = newTotalStock;
        }
    }

});
</script>

<?php
$content = ob_get_clean();
include 'template.php'; // template.php ahora está en la misma carpeta 'views'
?>