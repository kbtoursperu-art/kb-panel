CREATE DATABASE IF NOT EXISTS bd_kb;
USE bd_kb;

-- =======================================
-- 📌 TABLA DE CLIENTES
-- =======================================
CREATE TABLE Datos_clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro'),
    nro_pasaporte VARCHAR(50) UNIQUE NOT NULL,
    tipo_cliente ENUM('KB', 'Endosador') NOT NULL,
    nacionalidad VARCHAR(100),
    Comida VARCHAR(50),
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Clientes_KB (
    id_cliente INT PRIMARY KEY,
    edad INT,
    foto_pasaporte VARCHAR(255),
    nro_whatsapp VARCHAR(20),
    es_pagador BOOLEAN DEFAULT FALSE,
    grupo VARCHAR(20) NOT NULL,
    hotel VARCHAR(100),
    FOREIGN KEY (id_cliente) REFERENCES Datos_clientes(id_cliente) ON DELETE CASCADE
);

CREATE TABLE Clientes_Endosadores (
    id_cliente INT PRIMARY KEY,
    empresa_endosadora VARCHAR(100),
    contacto VARCHAR(100),
    telefono_contacto VARCHAR(20),
    email_contacto VARCHAR(100),
    FOREIGN KEY (id_cliente) REFERENCES Datos_clientes(id_cliente) ON DELETE CASCADE
);

-- =======================================
-- 📌 TABLA DE OPERACIONES
-- =======================================
CREATE TABLE Operaciones (
    id_operaciones INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    fecha_reserva DATE,
   nombre_servicio ENUM(
       -- 🏔️ SALKANTAY TREKS
'SALKANTAY A MACHU PICCHU 5 DÍAS',
'SALKANTAY A MACHU PICCHU 4 DÍAS',
'SALKANTAY A MACHU PICCHU 3 DÍAS',
'SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS',
'SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)',
'SALKANTAY TREK 5D/4N WITH LUXURY DOMES (PRIVADO)',
'SALKANTAY TREK 4D / 3N WITH LUXURY DOMES (PRIVADO)',
'SALKANTAY & HUMANTAY LAKE 2D WITH LUXURY DOMES (PRIVADO)',

-- 🏞️ CAMINO INCA
'CAMINO INCA 4 DÍAS',
'CAMINO INCA 4 DÍAS (PRIVADO)',
'CAMINO INCA 2 DÍAS',
'CAMINO INCA FULL DAY',

-- 🏯 MACHU PICCHU TOURS
'MACHU PICCHU DE UN DÍA',
'MACHU PICCHU EN TREN 2 DÍAS',
'VALLE SAGRADO A MACHU PICCHU 2 DÍAS',

-- TOURS CHOQUEQUIRAO
'CHOQUEQUIRAO 4 DÍAS',
'CHOQUEQUIRAO 4 DÍAS (PRIVADO)',
'CHOQUEQUIRAO 5 DÍAS (PRIVADO)',

-- TOURS ALTERNATIVOS 
'Q’ESHUACHAKA Y 4 LAGUNAS DE UN DÍA',
'HUCHUY QOSQO 3 DÍAS (PRIVADO)',
'AUSANGATE Y MONTAÑA DE COLORES 4 DÍAS',
'LARES A MACHU PICCHU 4 DÍAS (PRIVADO)',
'INCA JUNGLE TRAIL 4 DAYS',

-- 🌄 TOURS DE UN DÍA O CORTOS
'LAGUNA HUMANTAY DE UN DIA',
'MONTAÑA DE COLORES DE UN DIA',
'PALCOYO DE UN DIA',
'VALLE SAGRADO VIP DE UN DIA',
'MARAS MORAY DE UN DIA',
'WAQRAPUKARA DE UN DIA',
'7 LAGUNAS DE AUSANGATE DE UN DIA',
'CITY TOUR CUSCO MEDIO DIA',
'VALLE TRADICIONAL',
'VALLE SUR MEDIO DIA',
'CUATRIMOTOS MARAS Y MORAY',
'CUATRIMOTOS MONTAÑA DE COLORES',
'TRANSP. BY CAR',
'MIRABUS',

-- 🏝️ TOURS FUERA DE CUSCO
'ICA – PARACAS DE UN DIA',
'PUNO DE UN DÍA',
'MANU 4 DÍAS Y 3 NOCHES' 
    ),
    servicio_adicional VARCHAR(255),
    modalidad_retorno ENUM('Tren', 'Carro', 'Sin retorno'),
    incluye_ingreso ENUM('Con ingreso', 'Sin ingreso'),
    fecha_salida DATE,
    fecha_retorno DATE,
    empresa VARCHAR(50),
    observaciones VARCHAR(255),
    Encargado VARCHAR(50),
    FOREIGN KEY (id_cliente) REFERENCES Datos_clientes(id_cliente) ON DELETE CASCADE
);

