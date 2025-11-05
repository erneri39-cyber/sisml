<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Gestión de Medidas";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Medidas</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-medida-btn">
            <i class="bi bi-plus-lg"></i> Añadir Medida
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Descripción (Ej: Caja x10, Frasco 120ml)</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="medidas-tbody">
            <!-- Filas se insertarán dinámicamente -->
        </tbody>
    </table>
</div>

<!-- Modal para Añadir/Editar Medida -->
<div class="modal fade" id="medidaModal" tabindex="-1" aria-labelledby="medidaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="medidaForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="medidaModalLabel">Añadir Medida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="medidaId" name="id">
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion" required>
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
    const tbody = document.getElementById('medidas-tbody');
    const modalEl = document.getElementById('medidaModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('medidaForm');
    const modalLabel = document.getElementById('medidaModalLabel');
    const formStatus = document.getElementById('formStatus');

    const API_URL = '../controllers/medidas_controller.php';

    // Cargar todas las medidas al iniciar
    async function loadMedidas() {
        const response = await fetch(API_URL);
        const result = await response.json();
        tbody.innerHTML = '';
        if (result.success) {
            result.data.forEach(med => {
                tbody.innerHTML += `
                    <tr data-id="${med.id_medida}">
                        <td>${med.descripcion}</td>
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
    document.getElementById('add-medida-btn').addEventListener('click', () => {
        form.reset();
        document.getElementById('medidaId').value = '';
        modalLabel.textContent = 'Añadir Medida';
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
                const med = result.data;
                form.reset();
                formStatus.innerHTML = '';
                document.getElementById('medidaId').value = med.id_medida;
                document.getElementById('descripcion').value = med.descripcion;
                modalLabel.textContent = 'Editar Medida';
                modal.show();
            }
        }

        if (deleteBtn) {
            if (confirm('¿Está seguro de que desea eliminar esta medida?')) {
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
        const id = document.getElementById('medidaId').value;
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
            await loadMedidas();
            setTimeout(() => modal.hide(), 1000);
        } else {
            formStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    });

    loadMedidas();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>