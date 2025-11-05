<?php
/**
 * Controlador para procesar ajustes de inventario.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('manage_inventory_adjustments', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/InventoryAdjustment.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validación de datos
$requiredFields = ['id_product', 'id_batch', 'adjustment_type', 'quantity', 'reason'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "El campo '{$field}' es obligatorio."]);
        exit;
    }
}

if (!in_array($data['adjustment_type'], ['Entrada', 'Salida'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de ajuste no válido.']);
    exit;
}

$data['id_user'] = $_SESSION['user_id'];

try {
    $adjustmentModel = new InventoryAdjustment($pdo);
    $success = $adjustmentModel->create($data);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Ajuste de inventario registrado exitosamente.']);
    }

} catch (Exception $e) {
    http_response_code(409); // Conflict or Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>