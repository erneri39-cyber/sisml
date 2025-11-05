<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Reporte General de Ventas";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reporte General de Ventas</h1>
</div>

<!-- Filtros -->
<div class="row g-3 align-items-center mb-4 p-3 border rounded bg-light">
    <div class="col-auto">
        <label for="startDate" class="col-form-label">Desde:</label>
    </div>
    <div class="col-auto">
        <input type="date" id="startDate" class="form-control">
    </div>
    <div class="col-auto">
        <label for="endDate" class="col-form-label">Hasta:</label>
    </div>
    <div class="col-auto">
        <input type="date" id="endDate" class="form-control">
    </div>
    <div class="col-auto">
        <label for="sellerFilter" class="col-form-label">Vendedor:</label>
    </div>
    <div class="col-auto">
        <select id="sellerFilter" class="form-select">
            <!-- Vendedores se cargarán aquí -->
        </select>
    </div>
    <div class="col-auto">
        <button id="generateReportBtn" class="btn btn-primary">Generar Reporte</button>
    </div>
    <div class="col-auto">
        <button id="exportCsvBtn" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Exportar CSV</button>
    </div>
</div>

<!-- Contenedor de Resultados -->
<div id="report-results" class="d-none">
    
    <!-- Resumen -->
    <div class="row mb-4 g-4">
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Total Ventas</h5>
                    <p class="card-text fs-4" id="total-sales-count">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Ingresos Brutos</h5>
                    <p class="card-text fs-4" id="total-gross-revenue">$0.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Total Descuentos</h5>
                    <p class="card-text fs-4" id="total-discounts">-$0.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Ingresos Netos</h5>
                    <p class="card-text fs-4" id="total-net-revenue">$0.00</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Ventas -->
    <h4 class="mt-5">Rendimiento de Ventas por Día</h4>
    <div class="card mb-4">
        <div class="card-body">
            <canvas id="salesChart" width="400" height="150"></canvas>
        </div>
    </div>
    <!-- Tabla de Detalles -->
    <h4 class="mt-5">Detalle de Ventas</h4>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>N° Venta</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Origen</th>
                    <th>Monto Bruto</th>
                    <th>Descuentos</th>
                    <th>Monto Neto</th>
                </tr>
            </thead>
            <tbody id="report-tbody">
                <!-- Las filas se insertarán dinámicamente aquí -->
            </tbody>
        </table>
    </div>
</div>

<div id="status-message" class="mt-3"></div>

<!-- Incluir Chart.js desde un CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateBtn = document.getElementById('generateReportBtn');
    const statusMessageEl = document.getElementById('status-message');
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    const reportResultsEl = document.getElementById('report-results');
    const sellerFilter = document.getElementById('sellerFilter');
    const chartCanvas = document.getElementById('salesChart');
    let salesChartInstance = null; // Variable para mantener la instancia del gráfico

    generateBtn.addEventListener('click', async function() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const sellerId = sellerFilter.value;

        if (!startDate || !endDate) {
            alert('Por favor, seleccione un rango de fechas completo.');
            return;
        }

        statusMessageEl.innerHTML = '<div class="alert alert-info">Generando reporte...</div>';
        reportResultsEl.classList.add('d-none');

        let url = `../controllers/sales_report_controller.php?start_date=${startDate}&end_date=${endDate}`;
        if (sellerId) {
            url += `&seller_id=${sellerId}`;
        }

        try {
            const response = await fetch(url);
            const result = await response.json();

            statusMessageEl.innerHTML = '';

            if (result.success) {
                const { summary, details, chartData } = result.data;

                document.getElementById('total-sales-count').textContent = summary.total_sales_count;
                document.getElementById('total-gross-revenue').textContent = `$${summary.total_gross_revenue.toFixed(2)}`;
                document.getElementById('total-discounts').textContent = `-$${summary.total_discounts_applied.toFixed(2)}`;
                document.getElementById('total-net-revenue').textContent = `$${summary.total_net_revenue.toFixed(2)}`;

                const tbody = document.getElementById('report-tbody');
                tbody.innerHTML = details.map(item => `
                    <tr>
                        <td>${item.id_sale}</td>
                        <td>${item.sale_date}</td>
                        <td>${item.client_name}</td>
                        <td>${item.user_name}</td>
                        <td><span class="badge bg-${item.sale_source === 'Directa' ? 'secondary' : 'info'}">${item.sale_source}</span></td>
                        <td>$${parseFloat(item.total_amount).toFixed(2)}</td>
                        <td class="text-danger">-$${parseFloat(item.total_discount).toFixed(2)}</td>
                        <td class="fw-bold">$${(item.total_amount - item.total_discount).toFixed(2)}</td>
                    </tr>
                `).join('');

                renderSalesChart(chartData);

                reportResultsEl.classList.remove('d-none');
            } else {
                statusMessageEl.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        } catch (error) {
            statusMessageEl.innerHTML = `<div class="alert alert-danger">Error al conectar con el servidor.</div>`;
        }
    });

    // Event listener para el botón de exportar a CSV
    exportCsvBtn.addEventListener('click', function() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const sellerId = sellerFilter.value;

        if (!startDate || !endDate) {
            alert('Por favor, seleccione un rango de fechas completo para exportar.');
            return;
        }

        let url = `../controllers/export_sales_csv_controller.php?start_date=${startDate}&end_date=${endDate}`;
        if (sellerId) {
            url += `&seller_id=${sellerId}`;
        }

        // Abrir una nueva ventana/pestaña para descargar el CSV
        window.open(url, '_blank');
    });

    function renderSalesChart(data) {
        // Si ya existe un gráfico, lo destruimos antes de crear uno nuevo
        if (salesChartInstance) {
            salesChartInstance.destroy();
        }

        const labels = data.map(item => new Date(item.sale_day + 'T00:00:00').toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }));
        const chartValues = data.map(item => parseFloat(item.net_revenue_day));
        const discountValues = data.map(item => parseFloat(item.total_discount_day));

        const ctx = chartCanvas.getContext('2d');
        salesChartInstance = new Chart(ctx, {
            type: 'line', // Cambiado de 'bar' a 'line'
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Ingresos Netos ($)',
                        data: chartValues,
                        backgroundColor: 'rgba(0, 123, 255, 0.2)', // Azul
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.1
                    },
                    {
                        label: 'Descuentos ($)',
                        data: discountValues,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)', // Rojo
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '$' + value; }
                        }
                    }
                }
            }
        });
    }

    // Cargar vendedores en el filtro
    async function loadSellers() {
        try {
            const response = await fetch('../controllers/users_list_controller.php');
            const result = await response.json();
            if (result.success) {
                sellerFilter.innerHTML = '<option value="">Todos los Vendedores</option>';
                result.data.forEach(seller => {
                    sellerFilter.innerHTML += `<option value="${seller.id_user}">${seller.name}</option>`;
                });
            }
        } catch (error) {
            console.error('Error al cargar vendedores:', error);
        }
    }

    loadSellers();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>