<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_adjustment_report', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Historial de Ajustes de Inventario";
ob_start();
?>

<!-- Incluir CSS de Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Historial de Ajustes de Inventario</h1>
</div>

<!-- Filtros -->
<div class="row g-3 align-items-center mb-4 p-3 border rounded bg-light">
    <div class="col-md-4">
        <label for="productFilter" class="form-label">Producto:</label>
        <select id="productFilter" class="form-select"></select>
    </div>
    <div class="col-md-3">
        <label for="startDate" class="form-label">Desde:</label>
        <input type="date" id="startDate" class="form-control">
    </div>
    <div class="col-md-3">
        <label for="endDate" class="form-label">Hasta:</label>
        <input type="date" id="endDate" class="form-control">
    </div>
    <div class="col-md-2 d-flex align-items-end gap-2">
        <button id="generateReportBtn" class="btn btn-primary me-2">Filtrar</button>
        <button id="clearFiltersBtn" class="btn btn-secondary">Limpiar</button>
        <button id="exportCsvBtn" class="btn btn-success" title="Exportar a CSV"><i class="bi bi-file-earmark-spreadsheet"></i></button>
    </div>
</div>

<!-- Tabla de Resultados -->
<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Producto</th>
                <th>Nº Lote</th>
                <th>Tipo de Ajuste</th>
                <th>Cantidad</th>
                <th>Motivo</th>
                <th>Realizado por</th>
            </tr>
        </thead>
        <tbody id="report-tbody">
            <!-- Las filas se insertarán dinámicamente aquí -->
        </tbody>
    </table>
</div>

<div id="status-message" class="mt-3"></div>

<!-- Incluir JS de jQuery y Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    const generateBtn = $('#generateReportBtn');
    const exportCsvBtn = $('#exportCsvBtn');
    const clearFiltersBtn = $('#clearFiltersBtn');
    const statusMessageEl = $('#status-message');
    const productFilter = $('#productFilter');
    const startDateInput = $('#startDate');
    const endDateInput = $('#endDate');
    const tbody = $('#report-tbody');

    // Inicializar Select2 para productos
    productFilter.select2({
        theme: 'bootstrap-5',
        placeholder: 'Todos los productos',
        allowClear: true,
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

    async function loadReport() {
        statusMessageEl.html('<div class="alert alert-info">Generando reporte...</div>');
        tbody.html('');

        let url = new URL('../controllers/adjustment_report_controller.php', window.location.origin);
        if (productFilter.val()) url.searchParams.append('product_id', productFilter.val());
        if (startDateInput.val()) url.searchParams.append('start_date', startDateInput.val());
        if (endDateInput.val()) url.searchParams.append('end_date', endDateInput.val());

        try {
            const response = await fetch(url);
            const result = await response.json();
            statusMessageEl.html('');

            if (result.success) {
                if (result.data.length > 0) {
                    tbody.html(result.data.map(item => `
                        <tr>
                            <td>${new Date(item.adjustment_date).toLocaleString('es-ES')}</td>
                            <td>${item.product_name}</td>
                            <td>${item.batch_number}</td>
                            <td><span class="badge bg-${item.adjustment_type === 'Entrada' ? 'success' : 'danger'}">${item.adjustment_type}</span></td>
                            <td>${item.quantity}</td>
                            <td>${item.reason}</td>
                            <td>${item.user_name}</td>
                        </tr>
                    `).join(''));
                } else {
                    tbody.html('<tr><td colspan="7" class="text-center">No se encontraron ajustes con los filtros seleccionados.</td></tr>');
                }
            } else {
                statusMessageEl.html(`<div class="alert alert-danger">${result.message}</div>`);
            }
        } catch (error) {
            statusMessageEl.html(`<div class="alert alert-danger">Error al conectar con el servidor.</div>`);
        }
    }

    generateBtn.on('click', loadReport);

    exportCsvBtn.on('click', function() {
        let url = new URL('../controllers/export_adjustments_csv.php', window.location.origin);
        const productId = productFilter.val();
        const startDate = startDateInput.val();
        const endDate = endDateInput.val();

        if (productId) url.searchParams.append('product_id', productId);
        if (startDate) url.searchParams.append('start_date', startDate);
        if (endDate) url.searchParams.append('end_date', endDate);
        window.open(url, '_blank');
    });

    clearFiltersBtn.on('click', () => {
        productFilter.val(null).trigger('change');
        startDateInput.val('');
        endDateInput.val('');
        loadReport();
    });

    // Cargar reporte inicial sin filtros
    loadReport();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>