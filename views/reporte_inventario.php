<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_inventory_report', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Reporte de Valor de Inventario";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reporte de Valor de Inventario</h1>
</div>

<!-- Filtros -->
<div class="row g-3 align-items-center mb-4 p-3 border rounded bg-light">
    <div class="col-md-4">
        <label for="categoryFilter" class="form-label">Categoría:</label>
        <select id="categoryFilter" class="form-select"></select>
    </div>
    <div class="col-md-4">
        <label for="labFilter" class="form-label">Laboratorio:</label>
        <select id="labFilter" class="form-select"></select>
    </div>
    <div class="col-md-4 d-flex align-items-end gap-2">
        <button id="generateReportBtn" class="btn btn-primary me-2">Generar Reporte</button>
        <button id="clearFiltersBtn" class="btn btn-secondary">Limpiar</button>
        <button id="exportCsvBtn" class="btn btn-success" title="Exportar a CSV"><i class="bi bi-file-earmark-spreadsheet"></i></button>
    </div>
</div>

<!-- Contenedor de Resultados -->
<div id="report-results" class="d-none">
    <!-- Resumen -->
    <div class="row mb-4 g-4">
        <div class="col-md-4">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Valor Total (Costo)</h5>
                    <p class="card-text fs-4" id="total-cost-value">$0.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Valor Total (Venta Detalle)</h5>
                    <p class="card-text fs-4" id="total-sale-value">$0.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-dark bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Total Unidades en Stock</h5>
                    <p class="card-text fs-4" id="total-units">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Detalles -->
    <h4 class="mt-5">Detalle del Inventario</h4>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Laboratorio</th>
                    <th>Stock Total</th>
                    <th>Valor Total (Costo)</th>
                    <th>Valor Total (Venta)</th>
                </tr>
            </thead>
            <tbody id="report-tbody">
                <!-- Las filas se insertarán dinámicamente aquí -->
            </tbody>
        </table>
    </div>
</div>

<div id="status-message" class="mt-3"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateBtn = document.getElementById('generateReportBtn');
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const statusMessageEl = document.getElementById('status-message');
    const reportResultsEl = document.getElementById('report-results');
    const categoryFilter = document.getElementById('categoryFilter');
    const labFilter = document.getElementById('labFilter');

    // Cargar filtros
    async function loadFilters() {
        // Categorías
        const catResponse = await fetch('../controllers/categories_controller.php');
        const catResult = await catResponse.json();
        if (catResult.success) {
            categoryFilter.innerHTML = '<option value="">Todas las Categorías</option>';
            catResult.data.forEach(cat => {
                categoryFilter.innerHTML += `<option value="${cat.id_category}">${cat.name}</option>`;
            });
        }

        // Laboratorios
        const labResponse = await fetch('../controllers/laboratorios_controller.php');
        const labResult = await labResponse.json();
        if (labResult.success) {
            labFilter.innerHTML = '<option value="">Todos los Laboratorios</option>';
            labResult.data.forEach(lab => {
                labFilter.innerHTML += `<option value="${lab.id_laboratorio}">${lab.nombre}</option>`;
            });
        }
    }

    generateBtn.addEventListener('click', async function() {
        statusMessageEl.innerHTML = '<div class="alert alert-info">Generando reporte...</div>';
        reportResultsEl.classList.add('d-none');

        // CORRECCIÓN: Construir la URL como un string para manejar correctamente la ruta relativa.
        let url = '../controllers/inventory_report_controller.php';
        const params = new URLSearchParams();
        if (categoryFilter.value) params.append('category_id', categoryFilter.value);
        if (labFilter.value) params.append('lab_id', labFilter.value);

        try {
            const response = await fetch(`${url}?${params.toString()}`);
            const result = await response.json();
            statusMessageEl.innerHTML = '';

            if (result.success) {
                const { summary, details } = result.data;

                document.getElementById('total-cost-value').textContent = `$${summary.total_cost.toFixed(2)}`;
                document.getElementById('total-sale-value').textContent = `$${summary.total_sale.toFixed(2)}`;
                document.getElementById('total-units').textContent = summary.total_units;

                const tbody = document.getElementById('report-tbody');
                tbody.innerHTML = '';
                if (details.length > 0) {
                    tbody.innerHTML = details.map(item => `
                        <tr>
                            <td>${item.code}</td>
                            <td>${item.product_name}</td>
                            <td>${item.category_name || 'N/A'}</td>
                            <td>${item.laboratory_name || 'N/A'}</td>
                            <td>${item.total_stock}</td>
                            <td>$${parseFloat(item.total_cost_value).toFixed(2)}</td>
                            <td>$${parseFloat(item.total_sale_value).toFixed(2)}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">No se encontraron productos con los filtros seleccionados.</td></tr>';
                }

                reportResultsEl.classList.remove('d-none');
            } else {
                statusMessageEl.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        } catch (error) {
            statusMessageEl.innerHTML = `<div class="alert alert-danger">Error al conectar con el servidor.</div>`;
        }
    });

    exportCsvBtn.addEventListener('click', function() {
        let url = new URL('../controllers/export_inventory_csv.php', window.location.origin);
        const categoryId = categoryFilter.value;
        const labId = labFilter.value;

        if (categoryId) url.searchParams.append('category_id', categoryId);
        if (labId) url.searchParams.append('lab_id', labId);

        window.open(url, '_blank');
    });

    clearFiltersBtn.addEventListener('click', () => {
        categoryFilter.value = '';
        labFilter.value = '';
        reportResultsEl.classList.add('d-none');
        statusMessageEl.innerHTML = '';
    });

    loadFilters();
    generateBtn.click(); // Generar reporte inicial al cargar la página
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>