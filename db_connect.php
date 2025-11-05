<?php
/**
 * Archivo de Conexión a la Base de Datos usando PDO.
 * Proyecto: Far-MaríadeLourdes
 */

// 1. Parámetros de conexión a la base de datos.
// Es una buena práctica definir estas credenciales en un lugar central.
// Para mayor seguridad en producción, considera usar variables de entorno.
define('DB_HOST', 'localhost'); // O la IP del servidor de tu base de datos
define('DB_USER', 'root');      // Tu usuario de MySQL
define('DB_PASS', '');          // Tu contraseña de MySQL
define('DB_NAME', 'sisfarmarialourdes'); // El nombre de la base de datos
define('DB_CHARSET', 'utf8mb4'); // Juego de caracteres para soportar emojis y caracteres especiales

// 2. Creación del DSN (Data Source Name)
// Especifica el tipo de base de datos, el host y el nombre de la DB.
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// 3. Opciones de configuración para PDO
// Estas opciones mejoran la seguridad y el manejo de errores.
$options = [
    // Lanza excepciones en caso de errores, en lugar de warnings silenciosos.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Devuelve los resultados como un array asociativo (ej: $fila['nombre_columna']).
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Desactiva la emulación de sentencias preparadas para mayor seguridad contra inyección SQL.
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4. Intentar la conexión a la base de datos
try {
    // Crear una nueva instancia de PDO para la conexión.
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Si la conexión falla, se captura la excepción y se muestra un mensaje de error.
    // En un entorno de producción, deberías registrar este error en un archivo de log
    // en lugar de mostrarlo en pantalla por seguridad.
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error: No se pudo conectar a la base de datos. Por favor, contacte al administrador.");
}

// Si llegas aquí, la variable $pdo contiene el objeto de conexión y está lista para ser usada.
// Ejemplo de cómo usarlo en otros archivos:
// require_once 'config/db_connect.php';
// $stmt = $pdo->query('SELECT * FROM usuarios');
// ...
