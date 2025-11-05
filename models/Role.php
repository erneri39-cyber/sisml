<?php
/**
 * Modelo para la gestión de Roles y sus Permisos.
 */
class Role
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los roles activos.
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT id_rol, name FROM rol WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    }

    /**
     * Encuentra un rol por su ID.
     * @param int $id
     * @return mixed
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM rol WHERE id_rol = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Crea un nuevo rol.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO rol (name) VALUES (:name)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':name' => $data['name']]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza el nombre de un rol existente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE rol SET name = :name WHERE id_rol = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':name' => $data['name'], ':id' => $id]);
    }

    /**
     * Desactiva un rol (borrado lógico).
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Por seguridad, no permitir eliminar el rol de Administrador (ID 1)
        if ($id === 1) {
            return false;
        }
        $sql = "UPDATE rol SET is_active = 0 WHERE id_rol = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtiene los IDs de los permisos asociados a un rol.
     * USADO PARA EL MODAL DE EDICIÓN DE ROLES.
     * @param int $roleId
     * @return array
     */
    public function getPermissionIdsByRoleId(int $roleId): array
    {
        $stmt = $this->pdo->prepare("SELECT id_permission FROM role_permission WHERE id_rol = :role_id");
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtiene los NOMBRES de los permisos asociados a un rol.
     * USADO PARA CARGAR LA SESIÓN DEL USUARIO.
     * @param int $roleId
     * @return array
     */
    public function getPermissionNamesByRoleId(int $roleId): array
    {
        $sql = "SELECT p.name
                FROM permission p 
                JOIN role_permission rp ON p.id_permission = rp.id_permission 
                WHERE rp.id_rol = :role_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Actualiza los permisos para un rol.
     * Elimina los permisos existentes y asigna los nuevos.
     * @param int $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function updatePermissions(int $roleId, array $permissionIds): bool
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Eliminar todos los permisos actuales para este rol
            $stmtDelete = $this->pdo->prepare("DELETE FROM role_permission WHERE id_rol = :role_id");
            $stmtDelete->execute([':role_id' => $roleId]);

            // 2. Insertar los nuevos permisos si se proporcionó alguno
            if (!empty($permissionIds)) {
                $sqlInsert = "INSERT INTO role_permission (id_rol, id_permission) VALUES (:role_id, :permission_id)";
                $stmtInsert = $this->pdo->prepare($sqlInsert);
                foreach ($permissionIds as $permissionId) {
                    $stmtInsert->execute([':role_id' => $roleId, ':permission_id' => (int)$permissionId]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene los usuarios activos asignados a un rol específico.
     * @param int $id_rol
     * @return array
     */
    public function getActiveUsersByRoleId(int $id_rol): array
    {
        $sql = "
            SELECT 
                p.name, 
                u.username 
            FROM user u
            JOIN person p ON u.id_person = p.id_person
            WHERE u.id_rol = :id_rol AND u.is_active = 1
            ORDER BY p.name ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_rol' => $id_rol]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>