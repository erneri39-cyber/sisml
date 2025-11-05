<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Listado de Cotizaciones";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Listado de Cotizaciones</h1>
</div>

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
        <label for="statusFilter" class="col-form-label">Estado:</label>
    </div>
    <div class="col-auto">
        <select id="statusFilter" class="form-select">
            <option value="">Todos</option>
            <option value="Pendiente">Pendiente</option>
            <option value="Aceptada">Aceptada</option>
            <option value="Cancelada">Cancelada</option>
        </select>
    </div>
    <div class="col-auto">
        <label for="clientNameFilter" class="col-form-label">Cliente:</label>
    </div>
    <div class="col-auto">
        <input type="text" id="clientNameFilter" class="form-control" placeholder="Nombre del cliente...">
    </div>
    <div class="col-auto">
        <button id="filterBtn" class="btn btn-primary">Filtrar</button>
        <button id="clearFilterBtn" class="btn btn-secondary">Limpiar</button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>N° Cotización</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Sucursal</th>
                <th>Creado por</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="quotes-tbody">
            <!-- Las filas se insertarán dinámicamente aquí -->
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-4">
    <nav aria-label="Page navigation">
        <ul class="pagination" id="pagination-container">
            <!-- Los controles de paginación se insertarán aquí -->
        </ul>
    </table>
</div>

