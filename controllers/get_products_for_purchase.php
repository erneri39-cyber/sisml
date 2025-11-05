<?php
/**
 * Endpoint para obtener una lista de productos para el modal de búsqueda en el módulo de Compras.
 * Retorna campos clave como nombre, código, laboratorio, medida y categoría para la búsqueda avanzada.
 */

session_start();
header('Content-Type: application/json');

// 1. Protección y Conexión
if (!isset($_SESSION['user_id']) || !isset($_SESSION['id_branch'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once '../db_connect.php';

try {
    // 2. Parámetros de Búsqueda
    $searchTerm = $_GET['search'] ?? '';
    $params = [];

    // 3. Consulta SQL Avanzada
    $sql = "SELECT
                p.id_product,
                p.code AS product_code,
                p.name AS product_name,
                m.descripcion AS medida,
                c.name AS category_name,
                l.nombre AS laboratorio_name
            FROM product p
            LEFT JOIN medida m ON p.id_medida = m.id_medida
            LEFT JOIN category c ON p.id_category = c.id_category
            LEFT JOIN laboratorio l ON p.id_laboratorio = l.id_laboratorio";

    if (!empty($searchTerm)) {
        // CORRECCIÓN: Se quitan los alias 'p.' de la subconsulta, ya que no son válidos aquí.
        $sql .= " WHERE (name LIKE :searchTerm OR code LIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $sql .= " ORDER BY p.name ASC";

    // 4. Ejecución y Devolución
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $products]);

} catch (Exception $e) {
    error_log("Error al buscar productos para compra: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}