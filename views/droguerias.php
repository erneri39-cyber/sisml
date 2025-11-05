<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Gestión de Droguerías";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Droguerías</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-drogueria-btn">
            <i class="bi bi-plus-lg"></i> Añadir Droguería
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Vendedor</th>
                <th>Contacto</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="droguerias-tbody">
            <!-- Filas se insertarán dinámicamente -->
        </tbody>
    </table>
</div>

<!-- Modal para Añadir/Editar Droguería -->
<div class="modal fade" id="drogueriaModal" tabindex="-1" aria-labelledby="drogueriaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="drogueriaForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="drogueriaModalLabel">Añadir Droguería</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="drogueriaId" name="id">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="vendedor" class="form-label">Vendedor</label>
                        <input type="text" class="form-control" id="vendedor" name="vendedor">
                    </div>
                    <div class="mb-3">
                        <label for="contacto" class="form-label">Contacto</label>
                        <input type="text" class="form-control" id="contacto" name="contacto">
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono">
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="correo" name="correo">
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="2"></textarea>
                    </div>
                    <div id="formStatus" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('droguerias-tbody');
    const modalEl = document.getElementById('drogueriaModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('drogueriaForm');
    const modalLabel = document.getElementById('drogueriaModalLabel');
    const formStatus = document.getElementById('formStatus');

    const API_URL = '../controllers/droguerias_controller.php';

    // Cargar todas las droguerías al iniciar
    async function loadDroguerias() {
        const response = await fetch(API_URL);
        const result = await response.json();
        tbody.innerHTML = '';
        if (result.success) {
            result.data.forEach(d => {
                tbody.innerHTML += `
                    <tr data-id="${d.id_drogueria}">
                        <td>${d.nombre}</td>
                        <td>${d.vendedor || ''}</td>
                        <td>${d.contacto || ''}</td>
                        <td>${d.telefono || ''}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary edit-btn" title="Editar"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-btn" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        }
    }

    // Abrir modal para añadir
    document.getElementById('add-drogueria-btn').addEventListener('click', () => {
        form.reset();
        document.getElementById('drogueriaId').value = '';
        modalLabel.textContent = 'Añadir Droguería';
        formStatus.innerHTML = '';
        modal.show();
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
                const d = result.data;
                form.reset();
                formStatus.innerHTML = '';
                document.getElementById('drogueriaId').value = d.id_drogueria;
                document.getElementById('nombre').value = d.nombre;
                document.getElementById('vendedor').value = d.vendedor;
                document.getElementById('contacto').value = d.contacto;
                document.getElementById('telefono').value = d.telefono;
                document.getElementById('correo').value = d.correo;
                document.getElementById('direccion').value = d.direccion;
                modalLabel.textContent = 'Editar Droguería';
                modal.show();
            }
        }

        if (deleteBtn) {
            if (confirm('¿Está seguro de que desea eliminar esta droguería?')) {
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

    // Enviar formulario (Crear/Actualizar)
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('drogueriaId').value;
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
            await loadDroguerias();
            setTimeout(() => modal.hide(), 1000);
        } else {
            formStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    });

    loadDroguerias();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>