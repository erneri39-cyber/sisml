<?php
/**
 * Endpoint para exportar el reporte de productos más vendidos a un archivo CSV.
 */

session_start();

// 1. Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado.');
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_bestsellers_report', $_SESSION['user_permissions'])) {
    die('No tienes permisos para realizar esta acción.');
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    // 2. Obtener filtros de la URL
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;

    if (!$startDate || !$endDate) {
        die('El rango de fechas es obligatorio.');
    }

    // 3. Construir la consulta SQL (la misma que en el reporte visual)
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
            LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start_date' => $startDate . ' 00:00:00',
        ':end_date' => $endDate . ' 23:59:59'
    ]);
    $bestsellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Configurar cabeceras para la descarga del CSV
    $filename = "reporte_mas_vendidos_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Ranking', 'Codigo Producto', 'Producto', 'Cantidad Vendida', 'Ingresos Generados']);

    // 5. Escribir los datos en el CSV, añadiendo el ranking
    foreach ($bestsellers as $index => $row) {
        array_unshift($row, $index + 1); // Añadir el ranking al principio de la fila
        fputcsv($output, $row);
    }
    fclose($output);
    exit;

} catch (Exception $e) {
    error_log("Error al exportar CSV de más vendidos: " . $e->getMessage());
    die("Error al generar el archivo de exportación.");
}