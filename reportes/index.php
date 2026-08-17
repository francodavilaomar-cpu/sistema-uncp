<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin', 'encargado']);

$page_title = 'Reportes';

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$categoria_id = $_GET['categoria_id'] ?? '';
$estado = $_GET['estado'] ?? '';

// Obtener categorías para el filtro
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();

// Construir consulta base
$sql = "
    SELECT p.*, e.nombre as equipo_nombre, e.codigo_inventario, 
           c.nombre as categoria_nombre, u.nombre as usuario_nombre
    FROM prestamos p
    JOIN equipos e ON p.equipo_id = e.id
    LEFT JOIN categorias c ON e.categoria_id = c.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.fecha_prestamo BETWEEN ? AND ?
";
$params = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];

if ($categoria_id) {
    $sql .= " AND e.categoria_id = ?";
    $params[] = $categoria_id;
}

if ($estado) {
    $sql .= " AND p.estado = ?";
    $params[] = $estado;
}

$sql .= " ORDER BY p.fecha_prestamo DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$prestamos = $stmt->fetchAll();

// Estadísticas para el período
$stats = [
    'total_prestamos' => count($prestamos),
    'prestamos_activos' => count(array_filter($prestamos, function($p) { return $p['estado'] == 'activo'; })),
    'prestamos_devueltos' => count(array_filter($prestamos, function($p) { return $p['estado'] == 'devuelto'; })),
    'prestamos_vencidos' => count(array_filter($prestamos, function($p) { return $p['estado'] == 'vencido'; })),
];

// Equipos más prestados
$sql = "
    SELECT e.nombre, e.codigo_inventario, COUNT(p.id) as total_prestamos
    FROM prestamos p
    JOIN equipos e ON p.equipo_id = e.id
    WHERE p.fecha_prestamo BETWEEN ? AND ?
    GROUP BY e.id
    ORDER BY total_prestamos DESC
    LIMIT 10
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59']);
$equipos_mas_prestados = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-file-earmark-bar-graph"></i> Reportes</h4>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                       value="<?php echo $fecha_inicio; ?>">
            </div>
            <div class="col-md-3">
                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                       value="<?php echo $fecha_fin; ?>">
            </div>
            <div class="col-md-3">
                <label for="categoria_id" class="form-label">Categoría</label>
                <select class="form-select" id="categoria_id" name="categoria_id">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>"
                                <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="activo" <?php echo $estado == 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="devuelto" <?php echo $estado == 'devuelto' ? 'selected' : ''; ?>>Devuelto</option>
                    <option value="vencido" <?php echo $estado == 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Generar Reporte
                </button>
                <button type="button" class="btn btn-success" onclick="exportTableToCSV('tablaReporte', 'reporte_prestamos_<?php echo $fecha_inicio; ?>_<?php echo $fecha_fin; ?>.csv')">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6>Total Préstamos</h6>
                <h2><?php echo $stats['total_prestamos']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6>Préstamos Activos</h6>
                <h2><?php echo $stats['prestamos_activos']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Préstamos Devueltos</h6>
                <h2><?php echo $stats['prestamos_devueltos']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6>Préstamos Vencidos</h6>
                <h2><?php echo $stats['prestamos_vencidos']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Equipos más prestados -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Equipos Más Prestados</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Equipo</th>
                                <th>Código</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipos_mas_prestados as $index => $equipo): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($equipo['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($equipo['codigo_inventario']); ?></td>
                                    <td><strong><?php echo $equipo['total_prestamos']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de reporte -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-list-check"></i> Detalle de Préstamos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="tablaReporte">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Equipo</th>
                        <th>Categoría</th>
                        <th>Usuario</th>
                        <th>Fecha Esperada</th>
                        <th>Fecha Devolución</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamos as $prestamo): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($prestamo['fecha_prestamo'])); ?></td>
                            <td><?php echo htmlspecialchars($prestamo['equipo_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($prestamo['categoria_nombre'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($prestamo['usuario_nombre']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])); ?></td>
                            <td><?php echo $prestamo['fecha_devolucion_real'] ? date('d/m/Y', strtotime($prestamo['fecha_devolucion_real'])) : '-'; ?></td>
                            <td>
                                <?php
                                $badge_class = '';
                                switch ($prestamo['estado']) {
                                    case 'activo':
                                        $badge_class = 'badge-prestado';
                                        break;
                                    case 'devuelto':
                                        $badge_class = 'badge-disponible';
                                        break;
                                    case 'vencido':
                                        $badge_class = 'badge-vencido';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($prestamo['estado']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($prestamos)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                No hay préstamos en el período seleccionado
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>