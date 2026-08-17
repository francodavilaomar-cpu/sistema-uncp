<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin', 'encargado']);

$page_title = 'Editar Equipo';
$errores = [];

$id = $_GET['id'] ?? 0;

// Obtener equipo
$stmt = $pdo->prepare("SELECT * FROM equipos WHERE id = ?");
$stmt->execute([$id]);
$equipo = $stmt->fetch();

if (!$equipo) {
    $_SESSION['mensaje'] = 'Equipo no encontrado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: listar.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_inventario = trim($_POST['codigo_inventario'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria_id = $_POST['categoria_id'] ?? '';
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $estado = $_POST['estado'] ?? 'disponible';
    
    // Validaciones
    if (empty($codigo_inventario)) {
        $errores[] = 'El código de inventario es obligatorio.';
    }
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio.';
    }
    
    // Verificar código único (excepto el actual)
    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE codigo_inventario = ? AND id != ?");
        $stmt->execute([$codigo_inventario, $id]);
        if ($stmt->fetch()) {
            $errores[] = 'El código de inventario ya existe.';
        }
    }
    
    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE equipos 
                SET codigo_inventario = ?, nombre = ?, descripcion = ?, 
                    categoria_id = ?, ubicacion = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([$codigo_inventario, $nombre, $descripcion, $categoria_id, $ubicacion, $estado, $id]);
            
            // Registrar en historial
            $stmt = $pdo->prepare("
                INSERT INTO historial_movimientos (equipo_id, usuario_id, tipo, detalle) 
                VALUES (?, ?, 'edicion', ?)
            ");
            $stmt->execute([$id, $_SESSION['usuario_id'], "Equipo editado: $nombre"]);
            
            $_SESSION['mensaje'] = 'Equipo actualizado exitosamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: listar.php');
            exit();
        } catch (PDOException $e) {
            $errores[] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

// Obtener categorías
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Editar Equipo</h5>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="codigo_inventario" class="form-label">Código de Inventario *</label>
                            <input type="text" class="form-control" id="codigo_inventario" 
                                   name="codigo_inventario" required 
                                   value="<?php echo htmlspecialchars($equipo['codigo_inventario']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre del Equipo *</label>
                            <input type="text" class="form-control" id="nombre" 
                                   name="nombre" required 
                                   value="<?php echo htmlspecialchars($equipo['nombre']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($equipo['descripcion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="categoria_id" class="form-label">Categoría *</label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"
                                            <?php echo $equipo['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion"
                                   value="<?php echo htmlspecialchars($equipo['ubicacion'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="disponible" <?php echo $equipo['estado'] == 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="prestado" <?php echo $equipo['estado'] == 'prestado' ? 'selected' : ''; ?>>Prestado</option>
                                <option value="mantenimiento" <?php echo $equipo['estado'] == 'mantenimiento' ? 'selected' : ''; ?>>En mantenimiento</option>
                                <option value="dado_de_baja" <?php echo $equipo['estado'] == 'dado_de_baja' ? 'selected' : ''; ?>>Dado de baja</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Actualizar Equipo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>