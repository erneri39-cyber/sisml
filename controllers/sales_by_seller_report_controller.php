<?php
/**
 * Endpoint para generar un reporte de ventas por vendedor.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_sales_by_seller_report', $_SESSION['user_permissions'])) {
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
                u.id_user,
                p.name AS user_name,
                COUNT(s.id_sale) AS total_sales_count,
                SUM(s.total_amount) AS total_gross_revenue,
                SUM(d.total_discount) AS total_discounts,
                SUM(s.total_amount - d.total_discount) AS total_net_revenue
            FROM sale s
            JOIN user u ON s.id_user = u.id_user
            JOIN person p ON u.id_person = p.id_person
            LEFT JOIN (
                SELECT id_sale, COALESCE(SUM(discount), 0) as total_discount
                FROM detail_sale
                GROUP BY id_sale
            ) d ON s.id_sale = d.id_sale
            WHERE s.sale_date BETWEEN :start_date AND :end_date
            GROUP BY u.id_user, p.name
            ORDER BY total_net_revenue DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start_date' => $startDate . ' 00:00:00',
        ':end_date' => $endDate . ' 23:59:59'
    ]);
    $salesBySeller = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $salesBySeller]);

} catch (Exception $e) {
    error_log("Error en reporte de ventas por vendedor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el reporte.']);
}