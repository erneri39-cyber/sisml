<?php
/**
 * Endpoint para obtener productos y sus lotes disponibles para el TPV de venta rápida.
 * Optimizado para Select2.
 */

session_start();
header('Content-Type: application/json');

// 1. Proteger el endpoint
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

// 2. Incluir la conexión a la base de datos
require_once '../db_connect.php';

try {
    $searchTerm = $_GET['term'] ?? '';
    $id_branch = $_SESSION['id_branch'] ?? null;

    if (empty($id_branch)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Error de Sesión: ID de sucursal no definido.']);
        exit;
    }

    // Consulta para obtener productos y sus lotes con stock > 0 en la sucursal actual
    // CORRECCIÓN: Se seleccionan TODOS los campos de precio de la tabla 'batch'.
    $sql = "SELECT
                p.id_product,
                p.name AS product_name,
                p.code AS product_code,
                b.id_batch,
                b.batch_number,
                b.expiration_date,
                b.stock,
                b.sale_price,
                b.sale_price_2,
                b.sale_price_3,
                b.sale_price_b,
                b.sale_price_c
            FROM product p
            JOIN batch b ON p.id_product = b.id_product
            WHERE b.id_branch = :id_branch AND b.stock > 0";

    $params = [':id_branch' => $id_branch];

    if (!empty($searchTerm)) {
        $sql .= " AND (p.name LIKE :searchTerm OR p.code LIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $sql .= " ORDER BY p.name, b.expiration_date ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear los resultados para Select2
    $results = array_map(function($p) {
        $precioUnidad = number_format(floatval($p['sale_price_3']), 2); // Usamos sale_price_3 como precio de unidad por defecto
        return [
            'id' => $p['id_batch'],
            'text' => "[{$p['product_code']}] {$p['product_name']} | Lote: {$p['batch_number']} | Stock: {$p['stock']} | P. Unidad: \${$precioUnidad}",
            'data' => $p // Pasar todos los datos del lote para usar en JS
        ];
    }, $products);

    echo json_encode(['results' => $results]);

} catch (Exception $e) {
    error_log("Error al obtener productos para TPV: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor al buscar productos.', 'error_detail' => $e->getMessage()]);
}
?>