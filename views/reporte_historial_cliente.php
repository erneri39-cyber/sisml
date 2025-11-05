<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_client_purchase_history', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Historial de Compras por Cliente";
ob_start();
?>

<!-- Incluir CSS de Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Historial de Compras por Cliente</h1>
</div>

<!-- Filtros -->
<div class="row g-3 align-items-center mb-4 p-3 border rounded bg-light">
    <div class="col-md-4">
        <label for="clientFilter" class="form-label">Seleccione un Cliente:</label>
        <select id="clientFilter" class="form-select"></select>
    </div>
    <div class="col-md-3">
        <label for="startDate" class="form-label">Desde:</label>
        <input type="date" id="startDate" class="form-control" disabled>
    </div>
    <div class="col-md-3">
        <label for="endDate" class="form-label">Hasta:</label>
        <input type="date" id="endDate" class="form-control" disabled>
    </div>
    <div class="col-md-2 d-flex align-items-end justify-content-end gap-2">
        <button id="filterBtn" class="btn btn-primary" title="Filtrar" disabled><i class="bi bi-search"></i></button>
        <button id="exportCsvBtn" class="btn btn-success" title="Exportar a CSV" disabled><i class="bi bi-file-earmark-spreadsheet"></i></button>
    </div>
</div>

<!-- Tabla de Resultados -->
<div id="report-container" class="d-none">
    <h4 id="client-name-title"></h4>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>N° Venta</th>
                    <th>Fecha y Hora</th>
                    <th>Vendedor</th>
                    <th>Monto Bruto</th>
                    <th>Descuentos</th>
                    <th>Monto Neto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="report-tbody">
                <!-- Las filas se insertarán dinámicamente aquí -->
            </tbody>
        </table>
    </div>
    <!-- Contenedor de Paginación -->
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination" id="pagination-container">
            </ul>
        </nav>
    </div>
</div>

<div id="status-message" class="mt-3"></div>

