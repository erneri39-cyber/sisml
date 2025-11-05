<?php
/**
 * Endpoint para exportar el reporte de productos próximos a vencer a un archivo CSV.
 */

session_start();

// 1. Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado.');
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_expiration_report', $_SESSION['user_permissions'])) {
    die('No tienes permisos para realizar esta acción.');
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    $id_branch = $_SESSION['id_branch'];
    $days_threshold = 90;

    // 2. Obtener los datos (misma consulta que el reporte original)
    $sql = "SELECT
                p.code AS product_code,
                p.name AS product_name,
                l.nombre AS laboratory_name,
                b.batch_number,
                b.stock,
                b.expiration_date
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
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Configurar cabeceras para la descarga del CSV
    $filename = "reporte_vencimientos_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // 4. Abrir el flujo de salida de PHP para escribir el CSV
    $output = fopen('php://output', 'w');

    // 5. Escribir la fila de encabezado del CSV
    fputcsv($output, ['Codigo Producto', 'Producto', 'Laboratorio', 'Numero de Lote', 'Stock', 'Fecha de Vencimiento']);

    // 6. Escribir los datos en el CSV
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    error_log("Error al exportar CSV de vencimientos: " . $e->getMessage());
    die("Error al generar el archivo de exportación.");
}