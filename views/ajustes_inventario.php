<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('manage_inventory_adjustments', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Ajustes de Inventario";
ob_start();
?>

<!-- Incluir CSS de Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Ajustes Manuales de Inventario</h1>
</div>

<div class="card">
    <div class="card-body">
        <form id="adjustmentForm">
            <div class="row">
                <!-- Selección de Producto -->
                <div class="col-md-6 mb-3">
                    <label for="product-select" class="form-label">1. Seleccione un Producto</label>
                    <select id="product-select" class="form-select" required></select>
                </div>

                <!-- Selección de Lote -->
                <div class="col-md-6 mb-3">
                    <label for="batch-select" class="form-label">2. Seleccione el Lote a Ajustar</label>
                    <select id="batch-select" name="id_batch" class="form-select" required disabled></select>
                </div>
            </div>

            <hr>

            <div class="row">
                <!-- Tipo de Ajuste -->
                <div class="col-md-4 mb-3">
                    <label for="adjustment-type" class="form-label">3. Tipo de Ajuste</label>
                    <select id="adjustment-type" name="adjustment_type" class="form-select" required>
                        <option value="" selected disabled>Seleccione...</option>
                        <option value="Entrada">Entrada (Aumentar Stock)</option>
                        <option value="Salida">Salida (Disminuir Stock)</option>
                    </select>
                </div>

                <!-- Cantidad -->
                <div class="col-md-2 mb-3">
                    <label for="quantity" class="form-label">4. Cantidad</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" required>
                </div>

                <!-- Motivo -->
                <div class="col-md-6 mb-3">
                    <label for="reason" class="form-label">5. Motivo del Ajuste</label>
                    <input type="text" id="reason" name="reason" class="form-control" placeholder="Ej: Conteo físico, Producto dañado, Vencimiento..." required>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary btn-lg">Guardar Ajuste</button>
            </div>
        </form>
        <div id="status-message" class="mt-3"></div>
    </div>
</div>

<!-- Incluir JS de jQuery y Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    const productSelect = $('#product-select');
    const batchSelect = $('#batch-select');
    const adjustmentForm = $('#adjustmentForm');
    const statusMessageEl = $('#status-message');

    // Inicializar Select2 para productos
    productSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'Escriba para buscar un producto...',
        ajax: {
            url: '../controllers/get_products_for_tpv.php', // Reutilizamos este endpoint
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                const uniqueProducts = data.data.reduce((acc, current) => {
                    if (!acc.find(item => item.id_product === current.id_product)) {
                        acc.push(current);
                    }
                    return acc;
                }, []);
                return {
                    results: uniqueProducts.map(p => ({ id: p.id_product, text: `${p.product_name} (${p.product_code})` }))
                };
            },
            cache: true
        }
    });

    // Cuando se selecciona un producto, cargar sus lotes
    productSelect.on('select2:select', function (e) {
        const productId = e.params.data.id;
        batchSelect.prop('disabled', true).html('<option>Cargando lotes...</option>');

        fetch(`../controllers/batches_controller.php?product_id=${productId}`)
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data.length > 0) {
                    batchSelect.html('<option value="" selected disabled>Seleccione un lote</option>');
                    result.data.forEach(batch => {
                        batchSelect.append(new Option(`Lote: ${batch.batch_number} (Stock: ${batch.stock}, Vence: ${batch.expiration_date})`, batch.id_batch));
                    });
                    batchSelect.prop('disabled', false);
                } else {
                    batchSelect.html('<option>No hay lotes para este producto</option>');
                }
            });
    });

    // Enviar el formulario de ajuste
    adjustmentForm.on('submit', function(e) {
        e.preventDefault();
        statusMessageEl.html('<div class="alert alert-info">Procesando ajuste...</div>');

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.id_product = productSelect.val(); // Añadir el id_product al payload

        fetch('../controllers/inventory_adjustments_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                statusMessageEl.html(`<div class="alert alert-success">${result.message}</div>`);
                // Limpiar formulario para un nuevo ajuste
                adjustmentForm[0].reset();
                productSelect.val(null).trigger('change');
                batchSelect.prop('disabled', true).html('');
            } else {
                statusMessageEl.html(`<div class="alert alert-danger">Error: ${result.message}</div>`);
            }
        })
        .catch(error => {
            statusMessageEl.html(`<div class="alert alert-danger">Error de conexión.</div>`);
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>