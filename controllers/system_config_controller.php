<?php
/**
 * Controlador para la gestión de la Configuración del Sistema.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('manage_system_settings', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/SystemConfig.php';

$configModel = new SystemConfig($pdo);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $configs = $configModel->getAllAsAssoc();
            echo json_encode(['success' => true, 'data' => $configs]);
            break;

        case 'POST':
            // Los datos ahora vienen de $_POST y $_FILES, no de un JSON
            $data = $_POST;
            if (empty($data)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No se recibieron datos para actualizar.']);
                exit;
            }

            // Manejar la subida del logo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['logo'];
                $allowedTypes = ['image/png', 'image/jpeg'];
                $maxSize = 2 * 1024 * 1024; // 2 MB

                if (!in_array($file['type'], $allowedTypes)) {
                    throw new Exception('Tipo de archivo no permitido. Solo se aceptan PNG y JPG.');
                }
                if ($file['size'] > $maxSize) {
                    throw new Exception('El archivo es demasiado grande. El tamaño máximo es 2 MB.');
                }

                $uploadDir = dirname(__DIR__) . '/assets/img/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Usar un nombre de archivo consistente para el logo
                $newFileName = 'logo_empresa.png';
                $destination = $uploadDir . $newFileName;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception('Error al mover el archivo subido.');
                }

                // Añadir la ruta del logo a los datos que se guardarán en la BD
                $data['EMPRESA_LOGO_PATH'] = 'assets/img/' . $newFileName;
            }

            $userId = $_SESSION['user_id'];
            $ipAddress = $_SERVER['REMOTE_ADDR'];

            $configModel->updateSettings($data, $userId, $ipAddress);
            echo json_encode(['success' => true, 'message' => 'Configuración guardada exitosamente.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en system_config_controller: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>