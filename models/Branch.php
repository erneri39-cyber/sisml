<?php
/**
 * Modelo para la gestión de Sucursales (Branches).
 */
class Branch
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todas las sucursales activas.
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM branch WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    }

    /**
     * Encuentra una sucursal por su ID.
     * @param int $id
     * @return mixed
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM branch WHERE id_branch = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Crea una nueva sucursal.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO branch (name, address, phone) VALUES (:name, :address, :phone)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':address' => $data['address'] ?? null,
            ':phone' => $data['phone'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza una sucursal existente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE branch SET 
                    name = :name, 
                    address = :address, 
                    phone = :phone
                WHERE id_branch = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':address' => $data['address'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':id' => $id
        ]);
    }

    /**
     * Desactiva una sucursal (borrado lógico).
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE branch SET is_active = 0 WHERE id_branch = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>