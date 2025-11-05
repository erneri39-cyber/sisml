<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Reporte de Conversión de Cotizaciones";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reporte de Conversión de Cotizaciones a Ventas</h1>
</div>

<!-- Filtros -->
<div class="row g-3 align-items-center mb-4 p-3 border rounded bg-light">
    <div class="col-auto">
        <label for="startDate" class="col-form-label">Fecha de Venta (Desde):</label>
    </div>
    <div class="col-auto">
        <input type="date" id="startDate" class="form-control">
    </div>
    <div class="col-auto">
        <label for="endDate" class="col-form-label">Fecha de Venta (Hasta):</label>
    </div>
    <div class="col-auto">
        <input type="date" id="endDate" class="form-control">
    </div>
    <div class="col-auto">
        <button id="generateReportBtn" class="btn btn-primary">Generar Reporte</button>
    </div>
</div>

<!-- Contenedor de Resultados -->
<div id="report-results" class="d-none">
    
    <!-- Resumen -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Cotizado</h5>
                    <p class="card-text fs-4" id="total-quoted">$0.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Total Vendido</h5>
                    <p class="card-text fs-4" id="total-sold">$0.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-dark bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Tasa de Conversión (Monto)</h5>
                    <p class="card-text fs-4" id="conversion-rate">0.00%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Detalles -->
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>N° Cotización</th>
                    <th>N° Venta</th>
                    <th>Cliente</th>
                    <th>Fecha Cotización</th>
                    <th>Fecha Venta</th>
                    <th>Monto Cotizado</th>
                    <th>Monto Vendido</th>
                    <th>Descuentos</th>
                    <th>Diferencia</th>
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
    const statusMessageEl = document.getElementById('status-message');
    const reportResultsEl = document.getElementById('report-results');

    generateBtn.addEventListener('click', async function() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            alert('Por favor, seleccione un rango de fechas completo.');
            return;
        }

        statusMessageEl.innerHTML = '<div class="alert alert-info">Generando reporte...</div>';
        reportResultsEl.classList.add('d-none');

        const url = `../controllers/conversion_report_controller.php?start_date=${startDate}&end_date=${endDate}`;

        try {
            const response = await fetch(url);
            const result = await response.json();

            statusMessageEl.innerHTML = '';

            if (result.success) {
                const summary = result.data.summary;
                const details = result.data.details;

                document.getElementById('total-quoted').textContent = `$${summary.total_quoted.toFixed(2)}`;
                document.getElementById('total-sold').textContent = `$${summary.total_sold.toFixed(2)}`;
                document.getElementById('conversion-rate').textContent = `${summary.conversion_rate.toFixed(2)}%`;

                const tbody = document.getElementById('report-tbody');
                tbody.innerHTML = '';
                details.forEach(item => {
                    const difference = item.sale_amount - item.quote_amount;
                    const diffClass = difference > 0 ? 'text-success' : (difference < 0 ? 'text-danger' : '');
                    tbody.innerHTML += `<tr>
                        <td>${item.id_quotation}</td>
                        <td>${item.id_sale}</td>
                        <td>${item.client_name}</td>
                        <td>${item.quotation_date}</td>
                        <td>${item.sale_date}</td>
                        <td>$${parseFloat(item.quote_amount).toFixed(2)}</td>
                        <td>$${parseFloat(item.sale_amount).toFixed(2)}</td>
                        <td class="text-danger">-$${parseFloat(item.total_discount).toFixed(2)}</td>
                        <td class="${diffClass}">$${difference.toFixed(2)}</td>
                    </tr>`;
                });

                reportResultsEl.classList.remove('d-none');
            } else {
                statusMessageEl.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        } catch (error) {
            statusMessageEl.innerHTML = `<div class="alert alert-danger">Error al conectar con el servidor.</div>`;
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>