<?php
/**
 * Modelo para la gestión de Ajustes de Inventario.
 */
class InventoryAdjustment
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crea un nuevo ajuste de inventario y actualiza el stock del lote.
     * Utiliza una transacción para garantizar la integridad de los datos.
     *
     * @param array $data Datos del ajuste.
     * @return bool
     */
    public function create(array $data): bool
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Validar stock para salidas
            if ($data['adjustment_type'] === 'Salida') {
                $stmtCheck = $this->pdo->prepare("SELECT stock FROM batch WHERE id_batch = :id_batch FOR UPDATE");
                $stmtCheck->execute([':id_batch' => $data['id_batch']]);
                $currentStock = $stmtCheck->fetchColumn();

                if ($currentStock < $data['quantity']) {
                    throw new Exception("Stock insuficiente en el lote. Disponible: {$currentStock}, Solicitado para salida: {$data['quantity']}.");
                }
            }

            // 2. Insertar el registro del ajuste
            $sqlAdjustment = "INSERT INTO inventory_adjustment (id_product, id_batch, id_user, adjustment_type, quantity, reason) 
                              VALUES (:id_product, :id_batch, :id_user, :adjustment_type, :quantity, :reason)";
            $stmtAdjustment = $this->pdo->prepare($sqlAdjustment);
            $stmtAdjustment->execute($data);

            // 3. Actualizar el stock en la tabla 'batch'
            if ($data['adjustment_type'] === 'Entrada') {
                $sqlStock = "UPDATE batch SET stock = stock + :quantity WHERE id_batch = :id_batch";
            } else { // Salida
                $sqlStock = "UPDATE batch SET stock = stock - :quantity WHERE id_batch = :id_batch";
            }
            $stmtStock = $this->pdo->prepare($sqlStock);
            $stmtStock->execute([
                ':quantity' => $data['quantity'],
                ':id_batch' => $data['id_batch']
            ]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            // Relanzar la excepción para que el controlador la capture
            throw $e;
        }
    }
}
?>