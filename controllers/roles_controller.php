<?php
/**
 * Controlador para la gestión de Roles y Permisos.
 */

session_start();
header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('manage_roles', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para gestionar roles.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Role.php';
require_once dirname(__DIR__) . '/models/Permission.php';

$roleModel = new Role($pdo);
$permissionModel = new Permission($pdo);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['action']) && $_GET['action'] === 'permissions') {
                // Devuelve todos los permisos disponibles en el sistema
                $permissions = $permissionModel->getAll();
                echo json_encode(['success' => true, 'data' => $permissions]);
            } elseif (isset($_GET['id'])) {
                // Devuelve un rol específico y sus permisos asignados
                $id = (int)$_GET['id'];
                $role = $roleModel->findById($id);
                if ($role) {
                    // CORRECCIÓN: Obtener los permisos y asignarlos a una propiedad 'permissions' dentro del array del rol.
                    // El modelo ya devuelve los permisos, pero nos aseguramos de que se estructuren bien.
                    $role['permissions'] = $roleModel->getPermissionIdsByRoleId($id);
                    // Obtiene la lista de usuarios activos con este rol
                    $role['active_users'] = $roleModel->getActiveUsersByRoleId($id);

                    echo json_encode(['success' => true, 'data' => $role]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Rol no encontrado.']);
                }
            } else {
                // Devuelve todos los roles
                $roles = $roleModel->getAll();
                echo json_encode(['success' => true, 'data' => $roles]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['name']) || !isset($data['permissions'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre del rol es obligatorio.']);
                exit;
            }

            // Verificar si el nombre del rol ya existe
            $existingRole = $pdo->prepare("SELECT id_rol FROM rol WHERE name = :name");
            $existingRole->execute([':name' => $data['name']]);
            if ($existingRole->fetch()) {
                http_response_code(409); // 409 Conflict
                echo json_encode(['success' => false, 'message' => 'El nombre de este rol ya está en uso. Por favor, elija otro.']);
                exit;
            }

            // Crear el rol y asignar permisos en una sola transacción
            $newRoleId = $roleModel->create($data);
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $roleModel->updatePermissions($newRoleId, $data['permissions']);
            }
            $newRole = $roleModel->findById($newRoleId);
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Rol creado exitosamente.', 'data' => $newRole]);
            break;

        case 'PUT':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de rol no válido.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['name']) || !isset($data['permissions'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos incompletos para actualizar.']);
                exit;
            }

            // Actualizar nombre y permisos
            $roleModel->update($id, ['name' => $data['name']]);
            $roleModel->updatePermissions($id, $data['permissions']);

            echo json_encode(['success' => true, 'message' => 'Rol y permisos actualizados exitosamente.']);
            break;

        case 'DELETE':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de rol no válido.']);
                exit;
            }

            if ($roleModel->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Rol eliminado exitosamente.']);
            } else {
                http_response_code(403); // Forbidden
                echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el rol. Es posible que sea un rol protegido.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en roles_controller: " . $e->getMessage());
    // Mejorar el manejo de errores para duplicados en la actualización
    if ($e instanceof PDOException && $e->getCode() == '23000') {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Error: El nombre del rol ya está en uso.']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>