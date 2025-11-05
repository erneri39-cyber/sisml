<?php
/**
 * Modelo para la gestión de Clientes.
 */
class Client
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los clientes activos.
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id_person, name, document_number, address, phone, email, NIT, NRC, giro 
             FROM person 
             WHERE person_type = 'Cliente' AND is_active = 1 
             ORDER BY name"
        );
        return $stmt->fetchAll();
    }

    /**
     * Busca clientes para el componente Select2 del TPV.
     * @param string $searchTerm
     * @return array
     */
    public function search(string $searchTerm): array
    {
        $sql = "SELECT id_person, name 
                FROM person 
                WHERE person_type = 'Cliente' AND is_active = 1";
        
        $params = [];
        if (!empty($searchTerm)) {
            $sql .= " AND (name LIKE :searchTerm OR document_number LIKE :searchTerm)";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }
        $sql .= " ORDER BY name ASC LIMIT 50";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Encuentra un cliente por su ID.
     * @param int $id
     * @return mixed
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM person WHERE id_person = :id AND person_type = 'Cliente'");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Encuentra un cliente por un campo y valor específicos.
     * @param string $field El nombre de la columna (ej: 'document_number', 'NIT').
     * @param string $value El valor a buscar.
     * @return mixed
     */
    public function findByField(string $field, string $value)
    {
        // Validar que el campo sea uno de los permitidos para evitar inyección SQL en el nombre de la columna.
        if (!in_array($field, ['document_number', 'NIT'])) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT id_person, name, {$field} FROM person WHERE {$field} = :value AND person_type = 'Cliente'");
        $stmt->execute([':value' => $value]);
        return $stmt->fetch();
    }

    /**
     * Crea un nuevo cliente.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO person (name, document_number, address, phone, email, person_type, NIT, NRC, giro) 
                VALUES (:name, :document_number, :address, :phone, :email, 'Cliente', :nit, :nrc, :giro)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':document_number' => $data['document_number'],
            ':address' => $data['address'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':nit' => $data['nit'] ?? null,
            ':nrc' => $data['nrc'] ?? null,
            ':giro' => $data['giro'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza un cliente existente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE person SET 
                    name = :name, 
                    document_number = :document_number, 
                    address = :address, 
                    phone = :phone, 
                    email = :email,
                    NIT = :nit,
                    NRC = :nrc,
                    giro = :giro
                WHERE id_person = :id";
        $stmt = $this->pdo->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    /**
     * Desactiva un cliente (borrado lógico).
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE person SET is_active = 0 WHERE id_person = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>