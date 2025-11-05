<?php
/**
 * Controlador para la gestión de Cotizaciones (CRUD).
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once '../db_connect.php';
require_once dirname(__DIR__) . '/models/Quotation.php';
require_once 'PdfService.php';
require_once 'WhatsAppService.php';

$data = json_decode(file_get_contents('php://input'), true);
$quoteHeader = $data['quoteHeader'] ?? null;
$quoteDetails = $data['quoteDetails'] ?? null;

if (!$quoteHeader || !$quoteDetails || empty($quoteDetails)) {
    echo json_encode(['success' => false, 'message' => 'Datos de cotización incompletos.']);
    exit;
}

$quoteModel = new Quotation($pdo);
try {
    $pdo->beginTransaction();

    // 1. Insertar el encabezado de la cotización.
    $sqlHeader = "INSERT INTO quotation (id_client, id_user, id_branch, quotation_date, total_amount) 
                  VALUES (:id_client, :id_user, :id_branch, :quotation_date, :total_amount)";
    
    $stmtHeader = $pdo->prepare($sqlHeader);
    $quoteHeader['id_user'] = $_SESSION['user_id'];
    $quoteHeader['id_branch'] = $_SESSION['id_branch'];
    $quoteDate = date('Y-m-d H:i:s');
    $quoteHeader['quotation_date'] = $quoteDate;
    // Se pasan solo los parámetros que la consulta espera
    $stmtHeader->execute([':id_client' => $quoteHeader['id_client'], ':id_user' => $quoteHeader['id_user'], ':id_branch' => $quoteHeader['id_branch'], ':quotation_date' => $quoteHeader['quotation_date'], ':total_amount' => $quoteHeader['total_amount']]);
    $id_quotation = $pdo->lastInsertId();
    
    // 2. Insertar los detalles de la cotización.
    // CORRECCIÓN: Se elimina la columna 'price_type_applied' que no existe en la tabla 'detail_quotation'.
    $sqlDetail = "INSERT INTO detail_quotation (id_quotation, id_product, id_batch, quantity, price_quoted) 
                  VALUES (:id_quotation, :id_product, :id_batch, :quantity, :price_quoted)";
    $stmtDetail = $pdo->prepare($sqlDetail);

    foreach ($quoteDetails as $detail) {
        $stmtDetail->execute([
            'id_quotation' => $id_quotation,
            'id_product' => $detail['id_product'],
            'id_batch' => $detail['id_batch'],
            'quantity' => $detail['quantity'],
            'price_quoted' => $detail['sale_price_applied']
        ]);
    }

    $pdo->commit();

    // --- INICIO: Lógica post-creación: Generar PDF y enviarlo ---
    $pdfMessage = '';
    try {
        // Obtener datos del cliente para el PDF y WhatsApp
        $stmtClient = $pdo->prepare("SELECT name, phone FROM person WHERE id_person = :id_client");
        $stmtClient->execute(['id_client' => $quoteHeader['id_client']]);
        $clientData = $stmtClient->fetch(PDO::FETCH_ASSOC);
        
        if ($clientData) {
            // Preparar datos para el PDF
            $pdfQuoteData = $quoteHeader;
            $pdfQuoteData['id_quotation'] = $id_quotation;
            
            // Usar el modelo para obtener los detalles
            $pdfQuoteDetails = $quoteModel->getDetailsById($id_quotation);

            // Generar PDF
            $pdfService = new PdfService();
            $pdfPath = $pdfService->generateQuotePdf($pdfQuoteData, $pdfQuoteDetails, $clientData);

            // Enviar por WhatsApp
            $whatsAppService = new WhatsAppService();
            $message = "¡Hola {$clientData['name']}! Adjuntamos tu cotización #{$id_quotation}.";
            $whatsAppService->sendFileMessage($clientData['phone'], $message, $pdfPath, basename($pdfPath));
            $pdfMessage = ' y PDF enviado por WhatsApp.';
        }
    } catch (Exception $e) {
        error_log("Error en flujo post-cotización (PDF/WhatsApp) para cotización #{$id_quotation}: " . $e->getMessage());
        $pdfMessage = ' (ADVERTENCIA: No se pudo generar o enviar el PDF).';
    }

    echo json_encode(['success' => true, 'message' => "Cotización #{$id_quotation} creada exitosamente" . $pdfMessage]);
    // --- FIN: Lógica post-creación ---
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error al crear cotización: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar la cotización.']);
}