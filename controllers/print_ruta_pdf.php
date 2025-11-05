<?php
/**
 * Endpoint para generar y servir un PDF de una Hoja de Preparación para un pedido de ruta.
 */

session_start();

// 1. Proteger el endpoint
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acceso no autorizado.');
}

// 2. Incluir dependencias
require_once dirname(__DIR__) . '/db_connect.php';
require_once 'CustomTCPDF.php';

// 3. Validar el ID de la venta
$id_sale = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_sale <= 0) {
    http_response_code(400);
    die('ID de venta no válido.');
}

try {
    // 4. Obtener todos los datos necesarios para el PDF

    // a) Datos del encabezado de la venta y del cliente
    $stmtHeader = $pdo->prepare(
        "SELECT s.id_sale, s.sale_date, p.name as client_name, p.address as client_address
         FROM sale s
         JOIN person p ON s.id_client = p.id_person
         WHERE s.id_sale = :id"
    );
    $stmtHeader->execute(['id' => $id_sale]);
    $saleData = $stmtHeader->fetch(PDO::FETCH_ASSOC);

    if (!$saleData) {
        http_response_code(404);
        die('Pedido no encontrado.');
    }

    // b) Datos de los detalles de la venta (productos)
    $stmtDetails = $pdo->prepare(
        "SELECT p.code as product_code, p.name as product_name, ds.quantity
         FROM detail_sale ds 
         JOIN product p ON ds.id_product = p.id_product 
         WHERE ds.id_sale = :id
         ORDER BY p.name ASC"
    );
    $stmtDetails->execute(['id' => $id_sale]);
    $saleDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

    // 5. Generar el PDF
    $pdf = new CustomTCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Far-MaríadeLourdes');
    $pdf->SetTitle("Hoja de Preparación #{$saleData['id_sale']}");
    $pdf->setDocumentTitle("Hoja de Preparación #{$saleData['id_sale']}");
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    // Mover el contenido debajo del encabezado
    $pdf->SetY(50);

    $html = "<b>Cliente:</b> " . htmlspecialchars($saleData['client_name']) . "<br>";
    $html .= "<b>Dirección de Entrega:</b> " . htmlspecialchars($saleData['client_address']) . "<br>";
    $html .= "<b>Fecha del Pedido:</b> " . date('d/m/Y H:i', strtotime($saleData['sale_date'])) . "<br><br>";
    $html .= '<table border="1" cellpadding="4" cellspacing="0"><thead><tr><th width="75%"><b>Producto</b></th><th width="10%" align="center"><b>Cant.</b></th><th width="15%"><b>Check</b></th></tr></thead><tbody>';

    foreach ($saleDetails as $item) {
        $html .= "<tr><td>" . htmlspecialchars($item['product_name']) . "</td><td align=\"center\"><b>" . $item['quantity'] . "</b></td><td></td></tr>";
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // 6. Servir el archivo al navegador
    $pdf->Output("preparacion_pedido_{$id_sale}.pdf", 'I'); // 'I' para mostrar en navegador

} catch (Exception $e) {
    error_log("Error al generar PDF de preparación para la venta #{$id_sale}: " . $e->getMessage());
    http_response_code(500);
    die('Error al generar el documento PDF.');
}
?>