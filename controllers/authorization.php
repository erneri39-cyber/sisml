<?php
/**
 * Endpoint para verificar la contraseña de un supervisor para autorizaciones.
 */

session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté logueado y que la solicitud sea POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once '../db_connect.php'; // Corregida la ruta a db_connect.php

$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'La contraseña no puede estar vacía.']);
    exit;
}

try {
    // Buscar un usuario con rol 'Administrador' o 'Gerente'
    // En una implementación real, podríamos pedir también el usuario del supervisor
    // para mayor seguridad, pero para este ejemplo validamos contra cualquier supervisor.    
    // MODIFICADO: Se une con la tabla 'rol' para verificar el nombre del rol.
    $stmt = $pdo->prepare("SELECT u.password 
                           FROM user u
                           JOIN rol r ON u.id_rol = r.id_rol
                           WHERE r.name IN ('Administrador', 'Gerente')");
    $stmt->execute();
    $supervisors = $stmt->fetchAll();

    $authorized = false;
    foreach ($supervisors as $supervisor) {
        if (password_verify($password, $supervisor['password'])) {
            $authorized = true;
            break;
        }
    }

    if ($authorized) {
        echo json_encode(['success' => true, 'message' => 'Autorización concedida.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contraseña de supervisor incorrecta.']);
    }
} catch (PDOException $e) {
    error_log("Error de autorización: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
}
?>