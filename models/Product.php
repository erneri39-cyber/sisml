<?php
/**
 * Modelo para la gestión de Productos.
 */
class Product
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getDbConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Obtiene todos los productos que tienen lotes en una sucursal específica.
     * Esto asegura que solo se muestren productos con existencia en la sucursal del usuario.
     *
     * @param int $id_branch El ID de la sucursal.
     * @return array Lista de productos.
     */
    public function getAllByBranch(int $id_branch): array // MODIFICADO PARA GESTIÓN DE PRODUCTOS
    {
        // Esta consulta ahora devuelve todos los productos de una sucursal,
        // y calcula el stock total sumando los lotes.
        // Esto asegura que los productos recién creados (sin lotes/stock) también aparezcan.
        $sql = "SELECT 
                    p.id_product,
                    p.code,
                    p.name,
                    m.descripcion as medida,
                    p.location, -- Añadido para mostrar en la tabla
                    c.name as category_name,
                    l.nombre as laboratorio,
                    (SELECT SUM(b.stock) FROM batch b WHERE b.id_product = p.id_product AND b.id_branch = :id_branch_sub) as total_stock,
                    (SELECT image_path FROM product_images pi WHERE pi.id_product = p.id_product AND pi.is_primary = 1 LIMIT 1) as primary_image
                FROM product p
                LEFT JOIN medida m ON p.id_medida = m.id_medida
                LEFT JOIN category c ON p.id_category = c.id_category
                LEFT JOIN laboratorio l ON p.id_laboratorio = l.id_laboratorio
                WHERE p.id_branch = :id_branch_main
                GROUP BY p.id_product
                ORDER BY p.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_branch_sub' => $id_branch,
            ':id_branch_main' => $id_branch
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Crea un nuevo producto en la base de datos.
     *
     * @param array $data Datos del producto (name, code, description, id_medida, id_laboratorio, id_category, id_branch).
     * @return int El ID del producto recién creado.
     */
    public function create(array $data): int // Renombrado internamente, pero la lógica es la misma
    {
        // Asegurar que solo se incluyen los campos de la tabla 'product'
        $sql = "INSERT INTO product (name, code, id_medida, id_laboratorio, id_category, id_branch, location) 
                VALUES (:name, :code, :id_medida, :id_laboratorio, :id_category, :id_branch, :location)";
        
        $stmt = $this->pdo->prepare($sql);
        
        $stmt->execute([
            ':name' => $data['name'],
            ':code' => $data['code'],
            ':id_medida' => !empty($data['id_medida']) ? $data['id_medida'] : null,
            ':location' => $data['location'] ?? null, // Añadido
            ':id_laboratorio' => $data['id_laboratorio'] > 0 ? $data['id_laboratorio'] : null,
            ':id_category' => $data['id_category'],
            ':id_branch' => $data['id_branch']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Crea el primer lote para un producto recién creado.
     */
    public function createInitialBatch(int $id_product, array $data): bool
    {
        $sql = "INSERT INTO batch (id_product, id_branch, batch_number, expiration_date, stock, purchase_price, sale_price, sale_price_b, sale_price_c) 
                VALUES (:id_product, :id_branch, :batch_number, :expiration_date, :stock, :purchase_price, :sale_price, :sale_price_b, :sale_price_c)";
        
        $stmt = $this->pdo->prepare($sql);
        // Usar datos de precio del formulario y valores por defecto/ejemplo para el lote
        return $stmt->execute([
            ':id_product' => $id_product,
            ':id_branch' => $data['id_branch'],
            ':batch_number' => 'INI-' . time(), // Generar un número de lote inicial
            ':expiration_date' => date('Y-m-d', strtotime('+5 year')), // Asignar una fecha de caducidad lejana
            ':stock' => 0, // Stock inicial por defecto es 0
            ':purchase_price' => $data['purchase_price'] ?? 0,
            ':sale_price' => $data['sale_price_a'] ?? 0,
            ':sale_price_b' => $data['sale_price_b'] ?? 0,
            ':sale_price_c' => $data['sale_price_c'] ?? 0
        ]);
    }

    /**
     * Encuentra un producto por su ID.
     *
     * @param int $id_product El ID del producto.
     * @return array|false Los datos del producto o false si no se encuentra.
     */
    public function findById(int $id_product)
    {
        $sql = "SELECT * FROM product WHERE id_product = :id_product"; // Se mantiene simple para obtener todos los campos
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_product' => $id_product]);
        return $stmt->fetch();
    }

    /**
     * Encuentra un producto por su código.
     *
     * @param string $code El código del producto.
     * @return array|false Los datos del producto o false si no se encuentra.
     */
    public function findByCode(string $code)
    {
        $sql = "SELECT * FROM product WHERE code = :code";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':code' => $code]);
        return $stmt->fetch();
    }

    /**
     * Encuentra un producto por su ID y une los nombres de laboratorio y medida.
     *
     * @param int $id_product El ID del producto.
     * @return array|false Los datos del producto con detalles o false si no se encuentra.
     */
    public function findWithDetailsById(int $id_product)
    {
        $sql = "SELECT 
                    p.id_product, p.name, p.code, 
                    m.descripcion as medida, 
                    c.name as category_name,
                    l.nombre as laboratorio
                FROM product p
                LEFT JOIN laboratorio l ON p.id_laboratorio = l.id_laboratorio
                LEFT JOIN medida m ON p.id_medida = m.id_medida
                LEFT JOIN category c ON p.id_category = c.id_category
                WHERE p.id_product = :id_product";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_product' => $id_product]);
        return $stmt->fetch();
    }

    /**
     * Actualiza un producto existente en la base de datos.
     *
     * @param int $id_product El ID del producto a actualizar.
     * @param array $data Los nuevos datos del producto.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function update(int $id_product, array $data): bool
    {
        $sql = "UPDATE product SET 
                    name = :name, 
                    code = :code, 
                    id_medida = :id_medida, 
                    id_laboratorio = :id_laboratorio, 
                    id_category = :id_category,
                    location = :location -- Añadido
                WHERE id_product = :id_product";
        
        $stmt = $this->pdo->prepare($sql);

        // Añadir el id_product a los datos para el execute
        $data['id_product'] = $id_product;

        // El array de datos para execute debe coincidir con los placeholders
        return $stmt->execute([
            ':name' => $data['name'],
            ':code' => $data['code'],
            ':id_medida' => isset($data['id_medida']) ? $data['id_medida'] : null,
            ':id_laboratorio' => isset($data['id_laboratorio']) ? $data['id_laboratorio'] : null,
            ':location' => $data['location'] ?? null, // Añadido
            ':id_category' => $data['id_category'],
            ':id_product' => $data['id_product']
        ]);
    }

    /**
     * Obtiene todas las imágenes de un producto.
     * @param int $id_product
     * @return array
     */
    public function getImages(int $id_product): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM product_images WHERE id_product = :id_product ORDER BY is_primary DESC, created_at ASC");
        $stmt->execute([':id_product' => $id_product]);
        return $stmt->fetchAll();
    }

    /**
     * Añade una nueva imagen a un producto.
     * @param int $id_product
     * @param string $image_path
     * @return bool
     */
    public function addImage(int $id_product, string $image_path): bool
    {
        // Verificar si ya hay una imagen principal. Si no, esta será la principal.
        $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM product_images WHERE id_product = :id_product AND is_primary = 1");
        $stmtCheck->execute([':id_product' => $id_product]);
        $hasPrimary = $stmtCheck->fetchColumn() > 0;

        $sql = "INSERT INTO product_images (id_product, image_path, is_primary) VALUES (:id_product, :image_path, :is_primary)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_product' => $id_product,
            ':image_path' => $image_path,
            ':is_primary' => $hasPrimary ? 0 : 1
        ]);
    }

    /**
     * Establece una imagen como la principal para un producto.
     * @param int $id_product
     * @param int $id_product_image
     * @return bool
     */
    public function setPrimaryImage(int $id_product, int $id_product_image): bool
    {
        $this->pdo->beginTransaction();
        // Primero, quitar la marca de principal a todas las imágenes de este producto
        $stmtReset = $this->pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE id_product = :id_product");
        $stmtReset->execute([':id_product' => $id_product]);
        // Luego, establecer la nueva imagen principal
        $stmtSet = $this->pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id_product_image = :id_product_image AND id_product = :id_product");
        $success = $stmtSet->execute([':id_product_image' => $id_product_image, ':id_product' => $id_product]);
        $this->pdo->commit();
        return $success;
    }

    public function deleteImage(int $id_product_image): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM product_images WHERE id_product_image = :id_product_image");
        return $stmt->execute([':id_product_image' => $id_product_image]);
    }
}