<?php
/**
 * Endpoint para exportar el historial de compras de un cliente a un archivo CSV.
 */

session_start();

// 1. Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado.');
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_client_purchase_history', $_SESSION['user_permissions'])) {
    die('No tienes permisos para realizar esta acción.');
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    // 2. Obtener el ID del cliente de la URL
    $clientId = $_GET['client_id'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;

    if (!$clientId) {
        die('ID de cliente no proporcionado.');
    }

    // 3. Obtener el nombre del cliente para el nombre del archivo
    $stmtClient = $pdo->prepare("SELECT name FROM person WHERE id_person = :client_id");
    $stmtClient->execute([':client_id' => $clientId]);
    $clientName = $stmtClient->fetchColumn();
    $safeClientName = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $clientName));

    // 4. Construir la consulta SQL con los filtros
    $params = [':client_id' => $clientId];
    $whereClauses = ["s.id_client = :client_id"];

    $sql = "SELECT
                s.id_sale,
                s.sale_date,
                up.name as user_name,
                s.total_amount,
                (SELECT COALESCE(SUM(ds.discount), 0) FROM detail_sale ds WHERE ds.id_sale = s.id_sale) as total_discount
            FROM sale s
            JOIN user u ON s.id_user = u.id_user
            JOIN person up ON u.id_person = up.id_person";

    if ($startDate && $endDate) {
        $whereClauses[] = "DATE(s.sale_date) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate;
        $params[':end_date'] = $endDate;
    }

    $sql .= " WHERE " . implode(' AND ', $whereClauses) . " ORDER BY s.sale_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Configurar cabeceras para la descarga del CSV
    $filename = "historial_compras_" . $safeClientName . "_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['N Venta', 'Fecha y Hora', 'Vendedor', 'Monto Bruto', 'Descuentos', 'Monto Neto']);

    // 6. Escribir los datos en el CSV
    foreach ($sales as $sale) {
        $netAmount = (float)$sale['total_amount'] - (float)$sale['total_discount'];
        fputcsv($output, [
            $sale['id_sale'],
            $sale['sale_date'],
            $sale['user_name'],
            $sale['total_amount'],
            $sale['total_discount'],
            number_format($netAmount, 2, '.', '')
        ]);
    }
    fclose($output);
    exit;

} catch (Exception $e) {
    error_log("Error al exportar CSV de historial de cliente: " . $e->getMessage());
    die("Error al generar el archivo de exportación.");
}