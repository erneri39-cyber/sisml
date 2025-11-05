<?php
/**
 * Modelo para la gestión de Laboratorios.
 */
class Laboratorio
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los laboratorios activos.
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM laboratorio WHERE is_active = 1 ORDER BY nombre");
        return $stmt->fetchAll();
    }

    /**
     * Encuentra un laboratorio por su ID.
     * @param int $id
     * @return mixed
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM laboratorio WHERE id_laboratorio = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Crea un nuevo laboratorio.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO laboratorio (nombre, contacto, telefono, direccion) VALUES (:nombre, :contacto, :telefono, :direccion)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':contacto' => $data['contacto'] ?? null,
            ':telefono' => $data['telefono'] ?? null,
            ':direccion' => $data['direccion'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza un laboratorio existente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE laboratorio SET nombre = :nombre, contacto = :contacto, telefono = :telefono, direccion = :direccion WHERE id_laboratorio = :id";
        $stmt = $this->pdo->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    /**
     * Desactiva un laboratorio (borrado lógico).
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE laboratorio SET is_active = 0 WHERE id_laboratorio = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>