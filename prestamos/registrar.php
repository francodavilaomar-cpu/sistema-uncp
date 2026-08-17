<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin', 'encargado']);

$page_title = 'Registrar Préstamo';
$errores = [];

// Obtener equipos disponibles
$equipos_disponibles = $pdo->query("
    SELECT id, codigo_inventario, nombre 
    FROM equipos 
    WHERE estado = 'disponible' 
    ORDER BY nombre
")->fetchAll();

// Obtener usuarios activos
$usuarios = $pdo->query("
    SELECT id, nombre, email 
    FROM usuarios 
    WHERE estado = 1 
    ORDER BY nombre
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'] ?? '';
    $equipo_id = $_POST['equipo_id'] ?? '';
    $fecha_prestamo = $_POST['fecha_prestamo'] ?? date('Y-m-d H:i');
    $fecha_devolucion_esperada = $_POST['fecha_devolucion_esperada'] ?? '';
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Validaciones
    if (empty($usuario_id)) {
        $errores[] = 'Seleccione un usuario.';
    }
    if (empty($equipo_id)) {
        $errores[] = 'Seleccione un equipo.';
    }
    if (empty($fecha_devolucion_esperada)) {
        $errores[] = 'La fecha de devolución esperada es obligatoria.';
    } elseif (strtotime($fecha_devolucion_esperada) <= strtotime($fecha_prestamo)) {
        $errores[] = 'La fecha de devolución debe ser posterior a la fecha de préstamo.';
    }
    
    // Verificar que el equipo esté disponible
    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT estado FROM equipos WHERE id = ?");
        $stmt->execute([$equipo_id]);
        $equipo = $stmt->fetch();
        
        if (!$equipo || $equipo['estado'] != 'disponible') {
            $errores[] = 'El equipo seleccionado no está disponible.';
        }
    }
    
    // Verificar que el usuario no tenga préstamos vencidos
    if (empty($errores)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM prestamos 
            WHERE usuario_id = ? AND estado = 'activo' AND fecha_devolucion_esperada < NOW()
        ");
        $stmt->execute([$usuario_id]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = 'El usuario tiene préstamos vencidos. Debe devolverlos antes de solicitar otro.';
        }
    }
    
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();
            
            // Insertar préstamo
            $stmt = $pdo->prepare("
                INSERT INTO prestamos (usuario_id, equipo_id, fecha_prestamo, fecha_devolucion_esperada, observaciones, estado) 
                VALUES (?, ?, ?, ?, ?, 'activo')
            ");
            $stmt->execute([$usuario_id, $equipo_id, $fecha_prestamo, $fecha_devolucion_esperada, $observaciones]);
            
            // Actualizar estado del equipo
            $stmt = $pdo->prepare("UPDATE equipos SET estado = 'prestado' WHERE id = ?");
            $stmt->execute([$equipo_id]);
            
            // Registrar en historial
            $stmt = $pdo->prepare("SELECT nombre FROM equipos WHERE id = ?");
            $stmt->execute([$equipo_id]);
            $equipo_nombre = $stmt->fetch()['nombre'];
            
            $stmt = $pdo->prepare("
                INSERT INTO historial_movimientos (equipo_id, usuario_id, tipo, detalle) 
                VALUES (?, ?, 'prestamo', ?)
            ");
            $stmt->execute([$equipo_id, $usuario_id, "Préstamo de equipo: $equipo_nombre"]);
            
            $pdo->commit();
            
            $_SESSION['mensaje'] = 'Préstamo registrado exitosamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: vencidos.php');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = 'Error al registrar el préstamo: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> Registrar Préstamo</h5>
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
                        <label for="usuario_id" class="form-label">Usuario *</label>
                        <select class="form-select" id="usuario_id" name="usuario_id" required>
                            <option value="">Seleccionar usuario...</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>"
                                        <?php echo ($_POST['usuario_id'] ?? '') == $usuario['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['nombre'] . ' - ' . $usuario['email']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Seleccione un usuario.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="equipo_id" class="form-label">Equipo *</label>
                        <select class="form-select" id="equipo_id" name="equipo_id" required>
                            <option value="">Seleccionar equipo disponible...</option>
                            <?php foreach ($equipos_disponibles as $equipo): ?>
                                <option value="<?php echo $equipo['id']; ?>"
                                        <?php echo ($_POST['equipo_id'] ?? '') == $equipo['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($equipo['codigo_inventario'] . ' - ' . $equipo['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Seleccione un equipo.</div>
                        <?php if (empty($equipos_disponibles)): ?>
                            <div class="alert alert-warning mt-2">
                                <i class="bi bi-exclamation-triangle"></i> No hay equipos disponibles para préstamo.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_prestamo" class="form-label">Fecha de Préstamo *</label>
                            <input type="datetime-local" class="form-control" id="fecha_prestamo" 
                                   name="fecha_prestamo" required
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="fecha_devolucion_esperada" class="form-label">Fecha de Devolución Esperada *</label>
                            <input type="date" class="form-control" id="fecha_devolucion_esperada" 
                                   name="fecha_devolucion_esperada" required
                                   value="<?php echo htmlspecialchars($_POST['fecha_devolucion_esperada'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                  placeholder="Notas adicionales..."><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="../dashboard.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Registrar Préstamo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>