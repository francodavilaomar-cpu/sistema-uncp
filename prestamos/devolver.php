<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin', 'encargado']);

$page_title = 'Devolver Equipo';
$errores = [];

// Obtener préstamos activos
$prestamos_activos = $pdo->query("
    SELECT p.*, e.codigo_inventario, e.nombre as equipo_nombre, u.nombre as usuario_nombre 
    FROM prestamos p
    JOIN equipos e ON p.equipo_id = e.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.estado = 'activo'
    ORDER BY p.fecha_prestamo DESC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prestamo_id = $_POST['prestamo_id'] ?? '';
    $fecha_devolucion_real = $_POST['fecha_devolucion_real'] ?? date('Y-m-d H:i');
    $estado_equipo = $_POST['estado_equipo'] ?? 'disponible';
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    if (empty($prestamo_id)) {
        $errores[] = 'Seleccione un préstamo activo.';
    }
    
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();
            
            // Obtener información del préstamo
            $stmt = $pdo->prepare("SELECT * FROM prestamos WHERE id = ? AND estado = 'activo'");
            $stmt->execute([$prestamo_id]);
            $prestamo = $stmt->fetch();
            
            if (!$prestamo) {
                throw new Exception('Préstamo no encontrado o ya devuelto.');
            }
            
            // Verificar si está vencido
            $estado_prestamo = (strtotime($fecha_devolucion_real) > strtotime($prestamo['fecha_devolucion_esperada'])) 
                ? 'vencido' : 'devuelto';
            
            // Actualizar préstamo
            $stmt = $pdo->prepare("
                UPDATE prestamos 
                SET fecha_devolucion_real = ?, estado = ?, observaciones = CONCAT(IFNULL(observaciones, ''), ?)
                WHERE id = ?
            ");
            $nota_adicional = $observaciones ? " - Devolución: $observaciones" : '';
            $stmt->execute([$fecha_devolucion_real, $estado_prestamo, $nota_adicional, $prestamo_id]);
            
            // Actualizar estado del equipo
            $stmt = $pdo->prepare("UPDATE equipos SET estado = ? WHERE id = ?");
            $stmt->execute([$estado_equipo, $prestamo['equipo_id']]);
            
            // Registrar en historial
            $stmt = $pdo->prepare("
                INSERT INTO historial_movimientos (equipo_id, usuario_id, tipo, detalle) 
                VALUES (?, ?, 'devolucion', ?)
            ");
            $stmt->execute([$prestamo['equipo_id'], $prestamo['usuario_id'], "Devolución de equipo"]);
            
            $pdo->commit();
            
            $_SESSION['mensaje'] = 'Equipo devuelto exitosamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: vencidos.php');
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Devolver Equipo</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="prestamo_id" class="form-label">Préstamo Activo *</label>
                        <select class="form-select" id="prestamo_id" name="prestamo_id" required>
                            <option value="">Seleccionar préstamo...</option>
                            <?php foreach ($prestamos_activos as $prestamo): ?>
                                <option value="<?php echo $prestamo['id']; ?>">
                                    <?php echo htmlspecialchars($prestamo['codigo_inventario'] . ' - ' . $prestamo['equipo_nombre'] . ' - ' . $prestamo['usuario_nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Seleccione un préstamo.</div>
                        <?php if (empty($prestamos_activos)): ?>
                            <div class="alert alert-info mt-2">
                                <i class="bi bi-info-circle"></i> No hay préstamos activos.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_devolucion_real" class="form-label">Fecha de Devolución Real *</label>
                            <input type="datetime-local" class="form-control" id="fecha_devolucion_real" 
                                   name="fecha_devolucion_real" required
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="estado_equipo" class="form-label">Estado del Equipo al Devolver</label>
                            <select class="form-select" id="estado_equipo" name="estado_equipo">
                                <option value="disponible">Disponible</option>
                                <option value="mantenimiento">Enviar a Mantenimiento</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones de Devolución</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                  placeholder="Estado del equipo, accesorios, daños, etc."></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="vencidos.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Confirmar Devolución
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>