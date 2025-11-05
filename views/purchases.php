<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Purchase.php';

$purchaseModel = new Purchase($pdo);
$purchases = $purchaseModel->getAllByBranch($_SESSION['id_branch']);

$pageTitle = "Gestión de Compras";
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Compras (Sucursal: <?php echo htmlspecialchars($_SESSION['id_branch']); ?>)</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="new_purchase.php" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-lg"></i>
            Registrar Nueva Compra
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th scope="col"># Compra</th>
                <th scope="col">Fecha</th>
                <th scope="col">Proveedor (Droguería)</th>
                <th scope="col"># Documento</th>
                <th scope="col">Usuario</th>
                <th scope="col">Total</th>
                <th scope="col">Estado</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($purchases as $purchase): ?>
            <tr>
                <td><?php echo $purchase['id_purchase']; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($purchase['purchase_date'])); ?></td>
                <td><?php echo htmlspecialchars($purchase['drogueria_name']); ?></td>
                <td><?php echo htmlspecialchars($purchase['document_number']); ?></td>
                <td><?php echo htmlspecialchars($purchase['user_name']); ?></td>
                <td>$<?php echo number_format($purchase['total_cost'], 2); ?></td>
                <td><span class="badge bg-success"><?php echo htmlspecialchars($purchase['status']); ?></span></td>
                <td>
                    <a href="../reports/print_labels.php?id_purchase=<?php echo $purchase['id_purchase']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Etiquetas"><i class="bi bi-printer"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include 'template.php';
?>