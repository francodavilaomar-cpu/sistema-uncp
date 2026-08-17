<?php
// Configuración general del sistema
define('SITE_NAME', 'Sistema de Préstamo de Equipos');
define('SITE_URL', 'http://tu-dominio.infinityfreeapp.com'); // Cambia por tu dominio

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City'); // Ajusta según tu ubicación

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Cargar conexión a base de datos
require_once 'includes/db.php';

// Funciones auxiliares
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['usuario_rol'], $roles)) {
        header('Location: ' . SITE_URL . '/dashboard.php');
        exit();
    }
}

function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

function esEncargado() {
    return isset($_SESSION['usuario_rol']) && ($_SESSION['usuario_rol'] === 'encargado' || $_SESSION['usuario_rol'] === 'admin');
}
?>