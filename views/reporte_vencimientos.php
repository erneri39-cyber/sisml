<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_expiration_report', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Reporte de Próximos Vencimientos";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reporte de Productos Próximos a Vencer (Próximos 90 días)</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-success" id="exportCsvBtn">
            <i class="bi bi-file-earmark-spreadsheet"></i> Exportar a CSV
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Productos con fecha de vencimiento cercana en la sucursal actual.
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Código Producto</th>
                        <th>Producto</th>
                        <th>Laboratorio</th>
                        <th>Nº Lote</th>
                        <th>Stock en Lote</th>
                        <th>Fecha de Vencimiento</th>
                        <th>Días para Vencer</th>
                    </tr>
                </thead>
                <tbody id="report-tbody">
                    <!-- Las filas se insertarán dinámicamente aquí -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="status-message" class="mt-3"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('report-tbody');
    const statusMessageEl = document.getElementById('status-message');
    const exportCsvBtn = document.getElementById('exportCsvBtn');

    async function loadReport() {
        statusMessageEl.innerHTML = '<div class="alert alert-info">Generando reporte...</div>';
        tbody.innerHTML = '';

        try {
            const response = await fetch('../controllers/expiration_report_controller.php');
            const result = await response.json();
            statusMessageEl.innerHTML = '';

            if (result.success) {
                if (result.data.length > 0) {
                    result.data.forEach(item => {
                        const expirationDate = new Date(item.expiration_date + 'T00:00:00');
                        const today = new Date();
                        today.setHours(0, 0, 0, 0); // Normalizar la fecha de hoy
                        
                        const diffTime = expirationDate - today;
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                        let badgeClass = 'bg-success';
                        if (diffDays <= 30) {
                            badgeClass = 'bg-danger';
                        } else if (diffDays <= 60) {
                            badgeClass = 'bg-warning text-dark';
                        }

                        tbody.innerHTML += `
                            <tr>
                                <td>${item.product_code}</td>
                                <td>${item.product_name}</td>
                                <td>${item.laboratory_name || 'N/A'}</td>
                                <td>${item.batch_number}</td>
                                <td>${item.stock}</td>
                                <td>${new Date(item.expiration_date + 'T00:00:00').toLocaleDateString('es-ES')}</td>
                                <td><span class="badge ${badgeClass}">${diffDays} días</span></td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">No se encontraron productos próximos a vencer.</td></tr>';
                }
            } else {
                statusMessageEl.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        } catch (error) {
            statusMessageEl.innerHTML = `<div class="alert alert-danger">Error al conectar con el servidor.</div>`;
        }
    }

    // Event listener para el botón de exportar
    exportCsvBtn.addEventListener('click', function() {
        window.open('../controllers/export_expirations_csv.php', '_blank');
    });

    loadReport();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>