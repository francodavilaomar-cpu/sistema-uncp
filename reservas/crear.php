<?php
require_once '../config.php';
require_once '../includes/auth.php';

$page_title = 'Nueva Reserva';
$errores = [];

// Obtener equipos disponibles
$equipos_disponibles = $pdo->query("
    SELECT id, codigo_inventario, nombre 
    FROM equipos 
    WHERE estado = 'disponible' 
    ORDER BY nombre
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipo_id = $_POST['equipo_id'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    
    // Validaciones
    if (empty($equipo_id)) {
        $errores[] = 'Seleccione un equipo.';
    }
    if (empty($fecha_inicio) || empty($fecha_fin)) {
        $errores[] = 'Las fechas de inicio y fin son obligatorias.';
    } elseif (strtotime($fecha_fin) <= strtotime($fecha_inicio)) {
        $errores[] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
    }
    
    // Verificar que no haya reservas solapadas para el mismo equipo
    if (empty($errores)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reservas 
            WHERE equipo_id = ? 
            AND estado IN ('pendiente', 'confirmada')
            AND (
                (fecha_inicio BETWEEN ? AND ?) OR 
                (fecha_fin BETWEEN ? AND ?) OR
                (fecha_inicio <= ? AND fecha_fin >= ?)
            )
        ");
        $stmt->execute([$equipo_id, $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
        
        if ($stmt->fetchColumn() > 0) {
            $errores[] = 'El equipo ya está reservado en ese rango de fechas.';
        }
    }
    
    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reservas (usuario_id, equipo_id, fecha_reserva, fecha_inicio, fecha_fin, estado) 
                VALUES (?, ?, NOW(), ?, ?, 'pendiente')
            ");
            $stmt->execute([$_SESSION['usuario_id'], $equipo_id, $fecha_inicio, $fecha_fin]);
            
            $_SESSION['mensaje'] = 'Reserva creada exitosamente. Pendiente de confirmación.';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: listar.php');
            exit();
        } catch (PDOException $e) {
            $errores[] = 'Error al crear la reserva: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-calendar-plus"></i> Crear Nueva Reserva</h5>
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
                        <label for="equipo_id" class="form-label">Equipo a Reservar *</label>
                        <select class="form-select" id="equipo_id" name="equipo_id" required>
                            <option value="">Seleccionar equipo...</option>
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
                                <i class="bi bi-exclamation-triangle"></i> No hay equipos disponibles para reservar.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                            <input type="datetime-local" class="form-control" id="fecha_inicio" 
                                   name="fecha_inicio" required
                                   value="<?php echo htmlspecialchars($_POST['fecha_inicio'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                            <input type="datetime-local" class="form-control" id="fecha_fin" 
                                   name="fecha_fin" required
                                   value="<?php echo htmlspecialchars($_POST['fecha_fin'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        La reserva estará pendiente hasta que un encargado la confirme.
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-calendar-check"></i> Crear Reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>