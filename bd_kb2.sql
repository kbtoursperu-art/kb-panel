-- --------------------------------------------------------
-- Base de datos: `bd_kb` (script completo optimizado)
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS bd_kb
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE bd_kb;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ================================
-- TABLAS
-- ================================

-- almacen_devoluciones
CREATE TABLE IF NOT EXISTS `almacen_devoluciones` (
  `id_devolucion` INT(11) NOT NULL AUTO_INCREMENT,
  `id_salida` INT(11) NOT NULL,
  `cantidad_devuelta` INT(11) NOT NULL,
  `monto_devuelto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fecha_devolucion` DATE NOT NULL DEFAULT CURDATE(),
  `observacion` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_devolucion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- almacen_items
CREATE TABLE IF NOT EXISTS `almacen_items` (
  `id_item` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `tipo` ENUM('Consumible','Retornable','Garantia') NOT NULL,
  PRIMARY KEY (`id_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- almacen_movimientos
CREATE TABLE IF NOT EXISTS `almacen_movimientos` (
  `id_movimiento` INT(11) NOT NULL AUTO_INCREMENT,
  `id_stock` INT(11) DEFAULT NULL,
  `tipo` ENUM('Ingreso','Salida','Devolucion','Regalo','Garantia') NOT NULL,
  `cantidad` INT(11) NOT NULL,
  `monto` DECIMAL(10,2) DEFAULT 0.00,
  `referencia` VARCHAR(100) DEFAULT NULL,
  `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id_movimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- almacen_salidas
CREATE TABLE IF NOT EXISTS `almacen_salidas` (
  `id_salida` INT(11) NOT NULL AUTO_INCREMENT,
  `id_stock` INT(11) NOT NULL,
  `nombre_guia` VARCHAR(100) NOT NULL,
  `cantidad` INT(11) NOT NULL,
  `fecha_salida` DATE NOT NULL,
  `garantia_original` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `observacion` TEXT DEFAULT NULL,
  `estado` ENUM('Pendiente','Parcial','Devuelto') NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`id_salida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- almacen_stock
CREATE TABLE IF NOT EXISTS `almacen_stock` (
  `id_stock` INT(11) NOT NULL AUTO_INCREMENT,
  `id_item` INT(11) NOT NULL,
  `talla` VARCHAR(10) DEFAULT NULL,
  `cantidad_total` INT(11) NOT NULL DEFAULT 0,
  `cantidad_disponible` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_stock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `Datos_clientes` (
  `id_cliente` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `dni` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `genero` VARCHAR(20) DEFAULT NULL,
  `nro_pasaporte` VARCHAR(50) DEFAULT NULL,
  `nacionalidad` VARCHAR(50) DEFAULT NULL,
  `Comida` VARCHAR(50) DEFAULT NULL,
  `tipo_cliente` VARCHAR(20) DEFAULT 'KB',
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- clientes_endosadores
CREATE TABLE IF NOT EXISTS `clientes_endosadores` (
  `id_cliente` INT(11) NOT NULL,  -- NO AUTO_INCREMENT
  `empresa_endosadora` VARCHAR(100) DEFAULT NULL,
  `contacto` VARCHAR(100) DEFAULT NULL,
  `telefono_contacto` VARCHAR(20) DEFAULT NULL,
  `email_contacto` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  FOREIGN KEY (`id_cliente`) REFERENCES Datos_clientes(`id_cliente`) ON DELETE CASCADE
  FOREIGN KEY (`id_grupo `) REFERENCES grupos(`id_grupo `) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- clientes_kb
CREATE TABLE IF NOT EXISTS `clientes_kb` (
  `id_cliente` INT(11) NOT NULL AUTO_INCREMENT,
  `edad` INT(11) DEFAULT NULL,
  `foto_pasaporte` VARCHAR(255) DEFAULT NULL,
  `nro_whatsapp` VARCHAR(20) DEFAULT NULL,
  `es_pagador` TINYINT(1) DEFAULT 0,
  `hotel` VARCHAR(100) DEFAULT NULL,
  `id_grupo` INT(11) DEFAULT NULL,
  `fecha_nacimiento` DATE DEFAULT NULL,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- contabilidad
CREATE TABLE IF NOT EXISTS `contabilidad` (
  `id_contabilidad` INT(11) NOT NULL AUTO_INCREMENT,
  `id_operaciones` INT(11) NOT NULL,
  `metodo_pago` ENUM('Efectivo','We travel','Izipay','PAYPAL','Bcp','CULQI','YAPE') DEFAULT NULL,
  `metodo_pago_adicional` ENUM('Efectivo','We travel','Izipay','PAYPAL','Bcp','CULQI','YAPE') DEFAULT NULL,
  `tipo_moneda` ENUM('Dólares','Soles') DEFAULT NULL,
  `tipo_moneda_adicional` ENUM('Soles','Dólares') DEFAULT NULL,
  `comision` DECIMAL(10,2) DEFAULT NULL,
  `precio_servicio` DECIMAL(10,2) DEFAULT NULL,
  `precio_servicio_adicional` DECIMAL(10,2) DEFAULT 0.00,
  `pagado_adicional` DECIMAL(10,2) DEFAULT NULL,
  `saldo_adicional` DECIMAL(10,2) DEFAULT NULL,
  `pagado_a_cuenta` DECIMAL(10,2) DEFAULT NULL,
  `saldo_pendiente` DECIMAL(10,2) DEFAULT NULL,
  `fecha_pago_saldo` DATE DEFAULT NULL,
  `estado` ENUM('pendiente','pagado','reembolsado') DEFAULT 'pendiente',
  `modalidad_recibo` ENUM('FACTURA','FAC_EXPORTACION','BV_INTANGIBLE','BV_IGV') DEFAULT NULL,
  `nro_boleta_cuenta` VARCHAR(50) DEFAULT NULL,
  `nro_boleta_total` VARCHAR(50) DEFAULT NULL,
  `Nro_Comprobante_adicional` VARCHAR(50) DEFAULT NULL,
  `detraccion` DECIMAL(10,2) DEFAULT NULL,
  `NotaCredito` TINYINT(1) DEFAULT 0,
  `metodo_pago_saldo` VARCHAR(50) DEFAULT NULL,
  `tipo_moneda_saldo` VARCHAR(20) DEFAULT NULL,
  `monto_pago_saldo` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_contabilidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Usuario` VARCHAR(255) NOT NULL,
  `Contraseña` VARCHAR(255) NOT NULL,
  `Area` ENUM('Operaciones','Ventas','Almacén','Contabilidad','Planificación','Clientes') NOT NULL,
  `EsAdmin` TINYINT(1) NOT NULL DEFAULT 0,
  `rol` ENUM('admin','almacen') DEFAULT 'almacen',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ventas
CREATE TABLE IF NOT EXISTS `venta` (
  `id_venta` INT(11) NOT NULL AUTO_INCREMENT,
  `id_operaciones` INT(11) NOT NULL,
  `id_contabilidad` INT(11) NOT NULL,
  `nro_voucher` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- operaciones
CREATE TABLE IF NOT EXISTS `operaciones` (
  `id_operaciones` INT(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` INT(11) NOT NULL,
  `grupo` VARCHAR(20) DEFAULT NULL,
  `fecha_reserva` DATE DEFAULT NULL,
  `nombre_servicio` ENUM(
'SALKANTAY A MACHU PICCHU 5 DÍAS',
'SALKANTAY A MACHU PICCHU 4 DÍAS',
'SALKANTAY A MACHU PICCHU 3 DÍAS',
'SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS',
'SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)',
'SALKANTAY TREK 5D/4N WITH LUXURY DOMES (PRIVADO)',
'SALKANTAY TREK 4D / 3N WITH LUXURY DOMES (PRIVADO)',
'SALKANTAY & HUMANTAY LAKE 2D WITH LUXURY DOMES (PRIVADO)',
'CAMINO INCA 4 DÍAS',
'CAMINO INCA 4 DÍAS (PRIVADO)',
'CAMINO INCA 2 DÍAS',
'CAMINO INCA FULL DAY',
'MACHU PICCHU DE UN DÍA',
'MACHU PICCHU EN TREN 2 DÍAS',
'VALLE SAGRADO A MACHU PICCHU 2 DÍAS',
'CHOQUEQUIRAO 4 DÍAS',
'CHOQUEQUIRAO 4 DÍAS (PRIVADO)',
'CHOQUEQUIRAO 5 DÍAS (PRIVADO)',
'Q’ESHUACHAKA Y 4 LAGUNAS DE UN DÍA',
'HUCHUY QOSQO 3 DÍAS (PRIVADO)',
'AUSANGATE Y MONTAÑA DE COLORES 4 DÍAS',
'LARES A MACHU PICCHU 4 DÍAS (PRIVADO)',
'INCA JUNGLE TRAIL 4 DAYS',
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
'ICA – PARACAS DE UN DIA',
'PUNO DE UN DÍA',
'MANU 4 DÍAS Y 3 NOCHES'
  ),
  `servicio_adicional` VARCHAR(255) DEFAULT NULL,
  `modalidad_retorno` ENUM('Tren','Carro','Sin retorno') DEFAULT NULL,
  `incluye_ingreso` ENUM('Con ingreso','Sin ingreso') DEFAULT NULL,
  `fecha_salida` DATE DEFAULT NULL,
  `fecha_retorno` DATE DEFAULT NULL,
  `empresa` VARCHAR(50) DEFAULT NULL,
  `observaciones` VARCHAR(255) DEFAULT NULL,
  `Encargado` VARCHAR(50) DEFAULT NULL,
  `id_grupo` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id_operaciones`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- grupos
CREATE TABLE IF NOT EXISTS `grupos` (
  `id_grupo` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre_grupo` VARCHAR(50) NOT NULL,
  `hotel` VARCHAR(100) DEFAULT NULL,
  `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP(),
  `cantidad` INT(11) NOT NULL DEFAULT 0,
  `registrados` INT(11) NOT NULL DEFAULT 0,
  `estado` ENUM('abierto','cerrado') DEFAULT 'abierto',
  PRIMARY KEY (`id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- perfil_clientes
CREATE TABLE IF NOT EXISTS `perfil_clientes` (
  `id_perfil` INT(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` INT(11) NOT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) NOT NULL,
  `fecha_nacimiento` DATE DEFAULT NULL,
  `ocupacion` VARCHAR(100) DEFAULT NULL,
  `intereses` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_perfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- historial_viajes
CREATE TABLE IF NOT EXISTS `historial_viajes` (
  `id_historial` INT(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` INT(11) NOT NULL,
  `fecha_viaje` DATE NOT NULL,
  `destino` VARCHAR(255) NOT NULL,
  `tipo_servicio` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_historial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- planificacion
CREATE TABLE IF NOT EXISTS `planificacion` (
  `id_planificacion` INT(11) NOT NULL AUTO_INCREMENT,
  `id_operaciones` INT(11) NOT NULL,
  `id_grupo` INT(11) NOT NULL,
  `grupo` VARCHAR(20) NOT NULL,
  `nombre_guia` VARCHAR(100) DEFAULT NULL,
  `nombre_cocinero` VARCHAR(100) DEFAULT NULL,
  `nombre_asistente` VARCHAR(100) DEFAULT NULL,
  `grupo_operativo` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_planificacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================
-- TRIGGERS
-- ================================

DELIMITER $$

CREATE TRIGGER `trg_calcular_monto_devuelto` 
BEFORE INSERT ON `almacen_devoluciones` 
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
END $$

CREATE TRIGGER `trg_devolver_stock` 
AFTER INSERT ON `almacen_devoluciones` 
FOR EACH ROW 
BEGIN
    DECLARE v_id_stock INT;

    SELECT id_stock
    INTO v_id_stock
    FROM almacen_salidas
    WHERE id_salida = NEW.id_salida;

    UPDATE almacen_stock
    SET cantidad_disponible = cantidad_disponible + NEW.cantidad_devuelta
    WHERE id_stock = v_id_stock;
END $$

DELIMITER ;

-- ================================
-- FIN SCRIPT BASE COMPLETO
-- ================================

COMMIT;
