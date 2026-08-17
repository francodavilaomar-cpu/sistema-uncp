<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole(['admin']);

$page_title = 'Nuevo Usuario';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $rol = $_POST['rol'] ?? 'usuario';
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingrese un email válido.';
    }
    if (strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($password !== $password_confirm) {
        $errores[] = 'Las contraseñas no coinciden.';
    }
    
    // Verificar email único
    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errores[] = 'El email ya está registrado.';
        }
    }
    
    if (empty($errores)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, password_hash, rol, estado) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$nombre, $email, $password_hash, $rol]);
            
            $_SESSION['mensaje'] = 'Usuario creado exitosamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: listar.php');
            exit();
        } catch (PDOException $e) {
            $errores[] = 'Error al crear el usuario: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-person-plus"></i> Crear Nuevo Usuario</h5>
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
                               value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               placeholder="usuario@universidad.edu"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required minlength="8">
                            <small class="text-muted">Mínimo 8 caracteres</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" id="password_confirm" 
                                   name="password_confirm" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol *</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="usuario">Usuario</option>
                            <option value="encargado">Encargado</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>