<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin']);

$id = $_GET['id'] ?? 0;

try {
    // Obtener estado actual
    $stmt = $pdo->prepare("SELECT estado FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $nuevo_estado = $usuario['estado'] ? 0 : 1;
        
        $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
        $stmt->execute([$nuevo_estado, $id]);
        
        $_SESSION['mensaje'] = $nuevo_estado ? 'Usuario activado exitosamente.' : 'Usuario desactivado exitosamente.';
        $_SESSION['mensaje_tipo'] = 'success';
    }
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cambiar estado: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

header('Location: listar.php');
exit();
?>