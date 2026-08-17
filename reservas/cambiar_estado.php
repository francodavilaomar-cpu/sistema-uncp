<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin', 'encargado']);

$id = $_GET['id'] ?? 0;
$nuevo_estado = $_GET['estado'] ?? '';

// Validar estado
$estados_validos = ['confirmada', 'cancelada', 'completada'];
if (!in_array($nuevo_estado, $estados_validos)) {
    $_SESSION['mensaje'] = 'Estado no válido.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: listar.php');
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);
    
    $mensajes = [
        'confirmada' => 'Reserva confirmada exitosamente.',
        'cancelada' => 'Reserva cancelada exitosamente.',
        'completada' => 'Reserva marcada como completada.'
    ];
    
    $_SESSION['mensaje'] = $mensajes[$nuevo_estado];
    $_SESSION['mensaje_tipo'] = 'success';
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al actualizar: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

header('Location: listar.php');
exit();
?>