<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin']);

$page_title = 'Gestión de Usuarios';

// Filtros
$busqueda = $_GET['busqueda'] ?? '';
$rol = $_GET['rol'] ?? '';

// Construir consulta
$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if ($busqueda) {
    $sql .= " AND (nombre LIKE ? OR email LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if ($rol) {
    $sql .= " AND rol = ?";
    $params[] = $rol;
}

$sql .= " ORDER BY nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-people"></i> Usuarios</h4>
    <a href="crear.php" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Nuevo Usuario
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-5">
                <label for="busqueda" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="busqueda" name="busqueda" 
                       placeholder="Nombre o email..." value="<?php echo htmlspecialchars($busqueda); ?>">
            </div>
            <div class="col-md-5">
                <label for="rol" class="form-label">Rol</label>
                <select class="form-select" id="rol" name="rol">
                    <option value="">Todos</option>
                    <option value="admin" <?php echo $rol == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="encargado" <?php echo $rol == 'encargado' ? 'selected' : ''; ?>>Encargado</option>
                    <option value="usuario" <?php echo $rol == 'usuario' ? 'selected' : ''; ?>>Usuario</option>
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

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo $usuario['id']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td>
                                <?php
                                $badge_class = '';
                                switch ($usuario['rol']) {
                                    case 'admin':
                                        $badge_class = 'badge bg-danger';
                                        break;
                                    case 'encargado':
                                        $badge_class = 'badge bg-warning text-dark';
                                        break;
                                    case 'usuario':
                                        $badge_class = 'badge bg-info';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $badge_class; ?>">
                                    <?php echo ucfirst($usuario['rol']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($usuario['estado'] == 1): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'] ?? 'now')); ?></td>
                            <td>
                                <a href="editar.php?id=<?php echo $usuario['id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                    <a href="cambiar_estado.php?id=<?php echo $usuario['id']; ?>" 
                                       class="btn btn-sm <?php echo $usuario['estado'] ? 'btn-danger' : 'btn-success'; ?>" 
                                       title="<?php echo $usuario['estado'] ? 'Desactivar' : 'Activar'; ?>">
                                        <i class="bi <?php echo $usuario['estado'] ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                <i class="bi bi-people" style="font-size: 2rem;"></i>
                                <p class="mt-2">No se encontraron usuarios</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <button class="btn btn-outline-success" onclick="exportTableToCSV('tablaUsuarios', 'usuarios.csv')">
            <i class="bi bi-download"></i> Exportar CSV
        </button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>