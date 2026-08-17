<?php
// Configuración de la base de datos
$db_host = 'sqlXXX.infinityfree.com'; // Reemplaza con tu host
$db_name = 'if0_XXXXXXX_prestamos'; // Reemplaza con tu nombre de BD
$db_user = 'if0_XXXXXXX'; // Reemplaza con tu usuario
$db_pass = 'tu_contraseña'; // Reemplaza con tu contraseña

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log('Error de conexión: ' . $e->getMessage());
    die('Error de conexión a la base de datos. Por favor contacte al administrador.');
}
?>