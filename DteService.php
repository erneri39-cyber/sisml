<?php
require_once __DIR__ . '/PdfService.php';
require_once __DIR__ . '/WhatsAppService.php';
// 1. Incluir las nuevas clases de excepción
require_once __DIR__ . '/exceptions/InsufficientStockException.php';
require_once __DIR__ . '/exceptions/RecordNotFoundException.php';

/**
 * Servicio para gestionar la lógica de ventas, adaptándose al modo fiscal (DTE o Tradicional).
 */
class DteService
{
    private $pdo;
    private $fiscalMode;

    public function __construct(PDO $pdo, string $fiscalMode)
    {
        $this->pdo = $pdo;
        $this->fiscalMode = $fiscalMode;
    }

    /**
     * Procesa una nueva venta.
     *
     * @param array $saleData Datos de la venta (id_client, id_user, id_branch, total_amount, etc.)
     * @param array $saleDetails Detalles de la venta (productos, cantidades, precios)
     * @return array Resultado de la operación.
     */
    public function processSale(array $saleData, array $saleDetails): array
    {
        if ($this->fiscalMode === 'DTE') {
            return $this->processDteSale($saleData, $saleDetails);
        } else {
            return $this->processTraditionalSale($saleData, $saleDetails);
        }
    }

    /**
     * Procesa una venta en modo DTE.
     */
    private function processDteSale(array $saleData, array $saleDetails): array
    {
        // 1. Verificar conectividad con el Ministerio de Hacienda (MH).
        $isMhAvailable = $this->checkMhConnection();

        if ($isMhAvailable) {
            // Lógica para generar y enviar el DTE al MH.
            // ... (aquí iría la integración con la API del MH) ...
            // Si es exitoso:
            $saleData['electronic_status'] = 'Aceptada MH';
            $saleData['document_type'] = 'DTE-03'; // Ejemplo: Factura Consumidor Final
            // ...
        } else {
            // FLUJO DE CONTINGENCIA
            $saleData['electronic_status'] = 'Contingencia - Pendiente de Envío';
            $saleData['document_type'] = 'DTE-CONTINGENCIA';
            // Generar un número de serie de contingencia si es necesario.
            // ...
        }

        // 2. Guardar la venta en la base de datos.
        $this->saveSaleToDatabase($saleData, $saleDetails);

        $result = ['success' => true, 'message' => 'Venta DTE procesada.', 'status' => $saleData['electronic_status']];
        // 3. Ejecutar flujo de rutero si aplica
        return $this->handleRuteroFlow($saleData, $saleDetails, $result);
    }

    /**
     * Procesa una venta en modo Tradicional (Factura/Ticket interno).
     */
    private function processTraditionalSale(array $saleData, array $saleDetails): array
    {
        $saleData['document_type'] = 'Ticket Interno';
        $saleData['electronic_status'] = 'N/A';

        // Guardar la venta en la base de datos y descontar stock.
        $this->saveSaleToDatabase($saleData, $saleDetails);

        $result = ['success' => true, 'message' => 'Venta tradicional registrada.'];
        // Ejecutar flujo de rutero si aplica
        return $this->handleRuteroFlow($saleData, $saleDetails, $result);
    }

