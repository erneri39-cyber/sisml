<?php
/**
 * Endpoint para exportar el reporte de valor de inventario a un archivo CSV.
 */

session_start();

// 1. Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado.');
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_inventory_report', $_SESSION['user_permissions'])) {
    die('No tienes permisos para realizar esta acción.');
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    // 2. Obtener filtros de la URL
    $id_branch = $_SESSION['id_branch'];
    $categoryId = $_GET['category_id'] ?? null;
    $labId = $_GET['lab_id'] ?? null;

    $params = [':id_branch' => $id_branch];
    $whereClauses = ["p.id_branch = :id_branch", "b.stock > 0"];

    // 3. Construir la consulta SQL con los filtros
    $sql = "SELECT
                p.code,
                p.name AS product_name,
                c.name AS category_name,
                l.nombre AS laboratory_name,
                SUM(b.stock) AS total_stock,
                SUM(b.stock * b.purchase_price) AS total_cost_value,
                SUM(b.stock * b.sale_price) AS total_sale_value
            FROM product p
            JOIN batch b ON p.id_product = b.id_product
            LEFT JOIN category c ON p.id_category = c.id_category
            LEFT JOIN laboratorio l ON p.id_laboratorio = l.id_laboratorio";

    if ($categoryId) {
        $whereClauses[] = "p.id_category = :category_id";
        $params[':category_id'] = $categoryId;
    }
    if ($labId) {
        $whereClauses[] = "p.id_laboratorio = :lab_id";
        $params[':lab_id'] = $labId;
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }
    $sql .= " GROUP BY p.id_product, p.code, p.name, c.name, l.nombre ORDER BY p.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Configurar cabeceras y generar el CSV
    $filename = "reporte_valor_inventario_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Codigo', 'Producto', 'Categoria', 'Laboratorio', 'Stock Total', 'Valor Total (Costo)', 'Valor Total (Venta)']);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;

} catch (Exception $e) {
    error_log("Error al exportar CSV de valor de inventario: " . $e->getMessage());
    die("Error al generar el archivo de exportación.");
}