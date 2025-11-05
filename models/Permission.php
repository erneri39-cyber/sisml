<?php
/**
 * Modelo para la gestión de Permisos.
 */
class Permission
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los permisos disponibles.
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT id_permission, name, description FROM permission ORDER BY name");
        return $stmt->fetchAll();
    }
}
?>