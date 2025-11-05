<?php
/**
 * Controlador para la gestión de Clientes (CRUD).
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Client.php';

$clientModel = new Client($pdo);
$method = $_SERVER['REQUEST_METHOD'];

try {
    // El permiso se verifica para todas las acciones excepto GET
    if ($method !== 'GET' && (!isset($_SESSION['user_permissions']) || !in_array('manage_clients', $_SESSION['user_permissions']))) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para gestionar clientes.']);
        exit;
    }

    switch ($method) {
        case 'GET':
            // --- Lógica para la validación de unicidad ---
            if (isset($_GET['action']) && $_GET['action'] === 'check_uniqueness') {
                $field = $_GET['field'] ?? '';
                $value = $_GET['value'] ?? '';
                $ignoreId = isset($_GET['ignore_id']) ? (int)$_GET['ignore_id'] : null;

                if (empty($field) || empty($value)) {
                    echo json_encode(['exists' => false]);
                    exit;
                }

                $client = $clientModel->findByField($field, $value);
                $exists = false;
                if ($client) {
                    // El campo existe. Si no estamos editando (ignoreId es null) o si el ID encontrado es diferente al que ignoramos, entonces es un duplicado.
                    if ($ignoreId === null || $client['id_person'] != $ignoreId) {
                        $exists = true;
                    }
                }
                echo json_encode(['exists' => $exists]);
                exit; // Salir después de manejar la acción
            }
            elseif (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $client = $clientModel->findById($id);
                if ($client) {
                    echo json_encode(['success' => true, 'data' => $client]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
                }
            } elseif (isset($_GET['for_tpv'])) {
                // Lógica específica para Select2 en los TPV.
                // Devuelve los datos en el formato { results: [...] } que Select2 espera.
                $searchTerm = $_GET['term'] ?? '';
                $clients = $clientModel->search($searchTerm);
                echo json_encode(['results' => $clients]);
            } else {
                // Lógica para obtener todos los clientes (ej. para la página de gestión de clientes).
                $clients = $clientModel->getAll();
                echo json_encode(['success' => true, 'data' => $clients]); // Formato estándar para tablas.
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['name']) || empty($data['document_number'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nombre y Número de Documento son obligatorios.']);
                exit;
            }

            $newClientId = $clientModel->create($data);
            $newClient = $clientModel->findById($newClientId);
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Cliente creado exitosamente.', 'data' => $newClient]);
            break;

        case 'PUT':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id === 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['name']) || empty($data['document_number'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nombre y Número de Documento son obligatorios.']);
                exit;
            }

            if ($clientModel->update($id, $data)) {
                echo json_encode(['success' => true, 'message' => 'Cliente actualizado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el cliente.']);
            }
            break;

        case 'DELETE':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id === 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
                exit;
            }

            if ($clientModel->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Cliente eliminado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el cliente.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en clients_controller: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}