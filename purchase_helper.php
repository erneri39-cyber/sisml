<?php
/**
 * Funciones de ayuda para el módulo de Compras.
 */

/**
 * Genera y asigna códigos de venta únicos (para códigos de barras) a cada
 * unidad de producto en una compra recién creada.
 *
 * @param PDO $pdo La instancia de conexión a la base de datos.
 * @param int $id_purchase El ID de la compra que se acaba de registrar.
 * @return void
 */
function generateSaleCodesForPurchase(PDO $pdo, int $id_purchase): void
{
    try {
        // Obtener los detalles de la compra (id_detail_purchase y quantity)
        $stmtDetails = $pdo->prepare("SELECT id_detail_purchase, quantity FROM detail_purchase WHERE id_purchase = :id_purchase");
        $stmtDetails->execute([':id_purchase' => $id_purchase]);
        $details = $stmtDetails->fetchAll();

        $stmtInsertCode = $pdo->prepare(
            "INSERT INTO sale_code (id_detail_purchase, code) VALUES (:id_detail_purchase, :code)"
        );

        foreach ($details as $detail) {
            for ($i = 0; $i < $detail['quantity']; $i++) {
                // Generar un código único. Usamos el ID del detalle y un correlativo para asegurar unicidad.
                $uniqueCode = "P" . str_pad($detail['id_detail_purchase'], 7, '0', STR_PAD_LEFT) . "I" . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                
                $stmtInsertCode->execute([':id_detail_purchase' => $detail['id_detail_purchase'], ':code' => $uniqueCode]);
            }
        }
    } catch (Exception $e) {
        // Registrar el error pero no detener el flujo principal de la compra.
        error_log("Error al generar códigos de venta para la compra #{$id_purchase}: " . $e->getMessage());
    }
}