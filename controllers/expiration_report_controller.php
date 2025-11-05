<?php
/**
 * Endpoint para generar un reporte de productos próximos a vencer.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_expiration_report', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este reporte.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    $id_branch = $_SESSION['id_branch'];
    $days_threshold = 90; // Umbral de días para considerar "próximo a vencer"

    $sql = "SELECT
                p.code AS product_code,
                p.name AS product_name,
                b.batch_number,
                b.stock,
                b.expiration_date,
                l.nombre AS laboratory_name
            FROM batch b
            JOIN product p ON b.id_product = p.id_product
            LEFT JOIN laboratorio l ON p.id_laboratorio = l.id_laboratorio
            WHERE 
                b.id_branch = :id_branch AND
                b.stock > 0 AND
                b.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY b.expiration_date ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_branch' => $id_branch,
        ':days' => $days_threshold
    ]);
    $expiring_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $expiring_products]);

} catch (Exception $e) {
    error_log("Error en reporte de vencimientos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el reporte de vencimientos.']);
}