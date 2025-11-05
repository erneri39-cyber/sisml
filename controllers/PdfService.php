<?php

require_once 'CustomTCPDF.php';

/**
 * Servicio para generar documentos PDF.
 */
class PdfService
{
    /**
     * Genera un PDF para una Orden de Pedido (no fiscal).
     *
     * @param array $saleData Datos del encabezado de la venta.
     * @param array $saleDetails Detalles de la venta.
     * @param array $clientData Datos del cliente.
     * @return string Ruta al archivo PDF generado.
     */
    public function generateOrderPdf(array $saleData, array $saleDetails, array $clientData): string
    {
        $pdf = new CustomTCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Far-MaríadeLourdes');
        $pdf->SetTitle('Orden de Pedido');
        $pdf->setDocumentTitle("Orden de Pedido #{$saleData['id_sale']}");
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        // Mover el contenido debajo del encabezado
        $pdf->SetY(50);

        $html = "<b>Cliente:</b> {$clientData['name']}<br>";
        $html .= "<b>Fecha:</b> " . date('d/m/Y', strtotime($saleData['sale_date'])) . "<br><br>";
        $html .= '<table border="1" cellpadding="4" cellspacing="0"><tr><th width="55%"><b>Producto</b></th><th width="15%"><b>Cantidad</b></th><th width="15%"><b>Precio</b></th><th width="15%"><b>Subtotal</b></th></tr>';
        foreach ($saleDetails as $item) {
            $subtotal = $item['quantity'] * $item['sale_price_applied'];
            $html .= "<tr><td>{$item['name']}</td><td align=\"center\">{$item['quantity']}</td><td align=\"right\">\${$item['sale_price_applied']}</td><td align=\"right\">\${$subtotal}</td></tr>";
        }
        $html .= "</table>";
        $html .= "<br><br>";
        $html .= '<div style="text-align: right;"><h3>Total: $' . number_format($saleData['total_amount'], 2) . '</h3></div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $filePath = sys_get_temp_dir() . "/orden_pedido_{$saleData['id_sale']}.pdf";
        $pdf->Output($filePath, 'F'); // 'F' para guardar en un archivo local

        return $filePath;
    }

    /**
     * Genera un PDF para una Cotización.
     */
    public function generateQuotePdf(array $quoteData, array $quoteDetails, array $clientData): string
    {
        $pdf = new CustomTCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Far-MaríadeLourdes');
        $pdf->SetTitle("Cotización #{$quoteData['id_quotation']}");
        $pdf->setDocumentTitle("Cotización #{$quoteData['id_quotation']}");
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        // Mover el contenido debajo del encabezado
        $pdf->SetY(50);

        $html = "<b>Cliente:</b> {$clientData['name']}<br>";
        $html .= "<b>Fecha:</b> " . date('d/m/Y', strtotime($quoteData['quotation_date'])) . "<br><br>";
        $html .= '<table border="1" cellpadding="4" cellspacing="0"><thead><tr><th width="55%"><b>Producto</b></th><th width="15%" align="center"><b>Cantidad</b></th><th width="15%" align="right"><b>Precio Unit.</b></th><th width="15%" align="right"><b>Subtotal</b></th></tr></thead><tbody>';

        foreach ($quoteDetails as $item) {
            $price = (float)$item['price_quoted'];
            $quantity = (int)$item['quantity'];
            $subtotal = $price * $quantity;
            $html .= "<tr><td>{$item['product_name']}</td><td align=\"center\">{$quantity}</td><td align=\"right\">$" . number_format($price, 2) . "</td><td align=\"right\">$" . number_format($subtotal, 2) . "</td></tr>";
        }

        $html .= '</tbody></table>';
        $html .= '<br><br>';
        $html .= '<div style="text-align: right;"><h3>Total: $' . number_format($quoteData['total_amount'], 2) . '</h3></div>';
        $html .= '<br><br><p><i>Precios sujetos a cambio sin previo aviso. Validez de la cotización: 15 días.</i></p>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $filePath = sys_get_temp_dir() . "/cotizacion_{$quoteData['id_quotation']}.pdf";
        $pdf->Output($filePath, 'F');

        return $filePath;
    }
}