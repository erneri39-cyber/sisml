<?php
/**
 * Endpoint para obtener la lista de todas las cotizaciones generadas.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';

try {
    // --- LÓGICA PARA EXPIRAR COTIZACIONES ---
    // Primero, actualizamos el estado de las cotizaciones vigentes que tienen más de 15 días.
    // Corregido: El estado 'Pendiente' es el que expira.
    $sqlExpire = "UPDATE quotation SET status = 'Cancelada' 
                  WHERE status = 'Pendiente' AND quotation_date < DATE_SUB(NOW(), INTERVAL 15 DAY)";
    $pdo->exec($sqlExpire);
    // --- FIN DE LA LÓGICA DE EXPIRACIÓN ---


    // Recoger las fechas del filtro, si existen
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $status = $_GET['status'] ?? null;
    $clientName = $_GET['client_name'] ?? null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15; // Número de registros por página
    $offset = ($page - 1) * $limit;

    // Consulta para obtener las cotizaciones con los nombres del cliente y del usuario
    $sql = "SELECT 
                q.id_quotation,
                client_person.name AS client_name,
                user_person.name AS user_name,
                b.name AS branch_name,
                q.quotation_date,
                q.total_amount,
                q.status
            FROM quotation q
            JOIN person AS client_person ON q.id_client = client_person.id_person
            JOIN user u ON q.id_user = u.id_user
            JOIN person AS user_person ON u.id_person = user_person.id_person
            JOIN branch b ON q.id_branch = b.id_branch
            ";

    $params = [];
    $whereClauses = [];

    if ($startDate && $endDate) {
        // Añadir la cláusula WHERE para filtrar por rango de fechas
        // Se añade la hora al final para incluir todo el día de la fecha de fin.
        $whereClauses[] = "q.quotation_date BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate . ' 00:00:00';
        $params[':end_date'] = $endDate . ' 23:59:59';
    }

    if ($status) {
        $whereClauses[] = "q.status = :status";
        $params[':status'] = $status;
    }

    if ($clientName) {
        // Usamos LIKE para búsquedas parciales (ej: "juan" encontrará "Juan Perez")
        $whereClauses[] = "client_person.name LIKE :client_name";
        $params[':client_name'] = '%' . $clientName . '%';
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    // --- INICIO: Lógica de Paginación ---
    // 1. Obtener el conteo total de registros que coinciden con los filtros
    $countSql = "SELECT COUNT(q.id_quotation) FROM quotation q";
    if (!empty($whereClauses)) {
        // Reutilizamos las cláusulas WHERE, pero necesitamos ajustar los joins
        $countSql = "SELECT COUNT(q.id_quotation) 
                     FROM quotation q
                     JOIN person AS client_person ON q.id_client = client_person.id_person";
        $countSql .= " WHERE " . implode(' AND ', $whereClauses);
    }
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();

    // 2. Añadir orden y límites a la consulta principal
    $sql .= " ORDER BY q.quotation_date DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    // Necesitamos especificar el tipo de dato para limit y offset
    $stmt->bindParam(':limit', $params[':limit'], PDO::PARAM_INT);
    $stmt->bindParam(':offset', $params[':offset'], PDO::PARAM_INT);
    foreach ($params as $key => &$val) {
        if ($key !== ':limit' && $key !== ':offset') {
            $stmt->bindParam($key, $val);
        }
    }
    $stmt->execute($params);
    $quotes = $stmt->fetchAll();

    // Formatear la fecha para una mejor visualización
    foreach ($quotes as &$quote) {
        $quote['quotation_date'] = (new DateTime($quote['quotation_date']))->format('d/m/Y H:i');
    }

    // Devolver los datos junto con la información de paginación
    echo json_encode([
        'success' => true, 
        'data' => $quotes,
        'pagination' => [
            'total_records' => $totalRecords,
            'current_page' => $page,
            'per_page' => $limit,
            'total_pages' => ceil($totalRecords / $limit)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error al obtener la lista de cotizaciones: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos.']);
}