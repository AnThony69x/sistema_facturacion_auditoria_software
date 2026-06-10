-- ============================================================
-- SISTEMA DE FACTURACIÓN ELECTRÓNICA - ECUADOR (SRI)
-- Base de Datos: facturacion_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS facturacion_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE facturacion_db;

-- -----------------------------------------------------------
-- TABLA: usuarios
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    foto VARCHAR(255) DEFAULT 'default.png',
    rol ENUM('admin','cajero','bodeguero') DEFAULT 'cajero',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Usuario administrador por defecto (password: Admin123!)
INSERT INTO usuarios (nombres, username, password, rol) VALUES
('Administrador Sistema', 'admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- -----------------------------------------------------------
-- TABLA: clientes
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    tipo_identificacion ENUM('cedula','ruc','pasaporte') DEFAULT 'cedula',
    nombres VARCHAR(150) NOT NULL,
    direccion TEXT,
    telefono VARCHAR(20),
    correo VARCHAR(150),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Cliente consumidor final
INSERT INTO clientes (cedula, tipo_identificacion, nombres, direccion, telefono, correo) VALUES
('9999999999999', 'ruc', 'CONSUMIDOR FINAL', 'GUAYAQUIL - ECUADOR', '0000000000', 'consumidorfinal@sri.gob.ec');

-- -----------------------------------------------------------
-- TABLA: categorias_productos
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO categorias (nombre) VALUES ('General'), ('Electrónica'), ('Alimentos'), ('Ropa'), ('Servicios');

-- -----------------------------------------------------------
-- TABLA: productos
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    categoria_id INT DEFAULT 1,
    existencia DECIMAL(10,2) DEFAULT 0,
    precio_compra DECIMAL(10,4) DEFAULT 0,
    precio_venta DECIMAL(10,4) DEFAULT 0,
    iva DECIMAL(5,2) DEFAULT 15.00,
    unidad_medida VARCHAR(30) DEFAULT 'UNIDAD',
    foto VARCHAR(255) DEFAULT 'default_product.png',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLA: facturas (cabecera)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(20) NOT NULL UNIQUE,
    clave_acceso VARCHAR(50) UNIQUE,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_emision DATE NOT NULL,
    subtotal_sin_iva DECIMAL(10,4) DEFAULT 0,
    subtotal_con_iva DECIMAL(10,4) DEFAULT 0,
    descuento DECIMAL(10,4) DEFAULT 0,
    iva_total DECIMAL(10,4) DEFAULT 0,
    total DECIMAL(10,4) DEFAULT 0,
    forma_pago ENUM('efectivo','tarjeta','transferencia','cheque','credito') DEFAULT 'efectivo',
    observaciones TEXT,
    estado ENUM('borrador','autorizada','anulada','pendiente') DEFAULT 'borrador',
    estado_sri VARCHAR(50) DEFAULT NULL,
    xml_generado LONGTEXT,
    xml_autorizado LONGTEXT,
    fecha_autorizacion DATETIME DEFAULT NULL,
    numero_autorizacion VARCHAR(50) DEFAULT NULL,
    enviado_correo TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLA: factura_detalle
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS factura_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    producto_id INT NOT NULL,
    descripcion VARCHAR(300),
    cantidad DECIMAL(10,4) NOT NULL,
    precio_unitario DECIMAL(10,4) NOT NULL,
    descuento DECIMAL(10,4) DEFAULT 0,
    iva_porcentaje DECIMAL(5,2) DEFAULT 15.00,
    subtotal DECIMAL(10,4) NOT NULL,
    iva_valor DECIMAL(10,4) NOT NULL,
    total DECIMAL(10,4) NOT NULL,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLA: configuracion_empresa
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(200) NOT NULL,
    nombre_comercial VARCHAR(200),
    ruc VARCHAR(20) NOT NULL,
    direccion_matriz TEXT,
    telefono VARCHAR(30),
    correo VARCHAR(150),
    logo VARCHAR(255),
    ambiente ENUM('1','2') DEFAULT '1' COMMENT '1=Pruebas, 2=Produccion',
    tipo_emision ENUM('1') DEFAULT '1' COMMENT '1=Normal',
    secuencial INT DEFAULT 1,
    establecimiento VARCHAR(3) DEFAULT '001',
    punto_emision VARCHAR(3) DEFAULT '001',
    certificado_p12 VARCHAR(255),
    clave_certificado VARCHAR(255),
    smtp_host VARCHAR(150) DEFAULT 'smtp.gmail.com',
    smtp_port INT DEFAULT 587,
    smtp_user VARCHAR(150),
    smtp_pass VARCHAR(255),
    smtp_from_name VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO configuracion (razon_social, nombre_comercial, ruc, direccion_matriz, telefono, correo) VALUES
('MI EMPRESA S.A.', 'MI EMPRESA', '0999999999001', 'AV. PRINCIPAL 123, GUAYAQUIL', '0999999999', 'empresa@correo.com');

-- -----------------------------------------------------------
-- VISTA: resumen de facturas
-- -----------------------------------------------------------
CREATE OR REPLACE VIEW v_facturas AS
SELECT 
    f.id,
    f.numero_factura,
    f.clave_acceso,
    f.fecha_emision,
    c.cedula,
    c.nombres AS cliente,
    c.correo AS cliente_correo,
    u.nombres AS usuario,
    f.subtotal_sin_iva,
    f.subtotal_con_iva,
    f.descuento,
    f.iva_total,
    f.total,
    f.forma_pago,
    f.estado,
    f.estado_sri,
    f.enviado_correo,
    f.created_at
FROM facturas f
JOIN clientes c ON f.cliente_id = c.id
JOIN usuarios u ON f.usuario_id = u.id;
