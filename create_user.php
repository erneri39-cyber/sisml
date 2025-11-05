<?php
/**
 * Script para crear un usuario administrador inicial.
 * ADAPTADO PARA LA NUEVA ESTRUCTURA DE BASE DE DATOS (con tablas 'person' y 'rol').
 * Ejecutar este script una sola vez desde el navegador o la línea de comandos.
 * Por seguridad, se recomienda eliminar este archivo después de su uso.
 */

echo "<pre>"; // Para un formato de salida más legible

require_once 'db_connect.php'; // Asegúrate que la ruta es correcta

// --- DATOS DEL NUEVO USUARIO ---
// Puedes cambiar estos valores si lo deseas.
$person_name = 'Administrador del Sistema';
$person_document = '00000000-0'; // DUI/Documento de ejemplo
$username = 'admin';
$password_plain = 'admin123'; // ¡Usa una contraseña segura en un entorno real!
$role_name = 'Administrador';
$id_branch = 1; // ID de la sucursal (ej: 1 para Casa Matriz, asegúrate que exista en la tabla 'branch')

// 1. Hashear la contraseña para almacenarla de forma segura.
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

echo "Intentando crear el usuario '{$username}'...\n";

// Usaremos una transacción para asegurar que todas las operaciones se completen con éxito.
try {
    $pdo->beginTransaction();

    // PASO 1: Verificar si el usuario ya existe para evitar duplicados.
    $stmt = $pdo->prepare("SELECT id_user FROM user WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
        die("ERROR: El usuario '{$username}' ya existe en la base de datos.\n");
    }
    echo "OK: El usuario '{$username}' no existe, se procederá a crearlo.\n";

    // PASO 2: Obtener el ID del rol 'Administrador'.
    // Asegúrate de haber creado la tabla 'rol' y haber insertado los roles.
    $stmt_role = $pdo->prepare("SELECT id_rol FROM rol WHERE name = :role_name");
    $stmt_role->execute(['role_name' => $role_name]);
    $role_result = $stmt_role->fetch();
    if (!$role_result) {
        throw new Exception("El rol '{$role_name}' no fue encontrado en la tabla 'rol'. Por favor, créalo primero.");
    }
    $id_rol = $role_result['id_rol'];
    echo "OK: Rol '{$role_name}' encontrado con ID: {$id_rol}.\n";

    // PASO 3: Crear el registro en la tabla 'person'.
    $stmt_person = $pdo->prepare(
        "INSERT INTO person (name, document_number, person_type) VALUES (:name, :document_number, 'Empleado')"
    );
    $stmt_person->execute([
        'name' => $person_name,
        'document_number' => $person_document
    ]);
    $id_person = $pdo->lastInsertId();
    echo "OK: Persona '{$person_name}' creada con ID: {$id_person}.\n";

    // PASO 4: Crear el registro en la tabla 'user'.
    $stmt_user = $pdo->prepare(
        "INSERT INTO user (id_person, username, password, id_rol, id_branch) VALUES (:id_person, :username, :password, :id_rol, :id_branch)"
    );
    $stmt_user->execute([
        'id_person' => $id_person,
        'username' => $username,
        'password' => $password_hashed,
        'id_rol' => $id_rol,
        'id_branch' => $id_branch
    ]);
    echo "OK: Usuario '{$username}' creado y vinculado a la persona y rol.\n";

    // Si todo fue bien, confirmamos los cambios.
    $pdo->commit();
    
    echo "¡ÉXITO! Usuario '{$username}' creado correctamente.\n";
    echo "Ya puedes iniciar sesión con el usuario '{$username}' y la contraseña '{$password_plain}'.\n";

} catch (Exception $e) {
    // Si algo falla, revertimos todos los cambios.
    $pdo->rollBack();
    die("ERROR: No se pudo crear el usuario. " . $e->getMessage() . "\n");
}

echo "</pre>";