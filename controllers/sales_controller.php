<?php
/**
 * Controlador para procesar nuevas ventas.
 * Recibe los datos de la venta en formato JSON.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. Verificar que el usuario esté autenticado y la solicitud sea POST.
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

// 2. Incluir dependencias.
require_once '../db_connect.php';
require_once dirname(__DIR__) . '/DteService.php'; // Ruta corregida
// Incluimos las definiciones de las excepciones personalizadas
require_once dirname(__DIR__) . '/exceptions/InsufficientStockException.php';
require_once dirname(__DIR__) . '/exceptions/RecordNotFoundException.php';
require_once dirname(__DIR__) . '/exceptions/BusinessLogicException.php';


// 3. Obtener los datos de la venta del cuerpo de la solicitud (body).
$data = json_decode(file_get_contents('php://input'), true);

// CORRECCIÓN: Se ajusta para aceptar 'header' (enviado desde TPV de Ruta) o 'saleHeader' (enviado desde TPV principal).
// Si no existen, se asume que los datos del header están en el nivel raíz (para TPV de Ruta).
$saleHeader = $data['header'] ?? $data['saleHeader'] ?? array_diff_key($data, ['saleDetails' => 0]);
$saleDetails = $data['saleDetails'] ?? null;

// 4. Validar que los datos esenciales estén presentes.
if (!$saleHeader || !$saleDetails || empty($saleDetails)) {
    echo json_encode(['success' => false, 'message' => 'Datos de venta incompletos o inválidos.']);
    exit;
}

// 5. Añadir datos de la sesión al encabezado de la venta.
$saleHeader['id_user'] = $_SESSION['user_id'];
$saleHeader['id_branch'] = $_SESSION['id_branch'];
$saleHeader['sale_date'] = date('Y-m-d H:i:s'); // Fecha y hora actual
$saleHeader['is_rutero_sale'] = $saleHeader['is_rutero_sale'] ?? false; // Asegurar que el valor exista
$saleHeader['payment_method'] = $saleHeader['payment_method'] ?? 'Efectivo'; // Asegurar que el valor exista, ruta_tpv.php lo fija a 'Crédito'
$saleHeader['print_status'] = 'Completada'; // Valor por defecto
$saleHeader['id_quotation'] = $saleHeader['id_quotation'] ?? null; // Asegurar que el id_quotation exista

try {
    // 6. Instanciar el servicio de DTE con el modo fiscal de la sesión.
    $fiscalMode = $_SESSION['FISCAL_MODE'] ?? 'TRADICIONAL';
    $dteService = new DteService($pdo, $fiscalMode);

    // 7. Procesar la venta.
    $result = $dteService->processSale($saleHeader, $saleDetails);

    // 8. Si la venta fue exitosa y proviene de una cotización, actualizar el estado de la cotización.
    if ($result['success'] && !empty($saleHeader['id_quotation'])) {
        $stmtUpdateQuote = $pdo->prepare("UPDATE quotation SET status = 'Venta Generada' WHERE id_quotation = :id_quotation");
        $stmtUpdateQuote->execute(['id_quotation' => $saleHeader['id_quotation']]);
        
        // Añadir un mensaje informativo al resultado
        if ($stmtUpdateQuote->rowCount() > 0) {
            $result['message'] .= " Estado de la cotización #{$saleHeader['id_quotation']} actualizado a 'Venta Generada'.";
        }
    }


    // 8. Devolver el resultado al cliente (frontend).
    ob_clean(); // Limpia cualquier salida previa (warnings, espacios)
    echo json_encode($result);

} catch (InsufficientStockException $e) {
    // Error específico de stock: HTTP 409 Conflict
    http_response_code(409); 
    error_log("Conflicto de stock al procesar la venta: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

} catch (BusinessLogicException $e) {
    // Otros errores de negocio (ej. Lote no encontrado): HTTP 400 Bad Request
    http_response_code(400);
    error_log("Error de lógica de negocio al procesar la venta: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

} catch (Exception $e) {
    // Errores genéricos e inesperados: HTTP 500 Internal Server Error
    http_response_code(500);
    error_log("Error al procesar la venta: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error inesperado en el servidor.']);
}

/*
EJEMPLO DE JSON ESPERADO:
{
    "saleHeader": {
        "id_client": 1,
        "total_amount": 150.75,
        "payment_method": "Efectivo",
        "is_rutero_sale": true,
        "id_quotation": 123 // Opcional
    },
    "saleDetails": [
        { "id_product": 1, "id_batch": 1, "quantity": 2, "sale_price_applied": 50.00, "discount": 0 },
        { "id_product": 3, "id_batch": 5, "quantity": 1, "sale_price_applied": 50.75, "discount": 0 }
    ]
}
*/