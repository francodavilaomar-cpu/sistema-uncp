<?php
require_once __DIR__ . '/../config.php';

// Verificar autenticación
requireLogin();

// Obtener información del usuario actual
function getCurrentUser() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    return $stmt->fetch();
}

$usuario_actual = getCurrentUser();
?>