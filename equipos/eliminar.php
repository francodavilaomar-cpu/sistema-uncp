<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    
    try {
        // Verificar si el equipo tiene préstamos activos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE equipo_id = ? AND estado = 'activo'");
        $stmt->execute([$id]);
        $prestamos_activos = $stmt->fetchColumn();
        
        if ($prestamos_activos > 0) {
            $_SESSION['mensaje'] = 'No se puede eliminar: el equipo tiene préstamos activos.';
            $_SESSION['mensaje_tipo'] = 'danger';
        } else {
            // Registrar en historial antes de eliminar
            $stmt = $pdo->prepare("SELECT nombre FROM equipos WHERE id = ?");
            $stmt->execute([$id]);
            $equipo = $stmt->fetch();
            
            if ($equipo) {
                $stmt = $pdo->prepare("
                    INSERT INTO historial_movimientos (equipo_id, usuario_id, tipo, detalle) 
                    VALUES (?, ?, 'baja', ?)
                ");
                $stmt->execute([$id, $_SESSION['usuario_id'], "Equipo eliminado: " . $equipo['nombre']]);
                
                // Eliminar equipo
                $stmt = $pdo->prepare("DELETE FROM equipos WHERE id = ?");
                $stmt->execute([$id]);
                
                $_SESSION['mensaje'] = 'Equipo eliminado exitosamente.';
                $_SESSION['mensaje_tipo'] = 'success';
            }
        }
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al eliminar: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'danger';
    }
}

header('Location: listar.php');
exit();
?>