-- =======================================
-- 📌 TABLA DE CONTABILIDAD (CORREGIDA)
-- =======================================
CREATE TABLE Contabilidad (
    id_contabilidad INT AUTO_INCREMENT PRIMARY KEY, 
    id_operaciones INT NOT NULL,
    metodo_pago ENUM('Efectivo', 'We travel', 'Izipay', 'PAYPAL', 'Bcp','CULQI'),
    tipo_moneda ENUM('Dólares', 'Soles'),
    precio_servicio_adicional DECIMAL (10,2),
    comision DECIMAL(10,2),
    precio_servicio DECIMAL(10,2),
    pagado_a_cuenta DECIMAL(10,2),
    saldo_pendiente DECIMAL(10,2),
    fecha_pago_saldo DATE,
    estado ENUM('pendiente', 'pagado', 'reembolsado') DEFAULT 'pendiente',
    modalidad_recibo ENUM('FACTURA','FAC_EXPORTACION','BV_INTANGIBLE','BV_IGV'),
    nro_boleta_cuenta VARCHAR(50),
    nro_boleta_total VARCHAR(50),
    Nro_Comprobante_adicional VARCHAR(50),
    detraccion DECIMAL(10,2),
    NotaCredito BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_operaciones) REFERENCES Operaciones(id_operaciones) ON DELETE CASCADE
);
-- =======================================
-- 📌 TABLA DE VENTAS
-- =======================================
CREATE TABLE Venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_operaciones INT NOT NULL,
    id_contabilidad INT NOT NULL,  
    nro_voucher VARCHAR(50) UNIQUE NOT NULL,
    FOREIGN KEY (id_operaciones) REFERENCES Operaciones(id_operaciones) ON DELETE CASCADE,
    FOREIGN KEY (id_contabilidad) REFERENCES Contabilidad(id_contabilidad) ON DELETE CASCADE
);

-- =======================================
-- 📌 TABLA DE PLANIFICACIÓN
-- =======================================
CREATE TABLE Planificacion (
    id_planificacion INT AUTO_INCREMENT PRIMARY KEY, 
    id_operaciones INT NOT NULL,
    nombre_guia VARCHAR(100),
    nombre_cocinero VARCHAR(100),
    nombre_asistente VARCHAR(100),
    FOREIGN KEY (id_operaciones) REFERENCES Operaciones(id_operaciones) ON DELETE CASCADE
);

-- =======================================
-- 📌 TABLA HISTORIAL DE VIAJES
-- =======================================
CREATE TABLE historial_viajes (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    fecha_viaje DATE NOT NULL,
    destino VARCHAR(255) NOT NULL,
    tipo_servicio VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES Datos_clientes(id_cliente) ON DELETE CASCADE
);

-- =======================================
-- 📌 TABLA PERFIL CLIENTES
-- =======================================
CREATE TABLE perfil_clientes (
    id_perfil INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    direccion VARCHAR(255),
    telefono VARCHAR(20),
    email VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE,
    ocupacion VARCHAR(100),
    intereses TEXT,
    FOREIGN KEY (id_cliente) REFERENCES Datos_clientes(id_cliente) ON DELETE CASCADE
);

-- =======================================
-- 📌 TABLA CORREOS ENVIADOS
-- =======================================
CREATE TABLE correos_enviados (
    id_correo INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    email_destino VARCHAR(100) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES Datos_clientes(id_cliente) ON DELETE CASCADE
);

-- ==========================================================
-- 📦 SISTEMA DE ALMACÉN TURÍSTICO - ESTRUCTURA FINAL
-- ==========================================================

-- =======================================
-- 🔹 TABLA DE PRODUCTOS
-- =======================================
CREATE TABLE almacen_items (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('Consumible','Retornable','Garantia') NOT NULL
);

-- =======================================
-- 🔹 TABLA DE STOCK
-- =======================================
CREATE TABLE almacen_stock (
    id_stock INT AUTO_INCREMENT PRIMARY KEY,
    id_item INT NOT NULL,
    talla VARCHAR(10) NULL,
    cantidad_total INT NOT NULL DEFAULT 0,
    cantidad_disponible INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_item) REFERENCES almacen_items(id_item) ON DELETE CASCADE
);