<!-- Modal para Ver Detalles de la Cotización -->
<div class="modal fade" id="quoteDetailsModal" tabindex="-1" aria-labelledby="quoteDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quoteDetailsModalLabel">Detalles de la Cotización</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="quote-details-content">
                    <!-- El contenido de los detalles se cargará aquí -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('quotes-tbody');
    const listApiUrl = '../controllers/list_quotes_controller.php';
    const detailsApiUrl = '../controllers/quote_details_controller.php';

    // Inicializar el modal de Bootstrap
    const detailsModalEl = document.getElementById('quoteDetailsModal');
    const detailsModal = new bootstrap.Modal(detailsModalEl);
    const modalTitle = document.getElementById('quoteDetailsModalLabel');
    const modalBody = document.getElementById('quote-details-content');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const statusFilterInput = document.getElementById('statusFilter');
    const clientNameFilterInput = document.getElementById('clientNameFilter');
    const filterBtn = document.getElementById('filterBtn');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    const paginationContainer = document.getElementById('pagination-container');
    let currentPage = 1;

    /**
     * Carga la lista principal de cotizaciones con filtros opcionales.
     */
    async function loadQuotes(page = 1, startDate = '', endDate = '', status = '', clientName = '') {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Cargando...</td></tr>'; // Aumentado a 8 columnas
        currentPage = page;
        let url = new URL(listApiUrl, window.location.origin);
        if (startDate && endDate) {
            url.searchParams.append('start_date', startDate);
            url.searchParams.append('end_date', endDate);
        }
        if (status) {
            url.searchParams.append('status', status);
        }
        if (clientName) {
            url.searchParams.append('client_name', clientName);
        }
        url.searchParams.append('page', page);

        try {
            const response = await fetch(url);
            const result = await response.json();

            tbody.innerHTML = ''; // Limpiar la tabla

            if (result.success && result.data.length > 0) {
                result.data.forEach(quote => {
                    const totalFormatted = parseFloat(quote.total_amount).toFixed(2); // Corregido: 'Vigente' ahora es 'Pendiente'
                    const statusBadge = quote.status === 'Pendiente' 
                        ? `<span class="badge bg-success">${quote.status}</span>`
                        : quote.status === 'Aceptada' || quote.status === 'Venta Generada'
                        ? `<span class="badge bg-info text-dark">${quote.status}</span>`
                        : quote.status === 'Cancelada'
                        ? `<span class="badge bg-secondary">${quote.status}</span>`
                        : `<span class="badge bg-secondary">${quote.status}</span>`;

                    tbody.innerHTML += `
                        <tr data-id="${quote.id_quotation}">
                            <td>${quote.id_quotation}</td>
                            <td>${quote.client_name}</td>
                            <td>${quote.quotation_date}</td>
                            <td>$${totalFormatted}</td>
                            <td>${quote.branch_name}</td>
                            <td>${quote.user_name}</td>
                            <td>${statusBadge}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary view-btn" title="Detalles">Detalles</button>
                                <a href="../controllers/download_quote_pdf.php?id=${quote.id_quotation}" target="_blank" class="btn btn-sm btn-outline-danger" title="Descargar PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                ${quote.status === 'Pendiente' ? `<a href="tpv.php?from_quote=${quote.id_quotation}" class="btn btn-sm btn-outline-success" title="Convertir a Venta"><i class="bi bi-cart-check"></i></a>` : ''}
                                ${quote.status === 'Cancelada' ? `<a href="cotizaciones.php?requote_id=${quote.id_quotation}" class="btn btn-sm btn-outline-warning" title="Re-cotizar con precios actuales"><i class="bi bi-arrow-clockwise"></i></a>` : ''}
                            </td>
                        </tr>
                    `;
                });
            } else if (result.success) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No se encontraron cotizaciones.</td></tr>';
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Error: ${result.message}</td></tr>`;
            }
        } catch (error) {
            console.error(error);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error al cargar los datos.</td></tr>';
        }
    }

    /**
     * Maneja el evento de clic en la tabla para ver los detalles.
     */
    tbody.addEventListener('click', async function(e) {
        const viewBtn = e.target.closest('.view-btn');
        if (!viewBtn) return;

        const row = viewBtn.closest('tr');
        const quoteId = row.dataset.id;

        // Mostrar estado de carga en el modal
        modalTitle.textContent = `Detalles de la Cotización #${quoteId}`;
        modalBody.innerHTML = '<p class="text-center">Cargando detalles...</p>';
        detailsModal.show();

        try {
            const response = await fetch(`${detailsApiUrl}?id=${quoteId}`);
            const result = await response.json();

            if (result.success) {
                renderQuoteDetails(result.data);
            } else {
                modalBody.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        } catch (error) {
            modalBody.innerHTML = '<div class="alert alert-danger">Error al cargar los detalles.</div>';
        }
    });

    /**
     * Renderiza los detalles de la cotización dentro del modal.
     * @param {Array} details - Un array de objetos con los productos de la cotización.
     */
    function renderQuoteDetails(details) {
        if (details.length === 0) {
            modalBody.innerHTML = '<p>Esta cotización no tiene productos.</p>';
            return;
        }

        let tableHtml = '<table class="table table-bordered"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Tipo de Precio</th><th>Subtotal</th></tr></thead><tbody>';
        let total = 0;

        details.forEach(item => {
            const price = parseFloat(item.price_quoted);
            const quantity = parseInt(item.quantity);
            const subtotal = price * quantity;
            const priceType = item.price_type_applied || 'N/D'; // Mostrar 'N/D' si no está definido
            total += subtotal;

            tableHtml += `
                <tr>
                    <td>${item.product_name}</td>
                    <td>${quantity}</td>
                    <td>$${price.toFixed(2)}</td>
                    <td><span class="badge bg-secondary">${priceType}</span></td>
                    <td>$${subtotal.toFixed(2)}</td>
                </tr>`;
        });

        tableHtml += `</tbody><tfoot><tr><th colspan="4" class="text-end">Total:</th><th>$${total.toFixed(2)}</th></tr></tfoot></table>`;
        modalBody.innerHTML = tableHtml;
    }

    /**
     * Maneja el clic en el botón de filtrar.
     */
    filterBtn.addEventListener('click', function() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const status = statusFilterInput.value;
        const clientName = clientNameFilterInput.value;
        loadQuotes(1, startDate, endDate, status, clientName); // Al filtrar, siempre volver a la página 1
    });

    clearFilterBtn.addEventListener('click', function() {
        startDateInput.value = '';
        endDateInput.value = '';
        statusFilterInput.value = '';
        clientNameFilterInput.value = '';
        loadQuotes(1); // Al limpiar, volver a la página 1
    });

    /**
     * Renderiza los controles de paginación.
     */
    function renderPagination(paginationData) {
        paginationContainer.innerHTML = '';
        if (paginationData.total_pages <= 1) return;

        const { current_page, total_pages } = paginationData;

        // Botón "Anterior"
        let li = `<li class="page-item ${current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${current_page - 1}">Anterior</a>
                  </li>`;
        paginationContainer.innerHTML += li;

        // Botones de páginas
        for (let i = 1; i <= total_pages; i++) {
            li = `<li class="page-item ${i === current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                  </li>`;
            paginationContainer.innerHTML += li;
        }

        // Botón "Siguiente"
        li = `<li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${current_page + 1}">Siguiente</a>
              </li>`;
        paginationContainer.innerHTML += li;
    }

    // Manejar clics en la paginación
    paginationContainer.addEventListener('click', function(e) {
        e.preventDefault();
        const link = e.target.closest('a.page-link');
        if (link && !link.parentElement.classList.contains('disabled')) {
            const page = parseInt(link.dataset.page);
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const status = statusFilterInput.value;
            const clientName = clientNameFilterInput.value;
            loadQuotes(page, startDate, endDate, status, clientName);
        }
    });

    loadQuotes(1);
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>