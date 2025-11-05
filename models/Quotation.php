<?php
/**
 * Modelo para la gestión de Cotizaciones.
 */
class Quotation
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene los detalles (productos) de una cotización específica.
     * @param int $id_quotation
     * @return array
     */
    public function getDetailsById(int $id_quotation): array
    {
        // Se une con la tabla de productos para obtener el nombre.
        $stmt = $this->pdo->prepare(
            "SELECT dq.id_product, dq.id_batch, p.name as product_name, dq.quantity, dq.price_quoted
             FROM detail_quotation dq
             JOIN product p ON dq.id_product = p.id_product
             WHERE dq.id_quotation = :id_quotation"
        );
        $stmt->execute([':id_quotation' => $id_quotation]);
        return $stmt->fetchAll();
    }
}