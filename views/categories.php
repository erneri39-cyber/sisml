<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Gestión de Categorías";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Categorías</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-category-btn">
            <i class="bi bi-plus-lg"></i> Añadir Categoría
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="categories-tbody">
            <!-- Filas se insertarán dinámicamente -->
        </tbody>
    </table>
</div>

<!-- Modal para Añadir/Editar Categoría -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="categoryForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Añadir Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="categoryId" name="id">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="name" name="name" required>
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
    const tbody = document.getElementById('categories-tbody');
    const modalEl = document.getElementById('categoryModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('categoryForm');
    const modalLabel = document.getElementById('categoryModalLabel');
    const formStatus = document.getElementById('formStatus');

    const API_URL = '../controllers/categories_controller.php';

    async function loadCategories() {
        const response = await fetch(API_URL);
        const result = await response.json();
        tbody.innerHTML = '';
        if (result.success) {
            result.data.forEach(cat => {
                tbody.innerHTML += `
                    <tr data-id="${cat.id_category}">
                        <td>${cat.name}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary edit-btn" title="Editar"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-btn" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        }
    }

    document.getElementById('add-category-btn').addEventListener('click', () => {
        form.reset();
        document.getElementById('categoryId').value = '';
        modalLabel.textContent = 'Añadir Categoría';
        formStatus.innerHTML = '';
        modal.show();
        document.getElementById('name').focus();
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
                const cat = result.data;
                form.reset();
                formStatus.innerHTML = '';
                document.getElementById('categoryId').value = cat.id_category;
                document.getElementById('name').value = cat.name;
                modalLabel.textContent = 'Editar Categoría';
                modal.show();
                document.getElementById('name').focus();
            }
        }

        if (deleteBtn) {
            if (confirm('¿Está seguro de que desea eliminar esta categoría?')) {
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
        const id = document.getElementById('categoryId').value;
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
            await loadCategories();
            setTimeout(() => modal.hide(), 1000);
        } else {
            formStatus.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    });

    loadCategories();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>