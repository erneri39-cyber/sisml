<?php
/**
 * Controlador para obtener los datos del Reporte de Auditoría.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['user_permissions']) || !in_array('view_audit_log', $_SESSION['user_permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este reporte.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    $userIdFilter = $_GET['user_id'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;

    $params = [];
    $sql = "SELECT 
                al.change_date,
                p.name as user_name,
                al.config_key,
                al.old_value,
                al.new_value,
                al.ip_address
            FROM system_audit_log al
            JOIN user u ON al.id_user = u.id_user
            JOIN person p ON u.id_person = p.id_person";
    
    $whereClauses = [];
    if ($userIdFilter) {
        $whereClauses[] = "al.id_user = :user_id";
        $params[':user_id'] = $userIdFilter;
    }
    if ($startDate && $endDate) {
        $whereClauses[] = "DATE(al.change_date) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate;
        $params[':end_date'] = $endDate;
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $sql .= " ORDER BY al.change_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $logs]);
} catch (Exception $e) {
    error_log("Error en audit_log_controller: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener los registros de auditoría.']);
}
?>