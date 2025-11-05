<?php
/**
 * Modelo para la gestión de Lotes (Batch).
 */
class Batch
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los lotes de un producto específico.
     * @param int $id_product
     * @return array
     */
    public function findByProductId(int $id_product): array
    {
        $sql = "SELECT * FROM batch WHERE id_product = :id_product ORDER BY expiration_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_product' => $id_product]);
        return $stmt->fetchAll();
    }

    /**
     * Encuentra un lote por su ID.
     * @param int $id_batch
     * @return mixed
     */
    public function findById(int $id_batch)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM batch WHERE id_batch = :id_batch");
        $stmt->execute([':id_batch' => $id_batch]);
        return $stmt->fetch();
    }

    /**
     * Crea un nuevo lote para un producto.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        // Corregido para coincidir con la BD: sale_price_b, sale_price_c. Se elimina sale_price_4.
        $sql = "INSERT INTO batch (id_product, id_branch, batch_number, stock, purchase_price, sale_price, sale_price_b, sale_price_c, expiration_date) 
                VALUES (:id_product, :id_branch, :batch_number, :stock, :purchase_price, :sale_price, :sale_price_b, :sale_price_c, :expiration_date)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_product' => $data['id_product'],
            ':id_branch' => $_SESSION['id_branch'], // Tomado de la sesión
            ':batch_number' => $data['batch_number'],
            ':stock' => $data['stock'],
            ':purchase_price' => $data['purchase_price'] ?? 0,
            ':sale_price' => $data['sale_price'] ?? 0,
            ':sale_price_b' => $data['sale_price_2'] ?? 0, // Mapeo de sale_price_2 a sale_price_b
            ':sale_price_c' => $data['sale_price_3'] ?? 0, // Mapeo de sale_price_3 a sale_price_c
            ':expiration_date' => $data['expiration_date']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza un lote existente.
     * @param int $id_batch
     * @param array $data
     * @return bool
     */
    public function update(int $id_batch, array $data): bool
    {
        $sql = "UPDATE batch SET 
                    batch_number = :batch_number, 
                    stock = :stock, 
                    purchase_price = :purchase_price,
                    sale_price = :sale_price,
                    sale_price_b = :sale_price_b,
                    sale_price_c = :sale_price_c,
                    expiration_date = :expiration_date
                WHERE id_batch = :id_batch";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':batch_number' => $data['batch_number'],
            ':stock' => $data['stock'],
            ':purchase_price' => $data['purchase_price'] ?? 0,
            ':sale_price' => $data['sale_price'] ?? 0,
            ':sale_price_b' => $data['sale_price_2'] ?? 0, // Mapeo
            ':sale_price_c' => $data['sale_price_3'] ?? 0, // Mapeo
            ':expiration_date' => $data['expiration_date'],
            ':id_batch' => $id_batch
        ]);
    }

    /**
     * Elimina un lote.
     * Solo se permite si el stock es 0 para mantener la integridad.
     * @param int $id_batch
     * @return bool
     */
    public function delete(int $id_batch): bool
    {
        $sql = "DELETE FROM batch WHERE id_batch = :id_batch AND stock = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_batch' => $id_batch]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtiene la suma total de stock para un producto.
     * @param int $id_product
     * @return int
     */
    public function getTotalStockForProduct(int $id_product): int
    {
        $stmt = $this->pdo->prepare("SELECT SUM(stock) as total FROM batch WHERE id_product = :id_product");
        $stmt->execute([':id_product' => $id_product]);
        return (int)$stmt->fetchColumn();
    }
}
?>