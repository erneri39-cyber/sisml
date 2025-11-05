<?php
/**
 * Controlador para la gestión de Usuarios (CRUD).
 */

session_start();
header('Content-Type: application/json');

// Verificar autenticación y permisos (ej. solo administradores pueden gestionar usuarios)
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

// Comprobación de permisos para gestionar usuarios
if (!isset($_SESSION['user_permissions']) || !in_array('manage_users', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para gestionar usuarios.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Role.php'; // Necesario para obtener roles si no se hace en el modelo User
require_once dirname(__DIR__) . '/models/Branch.php'; // Necesario para obtener sucursales si no se hace en el modelo User

$userModel = new User($pdo);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $user = $userModel->findById((int)$_GET['id']);
                if ($user) {
                    echo json_encode(['success' => true, 'data' => $user]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
                }
            } elseif (isset($_GET['action'])) {
                if ($_GET['action'] === 'roles') {
                    $roles = $userModel->getRoles();
                    echo json_encode(['success' => true, 'data' => $roles]);
                } elseif ($_GET['action'] === 'branches') {
                    $branches = $userModel->getBranches();
                    echo json_encode(['success' => true, 'data' => $branches]);
                } elseif ($_GET['action'] === 'check_username') {
                    $username = $_GET['username'] ?? '';
                    $existingUser = $userModel->findByUsername($username);
                    echo json_encode([
                        'success' => true, 
                        'exists' => (bool)$existingUser,
                        'data' => $existingUser // Devolver el usuario completo (o false)
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Acción GET no válida.']);
                }
            } else {
                $users = $userModel->getAll();
                echo json_encode(['success' => true, 'data' => $users]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['username']) || empty($data['password']) || empty($data['person_name']) || empty($data['document_number']) || empty($data['id_rol']) || empty($data['id_branch'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos incompletos para crear usuario.']);
                exit;
            }
            // Verificar si el nombre de usuario ya existe
            if ($userModel->findByUsername($data['username'])) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso.']);
                exit;
            }

            $newUserId = $userModel->create($data);
            if ($newUserId) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente.', 'id_user' => $newUserId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al crear el usuario.']);
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0 || empty($data['username']) || empty($data['person_name']) || empty($data['document_number']) || empty($data['id_rol']) || empty($data['id_branch'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de usuario o datos incompletos para actualizar.']);
                exit;
            }
            // Verificar si el nombre de usuario ya existe y no es el usuario actual
            $existingUser = $userModel->findByUsername($data['username']);
            if ($existingUser && $existingUser['id_user'] != $id) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso por otro usuario.']);
                exit;
            }

            if ($userModel->update($id, $data)) {
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario.']);
            }
            break;

        case 'DELETE':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de usuario no válido.']);
                exit;
            }
            // No permitir que un usuario se elimine a sí mismo
            if ($id == $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta.']);
                exit;
            }

            if ($userModel->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el usuario.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en users_controller: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>