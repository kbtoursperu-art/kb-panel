SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS bd_kb;
USE bd_kb;

-- =====================================
-- CLIENTES
-- =====================================
CREATE TABLE datos_clientes (
  id_cliente INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  dni VARCHAR(20) UNIQUE,
  email VARCHAR(100),
  telefono VARCHAR(20),
  genero ENUM('M','F','Otro'),
  nro_pasaporte VARCHAR(50),
  comida VARCHAR(50),
  nacionalidad VARCHAR(50),
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  hotel VARCHAR(150),
  fecha_nacimiento DATE,
  foto_pasaporte VARCHAR(255),

  INDEX (dni),
  INDEX (apellido)
);

-- =====================================
-- GRUPOS
-- =====================================
CREATE TABLE grupos (
  id_grupo INT AUTO_INCREMENT PRIMARY KEY,
  nombre_grupo VARCHAR(50) NOT NULL,
  hotel VARCHAR(100),
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  cantidad INT DEFAULT 0,
  estado ENUM('abierto','cerrado') DEFAULT 'abierto',

  INDEX (nombre_grupo)
);

-- =====================================
-- CLIENTES POR GRUPO
-- =====================================
CREATE TABLE clientes_grupo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  id_grupo INT NOT NULL,
  tipo_cliente ENUM('KB','ENDOSADOR') DEFAULT 'KB',
  empresa_endosadora VARCHAR(100),
  contacto VARCHAR(100),
  telefono_contacto VARCHAR(20),
  email_contacto VARCHAR(100),
  es_pagador TINYINT(1) DEFAULT 0,

  UNIQUE KEY unique_cliente_grupo (id_cliente, id_grupo),

  INDEX (id_cliente),
  INDEX (id_grupo),

  FOREIGN KEY (id_cliente) REFERENCES datos_clientes(id_cliente) ON DELETE CASCADE,
  FOREIGN KEY (id_grupo) REFERENCES grupos(id_grupo) ON DELETE CASCADE
);

-- =====================================
-- SERVICIOS
-- =====================================
CREATE TABLE servicios (
  id_servicio INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200),
  duracion_dias INT,
  activo TINYINT(1) DEFAULT 1
);


-- =====================================
-- OPERACIONES
-- =====================================
CREATE TABLE operaciones (
  id_operaciones INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  fecha_reserva DATE,
  observaciones VARCHAR(255),
  encargado VARCHAR(50),
  id_grupo INT,
  tipo_precio ENUM('por_tour','total') DEFAULT 'por_tour',
  total_operacion DECIMAL(10,2) DEFAULT 0.00,
  estado ENUM('pendiente','confirmado','cancelado') DEFAULT 'pendiente',

  INDEX (id_cliente),
  INDEX (id_grupo),

  FOREIGN KEY (id_cliente) REFERENCES datos_clientes(id_cliente) ON DELETE CASCADE,
  FOREIGN KEY (id_grupo) REFERENCES grupos(id_grupo) ON DELETE SET NULL
);

-- =====================================
-- DETALLE OPERACIONES
-- =====================================
CREATE TABLE operaciones_detalle (
  id_detalle INT AUTO_INCREMENT PRIMARY KEY,
  id_operaciones INT NOT NULL,
  precio DECIMAL(10,2),
  fecha_salida DATE,
  fecha_retorno DATE,
  modalidad_retorno ENUM('Carro','Tren','Caminata'),
  incluye_ingreso ENUM('SI','NO'),
  servicio_adicional TEXT,
  tipo_moneda ENUM('Soles','Dólares') DEFAULT 'Soles',
  id_servicio INT,

  INDEX (id_operaciones),

  FOREIGN KEY (id_operaciones) REFERENCES operaciones(id_operaciones) ON DELETE CASCADE
);
USE bd_kb;

