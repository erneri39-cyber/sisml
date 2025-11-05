<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('manage_roles', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Roles y Permisos";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Roles y Permisos</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-role-btn">
            <i class="bi bi-plus-lg"></i>
            Añadir Rol
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Nombre del Rol</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody id="roles-tbody">
            <!-- Las filas se insertarán dinámicamente aquí -->
        </tbody>
    </table>
</div>

<!-- Modal para Añadir/Editar Rol -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="roleForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalLabel">Añadir Nuevo Rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="roleId" name="id_rol">
                    <div class="mb-3">
                        <label for="roleName" class="form-label">Nombre del Rol</label>
                        <input type="text" class="form-control" id="roleName" name="name" required>
                    </div>
                    <hr>
                    <h5>Permisos</h5>
                    <div id="permissions-container" class="row">
                        <!-- Los checkboxes de permisos se cargarán aquí -->
                    </div>
                    <hr>
                    <div class="mb-3" id="activeUsersSection" style="display: none;">
                        <label class="form-label fw-bold text-danger">⚠️ Usuarios Activos Asignados</label>
                        <div id="activeUsersList" class="alert alert-warning p-2 small"></div>
                    </div>
                    <div id="formStatus" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('roles-tbody');
    const roleModalEl = document.getElementById('roleModal');
    const roleModal = new bootstrap.Modal(roleModalEl);
    const roleForm = document.getElementById('roleForm');
    const roleModalLabel = document.getElementById('roleModalLabel');
    const formStatus = document.getElementById('formStatus');
    const permissionsContainer = document.getElementById('permissions-container');

    const API_URL = '../controllers/roles_controller.php';
    let allPermissions = [];

    // Cargar todos los permisos disponibles una sola vez
    async function loadAllPermissions() {
        const response = await fetch(`${API_URL}?action=permissions`);
        const result = await response.json();
        if (result.success) {
            allPermissions = result.data;
        }
    }

    // Renderizar los checkboxes de permisos en el modal
    function renderPermissionCheckboxes(assignedPermissionIds = []) {
        permissionsContainer.innerHTML = '';
        // Crear un Set con los IDs (que ahora son strings) para una búsqueda rápida.
        const assignedIdsSet = new Set(assignedPermissionIds.map(id => id.toString()));

        allPermissions.forEach(permission => {
            const isChecked = assignedIdsSet.has(permission.id_permission.toString());
            permissionsContainer.innerHTML += `
                <div class="col-md-6 mb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" value="${permission.id_permission}" id="perm-${permission.id_permission}" name="permissions[]" ${isChecked ? 'checked' : ''} title="${permission.name}">
                        <label class="form-check-label" for="perm-${permission.id_permission}">
                            ${permission.description || permission.name}
                        </label>
                    </div>
                </div>
            `;
        });
    }

    // Cargar roles en la tabla
    async function loadRoles() {
        const response = await fetch(API_URL);
        const result = await response.json();
        tbody.innerHTML = '';
        if (result.success) {
            result.data.forEach(role => {
                tbody.innerHTML += `
                    <tr data-id="${role.id_rol}">
                        <td>${role.id_rol}</td>
                        <td>${role.name}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary edit-btn" title="Editar"><i class="bi bi-pencil-square"></i></button>
                            ${role.id_rol != 1 ? `<button class="btn btn-sm btn-outline-danger delete-btn" title="Eliminar"><i class="bi bi-trash"></i></button>` : ''}
                        </td>
                    </tr>
                `;
            });
        }
    }

    // Abrir modal para añadir rol
    document.getElementById('add-role-btn').addEventListener('click', () => {
        roleForm.reset();
        document.getElementById('roleId').value = '';
        roleModalLabel.textContent = 'Añadir Nuevo Rol';
        formStatus.innerHTML = '';
        renderPermissionCheckboxes(); // Renderizar sin permisos asignados
        roleModal.show();
    });

    // Manejar click en botones de editar y eliminar
    tbody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        const deleteBtn = e.target.closest('.delete-btn');
        if (!editBtn && !deleteBtn) return;

        const row = e.target.closest('tr');
        const id = row.dataset.id;

        if (editBtn) {
            const response = await fetch(`${API_URL}?id=${id}`);
            const result = await response.json();
            if (result.success) {
                const role = result.data;
                roleForm.reset();
                formStatus.innerHTML = '';
                document.getElementById('roleId').value = role.id_rol;
                document.getElementById('roleName').value = role.name;
                roleModalLabel.textContent = 'Editar Rol';
                renderPermissionCheckboxes(role.permissions);

                // --- NUEVA LÓGICA PARA USUARIOS ACTIVOS ---
                const activeUsersList = document.getElementById('activeUsersList');
                const activeUsersSection = document.getElementById('activeUsersSection');
                const activeUsers = role.active_users || []; // Asegurar que sea un array vacío si no existe

                if (activeUsers.length > 0) {
                    activeUsersSection.style.display = 'block';
                    activeUsersList.innerHTML = ''; // Limpiar lista
                    activeUsers.forEach(user => {
                        const span = document.createElement('span');
                        span.className = 'badge bg-danger me-2 mb-1'; 
                        span.textContent = `${user.name} (${user.username})`;
                        activeUsersList.appendChild(span);
                    });
                } else {
                    activeUsersSection.style.display = 'none';
                }
                roleModal.show();
            }
        }

        if (deleteBtn) {
            if (confirm('¿Está seguro de que desea eliminar este rol? Los usuarios con este rol perderán sus permisos.')) {
                const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
                const result = await response.json();
                if (result.success) {
                    row.remove();
                    alert(result.message);
                } else {
                    alert('Error al eliminar: ' + result.message);
                }
            }
        }
    });

    // Enviar formulario (Crear/Actualizar)
    roleForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('roleId').value;
        const formData = new FormData(roleForm);
        
        // Construir el objeto de datos manualmente para incluir los permisos
        const data = {
            name: formData.get('name'),
            permissions: formData.getAll('permissions[]') // Obtiene todos los checkboxes marcados
        };

        const url = id ? `${API_URL}?id=${id}` : API_URL;
        const method = id ? 'PUT' : 'POST';

        formStatus.innerHTML = '<div class="alert alert-info">Guardando...</div>';

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data) // Enviar siempre el objeto completo
        });

        const result = await response.json();

        if (result.success) {
            formStatus.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
            await loadRoles();
            setTimeout(() => roleModal.hide(), 1500);
        } else {
            formStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    });

    // Carga inicial
    async function initialize() {
        await loadAllPermissions();
        await loadRoles();
    }

    initialize();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>