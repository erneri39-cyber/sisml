<?php
/**
 * Controlador para la gestión de Lotes (CRUD).
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Batch.php';

$batchModel = new Batch($pdo);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['product_id'])) {
                $batches = $batchModel->findByProductId((int)$_GET['product_id']);
                echo json_encode(['success' => true, 'data' => $batches]);
            } elseif (isset($_GET['id'])) {
                $batch = $batchModel->findById((int)$_GET['id']);
                echo json_encode(['success' => true, 'data' => $batch]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validación simple
            if (empty($data['batch_number']) || !isset($data['stock']) || !isset($data['sale_price']) || empty($data['expiration_date'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
                exit;
            }

            $newBatchId = $batchModel->create($data);
            $newBatch = $batchModel->findById($newBatchId);
            $totalStock = $batchModel->getTotalStockForProduct((int)$data['id_product']);

            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'Lote creado exitosamente.', 
                'data' => $newBatch,
                'total_stock' => $totalStock
            ]);
            break;

        case 'PUT':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id === 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de lote no proporcionado.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['batch_number']) || !isset($data['stock']) || !isset($data['sale_price']) || empty($data['expiration_date'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
                exit;
            }

            $success = $batchModel->update($id, $data);
            if ($success) {
                $updatedBatch = $batchModel->findById($id);
                $totalStock = $batchModel->getTotalStockForProduct((int)$updatedBatch['id_product']);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Lote actualizado exitosamente.',
                    'data' => $updatedBatch,
                    'total_stock' => $totalStock
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el lote.']);
            }
            break;

        case 'DELETE':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id === 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de lote no proporcionado.']);
                exit;
            }

            // Obtener el id_product antes de eliminar para poder recalcular el stock
            $batchToDelete = $batchModel->findById($id);
            if (!$batchToDelete) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Lote no encontrado.']);
                exit;
            }

            $success = $batchModel->delete($id);
            if ($success) {
                $totalStock = $batchModel->getTotalStockForProduct((int)$batchToDelete['id_product']);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Lote eliminado exitosamente.',
                    'total_stock' => $totalStock
                ]);
            } else {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar el lote porque tiene stock. Ponga el stock en 0 primero.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en batches_controller: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>