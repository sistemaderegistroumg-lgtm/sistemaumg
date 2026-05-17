-- ============================================================
--  Script SQL - Sistema de Asistencia UMG
--  Para ejecutar: abre phpMyAdmin → pestaña SQL → pega y ejecuta
-- ============================================================

CREATE DATABASE IF NOT EXISTS umg_asistencia
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE umg_asistencia;

CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    apellidos  VARCHAR(100) NOT NULL,
    correo     VARCHAR(150) NOT NULL,
    telefono   VARCHAR(20),
    carrera    VARCHAR(100),
    semestre   TINYINT UNSIGNED,
    seccion    VARCHAR(10),
    foto       VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles_usuarios (
    id_roles_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario   VARCHAR(80) NOT NULL,
    contrasena       VARCHAR(255) NOT NULL,
    correo           VARCHAR(150),
    telefono         VARCHAR(20),
    rol              ENUM('Administrador','Catedrático') NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_nombre_usuario (nombre_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cursos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(120) NOT NULL,
    salon          VARCHAR(30),
    horario_inicio TIME,
    horario_fin    TIME,
    catedratico_id INT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (catedratico_id) REFERENCES roles_usuarios(id_roles_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS curso_estudiante (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    curso_id      INT NOT NULL,
    estudiante_id INT NOT NULL,
    FOREIGN KEY (curso_id)      REFERENCES cursos(id)   ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uk_curso_est (curso_id, estudiante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asistencias (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    curso_id       INT NOT NULL,
    fecha          DATE NOT NULL,
    catedratico_id INT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id)       REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (catedratico_id) REFERENCES roles_usuarios(id_roles_usuario) ON DELETE SET NULL,
    UNIQUE KEY uk_curso_fecha (curso_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asistencia_detalle (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    asistencia_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    presente      TINYINT(1) NOT NULL DEFAULT 0,
    hora_registro TIME,
    FOREIGN KEY (asistencia_id) REFERENCES asistencias(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id)    ON DELETE CASCADE,
    UNIQUE KEY uk_det (asistencia_id, estudiante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin por defecto: usuario=admin, contraseña=admin123
INSERT IGNORE INTO roles_usuarios (nombre_usuario, contrasena, correo, rol)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@umg.edu.gt', 'Administrador');
