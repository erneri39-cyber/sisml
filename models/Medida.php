<?php
/**
 * Modelo para la gestión de Medidas.
 */
class Medida
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todas las medidas activas.
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM medida WHERE is_active = 1 ORDER BY descripcion");
        return $stmt->fetchAll();
    }

    /**
     * Encuentra una medida por su ID.
     * @param int $id
     * @return mixed
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM medida WHERE id_medida = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Crea una nueva medida.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO medida (descripcion) VALUES (:descripcion)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':descripcion' => $data['descripcion']]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza una medida existente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE medida SET descripcion = :descripcion WHERE id_medida = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':descripcion' => $data['descripcion'], ':id' => $id]);
    }

    /**
     * Desactiva una medida (borrado lógico).
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE medida SET is_active = 0 WHERE id_medida = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>