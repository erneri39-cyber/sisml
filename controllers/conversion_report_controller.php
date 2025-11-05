<?php
/**
 * Endpoint para generar un reporte de conversión de cotizaciones a ventas.
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

    if (!$startDate || !$endDate) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El rango de fechas es obligatorio.']);
        exit;
    }

    // 2. Preparar la consulta SQL
    // Unimos 'sale' con 'quotation' donde el id_quotation no es nulo.
    // Filtramos por la fecha de la venta (sale_date) ya que es la fecha de conversión.
    $sql = "SELECT 
                q.id_quotation,
                s.id_sale,
                p.name AS client_name,
                q.quotation_date,
                s.sale_date,
                q.total_amount AS quote_amount,
                s.total_amount AS sale_amount
            FROM sale s
            JOIN quotation q ON s.id_quotation = q.id_quotation
            JOIN person p ON q.id_client = p.id_person
            WHERE s.sale_date BETWEEN :start_date AND :end_date
            ORDER BY s.sale_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start_date' => $startDate . ' 00:00:00',
        ':end_date' => $endDate . ' 23:59:59'
    ]);

    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calcular el resumen
    $total_quoted = 0;
    $total_sold = 0;

    foreach ($details as &$item) {
        $total_quoted += (float)$item['quote_amount'];
        $total_sold += (float)$item['sale_amount'];
        // Formatear fechas para la vista
        $item['quotation_date'] = (new DateTime($item['quotation_date']))->format('d/m/Y');
        $item['sale_date'] = (new DateTime($item['sale_date']))->format('d/m/Y');
    }

    $conversion_rate = ($total_quoted > 0) ? ($total_sold / $total_quoted) * 100 : 0;

    $summary = [
        'total_quoted' => $total_quoted,
        'total_sold' => $total_sold,
        'conversion_rate' => $conversion_rate
    ];

    // 4. Devolver los datos
    echo json_encode(['success' => true, 'data' => ['summary' => $summary, 'details' => $details]]);

} catch (Exception $e) {
    error_log("Error en reporte de conversión: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar el reporte.']);
}