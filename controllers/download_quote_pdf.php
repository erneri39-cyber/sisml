<?php
/**
 * Endpoint para generar y servir un PDF de una cotización específica.
 */

session_start();

// 1. Proteger el endpoint
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acceso no autorizado.');
}

// 2. Incluir dependencias
require_once dirname(__DIR__) . '/db_connect.php';
require_once 'PdfService.php';

// 3. Validar el ID de la cotización
$id_quotation = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_quotation <= 0) {
    http_response_code(400);
    die('ID de cotización no válido.');
}

try {
    // 4. Obtener todos los datos necesarios para el PDF

    // a) Datos del encabezado de la cotización y del cliente
    $stmtHeader = $pdo->prepare(
        "SELECT q.*, p.name as client_name, p.phone as client_phone
         FROM quotation q
         JOIN person p ON q.id_client = p.id_person
         WHERE q.id_quotation = :id"
    );
    $stmtHeader->execute(['id' => $id_quotation]);
    $quoteData = $stmtHeader->fetch(PDO::FETCH_ASSOC);

    if (!$quoteData) {
        http_response_code(404);
        die('Cotización no encontrada.');
    }

    // b) Datos de los detalles de la cotización
    $stmtDetails = $pdo->prepare(
        "SELECT p.name as product_name, dq.quantity, dq.price_quoted 
         FROM detail_quotation dq JOIN product p ON dq.id_product = p.id_product 
         WHERE dq.id_quotation = :id"
    );
    $stmtDetails->execute(['id' => $id_quotation]);
    $quoteDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

    // c) Datos del cliente (para el servicio de PDF)
    $clientData = ['name' => $quoteData['client_name'], 'phone' => $quoteData['client_phone']];

    // 5. Generar el PDF
    $pdfService = new PdfService();
    $pdfPath = $pdfService->generateQuotePdf($quoteData, $quoteDetails, $clientData);

    // 6. Servir el archivo al navegador
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="cotizacion_' . $id_quotation . '.pdf"');
    header('Content-Length: ' . filesize($pdfPath));
    readfile($pdfPath);

    // 7. Limpiar el archivo temporal
    unlink($pdfPath);
    exit;

} catch (Exception $e) {
    error_log("Error al generar PDF de cotización #{$id_quotation}: " . $e->getMessage());
    http_response_code(500);
    die('Error al generar el documento PDF.');
}