<?php
/**
 * Controlador para la gestión de Pedidos de Ruta.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('manage_rutas', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para gestionar rutas.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'get_list';

        if ($action === 'get_list') {
            $statusFilter = $_GET['status'] ?? '';

            $sql = "SELECT 
                        s.id_sale,
                        p.name AS client_name,
                        s.sale_date,
                        s.total_amount,
                        s.rutero_status
                    FROM sale s
                    JOIN person p ON s.id_client = p.id_person
                    WHERE s.is_rutero_sale = 1";

            $params = [];
            if (!empty($statusFilter)) {
                $sql .= " AND s.rutero_status = :status";
                $params[':status'] = $statusFilter;
            }

            $sql .= " ORDER BY s.sale_date DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $rutas]);

        } elseif ($action === 'get_details') {
            $saleId = $_GET['id'] ?? 0;
            if ($saleId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de venta no válido.']);
                exit;
            }

            $sql = "SELECT p.name AS product_name, ds.quantity, ds.sale_price_applied, ds.discount
                    FROM detail_sale ds
                    JOIN product p ON ds.id_product = p.id_product
                    WHERE ds.id_sale = :id_sale";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_sale' => $saleId]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $details]);
        }

    } elseif ($method === 'PUT') {
        $id_sale = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id_sale <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no válido.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $newStatus = $data['status'] ?? '';

        $allowedStatus = ['En Espera', 'En Preparacion', 'Listo para Entrega', 'Entregado', 'Cancelado'];
        if (!in_array($newStatus, $allowedStatus)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Estado no válido.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE sale SET rutero_status = :status WHERE id_sale = :id_sale AND is_rutero_sale = 1");
        $stmt->execute([':status' => $newStatus, ':id_sale' => $id_sale]);

        echo json_encode(['success' => true, 'message' => 'Estado del pedido actualizado.']);
    }

} catch (Exception $e) {
    error_log("Error en rutas_controller: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor.']);
}
?>