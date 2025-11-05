<?php

class PdfService
{
    public function generateOrderPdf(array $saleData, array $saleDetails, array $clientData): string
    {
        $pdfPath = sys_get_temp_dir() . "/pedido_ruta_{$saleData['id_sale']}.pdf";

        $content = "=== PEDIDO DE RUTA ===\n\n";
        $content .= "Cliente: {$clientData['name']}\n";
        $content .= "Teléfono: {$clientData['phone']}\n";
        $content .= "Fecha: {$saleData['sale_date']}\n";
        $content .= "ID Pedido: #{$saleData['id_sale']}\n\n";
        $content .= "PRODUCTOS:\n";
        $content .= str_repeat("-", 50) . "\n";

        $total = 0;
        foreach ($saleDetails as $item) {
            $subtotal = $item['quantity'] * $item['sale_price_applied'] - ($item['discount'] ?? 0);
            $total += $subtotal;
            $content .= sprintf(
                "%3dx %-30s $%6.2f = $%7.2f\n",
                $item['quantity'],
                $item['product_name'] ?? "Prod #{$item['id_product']}",
                $item['sale_price_applied'],
                $subtotal
            );
        }

        $content .= str_repeat("-", 50) . "\n";
        $content .= sprintf("TOTAL: $%7.2f\n", $total);
        $content .= "\nGracias por su compra.\nFarmacia Lourdes";

        file_put_contents($pdfPath, $content);
        return $pdfPath;
    }
}