    /**
     * Guarda la venta y sus detalles en la BD y descuenta el stock del lote.
     * Utiliza una transacción para garantizar la integridad de los datos.
     */
    private function saveSaleToDatabase(array $saleData, array $saleDetails)
    {
        try {
            $this->pdo->beginTransaction();
            
            // --- Lógica de Vendedor Rutero ---
            if (!empty($saleData['is_rutero_sale']) && $saleData['is_rutero_sale'] == true) {
                // CORRECCIÓN: Asegurar que el estado de impresión se establezca correctamente para ventas de ruta.
                $saleData['print_status'] = 'En Espera'; // El pedido debe esperar a ser preparado en el módulo de rutas.
            }
            // --- Fin Lógica Rutero ---

            // 1. VERIFICAR STOCK ANTES DE CUALQUIER OPERACIÓN
            // Se usa "FOR UPDATE" para bloquear las filas y evitar race conditions.
            $stmtCheckStock = $this->pdo->prepare(
                "SELECT p.name AS product_name, b.stock FROM batch b JOIN product p ON b.id_product = p.id_product WHERE b.id_batch = :id_batch FOR UPDATE"
            );
            foreach ($saleDetails as $detail) {
                $stmtCheckStock->execute(['id_batch' => $detail['id_batch']]);
                $batch = $stmtCheckStock->fetch();

                if (!$batch) {
                    throw new RecordNotFoundException("El lote con ID {$detail['id_batch']} no fue encontrado.");
                }

                if ($batch['stock'] < $detail['quantity']) {
                    throw new InsufficientStockException("Stock insuficiente para '{$batch['product_name']}'. Disponible: {$batch['stock']}, Solicitado: {$detail['quantity']}.");
                }
            }

            // 2. Insertar el encabezado de la venta en la tabla 'sale'.
            // RECONSTRUCCIÓN: La consulta ahora incluye TODOS los campos necesarios para ser compatible con TPV de mostrador y TPV de ruta.
            $sqlSale = "INSERT INTO sale (id_client, id_user, id_branch, sale_date, document_type, total_amount, electronic_status, payment_method, is_rutero_sale, print_status, id_quotation, cash_received, cash_change, rutero_status)
                        VALUES (:id_client, :id_user, :id_branch, :sale_date, :document_type, :total_amount, :electronic_status, :payment_method, :is_rutero_sale, :print_status, :id_quotation, :cash_received, :cash_change, :rutero_status)";
            $stmtSale = $this->pdo->prepare($sqlSale);
            
            // RECONSTRUCCIÓN: Se construye un array de parámetros explícito para el execute(), asegurando una correspondencia perfecta y manejando valores opcionales.
            $stmtSale->execute([
                ':id_client' => $saleData['id_client'],
                ':id_user' => $saleData['id_user'],
                ':id_branch' => $saleData['id_branch'],
                ':sale_date' => $saleData['sale_date'],
                ':document_type' => $saleData['document_type'],
                ':total_amount' => $saleData['total_amount'],
                ':electronic_status' => $saleData['electronic_status'],
                ':payment_method' => $saleData['payment_method'],
                ':is_rutero_sale' => $saleData['is_rutero_sale'] ?? 0,
                ':print_status' => $saleData['print_status'] ?? 'Completada',
                ':id_quotation' => $saleData['id_quotation'] ?? null, // Asegurar que sea null si no viene
                ':cash_received' => $saleData['cash_received'] ?? 0,
                ':cash_change' => $saleData['cash_change'] ?? 0,
                ':rutero_status' => ($saleData['is_rutero_sale'] ?? 0) ? 'En Espera' : null
            ]);

            $id_sale = $this->pdo->lastInsertId();
            $saleData['id_sale'] = $id_sale; // Añadir el ID al array para usarlo después

            // 3. Preparar sentencias para el detalle y la actualización de stock.
            $sqlDetail = "INSERT INTO detail_sale (id_sale, id_product, id_batch, quantity, sale_price_applied, discount) 
                          VALUES (:id_sale, :id_product, :id_batch, :quantity, :sale_price_applied, :discount)";
            $stmtDetail = $this->pdo->prepare($sqlDetail);

            $sqlStock = "UPDATE batch SET stock = stock - :quantity WHERE id_batch = :id_batch";
            $stmtStock = $this->pdo->prepare($sqlStock);

            // 4. Iterar sobre los detalles, insertarlos y actualizar el stock de cada lote.
            foreach ($saleDetails as $detail) {
                $stmtDetail->execute([
                    ':id_sale' => $id_sale,
                    ':id_product' => $detail['id_product'],
                    ':id_batch' => $detail['id_batch'],
                    ':quantity' => $detail['quantity'],
                    ':sale_price_applied' => $detail['sale_price_applied'],
                    ':discount' => $detail['discount'] ?? 0
                ]);

                $stmtStock->execute(['quantity' => $detail['quantity'], 'id_batch' => $detail['id_batch']]);
            }

            // 5. Si todo fue exitoso, confirmar la transacción.
            $this->pdo->commit();

        } catch (Exception $e) {
            // Si algo falla, revertir todos los cambios.
            $this->pdo->rollBack();
            throw $e; // Relanzar la excepción para que el controlador la capture.
        }
    }

