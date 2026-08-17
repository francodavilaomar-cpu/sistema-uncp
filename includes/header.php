<?php
require_once __DIR__ . '/../config.php';
requireLogin();
$usuario_actual = getCurrentUser();

// Obtener notificaciones (préstamos vencidos)
$notificaciones = 0;
if (esEncargado()) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'activo' AND fecha_devolucion_esperada < NOW()");
    $notificaciones = $stmt->fetch()['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/estilos.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white">
            <div class="p-3">
                <h4 class="text-center mb-4">
                    <i class="bi bi-laptop"></i> PrestaEquipos
                </h4>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/dashboard.php">
                            <i class="bi bi-house-door"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/equipos/listar.php">
                            <i class="bi bi-pc-display"></i> Equipos
                        </a>
                    </li>
                    <?php if (esEncargado()): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/prestamos/registrar.php">
                            <i class="bi bi-arrow-left-right"></i> Préstamos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/prestamos/vencidos.php">
                            <i class="bi bi-exclamation-triangle"></i> Vencidos
                            <?php if ($notificaciones > 0): ?>
                                <span class="badge bg-danger"><?php echo $notificaciones; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/reservas/listar.php">
                            <i class="bi bi-calendar-check"></i> Reservas
                        </a>
                    </li>
                    <?php if (esAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/usuarios/listar.php">
                            <i class="bi bi-people"></i> Usuarios
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/reportes/index.php">
                            <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Contenido principal -->
        <main class="flex-grow-1">
            <!-- Header superior -->
            <header class="bg-white shadow-sm p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo isset($page_title) ? $page_title : 'Inicio'; ?></h5>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($usuario_actual['nombre']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text text-muted">
                                Rol: <?php echo ucfirst($usuario_actual['rol']); ?>
                            </span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                            </a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="container-fluid px-4">
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['mensaje_tipo'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php 
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['mensaje_tipo']);
                    ?>
                <?php endif; ?>