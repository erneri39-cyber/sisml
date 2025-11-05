<?php
/**
 * Modelo para la gestión de Compras.
 */
class Purchase
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crea una nueva compra y sus detalles, actualizando el stock en lotes.
     * Utiliza una transacción para garantizar la integridad de los datos.
     *
     * @param array $header Datos del encabezado de la compra.
     * @param array $details Array con los detalles de los productos de la compra.
     * @return int El ID de la compra creada.
     */
    public function create(array $header, array $details): int
    {
        try {
            $this->pdo->beginTransaction();

            // --- INICIO: Validación de Datos Críticos ---
            if (!isset($header['total_cost']) || (float)$header['total_cost'] <= 0) {
                throw new Exception("El costo total de la compra debe ser mayor que cero.");
            }

            foreach ($details as $item) {
                if (!isset($item['quantity']) || (int)$item['quantity'] <= 0 || !isset($item['purchase_price']) || (float)$item['purchase_price'] <= 0) {
                    throw new Exception("Cada producto en la compra debe tener una cantidad y un precio de compra mayores que cero.");
                }
            }
            // --- FIN: Validación de Datos Críticos ---

            // 1. Insertar encabezado de compra
            $sqlHeader = "INSERT INTO purchase (id_supplier, id_user, id_branch, purchase_date, document_type, document_number, total_amount) 
                          VALUES (:id_supplier, :id_user, :id_branch, :purchase_date, :document_type, :document_number, :total_amount)";
            $stmtHeader = $this->pdo->prepare($sqlHeader);
            $stmtHeader->execute([
                ':id_supplier' => $header['id_supplier'], // CORRECCIÓN: El JS envía 'id_supplier'
                ':id_user' => $header['id_user'],
                ':id_branch' => $header['id_branch'],
                ':purchase_date' => $header['purchase_date'] ?? date('Y-m-d H:i:s'), // Usar la fecha del form, o la actual como fallback
                ':document_number' => $header['document_number'],
                ':document_type' => 'Factura', // Valor por defecto, se puede ajustar
                ':total_amount' => $header['total_cost']
            ]);
            $id_purchase = (int)$this->pdo->lastInsertId();

            // 2. Preparar sentencias para lotes y detalles
            $sqlBatch = "INSERT INTO batch (id_product, id_branch, batch_number, stock, purchase_price, sale_price, sale_price_b, sale_price_c, sale_price_2, sale_price_3, expiration_date) 
                         VALUES (:id_product, :id_branch, :batch_number, :stock, :purchase_price, :sale_price, :sale_price_b, :sale_price_c, :sale_price_2, :sale_price_3, :expiration_date)";
            $stmtBatch = $this->pdo->prepare($sqlBatch);

            // Corregido para usar 'unit_price' y 'cod_pventa'
            $sqlDetail = "INSERT INTO detail_purchase (id_purchase, id_batch, quantity, unit_price, cod_pventa) 
                          VALUES (:id_purchase, :id_batch, :quantity, :unit_price, :cod_pventa)";
            $stmtDetail = $this->pdo->prepare($sqlDetail);

            // 3. Iterar sobre los detalles para crear lotes y registrar el detalle de compra
            foreach ($details as $item) {
                // Crear un nuevo lote por cada item de compra
                $stmtBatch->execute([
                    ':id_product' => $item['id_product'],
                    ':id_branch' => $header['id_branch'],
                    ':batch_number' => $item['batch_number'],
                    ':stock' => $item['quantity'],
                    ':purchase_price' => $item['purchase_price'] ?? 0,
                    ':sale_price' => $item['sale_price'] ?? 0,
                    ':sale_price_b' => $item['sale_price_b'] ?? 0, // Mapear 'sale_price_b' del JS a la columna 'sale_price_b'
                    ':sale_price_c' => $item['sale_price_c'] ?? 0, // Mapear 'sale_price_c' del JS a la columna 'sale_price_c'
                    ':sale_price_2' => $item['sale_price_2'] ?? 0,
                    ':sale_price_3' => $item['sale_price_3'] ?? 0,
                    ':expiration_date' => $item['expiration_date']
                ]);
                $id_batch = (int)$this->pdo->lastInsertId();

                // Generar código de venta único para la etiqueta
                $cod_pventa = "P" . str_pad($id_purchase, 5, '0', STR_PAD_LEFT) . "B" . str_pad($id_batch, 5, '0', STR_PAD_LEFT);

                // Insertar el detalle de la compra, vinculándolo al nuevo lote
                $stmtDetail->execute([
                    ':id_purchase' => $id_purchase,
                    ':id_batch' => $id_batch,
                    ':quantity' => $item['quantity'],
                    ':unit_price' => $item['purchase_price'], // Mapeo a unit_price
                    ':cod_pventa' => $cod_pventa
                ]);
            }

            $this->pdo->commit();
            return $id_purchase;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e; // Relanzar para que el controlador la capture
        }
    }

    /**
     * Obtiene todas las compras de una sucursal.
     * @param int $id_branch
     * @return array
     */
    public function getAllByBranch(int $id_branch): array
    {
        // Corregido: se usa id_supplier y total_amount. Se obtiene el nombre del usuario desde 'person'.
        $sql = "SELECT p.id_purchase, p.purchase_date, p.document_number, d.nombre as drogueria_name, up.name as user_name, p.total_amount as total_cost, p.status
                FROM purchase p
                JOIN drogueria d ON p.id_supplier = d.id_drogueria
                JOIN user u ON p.id_user = u.id_user
                JOIN person up ON u.id_person = up.id_person
                WHERE p.id_branch = :id_branch
                ORDER BY p.purchase_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_branch' => $id_branch]);
        return $stmt->fetchAll();
    }
}