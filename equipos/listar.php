<?php
require_once '../config.php';
require_once '../includes/auth.php';

$page_title = 'Listado de Equipos';

// Filtros
$busqueda = $_GET['busqueda'] ?? '';
$categoria_id = $_GET['categoria_id'] ?? '';
$estado = $_GET['estado'] ?? '';

// Construir consulta
$sql = "SELECT e.*, c.nombre as categoria_nombre FROM equipos e 
        LEFT JOIN categorias c ON e.categoria_id = c.id WHERE 1=1";
$params = [];

if ($busqueda) {
    $sql .= " AND (e.nombre LIKE ? OR e.codigo_inventario LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if ($categoria_id) {
    $sql .= " AND e.categoria_id = ?";
    $params[] = $categoria_id;
}

if ($estado) {
    $sql .= " AND e.estado = ?";
    $params[] = $estado;
}

$sql .= " ORDER BY e.codigo_inventario DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipos = $stmt->fetchAll();

// Obtener categorías para el filtro
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-pc-display"></i> Equipos</h4>
    <?php if (esEncargado()): ?>
        <a href="crear.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Equipo
        </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="busqueda" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="busqueda" name="busqueda" 
                       placeholder="Nombre o código..." value="<?php echo htmlspecialchars($busqueda); ?>">
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
                    <option value="disponible" <?php echo $estado == 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                    <option value="prestado" <?php echo $estado == 'prestado' ? 'selected' : ''; ?>>Prestado</option>
                    <option value="mantenimiento" <?php echo $estado == 'mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                    <option value="dado_de_baja" <?php echo $estado == 'dado_de_baja' ? 'selected' : ''; ?>>Dado de baja</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de equipos -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="tablaEquipos">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos as $equipo): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($equipo['codigo_inventario']); ?></strong></td>
                            <td><?php echo htmlspecialchars($equipo['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($equipo['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                            <td><?php echo htmlspecialchars($equipo['ubicacion'] ?? '-'); ?></td>
                            <td>
                                <?php
                                $badge_class = '';
                                switch ($equipo['estado']) {
                                    case 'disponible':
                                        $badge_class = 'badge-disponible';
                                        break;
                                    case 'prestado':
                                        $badge_class = 'badge-prestado';
                                        break;
                                    case 'mantenimiento':
                                        $badge_class = 'badge-mantenimiento';
                                        break;
                                    case 'dado_de_baja':
                                        $badge_class = 'badge-vencido';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($equipo['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($equipo['estado'] == 'disponible' && esEncargado()): ?>
                                    <a href="../prestamos/registrar.php?equipo_id=<?php echo $equipo['id']; ?>" 
                                       class="btn btn-sm btn-success" title="Prestar">
                                        <i class="bi bi-arrow-left-right"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($equipo['estado'] == 'disponible' && !esEncargado()): ?>
                                    <a href="../reservas/crear.php?equipo_id=<?php echo $equipo['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Reservar">
                                        <i class="bi bi-calendar-plus"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (esEncargado()): ?>
                                    <a href="editar.php?id=<?php echo $equipo['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (esAdmin()): ?>
                                    <form method="POST" action="eliminar.php" class="d-inline form-eliminar">
                                        <input type="hidden" name="id" value="<?php echo $equipo['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($equipos)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <p class="mt-2">No se encontraron equipos</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <button class="btn btn-outline-success" onclick="exportTableToCSV('tablaEquipos', 'equipos.csv')">
            <i class="bi bi-download"></i> Exportar CSV
        </button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>