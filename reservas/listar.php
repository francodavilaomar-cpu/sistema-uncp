<?php
require_once '../config.php';
require_once '../includes/auth.php';

$page_title = 'Gestión de Reservas';

// Obtener reservas según el rol
if (esEncargado()) {
    // Administradores y encargados ven todas las reservas
    $stmt = $pdo->query("
        SELECT r.*, e.nombre as equipo_nombre, e.codigo_inventario, 
               u.nombre as usuario_nombre, u.email as usuario_email
        FROM reservas r
        JOIN equipos e ON r.equipo_id = e.id
        JOIN usuarios u ON r.usuario_id = u.id
        ORDER BY r.fecha_reserva DESC
    ");
} else {
    // Usuarios normales solo ven sus reservas
    $stmt = $pdo->prepare("
        SELECT r.*, e.nombre as equipo_nombre, e.codigo_inventario
        FROM reservas r
        JOIN equipos e ON r.equipo_id = e.id
        WHERE r.usuario_id = ?
        ORDER BY r.fecha_reserva DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
}
$reservas = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-calendar-check"></i> Reservas</h4>
    <?php if (!esEncargado()): ?>
        <a href="crear.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Reserva
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="tablaReservas">
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <?php if (esEncargado()): ?>
                            <th>Usuario</th>
                        <?php endif; ?>
                        <th>Fecha Reserva</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estado</th>
                        <?php if (esEncargado()): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($reserva['codigo_inventario']); ?></strong><br>
                                <small><?php echo htmlspecialchars($reserva['equipo_nombre']); ?></small>
                            </td>
                            <?php if (esEncargado()): ?>
                                <td>
                                    <?php echo htmlspecialchars($reserva['usuario_nombre']); ?><br>
                                    <small><?php echo htmlspecialchars($reserva['usuario_email']); ?></small>
                                </td>
                            <?php endif; ?>
                            <td><?php echo date('d/m/Y H:i', strtotime($reserva['fecha_reserva'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($reserva['fecha_inicio'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($reserva['fecha_fin'])); ?></td>
                            <td>
                                <?php
                                $badge_class = '';
                                switch ($reserva['estado']) {
                                    case 'pendiente':
                                        $badge_class = 'badge bg-warning text-dark';
                                        break;
                                    case 'confirmada':
                                        $badge_class = 'badge bg-info';
                                        break;
                                    case 'cancelada':
                                        $badge_class = 'badge bg-danger';
                                        break;
                                    case 'completada':
                                        $badge_class = 'badge bg-success';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $badge_class; ?>">
                                    <?php echo ucfirst($reserva['estado']); ?>
                                </span>
                            </td>
                            <?php if (esEncargado()): ?>
                                <td>
                                    <?php if ($reserva['estado'] == 'pendiente'): ?>
                                        <a href="cambiar_estado.php?id=<?php echo $reserva['id']; ?>&estado=confirmada" 
                                           class="btn btn-sm btn-success" title="Confirmar">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                        <a href="cambiar_estado.php?id=<?php echo $reserva['id']; ?>&estado=cancelada" 
                                           class="btn btn-sm btn-danger" title="Cancelar">
                                            <i class="bi bi-x-circle"></i>
                                        </a>
                                    <?php elseif ($reserva['estado'] == 'confirmada'): ?>
                                        <a href="cambiar_estado.php?id=<?php echo $reserva['id']; ?>&estado=completada" 
                                           class="btn btn-sm btn-info" title="Completar">
                                            <i class="bi bi-check-square"></i>
                                        </a>
                                        <a href="cambiar_estado.php?id=<?php echo $reserva['id']; ?>&estado=cancelada" 
                                           class="btn btn-sm btn-danger" title="Cancelar">
                                            <i class="bi bi-x-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($reservas)): ?>
                        <tr>
                            <td colspan="<?php echo esEncargado() ? '7' : '6'; ?>" class="text-center text-muted py-3">
                                <i class="bi bi-calendar" style="font-size: 2rem;"></i>
                                <p class="mt-2">No hay reservas registradas</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <button class="btn btn-outline-success" onclick="exportTableToCSV('tablaReservas', 'reservas.csv')">
            <i class="bi bi-download"></i> Exportar CSV
        </button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>