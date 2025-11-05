<?php
/**
 * Endpoint para exportar el reporte de ventas por vendedor a un archivo CSV.
 */

session_start();

// 1. Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado.');
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_sales_by_seller_report', $_SESSION['user_permissions'])) {
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

    // 4. Configurar cabeceras para la descarga del CSV
    $filename = "reporte_ventas_por_vendedor_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Vendedor', 'N° de Ventas', 'Ingresos Brutos', 'Descuentos Otorgados', 'Ingresos Netos']);

    // 5. Escribir los datos en el CSV
    foreach ($salesBySeller as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;

} catch (Exception $e) {
    error_log("Error al exportar CSV de ventas por vendedor: " . $e->getMessage());
    die("Error al generar el archivo de exportación.");
}