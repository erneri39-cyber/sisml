<?php
/**
 * Modelo para la gestión de Categorías de productos.
 */
class Category
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todas las categorías activas, ordenadas por nombre.
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT id_category, name FROM category WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    }

    /**
     * Encuentra una categoría por su ID.
     * @param int $id
     * @return mixed
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM category WHERE id_category = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Crea una nueva categoría.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO category (name) VALUES (:name)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':name' => $data['name']]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza una categoría existente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE category SET name = :name WHERE id_category = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':name' => $data['name'], ':id' => $id]);
    }

    /**
     * Desactiva una categoría (borrado lógico).
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE category SET is_active = 0 WHERE id_category = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>