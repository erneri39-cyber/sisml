<?php
/**
 * Modelo para la gestión de la Configuración del Sistema.
 */
class SystemConfig
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todas las configuraciones como un array asociativo.
     * @return array ['key_name' => 'value', ...]
     */
    public function getAllAsAssoc(): array
    {
        $stmt = $this->pdo->query("SELECT key_name, value FROM system_config");
        $configs = [];
        foreach ($stmt->fetchAll() as $row) {
            $configs[$row['key_name']] = $row['value'];
        }
        return $configs;
    }

    /**
     * Actualiza múltiples configuraciones en la base de datos.
     * @param array $settings Un array asociativo ['key_name' => 'new_value', ...].
     * @param int $userId El ID del usuario que realiza el cambio.
     * @param string $ipAddress La dirección IP del usuario.
     * @return bool
     */
    public function updateSettings(array $settings, int $userId, string $ipAddress): bool
    {
        try {
            $this->pdo->beginTransaction();
            $stmtUpdate = $this->pdo->prepare("UPDATE system_config SET value = :value WHERE key_name = :key_name");
            $stmtSelectOld = $this->pdo->prepare("SELECT value FROM system_config WHERE key_name = :key_name");
            $stmtLog = $this->pdo->prepare(
                "INSERT INTO system_audit_log (id_user, config_key, old_value, new_value, ip_address) 
                 VALUES (:id_user, :config_key, :old_value, :new_value, :ip_address)"
            );

            foreach ($settings as $key => $value) {
                // 1. Obtener el valor antiguo
                $stmtSelectOld->execute([':key_name' => $key]);
                $oldValue = $stmtSelectOld->fetchColumn();

                // 2. Actualizar el valor
                $stmtUpdate->execute([':value' => $value, ':key_name' => $key]);

                // 3. Registrar el cambio en la auditoría
                $stmtLog->execute([':id_user' => $userId, ':config_key' => $key, ':old_value' => $oldValue, ':new_value' => $value, ':ip_address' => $ipAddress]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
?>