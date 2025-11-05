<?php

require_once dirname(__DIR__) . '/vendor/tecnickcom/tcpdf/tcpdf.php';

/**
 * Extiende la clase TCPDF para crear un encabezado y pie de página personalizados.
 */
class CustomTCPDF extends TCPDF {

    private $documentTitle = 'Documento';

    public function setDocumentTitle($title) {
        $this->documentTitle = $title;
    }

    /**
     * Define el encabezado personalizado para los documentos PDF.
     */
    public function Header() {
        // Logo (Asegúrate de que la ruta a la imagen sea correcta)
        // Se recomienda usar una ruta absoluta o relativa al script.
        // Por ejemplo: K_PATH_IMAGES . 'logo.png' si configuras la constante.
        // O una ruta relativa desde este archivo.
        $logo_path = $_SESSION['EMPRESA_LOGO_PATH'] ?? 'assets/img/logo_empresa.png';
        $image_file = dirname(__DIR__) . '/' . $logo_path;

        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 25, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        // Configuración de la fuente
        $this->SetFont('helvetica', 'B', 16);

        // Título de la empresa
        $this->SetXY(45, 10);
        $this->Cell(0, 10, 'Farmacia María de Lourdes', 0, 1, 'L');

        // Información de la empresa
        $this->SetFont('helvetica', '', 9);
        $this->SetXY(45, 16);
        $this->Cell(0, 8, 'Dirección de la farmacia, San Salvador', 0, 1, 'L');
        $this->SetXY(45, 20);
        $this->Cell(0, 8, 'Tel: 2222-3333 | Correo: info@farmacialourdes.com', 0, 1, 'L');

        // Título del Documento (ej. Cotización, Orden de Pedido)
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY(0, 35);
        $this->Cell(0, 10, $this->documentTitle, 0, 1, 'C');

        // Línea horizontal debajo del encabezado
        $this->Line(15, 45, $this->getPageWidth() - 15, 45);
    }

    /**
     * Define el pie de página personalizado.
     */
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}