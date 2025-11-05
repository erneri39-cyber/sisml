<?php
/**
 * Modelo para la gestión de Usuarios.
 */
class User
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los usuarios activos con sus detalles de persona, rol y sucursal.
     * @return array
     */
    public function getAll(): array
    {
        $sql = "SELECT 
                    u.id_user, 
                    u.username, 
                    u.id_rol, 
                    u.id_branch, 
                    p.id_person,
                    p.name AS person_name, 
                    p.email, 
                    p.phone, 
                    p.address,
                    r.name AS role_name,
                    b.name AS branch_name
                FROM user u
                JOIN person p ON u.id_person = p.id_person
                LEFT JOIN rol r ON u.id_rol = r.id_rol
                LEFT JOIN branch b ON u.id_branch = b.id_branch
                WHERE u.is_active = 1
                ORDER BY p.name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Encuentra un usuario por su ID con todos sus detalles.
     * @param int $id
     * @return mixed
     */
    public function findById(int $id)
    {
        $sql = "SELECT 
                    u.id_user, 
                    u.username, 
                    u.id_rol, 
                    u.id_branch, 
                    p.id_person,
                    p.name AS person_name, 
                    p.email, 
                    p.phone, 
                    p.address,
                    p.document_number
                FROM user u
                JOIN person p ON u.id_person = p.id_person
                WHERE u.id_user = :id AND u.is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Encuentra un usuario por su nombre de usuario.
     * @param string $username
     * @return mixed
     */
    public function findByUsername(string $username)
    {
        $stmt = $this->pdo->prepare("SELECT id_user FROM user WHERE username = :username AND is_active = 1");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    /**
     * Crea un nuevo usuario y su registro de persona asociado.
     * @param array $data
     * @return int El ID del usuario recién creado.
     */
    public function create(array $data): int
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Insertar en la tabla 'person'
            // CORREGIDO: Se añade document_number y person_type
            $sqlPerson = "INSERT INTO person (name, document_number, email, phone, address, person_type) VALUES (:name, :document_number, :email, :phone, :address, 'Empleado')";
            $stmtPerson = $this->pdo->prepare($sqlPerson);
            $stmtPerson->execute([
                ':name' => $data['person_name'],
                ':document_number' => $data['document_number'] ?? '00000000-0', // Valor por defecto si no se provee
                ':email' => $data['email'] ?? null,
                ':phone' => $data['phone'] ?? null,
                ':address' => $data['address'] ?? null,
            ]);
            $id_person = (int)$this->pdo->lastInsertId();

            // 2. Insertar en la tabla 'user'
            $sqlUser = "INSERT INTO user (id_person, username, password, id_rol, id_branch) VALUES (:id_person, :username, :password, :id_rol, :id_branch)";
            $stmtUser = $this->pdo->prepare($sqlUser);
            $stmtUser->execute([
                ':id_person' => $id_person,
                ':username' => $data['username'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT), // Hashear la contraseña
                ':id_rol' => $data['id_rol'],
                ':id_branch' => $data['id_branch'],
            ]);
            $id_user = (int)$this->pdo->lastInsertId();

            $this->pdo->commit();
            return $id_user;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza un usuario existente y su registro de persona asociado.
     * @param int $id_user
     * @param array $data
     * @return bool
     */
    public function update(int $id_user, array $data): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Obtener el id_person asociado al id_user
            $stmtGetPersonId = $this->pdo->prepare("SELECT id_person FROM user WHERE id_user = :id_user");
            $stmtGetPersonId->execute([':id_user' => $id_user]);
            $id_person = $stmtGetPersonId->fetchColumn();

            if (!$id_person) {
                throw new Exception("No se encontró el registro de persona para el usuario ID: $id_user");
            }

            // 1. Actualizar la tabla 'person'
            // CORREGIDO: Se añade document_number
            $sqlPerson = "UPDATE person SET name = :name, document_number = :document_number, email = :email, phone = :phone, address = :address WHERE id_person = :id_person";
            $stmtPerson = $this->pdo->prepare($sqlPerson);
            $stmtPerson->execute([
                ':name' => $data['person_name'],
                ':email' => $data['email'] ?? null,
                ':document_number' => $data['document_number'] ?? '00000000-0',
                ':phone' => $data['phone'] ?? null,
                ':address' => $data['address'] ?? null,
                ':id_person' => $id_person,
            ]);

            // 2. Actualizar la tabla 'user'
            $sqlUser = "UPDATE user SET username = :username, id_rol = :id_rol, id_branch = :id_branch";
            $paramsUser = [
                ':username' => $data['username'],
                ':id_rol' => $data['id_rol'],
                ':id_branch' => $data['id_branch'],
            ];

            if (!empty($data['password'])) {
                $sqlUser .= ", password = :password";
                $paramsUser[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            $sqlUser .= " WHERE id_user = :id_user";
            $paramsUser[':id_user'] = $id_user;

            $stmtUser = $this->pdo->prepare($sqlUser);
            $stmtUser->execute($paramsUser);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Desactiva un usuario (borrado lógico) y su registro de persona asociado.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Obtener el id_person asociado al id_user
            $stmtGetPersonId = $this->pdo->prepare("SELECT id_person FROM user WHERE id_user = :id_user");
            $stmtGetPersonId->execute([':id_user' => $id]);
            $id_person = $stmtGetPersonId->fetchColumn();

            if (!$id_person) {
                throw new Exception("No se encontró el registro de persona para el usuario ID: $id");
            }

            // Desactivar en la tabla 'user'
            $stmtUser = $this->pdo->prepare("UPDATE user SET is_active = 0 WHERE id_user = :id");
            $userUpdated = $stmtUser->execute([':id' => $id]);

            $this->pdo->commit();
            return $userUpdated;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene todos los roles activos.
     * @return array
     */
    public function getRoles(): array
    {
        $stmt = $this->pdo->query("SELECT id_rol, name FROM rol WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    }

    /**
     * Obtiene todas las sucursales activas.
     * @return array
     */
    public function getBranches(): array
    {
        $stmt = $this->pdo->query("SELECT id_branch, name FROM branch WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los nombres de los permisos asociados a un ID de rol.
     * @param int $roleId
     * @return array Un array de nombres de permisos (strings).
     */
    public function getPermissionsByRoleId(int $roleId): array
    {
        $sql = "SELECT p.id_permission -- CORREGIDO: Seleccionar el ID del permiso, no el nombre.
                FROM permission p -- La tabla se llama 'permission'
                JOIN role_permission rp ON p.id_permission = rp.id_permission
                WHERE rp.id_rol = :role_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Devuelve solo la columna 'name' como un array simple
    }
}
?>