-- =======================================
-- 🔹 TABLA DE SALIDAS (a guías)
-- =======================================
CREATE TABLE almacen_salidas (
    id_salida INT AUTO_INCREMENT PRIMARY KEY,
    id_stock INT NOT NULL,
    nombre_guia VARCHAR(100) NOT NULL,
    cantidad INT NOT NULL,
    fecha_salida DATE NOT NULL,
    garantia_original DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observacion TEXT,
    estado ENUM('Pendiente','Parcial','Devuelto') NOT NULL DEFAULT 'Pendiente',
    FOREIGN KEY (id_stock) REFERENCES almacen_stock(id_stock) ON DELETE CASCADE
);

-- =======================================
-- 🔹 TABLA DE DEVOLUCIONES
-- =======================================
CREATE TABLE almacen_devoluciones (
    id_devolucion INT AUTO_INCREMENT PRIMARY KEY,
    id_salida INT NOT NULL,
    cantidad_devuelta INT NOT NULL,
    monto_devuelto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha_devolucion DATE NOT NULL DEFAULT CURRENT_DATE,
    observacion TEXT,
    FOREIGN KEY (id_salida) REFERENCES almacen_salidas(id_salida) ON DELETE CASCADE
);

-- =======================================
-- 🔹 TABLA DE MOVIMIENTOS (KARDEX)
-- =======================================
CREATE TABLE almacen_movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_stock INT NULL,
    tipo ENUM('Ingreso','Salida','Devolucion','Regalo','Garantia') NOT NULL,
    cantidad INT NOT NULL,
    monto DECIMAL(10,2) DEFAULT 0.00,
    referencia VARCHAR(100),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_stock) REFERENCES almacen_stock(id_stock) ON DELETE SET NULL
);

-- =======================================
-- 🔹 VISTA DE SALIDAS PENDIENTES
-- =======================================
CREATE VIEW vista_pendientes AS
SELECT
    s.id_salida,
    i.nombre AS producto,
    s.nombre_guia,
    s.cantidad,
    IFNULL(SUM(d.cantidad_devuelta),0) AS devuelto,
    (s.cantidad - IFNULL(SUM(d.cantidad_devuelta),0)) AS pendiente
FROM almacen_salidas s
JOIN almacen_stock st ON s.id_stock = st.id_stock
JOIN almacen_items i ON st.id_item = i.id_item
LEFT JOIN almacen_devoluciones d ON s.id_salida = d.id_salida
GROUP BY s.id_salida
HAVING pendiente > 0;

-- =======================================
-- 🔹 DATA INICIAL
-- =======================================

INSERT INTO almacen_items (nombre, tipo) VALUES
('Bastón', 'Retornable'),
('Sleeping Bag', 'Retornable'),
('Dafor Bag', 'Garantia'),
('Polo KB', 'Consumible');

INSERT INTO almacen_stock (id_item, talla, cantidad_total, cantidad_disponible) VALUES
(1, NULL, 10, 10),
(2, NULL, 5, 5),
(3, NULL, 3, 3),
(4, 'S', 20, 20),
(4, 'M', 20, 20),
(4, 'L', 20, 20),
(4, 'XL', 15, 15);

CREATE OR REPLACE VIEW vista_garantias_guias AS
SELECT
    s.nombre_guia AS guia,

    SUM(
        CASE
            WHEN i.tipo = 'Garantia'
            THEN s.garantia_original
            ELSE 0
        END
    ) AS total_entregado,

    SUM(
        CASE
            WHEN i.tipo = 'Garantia'
            THEN IFNULL(d.total_devuelto,0)
            ELSE 0
        END
    ) AS total_devuelto,

    SUM(
        CASE
            WHEN i.tipo = 'Garantia'
            THEN (s.garantia_original - IFNULL(d.total_devuelto,0))
            ELSE 0
        END
    ) AS pendiente

FROM almacen_salidas s
JOIN almacen_stock st ON s.id_stock = st.id_stock
JOIN almacen_items i ON st.id_item = i.id_item

LEFT JOIN (
    SELECT
        id_salida,
        SUM(monto_devuelto) AS total_devuelto
    FROM almacen_devoluciones
    GROUP BY id_salida
) d ON d.id_salida = s.id_salida

GROUP BY s.nombre_guia
HAVING total_entregado > 0;

DELIMITER $$

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

    SET NEW.monto_devuelto =
        (g_total / cant_total) * NEW.cantidad_devuelta;
END$$

DELIMITER ;

UPDATE almacen_devoluciones d
JOIN almacen_salidas s ON s.id_salida = d.id_salida
SET d.monto_devuelto = (s.garantia_original / s.cantidad) * d.cantidad_devuelta
WHERE d.monto_devuelto = 0;

