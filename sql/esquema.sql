-- ==========================================================
-- Garza Night · Esquema de base de datos (MySQL / MariaDB)
-- Importar desde phpMyAdmin (pestaña "Importar") o con:
--   mysql -u root < esquema.sql
-- ==========================================================
DROP DATABASE IF EXISTS garza_night;
CREATE DATABASE garza_night CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE garza_night;

-- ---------- USUARIOS ----------
CREATE TABLE usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(80)  NOT NULL,
  correo        VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  carrera       VARCHAR(80)  NULL,
  descripcion   VARCHAR(200) NULL,
  rol           ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- TEMÁTICAS (solo el admin las administra) ----------
CREATE TABLE tematicas (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------- EVENTOS ----------
CREATE TABLE eventos (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organizador_id       INT UNSIGNED NOT NULL,
  tematica_id          INT UNSIGNED NOT NULL,
  titulo               VARCHAR(100) NOT NULL,
  slogan               VARCHAR(120) NULL,
  descripcion          TEXT NOT NULL,
  imagen_url           VARCHAR(500) NOT NULL,
  lugar                VARCHAR(100) NOT NULL,
  direccion            VARCHAR(200) NOT NULL,
  fecha                DATE NOT NULL,
  hora_inicio          TIME NOT NULL,
  hora_fin             TIME NULL,
  costo_cover          DECIMAL(8,2) NOT NULL DEFAULT 0,   -- 0 = gratis
  invitados_especiales VARCHAR(200) NULL,
  politica_bebidas     ENUM('llevar_propias','permitido_llevar','no_permitido','incluidas') NOT NULL,
  actividades          VARCHAR(300) NULL,
  codigo_vestimenta    VARCHAR(80)  NULL,
  edad_minima          TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0, 18 o 21
  capacidad            INT UNSIGNED NULL,                   -- NULL = sin límite
  estado               ENUM('activo','cancelado') NOT NULL DEFAULT 'activo',
  creado_en            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organizador_id) REFERENCES usuarios(id)  ON DELETE CASCADE,
  FOREIGN KEY (tematica_id)    REFERENCES tematicas(id) ON DELETE RESTRICT,
  INDEX idx_fecha (fecha)
) ENGINE=InnoDB;

-- ---------- ASISTENCIAS (usuario <-> evento) ----------
CREATE TABLE asistencias (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  evento_id  INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_asistencia (evento_id, usuario_id),  -- evita registros duplicados
  FOREIGN KEY (evento_id)  REFERENCES eventos(id)  ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- FAVORITOS ----------
CREATE TABLE favoritos (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  evento_id  INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  UNIQUE KEY uq_favorito (evento_id, usuario_id),
  FOREIGN KEY (evento_id)  REFERENCES eventos(id)  ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- COMENTARIOS ----------
CREATE TABLE comentarios (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  evento_id  INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  texto      VARCHAR(200) NOT NULL,
  creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (evento_id)  REFERENCES eventos(id)  ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================================
-- DATOS INICIALES
-- ==========================================================
INSERT INTO tematicas (nombre) VALUES
 ('Sin temática'),('Temática libre'),('Halloween'),('Navidad'),('Disfraces');

-- Cuenta de administrador de prueba:
--   correo: admin@uaeh.edu.mx   contraseña: password
-- (hash bcrypt de "password"; cámbiala antes de la entrega final)
INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES
 ('Administrador','admin@uaeh.edu.mx',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin');
