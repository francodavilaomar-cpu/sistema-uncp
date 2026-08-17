<?php
require_once 'config.php';

// Redirigir según el estado de autenticación
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit();
?>