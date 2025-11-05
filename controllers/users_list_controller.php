<?php
/**
 * Endpoint para obtener una lista de todos los usuarios activos (vendedores).
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    $sql = "SELECT u.id_user, p.name 
            FROM user u
            JOIN person p ON u.id_person = p.id_person
            WHERE u.is_active = 1
            ORDER BY p.name ASC";
    
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $users]);
} catch (Exception $e) {
    error_log("Error al obtener lista de usuarios: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener la lista de vendedores.']);
}