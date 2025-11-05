<?php
/**
 * Endpoint para exportar el historial de ajustes de inventario a un archivo CSV.
 */

session_start();

// 1. Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado.');
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_adjustment_report', $_SESSION['user_permissions'])) {
    die('No tienes permisos para realizar esta acción.');
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    // 2. Obtener filtros de la URL
    $productId = $_GET['product_id'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;

    $params = [];
    $whereClauses = [];

    // 3. Construir la consulta SQL con los filtros
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
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Configurar cabeceras para la descarga del CSV
    $filename = "reporte_ajustes_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Fecha y Hora', 'Producto', 'Nº Lote', 'Tipo de Ajuste', 'Cantidad', 'Motivo', 'Realizado por']);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;

} catch (Exception $e) {
    error_log("Error al exportar CSV de ajustes: " . $e->getMessage());
    die("Error al generar el archivo de exportación.");
}