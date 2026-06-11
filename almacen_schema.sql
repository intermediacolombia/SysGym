-- =====================================================
-- Módulo Almacén (insumos internos) — esquema y permisos
-- Ejecutar manualmente contra la base de datos del sistema.
-- =====================================================

CREATE TABLE `almacen_categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `borrado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `almacen_elementos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `categoria_id` int DEFAULT NULL,
  `unidad_medida` varchar(20) NOT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) DEFAULT NULL,
  `alerta_stock` tinyint(1) NOT NULL DEFAULT '0',
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `borrado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `fk_almacen_elementos_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `almacen_categorias` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `almacen_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `elemento_id` int NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `observacion` text,
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `elemento_id` (`elemento_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_almacen_movimientos_elemento`
    FOREIGN KEY (`elemento_id`) REFERENCES `almacen_elementos` (`id`),
  CONSTRAINT `fk_almacen_movimientos_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Permisos del módulo
INSERT INTO `permissions` (`name`, `category`) VALUES
('Administrar Almacén', 'Almacén'),
('Registrar Entradas Almacén', 'Almacén'),
('Registrar Salidas Almacén', 'Almacén');

-- Asignar todos los permisos de Almacén al rol Administrador (id 1)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`
WHERE `name` IN ('Administrar Almacén', 'Registrar Entradas Almacén', 'Registrar Salidas Almacén');
