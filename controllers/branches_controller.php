<?php
/**
 * Controlador para la gestión de Sucursales (CRUD).
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Branch.php';

$method = $_SERVER['REQUEST_METHOD'];
$branchModel = new Branch($pdo);

switch ($method) {
    case 'GET':
        handleGet($branchModel);
        break;
    case 'POST':
        handlePost($branchModel);
        break;
    case 'PUT':
        handlePut($branchModel);
        break;
    case 'DELETE':
        handleDelete($branchModel);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no soportado.']);
        break;
}

function handleGet(Branch $model) {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $branch = $model->findById($id);
        if ($branch) {
            echo json_encode(['success' => true, 'data' => $branch]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sucursal no encontrada.']);
        }
    } else {
        $branches = $model->getAll();
        echo json_encode(['success' => true, 'data' => $branches]);
    }
}

function handlePost(Branch $model) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
        return;
    }

    try {
        $newId = $model->create($data);
        $newBranch = $model->findById($newId);
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Sucursal creada exitosamente.', 'data' => $newBranch]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear la sucursal.']);
    }
}

function handlePut(Branch $model) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Falta el ID de la sucursal.']);
        return;
    }

    $id = (int)$_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
        return;
    }

    try {
        $success = $model->update($id, $data);
        if ($success) {
            $updatedBranch = $model->findById($id);
            echo json_encode(['success' => true, 'message' => 'Sucursal actualizada exitosamente.', 'data' => $updatedBranch]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la sucursal.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la sucursal.']);
    }
}

function handleDelete(Branch $model) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Falta el ID de la sucursal.']);
        return;
    }

    $id = (int)$_GET['id'];

    try {
        $success = $model->delete($id);
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Sucursal eliminada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la sucursal.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la sucursal.']);
    }
}

?>