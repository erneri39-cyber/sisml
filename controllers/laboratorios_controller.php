<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Laboratorio.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$model = new Laboratorio($pdo);

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                $data = $model->findById($id);
                if (!$data) http_response_code(404);
            } else {
                $data = $model->getAll();
            }
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['nombre'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
                exit;
            }
            $newId = $model->create($input);
            $newData = $model->findById($newId);
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Laboratorio creado.', 'data' => $newData]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['nombre'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
                exit;
            }
            $model->update($id, $input);
            $updatedData = $model->findById($id);
            echo json_encode(['success' => true, 'message' => 'Laboratorio actualizado.', 'data' => $updatedData]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
                exit;
            }
            $model->delete($id);
            echo json_encode(['success' => true, 'message' => 'Laboratorio eliminado.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    if ($e->getCode() == '23000') { // Error de integridad (nombre duplicado)
        echo json_encode(['success' => false, 'message' => 'Error: El nombre del laboratorio ya existe.']);
    } else {
        error_log("Error en laboratorios_controller: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en laboratorios_controller: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error inesperado.']);
}
?>