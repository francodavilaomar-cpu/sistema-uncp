<?php
require_once 'config.php';
require_once 'includes/auth.php';

$page_title = 'Panel de Control';

// Obtener estadísticas según el rol
if (esEncargado()) {
    // Estadísticas para administradores y encargados
    $stats = [
        'total_equipos' => $pdo->query("SELECT COUNT(*) FROM equipos")->fetchColumn(),
        'equipos_disponibles' => $pdo->query("SELECT COUNT(*) FROM equipos WHERE estado = 'disponible'")->fetchColumn(),
        'equipos_prestados' => $pdo->query("SELECT COUNT(*) FROM equipos WHERE estado = 'prestado'")->fetchColumn(),
        'equipos_mantenimiento' => $pdo->query("SELECT COUNT(*) FROM equipos WHERE estado = 'mantenimiento'")->fetchColumn(),
        'prestamos_activos' => $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo'")->fetchColumn(),
        'prestamos_vencidos' => $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo' AND fecha_devolucion_esperada < NOW()")->fetchColumn(),
        'reservas_pendientes' => $pdo->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn(),
    ];
    
    if (esAdmin()) {
        $stats['total_usuarios'] = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 1")->fetchColumn();
    }
    
    // Últimos préstamos
    $stmt = $pdo->query("
        SELECT p.*, e.nombre as equipo_nombre, u.nombre as usuario_nombre 
        FROM prestamos p
        JOIN equipos e ON p.equipo_id = e.id
        JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.fecha_prestamo DESC
        LIMIT 10
    ");
    $ultimos_prestamos = $stmt->fetchAll();
} else {
    // Estadísticas para usuarios normales
    $usuario_id = $_SESSION['usuario_id'];
    
    $stats = [
        'mis_prestamos_activos' => $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND estado = 'activo'")->execute([$usuario_id]) ? 
                                   $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND estado = 'activo'")->fetchColumn() : 0,
        'mis_reservas' => $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE usuario_id = ? AND estado IN ('pendiente', 'confirmada')")->execute([$usuario_id]) ?
                        $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE usuario_id = ? AND estado IN ('pendiente', 'confirmada')")->fetchColumn() : 0,
    ];
    
    // Últimos préstamos del usuario
    $stmt = $pdo->prepare("
        SELECT p.*, e.nombre as equipo_nombre 
        FROM prestamos p
        JOIN equipos e ON p.equipo_id = e.id
        WHERE p.usuario_id = ?
        ORDER BY p.fecha_prestamo DESC
        LIMIT 10
    ");
    $stmt->execute([$usuario_id]);
    $ultimos_prestamos = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h4>Bienvenido, <?php echo htmlspecialchars($usuario_actual['nombre']); ?> 👋</h4>
        <p class="text-muted">Resumen general del sistema de préstamos</p>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <?php if (esEncargado()): ?>
        <div class="col-md-3 mb-3">
            <div class="card card-statistics">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Equipos</h6>
                            <h2 class="mb-0"><?php echo $stats['total_equipos']; ?></h2>
                        </div>
                        <i class="bi bi-pc-display" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Disponibles</h6>
                            <h2 class="mb-0"><?php echo $stats['equipos_disponibles']; ?></h2>
                        </div>
                        <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Prestados</h6>
                            <h2 class="mb-0"><?php echo $stats['equipos_prestados']; ?></h2>
                        </div>
                        <i class="bi bi-arrow-left-right" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Vencidos</h6>
                            <h2 class="mb-0"><?php echo $stats['prestamos_vencidos']; ?></h2>
                        </div>
                        <i class="bi bi-exclamation-triangle" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (esAdmin()): ?>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Usuarios</h6>
                            <h2 class="mb-0"><?php echo $stats['total_usuarios']; ?></h2>
                        </div>
                        <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="col-md-4 mb-3">
            <div class="card card-statistics">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Mis Préstamos Activos</h6>
                            <h2 class="mb-0"><?php echo $stats['mis_prestamos_activos']; ?></h2>
                        </div>
                        <i class="bi bi-laptop" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Mis Reservas</h6>
                            <h2 class="mb-0"><?php echo $stats['mis_reservas']; ?></h2>
                        </div>
                        <i class="bi bi-calendar-check" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6 class="card-title mb-3">¿Necesitas un equipo?</h6>
                    <a href="reservas/crear.php" class="btn btn-light">Reservar Ahora</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Últimos préstamos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i> 
                    <?php echo esEncargado() ? 'Últimos Préstamos' : 'Mi Historial Reciente'; ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Usuario</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Devolución</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_prestamos as $prestamo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prestamo['equipo_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($prestamo['usuario_nombre'] ?? ''); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($prestamo['fecha_prestamo'])); ?></td>
                                    <td><?php echo $prestamo['fecha_devolucion_esperada'] ? date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) : '-'; ?></td>
                                    <td>
                                        <?php
                                        $badge_class = '';
                                        $badge_text = '';
                                        switch ($prestamo['estado']) {
                                            case 'activo':
                                                $badge_class = 'badge-prestado';
                                                $badge_text = 'Activo';
                                                break;
                                            case 'devuelto':
                                                $badge_class = 'badge-disponible';
                                                $badge_text = 'Devuelto';
                                                break;
                                            case 'vencido':
                                                $badge_class = 'badge-vencido';
                                                $badge_text = 'Vencido';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($ultimos_prestamos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        No hay préstamos registrados
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>