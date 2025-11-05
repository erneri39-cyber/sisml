<?php
/**
 * Controlador para la gestión de Categorías de productos (CRUD).
 */

session_start();
header('Content-Type: application/json');

// 1. Verificar autenticación y permisos (si es necesario)
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

// 2. Incluir dependencias
require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Category.php';

$categoryModel = new Category($pdo);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $category = $categoryModel->findById($id);
                if ($category) {
                    echo json_encode(['success' => true, 'data' => $category]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Categoría no encontrada.']);
                }
            } else {
                $categories = $categoryModel->getAll();
                echo json_encode(['success' => true, 'data' => $categories]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
                exit;
            }

            $newCategoryId = $categoryModel->create($data);
            $newCategory = $categoryModel->findById($newCategoryId);
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Categoría creada exitosamente.', 'data' => $newCategory]);
            break;

        case 'PUT':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id === 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de categoría no proporcionado.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
                exit;
            }

            $success = $categoryModel->update($id, $data);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Categoría actualizada exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la categoría.']);
            }
            break;

        case 'DELETE':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id === 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de categoría no proporcionado.']);
                exit;
            }

            $success = $categoryModel->delete($id);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Categoría eliminada exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la categoría.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>