DELIMITER $$

CREATE TRIGGER trg_devolver_stock
AFTER INSERT ON almacen_devoluciones
FOR EACH ROW
BEGIN
    DECLARE v_id_stock INT;

    -- Obtener el id_stock desde la salida
    SELECT id_stock
    INTO v_id_stock
    FROM almacen_salidas
    WHERE id_salida = NEW.id_salida;

    -- DEVOLVER AL STOCK DISPONIBLE
    UPDATE almacen_stock
    SET cantidad_disponible = cantidad_disponible + NEW.cantidad_devuelta
    WHERE id_stock = v_id_stock;
END$$

DELIMITER ;

-- =======================================
-- 📌 TABLA DE USUARIOS
-- =======================================
CREATE TABLE Usuarios (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Usuario VARCHAR(255) NOT NULL UNIQUE,
    Contraseña VARCHAR(255) NOT NULL,
    Area ENUM('Operaciones', 'Ventas', 'Almacén', 'Contabilidad', 'Planificación', 'Clientes') NOT NULL,
    EsAdmin TINYINT(1) NOT NULL DEFAULT 0
);

-- =======================================
-- 📌 VISTAS
-- =======================================
-- =======================================
-- 📌 VISTA PARA CLIENTES KB
-- =======================================
CREATE OR REPLACE VIEW Vista_Operaciones_KB AS
SELECT 
o.id_operaciones,
    o.id_cliente,
    o.fecha_reserva,
    o.nombre_servicio,
    o.servicio_adicional,
    o.modalidad_retorno,
    o.incluye_ingreso,
    o.fecha_salida,
    o.fecha_retorno,
    o.empresa,
    o.observaciones,
    o.Encargado,
    c.metodo_pago, 
    c.tipo_moneda, 
    c.precio_servicio, 
    c.pagado_a_cuenta, 
    c.saldo_pendiente, 
    c.fecha_pago_saldo, 
    c.comision,
    d.nombre AS nombre_cliente, 
    d.nro_pasaporte, 
    d.tipo_cliente,
    kb.grupo, 
    kb.hotel
FROM Operaciones o
LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
LEFT JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
LEFT JOIN Clientes_KB kb ON d.id_cliente = kb.id_cliente
WHERE d.tipo_cliente = 'KB';

-- =======================================
-- 📌 VISTA PARA CLIENTES ENDOSADORES
-- =======================================
CREATE OR REPLACE VIEW Vista_Operaciones_Endosador AS
SELECT 
    o.id_operaciones,
    o.fecha_reserva,
    o.nombre_servicio,
    o.fecha_salida,
    o.fecha_retorno,
    o.servicio_adicional,
    o.modalidad_retorno,
    d.id_cliente,
    d.nombre AS nombre_cliente,
    d.apellido,
    d.nro_pasaporte,
    d.tipo_cliente,
    e.empresa_endosadora,
    e.contacto,
    e.telefono_contacto,
    e.email_contacto,
    c.precio_servicio,
    c.pagado_a_cuenta,
    c.saldo_pendiente,
    c.fecha_pago_saldo,
    c.metodo_pago,
    c.tipo_moneda,
    c.comision
FROM Operaciones o
LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
LEFT JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
LEFT JOIN Clientes_Endosadores e ON o.id_cliente = e.id_cliente
WHERE d.tipo_cliente = 'Endosador';


CREATE OR REPLACE VIEW Vista_Ventas AS
SELECT 
    v.*, 
    o.nombre_servicio, o.fecha_salida, o.fecha_retorno, o.fecha_reserva, 
    kb.grupo,
    c.metodo_pago, c.precio_servicio, c.pagado_a_cuenta, 
    c.saldo_pendiente, c.fecha_pago_saldo
FROM Venta v
LEFT JOIN Operaciones o ON v.id_operaciones = o.id_operaciones
LEFT JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
LEFT JOIN Clientes_KB kb ON d.id_cliente = kb.id_cliente
LEFT JOIN Contabilidad c ON v.id_contabilidad = c.id_contabilidad;

CREATE OR REPLACE VIEW Vista_Planificacion AS
SELECT p.*, o.nombre_servicio, o.fecha_salida, o.fecha_retorno
FROM Planificacion p
LEFT JOIN Operaciones o ON p.id_operaciones = o.id_operaciones;

CREATE OR REPLACE VIEW Vista_DatosClientes AS
SELECT * FROM Datos_clientes;

ALTER TABLE Datos_clientes AUTO_INCREMENT = 1;
