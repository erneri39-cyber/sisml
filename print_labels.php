<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Acceso denegado.');
}

require_once 'db_connect.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php'; // Asegúrate que la ruta sea correcta

$id_purchase = filter_input(INPUT_GET, 'id_purchase', FILTER_VALIDATE_INT);

if (!$id_purchase) {
    die('ID de compra no válido.');
}

// Obtener los datos para las etiquetas
// Corregido: Se obtiene el cod_pventa desde detail_purchase y el nombre del producto a través del lote.
$stmt = $pdo->prepare(
    "SELECT dp.cod_pventa, p.name
     FROM detail_purchase dp
     JOIN batch b ON dp.id_batch = b.id_batch
     JOIN product p ON b.id_product = p.id_product
     WHERE dp.id_purchase = :id_purchase AND dp.cod_pventa IS NOT NULL"
);
$stmt->execute(['id_purchase' => $id_purchase]);
$labels = $stmt->fetchAll();

if (empty($labels)) {
    die('No se encontraron etiquetas para esta compra.');
}

// Crear el documento PDF
$pdf = new TCPDF('P', 'mm', [40, 25], true, 'UTF-8', false); // Tamaño de etiqueta común: 40x25mm

$pdf->SetCreator('Far-MaríadeLourdes');
$pdf->SetAuthor('Sistema');
$pdf->SetTitle('Etiquetas de Productos');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(2, 2, 2);
$pdf->SetAutoPageBreak(true, 2);

// Estilo para el código de barras
$style = [
    'position' => '',
    'align' => 'C',
    'stretch' => false,
    'fitwidth' => true,
    'cellfitalign' => '',
    'border' => false,
    'hpadding' => 'auto',
    'vpadding' => 'auto',
    'fgcolor' => [0, 0, 0],
    'bgcolor' => false,
    'text' => true,
    'font' => 'helvetica',
    'fontsize' => 7,
    'stretchtext' => 4
];

foreach ($labels as $label) {
    $pdf->AddPage();
    
    // Nombre del producto (corto)
    $productName = (strlen($label['name']) > 25) ? substr($label['name'], 0, 22) . '...' : $label['name'];
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell(0, 3, $productName, 0, 1, 'C');

    // Código de barras (Code 128 es una buena opción)
    $pdf->write1DBarcode($label['cod_pventa'], 'C128', '', '', '', 15, 0.4, $style, 'N');
}

$pdf->Output('etiquetas_compra_' . $id_purchase . '.pdf', 'I'); // 'I' para mostrar en navegador