<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin', 'encargado']);

$page_title = 'Nuevo Equipo';
$errores = [];

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
    if (empty($categoria_id)) {
        $errores[] = 'Debe seleccionar una categoría.';
    }
    
    // Verificar código único
    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE codigo_inventario = ?");
        $stmt->execute([$codigo_inventario]);
        if ($stmt->fetch()) {
            $errores[] = 'El código de inventario ya existe.';
        }
    }
    
    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO equipos (codigo_inventario, nombre, descripcion, categoria_id, ubicacion, estado) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$codigo_inventario, $nombre, $descripcion, $categoria_id, $ubicacion, $estado]);
            
            // Registrar en historial
            $equipo_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                INSERT INTO historial_movimientos (equipo_id, usuario_id, tipo, detalle) 
                VALUES (?, ?, 'alta', ?)
            ");
            $stmt->execute([$equipo_id, $_SESSION['usuario_id'], "Equipo creado: $nombre"]);
            
            $_SESSION['mensaje'] = 'Equipo creado exitosamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: listar.php');
            exit();
        } catch (PDOException $e) {
            $errores[] = 'Error al crear el equipo: ' . $e->getMessage();
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
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Registrar Nuevo Equipo</h5>
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
                                   value="<?php echo htmlspecialchars($_POST['codigo_inventario'] ?? ''); ?>">
                            <div class="invalid-feedback">El código es obligatorio.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre del Equipo *</label>
                            <input type="text" class="form-control" id="nombre" 
                                   name="nombre" required 
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                            <div class="invalid-feedback">El nombre es obligatorio.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                  placeholder="Características del equipo..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="categoria_id" class="form-label">Categoría *</label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"
                                            <?php echo ($_POST['categoria_id'] ?? '') == $categoria['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleccione una categoría.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion"
                                   placeholder="Ej: Laboratorio 2"
                                   value="<?php echo htmlspecialchars($_POST['ubicacion'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="estado" class="form-label">Estado Inicial</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="disponible">Disponible</option>
                                <option value="mantenimiento">En mantenimiento</option>
                                <option value="dado_de_baja">Dado de baja</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Equipo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>