<?php
// --- Controlador Embebido ---
session_start();

// 1. Proteger la página: Redirigir si no hay sesión de usuario.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Comprobar permisos: Solo usuarios con 'manage_users' pueden acceder.
if (!isset($_SESSION['user_permissions']) || !in_array('manage_users', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php'); // Redirigir a una página de acceso denegado o dashboard
    exit;
}

// 3. Incluir dependencias (solo si se necesita lógica de PHP aquí, pero el JS hará las llamadas al controlador)
// require_once dirname(__DIR__) . '/db_connect.php';
// require_once dirname(__DIR__) . '/models/User.php';

$pageTitle = "Gestión de Usuarios";
ob_start();
?>

<!-- --- Vista --- -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Usuarios</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-user-btn">
            <i class="bi bi-plus-lg"></i>
            Añadir Usuario
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Nombre</th>
                <th scope="col">Usuario</th>
                <th scope="col">Email</th>
                <th scope="col">Rol</th>
                <th scope="col">Sucursal</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody id="users-tbody">
            <!-- Las filas se insertarán dinámicamente aquí -->
        </tbody>
    </table>
</div>

<!-- Modal para Añadir/Editar Usuario -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Añadir Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id_user">
                    <div class="mb-3">
                        <label for="personName" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="personName" name="person_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="documentNumber" class="form-label">Número de Documento (DUI)</label>
                        <input type="text" class="form-control" id="documentNumber" name="document_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div id="usernameFeedback" class="form-text"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Dejar vacío para no cambiar">
                        <div class="form-text">Dejar vacío para no cambiar la contraseña existente.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rol</label>
                        <select class="form-select" id="role" name="id_rol" required>
                            <!-- Opciones cargadas por JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="branch" class="form-label">Sucursal</label>
                        <select class="form-select" id="branch" name="id_branch" required>
                            <!-- Opciones cargadas por JS -->
                        </select>
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
    const tbody = document.getElementById('users-tbody');
    const userModalEl = document.getElementById('userModal');
    const userModal = new bootstrap.Modal(userModalEl);
    const userForm = document.getElementById('userForm');
    const userModalLabel = document.getElementById('userModalLabel');
    const formStatus = document.getElementById('formStatus');
    const roleSelect = document.getElementById('role');
    const branchSelect = document.getElementById('branch');
    const usernameInput = document.getElementById('username');
    const usernameFeedback = document.getElementById('usernameFeedback');

    const API_URL = '../controllers/users_controller.php';

    let currentEditingUserId = null; // Para la validación de username en edición

    // Función de "debounce" para no llamar al servidor en cada pulsación
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Cargar usuarios
    async function loadUsers() {
        try {
            const response = await fetch(API_URL);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.success) {
                result.data.forEach(user => {
                    tbody.innerHTML += `
                        <tr data-id="${user.id_user}">
                            <td>${user.id_user}</td>
                            <td>${user.person_name}</td>
                            <td>${user.username}</td>
                            <td>${user.email || ''}</td>
                            <td>${user.role_name || 'N/A'}</td>
                            <td>${user.branch_name || 'N/A'}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary edit-btn" title="Editar"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-outline-danger delete-btn" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${result.message}</td></tr>`;
            }
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error al cargar los datos.</td></tr>`;
        }
    }

    // Cargar roles y sucursales para los selects
    async function loadSelectOptions() {
        // Roles
        const rolesResponse = await fetch(`${API_URL}?action=roles`);
        const rolesResult = await rolesResponse.json();
        if (rolesResult.success) {
            roleSelect.innerHTML = '<option value="" disabled selected>Seleccione un rol</option>';
            rolesResult.data.forEach(role => {
                roleSelect.innerHTML += `<option value="${role.id_rol}">${role.name}</option>`;
            });
        }

        // Sucursales
        const branchesResponse = await fetch(`${API_URL}?action=branches`);
        const branchesResult = await branchesResponse.json();
        if (branchesResult.success) {
            branchSelect.innerHTML = '<option value="" disabled selected>Seleccione una sucursal</option>';
            branchesResult.data.forEach(branch => {
                branchSelect.innerHTML += `<option value="${branch.id_branch}">${branch.name}</option>`;
            });
        }
    }

    // Abrir modal para añadir usuario
    document.getElementById('add-user-btn').addEventListener('click', () => {
        userForm.reset();
        document.getElementById('userId').value = '';
        document.getElementById('password').required = true; // Contraseña es obligatoria al crear
        userModalLabel.textContent = 'Añadir Nuevo Usuario';
        formStatus.innerHTML = '';
        usernameFeedback.innerHTML = '';
        usernameInput.classList.remove('is-invalid', 'is-valid');
        currentEditingUserId = null;
        userModal.show();
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
                const user = result.data;
                userForm.reset();
                formStatus.innerHTML = '';
                usernameFeedback.innerHTML = '';
                usernameInput.classList.remove('is-invalid', 'is-valid');

                document.getElementById('userId').value = user.id_user;
                document.getElementById('personName').value = user.person_name;
                document.getElementById('documentNumber').value = user.document_number || '';
                document.getElementById('username').value = user.username;
                document.getElementById('password').value = ''; // No precargar contraseña
                document.getElementById('password').required = false; // Contraseña no obligatoria al editar
                document.getElementById('email').value = user.email || '';
                document.getElementById('phone').value = user.phone || '';
                document.getElementById('address').value = user.address || '';
                document.getElementById('role').value = user.id_rol;
                document.getElementById('branch').value = user.id_branch;

                userModalLabel.textContent = 'Editar Usuario';
                currentEditingUserId = user.id_user;
                userModal.show();
            } else {
                alert('Error al cargar usuario: ' + result.message);
            }
        }

        if (deleteBtn) {
            if (confirm('¿Está seguro de que desea eliminar este usuario? Esta acción es irreversible.')) {
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
    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('userId').value;
        const formData = new FormData(userForm);
        const data = Object.fromEntries(formData.entries());

        // Eliminar id_user del objeto de datos si es una actualización, ya que va en la URL
        if (id) {
            delete data.id_user;
        }
        // Si la contraseña está vacía, no la enviamos para que no se actualice
        if (data.password === '') {
            delete data.password;
        }

        const url = id ? `${API_URL}?id=${id}` : API_URL;
        const method = id ? 'PUT' : 'POST';

        formStatus.innerHTML = '<div class="alert alert-info">Guardando...</div>';

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            formStatus.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
            await loadUsers();
            setTimeout(() => userModal.hide(), 1000);
        } else {
            formStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    });

    // Validación de nombre de usuario en tiempo real
    usernameInput.addEventListener('input', debounce(async () => {
        const username = usernameInput.value.trim();
        if (username === '') {
            usernameFeedback.innerHTML = '';
            usernameInput.classList.remove('is-invalid', 'is-valid');
            return;
        }

        usernameFeedback.textContent = 'Verificando...';
        usernameFeedback.className = 'form-text text-muted';
        usernameInput.classList.remove('is-invalid', 'is-valid');

        try {
            const response = await fetch(`${API_URL}?action=check_username&username=${encodeURIComponent(username)}`);
            const result = await response.json();

            if (result.exists) {
                // Si estamos editando y el usuario existente es el mismo que estamos editando, es válido.
                if (currentEditingUserId && result.data && result.data.id_user == currentEditingUserId) {
                    usernameFeedback.textContent = 'Nombre de usuario disponible.';
                    usernameFeedback.className = 'form-text text-success';
                    usernameInput.classList.add('is-valid');
                } else {
                    usernameFeedback.textContent = 'Este nombre de usuario ya está en uso.';
                    usernameFeedback.className = 'form-text text-danger';
                    usernameInput.classList.add('is-invalid');
                }
            } else {
                usernameFeedback.textContent = 'Nombre de usuario disponible.';
                usernameFeedback.className = 'form-text text-success';
                usernameInput.classList.add('is-valid');
            }
        } catch (error) {
            usernameFeedback.textContent = 'No se pudo verificar el nombre de usuario.';
            usernameFeedback.className = 'form-text text-warning';
        }
    }, 500)); // Retraso de 500ms

    // Carga inicial
    loadUsers();
    loadSelectOptions();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>