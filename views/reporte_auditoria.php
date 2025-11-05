<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_audit_log', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Reporte de Auditoría";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reporte de Auditoría del Sistema</h1>
</div>

<!-- Filtros -->
<div class="row g-3 align-items-center mb-4 p-3 border rounded bg-light">
    <div class="col-md-4">
        <label for="userFilter" class="form-label">Filtrar por Usuario:</label>
        <select id="userFilter" class="form-select"></select>
    </div>
    <div class="col-md-3">
        <label for="startDate" class="form-label">Desde:</label>
        <input type="date" id="startDate" class="form-control">
    </div>
    <div class="col-md-3">
        <label for="endDate" class="form-label">Hasta:</label>
        <input type="date" id="endDate" class="form-control">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button id="filterBtn" class="btn btn-primary me-2">Filtrar</button>
        <button id="clearFiltersBtn" class="btn btn-secondary">Limpiar</button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Usuario</th>
                <th>Clave de Configuración</th>
                <th>Valor Anterior</th>
                <th>Valor Nuevo</th>
                <th>Dirección IP</th>
            </tr>
        </thead>
        <tbody id="audit-log-tbody">
            <!-- Las filas se insertarán dinámicamente aquí -->
        </tbody>
    </table>
</div>

<div id="status-message" class="mt-3"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('audit-log-tbody');
    const statusMessageEl = document.getElementById('status-message');
    const userFilter = document.getElementById('userFilter');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const filterBtn = document.getElementById('filterBtn');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');

    // Cargar usuarios en el filtro
    async function loadUsers() {
        try {
            const response = await fetch('../controllers/users_list_controller.php');
            const result = await response.json();
            if (result.success) {
                userFilter.innerHTML = '<option value="">Todos los Usuarios</option>';
                result.data.forEach(user => {
                    userFilter.innerHTML += `<option value="${user.id_user}">${user.name}</option>`;
                });
            }
        } catch (error) {
            console.error('Error al cargar usuarios:', error);
        }
    }

    async function loadAuditLog() {
        statusMessageEl.innerHTML = '<div class="alert alert-info">Cargando registros...</div>';
        tbody.innerHTML = '';

        let url = new URL('../controllers/audit_log_controller.php', window.location.origin);
        if (userFilter.value) url.searchParams.append('user_id', userFilter.value);
        if (startDateInput.value) url.searchParams.append('start_date', startDateInput.value);
        if (endDateInput.value) url.searchParams.append('end_date', endDateInput.value);

        try {
            const response = await fetch(url);
            const result = await response.json();
            statusMessageEl.innerHTML = '';

            if (result.success) {
                if (result.data.length > 0) {
                    tbody.innerHTML = result.data.map(log => `
                        <tr>
                            <td>${new Date(log.change_date).toLocaleString('es-ES')}</td>
                            <td>${log.user_name}</td>
                            <td><span class="badge bg-secondary">${log.config_key}</span></td>
                            <td>${log.old_value || '<i>N/A</i>'}</td>
                            <td>${log.new_value}</td>
                            <td>${log.ip_address}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay registros de auditoría.</td></tr>';
                }
            } else {
                statusMessageEl.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            }
        } catch (error) {
            statusMessageEl.innerHTML = `<div class="alert alert-danger">Error de conexión al cargar los registros.</div>`;
        }
    }

    filterBtn.addEventListener('click', loadAuditLog);

    clearFiltersBtn.addEventListener('click', () => {
        userFilter.value = '';
        startDateInput.value = '';
        endDateInput.value = '';
        loadAuditLog();
    });

    loadUsers().then(loadAuditLog); // Cargar usuarios y luego el log inicial
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>