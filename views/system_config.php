<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('manage_system_settings', $_SESSION['user_permissions'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Configuración del Sistema";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Configuración del Sistema</h1>
</div>

<div class="card">
    <div class="card-body">
        <form id="configForm" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="empresa_nombre" class="form-label">Nombre de la Empresa</label>
                <input type="text" class="form-control" id="empresa_nombre" name="EMPRESA_NOMBRE">
                <div class="form-text">Este nombre aparecerá en los encabezados de los documentos PDF.</div>
            </div>

            <div class="mb-3">
                <label for="fiscal_mode" class="form-label">Modo Fiscal</label>
                <select class="form-select" id="fiscal_mode" name="FISCAL_MODE">
                    <option value="TRADICIONAL">Tradicional (Tickets Internos)</option>
                    <option value="DTE">DTE (Documento Tributario Electrónico)</option>
                </select>
                <div class="form-text">Define cómo se procesarán las ventas. <strong>¡Cuidado!</strong> Cambiar esto afecta la facturación.</div>
            </div>

            <div class="mb-3">
                <label for="logo" class="form-label">Logo de la Empresa</label>
                <input class="form-control" type="file" id="logo" name="logo" accept="image/png, image/jpeg">
                <div class="form-text">Sube un nuevo logo (PNG o JPG). Si no seleccionas un archivo, se mantendrá el logo actual.</div>
                <div class="mt-2">
                    <label class="form-label">Logo Actual:</label><br>
                    <img id="logo-preview" src="" alt="Logo Actual" style="max-height: 80px; background-color: #f8f9fa; padding: 5px; border-radius: 5px;">
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg">Guardar Configuración</button>
            </div>
        </form>
        <div id="formStatus" class="mt-3"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('configForm');
    const formStatus = document.getElementById('formStatus');
    const API_URL = '../controllers/system_config_controller.php';

    // Cargar la configuración actual al iniciar
    async function loadConfig() {
        try {
            const response = await fetch(API_URL);
            const result = await response.json();

            if (result.success) {
                document.getElementById('empresa_nombre').value = result.data.EMPRESA_NOMBRE || '';
                document.getElementById('fiscal_mode').value = result.data.FISCAL_MODE || 'TRADICIONAL';
                document.getElementById('logo-preview').src = `../${result.data.EMPRESA_LOGO_PATH}?v=${new Date().getTime()}` || '';
            } else {
                formStatus.innerHTML = `<div class="alert alert-danger">Error al cargar la configuración: ${result.message}</div>`;
            }
        } catch (error) {
            formStatus.innerHTML = `<div class="alert alert-danger">Error de conexión al cargar la configuración.</div>`;
        }
    }

    // Enviar el formulario para guardar
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        formStatus.innerHTML = '<div class="alert alert-info">Guardando...</div>';

        const formData = new FormData(form);

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                // No se establece Content-Type, el navegador lo hará por nosotros con el boundary correcto para multipart/form-data
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                formStatus.innerHTML = `<div class="alert alert-success">${result.message} <br><small>Algunos cambios pueden requerir que cierre y vuelva a iniciar sesión para tener efecto.</small></div>`;
            } else {
                formStatus.innerHTML = `<div class="alert alert-danger">Error al guardar: ${result.message}</div>`;
            }
            loadConfig(); // Recargar la configuración para mostrar el nuevo logo si se cambió
        } catch (error) {
            formStatus.innerHTML = `<div class="alert alert-danger">Error de conexión al guardar.</div>`;
        }
    });

    loadConfig();
});
</script>

<?php
$content = ob_get_clean();
include 'template.php';
?>