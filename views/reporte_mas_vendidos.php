<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_bestsellers_report', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Reporte de Productos Más Vendidos";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reporte de Productos Más Vendidos</h1>
</div>

<!-- Filtros -->
<div class="row g-3 align-items-center mb-4 p-3 border rounded bg-light">
    <div class="col-md-4">
        <label for="startDate" class="form-label">Desde:</label>
        <input type="date" id="startDate" class="form-control">
    </div>
    <div class="col-md-4">
        <label for="endDate" class="form-label">Hasta:</label>
        <input type="date" id="endDate" class="form-control">
    </div>
    <div class="col-md-4 d-flex align-items-end gap-2">
        <button id="generateReportBtn" class="btn btn-primary">Generar Reporte</button>
        <button id="exportCsvBtn" class="btn btn-success" title="Exportar a CSV"><i class="bi bi-file-earmark-spreadsheet"></i></button>
    </div>
</div>

<!-- Tabla de Resultados -->
<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Ranking</th>
                <th>Código Producto</th>
                <th>Producto</th>
                <th>Cantidad Vendida</th>
                <th>Ingresos Generados</th>
            </tr>
        </thead>
        <tbody id="report-tbody">
            <!-- Las filas se insertarán dinámicamente aquí -->
        </tbody>
    </table>
</div>

<div id="status-message" class="mt-3"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateBtn = document.getElementById('generateReportBtn');
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    const tbody = document.getElementById('report-tbody');
    const statusMessageEl = document.getElementById('status-message');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    // Establecer fechas por defecto (mes actual)
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    startDateInput.value = firstDay.toISOString().split('T')[0];
    endDateInput.value = today.toISOString().split('T')[0];

    async function loadReport() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!startDate || !endDate) {
            alert('Por favor, seleccione un rango de fechas completo.');
            return;
        }

        statusMessageEl.innerHTML = '<div class="alert alert-info">Generando reporte...</div>';
        tbody.innerHTML = '';

        try {
            const url = `../controllers/bestsellers_report_controller.php?start_date=${startDate}&end_date=${endDate}`;
            const response = await fetch(url);
            const result = await response.json();
            statusMessageEl.innerHTML = '';

            if (result.success) {
                if (result.data.length > 0) {
                    tbody.innerHTML = result.data.map((item, index) => `
                        <tr>
                            <td><span class="badge bg-primary">${index + 1}</span></td>
                            <td>${item.product_code}</td>
                            <td>${item.product_name}</td>
                            <td class="fw-bold">${item.total_quantity_sold}</td>
                            <td>$${parseFloat(item.total_revenue).toFixed(2)}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No se encontraron ventas en el rango de fechas seleccionado.</td></tr>';
                }
            } else {
                statusMessageEl.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        } catch (error) {
            statusMessageEl.innerHTML = `<div class="alert alert-danger">Error al conectar con el servidor.</div>`;
        }
    }

    generateBtn.addEventListener('click', loadReport);

    exportCsvBtn.addEventListener('click', function() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!startDate || !endDate) {
            alert('Por favor, seleccione un rango de fechas para exportar.');
            return;
        }

        const url = `../controllers/export_bestsellers_csv.php?start_date=${startDate}&end_date=${endDate}`;
        window.open(url, '_blank');
    });

    // Cargar el reporte inicial al cargar la página
    loadReport();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>