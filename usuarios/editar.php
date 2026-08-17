<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin']);

$page_title = 'Editar Usuario';
$errores = [];

$id = $_GET['id'] ?? 0;

// Obtener usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    $_SESSION['mensaje'] = 'Usuario no encontrado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: listar.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? 'usuario';
    $nuevo_password = $_POST['nuevo_password'] ?? '';
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingrese un email válido.';
    }
    
    // Verificar email único (excepto el actual)
    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $errores[] = 'El email ya está registrado.';
        }
    }
    
    if (empty($errores)) {
        try {
            if (!empty($nuevo_password)) {
                if (strlen($nuevo_password) < 8) {
                    $errores[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
                } else {
                    $password_hash = password_hash($nuevo_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET nombre = ?, email = ?, rol = ?, password_hash = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$nombre, $email, $rol, $password_hash, $id]);
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET nombre = ?, email = ?, rol = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $email, $rol, $id]);
            }
            
            if (empty($errores)) {
                $_SESSION['mensaje'] = 'Usuario actualizado exitosamente.';
                $_SESSION['mensaje_tipo'] = 'success';
                header('Location: listar.php');
                exit();
            }
        } catch (PDOException $e) {
            $errores[] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Editar Usuario</h5>
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
                        <label for="nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required
                               value="<?php echo htmlspecialchars($usuario['nombre']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo htmlspecialchars($usuario['email']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="nuevo_password" class="form-label">Nueva Contraseña (opcional)</label>
                        <input type="password" class="form-control" id="nuevo_password" name="nuevo_password">
                        <small class="text-muted">Dejar en blanco para mantener la contraseña actual</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol *</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="usuario" <?php echo $usuario['rol'] == 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                            <option value="encargado" <?php echo $usuario['rol'] == 'encargado' ? 'selected' : ''; ?>>Encargado</option>
                            <option value="admin" <?php echo $usuario['rol'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Actualizar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>