    /**
     * Maneja la lógica post-venta para vendedores ruteros.
     */
    private function handleRuteroFlow(array $saleData, array $saleDetails, array $initialResult): array
    {
        if (empty($saleData['is_rutero_sale']) || $saleData['is_rutero_sale'] != true) {
            return $initialResult;
        }

        // En un entorno de producción, este bloque debería estar en una cola de trabajos
        // para no retrasar la respuesta al usuario.
        try {
            // Obtener datos del cliente (teléfono)
            // 🛡️ CORRECCIÓN CRÍTICA: El controlador pasa 'id_client', que corresponde a 'id_person' en la tabla.
            $stmtClient = $this->pdo->prepare("SELECT name, phone FROM person WHERE id_person = :id_person"); 
            $stmtClient->execute(['id_person' => $saleData['id_client']]);
            $clientData = $stmtClient->fetch();

            if ($clientData && !empty($clientData['phone'])) {
                // Generar PDF
                $pdfService = new PdfService();
                // Pasamos los detalles de la venta que ya tenemos
                $pdfPath = $pdfService->generateOrderPdf($saleData, $saleDetails, $clientData);

                // Enviar por WhatsApp (simulación)
                $whatsAppService = new WhatsAppService();
                $message = "¡Hola {$clientData['name']}! Hemos recibido tu pedido #{$saleData['id_sale']}. Lo estamos preparando.";
                $whatsAppService->sendFileMessage($clientData['phone'], $message, $pdfPath, basename($pdfPath));

                $initialResult['message'] .= ' Pedido de rutero registrado y notificado por WhatsApp.';
            } else {
                // Si el cliente no tiene teléfono, solo se registra el pedido sin notificar.
                $initialResult['message'] .= ' Pedido de rutero registrado. (Cliente sin teléfono para notificación).';
                error_log("Flujo de rutero para venta #{$saleData['id_sale']}: Cliente sin número de teléfono.");
            }

        } catch (Exception $e) {
            error_log("Error en el flujo de rutero: " . $e->getMessage());
            $initialResult['message'] .= ' (ADVERTENCIA: No se pudo notificar al cliente por WhatsApp).';
        }
        return $initialResult;
    }

    private function checkMhConnection(): bool
    {
        // Simulación: En un caso real, aquí harías un ping o una llamada a un endpoint de status del MH.
        return false; // Forzamos la contingencia para el ejemplo.
    }
}




























































































































































































































































































