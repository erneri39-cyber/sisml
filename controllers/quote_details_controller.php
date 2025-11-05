<?php
/**
 * Endpoint para obtener los detalles de una cotización específica.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Quotation.php';

$id_quotation = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_quotation <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de cotización no válido.']);
    exit;
}

try {
    $quoteModel = new Quotation($pdo);
    // Modificamos la llamada para usar un método que una las tablas
    $stmt = $pdo->prepare(
        "SELECT dq.id_product, dq.id_batch, p.name as product_name, dq.quantity, dq.price_quoted 
         FROM detail_quotation dq JOIN product p ON dq.id_product = p.id_product 
         WHERE dq.id_quotation = :id"
    );
    $stmt->execute(['id' => $id_quotation]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $details]);

} catch (PDOException $e) {
    error_log("Error al obtener detalles de cotización: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos.']);
}