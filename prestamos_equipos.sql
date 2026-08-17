-- Crear base de datos
CREATE DATABASE IF NOT EXISTS prestamos_equipos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE prestamos_equipos;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'encargado', 'usuario') NOT NULL DEFAULT 'usuario',
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB;

-- Tabla de equipos
CREATE TABLE IF NOT EXISTS equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_inventario VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria_id INT,
    estado ENUM('disponible', 'prestado', 'mantenimiento', 'dado_de_baja') NOT NULL DEFAULT 'disponible',
    ubicacion VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    INDEX idx_codigo (codigo_inventario),
    INDEX idx_estado (estado),
    INDEX idx_categoria (categoria_id)
) ENGINE=InnoDB;

-- Tabla de préstamos
CREATE TABLE IF NOT EXISTS prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    equipo_id INT NOT NULL,
    fecha_prestamo DATETIME NOT NULL,
    fecha_devolucion_esperada DATETIME NOT NULL,
    fecha_devolucion_real DATETIME,
    estado ENUM('activo', 'devuelto', 'vencido') NOT NULL DEFAULT 'activo',
    observaciones TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_equipo (equipo_id),
    INDEX idx_estado (estado),
    INDEX idx_fechas (fecha_prestamo, fecha_devolucion_esperada)
) ENGINE=InnoDB;

-- Tabla de reservas
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    equipo_id INT NOT NULL,
    fecha_reserva DATETIME NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada') NOT NULL DEFAULT 'pendiente',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_equipo (equipo_id),
    INDEX idx_estado (estado),
    INDEX idx_fechas (fecha_inicio, fecha_fin)
) ENGINE=InnoDB;

-- Tabla de historial de movimientos
CREATE TABLE IF NOT EXISTS historial_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT,
    usuario_id INT,
    tipo ENUM('alta', 'edicion', 'prestamo', 'devolucion', 'mantenimiento', 'baja') NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalle TEXT,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_equipo (equipo_id),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB;

-- Insertar datos de ejemplo

-- Usuario administrador por defecto (contraseña: admin123)
INSERT INTO usuarios (nombre, email, password_hash, rol, estado) VALUES 
('Administrador', 'admin@universidad.edu', '$2y$10$YourHashHere', 'admin', 1);

-- Categorías de ejemplo
INSERT INTO categorias (nombre, descripcion) VALUES 
('Laptops', 'Computadoras portátiles para préstamo'),
('Proyectores', 'Proyectores multimedia'),
('Tablets', 'Tabletas electrónicas'),
('Cámaras', 'Cámaras fotográficas y de video'),
('Accesorios', 'Accesorios de cómputo');

-- Equipos de ejemplo
INSERT INTO equipos (codigo_inventario, nombre, categoria_id, estado, ubicacion) VALUES 
('INV-001', 'Laptop HP Pavilion', 1, 'disponible', 'Laboratorio 1'),
('INV-002', 'Laptop Dell Latitude', 1, 'disponible', 'Laboratorio 2'),
('INV-003', 'Proyector Epson', 2, 'disponible', 'Aula Magna'),
('INV-004', 'Tablet Samsung', 3, 'disponible', 'Biblioteca'),
('INV-005', 'Cámara Canon', 4, 'mantenimiento', 'Estudio de grabación');