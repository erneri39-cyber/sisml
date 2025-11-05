<?php
/**
 * Endpoint para generar un reporte de valor de inventario.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_inventory_report', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este reporte.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    $id_branch = $_SESSION['id_branch'];
    $categoryId = $_GET['category_id'] ?? null;
    $labId = $_GET['lab_id'] ?? null;

    $params = [':id_branch' => $id_branch];
    $whereClauses = ["p.id_branch = :id_branch", "b.stock > 0"];

    $sql = "SELECT
                p.id_product,
                p.code,
                p.name AS product_name,
                c.name AS category_name,
                l.nombre AS laboratory_name,
                SUM(b.stock) AS total_stock,
                SUM(b.stock * b.purchase_price) AS total_cost_value,
                SUM(b.stock * b.sale_price) AS total_sale_value
            FROM product p
            JOIN batch b ON p.id_product = b.id_product
            LEFT JOIN category c ON p.id_category = c.id_category
            LEFT JOIN laboratorio l ON p.id_laboratorio = l.id_laboratorio";

    if ($categoryId) {
        $whereClauses[] = "p.id_category = :category_id";
        $params[':category_id'] = $categoryId;
    }

    if ($labId) {
        $whereClauses[] = "p.id_laboratorio = :lab_id";
        $params[':lab_id'] = $labId;
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $sql .= " GROUP BY p.id_product, p.code, p.name, c.name, l.nombre ORDER BY p.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular resumen
    $total_cost = array_sum(array_column($details, 'total_cost_value'));
    $total_sale = array_sum(array_column($details, 'total_sale_value'));
    $total_units = array_sum(array_column($details, 'total_stock'));

    $summary = ['total_cost' => $total_cost, 'total_sale' => $total_sale, 'total_units' => $total_units];

    echo json_encode(['success' => true, 'data' => ['summary' => $summary, 'details' => $details]]);

} catch (Exception $e) {
    error_log("Error en reporte de inventario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el reporte.']);
}