<?php
/**
 * Modelo para la gestión de Droguerías.
 */
class Drogueria
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todas las droguerías activas.
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM drogueria WHERE is_active = 1 ORDER BY nombre");
        return $stmt->fetchAll();
    }

    /**
     * Encuentra una droguería por su ID.
     * @param int $id
     * @return mixed
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM drogueria WHERE id_drogueria = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Crea una nueva droguería.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO drogueria (nombre, vendedor, contacto, telefono, correo, direccion) VALUES (:nombre, :vendedor, :contacto, :telefono, :correo, :direccion)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':vendedor' => $data['vendedor'] ?? null,
            ':contacto' => $data['contacto'] ?? null,
            ':telefono' => $data['telefono'] ?? null,
            ':correo' => $data['correo'] ?? null,
            ':direccion' => $data['direccion'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza una droguería existente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE drogueria SET 
                    nombre = :nombre, 
                    vendedor = :vendedor, 
                    contacto = :contacto, 
                    telefono = :telefono, 
                    correo = :correo, 
                    direccion = :direccion 
                WHERE id_drogueria = :id";
        $stmt = $this->pdo->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    /**
     * Desactiva una droguería (borrado lógico).
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE drogueria SET is_active = 0 WHERE id_drogueria = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Restaura una droguería desactivada (borrado lógico).
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool
    {
        $sql = "UPDATE drogueria SET is_active = 1 WHERE id_drogueria = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>