<?php
/**
 * Endpoint para generar un reporte de los productos más vendidos.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_bestsellers_report', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este reporte.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;

    if (!$startDate || !$endDate) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El rango de fechas es obligatorio.']);
        exit;
    }

    $sql = "SELECT
                p.code AS product_code,
                p.name AS product_name,
                SUM(ds.quantity) AS total_quantity_sold,
                SUM((ds.quantity * ds.sale_price_applied) - ds.discount) AS total_revenue
            FROM detail_sale ds
            JOIN product p ON ds.id_product = p.id_product
            JOIN sale s ON ds.id_sale = s.id_sale
            WHERE s.sale_date BETWEEN :start_date AND :end_date
            GROUP BY p.id_product, p.code, p.name
            ORDER BY total_quantity_sold DESC
            LIMIT 100"; // Limitar a los 100 más vendidos

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start_date' => $startDate . ' 00:00:00',
        ':end_date' => $endDate . ' 23:59:59'
    ]);
    $bestsellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $bestsellers]);

} catch (Exception $e) {
    error_log("Error en reporte de más vendidos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el reporte.']);
}