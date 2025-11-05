<?php
/**
 * Endpoint para obtener todos los productos y sus lotes disponibles para la venta
 * en la sucursal del usuario logueado.
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
    // Determinar si se deben mostrar todos los productos o solo los que tienen stock
    $showAll = isset($_GET['all']) && $_GET['all'] === 'true';
    // Obtener el término de búsqueda de Select2
    $searchTerm = $_GET['term'] ?? '';
    
    // 3. CORRECCIÓN CRÍTICA: Inicializar $params como array vacío.
    $params = [];

    if ($showAll) {
        // --- Lógica para COMPRAS (Mostrar TODOS los productos, sin filtro de stock/sucursal) ---
        $sql = "SELECT 
                    p.id_product,
                    p.name AS product_name,
                    p.code AS product_code
                FROM product p
                WHERE p.is_active = 1"; // Se mantiene el filtro de activo
        
        if (!empty($searchTerm)) {
            $sql .= " AND (p.name LIKE :searchTerm OR p.code LIKE :searchTerm)";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }
        $sql .= " ORDER BY p.name ASC"; // No se necesita GROUP BY si solo seleccionamos de 'product'
        
    } else {
        // --- Lógica para TPV/VENTAS (Mostrar lotes con STOCK en la sucursal actual) ---
        
        $id_branch = $_SESSION['id_branch'] ?? null;

        if (empty($id_branch)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Error de Sesión: ID de sucursal no definido.']);
            exit;
        }
        
        // El filtro de sucursal se añade SOLO aquí:
        $params = ['id_branch' => $id_branch];
        $sql = "SELECT 
                    p.id_product,
                    p.name AS product_name,
                    p.code AS product_code,
                    b.id_batch,
                    b.batch_number,
                    b.stock,
                    b.purchase_price,
                    b.sale_price,
                    b.sale_price_b,
                    b.sale_price_c,
                    b.sale_price_2,
                    b.sale_price_3
                FROM product p
                JOIN batch b ON p.id_product = b.id_product
                WHERE b.id_branch = :id_branch"; // El filtro va por el lote
        
        $sql .= " AND b.stock > 0";
        
        // CORRECCIÓN: Se descomenta el bloque para activar el filtrado por término de búsqueda.
        if (!empty($searchTerm)) {
            $sql .= " AND (p.name LIKE :searchTerm OR p.code LIKE :searchTerm)";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }
        $sql .= " ORDER BY p.name, b.expiration_date";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params); // Ahora $params solo contiene lo que $sql necesita
    $products = $stmt->fetchAll();
    
    // --- Lógica de Formateo de Respuesta ---
    if (empty($searchTerm) && !$showAll) {
        // Si no hay término de búsqueda y no es para compras, es una solicitud de precarga (ej. cotizaciones.php).
        // Devolver en el formato { success: true, data: [...] }
        echo json_encode(['success' => true, 'data' => $products]);
    } else {
        // Si hay término de búsqueda o es para compras, formatear para Select2.
        $results = array_map(function($p) use ($showAll) {
            $text = $p['product_code'] . ' - ' . $p['product_name'];
            if (!$showAll) {
                 $text .= ' (Lote: ' . $p['batch_number'] . ' | Stock: ' . $p['stock'] . ')';
            }
    
            return [
                'id' => $showAll ? $p['id_product'] : $p['id_batch'],
                'text' => $text,
                'data' => $p
            ];
        }, $products);
    
        // Devolver en formato para Select2
        echo json_encode(['results' => $results, 'pagination' => ['more' => false]]);
    }
    
} catch (Exception $e) {
    error_log("Error al obtener productos para TPV/Compra: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor al buscar productos.', 'error_detail' => $e->getMessage()]);
}
?>