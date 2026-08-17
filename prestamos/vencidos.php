<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin', 'encargado']);

$page_title = 'Préstamos Activos y Vencidos';

// Obtener préstamos activos y vencidos
$stmt = $pdo->query("
    SELECT p.*, 
           e.codigo_inventario, e.nombre as equipo_nombre,
           u.nombre as usuario_nombre, u.email as usuario_email,
           DATEDIFF(NOW(), p.fecha_devolucion_esperada) as dias_vencido
    FROM prestamos p
    JOIN equipos e ON p.equipo_id = e.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.estado IN ('activo', 'vencido')
    ORDER BY 
        CASE WHEN p.fecha_devolucion_esperada < NOW() THEN 0 ELSE 1 END,
        p.fecha_devolucion_esperada ASC
");
$prestamos = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-clock-history"></i> Préstamos Activos</h4>
    <div>
        <a href="registrar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Préstamo
        </a>
        <a href="devolver.php" class="btn btn-success">
            <i class="bi bi-check-circle"></i> Devolver Equipo
        </a>
    </div>
</div>

<!-- Alertas de vencidos -->
<?php
$vencidos = array_filter($prestamos, function($p) { 
    return strtotime($p['fecha_devolucion_esperada']) < time(); 
});
if (!empty($vencidos)):
?>
<div class="alert alert-danger">
    <h5><i class="bi bi-exclamation-triangle"></i> ¡Atención! Hay <?php echo count($vencidos); ?> préstamo(s) vencido(s)</h5>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="tablaPrestamos">
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Usuario</th>
                        <th>Fecha Préstamo</th>
                        <th>Fecha Esperada</th>
                        <th>Días Vencido</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamos as $prestamo): 
                        $es_vencido = strtotime($prestamo['fecha_devolucion_esperada']) < time();
                    ?>
                        <tr class="<?php echo $es_vencido ? 'table-danger' : ''; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($prestamo['codigo_inventario']); ?></strong><br>
                                <small><?php echo htmlspecialchars($prestamo['equipo_nombre']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($prestamo['usuario_nombre']); ?><br>
                                <small><?php echo htmlspecialchars($prestamo['usuario_email']); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($prestamo['fecha_prestamo'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])); ?></td>
                            <td>
                                <?php if ($es_vencido): ?>
                                    <span class="text-danger fw-bold">
                                        <?php echo $prestamo['dias_vencido']; ?> día(s)
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">A tiempo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($es_vencido): ?>
                                    <span class="badge badge-vencido">Vencido</span>
                                <?php else: ?>
                                    <span class="badge badge-prestado">Activo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="devolver.php?prestamo_id=<?php echo $prestamo['id']; ?>" 
                                   class="btn btn-sm btn-success" title="Devolver">
                                    <i class="bi bi-check-circle"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($prestamos)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                <p class="mt-2">No hay préstamos activos</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <button class="btn btn-outline-success" onclick="exportTableToCSV('tablaPrestamos', 'prestamos_activos.csv')">
            <i class="bi bi-download"></i> Exportar CSV
        </button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>