á
            $sqlSale = "INSERT INTO sale (id_client, id_user, id_branch, sale_date, document_type, total_amount, electronic_status, payment_method, is_rutero_sale, print_status, id_quotation, cash_received, cash_change)
                        VALUES (:id_client, :id_user, :id_branch, :sale_date, :document_type, :total_amount, :electronic_status, :payment_method, :is_rutero_sale, :print_status, :id_quotation, :cash_received, :cash_change)";
            $stmtSale = $this->pdo->prepare($sqlSale);
            
            // RECONSTRUCCIÓN: Se construye un array de parámetros explícito para el execute(), asegurando que solo se envíen los datos que la consulta espera.
            $paramsToExecute = [
                ':id_client' => $saleData['id_client'],
                ':id_user' => $saleData['id_user'],
                ':id_branch' => $saleData['id_branch'],
                ':sale_date' => $saleData['sale_date'],
                ':document_type' => $saleData['document_type'],
                ':total_amount' => $saleData['total_amount'],
                ':electronic_status' => $saleData['electronic_status'],
                ':payment_method' => $saleData['payment_method'],
                ':is_rutero_sale' => $saleData['is_rutero_sale'],
                ':print_status' => $saleData['print_status'],
                ':id_quotation' => $saleData['id_quotation'],
                ':cash_received' => $saleData['cash_received'],
                ':cash_change' => $saleData['cash_change']
            ]);
            $stmtSale->execute($paramsToExecute);

            $id_sale = $this->pdo->lastInsertId();
            $saleData['id_sale'] = $id_sale; // Añadir el ID al array para usarlo después

            // 3. Preparar sentencias para el detalle y la actualización de stock.
            $sqlDetail = "INSERT INTO detail_sale (id_sale, id_product, id_batch, quantity, sale_price_applied, discount) 
                          VALUES (:id_sale, :id_product, :id_batch, :quantity, :sale_price_applied, :discount)";
            $stmtDetail = $this->pdo->prepare($sqlDetail);

            $sqlStock = "UPDATE batch SET stock = stock - :quantity WHERE id_batch = :id_batch";
            $stmtStock = $this->pdo->prepare($sqlStock);

            // 4. Iterar sobre los detalles, insertarlos y actualizar el stock de cada lote.
            foreach ($saleDetails as $detail) {
                $stmtDetail->execute([
                    ':id_sale' => $id_sale,
                    ':id_product' => $detail['id_product'],
                    ':id_batch' => $detail['id_batch'],
                    ':quantity' => $detail['quantity'],
                    ':sale_price_applied' => $detail['sale_price_applied'],
                    ':discount' => $detail['discount'] ?? 0
                ]);

                $stmtStock->execute(['quantity' => $detail['quantity'], 'id_batch' => $detail['id_batch']]);
            }

            // 5. Si todo fue exitoso, confirmar la transacción.
            $this->pdo->commit();

        } catch (Exception $e) {
            // Si algo falla, revertir todos los cambios.
            $this->pdo->rollBack();
            throw $e; // Relanzar la excepción para que el controlador la capture.
        }
    }

    /**
     * Maneja la lógica post-venta para vendedores ruteros.
     */
    private function handleRuteroFlow(array $saleData, array $saleDetails, array $initialResult): array
    {
        if (empty($saleData['is_rutero_sale']) || $saleData['is_rutero_sale'] != true) {
            return $initialResult;
        }

        try {
            // Obtener datos del cliente (teléfono)
            $stmtClient = $this->pdo->prepare("SELECT name, phone FROM person WHERE id_person = :id_client");
            $stmtClient->execute(['id_client' => $saleData['id_client']]);
            $clientData = $stmtClient->fetch();

            if (!$clientData || empty($clientData['phone'])) {
                throw new Exception("No se encontró el número de teléfono del cliente para la notificación.");
            }

            // Generar PDF
            $pdfService = new PdfService();
            $pdfPath = $pdfService->generateOrderPdf($saleData, $saleDetails, $clientData);

            // Enviar por WhatsApp
            $whatsAppService = new WhatsAppService();
            $message = "¡Hola {$clientData['name']}! Adjuntamos tu orden de pedido #{$saleData['id_sale']}.";
            $whatsAppService->sendFileMessage($clientData['phone'], $message, $pdfPath, basename($pdfPath));

            $initialResult['message'] .= ' Pedido de rutero registrado y notificado por WhatsApp.';
        } catch (Exception $e) {
            error_log("Error en el flujo de rutero: " . $e->getMessage());
            $initialResult['message'] .= ' (ADVERTENCIA: No se pudo notificar al cliente por WhatsApp).';
        }
        return $initialResult;
    }

    private function checkMhConnection(): bool
    {
        // Simulación: En un caso real, aquí harías un ping o una llamada a un endpoint de status del MH.
        return false; // Forzamos la contingencia para el ejemplo.
    }
}