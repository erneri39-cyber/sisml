<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Gestión de Clientes";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Clientes</h1>
    <?php if (isset($_SESSION['user_permissions']) && in_array('manage_clients', $_SESSION['user_permissions'])): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-client-btn">
            <i class="bi bi-plus-lg"></i> Añadir Cliente
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>NIT</th>
                <th>NRC</th>
                <?php if (isset($_SESSION['user_permissions']) && in_array('manage_clients', $_SESSION['user_permissions'])): ?>
                <th>Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody id="clients-tbody">
            <!-- Las filas se insertarán dinámicamente aquí -->
        </tbody>
    </table>
</div>

<!-- Modal para Añadir/Editar Cliente -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="clientForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientModalLabel">Añadir Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="clientId" name="id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="document_number" class="form-label">Número de Documento (DUI)</label>
                            <input type="text" class="form-control" id="document_number" name="document_number" required>
                            <div id="duiFeedback" class="form-text"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nit" class="form-label">NIT</label>
                            <input type="text" class="form-control" id="nit" name="nit">
                            <div id="nitFeedback" class="form-text"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nrc" class="form-label">NRC (Registro Fiscal)</label>
                            <input type="text" class="form-control" id="nrc" name="nrc">
                        </div>
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

<!-- Incluir imask.js desde CDN -->
<script src="https://unpkg.com/imask"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('clients-tbody');
        const modalEl = document.getElementById('clientModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('clientForm');
        const modalLabel = document.getElementById('clientModalLabel');
        const formStatus = document.getElementById('formStatus');
        const canManage = <?php echo json_encode(isset($_SESSION['user_permissions']) && in_array('manage_clients', $_SESSION['user_permissions'])); ?>;
        
        // Elementos para validación
        const duiInput = document.getElementById('document_number');
        const nitInput = document.getElementById('nit');
        const duiFeedback = document.getElementById('duiFeedback');
        const nitFeedback = document.getElementById('nitFeedback');

        // Inicializar la máscara para el campo DUI
        const duiMask = IMask(duiInput, { mask: '00000000-0' });

        // Inicializar la máscara para el campo NIT
        const nitMask = IMask(nitInput, { mask: '0000-000000-000-0' });

        const API_URL = '../controllers/clients_controller.php';

        async function loadClients() {
            const response = await fetch(API_URL);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.success) {
                result.data.forEach(client => {
                    const actionsHtml = canManage ? `
                        <td>
                            <button class="btn btn-sm btn-outline-secondary edit-btn" title="Editar"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-btn" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </td>` : '';
                    tbody.innerHTML += `
                        <tr data-id="${client.id_person}">
                            <td>${client.name}</td>
                            <td>${client.document_number}</td>
                            <td>${client.phone || ''}</td>
                            <td>${client.email || ''}</td>
                            <td>${client.NIT || ''}</td>
                            <td>${client.NRC || ''}</td>
                            ${actionsHtml}
                        </tr>
                    `;
                });
            }
        }

        if (canManage) {
            document.getElementById('add-client-btn').addEventListener('click', () => {
                form.reset();
                document.getElementById('clientId').value = '';
                modalLabel.textContent = 'Añadir Nuevo Cliente';
                formStatus.innerHTML = '';
                duiFeedback.innerHTML = '';
                nitFeedback.innerHTML = '';
                duiInput.classList.remove('is-invalid', 'is-valid');
                nitInput.classList.remove('is-invalid', 'is-valid');
                modal.show();
            });

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
                        const client = result.data;
                        form.reset();
                        formStatus.innerHTML = '';
                        duiFeedback.innerHTML = '';
                        nitFeedback.innerHTML = '';
                        duiInput.classList.remove('is-invalid', 'is-valid');
                        nitInput.classList.remove('is-invalid', 'is-valid');
                        document.getElementById('clientId').value = client.id_person;
                        document.getElementById('name').value = client.name;
                        document.getElementById('document_number').value = client.document_number;
                        document.getElementById('address').value = client.address || '';
                        document.getElementById('phone').value = client.phone || '';
                        document.getElementById('email').value = client.email || '';
                        document.getElementById('nit').value = client.NIT || '';
                        document.getElementById('nrc').value = client.NRC || '';
                        modalLabel.textContent = 'Editar Cliente';
                        modal.show();
                    }
                }

                if (deleteBtn) {
                    if (confirm('¿Está seguro de que desea eliminar este cliente? Esta acción no se puede deshacer.')) {
                        const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
                        const result = await response.json();
                        if (result.success) {
                            row.remove();
                        } else {
                            alert('Error al eliminar: ' + result.message);
                        }
                    }
                }
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const id = document.getElementById('clientId').value;
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

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
                    await loadClients();
                    setTimeout(() => modal.hide(), 1000);
                } else {
                    formStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                }
            });
        }

        // --- Lógica de Validación de Duplicados ---
        function debounce(func, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        async function validateField(inputEl, feedbackEl, fieldName) {
            const value = inputEl.value.trim();
            const ignoreId = document.getElementById('clientId').value;

            if (value === '') {
                feedbackEl.innerHTML = '';
                inputEl.classList.remove('is-invalid', 'is-valid');
                return;
            }

            feedbackEl.textContent = 'Verificando...';
            feedbackEl.className = 'form-text text-muted';

            let url = `${API_URL}?action=check_uniqueness&field=${fieldName}&value=${encodeURIComponent(value)}`;
            if (ignoreId) {
                url += `&ignore_id=${ignoreId}`;
            }

            const response = await fetch(url);
            const result = await response.json();

            if (result.exists) {
                feedbackEl.textContent = `Este ${fieldName === 'document_number' ? 'DUI' : 'NIT'} ya está registrado.`;
                feedbackEl.className = 'form-text text-danger';
                inputEl.classList.add('is-invalid');
            } else {
                feedbackEl.textContent = `${fieldName === 'document_number' ? 'DUI' : 'NIT'} disponible.`;
                feedbackEl.className = 'form-text text-success';
                inputEl.classList.add('is-valid');
            }
        }

        duiInput.addEventListener('input', debounce(() => validateField(duiInput, duiFeedback, 'document_number'), 500));
        nitInput.addEventListener('input', debounce(() => validateField(nitInput, nitFeedback, 'NIT'), 500));

        loadClients();
    });
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>