<?php
$pageTitle = "Dashboard";
ob_start(); // Inicia el buffer de salida
?>

<!-- Contenido específico del Dashboard -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row">
    <!-- Alerta DTE en Contingencia -->
    <div class="col-md-6 mb-4">
        <div class="card border-danger shadow-sm">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle-fill"></i> MODO CONTINGENCIA ACTIVO
            </div>
            <div class="card-body text-danger">
                <h5 class="card-title">DTE en Contingencia</h5>
                <p class="card-text">El sistema está operando en modo de contingencia. Los Documentos Tributarios Electrónicos (DTE) no se están enviando al Ministerio de Hacienda en tiempo real.</p>
                <a href="ventas_contingencia.php" class="btn btn-danger">Ver DTE pendientes de envío</a>
            </div>
        </div>
    </div>

    <!-- Alerta Facturas Pendientes -->
    <div class="col-md-6 mb-4">
        <div class="card border-warning shadow-sm">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-printer-fill"></i> FACTURAS PENDIENTES
            </div>
            <div class="card-body text-dark">
                <h5 class="card-title">Facturas Pendientes de Impresión</h5>
                <p class="card-text">Hay facturas que han sido generadas pero aún no se han impreso para su entrega física. Asegúrese de procesarlas.</p>
                <a href="ventas_pendientes.php" class="btn btn-warning">Ver facturas pendientes</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean(); // Captura el buffer de salida y lo limpia
include 'template.php'; // template.php ahora está en la misma carpeta 'views'
?>