<?php
/**
 * Endpoint para generar un reporte general de ventas.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    // 1. Validar parámetros de fecha
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $sellerId = $_GET['seller_id'] ?? null;

    if (!$startDate || !$endDate) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El rango de fechas es obligatorio.']);
        exit;
    }
    
    $params = [
        ':start_date' => $startDate . ' 00:00:00',
        ':end_date' => $endDate . ' 23:59:59'
    ];
    // 2. Preparar la consulta SQL
    $sql = "SELECT 
                s.id_sale,
                s.sale_date,
                client_p.name AS client_name,
                user_p.name AS user_name,
                s.total_amount,
                COALESCE(SUM(ds.discount), 0) AS total_discount,
                CASE 
                    WHEN s.id_quotation IS NOT NULL THEN 'Cotización'
                    ELSE 'Directa'
                END AS sale_source
            FROM sale s
            JOIN person client_p ON s.id_client = client_p.id_person
            JOIN user u ON s.id_user = u.id_user
            JOIN person user_p ON u.id_person = user_p.id_person
            LEFT JOIN detail_sale ds ON s.id_sale = ds.id_sale
            WHERE s.sale_date BETWEEN :start_date AND :end_date";

    if ($sellerId) {
        $sql .= " AND s.id_user = :seller_id";
        $params[':seller_id'] = $sellerId;
    }

    $sql .= " GROUP BY s.id_sale ORDER BY s.sale_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Preparar datos para el gráfico (nueva consulta)
    $sqlChart = "SELECT 
                    DATE(s.sale_date) AS sale_day,
                    SUM(s.total_amount - COALESCE(ds.discount, 0)) AS net_revenue_day,
                    SUM(COALESCE(ds.discount, 0)) AS total_discount_day
                 FROM sale s
                 LEFT JOIN detail_sale ds ON s.id_sale = ds.id_sale
                 WHERE s.sale_date BETWEEN :start_date AND :end_date";
    
    if ($sellerId) {
        $sqlChart .= " AND s.id_user = :seller_id";
    }

    $sqlChart .= " GROUP BY sale_day ORDER BY sale_day ASC";
    
    $stmtChart = $pdo->prepare($sqlChart);
    $stmtChart->execute($params);
    $chartData = $stmtChart->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calcular el resumen
    $total_sales_count = count($details);
    $total_gross_revenue = 0;
    $total_discounts_applied = 0;

    foreach ($details as &$item) {
        $total_gross_revenue += (float)$item['total_amount'];
        $total_discounts_applied += (float)$item['total_discount'];
        // Formatear fecha para la vista
        $item['sale_date'] = (new DateTime($item['sale_date']))->format('d/m/Y H:i');
    }

    $total_net_revenue = $total_gross_revenue - $total_discounts_applied;

    $summary = [
        'total_sales_count' => $total_sales_count,
        'total_gross_revenue' => $total_gross_revenue,
        'total_discounts_applied' => $total_discounts_applied,
        'total_net_revenue' => $total_net_revenue
    ];

    // 4. Devolver los datos
    echo json_encode(['success' => true, 'data' => ['summary' => $summary, 'details' => $details, 'chartData' => $chartData]]);

} catch (Exception $e) {
    error_log("Error en reporte de ventas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el reporte: ' . $e->getMessage()]);
}