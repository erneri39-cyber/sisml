<?php
/**
 * Controlador para la gestión de Productos (CRUD).
 */

session_start();
header('Content-Type: application/json');

// Proteger el endpoint: verificar sesión y método de solicitud
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/models/Product.php';

$method = $_SERVER['REQUEST_METHOD'];
$productModel = new Product($pdo);

switch ($method) {
    case 'POST':
        handlePostRequest($productModel); // Nueva función para manejar todos los POST
        break;
    case 'GET':
        handleGet($productModel);
        break;
    case 'PUT':
        handleUpdate($productModel);
        break;
    case 'DELETE':
        handleDelete($productModel);
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Método no soportado.']);
        break;
}

/**
 * Maneja todas las solicitudes POST, incluyendo creación de producto y gestión de imágenes.
 */
function handlePostRequest(Product $productModel) {
    $action = $_GET['action'] ?? '';

    if ($action === 'upload_image') {
        handleUploadImage($productModel);
    } elseif ($action === 'set_primary') {
        handleSetPrimaryImage($productModel);
    } else {
        // Si no hay acción específica, asumimos que es una creación de producto
        handleCreate($productModel);
    }
}

/**
 * Maneja la subida de una imagen de producto.
 */
function handleUploadImage(Product $productModel) {
    $productId = isset($_POST['id_product']) ? (int)$_POST['id_product'] : 0;
    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de producto no válido.']);
        exit;
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $uploadDir = dirname(__DIR__) . '/uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = uniqid() . '-' . basename($file['name']);
        $destination = $uploadDir . $fileName;
        $relativePath = 'uploads/products/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $productModel->addImage($productId, $relativePath);
            echo json_encode(['success' => true, 'message' => 'Imagen subida exitosamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No se recibió ninguna imagen o hubo un error en la subida.']);
    }
    exit;
}

/**
 * Establece una imagen como la principal para un producto.
 */
function handleSetPrimaryImage(Product $productModel) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id_product']) || !isset($data['id_product_image'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos para establecer imagen principal.']);
        exit;
    }
    $productModel->setPrimaryImage($data['id_product'], $data['id_product_image']);
    echo json_encode(['success' => true, 'message' => 'Imagen principal actualizada.']);
    exit;
}

/**
 * Maneja la creación de un nuevo producto.
 */