CREATE TABLE adicionales_detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_detalle INT NOT NULL,
  nombre VARCHAR(100),
  precio DECIMAL(10,2),

  INDEX (id_detalle),

  FOREIGN KEY (id_detalle)
  REFERENCES operaciones_detalle(id_detalle)
  ON DELETE CASCADE
);
-- =====================================
-- PAGOS
-- =====================================
CREATE TABLE pagos (
  id_pago INT AUTO_INCREMENT PRIMARY KEY,
  id_operaciones INT NOT NULL,
  id_detalle INT NULL,
  tipo ENUM('tour','adicional','cuenta','saldo','reembolso') DEFAULT 'tour',
  metodo_pago VARCHAR(50),
  moneda ENUM('Soles','Dólares'),
  monto DECIMAL(10,2),
  fecha DATE,
  observacion TEXT,
  tipo_cambio DECIMAL(10,3) DEFAULT 1.000,
  monto_convertido DECIMAL(10,2),

  INDEX (id_operaciones),
  INDEX (id_detalle),

  FOREIGN KEY (id_operaciones) REFERENCES operaciones(id_operaciones) ON DELETE CASCADE,
  FOREIGN KEY (id_detalle) REFERENCES operaciones_detalle(id_detalle) ON DELETE CASCADE
);

-- =====================================
-- CONTABILIDAD
-- =====================================
CREATE TABLE contabilidad (
  id_contabilidad INT AUTO_INCREMENT PRIMARY KEY,
  id_operaciones INT,
  comision DECIMAL(10,2),
  estado ENUM('pendiente','pagado','cancelado') DEFAULT 'pendiente',
  modalidad_recibo VARCHAR(50),
  nro_boleta_cuenta VARCHAR(50),
  nro_boleta_total VARCHAR(50),
  detraccion DECIMAL(10,2),
  igv DECIMAL(10,2),
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX (id_operaciones),

  FOREIGN KEY (id_operaciones) REFERENCES operaciones(id_operaciones)
);

-- =====================================
-- PLANIFICACION (corregido)
-- =====================================
CREATE TABLE planificacion (
  id_planificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_operaciones INT NULL,
  id_grupo INT NOT NULL,
  nombre_guia VARCHAR(100),
  nombre_cocinero VARCHAR(100),
  nombre_asistente VARCHAR(100),
  grupo_operativo VARCHAR(100),

  INDEX (id_operaciones),
  INDEX (id_grupo),

  FOREIGN KEY (id_operaciones) REFERENCES operaciones(id_operaciones),
  FOREIGN KEY (id_grupo) REFERENCES grupos(id_grupo)
);

-- =====================================
-- ALMACEN
-- =====================================
CREATE TABLE almacen_items (
  id_item INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100),
  tipo ENUM('Consumible','Retornable','Garantia')
);

CREATE TABLE almacen_stock (
  id_stock INT AUTO_INCREMENT PRIMARY KEY,
  id_item INT,
  talla VARCHAR(10),
  cantidad_total INT DEFAULT 0,
  cantidad_disponible INT DEFAULT 0,

  INDEX (id_item),

  FOREIGN KEY (id_item) REFERENCES almacen_items(id_item) ON DELETE CASCADE
);

CREATE TABLE almacen_salidas (
  id_salida INT AUTO_INCREMENT PRIMARY KEY,
  id_stock INT,
  nombre_guia VARCHAR(100),
  cantidad INT,
  fecha_salida DATE,
  garantia_original DECIMAL(10,2) DEFAULT 0.00,
  observacion TEXT,
  estado ENUM('Pendiente','Parcial','Devuelto') DEFAULT 'Pendiente',

  INDEX (id_stock),

  FOREIGN KEY (id_stock) REFERENCES almacen_stock(id_stock) ON DELETE CASCADE
);

CREATE TABLE almacen_devoluciones (
  id_devolucion INT AUTO_INCREMENT PRIMARY KEY,
  id_salida INT,
  cantidad_devuelta INT,
  monto_devuelto DECIMAL(10,2),
  fecha_devolucion DATE DEFAULT CURRENT_DATE,
  observacion TEXT,

  INDEX (id_salida),

  FOREIGN KEY (id_salida) REFERENCES almacen_salidas(id_salida) ON DELETE CASCADE
);

