<?php
/**
 * Endpoint para generar un reporte del historial de compras de un cliente.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_client_purchase_history', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este reporte.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    $action = $_GET['action'] ?? 'get_sales';

    if ($action === 'get_sales') {
        $clientId = $_GET['client_id'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 15; // Registros por página
        $offset = ($page - 1) * $limit;

        if (!$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
            exit;
        }

        $params = [':client_id' => $clientId];
        $whereClauses = ["s.id_client = :client_id"];

        // Base de la consulta para reutilizar en conteo y selección
        $baseSql = "FROM sale s
                    JOIN user u ON s.id_user = u.id_user
                    JOIN person up ON u.id_person = up.id_person";

        if ($startDate && $endDate) {
            $whereClauses[] = "DATE(s.sale_date) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }

        $whereSql = " WHERE " . implode(' AND ', $whereClauses);

        // 1. Obtener el conteo total de registros
        $countSql = "SELECT COUNT(s.id_sale) " . $baseSql . $whereSql;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();

        // 2. Obtener los registros de la página actual
        $sql = "SELECT
                    s.id_sale,
                    s.sale_date,
                    s.total_amount,
                    (SELECT COALESCE(SUM(ds.discount), 0) FROM detail_sale ds WHERE ds.id_sale = s.id_sale) as total_discount,
                    up.name as user_name
                " . $baseSql . $whereSql . " ORDER BY s.sale_date DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        // Bind de parámetros, incluyendo limit y offset
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true, 
            'data' => $sales,
            'pagination' => [
                'total_records' => (int)$totalRecords,
                'current_page' => $page,
                'total_pages' => ceil($totalRecords / $limit)
            ]
        ]);

    } elseif ($action === 'get_sale_details') {
        $saleId = $_GET['sale_id'] ?? null;
        if (!$saleId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado.']);
            exit;
        }

        $sql = "SELECT
                    p.name as product_name,
                    ds.quantity,
                    ds.sale_price_applied,
                    ds.discount
                FROM detail_sale ds
                JOIN product p ON ds.id_product = p.id_product
                WHERE ds.id_sale = :sale_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':sale_id' => $saleId]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $details]);
    }

} catch (Exception $e) {
    error_log("Error en reporte de historial de cliente: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el reporte.']);
}