<?php
/**
 * Endpoint para generar un reporte del historial de ajustes de inventario.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_adjustment_report', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este reporte.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    $productId = $_GET['product_id'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;

    $params = [];
    $whereClauses = [];

    $sql = "SELECT
                ia.adjustment_date,
                p.name AS product_name,
                b.batch_number,
                ia.adjustment_type,
                ia.quantity,
                ia.reason,
                up.name AS user_name
            FROM inventory_adjustment ia
            JOIN product p ON ia.id_product = p.id_product
            JOIN batch b ON ia.id_batch = b.id_batch
            JOIN user u ON ia.id_user = u.id_user
            JOIN person up ON u.id_person = up.id_person";

    if ($productId) {
        $whereClauses[] = "ia.id_product = :product_id";
        $params[':product_id'] = $productId;
    }
    if ($startDate && $endDate) {
        $whereClauses[] = "DATE(ia.adjustment_date) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate;
        $params[':end_date'] = $endDate;
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $sql .= " ORDER BY ia.adjustment_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $adjustments]);

} catch (Exception $e) {
    error_log("Error en reporte de ajustes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el reporte de ajustes.']);
}