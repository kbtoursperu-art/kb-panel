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

CREATE TABLE IF NOT EXISTS almacen_items (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria ENUM('Ropa', 'Equipo', 'Accesorio', 'Garantía') NOT NULL,
    tiene_talla BOOLEAN DEFAULT FALSE,
    tiene_color BOOLEAN DEFAULT FALSE,
    tiene_serie BOOLEAN DEFAULT FALSE,
    descripcion TEXT
);

CREATE TABLE IF NOT EXISTS almacen_tallas (
    id_talla INT AUTO_INCREMENT PRIMARY KEY,
    id_item INT NOT NULL,
    talla VARCHAR(10),
    FOREIGN KEY (id_item) REFERENCES almacen_items(id_item) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS almacen_stock (
    id_stock INT AUTO_INCREMENT PRIMARY KEY,
    id_item INT NOT NULL,
    id_talla INT NULL,
    numero_serie VARCHAR(50) NULL,        -- 🆔 Solo para maletas
    color VARCHAR(30) NULL,               -- 🎨 Solo para maletas
    cantidad_total INT DEFAULT 0,
    cantidad_disponible INT DEFAULT 0,
    FOREIGN KEY (id_item) REFERENCES almacen_items(id_item) ON DELETE CASCADE,
    FOREIGN KEY (id_talla) REFERENCES almacen_tallas(id_talla) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS almacen_movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_stock INT NOT NULL,
    tipo_movimiento ENUM('Entrada','Salida','Alquiler','Devolucion','Regalo','Garantía') NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    monto DECIMAL(10,2) DEFAULT 0.00,
    observacion VARCHAR(255) DEFAULT NULL,
    registrado_por VARCHAR(100) DEFAULT NULL,
    fecha_movimiento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_stock) REFERENCES almacen_stock(id_stock) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS almacen_pasajeros (
    id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_stock INT NOT NULL,
    id_servicio INT NULL,   -- 🔗 se relacionará con Operaciones.id_operaciones
    cantidad INT DEFAULT 1,
    tipo_articulo ENUM('Bastón','Sleeping Bag','Dafor','Maleta','Polo') NOT NULL,
    tipo_uso ENUM('Alquiler','Garantía','Regalo','Uso') DEFAULT 'Uso',
    monto DECIMAL(10,2) DEFAULT 0.00,
    fecha_salida DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_retorno DATETIME NULL,
    estado ENUM('En uso','Devuelto','Entregado') DEFAULT 'En uso',
    observacion TEXT,
    
    -- 🔗 Relaciones (foreign keys)
    FOREIGN KEY (id_cliente) REFERENCES Datos_clientes(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_stock) REFERENCES almacen_stock(id_stock) ON DELETE CASCADE,
    FOREIGN KEY (id_servicio) REFERENCES Operaciones(id_operaciones) ON DELETE SET NULL
);


CREATE TABLE IF NOT EXISTS almacen_historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_asignacion INT,
    accion ENUM('Entrega','Devolución','Entrada','Salida','Regalo','Garantía') NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    detalles TEXT,
    FOREIGN KEY (id_asignacion) REFERENCES almacen_pasajeros(id_asignacion) ON DELETE CASCADE
);

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
