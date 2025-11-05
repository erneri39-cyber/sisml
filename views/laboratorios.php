<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Gestión de Laboratorios";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Laboratorios</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-laboratorio-btn">
            <i class="bi bi-plus-lg"></i> Añadir Laboratorio
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Contacto</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="laboratorios-tbody">
            <!-- Filas se insertarán dinámicamente -->
        </tbody>
    </table>
</div>

<!-- Modal para Añadir/Editar Laboratorio -->
<div class="modal fade" id="laboratorioModal" tabindex="-1" aria-labelledby="laboratorioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="laboratorioForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="laboratorioModalLabel">Añadir Laboratorio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="laboratorioId" name="id">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
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
    const tbody = document.getElementById('laboratorios-tbody');
    const modalEl = document.getElementById('laboratorioModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('laboratorioForm');
    const modalLabel = document.getElementById('laboratorioModalLabel');
    const formStatus = document.getElementById('formStatus');

    const API_URL = '../controllers/laboratorios_controller.php';

    // Cargar todos los laboratorios al iniciar
    async function loadLaboratorios() {
        const response = await fetch(API_URL);
        const result = await response.json();
        tbody.innerHTML = '';
        if (result.success) {
            result.data.forEach(lab => {
                tbody.innerHTML += `
                    <tr data-id="${lab.id_laboratorio}">
                        <td>${lab.nombre}</td>
                        <td>${lab.contacto || ''}</td>
                        <td>${lab.telefono || ''}</td>
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
    document.getElementById('add-laboratorio-btn').addEventListener('click', () => {
        form.reset();
        document.getElementById('laboratorioId').value = '';
        modalLabel.textContent = 'Añadir Laboratorio';
        formStatus.innerHTML = '';
        modal.show();
    });

    // Manejar click en botones de editar y eliminar
    tbody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        const deleteBtn = e.target.closest('.delete-btn');
        const row = e.target.closest('tr');
        const id = row.dataset.id;

        if (editBtn) {
            const response = await fetch(`${API_URL}?id=${id}`);
            const result = await response.json();
            if (result.success) {
                const lab = result.data;
                form.reset();
                formStatus.innerHTML = '';
                document.getElementById('laboratorioId').value = lab.id_laboratorio;
                document.getElementById('nombre').value = lab.nombre;
                document.getElementById('contacto').value = lab.contacto;
                document.getElementById('telefono').value = lab.telefono;
                document.getElementById('direccion').value = lab.direccion;
                modalLabel.textContent = 'Editar Laboratorio';
                modal.show();
            }
        }

        if (deleteBtn) {
            if (confirm('¿Está seguro de que desea eliminar este laboratorio?')) {
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
        const id = document.getElementById('laboratorioId').value;
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
            await loadLaboratorios();
            setTimeout(() => modal.hide(), 1000);
        } else {
            formStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    });

    loadLaboratorios();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>