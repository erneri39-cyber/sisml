<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$pageTitle = "Gestión de Sucursales";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Sucursales</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-sucursal-btn">
            <i class="bi bi-plus-lg"></i>
            Añadir Sucursal
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Nombre</th>
                <th scope="col">Dirección</th>
                <th scope="col">Teléfono</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody id="sucursales-tbody">
            <!-- Las filas se insertarán dinámicamente aquí -->
        </tbody>
    </table>
</div>

<!-- Modal para Añadir/Editar Sucursal -->
<div class="modal fade" id="sucursalModal" tabindex="-1" aria-labelledby="sucursalModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="sucursalForm">
        <div class="modal-header">
          <h5 class="modal-title" id="sucursalModalLabel">Añadir Nueva Sucursal</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="sucursalId" name="id">
            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="nombre" name="name" required>
            </div>
            <div class="mb-3">
              <label for="direccion" class="form-label">Dirección</label>
              <input type="text" class="form-control" id="direccion" name="address">
            </div>
            <div class="mb-3">
              <label for="telefono" class="form-label">Teléfono</label>
              <input type="text" class="form-control" id="telefono" name="phone">
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
    const tbody = document.getElementById('sucursales-tbody');
    const modalEl = document.getElementById('sucursalModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('sucursalForm');
    const modalLabel = document.getElementById('sucursalModalLabel');
    const formStatus = document.getElementById('formStatus');

    const API_URL = '../controllers/branches_controller.php';

    async function loadSucursales() {
        try {
            const response = await fetch(API_URL);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.success) {
                result.data.forEach(s => {
                    tbody.innerHTML += `
                        <tr data-id="${s.id_branch}">
                            <td>${s.id_branch}</td>
                            <td>${s.name}</td>
                            <td>${s.address || ''}</td>
                            <td>${s.phone || ''}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary edit-btn" title="Editar"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-outline-danger delete-btn" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${result.message}</td></tr>`;
            }
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error al cargar los datos.</td></tr>`;
        }
    }

    document.getElementById('add-sucursal-btn').addEventListener('click', () => {
        form.reset();
        document.getElementById('sucursalId').value = '';
        modalLabel.textContent = 'Añadir Nueva Sucursal';
        formStatus.innerHTML = '';
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
                const s = result.data;
                form.reset();
                formStatus.innerHTML = '';
                document.getElementById('sucursalId').value = s.id_branch;
                document.getElementById('nombre').value = s.name;
                document.getElementById('direccion').value = s.address;
                document.getElementById('telefono').value = s.phone;
                modalLabel.textContent = 'Editar Sucursal';
                modal.show();
            }
        }

        if (deleteBtn) {
            if (confirm('¿Está seguro de que desea eliminar esta sucursal?')) {
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
        const id = document.getElementById('sucursalId').value;
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
            await loadSucursales();
            modal.hide();
        } else {
            formStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    });

    loadSucursales();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php'; // template.php ahora está en la misma carpeta 'views'
?>