-- =====================================
-- USUARIOS (corregido)
-- =====================================
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(255),
  contrasena VARCHAR(255),
  area ENUM('Operaciones','Ventas','Almacén','Contabilidad','Planificación','Clientes'),
  es_admin TINYINT(1) DEFAULT 0,
  rol ENUM('admin','almacen') DEFAULT 'almacen'
);

INSERT INTO usuarios (usuario, contrasena, area, es_admin, rol) VALUES
('anais', '$2y$10$GrnccL7TXBSfQc7Bri/GhOq2kkZF3fkHtQLURFP/ekgjP5qeyYBrS', 'Operaciones', 1, 'almacen'),
('Nayruth', '$2y$10$zX79WRTrSp6kLBq8M7ib2ubyV83SlcZi/zd/XnfLpui3qbO4n0yaK', 'Operaciones', 0, 'almacen');

-- =====================================
-- TRIGGERS PRO
-- =====================================
DELIMITER $$

-- VALIDAR STOCK
CREATE TRIGGER trg_validar_stock_salida
BEFORE INSERT ON almacen_salidas
FOR EACH ROW
BEGIN
    DECLARE stock_actual INT;

    SELECT cantidad_disponible
    INTO stock_actual
    FROM almacen_stock
    WHERE id_stock = NEW.id_stock;

    IF NEW.cantidad > stock_actual THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock insuficiente';
    END IF;
END$$

-- CALCULAR DEVOLUCION
CREATE TRIGGER trg_calcular_monto_devuelto
BEFORE INSERT ON almacen_devoluciones
FOR EACH ROW
BEGIN
    DECLARE g_total DECIMAL(10,2);
    DECLARE cant_total INT;

    SELECT garantia_original, cantidad
    INTO g_total, cant_total
    FROM almacen_salidas
    WHERE id_salida = NEW.id_salida;

    IF cant_total > 0 THEN
        SET NEW.monto_devuelto = (g_total / cant_total) * NEW.cantidad_devuelta;
    ELSE
        SET NEW.monto_devuelto = 0;
    END IF;
END$$

-- ACTUALIZAR ESTADO
CREATE TRIGGER trg_actualizar_estado_salida
AFTER INSERT ON almacen_devoluciones
FOR EACH ROW
BEGIN
    DECLARE total_salida INT;
    DECLARE total_devuelto INT;

    SELECT cantidad INTO total_salida FROM almacen_salidas WHERE id_salida = NEW.id_salida;

    SELECT IFNULL(SUM(cantidad_devuelta),0)
    INTO total_devuelto
    FROM almacen_devoluciones
    WHERE id_salida = NEW.id_salida;

    IF total_devuelto = 0 THEN
        UPDATE almacen_salidas SET estado = 'Pendiente' WHERE id_salida = NEW.id_salida;
    ELSEIF total_devuelto < total_salida THEN
        UPDATE almacen_salidas SET estado = 'Parcial' WHERE id_salida = NEW.id_salida;
    ELSE
        UPDATE almacen_salidas SET estado = 'Devuelto' WHERE id_salida = NEW.id_salida;
    END IF;
END$$

-- DEVOLVER STOCK
CREATE TRIGGER trg_devolver_stock
AFTER INSERT ON almacen_devoluciones
FOR EACH ROW
BEGIN
    DECLARE v_id_stock INT;

    SELECT id_stock INTO v_id_stock
    FROM almacen_salidas
    WHERE id_salida = NEW.id_salida;

    UPDATE almacen_stock
    SET cantidad_disponible = cantidad_disponible + NEW.cantidad_devuelta
    WHERE id_stock = v_id_stock;
END$$

DELIMITER ;

COMMIT;