function handleCreate(Product $productModel) {
    $data = json_decode(file_get_contents('php://input'), true);

    // 1. Validación de campos obligatorios
    if (empty($data['name']) || empty($data['code']) || empty($data['id_category'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios (código, nombre o categoría).']);
        exit;
    }

    // 2. Añadir datos de sesión al array de datos
    $data['id_branch'] = (int)($_SESSION['id_branch'] ?? 0);
    if ($data['id_branch'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo determinar la sucursal del usuario.']);
        exit;
    }

    try {
        // INICIO DE LA TRANSACCIÓN
        $productModel->getDbConnection()->beginTransaction(); // Acceder al PDO a través de un método en el modelo

        // 3. Verificar si el código ya existe
        $existingProduct = $productModel->findByCode($data['code']);
        if ($existingProduct) {
            http_response_code(409); // 409 Conflict
            echo json_encode(['success' => false, 'message' => 'Error: El código del producto ya existe.']);
            $productModel->getDbConnection()->rollBack();
            exit;
        }

        // 4. Crear el Producto
        $newProductId = $productModel->create($data);

        if ($newProductId > 0) {
            // 5. Crear el Lote Inicial
            // Aunque no tengamos campos de precio, creamos un lote con precios 0 para consistencia.
            $productModel->createInitialBatch($newProductId, $data);

            // 6. Confirmar la transacción
            $productModel->getDbConnection()->commit();
            $newProductData = $productModel->findWithDetailsById($newProductId);

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Producto creado exitosamente.', 'data' => $newProductData]);
        } else {
            $productModel->getDbConnection()->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el producto.']);
        }
    } catch (PDOException $e) {
        $productModel->getDbConnection()->rollBack();
        error_log("Error al crear producto: " . $e->getMessage());
        http_response_code(500);
        // Revisar si es un error de código duplicado
        if ($e->getCode() == '23000') {
            echo json_encode(['success' => false, 'message' => 'Error: El código ya está en uso por otro producto.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error en la base de datos al crear el producto. Detalles: ' . $e->getMessage()]);
        }
    }
}

/**
 * Maneja la obtención de un producto por su ID.
 */
function handleGet(Product $productModel) {
    // --- NUEVA LÓGICA PARA VERIFICAR CÓDIGO ---
    if (isset($_GET['action']) && $_GET['action'] === 'get_images') {
        $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Falta el ID del producto.']);
            exit;
        }
        $images = $productModel->getImages($productId);
        echo json_encode(['success' => true, 'data' => $images]);
        exit;
    }
    // --- FIN LÓGICA IMÁGENES ---

    if (isset($_GET['action']) && $_GET['action'] === 'check_code') {
        $code = $_GET['code'] ?? '';
        $ignoreId = isset($_GET['ignore_id']) ? (int)$_GET['ignore_id'] : null;

        if (empty($code)) {
            echo json_encode(['exists' => false]); // No verificar si está vacío
            exit;
        }

        $product = $productModel->findByCode($code);
        $exists = false;
        if ($product) {
            // Si encontramos un producto, verificamos si es el mismo que estamos editando.
            // Si no es el mismo (o si no estamos editando), entonces el código "existe" como duplicado.
            if ($ignoreId === null || $product['id_product'] != $ignoreId) {
                $exists = true;
            }
        }
        echo json_encode(['exists' => $exists]);
        exit;
    }
    // --- FIN DE LA NUEVA LÓGICA ---

    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($productId <= 0) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Falta el ID del producto.']);
        exit;
    }

    $product = $productModel->findById($productId);
    if ($product) {
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
    }
}

/**
 * Maneja la actualización de un producto existente.
 */
function handleUpdate(Product $productModel) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Falta el ID del producto para actualizar.']);
        exit;
    }

    $productId = (int)$_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);

    // Validación
    if (empty($data['name']) || empty($data['code']) || empty($data['id_category'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nombre, código y categoría son obligatorios.']);
        exit;
    }

    try {
        // Verificación de código único ANTES de actualizar
        $existingProduct = $productModel->findByCode($data['code']);
        if ($existingProduct && $existingProduct['id_product'] != $productId) {
            http_response_code(409); // 409 Conflict
            echo json_encode(['success' => false, 'message' => 'Error: El código ya está en uso por otro producto.']);
            exit;
        }

        $success = $productModel->update($productId, $data);
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente.']);
        } else {
            // Esto puede pasar si no se cambió ningún dato, no es necesariamente un error.
            echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente (sin cambios detectados).']);
        }
    } catch (PDOException $e) {
        error_log("Error al actualizar producto: " . $e->getMessage());
        http_response_code(500);
        // El error de duplicado ya se maneja arriba, pero lo dejamos como fallback.
        if ($e->getCode() == '23000') {
            echo json_encode(['success' => false, 'message' => 'Error: El código ya está en uso por otro producto.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error en la base de datos al actualizar.']);
        }
    }
}

/**
 * Maneja la eliminación de una imagen o el establecimiento de una como principal.
 */
function handleDelete(Product $productModel) {
    $action = $_GET['action'] ?? '';
    $id_product_image = isset($_GET['id_image']) ? (int)$_GET['id_image'] : 0;

    if ($action === 'delete_image' && $id_product_image > 0) {
        // Opcional: obtener la ruta para borrar el archivo físico
        // $image = $pdo->...
        // unlink(dirname(__DIR__) . '/' . $image['image_path']);
        if ($productModel->deleteImage($id_product_image)) {
            echo json_encode(['success' => true, 'message' => 'Imagen eliminada.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la imagen.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción de eliminación no válida.']);
    }
}
?>