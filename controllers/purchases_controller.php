<?php
/**
 * Controlador para la gestión de Compras.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Purchase.php';
require_once dirname(__DIR__) . '/purchase_helper.php'; // Incluimos el helper

$method = $_SERVER['REQUEST_METHOD'];
$purchaseModel = new Purchase($pdo);

switch ($method) {
    case 'POST':
        handleCreate($purchaseModel, $pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no soportado.']);
        break;
}

/**
 * Maneja la creación de una nueva compra.
 */
function handleCreate(Purchase $purchaseModel, PDO $pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $header = $data['header'] ?? null;
    $details = $data['details'] ?? null;

    if (!$header || !$details || empty($details)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos de compra incompletos.']);
        exit;
    }

    try {
        // Añadir datos de sesión al encabezado para que el modelo los utilice
        $header['id_user'] = $_SESSION['user_id'];
        $header['id_branch'] = $_SESSION['id_branch'];
        
        $newPurchaseId = $purchaseModel->create($header, $details);

        // Generar los códigos de venta para las etiquetas
        generateSaleCodesForPurchase($pdo, $newPurchaseId);

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Compra registrada exitosamente.', 'id_purchase' => $newPurchaseId]);
    } catch (Exception $e) {
        error_log("Error al crear compra: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error en el servidor al registrar la compra: ' . $e->getMessage()]);
    }
}
?>