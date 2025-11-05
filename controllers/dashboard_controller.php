<?php
/**
 * Controlador para obtener los datos del Dashboard.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

$id_branch = $_SESSION['id_branch'];
$low_stock_threshold = 10; // Umbral para considerar "bajo stock"

try {
    // 1. Obtener resumen de ventas del día para la sucursal actual
    $sqlSales = "SELECT 
                    COUNT(id_sale) as sales_count, 
                    COALESCE(SUM(total_amount - (SELECT COALESCE(SUM(discount), 0) FROM detail_sale ds WHERE ds.id_sale = s.id_sale)), 0) as total_revenue
                 FROM sale s
                 WHERE id_branch = :id_branch AND DATE(sale_date) = CURDATE()";
    $stmtSales = $pdo->prepare($sqlSales);
    $stmtSales->execute([':id_branch' => $id_branch]);
    $salesSummary = $stmtSales->fetch(PDO::FETCH_ASSOC);

    // 2. Obtener productos con bajo stock en la sucursal actual
    $sqlLowStock = "SELECT 
                        p.id_product, 
                        p.name, 
                        p.code, 
                        SUM(b.stock) as total_stock
                    FROM product p
                    JOIN batch b ON p.id_product = b.id_product
                    WHERE p.id_branch = :id_branch
                    GROUP BY p.id_product, p.name, p.code
                    HAVING total_stock <= :threshold AND total_stock > 0
                    ORDER BY total_stock ASC";
    $stmtLowStock = $pdo->prepare($sqlLowStock);
    $stmtLowStock->execute([
        ':id_branch' => $id_branch,
        ':threshold' => $low_stock_threshold
    ]);
    $lowStockProducts = $stmtLowStock->fetchAll(PDO::FETCH_ASSOC);

    // 3. Combinar los resultados
    $data = [
        'sales_summary' => $salesSummary,
        'low_stock_products' => $lowStockProducts
    ];

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    error_log("Error en dashboard_controller: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al obtener los datos del dashboard.']);
}
?>