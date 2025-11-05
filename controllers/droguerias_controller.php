<?php
/**
 * Controlador para la gestión de Droguerías (CRUD).
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Drogueria.php';

$drogueriaModel = new Drogueria($pdo);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $drogueria = $drogueriaModel->findById($id);
                if ($drogueria) {
                    echo json_encode(['success' => true, 'data' => $drogueria]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Droguería no encontrada.']);
                }
            } else {
                $droguerias = $drogueriaModel->getAll();
                echo json_encode(['success' => true, 'data' => $droguerias]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['nombre'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
                exit;
            }
            $newId = $drogueriaModel->create($data);
            $newDrogueria = $drogueriaModel->findById($newId);
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Droguería creada exitosamente.', 'data' => $newDrogueria]);
            break;

        case 'PUT':
            $id = (int)$_GET['id'];
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de droguería no proporcionado.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['nombre'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
                exit;
            }

            $drogueriaModel->update($id, $data);
            $updatedDrogueria = $drogueriaModel->findById($id);
            echo json_encode(['success' => true, 'message' => 'Droguería actualizada exitosamente.', 'data' => $updatedDrogueria]);
            break;

        case 'DELETE':
            $id = (int)$_GET['id'];
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de droguería no proporcionado.']);
                exit;
            }

            if ($drogueriaModel->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Droguería eliminada exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la droguería.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
    }
} catch (PDOException $e) {
    error_log("Error de base de datos en droguerias_controller: " . $e->getMessage());
    http_response_code(500);
    // Verificar si es un error de duplicado (código 23000)
    if ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'message' => 'Error: El nombre de la droguería ya existe.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
    }
} catch (Exception $e) {
    error_log("Error general en droguerias_controller: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error inesperado.']);
}

?>