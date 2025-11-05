<?php
/**
 * Controlador para la autenticación de usuarios.
 */

// 1. Iniciar la sesión
// Es lo primero que debemos hacer para poder acceder a $_SESSION.
session_start();

// 2. Verificar que la solicitud sea por método POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Si no es POST, redirigir al login.
    header("Location: ../views/login.php"); // Esta ruta ahora será correcta
    exit;
}

// 3. Incluir la conexión a la base de datos
require_once '../db_connect.php'; // Ajustamos la ruta a la conexión
require_once '../models/User.php'; // Necesario para el modelo de usuario
require_once '../models/Role.php'; // Necesario para el modelo de Rol

// 4. Recibir y limpiar los datos del formulario
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    // Redirigir si los campos están vacíos
    header("Location: ../views/login.php?error=empty"); // Esta ruta ahora será correcta
    exit;
}

try {
    // 5. Preparar y ejecutar la consulta para obtener el usuario
    // Usamos sentencias preparadas para prevenir inyección SQL.
    // MODIFICADO: Se une con la tabla 'person' y se selecciona 'id_rol'.
    $sql = "SELECT 
                u.id_user, u.username, u.password, u.id_branch, u.id_rol, p.name 
            FROM user u
            JOIN person p ON u.id_person = p.id_person
            WHERE u.username = :username AND u.is_active = 1
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    // 6. Verificar si el usuario existe y la contraseña es correcta
    if ($user && password_verify($password, $user['password'])) {
        // Contraseña correcta. Ahora obtenemos el modo fiscal.

        // MODIFICADO: Se consulta la tabla 'system_config' para obtener todas las configuraciones.
        $stmt_settings = $pdo->prepare("SELECT key_name, value FROM system_config");
        $stmt_settings->execute();
        $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

        $fiscal_mode = $settings['FISCAL_MODE'] ?? 'TRADICIONAL';
        $logo_path = $settings['EMPRESA_LOGO_PATH'] ?? 'assets/img/logo_empresa.png';

        // 7. Regenerar el ID de sesión para prevenir ataques de fijación de sesión
        session_regenerate_id(true);

        // 8. Guardar los datos del usuario y configuración en la sesión
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role_id'] = $user['id_rol']; // Guardamos el ID del rol
        $_SESSION['user_name'] = $user['name']; // Guardamos el nombre real de la persona
        $_SESSION['id_branch'] = $user['id_branch'];
        $_SESSION['FISCAL_MODE'] = $fiscal_mode;
        $_SESSION['EMPRESA_LOGO_PATH'] = $logo_path; // Guardar la ruta del logo en la sesión

        // Obtener y guardar los permisos del usuario en la sesión (CORREGIDO)
        // Usamos el modelo de Rol para obtener los NOMBRES de los permisos.
        $roleModel = new Role($pdo);
        $_SESSION['user_permissions'] = $roleModel->getPermissionNamesByRoleId($user['id_rol']);

        // 9. Redirigir al dashboard
        header("Location: ../views/dashboard.php"); // Esta ruta ahora será correcta
        exit;

    } else {
        // Usuario no encontrado o contraseña incorrecta
        header("Location: ../views/login.php?error=credentials"); // Esta ruta ahora será correcta
        exit;
    }

} catch (PDOException $e) {
    // Manejar errores de base de datos
    error_log("Error en login: " . $e->getMessage());
    header("Location: ../views/login.php?error=db"); // Esta ruta ahora será correcta
    exit;
}

?>