<!-- Modal para Ver Detalles de la Venta -->
<div class="modal fade" id="saleDetailsModal" tabindex="-1" aria-labelledby="saleDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saleDetailsModalLabel">Detalles de la Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="sale-details-content">
                <!-- El contenido de los detalles se cargará aquí -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Incluir JS de jQuery y Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    const clientFilter = $('#clientFilter');
    const reportContainer = $('#report-container');
    const clientNameTitle = $('#client-name-title');
    const tbody = $('#report-tbody');
    const statusMessageEl = $('#status-message');
    const paginationContainer = $('#pagination-container');
    const startDateInput = $('#startDate');
    const endDateInput = $('#endDate');
    const filterBtn = $('#filterBtn');
    const exportCsvBtn = $('#exportCsvBtn');
    const detailsModal = new bootstrap.Modal(document.getElementById('saleDetailsModal'));
    const detailsModalBody = $('#sale-details-content');
    const detailsModalLabel = $('#saleDetailsModalLabel');

    // Inicializar Select2 para clientes
    clientFilter.select2({
        theme: 'bootstrap-5',
        placeholder: 'Escriba para buscar un cliente...',
        ajax: {
            url: '../controllers/clients_controller.php',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.data.map(c => ({ id: c.id_person, text: c.name }))
                };
            },
            cache: true
        }
    });

    // Cargar historial al seleccionar un cliente
    clientFilter.on('select2:select', function(e) {
        const client = e.params.data;
        clientNameTitle.text(`Historial de: ${client.text}`);
        // Habilitar filtros y botones
        startDateInput.prop('disabled', false);
        endDateInput.prop('disabled', false);
        filterBtn.prop('disabled', false);
        exportCsvBtn.prop('disabled', false);
        // Cargar historial inicial para la página 1
        loadHistory(client.id, '', '', 1);
    });

    filterBtn.on('click', function() {
        const clientId = clientFilter.val();
        if (clientId) {
            // Al filtrar, siempre volver a la página 1
            loadHistory(clientId, startDateInput.val(), endDateInput.val(), 1);
        }
    });

    async function loadHistory(clientId, startDate, endDate, page) {
        statusMessageEl.html('<div class="alert alert-info">Cargando historial...</div>');
        reportContainer.removeClass('d-none');
        tbody.html('');
        paginationContainer.html(''); // Limpiar paginación

        try {
            let url = `../controllers/client_purchase_history_controller.php?action=get_sales&client_id=${clientId}&page=${page}`;
            if (startDate && endDate) {
                url += `&start_date=${startDate}&end_date=${endDate}`;
            }
            const response = await fetch(url);
            const result = await response.json();
            statusMessageEl.html('');

            if (result.success && result.data && result.data.length > 0) {
                tbody.html(result.data.map(sale => {
                    const netAmount = parseFloat(sale.total_amount) - parseFloat(sale.total_discount);
                    return `
                        <tr data-sale-id="${sale.id_sale}">
                            <td>${sale.id_sale}</td>
                            <td>${new Date(sale.sale_date).toLocaleString('es-ES')}</td>
                            <td>${sale.user_name}</td>
                            <td>$${parseFloat(sale.total_amount).toFixed(2)}</td>
                            <td class="text-danger">-$${parseFloat(sale.total_discount).toFixed(2)}</td>
                            <td class="fw-bold">$${netAmount.toFixed(2)}</td>
                            <td><button class="btn btn-sm btn-outline-primary view-details-btn">Ver Detalles</button></td>
                        </tr>
                    `;
                }).join(''));
                renderPagination(result.pagination);
            } else {
                tbody.html('<tr><td colspan="7" class="text-center">Este cliente no tiene compras registradas.</td></tr>');
            }
        } catch (error) {
            statusMessageEl.html('<div class="alert alert-danger">Error al cargar el historial.</div>');
        }
    }

    function renderPagination(paginationData) {
        if (!paginationData || paginationData.total_pages <= 1) {
            paginationContainer.html('');
            return;
        }

        const { current_page, total_pages } = paginationData;
        let paginationHtml = '';

        // Botón "Anterior"
        paginationHtml += `<li class="page-item ${current_page === 1 ? 'disabled' : ''}">
                               <a class="page-link" href="#" data-page="${current_page - 1}">Anterior</a>
                           </li>`;

        // Botones de páginas
        for (let i = 1; i <= total_pages; i++) {
            paginationHtml += `<li class="page-item ${i === current_page ? 'active' : ''}">
                                   <a class="page-link" href="#" data-page="${i}">${i}</a>
                               </li>`;
        }

        // Botón "Siguiente"
        paginationHtml += `<li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
                               <a class="page-link" href="#" data-page="${current_page + 1}">Siguiente</a>
                           </li>`;

        paginationContainer.html(paginationHtml);
    }

    paginationContainer.on('click', 'a.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        loadHistory(clientFilter.val(), startDateInput.val(), endDateInput.val(), page);
    }

    // Exportar a CSV
    exportCsvBtn.on('click', function() {
        const clientId = clientFilter.val();
        if (!clientId) {
            alert('Por favor, seleccione un cliente primero.');
            return;
        }
        
        let url = `../controllers/export_client_history_csv.php?client_id=${clientId}`;
        const startDate = startDateInput.val();
        const endDate = endDateInput.val();
        if (startDate && endDate) {
            url += `&start_date=${startDate}&end_date=${endDate}`;
        }
        window.open(url, '_blank');
    });

    // Ver detalles de una venta
    tbody.on('click', '.view-details-btn', async function() {
        const saleId = $(this).closest('tr').data('sale-id');
        detailsModalLabel.text(`Detalles de la Venta #${saleId}`);
        detailsModalBody.html('<p class="text-center">Cargando...</p>');
        detailsModal.show();

        const response = await fetch(`../controllers/client_purchase_history_controller.php?action=get_sale_details&sale_id=${saleId}`);
        const result = await response.json();

        if (result.success) {
            let tableHtml = '<table class="table table-bordered"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Descuento</th><th>Subtotal</th></tr></thead><tbody>';
            let total = 0;
            result.data.forEach(item => {
                const subtotal = (item.quantity * item.sale_price_applied) - item.discount;
                total += subtotal;
                tableHtml += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>$${parseFloat(item.sale_price_applied).toFixed(2)}</td>
                        <td class="text-danger">-$${parseFloat(item.discount).toFixed(2)}</td>
                        <td>$${subtotal.toFixed(2)}</td>
                    </tr>`;
            });
            tableHtml += `</tbody><tfoot><tr><th colspan="4" class="text-end">Total:</th><th class="fw-bold">$${total.toFixed(2)}</th></tr></tfoot></table>`;
            detailsModalBody.html(tableHtml);
        } else {
            detailsModalBody.html('<div class="alert alert-danger">No se pudieron cargar los detalles.</div>');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>