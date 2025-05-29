-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-05-2025 a las 09:31:12
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `granja`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `ActualizarInventarioProducto` (IN `p_id_producto` INT, IN `p_nombre` VARCHAR(100), IN `p_descripcion` TEXT, IN `p_precio` DECIMAL(10,2), IN `p_cantidad` INT, IN `p_categoria` ENUM('Leche','Huevos','Carne','Pollo','Frutas','Verduras'), IN `p_disponible` TINYINT(1))   BEGIN
    DECLARE old_nombre VARCHAR(100);
    DECLARE old_precio DECIMAL(10,2);
    DECLARE old_cantidad INT;
    DECLARE old_disponible TINYINT(1);
    
    -- Obtener valores antiguos para registro en auditoría
    SELECT nombre, precio, cantidad, disponible 
    INTO old_nombre, old_precio, old_cantidad, old_disponible
    FROM inventario_productos 
    WHERE id_producto = p_id_producto;
    
    -- Actualizar el producto
    UPDATE inventario_productos SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        precio = p_precio,
        cantidad = p_cantidad,
        categoria = p_categoria,
        disponible = p_disponible
    WHERE id_producto = p_id_producto;
    
    -- Registrar en auditoría
    INSERT INTO auditorias (usuario, tabla_afectada, accion, id_registro, detalles)
    VALUES (
        CURRENT_USER(), 
        'inventario_productos', 
        'UPDATE', 
        p_id_producto,
        CONCAT(
            'Producto actualizado: ', old_nombre, ' -> ', p_nombre, 
            ' | Precio: $', old_precio, ' -> $', p_precio,
            ' | Cantidad: ', old_cantidad, ' -> ', p_cantidad,
            ' | Disponible: ', IF(old_disponible=1, 'Sí', 'No'), ' -> ', IF(p_disponible=1, 'Sí', 'No')
        )
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ActualizarInventarioVenta` (IN `p_id_producto` INT, IN `p_cantidad` INT, IN `p_id_pedido` INT, IN `p_id_cliente` INT, IN `p_metodo_pago` VARCHAR(50))   BEGIN
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_stock_actual INT;
    DECLARE v_nombre_producto VARCHAR(100);
    
    -- Obtener información del producto
    SELECT precio, cantidad, nombre INTO v_precio, v_stock_actual, v_nombre_producto
    FROM inventario_productos 
    WHERE id_producto = p_id_producto FOR UPDATE;
    
    -- Verificar stock suficiente
    IF v_stock_actual < p_cantidad THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'No hay suficiente stock para completar la venta';
    ELSE
        -- Registrar la venta
        INSERT INTO ventas (
            id_pedido, 
            id_producto, 
            cantidad, 
            precio_unitario, 
            precio_total, 
            id_cliente,
            metodo_pago
        ) VALUES (
            p_id_pedido,
            p_id_producto,
            p_cantidad,
            v_precio,
            v_precio * p_cantidad,
            p_id_cliente,
            p_metodo_pago
        );
        
        -- Actualizar el inventario
        UPDATE inventario_productos 
        SET cantidad = cantidad - p_cantidad
        WHERE id_producto = p_id_producto;
        
        -- Registrar alerta de venta
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'venta',
            CONCAT('Venta registrada: ', p_cantidad, ' unidades de "', 
                  v_nombre_producto, '" (ID: ', p_id_producto, 
                  ') por $', (v_precio * p_cantidad), 
                  '. Cliente ID: ', p_id_cliente, '.'),
            NOW()
        );
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ActualizarVacuna` (IN `p_id_vacuna` INT, IN `p_nombre` VARCHAR(100), IN `p_descripcion` TEXT, IN `p_fabricante` VARCHAR(100), IN `p_temperatura_almacenamiento` VARCHAR(50), IN `p_vida_util` VARCHAR(50), IN `p_cantidad` INT)   BEGIN
    UPDATE Vacunas
    SET nombre = p_nombre,
        descripcion = p_descripcion,
        fabricante = p_fabricante,
        temperatura_almacenamiento = p_temperatura_almacenamiento,
        vida_util = p_vida_util,
        cantidad = p_cantidad
    WHERE id_vacuna = p_id_vacuna;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_alimentacion` (IN `p_id_alimentacion` INT, IN `p_id_especie` INT, IN `p_tipo_alimento` VARCHAR(100), IN `p_comidas_por_dia` INT, IN `p_cantidad_gramos` DECIMAL(10,2), IN `p_fecha_ultima_alimentacion` DATE)   BEGIN
    UPDATE Alimentacion 
    SET id_especie = p_id_especie, 
        tipo_alimento = p_tipo_alimento,
        comidas_por_dia = p_comidas_por_dia,
        cantidad_gramos = p_cantidad_gramos,
        fecha_ultima_alimentacion = p_fecha_ultima_alimentacion
    WHERE id_alimentacion = p_id_alimentacion;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_animal` (IN `p_id_animal` INT, IN `p_nombre_cientifico` VARCHAR(100), IN `p_nombre_comun` VARCHAR(100), IN `p_id_especie` INT, IN `p_edad` INT, IN `p_ubicacion` VARCHAR(255), IN `p_estado` ENUM('Sano','Enfermo','Recuperación'), IN `p_descripcion` TEXT)   BEGIN
    UPDATE animales 
    SET nombre_cientifico = p_nombre_cientifico, 
        nombre_comun = p_nombre_comun,
        id_especie = p_id_especie,
        edad = p_edad,
        ubicacion = p_ubicacion,
        estado = p_estado,
        descripcion = p_descripcion
    WHERE id_animal = p_id_animal;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_empleado` (IN `p_id_empleado` INT, IN `p_nombre` VARCHAR(100), IN `p_apellido` VARCHAR(100), IN `p_cargo` VARCHAR(100), IN `p_salario` DECIMAL(10,2))   BEGIN
    UPDATE empleados 
    SET nombre = p_nombre, 
        apellido = p_apellido,
        cargo = p_cargo,
        salario = p_salario
    WHERE id_empleado = p_id_empleado;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_especie` (IN `p_id_especie` INT, IN `p_nombre_especie` VARCHAR(50), IN `p_descripcion` TEXT)   BEGIN
    UPDATE especies 
    SET nombre_especie = p_nombre_especie, 
        descripcion = p_descripcion
    WHERE id_especie = p_id_especie;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_estado_salud` (IN `p_id_historial` INT, IN `p_estado_anterior` VARCHAR(100), IN `p_estado_nuevo` VARCHAR(100))   BEGIN
    UPDATE historial_estado_salud 
    SET estado_anterior = p_estado_anterior,
    estado_nuevo = p_estado_nuevo
    WHERE id_historial = p_id_historial;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_inventario` (IN `p_id_inventario` INT, IN `p_producto` VARCHAR(100), IN `p_cantidad` INT, IN `p_fecha_ingreso` DATE)   BEGIN
    UPDATE inventario 
    SET nombre_producto = p_producto, 
        cantidad = p_cantidad,
        fecha_ingreso = p_fecha_ingreso
    WHERE id_producto = p_id_inventario;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_planta` (IN `p_id_planta` INT, IN `p_nombre_cientifico` VARCHAR(100), IN `p_nombre_comun` VARCHAR(100), IN `p_ubicacion` VARCHAR(255), IN `p_estado` ENUM('Sano','Enfermo','Recuperación'), IN `p_descripcion` TEXT)   BEGIN
    UPDATE plantas 
    SET nombre_cientifico = p_nombre_cientifico, 
        nombre_comun = p_nombre_comun,
        ubicacion = p_ubicacion,
        estado = p_estado,
        descripcion = p_descripcion
    WHERE id_planta = p_id_planta;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_produccion` (IN `p_id_produccion` INT, IN `p_id_animal` INT, IN `p_tipo_produccion` ENUM('Leche','Huevos','Carne','Otro'), IN `p_cantidad` DECIMAL(10,2), IN `p_fecha_recoleccion` DATE)   BEGIN
    UPDATE Produccion 
    SET id_animal = p_id_animal, 
        tipo_produccion = p_tipo_produccion,
        cantidad = p_cantidad,
        fecha_recoleccion = p_fecha_recoleccion
    WHERE id_produccion = p_id_produccion;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_proveedor` (IN `p_id_proveedor` INT, IN `p_nombre` VARCHAR(100), IN `p_telefono` VARCHAR(100), IN `p_direccion` VARCHAR(255))   BEGIN
    UPDATE proveedores 
    SET nombre = p_nombre, 
        telefono = p_telefono,
        direccion = p_direccion
    WHERE id_proveedor = p_id_proveedor;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `EliminarAnimal` (IN `animal_id` INT)   BEGIN
    DELETE FROM Produccion WHERE id_animal = animal_id;
    DELETE FROM Ventas WHERE id_animal = animal_id;
    DELETE FROM Costos WHERE id_animal = animal_id;
    DELETE FROM reportes WHERE id_animal = animal_id;
    DELETE FROM Vacunacion WHERE id_animal = animal_id;
    DELETE FROM Historial_Estado_Salud WHERE id_animal = animal_id;
    DELETE FROM Ubicacion_Georreferenciada WHERE id_animal = animal_id;
    DELETE FROM animales WHERE id_animal = animal_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `EliminarEmpleado` (IN `empleado_id` INT)   BEGIN
    DELETE FROM Costos WHERE id_empleado = empleado_id;
    DELETE FROM Vacunacion WHERE id_empleado = empleado_id;
    DELETE FROM Empleados WHERE id_empleado = empleado_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `EliminarEspecie` (IN `especie_id` INT)   BEGIN
    DELETE FROM Alimentacion WHERE id_especie = especie_id;
    DELETE FROM Costos WHERE id_especie = especie_id;
    DELETE FROM especies WHERE id_especie = especie_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `EliminarInventarioProducto` (IN `p_id_producto` INT)   BEGIN
    DECLARE v_nombre VARCHAR(100);
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_categoria VARCHAR(20);
    
    -- Obtener datos del producto para registro en auditoría
    SELECT nombre, precio, categoria 
    INTO v_nombre, v_precio, v_categoria
    FROM inventario_productos 
    WHERE id_producto = p_id_producto;
    
    -- Eliminar el producto
    DELETE FROM inventario_productos WHERE id_producto = p_id_producto;
    
    -- Registrar en auditoría
    INSERT INTO auditorias (usuario, tabla_afectada, accion, id_registro, detalles)
    VALUES (
        CURRENT_USER(), 
        'inventario_productos', 
        'DELETE', 
        p_id_producto,
        CONCAT('Producto eliminado: ', v_nombre, ' | Categoría: ', v_categoria, ' | Precio: $', v_precio)
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `EliminarPlanta` (IN `planta_id` INT)   BEGIN
    DELETE FROM reportes WHERE id_planta = planta_id;
    DELETE FROM Historial_Estado_Salud WHERE id_planta = planta_id;
    DELETE FROM Ubicacion_Georreferenciada WHERE id_planta = planta_id;
    DELETE FROM plantas WHERE id_planta = planta_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `EliminarProveedor` (IN `proveedor_id` INT)   BEGIN
    DELETE FROM Inventario WHERE id_proveedor = proveedor_id;
    DELETE FROM Proveedores WHERE id_proveedor = proveedor_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `EliminarReporte` (IN `reporte_id` INT)   BEGIN
    DELETE FROM Imagenes_Adjuntos WHERE id_reporte = reporte_id;
    DELETE FROM tratamientos WHERE id_reporte = reporte_id;
    DELETE FROM reportes WHERE id_reporte = reporte_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `EliminarVacuna` (IN `vacuna_id` INT)   BEGIN
    DELETE FROM Vacunacion WHERE id_vacuna = vacuna_id;
    DELETE FROM Vacunas WHERE id_vacuna = vacuna_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarAlimentacion` (IN `p_id_especie` INT, IN `p_tipo_alimento` VARCHAR(100), IN `p_comidas_por_dia` INT, IN `p_cantidad_gramos` DECIMAL(10,2), IN `p_fecha_alimentacion` DATE)   BEGIN
    INSERT INTO Alimentacion (id_especie, tipo_alimento, comidas_por_dia, cantidad_gramos, fecha_ultima_alimentacion)
    VALUES (p_id_especie, p_tipo_alimento, p_comidas_por_dia, p_cantidad_gramos, p_fecha_alimentacion);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarAnimal` (IN `p_id_especie` INT, IN `p_nombre_cientifico` VARCHAR(100), IN `p_nombre_comun` VARCHAR(100), IN `p_edad` INT, IN `p_ubicacion` VARCHAR(255), IN `p_estado` ENUM('Sano','Enfermo','Recuperación'), IN `p_descripcion` TEXT)   BEGIN
    INSERT INTO animales (id_especie, nombre_cientifico, nombre_comun, edad, ubicacion, estado, descripcion)
    VALUES (p_id_especie, p_nombre_cientifico, p_nombre_comun,  p_edad, p_ubicacion, p_estado, p_descripcion);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarCosto` (IN `p_tipo_costo` ENUM('Alimentación','Salud','Mantenimiento','Salarios','Otro'), IN `p_descripcion` TEXT, IN `p_monto` DECIMAL(10,2), IN `p_fecha` DATE, IN `p_id_empleado` INT, IN `p_id_animal` INT, IN `p_id_especie` INT)   BEGIN
    INSERT INTO Costos (tipo_costo, descripcion, monto, fecha, id_empleado, id_animal, id_especie)
    VALUES (p_tipo_costo, p_descripcion, p_monto, p_fecha, p_id_empleado, p_id_animal, p_id_especie);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarEmpleado` (IN `nombre` VARCHAR(100), IN `rol` VARCHAR(50), IN `telefono` VARCHAR(20), IN `fecha_contratacion` DATE, IN `salario` DECIMAL(10,2))   BEGIN
    INSERT INTO Empleados (nombre, rol, telefono, fecha_contratacion, salario)
    VALUES (nombre, rol, telefono, fecha_contratacion, salario);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarEspecie` (IN `nombre` VARCHAR(50), IN `descripcion` TEXT)   BEGIN
    INSERT INTO especies (nombre_especie, descripcion) VALUES (nombre, descripcion);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarHistorialSalud` (IN `p_id_animal` INT, IN `p_id_planta` INT, IN `p_estado_anterior` ENUM('Sano','Enfermo','Recuperacion'), IN `p_estado_nuevo` ENUM('Sano','Enfermo','Recuperacion'))   BEGIN
    INSERT INTO historial_estado_salud (id_animal, id_planta, estado_anterior, estado_nuevo, fecha_cambio)
    VALUES (p_id_animal, p_id_planta, p_estado_anterior, p_estado_nuevo, NOW());
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarInventario` (IN `p_nombre_producto` VARCHAR(100), IN `p_cantidad` DECIMAL(10,2), IN `p_unidad_medida` VARCHAR(20), IN `p_fecha_ingreso` DATE, IN `p_id_proveedor` INT)   BEGIN
    INSERT INTO Inventario (nombre_producto, cantidad, unidad_medida, fecha_ingreso, id_proveedor)
    VALUES (p_nombre_producto, p_cantidad, p_unidad_medida, p_fecha_ingreso, p_id_proveedor);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarInventarioProducto` (IN `p_nombre` VARCHAR(100), IN `p_descripcion` TEXT, IN `p_precio` DECIMAL(10,2), IN `p_cantidad` INT, IN `p_categoria` ENUM('Leche','Huevos','Carne','Pollo','Frutas','Verduras'), IN `p_disponible` TINYINT(1))   BEGIN
    INSERT INTO inventario_productos (
        nombre, 
        descripcion, 
        precio, 
        cantidad, 
        categoria, 
        disponible
    ) VALUES (
        p_nombre,
        p_descripcion,
        p_precio,
        p_cantidad,
        p_categoria,
        p_disponible
    );
    
    -- Registrar en auditoría
    INSERT INTO auditorias (usuario, tabla_afectada, accion, id_registro, detalles)
    VALUES (
        CURRENT_USER(), 
        'inventario_productos', 
        'INSERT', 
        LAST_INSERT_ID(),
        CONCAT('Nuevo producto: ', p_nombre, ' | Precio: $', p_precio, ' | Cantidad: ', p_cantidad)
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarPlanta` (IN `nombre_cientifico` VARCHAR(100), IN `nombre_comun` VARCHAR(100), IN `ubicacion` VARCHAR(255), IN `estado` ENUM('Sano','Enfermo','Recuperación'), IN `descripcion` TEXT)   BEGIN
    INSERT INTO plantas (nombre_cientifico, nombre_comun, ubicacion, estado, descripcion)
    VALUES (nombre_cientifico, nombre_comun, ubicacion, estado, descripcion);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarProduccion` (IN `id_animal` INT, IN `tipo_produccion` ENUM('Leche','Huevos','Carne','Otro'), IN `cantidad` DECIMAL(10,2), IN `fecha_recoleccion` DATE)   BEGIN
    INSERT INTO Produccion (id_animal, tipo_produccion, cantidad, fecha_recoleccion)
    VALUES (id_animal, tipo_produccion, cantidad, fecha_recoleccion);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarProveedor` (IN `p_nombre` VARCHAR(100), IN `p_telefono` VARCHAR(20), IN `p_direccion` VARCHAR(255), IN `p_tipo_producto` VARCHAR(100))   BEGIN
    INSERT INTO Proveedores (nombre, telefono, direccion, tipo_producto)
    VALUES (p_nombre, p_telefono, p_direccion, p_tipo_producto);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarReporte` (IN `p_id_usuario` INT, IN `p_id_planta` INT, IN `p_id_animal` INT, IN `p_tipo` ENUM('Planta','Animal'), IN `p_diagnostico` TEXT)   BEGIN
    INSERT INTO reportes (id_usuario, id_planta, id_animal, tipo, diagnostico, fecha_reporte)
    VALUES (p_id_usuario, p_id_planta, p_id_animal, p_tipo, p_diagnostico, NOW());
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarTratamiento` (IN `p_id_reporte` INT, IN `p_descripcion` TEXT, IN `p_fecha_inicio` DATE, IN `p_fecha_fin` DATE, IN `p_resultado` ENUM('Exitoso','En Proceso','Fallido'))   BEGIN
    INSERT INTO tratamientos (id_reporte, descripcion, fecha_inicio, fecha_fin, resultado)
    VALUES (p_id_reporte, p_descripcion, p_fecha_inicio, p_fecha_fin, p_resultado);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarVacuna` (IN `p_nombre` VARCHAR(100), IN `p_descripcion` TEXT, IN `p_fabricante` VARCHAR(100), IN `p_temperatura_almacenamiento` VARCHAR(50), IN `p_vida_util` VARCHAR(50), IN `cantidad` INT)   BEGIN
    INSERT INTO Vacunas (nombre, descripcion, fabricante, temperatura_almacenamiento, vida_util)
    VALUES (p_nombre, p_descripcion, p_fabricante, p_temperatura_almacenamiento, p_vida_util);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarVacunacion` (IN `p_id_animal` INT, IN `p_id_vacuna` INT, IN `p_fecha_aplicacion` DATE, IN `p_proxima_dosis` DATE, IN `p_dosis` VARCHAR(50), IN `p_id_empleado` INT, IN `p_observaciones` TEXT)   BEGIN
    INSERT INTO Vacunacion (id_animal, id_vacuna, fecha_aplicacion, proxima_dosis, dosis, id_empleado, observaciones)
    VALUES (p_id_animal, p_id_vacuna, p_fecha_aplicacion, p_proxima_dosis, p_dosis, p_id_empleado, p_observaciones);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertarVenta` (IN `id_produccion` INT, IN `id_animal` INT, IN `cantidad` DECIMAL(10,2), IN `precio_total` DECIMAL(10,2), IN `fecha_venta` DATE, IN `comprador` VARCHAR(100))   BEGIN
    INSERT INTO Ventas (id_produccion, id_animal, cantidad, precio_total, fecha_venta, comprador)
    VALUES (id_produccion, id_animal, cantidad, precio_total, fecha_venta, comprador);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `RealizarCompra` (IN `p_id_cliente` INT, IN `p_direccion_envio` VARCHAR(255), IN `p_metodo_pago` VARCHAR(50))   BEGIN
    DECLARE v_total DECIMAL(10,2) DEFAULT 0;
    DECLARE v_id_pedido INT;
    DECLARE v_cantidad_disponible INT;
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE v_id_producto INT;
    DECLARE v_cantidad INT;
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_nombre_cliente VARCHAR(100);
    DECLARE v_email_cliente VARCHAR(100);
    DECLARE v_productos_lista TEXT DEFAULT '';
    DECLARE v_nombre_producto VARCHAR(100);
    DECLARE v_mensaje_error TEXT;
    
    -- Cursor para los productos en el carrito
    DECLARE cur CURSOR FOR 
        SELECT c.id_producto, c.cantidad, p.precio, p.nombre 
        FROM carrito_compras c
        JOIN inventario_productos p ON c.id_producto = p.id_producto
        WHERE c.id_cliente = p_id_cliente;
    
    -- Manejador para fin del cursor
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    -- Obtener información del cliente
    SELECT nombre, email INTO v_nombre_cliente, v_email_cliente
    FROM clientes WHERE id_cliente = p_id_cliente;
    
    -- Iniciar transacción
    START TRANSACTION;
    
    -- Crear el pedido
    INSERT INTO pedidos (id_cliente, total, direccion_envio, metodo_pago, estado)
    VALUES (p_id_cliente, 0, p_direccion_envio, p_metodo_pago, 'pendiente');
    
    SET v_id_pedido = LAST_INSERT_ID();
    
    -- Procesar cada producto del carrito
    OPEN cur;
    calc_loop: LOOP
        FETCH cur INTO v_id_producto, v_cantidad, v_precio, v_nombre_producto;
        IF v_done THEN
            LEAVE calc_loop;
        END IF;
        
        -- Verificar disponibilidad
        SELECT cantidad INTO v_cantidad_disponible 
        FROM inventario_productos 
        WHERE id_producto = v_id_producto FOR UPDATE;
        
        IF v_cantidad_disponible < v_cantidad THEN
            -- No hay suficiente stock
            ROLLBACK;
            SET v_mensaje_error = CONCAT('No hay suficiente stock para el producto "', 
                                        v_nombre_producto, '" (ID: ', v_id_producto, 
                                        '). Stock disponible: ', v_cantidad_disponible);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje_error;
            LEAVE calc_loop;
        END IF;
        
        -- Sumar al total
        SET v_total = v_total + (v_precio * v_cantidad);
        
        -- Registrar la venta
        CALL ActualizarInventarioVenta(
            v_id_producto, 
            v_cantidad, 
            v_id_pedido, 
            p_id_cliente, 
            p_metodo_pago
        );
        
        -- Agregar a la lista de productos
        SET v_productos_lista = CONCAT(v_productos_lista, 
            IF(v_productos_lista = '', '', ', '), 
            v_nombre_producto, ' (', v_cantidad, ' x $', v_precio, ')');
    END LOOP;
    CLOSE cur;
    
    -- Actualizar el total del pedido
    UPDATE pedidos 
    SET total = v_total 
    WHERE id_pedido = v_id_pedido;
    
    -- Vaciar carrito del cliente
    DELETE FROM carrito_compras WHERE id_cliente = p_id_cliente;
    
    -- Confirmar transacción
    COMMIT;
    
    -- Registrar alerta con información detallada
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'venta', 
        CONCAT(
            'Nuevo pedido #', v_id_pedido, 
            ' - Cliente: ', v_nombre_cliente, ' (', v_email_cliente, ')',
            ' - Productos: ', v_productos_lista,
            ' - Total: $', v_total, ' COP',
            ' - Método de pago: ', p_metodo_pago
        ), 
        NOW()
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `VerificarAccesoUsuario` (IN `p_id_usuario` INT, IN `p_exito` BOOLEAN, OUT `p_permite_acceso` BOOLEAN, OUT `p_mensaje` VARCHAR(255))   BEGIN
    DECLARE v_intentos INT;
    DECLARE v_bloqueado_hasta DATETIME;
    DECLARE v_minutos_restantes INT;
    
    -- Verificar si existe registro de bloqueo, si no, crearlo
    IF NOT EXISTS (SELECT 1 FROM bloqueo_usuarios WHERE id_usuario = p_id_usuario) THEN
        INSERT INTO bloqueo_usuarios (id_usuario, intentos_fallidos) 
        VALUES (p_id_usuario, 0);
    END IF;
    
    -- Obtener estado actual
    SELECT intentos_fallidos, bloqueado_hasta 
    INTO v_intentos, v_bloqueado_hasta
    FROM bloqueo_usuarios 
    WHERE id_usuario = p_id_usuario;
    
    -- Verificar si el bloqueo ha expirado
    IF v_bloqueado_hasta IS NOT NULL AND v_bloqueado_hasta <= NOW() THEN
        -- Desbloquear automáticamente
        UPDATE bloqueo_usuarios
        SET intentos_fallidos = 0,
            bloqueado_desde = NULL,
            bloqueado_hasta = NULL
        WHERE id_usuario = p_id_usuario;
        
        SET v_intentos = 0;
        SET v_bloqueado_hasta = NULL;
        
        -- Registrar desbloqueo automático
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES ('usuario', CONCAT('Usuario ID ', p_id_usuario, ' desbloqueado automáticamente.'), NOW());
    END IF;
    
    -- Procesar el intento de acceso
    IF p_exito THEN
        -- Éxito: reiniciar contador
        UPDATE bloqueo_usuarios 
        SET intentos_fallidos = 0,
            bloqueado_desde = NULL,
            bloqueado_hasta = NULL
        WHERE id_usuario = p_id_usuario;
        
        SET p_permite_acceso = TRUE;
        SET p_mensaje = 'Acceso concedido';
    ELSE
        -- Falla: gestionar bloqueo
        IF v_bloqueado_hasta IS NULL OR v_bloqueado_hasta <= NOW() THEN
            -- Incrementar intentos fallidos si no está bloqueado
            UPDATE bloqueo_usuarios 
            SET intentos_fallidos = v_intentos + 1
            WHERE id_usuario = p_id_usuario;
            
            -- Verificar si debe bloquearse (3 o más intentos)
            IF v_intentos + 1 >= 3 THEN
                UPDATE bloqueo_usuarios
                SET bloqueado_desde = NOW(),
                    bloqueado_hasta = NOW() + INTERVAL 3 MINUTE
                WHERE id_usuario = p_id_usuario;
                
                SET p_permite_acceso = FALSE;
                SET p_mensaje = 'Cuenta bloqueada por 30 minutos debido a múltiples intentos fallidos';
                
                -- Registrar bloqueo
                INSERT INTO alertas (categoria, mensaje, fecha)
                VALUES ('usuario', CONCAT('Usuario ID ', p_id_usuario, ' bloqueado por 30 minutos.'), NOW());
            ELSE
                SET p_permite_acceso = FALSE;
                SET p_mensaje = CONCAT('Credenciales incorrectas. Intentos restantes: ', 3 - (v_intentos + 1));
            END IF;
        ELSE
            -- Ya está bloqueado
            SET v_minutos_restantes = TIMESTAMPDIFF(MINUTE, NOW(), v_bloqueado_hasta);
            SET p_permite_acceso = FALSE;
            SET p_mensaje = CONCAT('Cuenta temporalmente bloqueada. Tiempo restante: ', v_minutos_restantes, ' minutos');
        END IF;
    END IF;
    
    -- Registrar el intento de acceso en log_accesos
    INSERT INTO log_accesos (id_usuario, exito, direccion_ip)
    VALUES (p_id_usuario, p_exito, NULL); -- Puedes agregar la IP si está disponible
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `EstadoSaludAnimal` (`id_animal` INT) RETURNS VARCHAR(255) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC RETURN (  
    SELECT estado_nuevo 
    FROM historial_estado_salud 
    WHERE id_animal = id_animal  
    LIMIT 1  
)$$

CREATE DEFINER=`root`@`localhost` FUNCTION `mostrar_cantidad_producto` (`p_nombre_producto` VARCHAR(100)) RETURNS VARCHAR(255) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN  
    RETURN (  
        SELECT CONCAT('ID: ', id_producto, ', Nombre: ', nombre_producto, ', Cantidad: ', cantidad)  
        FROM inventario  
        WHERE nombre_producto = p_nombre_producto  
        LIMIT 1
    ); 
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `UltimaVacunaAnimal` (`id_animal` INT) RETURNS DATE DETERMINISTIC RETURN (  
    SELECT fecha_aplicacion  
    FROM vacunacion  
    WHERE id_animal = id_animal  
    ORDER BY fecha_aplicacion DESC  
    LIMIT 1  
)$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas`
--

CREATE TABLE `alertas` (
  `id_alerta` int(11) NOT NULL,
  `categoria` varchar(20) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `leido` tinyint(1) DEFAULT 0,
  `prioridad` enum('baja','media','alta','critica') DEFAULT 'media'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
PARTITION BY LIST COLUMNS(`categoria`)
(
PARTITION p_animal VALUES IN ('animal') ENGINE=InnoDB,
PARTITION p_planta VALUES IN ('planta') ENGINE=InnoDB,
PARTITION p_venta VALUES IN ('venta') ENGINE=InnoDB,
PARTITION p_empleado VALUES IN ('empleado') ENGINE=InnoDB,
PARTITION p_usuario VALUES IN ('usuario') ENGINE=InnoDB,
PARTITION p_inventario VALUES IN ('inventario') ENGINE=InnoDB,
PARTITION p_inicio_sesion VALUES IN ('inicio_sesion') ENGINE=InnoDB,
PARTITION p_salud VALUES IN ('salud') ENGINE=InnoDB,
PARTITION p_vacunas VALUES IN ('vacunas') ENGINE=InnoDB,
PARTITION p_vacunacion VALUES IN ('vacunacion') ENGINE=InnoDB,
PARTITION p_otro VALUES IN ('otro') ENGINE=InnoDB
);

--
-- Volcado de datos para la tabla `alertas`
--

INSERT INTO `alertas` (`id_alerta`, `categoria`, `mensaje`, `fecha`, `leido`, `prioridad`) VALUES
(2, 'animal', 'Se ha eliminado un registro de vacunación para el animal con ID 4 y la vacuna ID 4.', '2025-04-08 02:18:39', 1, 'media'),
(11, 'animal', 'Cambio estado animal 3: Enfermo → Sano', '2025-04-22 03:03:55', 1, 'media'),
(15, 'animal', 'Nueva producción registrada: 10.00 de Huevos. Animal: Gallina Ponedora', '2025-04-22 06:12:42', 1, 'media'),
(41, 'animal', 'Cambio de estado en animal ID 1: Sano → Enfermo. Nombre: Gallina ponedora.', '2025-05-26 23:03:08', 1, 'media'),
(42, 'animal', 'Cambio estado animal 1: Sano → Enfermo', '2025-05-26 23:03:08', 1, 'media'),
(47, 'animal', 'Cambio de estado en animal ID 1: Enfermo → Sano. Nombre: Gallina ponedora.', '2025-05-27 02:27:10', 1, 'media'),
(48, 'animal', 'Cambio estado animal 1: Enfermo → Sano', '2025-05-27 02:27:10', 1, 'media'),
(49, 'animal', 'Cambio de estado en animal ID 2: Sano → Enfermo. Nombre: Gallina de engorde.', '2025-05-27 02:27:25', 1, 'media'),
(50, 'animal', 'Cambio estado animal 2: Sano → Enfermo', '2025-05-27 02:27:25', 1, 'media'),
(57, 'animal', 'Nuevo animal registrado: Gallina Ponedora (ID: 38). Especie: Gallinas. Estado: No especificado.', '2025-05-29 01:49:21', 1, 'media'),
(13, 'planta', 'Nuevo producto en inventario: Abono para plantas. Cantidad: 300.00 Unidades. Proveedor: AgroAlimentos', '2025-04-22 05:59:55', 1, 'media'),
(14, 'planta', 'Nueva planta registrada: Girasol (ID: 21). Ubicación: Campo 8. Estado: Sano.', '2025-04-22 06:06:30', 1, 'media'),
(16, 'planta', 'Nuevo reporte ingresado para Planta con ID 14. Diagnóstico: Infección fúngica detectada', '2025-04-22 06:22:27', 1, 'media'),
(43, 'planta', 'Cambio de estado en planta ID 4: Sano → Enfermo. Nombre: Cacao.', '2025-05-27 00:25:03', 1, 'media'),
(44, 'planta', 'Cambio de estado en planta ID 5: Recuperación → Sano. Nombre: Arroz.', '2025-05-27 00:31:50', 1, 'media'),
(45, 'planta', 'Cambio de estado en planta ID 8: Sano → Enfermo. Nombre: Aguacate.', '2025-05-27 00:48:57', 1, 'media'),
(46, 'planta', 'Nueva planta registrada: Ají (ID: 22). Ubicación: Huerto 2. Estado: Sano.', '2025-05-27 00:58:17', 1, 'media'),
(58, 'planta', 'Nuevo reporte ingresado para Planta con ID 8. Diagnóstico: n', '2025-05-29 01:56:34', 1, 'media'),
(17, 'venta', 'Nueva venta registrada: $500000.00 por 100.00 de Carne. Comprador: Carnicería La Pradera.', '2025-04-22 06:55:51', 1, 'media'),
(21, 'venta', 'Nuevo pedido #1 - Cliente: Carlos Pérez (carlos.perez@email.com) - Productos: Leche entera (7 x $3500.00), Mantequilla (2 x $7000.00) - Total: $38500.00 COP - Método de pago: contraentrega', '2025-05-01 01:04:58', 1, 'media'),
(22, 'venta', 'Nuevo pedido #2 - Cliente: Carlos Pérez (carlos.perez@email.com) - Productos: Leche entera (1 x $3500.00) - Total: $3500.00 COP - Método de pago: tarjeta', '2025-05-01 01:10:10', 1, 'media'),
(23, 'venta', 'Nuevo pedido #3 - Cliente: Carlos Pérez (carlos.perez@email.com) - Productos: Huevos orgánicos (2 x $15000.00), Mantequilla (1 x $7000.00) - Total: $37000.00 COP - Método de pago: contraentrega', '2025-05-01 02:07:23', 1, 'media'),
(27, 'venta', 'Venta registrada: 1 unidades de \"Leche entera\" (ID: 2) por $3500.00. Cliente ID: 5.', '2025-05-01 05:04:26', 1, 'media'),
(29, 'venta', 'Venta registrada: 1 unidades de \"Queso fresco\" (ID: 7) por $12000.00. Cliente ID: 5.', '2025-05-01 05:04:26', 1, 'media'),
(31, 'venta', 'Venta registrada: 8 unidades de \"Yogur natural\" (ID: 13) por $48000.00. Cliente ID: 5.', '2025-05-01 05:04:26', 1, 'media'),
(32, 'venta', 'Nuevo pedido #1 - Cliente: Tatiana (tati@email.com) - Productos: Leche entera (1 x $3500.00), Queso fresco (1 x $12000.00), Yogur natural (8 x $6000.00) - Total: $63500.00 COP - Método de pago: tarjeta', '2025-05-01 05:04:26', 1, 'media'),
(35, 'venta', 'Venta registrada: 5 unidades de \"Huevos de codorniz\" (ID: 8) por $75000.00. Cliente ID: 1.', '2025-05-26 15:23:23', 1, 'media'),
(37, 'venta', 'Venta registrada: 2 unidades de \"Leche entera\" (ID: 2) por $7000.00. Cliente ID: 1.', '2025-05-26 15:23:23', 1, 'media'),
(39, 'venta', 'Venta registrada: 2 unidades de \"Huevos de gallina\" (ID: 1) por $24000.00. Cliente ID: 1.', '2025-05-26 15:23:23', 1, 'media'),
(40, 'venta', 'Nuevo pedido #2 - Cliente: Elsa (elsa@gmail.com) - Productos: Huevos de codorniz (5 x $15000.00), Leche entera (2 x $3500.00), Huevos de gallina (2 x $12000.00) - Total: $106000.00 COP - Método de pago: contraentrega', '2025-05-26 15:23:23', 1, 'media'),
(18, 'empleado', 'Nuevo empleado registrado: Yoisi Palacios. Cargo: Administrador. Salario: $5000000.00.', '2025-04-22 07:02:20', 1, 'media'),
(4, 'usuario', 'Usuario ID 1 bloqueado por 30 minutos.', '2025-04-21 20:33:50', 1, 'media'),
(5, 'usuario', 'Usuario ID 2 bloqueado por 30 minutos.', '2025-04-21 20:36:13', 1, 'media'),
(6, 'usuario', 'Usuario ID 1 bloqueado por 30 minutos.', '2025-04-21 20:49:38', 1, 'media'),
(7, 'usuario', 'Usuario ID 2 bloqueado por 30 minutos.', '2025-04-21 20:55:16', 1, 'media'),
(8, 'usuario', 'Usuario ID 3 bloqueado temporalmente', '2025-04-21 21:10:40', 1, 'media'),
(9, 'usuario', 'Usuario ID 4 bloqueado temporalmente', '2025-04-21 21:20:53', 1, 'media'),
(10, 'usuario', 'Usuario ID 4 bloqueado temporalmente', '2025-04-21 21:22:39', 1, 'media'),
(19, 'usuario', 'Nuevo usuario registrado: Juan Perez. Tipo: Investigador. Correo: juan.perez@gmail.com.', '2025-04-24 03:47:52', 1, 'media'),
(24, 'usuario', 'Nuevo usuario registrado: Yussy - Correo: yussy@gmail.com - Teléfono: 1234567', '2025-05-01 04:56:21', 1, 'media'),
(25, 'usuario', 'Nuevo usuario registrado: Tatiana - Correo: tati@email.com - Teléfono: 1234567', '2025-05-01 05:02:33', 1, 'media'),
(33, 'usuario', 'Nuevo usuario registrado: delascar - Correo: delascar@gmail.com - Teléfono: 12345', '2025-05-15 14:41:18', 1, 'media'),
(59, 'usuario', 'Usuario ID 18 bloqueado manualmente por el administrador.', '2025-05-29 05:45:30', 1, 'media'),
(60, 'usuario', 'Usuario ID 18 desbloqueado manualmente por el administrador.', '2025-05-29 05:45:40', 1, 'media'),
(20, 'inventario', 'Nuevo producto en inventario: Antibióticos para cerdos. Cantidad: 100.00 Dósis. Proveedor: VetSalud', '2025-04-24 05:51:31', 1, 'media'),
(26, 'inventario', 'Actualización de producto \"Leche entera\" (ID: 2). Cambios: Cantidad: 72 → 71. ', '2025-05-01 05:04:26', 1, 'media'),
(28, 'inventario', 'Actualización de producto \"Queso fresco\" (ID: 7). Cambios: Cantidad: 40 → 39. ', '2025-05-01 05:04:26', 1, 'media'),
(30, 'inventario', 'Actualización de producto \"Yogur natural\" (ID: 13). Cambios: Cantidad: 60 → 52. ', '2025-05-01 05:04:26', 1, 'media'),
(34, 'inventario', 'Actualización de producto \"Huevos de codorniz\" (ID: 8). Cambios: Cantidad: 100 → 95. ', '2025-05-26 15:23:23', 1, 'media'),
(36, 'inventario', 'Actualización de producto \"Leche entera\" (ID: 2). Cambios: Cantidad: 71 → 69. ', '2025-05-26 15:23:23', 1, 'media'),
(38, 'inventario', 'Actualización de producto \"Huevos de gallina\" (ID: 1). Cambios: Cantidad: 150 → 148. ', '2025-05-26 15:23:23', 1, 'media'),
(51, 'inventario', 'Producto agotado: \"Carne de res\" (ID: 3) - Categoría: Carne - Precio: $25000.00 COP', '2025-05-29 01:37:18', 1, 'media'),
(52, 'inventario', 'Actualización de producto: \"Carne de res\" (ID: 3) - Disponibilidad cambiada de Disponible a No disponible', '2025-05-29 01:37:18', 1, 'media'),
(53, 'inventario', 'Actualización de producto \"Carne de res\" (ID: 3). Cambios: Cantidad: 50 → 0. Disponibilidad: Disponible → No disponible. ', '2025-05-29 01:37:18', 1, 'media'),
(54, 'inventario', 'Actualización de producto \"Carne de res\" (ID: 3). Cambios: Cantidad: 0 → 9. ', '2025-05-29 01:37:31', 1, 'media'),
(55, 'inventario', 'Actualización de producto: \"Carne de res\" (ID: 3) - Disponibilidad cambiada de No disponible a Disponible', '2025-05-29 01:37:38', 1, 'media'),
(56, 'inventario', 'Actualización de producto \"Carne de res\" (ID: 3). Cambios: Disponibilidad: No disponible → Disponible. ', '2025-05-29 01:37:38', 1, 'media'),
(61, 'inventario', 'Actualización de producto \"Carne de res\" (ID: 3). Cambios: Cantidad: 9 → 45. ', '2025-05-29 05:55:00', 0, 'media'),
(1, 'salud', 'El estado de salud ha cambiado para el ID 14 de Sano a Enfermo.', '2025-03-18 04:58:41', 1, 'media'),
(12, 'salud', 'El estado de salud ha cambiado para el ID 3 de Sano a Enfermo.', '2025-04-22 05:55:03', 1, 'media');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alimentacion`
--

CREATE TABLE `alimentacion` (
  `id_alimentacion` int(11) NOT NULL,
  `id_especie` int(11) NOT NULL,
  `tipo_alimento` varchar(100) NOT NULL,
  `comidas_por_dia` int(11) NOT NULL,
  `cantidad_gramos` decimal(10,2) NOT NULL,
  `fecha_ultima_alimentacion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alimentacion`
--

INSERT INTO `alimentacion` (`id_alimentacion`, `id_especie`, `tipo_alimento`, `comidas_por_dia`, `cantidad_gramos`, `fecha_ultima_alimentacion`) VALUES
(1, 1, 'Maíz', 2, 50.00, '2024-02-25'),
(2, 2, 'Trigo', 2, 35.00, '2024-02-27'),
(3, 2, 'Pasto', 3, 100.00, '2024-02-25'),
(4, 2, 'Heno', 3, 80.00, '2024-02-26'),
(5, 3, 'Comida para peces', 2, 30.00, '2024-02-25'),
(6, 3, 'Comida para peces', 2, 25.00, '2024-02-26'),
(7, 4, 'Maíz molido', 3, 60.00, '2024-02-25'),
(8, 4, 'Salvado', 3, 50.00, '2024-02-26'),
(9, 1, 'Trigo', 2, 45.00, '2024-02-27'),
(10, 2, 'Pasto', 3, 90.00, '2024-02-27'),
(11, 3, 'Algas', 2, 20.00, '2024-02-27'),
(12, 4, 'Harina de soya', 3, 70.00, '2024-02-27'),
(13, 1, 'Trigo', 3, 20.00, '2025-04-21');

--
-- Disparadores `alimentacion`
--
DELIMITER $$
CREATE TRIGGER `alerta_cambio_alimentacion` AFTER INSERT ON `alimentacion` FOR EACH ROW BEGIN
    DECLARE especie_nombre VARCHAR(50);
    
    SELECT nombre_especie INTO especie_nombre 
    FROM especies 
    WHERE id_especie = NEW.id_especie;
    
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'animal',
        CONCAT(
            'Nuevo registro de alimentación para ', especie_nombre, 
            '. Alimento: ', NEW.tipo_alimento, 
            '. Cantidad: ', NEW.cantidad_gramos, 'g por comida.'
        ),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `animales`
--

CREATE TABLE `animales` (
  `id_animal` int(11) NOT NULL,
  `id_especie` int(11) NOT NULL,
  `nombre_cientifico` varchar(100) NOT NULL,
  `nombre_comun` varchar(100) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `estado` enum('Sano','Enfermo','Recuperación') DEFAULT 'Sano',
  `descripcion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `animales`
--

INSERT INTO `animales` (`id_animal`, `id_especie`, `nombre_cientifico`, `nombre_comun`, `edad`, `ubicacion`, `estado`, `descripcion`, `fecha_registro`) VALUES
(1, 1, 'Gallus gallus domesticus', 'Gallina ponedora', 2, 'Galpón 1', 'Sano', 'Gallina en producción de huevos', '2025-03-09 00:13:54'),
(2, 1, 'Gallus gallus domesticus', 'Gallina de engorde', 1, 'Galpón 2', 'Enfermo', 'Gallina en fase de crecimiento', '2025-03-09 00:13:54'),
(3, 1, 'Gallus gallus domesticus', 'Gallina Ponedora', 4, 'Galpon', 'Sano', 'Gallina en producción de huevos', '2025-03-09 00:13:54'),
(5, 1, 'Gallus gallus domesticus', 'Gallina ponedora', 2, 'Galpón 3', 'Recuperación', 'En tratamiento por infección leve', '2025-03-09 00:13:54'),
(6, 2, 'Bos taurus', 'Vaca lechera', 5, 'Establo 1', 'Sano', 'Producción de leche diaria', '2025-03-09 00:13:54'),
(7, 2, 'Bos taurus', 'Toro semental', 4, 'Establo 2', 'Sano', 'Toro reproductor de la granja', '2025-03-09 00:13:54'),
(8, 2, 'Bos taurus', 'Vaca de engorde', 3, 'Establo 1', 'Sano', 'Engorde para carne', '2025-03-09 00:13:54'),
(9, 2, 'Bos taurus', 'Toro semental', 6, 'Establo 3', 'Enfermo', 'Dificultad para caminar, en revisión veterinaria', '2025-03-09 00:13:54'),
(10, 2, 'Bos taurus', 'Vaca lechera', 7, 'Establo 2', 'Recuperación', 'Tratamiento por mastitis', '2025-03-09 00:13:54'),
(11, 3, 'Oreochromis niloticus', 'Tilapia', 1, 'Estanque 1', 'Sano', 'Crecimiento en agua dulce', '2025-03-09 00:13:54'),
(12, 3, 'Oreochromis niloticus', 'Tilapia', 2, 'Estanque 1', 'Sano', 'Lista para cosecha', '2025-03-09 00:13:54'),
(13, 3, 'Pangasius hypophthalmus', 'Bagre', 3, 'Estanque 2', 'Sano', 'Buen desarrollo en estanque artificial', '2025-03-09 00:13:54'),
(14, 3, 'Pangasius hypophthalmus', 'Bagre', 2, 'Estanque 2', 'Sano', 'En fase de crecimiento', '2025-03-09 00:13:54'),
(15, 3, 'Oreochromis niloticus', 'Tilapia', 1, 'Estanque 3', 'Enfermo', 'Síntomas de infección en las branquias', '2025-03-09 00:13:54'),
(16, 4, 'Sus scrofa domesticus', 'Cerdo de engorde', 4, 'Corral 1', 'Sano', 'Engorde para comercialización', '2025-03-09 00:13:54'),
(17, 4, 'Sus scrofa domesticus', 'Cerda reproductora', 3, 'Corral 2', 'Sano', 'Preñada, próxima a parto', '2025-03-09 00:13:54'),
(18, 4, 'Sus scrofa domesticus', 'Cerdo de engorde', 2, 'Corral 1', 'Enfermo', 'Pérdida de apetito y fiebre', '2025-03-09 00:13:54'),
(19, 4, 'Sus scrofa domesticus', 'Cerdo de engorde', 3, 'Corral 3', 'Sano', 'Crecimiento adecuado', '2025-03-09 00:13:54'),
(20, 4, 'Sus scrofa domesticus', 'Cerda reproductora', 5, 'Corral 2', 'Recuperación', 'En tratamiento por infección respiratoria', '2025-03-09 00:13:54'),
(21, 1, 'Gallus gallus domesticus', 'Gallina de engorde', 1, 'Galpón 3', 'Sano', 'En fase de crecimiento inicial', '2025-03-09 00:15:00'),
(22, 1, 'Gallus gallus domesticus', 'Gallina ponedora', 2, 'Galpón 2', 'Enfermo', 'Síntomas de debilidad y baja producción de huevos', '2025-03-09 00:15:00'),
(23, 2, 'Bos taurus', 'Toro semental', 5, 'Establo 3', 'Sano', 'Mantenimiento genético del hato', '2025-03-09 00:15:00'),
(24, 2, 'Bos taurus', 'Vaca lechera', 6, 'Establo 1', 'Recuperación', 'Tratamiento post-parto para mejorar la producción de leche', '2025-03-09 00:15:00'),
(25, 3, 'Oreochromis niloticus', 'Tilapia', 2, 'Estanque 2', 'Sano', 'Crecimiento adecuado para mercado', '2025-03-09 00:15:00'),
(26, 3, 'Pangasius hypophthalmus', 'Bagre', 3, 'Estanque 3', 'Recuperación', 'Se aplicó tratamiento por parásitos en la piel', '2025-03-09 00:15:00'),
(27, 4, 'Sus scrofa domesticus', 'Cerdo de engorde', 4, 'Corral 3', 'Sano', 'Listo para sacrificio', '2025-03-09 00:15:00'),
(28, 4, 'Sus scrofa domesticus', 'Cerda reproductora', 3, 'Corral 1', 'Sano', 'En gestación, revisiones veterinarias constantes', '2025-03-09 00:15:00'),
(29, 4, 'Sus scrofa domesticus', 'Cerdo de engorde', 2, 'Corral 2', 'Enfermo', 'Pérdida de peso y falta de apetito, bajo observación', '2025-03-09 00:15:00'),
(30, 4, 'Sus scrofa domesticus', 'Cerda reproductora', 5, 'Corral 2', 'Recuperación', 'Recuperándose de una infección leve', '2025-03-09 00:15:00'),
(38, 1, 'Gallus gallus domesticus', 'Gallina Ponedora', 2, 'Galpón 1', NULL, 'Gallina en producción de huevos', '2025-05-29 01:49:21');

--
-- Disparadores `animales`
--
DELIMITER $$
CREATE TRIGGER `alerta_cambio_estado_animal` AFTER UPDATE ON `animales` FOR EACH ROW BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'animal',
            CONCAT('Cambio de estado en animal ID ', NEW.id_animal, ': ', OLD.estado, ' → ', NEW.estado, 
                   '. Nombre: ', NEW.nombre_comun, '.'),
            NOW()
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `alerta_nuevo_animal` AFTER INSERT ON `animales` FOR EACH ROW BEGIN
    DECLARE especie_nombre VARCHAR(50);
    
    SELECT nombre_especie INTO especie_nombre 
    FROM especies 
    WHERE id_especie = NEW.id_especie;
    
    -- Asegurarse de que todos los campos requeridos tengan valores
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'animal',
        CONCAT('Nuevo animal registrado: ', 
               IFNULL(NEW.nombre_comun, 'Sin nombre'), 
               ' (ID: ', NEW.id_animal, '). Especie: ', 
               IFNULL(especie_nombre, 'Desconocida'), 
               '. Estado: ', IFNULL(NEW.estado, 'No especificado'), '.'),
        NOW()
    );
    
    -- Insertar en auditorías si existe la tabla
    IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'auditorias') > 0 THEN
        INSERT INTO auditorias (usuario, direccion_ip, tabla_afectada, accion, id_registro, detalles)
        VALUES (
            CURRENT_USER(),
            (SELECT SUBSTRING_INDEX(USER(), '@', -1)),
            'animales',
            'INSERT',
            NEW.id_animal,
            CONCAT('Nuevo animal: ', IFNULL(NEW.nombre_comun, 'Sin nombre'), ' | Especie: ', IFNULL(especie_nombre, 'Desconocida'), ' | Estado: ', IFNULL(NEW.estado, 'No especificado'))
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_animales` AFTER UPDATE ON `animales` FOR EACH ROW BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'animal',
            CONCAT('Cambio estado animal ', NEW.id_animal, ': ', OLD.estado, ' → ', NEW.estado),
            NOW()
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditorias`
--

CREATE TABLE `auditorias` (
  `id_auditoria` int(11) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario` varchar(100) DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `tabla_afectada` varchar(50) NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `id_registro` int(11) DEFAULT NULL,
  `detalles` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditorias`
--

INSERT INTO `auditorias` (`id_auditoria`, `fecha_hora`, `usuario`, `direccion_ip`, `tabla_afectada`, `accion`, `id_registro`, `detalles`) VALUES
(1, '2025-05-29 01:37:18', 'root@localhost', 'localhost', 'inventario_productos', 'UPDATE', 3, 'Actualización de producto \"Carne de res\". Cambios: Cantidad: 50 → 0. Disponibilidad: Disponible → No disponible. '),
(2, '2025-05-29 01:37:18', 'root@localhost', NULL, 'inventario_productos', 'UPDATE', 3, 'Producto actualizado: Carne de res -> Carne de res | Precio: $25000.00 -> $25000.00 | Cantidad: 50 -> 0 | Disponible: Sí -> No'),
(3, '2025-05-29 01:37:31', 'root@localhost', 'localhost', 'inventario_productos', 'UPDATE', 3, 'Actualización de producto \"Carne de res\". Cambios: Cantidad: 0 → 9. '),
(4, '2025-05-29 01:37:31', 'root@localhost', NULL, 'inventario_productos', 'UPDATE', 3, 'Producto actualizado: Carne de res -> Carne de res | Precio: $25000.00 -> $25000.00 | Cantidad: 0 -> 9 | Disponible: No -> No'),
(5, '2025-05-29 01:37:38', 'root@localhost', 'localhost', 'inventario_productos', 'UPDATE', 3, 'Actualización de producto \"Carne de res\". Cambios: Disponibilidad: No disponible → Disponible. '),
(6, '2025-05-29 01:37:38', 'root@localhost', NULL, 'inventario_productos', 'UPDATE', 3, 'Producto actualizado: Carne de res -> Carne de res | Precio: $25000.00 -> $25000.00 | Cantidad: 9 -> 9 | Disponible: No -> Sí'),
(7, '2025-05-29 01:49:21', 'root@localhost', 'localhost', 'animales', 'INSERT', 38, 'Nuevo animal: Gallina Ponedora | Especie: Gallinas | Estado: No especificado'),
(8, '2025-05-29 05:55:00', 'root@localhost', 'localhost', 'inventario_productos', 'UPDATE', 3, 'Actualización de producto \"Carne de res\". Cambios: Cantidad: 9 → 45. '),
(9, '2025-05-29 05:55:00', 'root@localhost', NULL, 'inventario_productos', 'UPDATE', 3, 'Producto actualizado: Carne de res -> Carne de res | Precio: $25000.00 -> $25000.00 | Cantidad: 9 -> 45 | Disponible: Sí -> Sí');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bloqueo_usuarios`
--

CREATE TABLE `bloqueo_usuarios` (
  `id_bloqueo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_desde` datetime DEFAULT NULL,
  `bloqueado_hasta` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bloqueo_usuarios`
--

INSERT INTO `bloqueo_usuarios` (`id_bloqueo`, `id_usuario`, `intentos_fallidos`, `bloqueado_desde`, `bloqueado_hasta`) VALUES
(1, 4, 0, NULL, NULL),
(4, 1, 0, NULL, NULL),
(7, 5, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito_compras`
--

CREATE TABLE `carrito_compras` (
  `id_item` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito_compras`
--

INSERT INTO `carrito_compras` (`id_item`, `id_cliente`, `id_producto`, `cantidad`, `fecha_agregado`) VALUES
(6, 4, 2, 1, '2025-05-01 04:59:49'),
(7, 4, 19, 1, '2025-05-01 04:59:55'),
(14, 1, 2, 1, '2025-05-26 20:42:15'),
(15, 1, 3, 1, '2025-05-29 07:03:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre`, `email`, `telefono`, `direccion`, `password`, `fecha_registro`) VALUES
(1, 'Elsa', 'elsa@gmail.com', '1234', 'La Gloria', '$2y$10$CNbVbheq5HD4Z0vPWDKEhOsAp/c.yNoWu1SD4U7PVL3hdF83DmA3a', '2025-04-30 23:59:11'),
(4, 'Yussy', 'yussy@gmail.com', '1234567', 'Calle 123', '$2y$10$4806hyMaUHscQuyIGWSJuuuO8l35fgGzm4.URbak/z0JU8QDzhr/e', '2025-05-01 04:56:21'),
(5, 'Tatiana', 'tati@email.com', '1234567', 'Centro', '$2y$10$gXfzjlzKedYrA8kXBDh3reuBLiye2VyZBmX.tO.ut4Bv89rED6uBe', '2025-05-01 05:02:33'),
(6, 'delascar', 'delascar@gmail.com', '12345', 'Calle 123', '$2y$10$TYYPKfvuUxi57K5LizuHluXIzOCcPoNSEkQn9uWH1PObDXVePLluy', '2025-05-15 14:41:18');

--
-- Disparadores `clientes`
--
DELIMITER $$
CREATE TRIGGER `alerta_nuevo_cliente` AFTER INSERT ON `clientes` FOR EACH ROW BEGIN
    -- Solo incluir información básica del cliente
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'usuario', 
        CONCAT(
            'Nuevo usuario registrado: ', NEW.nombre,
            ' - Correo: ', NEW.email,
            IFNULL(CONCAT(' - Teléfono: ', NEW.telefono), '')
        ), 
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costos`
--

CREATE TABLE `costos` (
  `id_costo` int(11) NOT NULL,
  `tipo_costo` enum('Alimentación','Salud','Mantenimiento','Salarios','Otro') NOT NULL,
  `descripcion` text NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `id_animal` int(11) DEFAULT NULL,
  `id_especie` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `costos`
--

INSERT INTO `costos` (`id_costo`, `tipo_costo`, `descripcion`, `monto`, `fecha`, `id_empleado`, `id_animal`, `id_especie`) VALUES
(1, 'Alimentación', 'Compra de maíz para pollos', 50000.00, '2024-02-01', NULL, NULL, 1),
(2, 'Salud', 'Consulta veterinaria para ganado', 200000.00, '2024-02-05', 1, NULL, 2),
(3, 'Mantenimiento', 'Reparación de cercas', 3500000.00, '2024-02-10', NULL, NULL, NULL),
(4, 'Salarios', 'Pago mensual a empleados', 10800000.00, '2024-02-15', NULL, NULL, NULL),
(5, 'Otro', 'Compra de herramientas de trabajo', 600000.00, '2024-02-20', NULL, NULL, NULL),
(6, 'Alimentación', 'Compra de balanceado para gallinas', 1200000.00, '2024-02-15', NULL, NULL, 1),
(7, 'Salud', 'Desparasitación de bovinos', 800000.00, '2024-02-18', 1, NULL, 2),
(8, 'Mantenimiento', 'Reparación de silo de almacenamiento', 950000.00, '2024-02-20', NULL, NULL, NULL),
(9, 'Salarios', 'Pago de nómina', 42000000.00, '2024-02-25', NULL, NULL, NULL),
(10, 'Otro', 'Compra de insumos veterinarios', 650000.00, '2024-02-28', NULL, NULL, NULL),
(11, 'Alimentación', 'Compra de pasto para ganado', 1800000.00, '2024-03-01', NULL, NULL, 2),
(12, 'Salud', 'Atención médica a cerdos', 500000.00, '2024-03-05', 1, NULL, 3),
(13, 'Mantenimiento', 'Pintura y reparación de corrales', 750000.00, '2024-03-07', NULL, NULL, NULL),
(14, 'Salarios', 'Pago de horas extra', 3200000.00, '2024-03-10', NULL, NULL, NULL),
(15, 'Otro', 'Compra de herramientas agrícolas', 980000.00, '2024-03-12', NULL, NULL, NULL),
(16, 'Alimentación', 'Compra de suplemento mineral para vacas', 1300000.00, '2024-03-14', NULL, NULL, 2),
(17, 'Salud', 'Chequeo veterinario rutinario', 420000.00, '2024-03-17', 1, NULL, 2),
(18, 'Mantenimiento', 'Cambio de bebederos automáticos', 870000.00, '2024-03-20', NULL, NULL, NULL),
(19, 'Salarios', 'Bonificación por productividad', 4500000.00, '2024-03-22', NULL, NULL, NULL),
(20, 'Otro', 'Compra de postes para cercado', 1200000.00, '2024-03-25', NULL, NULL, NULL),
(21, 'Alimentación', 'Compra de balanceado para gallinas', 1200000.00, '2024-02-15', NULL, NULL, 1),
(22, 'Salud', 'Desparasitación de bovinos', 800000.00, '2024-02-18', 1, NULL, 2),
(23, 'Mantenimiento', 'Reparación de silo de almacenamiento', 950000.00, '2024-02-20', NULL, NULL, NULL),
(24, 'Salarios', 'Pago de nómina', 42000000.00, '2024-02-25', NULL, NULL, NULL),
(25, 'Otro', 'Compra de insumos veterinarios', 650000.00, '2024-02-28', NULL, NULL, NULL),
(26, 'Alimentación', 'Compra de pasto para ganado', 1800000.00, '2024-03-01', NULL, NULL, 2),
(27, 'Salud', 'Atención médica a cerdos', 500000.00, '2024-03-05', 1, NULL, 3),
(28, 'Mantenimiento', 'Pintura y reparación de corrales', 750000.00, '2024-03-07', NULL, NULL, NULL),
(29, 'Salarios', 'Pago de horas extra', 3200000.00, '2024-03-10', NULL, NULL, NULL),
(30, 'Otro', 'Compra de herramientas agrícolas', 980000.00, '2024-03-12', NULL, NULL, NULL),
(31, 'Alimentación', 'Compra de suplemento mineral para vacas', 1300000.00, '2024-03-14', NULL, NULL, 2),
(32, 'Salud', 'Chequeo veterinario rutinario', 420000.00, '2024-03-17', 1, NULL, 2),
(33, 'Mantenimiento', 'Cambio de bebederos automáticos', 870000.00, '2024-03-20', NULL, NULL, NULL),
(34, 'Salarios', 'Bonificación por productividad', 4500000.00, '2024-03-22', NULL, NULL, NULL),
(35, 'Otro', 'Compra de postes para cercado', 1200000.00, '2024-03-25', NULL, NULL, NULL),
(36, 'Mantenimiento', 'Reparación de sistema de riego', 150000.00, '2025-03-01', NULL, NULL, NULL),
(37, 'Salud', 'Vacunación de ganado bovino', 120000.00, '2025-03-02', NULL, 3, NULL),
(38, 'Alimentación', 'Compra de alimento para aves', 180000.00, '2025-03-03', NULL, 1, NULL),
(39, 'Mantenimiento', 'Compra de fertilizantes para el café', 75000.00, '2025-03-04', NULL, NULL, 1),
(40, 'Salarios', 'Pago a veterinario especialista', 2000000.00, '2025-03-05', 2, NULL, NULL),
(41, 'Otro', 'Implementación de sistema de control de plagas', 95000.00, '2025-03-06', NULL, NULL, NULL),
(42, 'Salud', 'Tratamiento para infección en cerdos', 130000.00, '2025-03-07', NULL, 5, NULL),
(43, 'Mantenimiento', 'Compra de herramientas de poda', 65000.00, '2025-03-08', NULL, NULL, NULL),
(45, 'Salarios', 'Pago a ingeniero agrónomo', 1800000.00, '2025-03-10', 3, NULL, NULL),
(48, 'Salarios', 'Pago de salario a empleados', 40000000.00, '2025-04-21', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pedido`
--

CREATE TABLE `detalles_pedido` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_pedido`
--

INSERT INTO `detalles_pedido` (`id_detalle`, `id_pedido`, `id_producto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 2, 7, 3500.00),
(2, 1, 19, 2, 7000.00),
(4, 2, 2, 1, 3500.00),
(5, 3, 14, 2, 15000.00),
(6, 3, 19, 1, 7000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id_empleado` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rol` varchar(50) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_contratacion` date NOT NULL,
  `salario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`id_empleado`, `nombre`, `rol`, `telefono`, `fecha_contratacion`, `salario`) VALUES
(1, 'Carlos Pérez', 'Veterinario', '3112233445', '2024-01-15', 3500000.00),
(2, 'María López', 'Investigador', '3123344556', '2023-05-20', 4200000.00),
(3, 'José Ramírez', 'Botánico', '3134455667', '2022-09-10', 3900000.00),
(4, 'Laura Gómez', 'Administrador', '3145566778', '2021-12-01', 5000000.00),
(5, 'Fernando Ríos', 'Veterinario', '3156677889', '2024-02-10', 3600000.00),
(6, 'Sofía Medina', 'Investigador', '3167788990', '2023-06-05', 4300000.00),
(7, 'Andrés Castro', 'Botánico', '3178899001', '2022-10-15', 4000000.00),
(8, 'Patricia Suárez', 'Administrador', '3189900112', '2021-11-20', 5100000.00),
(9, 'Ricardo Torres', 'Veterinario', '3190011223', '2024-03-01', 3450000.00),
(10, 'Elena Vargas', 'Investigador', '3201122334', '2023-07-25', 4150000.00),
(11, 'Juan Herrera', 'Encargado de Galpones', '3101122334', '2022-08-15', 2800000.00),
(12, 'Marcos Beltrán', 'Operario de Ganadería', '3112233445', '2023-02-10', 2500000.00),
(13, 'Ana Castaño', 'Operaria de Estanques', '3123344556', '2024-01-05', 2400000.00),
(14, 'Luis Rojas', 'Mecánico de Maquinaria', '3134455667', '2021-06-20', 3200000.00),
(15, 'Carmen Villalobos', 'Encargada de Invernaderos', '3145566778', '2023-04-15', 2900000.00),
(16, 'Diego Montoya', 'Veterinario', '3156677889', '2022-09-12', 3700000.00),
(17, 'Santiago Pérez', 'Botánico', '3167788990', '2023-07-08', 3950000.00),
(18, 'Paula Sánchez', 'Administradora de Recursos', '3178899001', '2021-11-30', 4800000.00),
(19, 'Manuel López', 'Operario de Cultivos', '3189900112', '2023-03-22', 2550000.00),
(20, 'Esteban Castillo', 'Técnico en Irrigación', '3190011223', '2022-10-18', 2750000.00),
(21, 'Lucía Ramírez', 'Encargada de Control de Plagas', '3201122334', '2023-05-05', 3100000.00),
(22, 'Roberto Medina', 'Operario de Alimentación Animal', '3212233445', '2024-02-14', 2650000.00),
(23, 'Fernanda Torres', 'Supervisora de Calidad', '3223344556', '2021-08-25', 4500000.00),
(24, 'Hugo Vargas', 'Encargado de Bodegas', '3234455667', '2022-12-10', 2800000.00),
(25, 'Andrea Gómez', 'Contadora', '3245566778', '2021-07-01', 5200000.00),
(26, 'Ramiro Suárez', 'Operario de Silo', '3256677889', '2023-09-17', 2700000.00),
(27, 'Gabriela Pérez', 'Ingeniera Agrónoma', '3267788990', '2022-04-10', 4800000.00),
(28, 'Leonardo Ríos', 'Técnico en Reproducción Animal', '3278899001', '2023-06-05', 3500000.00),
(29, 'Isabel Fernández', 'Encargada de Biotecnología', '3289900112', '2021-09-30', 4950000.00),
(30, 'Mario Ortiz', 'Encargado de Seguridad', '3290011223', '2022-11-12', 2600000.00),
(31, 'Carlos Mendoza', 'Encargado de Galpones', '3301122334', '2023-01-10', 2800000.00),
(32, 'Raúl Castro', 'Encargado de Galpones', '3312233445', '2022-07-22', 2800000.00),
(33, 'Diego Vargas', 'Operario de Ganadería', '3323344556', '2023-05-19', 2500000.00),
(34, 'Javier Camacho', 'Operario de Ganadería', '3334455667', '2024-01-12', 2500000.00),
(35, 'Camila López', 'Operaria de Estanques', '3345566778', '2023-09-15', 2400000.00),
(36, 'Héctor Morales', 'Operario de Estanques', '3356677889', '2022-11-30', 2400000.00),
(37, 'Pedro Salinas', 'Mecánico de Maquinaria', '3367788990', '2021-05-28', 3200000.00),
(38, 'Rodolfo Guzmán', 'Mecánico de Maquinaria', '3378899001', '2023-06-18', 3200000.00),
(39, 'Diana Herrera', 'Encargada de Invernaderos', '3389900112', '2022-08-05', 2900000.00),
(40, 'Natalia Quintero', 'Encargada de Invernaderos', '3390011223', '2023-03-21', 2900000.00),
(41, 'Esteban Peña', 'Veterinario', '3401122334', '2022-10-10', 3700000.00),
(42, 'Gloria Fernández', 'Veterinaria', '3412233445', '2023-02-17', 3700000.00),
(43, 'Ángela Rojas', 'Botánica', '3423344556', '2022-12-01', 3950000.00),
(44, 'Martín Suárez', 'Botánico', '3434455667', '2023-08-10', 3950000.00),
(45, 'Mónica Estrada', 'Administradora de Recursos', '3445566778', '2021-06-25', 4800000.00),
(46, 'Ricardo Correa', 'Administrador de Recursos', '3456677889', '2023-04-12', 4800000.00),
(47, 'Sergio Tovar', 'Operario de Cultivos', '3467788990', '2023-07-15', 2550000.00),
(48, 'Lorena Pineda', 'Operaria de Cultivos', '3478899001', '2022-05-20', 2550000.00),
(49, 'Humberto Giraldo', 'Técnico en Irrigación', '3489900112', '2023-06-05', 2750000.00),
(50, 'Gabriel León', 'Técnico en Irrigación', '3490011223', '2022-10-15', 2750000.00),
(51, 'Silvia Álvarez', 'Encargada de Control de Plagas', '3501122334', '2023-09-05', 3100000.00),
(52, 'Andrea Velásquez', 'Encargada de Control de Plagas', '3512233445', '2022-11-10', 3100000.00),
(53, 'David Espinoza', 'Operario de Alimentación Animal', '3523344556', '2023-03-22', 2650000.00),
(54, 'Paula Contreras', 'Operaria de Alimentación Animal', '3534455667', '2022-12-18', 2650000.00),
(55, 'Beatriz Ocampo', 'Supervisora de Calidad', '3545566778', '2021-07-10', 4500000.00),
(56, 'Emiliano Gutiérrez', 'Supervisor de Calidad', '3556677889', '2023-02-05', 4500000.00),
(57, 'Francisco Méndez', 'Encargado de Bodegas', '3567788990', '2022-05-15', 2800000.00),
(58, 'Cristina Becerra', 'Encargada de Bodegas', '3578899001', '2023-08-20', 2800000.00),
(59, 'Felipe Ortega', 'Contador', '3589900112', '2021-04-01', 5200000.00),
(60, 'Liliana Vázquez', 'Contadora', '3590011223', '2023-09-25', 5200000.00),
(61, 'Oscar Delgado', 'Operario de Silo', '3601122334', '2023-06-08', 2700000.00),
(62, 'Roberto Galiano', 'Operario de Silo', '3612233445', '2022-11-02', 2700000.00),
(63, 'Hugo Zambrano', 'Ingeniero Agrónomo', '3623344556', '2022-09-15', 4800000.00),
(64, 'María Enciso', 'Ingeniera Agrónoma', '3634455667', '2023-07-30', 4800000.00),
(65, 'Eduardo Montalvo', 'Técnico en Reproducción Animal', '3645566778', '2022-12-10', 3500000.00),
(66, 'Jessica Palacios', 'Técnica en Reproducción Animal', '3656677889', '2023-05-19', 3500000.00),
(67, 'Ismael Castillo', 'Encargado de Biotecnología', '3667788990', '2021-08-21', 4950000.00),
(68, 'Viviana Gómez', 'Encargada de Biotecnología', '3678899001', '2023-06-11', 4950000.00),
(69, 'Tomás Herrera', 'Encargado de Seguridad', '3689900112', '2022-11-05', 2600000.00),
(70, 'Rafael Peña', 'Encargado de Seguridad', '3690011223', '2023-08-08', 2600000.00),
(71, 'Yoisi Palacios', 'Administrador', '3234799112', '2025-04-21', 5000000.00);

--
-- Disparadores `empleados`
--
DELIMITER $$
CREATE TRIGGER `alerta_nuevo_empleado` AFTER INSERT ON `empleados` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'empleado',
        CONCAT('Nuevo empleado registrado: ', NEW.nombre, '. Cargo: ', NEW.rol, 
               '. Salario: $', NEW.salario, '.'),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especies`
--

CREATE TABLE `especies` (
  `id_especie` int(11) NOT NULL,
  `nombre_especie` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especies`
--

INSERT INTO `especies` (`id_especie`, `nombre_especie`, `descripcion`) VALUES
(1, 'Gallinas', 'Aves de corral para producción de huevos y carne'),
(2, 'Ganado', 'Bovinos criados para producción de carne y leche'),
(3, 'Peces', 'Peces de criadero para consumo'),
(4, 'Cerdos', 'Cerdos para producción de carne');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estado_salud`
--

CREATE TABLE `historial_estado_salud` (
  `id_historial` int(11) NOT NULL,
  `id_animal` int(11) DEFAULT NULL,
  `id_planta` int(11) DEFAULT NULL,
  `estado_anterior` enum('Sano','Enfermo','Recuperación') NOT NULL,
  `estado_nuevo` enum('Sano','Enfermo','Recuperación') NOT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_estado_salud`
--

INSERT INTO `historial_estado_salud` (`id_historial`, `id_animal`, `id_planta`, `estado_anterior`, `estado_nuevo`, `fecha_cambio`) VALUES
(1, 3, NULL, 'Enfermo', 'Sano', '2025-02-01 05:00:00'),
(2, 7, NULL, 'Sano', 'Enfermo', '2025-02-05 05:00:00'),
(3, 15, NULL, 'Sano', 'Enfermo', '2025-02-10 05:00:00'),
(4, 12, NULL, 'Recuperación', 'Sano', '2025-02-15 05:00:00'),
(5, NULL, 2, 'Enfermo', 'Sano', '2025-02-20 05:00:00'),
(8, NULL, 14, 'Enfermo', 'Recuperación', '2025-03-18 04:58:41'),
(10, 3, NULL, 'Sano', 'Enfermo', '2025-04-22 05:55:03');

--
-- Disparadores `historial_estado_salud`
--
DELIMITER $$
CREATE TRIGGER `alerta_cambio_estado_salud` AFTER INSERT ON `historial_estado_salud` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'salud',
        CONCAT('El estado de salud ha cambiado para el ID ', 
               CASE 
                   WHEN NEW.id_animal IS NOT NULL THEN NEW.id_animal
                   ELSE NEW.id_planta 
               END,
               ' de ', NEW.estado_anterior, ' a ', NEW.estado_nuevo, '.'),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `alerta_eliminacion_historial_salud` AFTER DELETE ON `historial_estado_salud` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'salud',
        CONCAT('Se ha eliminado un historial de estado de salud para el ID ', COALESCE(OLD.id_animal, OLD.id_planta), '.'),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id_producto` int(11) NOT NULL,
  `nombre_producto` varchar(100) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `unidad_medida` varchar(20) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `id_proveedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id_producto`, `nombre_producto`, `cantidad`, `unidad_medida`, `fecha_ingreso`, `id_proveedor`) VALUES
(1, 'Maíz', 200.00, 'kg', '2024-02-25', 1),
(2, 'Heno', 200.00, 'kg', '2025-04-21', 2),
(23, 'Concentrado para ganado', 300.00, 'kg', '2024-02-27', 1),
(24, 'Sal mineralizada', 100.00, 'kg', '2024-02-28', 3),
(25, 'Vitaminas para aves', 50.00, 'unidades', '2024-03-01', 3),
(26, 'Vacuna contra fiebre aftosa', 20.00, 'dosis', '2024-03-02', 4),
(27, 'Antibiótico para cerdos', 30.00, 'dosis', '2024-03-03', 4),
(28, 'Pala de trabajo', 10.00, 'unidades', '2024-03-04', 5),
(29, 'Bebederos para aves', 15.00, 'unidades', '2024-03-05', 5),
(30, 'Comederos para cerdos', 20.00, 'unidades', '2024-03-06', 5),
(31, 'Electrolitos para bovinos', 40.00, 'litros', '2024-03-07', 3),
(32, 'Sacos de arena', 25.00, 'unidades', '2024-03-08', 6),
(33, 'Herramientas de poda', 12.00, 'unidades', '2024-03-09', 6),
(34, 'Antiparasitario oral', 35.00, 'dosis', '2024-03-10', 4),
(35, 'Fardo de alfalfa', 120.00, 'kg', '2024-03-11', 2),
(36, 'Concentrado para pollos', 200.00, 'kg', '2024-03-12', 1),
(37, 'Malla para cercado', 30.00, 'rollos', '2024-03-13', 6),
(38, 'Desinfectante veterinario', 50.00, 'litros', '2024-03-14', 4),
(39, 'Guantes de trabajo', 100.00, 'pares', '2024-03-15', 5),
(40, 'Bolsas para ensilaje', 60.00, 'unidades', '2024-03-16', 2),
(41, 'Casetas para gallinas', 5.00, 'unidades', '2024-03-17', 6),
(42, 'Lamparas de calor para criadero', 8.00, 'unidades', '2024-03-18', 5),
(43, 'Fertilizante NPK', 50.00, 'kg', '2025-03-01', 1),
(44, 'Antibióticos para ganado', 20.00, 'frascos', '2025-03-02', 2),
(45, 'Semillas de maíz', 100.00, 'kg', '2025-03-03', 3),
(46, 'Herramientas de poda', 10.00, 'unidades', '2025-03-04', 4),
(47, 'Alimento balanceado para cerdos', 200.00, 'kg', '2025-03-05', 5),
(48, 'Plántulas de café', 75.00, 'unidades', '2025-03-06', 3),
(49, 'Insecticida biológico', 15.00, 'litros', '2025-03-07', 1),
(50, 'Riego automatizado', 2.00, 'sistemas', '2025-03-09', 4),
(51, 'Suplementos minerales para bovinos', 40.00, 'kg', '2025-03-10', 5),
(52, 'Guantes', 200.00, 'Unidades', '2025-04-19', 1),
(54, 'Antibióticos para cerdos', 100.00, 'Dósis', '2025-04-01', 3);

--
-- Disparadores `inventario`
--
DELIMITER $$
CREATE TRIGGER `alerta_cambio_inventario` AFTER INSERT ON `inventario` FOR EACH ROW BEGIN
    DECLARE proveedor_nombre VARCHAR(100);

    SELECT nombre INTO proveedor_nombre 
    FROM proveedores 
    WHERE id_proveedor = NEW.id_proveedor;

    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'inventario',
        CONCAT('Nuevo producto en inventario: ', NEW.nombre_producto, 
               '. Cantidad: ', NEW.cantidad, ' ', NEW.unidad_medida, 
               '. Proveedor: ', IFNULL(proveedor_nombre, 'No especificado')),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_productos`
--

CREATE TABLE `inventario_productos` (
  `id_producto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `categoria` enum('Leche','Huevos','Carne','Pollo','Frutas','Verduras') NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario_productos`
--

INSERT INTO `inventario_productos` (`id_producto`, `nombre`, `descripcion`, `precio`, `cantidad`, `categoria`, `disponible`, `fecha_creacion`) VALUES
(1, 'Huevos de gallina', 'Huevos frescos de gallinas criadas en libertad', 12000.00, 148, 'Huevos', 1, '2025-05-01 00:39:41'),
(2, 'Leche entera', 'Leche fresca pasteurizada', 3500.00, 69, 'Leche', 1, '2025-05-01 00:39:41'),
(3, 'Carne de res', 'Cortes premium de carne de res', 25000.00, 45, 'Carne', 1, '2025-05-01 00:39:41'),
(4, 'Pollo entero', 'Pollo fresco criado sin hormonas', 15000.00, 60, 'Pollo', 1, '2025-05-01 00:39:41'),
(5, 'Manzanas', 'Manzanas rojas del huerto', 5000.00, 120, 'Frutas', 1, '2025-05-01 00:39:41'),
(6, 'Zanahorias', 'Zanahorias orgánicas recién cosechadas', 3000.00, 90, 'Verduras', 1, '2025-05-01 00:39:41'),
(7, 'Queso fresco', 'Queso fresco de leche entera', 12000.00, 39, 'Leche', 1, '2025-05-01 00:39:41'),
(8, 'Huevos de codorniz', 'Huevos pequeños de codorniz', 15000.00, 95, 'Huevos', 1, '2025-05-01 00:39:41'),
(9, 'Lomo de cerdo', 'Corte premium de cerdo', 22000.00, 30, 'Carne', 1, '2025-05-01 00:39:41'),
(10, 'Pechuga de pollo', 'Pechuga deshuesada y sin piel', 18000.00, 45, 'Pollo', 1, '2025-05-01 00:39:41'),
(11, 'Plátanos', 'Plátanos maduros de excelente calidad', 4000.00, 70, 'Frutas', 1, '2025-05-01 00:39:41'),
(12, 'Tomates', 'Tomates rojos jugosos', 4500.00, 85, 'Verduras', 1, '2025-05-01 00:39:41'),
(13, 'Yogur natural', 'Yogur natural sin azúcar añadida', 6000.00, 52, 'Leche', 1, '2025-05-01 00:39:41'),
(14, 'Huevos orgánicos', 'Huevos de gallinas alimentadas orgánicamente', 15000.00, 73, 'Huevos', 1, '2025-05-01 00:39:41'),
(15, 'Costilla de res', 'Costilla para asar o guisar', 28000.00, 25, 'Carne', 1, '2025-05-01 00:39:41'),
(16, 'Muslos de pollo', 'Muslos con piel y hueso', 12000.00, 55, 'Pollo', 1, '2025-05-01 00:39:41'),
(17, 'Fresas', 'Fresas frescas y dulces', 8000.00, 40, 'Frutas', 1, '2025-05-01 00:39:41'),
(18, 'Lechuga', 'Lechuga crespa orgánica', 3500.00, 65, 'Verduras', 1, '2025-05-01 00:39:41'),
(19, 'Mantequilla', 'Mantequilla de leche entera', 7000.00, 47, 'Leche', 1, '2025-05-01 00:39:41'),
(20, 'Huevos jumbo', 'Huevos tamaño jumbo', 13000.00, 90, 'Huevos', 1, '2025-05-01 00:39:41');

--
-- Disparadores `inventario_productos`
--
DELIMITER $$
CREATE TRIGGER `alerta_actualizacion_producto` AFTER UPDATE ON `inventario_productos` FOR EACH ROW BEGIN
    -- Solo registrar alerta si cambió el precio o la disponibilidad
    IF OLD.precio != NEW.precio OR OLD.disponible != NEW.disponible THEN
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'inventario', 
            CONCAT(
                'Actualización de producto: "', NEW.nombre, 
                '" (ID: ', NEW.id_producto, ')',
                IF(OLD.precio != NEW.precio, 
                   CONCAT(' - Precio actualizado de $', OLD.precio, ' a $', NEW.precio, ' COP'), 
                   ''),
                IF(OLD.disponible != NEW.disponible, 
                   CONCAT(' - Disponibilidad cambiada de ', IF(OLD.disponible = 1, 'Disponible', 'No disponible'),
                          ' a ', IF(NEW.disponible = 1, 'Disponible', 'No disponible')), 
                   '')
            ), 
            NOW()
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `alerta_bajo_stock` AFTER UPDATE ON `inventario_productos` FOR EACH ROW BEGIN
    DECLARE v_proveedor_info VARCHAR(255) DEFAULT '';
    
    -- Obtener información del proveedor principal (si existe)
    SELECT CONCAT('Proveedor principal: ', nombre, ' (Tel: ', IFNULL(telefono, 'No disponible'), ')')
    INTO v_proveedor_info
    FROM proveedores
    WHERE tipo_producto LIKE CONCAT('%', NEW.categoria, '%')
    LIMIT 1;
    
    IF NEW.cantidad < 5 AND NEW.cantidad > 0 THEN
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'inventario', 
            CONCAT(
                'Producto con bajo stock: "', NEW.nombre, 
                '" (ID: ', NEW.id_producto, ') - Cantidad restante: ', NEW.cantidad,
                ' - Categoría: ', NEW.categoria,
                ' - Precio: $', NEW.precio, ' COP',
                IF(v_proveedor_info = '', '', CONCAT(' - ', v_proveedor_info))
            ), 
            NOW()
        );
    ELSEIF NEW.cantidad <= 0 THEN
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'inventario', 
            CONCAT(
                'Producto agotado: "', NEW.nombre, 
                '" (ID: ', NEW.id_producto, ') - Categoría: ', NEW.categoria,
                ' - Precio: $', NEW.precio, ' COP',
                IF(v_proveedor_info = '', '', CONCAT(' - ', v_proveedor_info))
            ), 
            NOW()
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_after_delete_inventario_productos` AFTER DELETE ON `inventario_productos` FOR EACH ROW BEGIN
    -- Insertar en alertas
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'inventario',
        CONCAT('Producto eliminado del inventario: "', OLD.nombre, 
              '" (ID: ', OLD.id_producto, '). Categoría: ', OLD.categoria, 
              '. Último precio: $', OLD.precio, '.'),
        NOW()
    );
    
    -- Insertar en auditorías
    INSERT INTO auditorias (usuario, direccion_ip, tabla_afectada, accion, id_registro, detalles)
    VALUES (
        CURRENT_USER(),
        (SELECT SUBSTRING_INDEX(USER(), '@', -1)),
        'inventario_productos',
        'DELETE',
        OLD.id_producto,
        CONCAT('Producto eliminado: "', OLD.nombre, '" | Categoría: ', OLD.categoria, 
               ' | Último precio: $', OLD.precio)
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_after_insert_inventario_productos` AFTER INSERT ON `inventario_productos` FOR EACH ROW BEGIN
    -- Insertar en alertas
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'inventario',
        CONCAT('Nuevo producto agregado al inventario: "', NEW.nombre, 
               '" (ID: ', NEW.id_producto, '). Categoría: ', NEW.categoria, 
               '. Precio: $', NEW.precio, '. Cantidad inicial: ', NEW.cantidad, ' unidades.'),
        NOW()
    );
    
    -- Insertar en auditorías
    INSERT INTO auditorias (usuario, direccion_ip, tabla_afectada, accion, id_registro, detalles)
    VALUES (
        CURRENT_USER(),
        (SELECT SUBSTRING_INDEX(USER(), '@', -1)),
        'inventario_productos',
        'INSERT',
        NEW.id_producto,
        CONCAT('Nuevo producto: "', NEW.nombre, '" | Categoría: ', NEW.categoria, 
               ' | Precio: $', NEW.precio, ' | Cantidad: ', NEW.cantidad)
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_after_update_inventario_productos` AFTER UPDATE ON `inventario_productos` FOR EACH ROW BEGIN
    DECLARE cambios TEXT DEFAULT '';
    
    -- Verificar cambios en cantidad
    IF OLD.cantidad != NEW.cantidad THEN
        SET cambios = CONCAT(cambios, 'Cantidad: ', OLD.cantidad, ' → ', NEW.cantidad, '. ');
    END IF;
    
    -- Verificar cambios en precio
    IF OLD.precio != NEW.precio THEN
        SET cambios = CONCAT(cambios, 'Precio: $', OLD.precio, ' → $', NEW.precio, '. ');
    END IF;
    
    -- Verificar cambios en disponibilidad
    IF OLD.disponible != NEW.disponible THEN
        SET cambios = CONCAT(cambios, 'Disponibilidad: ', 
            IF(OLD.disponible=1, 'Disponible', 'No disponible'), 
            ' → ', IF(NEW.disponible=1, 'Disponible', 'No disponible'), '. ');
    END IF;
    
    -- Si hay cambios, registrar alerta
    IF cambios != '' THEN
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'inventario',
            CONCAT('Actualización de producto "', NEW.nombre, 
                  '" (ID: ', NEW.id_producto, '). Cambios: ', cambios),
            NOW()
        );
        
        -- Insertar en auditorías
        INSERT INTO auditorias (usuario, direccion_ip, tabla_afectada, accion, id_registro, detalles)
        VALUES (
            CURRENT_USER(),
            (SELECT SUBSTRING_INDEX(USER(), '@', -1)),
            'inventario_productos',
            'UPDATE',
            NEW.id_producto,
            CONCAT('Actualización de producto "', NEW.nombre, '". Cambios: ', cambios)
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_accesos`
--

CREATE TABLE `log_accesos` (
  `id_log` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha_intento` timestamp NOT NULL DEFAULT current_timestamp(),
  `exito` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `log_accesos`
--

INSERT INTO `log_accesos` (`id_log`, `id_usuario`, `fecha_intento`, `exito`) VALUES
(8, 1, '2025-04-15 23:57:00', 1),
(9, 1, '2025-04-15 23:57:06', 0),
(10, 1, '2025-04-16 00:15:10', 1),
(11, 1, '2025-04-16 00:15:16', 0),
(12, 1, '2025-04-16 00:15:29', 0),
(13, 1, '2025-04-16 00:15:39', 1),
(14, 1, '2025-04-16 00:17:58', 1),
(15, 1, '2025-04-16 00:18:04', 0),
(16, 1, '2025-04-16 00:28:46', 1),
(17, 1, '2025-04-16 00:29:06', 1),
(18, 1, '2025-04-16 00:29:19', 1),
(19, 1, '2025-04-21 20:14:38', 1),
(20, 1, '2025-04-21 20:25:37', 1),
(21, 1, '2025-04-21 20:40:20', 1),
(22, 1, '2025-04-21 20:42:49', 1),
(23, 1, '2025-04-21 20:42:57', 1),
(24, 2, '2025-04-21 20:54:18', 1),
(25, 2, '2025-04-21 20:54:39', 1),
(26, 2, '2025-04-21 20:55:00', 1),
(27, 3, '2025-04-21 21:02:14', 1),
(28, 3, '2025-04-21 21:02:27', 1),
(29, 3, '2025-04-21 21:02:40', 1),
(30, 3, '2025-04-21 21:09:50', 1),
(31, 3, '2025-04-21 21:10:05', 1),
(32, 4, '2025-04-21 21:20:20', 1),
(33, 4, '2025-04-21 21:20:35', 1),
(34, 4, '2025-04-21 21:22:17', 1),
(35, 1, '2025-04-21 21:23:43', 1),
(36, 1, '2025-04-21 22:13:02', 1),
(37, 1, '2025-04-21 22:21:50', 1),
(38, 1, '2025-04-22 02:46:24', 1),
(39, 1, '2025-04-22 09:06:05', 1),
(40, 1, '2025-04-22 09:06:33', 1),
(41, 1, '2025-04-23 22:20:21', 1),
(42, 1, '2025-04-23 22:22:05', 1),
(43, 18, '2025-04-24 04:12:28', 1),
(44, 18, '2025-04-24 04:23:27', 1),
(45, 4, '2025-04-29 03:46:40', 1),
(46, 1, '2025-04-29 03:46:57', 1),
(47, 4, '2025-04-29 03:47:54', 1),
(48, 4, '2025-04-29 03:57:30', 1),
(49, 4, '2025-04-29 03:58:15', 1),
(50, 4, '2025-04-29 04:01:56', 1),
(51, 4, '2025-04-29 04:03:57', 1),
(52, 1, '2025-04-29 04:07:36', 1),
(53, 1, '2025-04-29 12:09:14', 1),
(54, 4, '2025-04-29 12:09:29', 1),
(55, 4, '2025-04-29 12:45:20', 1),
(56, 18, '2025-04-30 17:55:35', 1),
(57, 18, '2025-04-30 21:07:25', 1),
(58, 18, '2025-04-30 22:02:01', 1),
(59, 4, '2025-05-01 03:29:24', 1),
(60, 4, '2025-05-01 03:41:28', 1),
(61, 4, '2025-05-01 03:55:56', 1),
(62, 5, '2025-05-01 03:57:00', 1),
(63, 6, '2025-05-01 04:10:20', 1),
(64, 7, '2025-05-01 04:11:25', 1),
(65, 7, '2025-05-01 04:12:45', 1),
(66, 7, '2025-05-01 04:16:46', 1),
(67, 7, '2025-05-01 04:17:05', 1),
(68, 7, '2025-05-01 04:18:40', 1),
(69, 7, '2025-05-01 04:20:31', 1),
(70, 6, '2025-05-01 04:20:46', 1),
(71, 5, '2025-05-01 04:20:56', 1),
(72, 4, '2025-05-01 04:21:08', 1),
(73, 4, '2025-05-01 04:33:19', 1),
(74, 4, '2025-05-01 04:41:22', 1),
(75, 4, '2025-05-01 05:05:05', 1),
(76, 6, '2025-05-01 05:05:57', 1),
(77, 7, '2025-05-01 05:06:19', 1),
(78, 18, '2025-05-01 05:06:46', 1),
(79, 4, '2025-05-01 05:11:10', 1),
(80, 4, '2025-05-15 14:42:38', 1),
(81, 4, '2025-05-26 15:25:05', 1),
(82, 4, '2025-05-26 23:02:47', 1),
(83, 5, '2025-05-26 23:05:36', 1),
(84, 5, '2025-05-26 23:44:58', 1),
(85, 4, '2025-05-26 23:45:09', 1),
(86, 5, '2025-05-27 02:32:16', 1),
(87, 5, '2025-05-27 02:54:26', 1),
(88, 5, '2025-05-28 19:44:26', 1),
(89, 5, '2025-05-28 19:56:12', 1),
(90, 5, '2025-05-28 19:58:16', 1),
(91, 5, '2025-05-28 20:00:51', 1),
(92, 5, '2025-05-28 20:07:42', 1),
(93, 5, '2025-05-28 20:08:00', 1),
(94, 4, '2025-05-28 20:13:46', 1),
(95, 4, '2025-05-28 22:46:10', 1),
(96, 5, '2025-05-28 23:35:28', 1),
(97, 5, '2025-05-28 23:49:54', 1),
(98, 5, '2025-05-28 23:51:06', 1),
(99, 1, '2025-05-28 23:52:46', 1),
(100, 6, '2025-05-28 23:53:55', 1),
(101, 6, '2025-05-28 23:55:15', 1),
(102, 6, '2025-05-28 23:55:17', 1),
(103, 6, '2025-05-28 23:57:55', 1),
(104, 4, '2025-05-29 00:05:13', 1),
(105, 6, '2025-05-29 00:05:17', 1),
(106, 4, '2025-05-29 00:05:38', 1),
(107, 6, '2025-05-29 00:05:39', 1),
(108, 4, '2025-05-29 00:06:33', 1),
(109, 4, '2025-05-29 00:07:20', 1),
(110, 7, '2025-05-29 00:27:20', 1),
(111, 4, '2025-05-29 01:36:55', 1),
(112, 4, '2025-05-29 01:51:10', 1),
(113, 3, '2025-05-29 02:04:42', 1),
(114, 5, '2025-05-29 03:23:43', 1),
(115, 4, '2025-05-29 03:38:16', 1),
(116, 5, '2025-05-29 03:41:06', 1),
(117, 5, '2025-05-29 05:10:49', 1),
(118, 4, '2025-05-29 05:17:18', 1),
(119, 4, '2025-05-29 05:24:27', 1),
(120, 4, '2025-05-29 05:39:08', 1),
(121, 5, '2025-05-29 05:47:03', 1),
(122, 4, '2025-05-29 05:55:32', 1),
(123, 5, '2025-05-29 06:05:44', 1),
(124, 7, '2025-05-29 06:08:16', 1),
(125, 5, '2025-05-29 06:12:32', 1),
(126, 5, '2025-05-29 06:14:11', 1),
(127, 6, '2025-05-29 06:14:36', 1),
(128, 3, '2025-05-29 06:15:17', 1),
(129, 3, '2025-05-29 06:16:06', 1),
(130, 10, '2025-05-29 06:36:00', 1),
(131, 10, '2025-05-29 06:48:09', 1),
(132, 10, '2025-05-29 06:49:22', 1),
(133, 3, '2025-05-29 06:50:45', 1);

--
-- Disparadores `log_accesos`
--
DELIMITER $$
CREATE TRIGGER `gestion_bloqueo` AFTER INSERT ON `log_accesos` FOR EACH ROW BEGIN
    -- Solo actuar en accesos fallidos
    IF NEW.exito = 0 THEN
        -- Actualizar contador de intentos fallidos
        INSERT INTO bloqueo_usuarios (id_usuario, intentos_fallidos) 
        VALUES (NEW.id_usuario, 1)
        ON DUPLICATE KEY UPDATE intentos_fallidos = intentos_fallidos + 1;
        
        -- Verificar si debe bloquearse (3 o más intentos)
        UPDATE bloqueo_usuarios 
        SET bloqueado_desde = NOW(),
            bloqueado_hasta = NOW() + INTERVAL 30 MINUTE
        WHERE id_usuario = NEW.id_usuario 
        AND intentos_fallidos >= 3
        AND (bloqueado_hasta IS NULL OR bloqueado_hasta <= NOW());
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_accesos_clientes`
--

CREATE TABLE `log_accesos_clientes` (
  `id_log` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `fecha_intento` timestamp NOT NULL DEFAULT current_timestamp(),
  `exito` tinyint(1) DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','procesando','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `direccion_envio` varchar(255) DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `id_cliente`, `fecha_pedido`, `total`, `estado`, `direccion_envio`, `metodo_pago`) VALUES
(1, 5, '2025-05-01 05:04:26', 63500.00, 'pendiente', 'Barrio Jardin', 'tarjeta'),
(2, 1, '2025-05-26 15:23:22', 106000.00, 'pendiente', 'Jardin', 'contraentrega');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plantas`
--

CREATE TABLE `plantas` (
  `id_planta` int(11) NOT NULL,
  `nombre_cientifico` varchar(100) NOT NULL,
  `nombre_comun` varchar(100) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `estado` enum('Sano','Enfermo','Recuperación') DEFAULT 'Sano',
  `descripcion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plantas`
--

INSERT INTO `plantas` (`id_planta`, `nombre_cientifico`, `nombre_comun`, `ubicacion`, `estado`, `descripcion`, `fecha_registro`) VALUES
(1, 'Zea mays', 'Maíz', 'Campo 1', 'Sano', 'Cultivo de maíz en fase de crecimiento', '2025-03-09 00:20:59'),
(2, 'Solanum tuberosum', 'Papa', 'Campo 2', 'Sano', 'Cultivo de papa en temporada de cosecha', '2025-03-09 00:20:59'),
(3, 'Coffea arabica', 'Café', 'Plantación 1', 'Sano', 'Plantas en etapa de floración', '2025-03-09 00:20:59'),
(4, 'Theobroma cacao', 'Cacao', 'Plantacion 1', 'Enfermo', 'Cultivo de cacao en fase de crecimiento', '2025-03-09 00:20:59'),
(5, 'Oryza sativa', 'Arroz', 'Campo 3', 'Sano', 'Se aplicó tratamiento contra plagas', '2025-03-09 00:20:59'),
(6, 'Citrus sinensis', 'Naranjo', 'Huerto 1', 'Sano', 'Árboles frutales en buen estado', '2025-03-09 00:20:59'),
(7, 'Mangifera indica', 'Mango', 'Huerto 2', 'Sano', 'Producción de frutos', '2025-03-09 00:20:59'),
(8, 'Persea americana', 'Aguacate', 'Huerto 3', 'Enfermo', 'Crecimiento adecuado', '2025-03-09 00:20:59'),
(9, 'Capsicum annuum', 'Ají', 'Campo 4', 'Enfermo', 'Marchitez bacteriana detectada', '2025-03-09 00:20:59'),
(10, 'Phaseolus vulgaris', 'Frijol', 'Campo 5', 'Sano', 'Plantas en fase de floración', '2025-03-09 00:20:59'),
(11, 'Saccharum officinarum', 'Caña de azúcar', 'Campo 6', 'Sano', 'Cultivo en pleno desarrollo', '2025-03-09 00:20:59'),
(12, 'Glycine max', 'Soya', 'Campo 7', 'Recuperación', 'En tratamiento por deficiencia de nutrientes', '2025-03-09 00:20:59'),
(14, 'Allium cepa', 'Cebolla', 'Campo 9', 'Enfermo', 'Infección fúngica detectada', '2025-03-09 00:20:59'),
(15, 'Lycopersicon esculentum', 'Tomate', 'Invernadero 1', 'Sano', 'Cultivo con producción continua', '2025-03-09 00:20:59'),
(16, 'Brassica oleracea', 'Repollo', 'Invernadero 2', 'Sano', 'Buena formación de cabezas', '2025-03-09 00:20:59'),
(17, 'Cucumis sativus', 'Pepino', 'Invernadero 3', 'Sano', 'Cultivo en crecimiento vertical', '2025-03-09 00:20:59'),
(18, 'Psidium guajava', 'Guayaba', 'Huerto 4', 'Recuperación', 'Tratamiento contra insectos defoliadores', '2025-03-09 00:20:59'),
(19, 'Ananas comosus', 'Piña', 'Huerto 5', 'Sano', 'Plantas en etapa de maduración', '2025-03-09 00:20:59'),
(20, 'Helianthus annuus', 'Girasol', 'Campo 10', 'Sano', 'Cultivo en fase de floración', '2025-03-09 00:20:59'),
(21, 'elianthus annuus', 'Girasol', 'Campo 8', 'Sano', 'Cultivo en fase de crecimiento', '2025-04-22 06:06:30'),
(22, 'Capsicum annuum', 'Ají', 'Huerto 2', 'Sano', 'ninguna', '2025-05-27 00:58:17');

--
-- Disparadores `plantas`
--
DELIMITER $$
CREATE TRIGGER `alerta_cambio_estado_planta` AFTER UPDATE ON `plantas` FOR EACH ROW BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'planta',
            CONCAT('Cambio de estado en planta ID ', NEW.id_planta, ': ', OLD.estado, ' → ', NEW.estado, 
                   '. Nombre: ', NEW.nombre_comun, '.'),
            NOW()
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `alerta_nueva_planta` AFTER INSERT ON `plantas` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'planta',
        CONCAT('Nueva planta registrada: ', NEW.nombre_comun, ' (ID: ', NEW.id_planta, '). Ubicación: ', 
               NEW.ubicacion, '. Estado: ', NEW.estado, '.'),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `produccion`
--

CREATE TABLE `produccion` (
  `id_produccion` int(11) NOT NULL,
  `id_animal` int(11) DEFAULT NULL,
  `tipo_produccion` enum('Leche','Huevos','Carne','Otro') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `fecha_recoleccion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `produccion`
--

INSERT INTO `produccion` (`id_produccion`, `id_animal`, `tipo_produccion`, `cantidad`, `fecha_recoleccion`) VALUES
(1, 1, 'Huevos', 30.00, '2024-02-01'),
(2, 2, 'Leche', 15.00, '2024-02-02'),
(3, 3, 'Carne', 200.00, '2024-02-03'),
(5, 5, 'Leche', 18.00, '2024-02-05'),
(6, 1, 'Huevos', 10.00, '2024-02-27'),
(7, 2, 'Huevos', 8.00, '2024-02-28'),
(8, 3, 'Leche', 15.00, '2024-02-27'),
(10, 7, 'Carne', 50.00, '2024-02-29'),
(11, 8, 'Carne', 55.00, '2024-02-29'),
(27, 1, 'Huevos', 35.00, '2024-02-10'),
(28, 2, 'Leche', 22.00, '2024-02-11'),
(29, 3, 'Carne', 250.00, '2024-02-12'),
(31, 5, 'Leche', 30.00, '2024-02-14'),
(32, 6, 'Carne', 275.00, '2024-02-15'),
(33, 7, 'Huevos', 33.00, '2024-02-16'),
(34, 8, 'Leche', 28.00, '2024-02-17'),
(35, 9, 'Carne', 260.00, '2024-02-18'),
(36, 10, 'Huevos', 38.00, '2024-02-19'),
(37, 11, 'Leche', 25.00, '2024-02-20'),
(38, 12, 'Carne', 290.00, '2024-02-21'),
(39, 13, 'Huevos', 42.00, '2024-02-22'),
(40, 14, 'Leche', 27.00, '2024-02-23'),
(41, 15, 'Carne', 300.00, '2024-02-24'),
(42, 3, 'Huevos', 10.00, '2025-04-15');

--
-- Disparadores `produccion`
--
DELIMITER $$
CREATE TRIGGER `alerta_nueva_produccion` AFTER INSERT ON `produccion` FOR EACH ROW BEGIN
    DECLARE animal_nombre VARCHAR(100);
    
    SELECT nombre_comun INTO animal_nombre 
    FROM animales 
    WHERE id_animal = NEW.id_animal;
    
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'animal',
        CONCAT(
            'Nueva producción registrada: ', NEW.cantidad, 
            ' de ', NEW.tipo_produccion, 
            '. Animal: ', IFNULL(animal_nombre, 'No especificado')
        ),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `tipo_producto` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_proveedor`, `nombre`, `telefono`, `direccion`, `tipo_producto`) VALUES
(1, 'AgroAlimentos', '1234567', 'Calle 123', 'Alimentos balanceados'),
(2, 'Forrajes del Campo', '1234579', 'Medellin', 'Pasto y heno'),
(3, 'VetSalud', '555666777', 'Carretera 789, Municipio', 'Medicinas veterinarias'),
(4, 'Herramientas Agropecuarias S.A.', '111222333', 'Zona Industrial 10, Ciudad', 'Herramientas y equipamiento'),
(5, 'CampoSano Ltda.', '444555666', 'Kilómetro 15, Vía Rural', 'Suplementos nutricionales'),
(6, 'Cercas y Estructuras Rurales', '777888999', 'Avenida Principal 500, Pueblo', 'Materiales para cercado'),
(7, 'BioFarm Insumos', '222333444', 'Sector Agropecuario, Zona 3', 'Desinfectantes y biocidas'),
(8, 'AgroFert', '3123456789', 'Calle 12 #45-67, Bogotá', 'Fertilizantes'),
(9, 'VetSalud', '3209876543', 'Carrera 7 #89-12, Medellín', 'Medicamentos veterinarios'),
(10, 'BioSemillas', '3141592653', 'Avenida 15 #23-56, Cali', 'Semillas y plántulas'),
(11, 'AgroTools', '3112223333', 'Calle 8 #10-20, Bucaramanga', 'Herramientas agrícolas'),
(12, 'NutriAnimal', '3101112233', 'Diagonal 45 #67-89, Barranquilla', 'Alimentos para animales');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id_reporte` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_planta` int(11) DEFAULT NULL,
  `id_animal` int(11) DEFAULT NULL,
  `tipo` enum('Planta','Animal') NOT NULL,
  `diagnostico` text NOT NULL,
  `fecha_reporte` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id_reporte`, `id_usuario`, `id_planta`, `id_animal`, `tipo`, `diagnostico`, `fecha_reporte`) VALUES
(1, 1, 1, NULL, 'Planta', 'Hongo en hojas', '2025-03-09 00:30:29'),
(2, 2, 2, NULL, 'Planta', 'Bacterias en raíz', '2025-03-09 00:30:29'),
(3, 3, 3, NULL, 'Planta', 'Plaga en el tallo', '2025-03-09 00:30:29'),
(4, 4, 4, NULL, 'Planta', 'Déficit nutricional', '2025-03-09 00:30:29'),
(5, 5, 5, NULL, 'Planta', 'Ataque de insectos', '2025-03-09 00:30:29'),
(6, 6, 6, NULL, 'Planta', 'Manchas virales', '2025-03-09 00:30:29'),
(7, 7, 7, NULL, 'Planta', 'Falta de riego', '2025-03-09 00:30:29'),
(8, 8, 8, NULL, 'Planta', 'Fungosis en flores', '2025-03-09 00:30:29'),
(9, 9, 9, NULL, 'Planta', 'Necrosis en hojas', '2025-03-09 00:30:29'),
(10, 10, 10, NULL, 'Planta', 'Raíz podrida', '2025-03-09 00:30:29'),
(11, 1, NULL, NULL, 'Animal', 'Infección en gallina', '2025-03-09 00:30:29'),
(12, 2, NULL, NULL, 'Animal', 'Enfermedad respiratoria en cerdo', '2025-03-09 00:30:29'),
(13, 3, NULL, NULL, 'Animal', 'Problema digestivo en vaca', '2025-03-09 00:30:29'),
(14, 4, NULL, NULL, 'Animal', 'Pérdida de peso en tilapia', '2025-03-09 00:30:29'),
(15, 5, NULL, NULL, 'Animal', 'Parásitos en oveja', '2025-03-09 00:30:29'),
(16, 7, 14, NULL, 'Planta', 'Infección fúngica detectada', '2025-04-22 06:22:27'),
(17, 4, 8, NULL, 'Planta', 'n', '2025-05-29 01:56:34');

--
-- Disparadores `reportes`
--
DELIMITER $$
CREATE TRIGGER `alerta_eliminacion_reporte` AFTER DELETE ON `reportes` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        CASE WHEN OLD.tipo = 'Animal' THEN 'animal' ELSE 'planta' END,
        CONCAT('Se ha eliminado un reporte de ', OLD.tipo, ' con ID ', COALESCE(OLD.id_animal, OLD.id_planta), '.'),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `alerta_nuevo_reporte` AFTER INSERT ON `reportes` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        CASE WHEN NEW.tipo = 'Animal' THEN 'animal' ELSE 'planta' END,
        CONCAT('Nuevo reporte ingresado para ', NEW.tipo, ' con ID ', COALESCE(NEW.id_animal, NEW.id_planta), 
               '. Diagnóstico: ', NEW.diagnostico),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tratamientos`
--

CREATE TABLE `tratamientos` (
  `id_tratamiento` int(11) NOT NULL,
  `id_reporte` int(11) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `resultado` enum('Exitoso','En Proceso','Fallido') DEFAULT 'En Proceso'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tratamientos`
--

INSERT INTO `tratamientos` (`id_tratamiento`, `id_reporte`, `descripcion`, `fecha_inicio`, `fecha_fin`, `resultado`) VALUES
(1, 1, 'Aplicación de fungicida en cacao', '2025-03-02', '2025-03-10', 'En Proceso'),
(2, 2, 'Uso de bactericida en ají', '2025-03-03', '2025-03-12', 'Fallido'),
(3, 3, 'Monitoreo post-tratamiento de plaga', '2025-03-04', NULL, 'En Proceso'),
(4, 4, 'Tratamiento para déficit nutricional', '2025-03-05', '2025-03-15', 'Exitoso'),
(5, 5, 'Uso de insecticida en hojas', '2025-03-06', '2025-03-16', 'Fallido'),
(6, 6, 'Cambio de sustrato y nutrientes', '2025-03-07', '2025-03-17', 'En Proceso'),
(7, 7, 'Tratamiento de riego controlado', '2025-03-08', '2025-03-18', 'En Proceso'),
(8, 8, 'Fungicida aplicado en flores', '2025-03-09', '2025-03-19', 'Exitoso'),
(9, 9, 'Abono orgánico y control de plagas', '2025-03-10', NULL, 'En Proceso'),
(10, 10, 'Control biológico para hongos', '2025-03-11', '2025-03-20', 'En Proceso'),
(11, 11, 'Administración de antibiótico a gallina', '2025-03-12', '2025-03-21', 'Exitoso'),
(12, 12, 'Cambio de alimentación en cerdo', '2025-03-13', '2025-03-22', 'Fallido'),
(13, 13, 'Tratamiento digestivo en vaca', '2025-03-14', '2025-03-23', 'En Proceso'),
(14, 14, 'Vitaminas para tilapia', '2025-03-15', '2025-03-24', 'Exitoso'),
(15, 15, 'Desparasitación de ovejas', '2025-03-16', '2025-03-25', 'Fallido'),
(16, 16, 'Tratamiento contra hongo en plantacion de cebolla', '2025-04-01', '2024-04-20', 'En Proceso');

--
-- Disparadores `tratamientos`
--
DELIMITER $$
CREATE TRIGGER `alerta_cambio_estado_tratamiento` AFTER UPDATE ON `tratamientos` FOR EACH ROW BEGIN
    IF OLD.resultado <> NEW.resultado THEN
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'salud',
            CONCAT('El tratamiento ID ', NEW.id_tratamiento, ' ha cambiado su estado a ', NEW.resultado, '.'),
            NOW()
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `alerta_eliminacion_tratamiento` AFTER DELETE ON `tratamientos` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'salud',
        CONCAT('Se ha eliminado el tratamiento ID ', OLD.id_tratamiento, '.'),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicacion_georreferenciada`
--

CREATE TABLE `ubicacion_georreferenciada` (
  `id_ubicacion` int(11) NOT NULL,
  `id_animal` int(11) DEFAULT NULL,
  `id_planta` int(11) DEFAULT NULL,
  `latitud` decimal(10,6) NOT NULL,
  `longitud` decimal(10,6) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `tipo_usuario` enum('Investigador','Veterinario','Botánico','Administrador') NOT NULL,
  `clave` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `telefono`, `tipo_usuario`, `clave`) VALUES
(1, 'Carlos Pérez', 'carlos.perez@email.com', '3112233445', 'Veterinario', 'clave123'),
(2, 'María López', 'maria.lopez@email.com', '3123344556', 'Investigador', 'clave456'),
(3, 'José Ramírez', 'jose.ramirez@email.com', '3134455667', 'Botánico', 'clave789'),
(4, 'Laura Gómez', 'laura.gomez@email.com', '3145566778', 'Administrador', 'admin001'),
(5, 'Fernando Ríos', 'fernando.rios@email.com', '3156677889', 'Veterinario', '$2y$10$zn7vaFw5VmafHubk5t7YjOJu9hYTOkfgDLDTQXKNrVmUzsP1daZN2'),
(6, 'Sofía Medina', 'sofia.medina@email.com', '3167788990', 'Investigador', 'invest002'),
(7, 'Andrés Castro', 'andres.castro@email.com', '3178899001', 'Botánico', 'botan003'),
(8, 'Patricia Suárez', 'patricia.suarez@email.com', '3189900112', 'Administrador', 'admin002'),
(9, 'Ricardo Torres', 'ricardo.torres@email.com', '3190011223', 'Veterinario', 'veter002'),
(10, 'Elena Vargas', 'elena.vargas@email.com', '3201122334', 'Investigador', 'invest003'),
(18, 'Juan Perez', 'juan.perez@gmail.com', '1234', 'Investigador', '$2y$10$doo8v6TmLBpiKxV6EU7GyO.XHQ1.YvK2iD5QvRpuXOfDWZTbxRMcK');

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `alerta_nuevo_usuario` AFTER INSERT ON `usuarios` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'usuario',  -- Categoría específica para partición
        CONCAT(
            'Nuevo usuario registrado: ', NEW.nombre, 
            '. Tipo: ', NEW.tipo_usuario, 
            '. Correo: ', NEW.correo, '.'
        ),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_usuarios` AFTER UPDATE ON `usuarios` FOR EACH ROW BEGIN
    IF OLD.tipo_usuario != NEW.tipo_usuario THEN
        INSERT INTO alertas (categoria, mensaje, fecha)
        VALUES (
            'usuario',  -- Categoría específica para partición
            CONCAT('Cambio privilegios usuario ', NEW.id_usuario, ': ', OLD.tipo_usuario, ' → ', NEW.tipo_usuario),
            NOW()
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vacunacion`
--

CREATE TABLE `vacunacion` (
  `id_vacunacion` int(11) NOT NULL,
  `id_animal` int(11) NOT NULL,
  `id_vacuna` int(11) NOT NULL,
  `fecha_aplicacion` date NOT NULL,
  `proxima_dosis` date DEFAULT NULL,
  `dosis` varchar(50) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vacunacion`
--

INSERT INTO `vacunacion` (`id_vacunacion`, `id_animal`, `id_vacuna`, `fecha_aplicacion`, `proxima_dosis`, `dosis`, `id_empleado`, `observaciones`) VALUES
(1, 1, 1, '2025-02-01', '2025-08-01', '5ml', 1, 'Vacuna aplicada sin reacciones adversas.'),
(2, 2, 2, '2025-02-05', '2025-08-05', '10ml', 5, 'Leve inflamación en el área de aplicación.'),
(3, 3, 3, '2025-02-10', '2025-08-10', '8ml', 9, 'Sin efectos secundarios observados.'),
(5, 5, 5, '2025-02-20', '2025-08-20', '6ml', 5, 'El animal mostró fatiga leve.'),
(6, 6, 6, '2025-02-25', '2025-08-25', '5ml', 9, 'Vacuna aplicada sin problemas.'),
(7, 7, 7, '2025-03-01', '2025-09-01', '4ml', 1, 'Observación: buena respuesta inmunitaria.'),
(8, 8, 8, '2025-03-05', '2025-09-05', '9ml', 5, 'Sin reacciones adversas.'),
(9, 9, 1, '2025-03-10', '2025-09-10', '5ml', 9, 'Aplicación en horario matutino sin inconvenientes.'),
(10, 10, 2, '2025-03-15', '2025-09-15', '10ml', 1, 'Leve fiebre las primeras 24 horas.'),
(11, 11, 3, '2025-03-20', '2025-09-20', '8ml', 5, 'Sin síntomas post-vacunación.'),
(12, 12, 4, '2025-03-25', '2025-09-25', '7ml', 9, 'Animal estable tras la vacunación.'),
(13, 13, 5, '2025-04-01', '2025-10-01', '6ml', 1, 'Buena tolerancia a la vacuna.'),
(14, 14, 6, '2025-04-05', '2025-10-05', '5ml', 5, 'Seguimiento sin complicaciones.'),
(15, 15, 7, '2025-04-10', '2025-10-10', '4ml', 9, 'Vacunación completada sin efectos adversos.'),
(16, 16, 8, '2025-04-15', '2025-10-15', '9ml', 1, 'Dosis administrada correctamente.'),
(17, 17, 1, '2025-04-20', '2025-10-20', '5ml', 5, 'Animal tranquilo tras la aplicación.'),
(18, 18, 2, '2025-04-25', '2025-10-25', '10ml', 9, 'Chequeo post-vacuna normal.'),
(19, 19, 3, '2025-05-01', '2025-11-01', '8ml', 1, 'Vacuna administrada sin problemas.'),
(20, 20, 4, '2025-05-05', '2025-11-05', '7ml', 5, 'Monitorización en curso, sin anomalías.'),
(21, 21, 5, '2025-05-10', '2025-11-10', '6ml', 9, 'El animal mostró signos normales de actividad.'),
(22, 22, 6, '2025-05-15', '2025-11-15', '5ml', 1, 'Revisión satisfactoria.'),
(23, 23, 7, '2025-05-20', '2025-11-20', '4ml', 5, 'Vacuna aplicada sin incidentes.'),
(24, 24, 8, '2025-05-25', '2025-11-25', '9ml', 9, 'Animal en buenas condiciones tras la vacunación.'),
(25, 25, 1, '2025-06-01', '2025-12-01', '5ml', 1, 'Sin efectos negativos.'),
(26, 26, 2, '2025-06-05', '2025-12-05', '10ml', 5, 'Vacunación exitosa.'),
(27, 27, 3, '2025-06-10', '2025-12-10', '8ml', 9, 'Buen estado de salud post-vacunación.'),
(28, 28, 4, '2025-06-15', '2025-12-15', '7ml', 1, 'Comportamiento normal después de la vacuna.'),
(29, 29, 5, '2025-06-20', '2025-12-20', '6ml', 5, 'El animal se mantiene activo.'),
(30, 30, 6, '2025-06-25', '2025-12-25', '5ml', 9, 'Sin efectos adversos observados.');

--
-- Disparadores `vacunacion`
--
DELIMITER $$
CREATE TRIGGER `alerta_eliminacion_vacunacion` AFTER DELETE ON `vacunacion` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'vacunacion',
        CONCAT('Se ha eliminado un registro de vacunación para el animal con ID ', OLD.id_animal, ' y la vacuna ID ', OLD.id_vacuna, '.'),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `alerta_proxima_vacunacion` AFTER INSERT ON `vacunacion` FOR EACH ROW BEGIN
    IF NEW.proxima_dosis IS NOT NULL AND NEW.proxima_dosis <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN
        INSERT INTO alertas (categoria, mensaje, fecha) 
        VALUES (
            'vacunacion',
            CONCAT('Recordatorio: El animal con ID ', NEW.id_animal, ' debe recibir la vacuna ', NEW.id_vacuna, ' el ', NEW.proxima_dosis, '.'),
            NOW()
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vacunas`
--

CREATE TABLE `vacunas` (
  `id_vacuna` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `fabricante` varchar(100) NOT NULL,
  `temperatura_almacenamiento` varchar(50) NOT NULL,
  `vida_util` varchar(50) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vacunas`
--

INSERT INTO `vacunas` (`id_vacuna`, `nombre`, `descripcion`, `fabricante`, `temperatura_almacenamiento`, `vida_util`, `cantidad`) VALUES
(1, 'Fiebre Aftosa', 'Protege contra la fiebre aftosa en bovinos', 'Zoetis', 'Entre 2°C y 8°C', '2 años', 50),
(2, 'Vacuna contra la Rabia', 'Protección anual contra el virus de la rabia', 'Zoetis', 'Entre 4°C y 5°C', '1 año', 70),
(3, 'Brucelosis', 'Inmunización contra la brucelosis bovina', 'Boehringer Ingelheim', 'Entre 2°C y 8°C', '2 años', 20),
(4, 'Newcastle', 'Protección contra la enfermedad de Newcastle en aves', 'Ceva Santé Animale', 'Entre 2°C y 8°C', '1.5 años', 40),
(5, 'Leptospirosis', 'Prevención de infecciones por Leptospira en ganado', 'Pfizer', 'Entre 2°C y 8°C', '2 años', 60),
(6, 'Clostridiosis', 'Protege contra infecciones clostridiales en bovinos y ovinos', 'Biogénesis Bagó', 'Entre 2°C y 8°C', '2 años', 25),
(7, 'Influenza Porcina', 'Prevención de la gripe porcina', 'HIPRA', 'Entre 2°C y 8°C', '1 año', 35),
(8, 'Pasteurelosis', 'Protección contra la pasteurelosis en bovinos y ovinos', 'Virbac', 'Entre 2°C y 8°C', '2 años', 45);

--
-- Disparadores `vacunas`
--
DELIMITER $$
CREATE TRIGGER `alerta_baja_vacunas` AFTER UPDATE ON `vacunas` FOR EACH ROW BEGIN
    IF NEW.cantidad < 5 THEN
        INSERT INTO alertas (categoria, mensaje, fecha) 
        VALUES (
            'vacunas',
            CONCAT('Alerta: La vacuna ', NEW.nombre, ' está baja en stock (', NEW.cantidad, ' restantes).'),
            NOW()
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `alerta_eliminacion_vacuna` AFTER DELETE ON `vacunas` FOR EACH ROW BEGIN
    INSERT INTO alertas (categoria, mensaje, fecha)
    VALUES (
        'vacunas',
        CONCAT('Se ha eliminado la vacuna: ', OLD.nombre, '.'),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `precio_total` decimal(10,2) NOT NULL,
  `fecha_venta` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_cliente` int(11) DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `estado` enum('pendiente','completada','cancelada') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `id_pedido`, `id_producto`, `cantidad`, `precio_unitario`, `precio_total`, `fecha_venta`, `id_cliente`, `metodo_pago`, `estado`) VALUES
(1, 1, 2, 1, 3500.00, 3500.00, '2025-05-01 05:04:26', 5, 'tarjeta', 'pendiente'),
(2, 1, 7, 1, 12000.00, 12000.00, '2025-05-01 05:04:26', 5, 'tarjeta', 'pendiente'),
(3, 1, 13, 8, 6000.00, 48000.00, '2025-05-01 05:04:26', 5, 'tarjeta', 'pendiente'),
(4, 2, 8, 5, 15000.00, 75000.00, '2025-05-26 15:23:23', 1, 'contraentrega', 'pendiente'),
(5, 2, 2, 2, 3500.00, 7000.00, '2025-05-26 15:23:23', 1, 'contraentrega', 'pendiente'),
(6, 2, 1, 2, 12000.00, 24000.00, '2025-05-26 15:23:23', 1, 'contraentrega', 'pendiente');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_alimentacion_especies`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_alimentacion_especies` (
`nombre_especie` varchar(50)
,`tipo_alimento` varchar(100)
,`total_consumido` decimal(32,2)
,`fecha_ultima_alimentacion` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_animales_especies`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_animales_especies` (
`id_animal` int(11)
,`nombre_cientifico` varchar(100)
,`nombre_comun` varchar(100)
,`nombre_especie` varchar(50)
,`edad` int(11)
,`ubicacion` varchar(255)
,`estado` enum('Sano','Enfermo','Recuperación')
,`fecha_registro` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_auditoria`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_auditoria` (
`id_alerta` int(11)
,`categoria` varchar(20)
,`descripcion_evento` mediumtext
,`fecha` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_costos_animales`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_costos_animales` (
`nombre_comun` varchar(100)
,`tipo_costo` enum('Alimentación','Salud','Mantenimiento','Salarios','Otro')
,`descripcion` text
,`monto` decimal(10,2)
,`fecha` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_costos_totales`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_costos_totales` (
`tipo_costo` enum('Alimentación','Salud','Mantenimiento','Salarios','Otro')
,`total_gastado` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_empleados_costos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_empleados_costos` (
`id_empleado` int(11)
,`nombre` varchar(100)
,`rol` varchar(50)
,`tipo_costo` enum('Alimentación','Salud','Mantenimiento','Salarios','Otro')
,`monto` decimal(10,2)
,`fecha` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_inventario_proveedores`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_inventario_proveedores` (
`id_producto` int(11)
,`nombre_producto` varchar(100)
,`cantidad` decimal(10,2)
,`unidad_medida` varchar(20)
,`fecha_ingreso` date
,`proveedor` varchar(100)
,`telefono` varchar(20)
,`direccion` varchar(255)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_produccion_ventas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_produccion_ventas` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_reportes_salud`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_reportes_salud` (
`id_reporte` int(11)
,`usuario` varchar(100)
,`nombre` varchar(100)
,`tipo` enum('Planta','Animal')
,`diagnostico` text
,`fecha_reporte` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_tratamientos_reportes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_tratamientos_reportes` (
`id_tratamiento` int(11)
,`tipo` enum('Planta','Animal')
,`nombre` varchar(100)
,`descripcion` text
,`fecha_inicio` date
,`fecha_fin` date
,`resultado` enum('Exitoso','En Proceso','Fallido')
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_vacunaciones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_vacunaciones` (
`id_vacunacion` int(11)
,`animal` varchar(100)
,`vacuna` varchar(100)
,`fecha_aplicacion` date
,`proxima_dosis` date
,`dosis` varchar(50)
,`id_empleado` int(11)
,`observaciones` text
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_alimentacion_especies`
--
DROP TABLE IF EXISTS `vista_alimentacion_especies`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_alimentacion_especies`  AS SELECT `e`.`nombre_especie` AS `nombre_especie`, `al`.`tipo_alimento` AS `tipo_alimento`, sum(`al`.`cantidad_gramos`) AS `total_consumido`, `al`.`fecha_ultima_alimentacion` AS `fecha_ultima_alimentacion` FROM (`alimentacion` `al` join `especies` `e` on(`al`.`id_especie` = `e`.`id_especie`)) GROUP BY `e`.`nombre_especie`, `al`.`tipo_alimento`, `al`.`fecha_ultima_alimentacion` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_animales_especies`
--
DROP TABLE IF EXISTS `vista_animales_especies`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_animales_especies`  AS SELECT `a`.`id_animal` AS `id_animal`, `a`.`nombre_cientifico` AS `nombre_cientifico`, `a`.`nombre_comun` AS `nombre_comun`, `e`.`nombre_especie` AS `nombre_especie`, `a`.`edad` AS `edad`, `a`.`ubicacion` AS `ubicacion`, `a`.`estado` AS `estado`, `a`.`fecha_registro` AS `fecha_registro` FROM (`animales` `a` join `especies` `e` on(`a`.`id_especie` = `e`.`id_especie`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_auditoria`
--
DROP TABLE IF EXISTS `vista_auditoria`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_auditoria`  AS SELECT `alertas`.`id_alerta` AS `id_alerta`, `alertas`.`categoria` AS `categoria`, CASE WHEN `alertas`.`categoria` = 'animal' THEN concat('Evento relacionado con un animal: ',`alertas`.`mensaje`) WHEN `alertas`.`categoria` = 'planta' THEN concat('Evento relacionado con una planta: ',`alertas`.`mensaje`) WHEN `alertas`.`categoria` = 'venta' THEN concat('Transacción de venta: ',`alertas`.`mensaje`) WHEN `alertas`.`categoria` = 'empleado' THEN concat('Acción relacionada con un empleado: ',`alertas`.`mensaje`) WHEN `alertas`.`categoria` = 'usuario' THEN concat('Acción relacionada con un usuario: ',`alertas`.`mensaje`) WHEN `alertas`.`categoria` = 'inventario' THEN concat('Movimiento de inventario: ',`alertas`.`mensaje`) WHEN `alertas`.`categoria` = 'salud' THEN concat('Cambio de estado de salud: ',`alertas`.`mensaje`) WHEN `alertas`.`categoria` in ('vacunas','vacunacion') THEN concat('Registro de vacunación: ',`alertas`.`mensaje`) ELSE concat('Registro general del sistema: ',`alertas`.`mensaje`) END AS `descripcion_evento`, `alertas`.`fecha` AS `fecha` FROM `alertas` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_costos_animales`
--
DROP TABLE IF EXISTS `vista_costos_animales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_costos_animales`  AS SELECT `a`.`nombre_comun` AS `nombre_comun`, `c`.`tipo_costo` AS `tipo_costo`, `c`.`descripcion` AS `descripcion`, `c`.`monto` AS `monto`, `c`.`fecha` AS `fecha` FROM (`costos` `c` join `animales` `a` on(`c`.`id_animal` = `a`.`id_animal`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_costos_totales`
--
DROP TABLE IF EXISTS `vista_costos_totales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_costos_totales`  AS SELECT `costos`.`tipo_costo` AS `tipo_costo`, sum(`costos`.`monto`) AS `total_gastado` FROM `costos` GROUP BY `costos`.`tipo_costo` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_empleados_costos`
--
DROP TABLE IF EXISTS `vista_empleados_costos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_empleados_costos`  AS SELECT `e`.`id_empleado` AS `id_empleado`, `e`.`nombre` AS `nombre`, `e`.`rol` AS `rol`, `c`.`tipo_costo` AS `tipo_costo`, `c`.`monto` AS `monto`, `c`.`fecha` AS `fecha` FROM (`empleados` `e` left join `costos` `c` on(`e`.`id_empleado` = `c`.`id_empleado`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_inventario_proveedores`
--
DROP TABLE IF EXISTS `vista_inventario_proveedores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_inventario_proveedores`  AS SELECT `i`.`id_producto` AS `id_producto`, `i`.`nombre_producto` AS `nombre_producto`, `i`.`cantidad` AS `cantidad`, `i`.`unidad_medida` AS `unidad_medida`, `i`.`fecha_ingreso` AS `fecha_ingreso`, `p`.`nombre` AS `proveedor`, `p`.`telefono` AS `telefono`, `p`.`direccion` AS `direccion` FROM (`inventario` `i` left join `proveedores` `p` on(`i`.`id_proveedor` = `p`.`id_proveedor`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_produccion_ventas`
--
DROP TABLE IF EXISTS `vista_produccion_ventas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_produccion_ventas`  AS SELECT `p`.`id_produccion` AS `id_produccion`, `a`.`nombre_comun` AS `animal`, `p`.`tipo_produccion` AS `tipo_produccion`, `p`.`cantidad` AS `cantidad`, `p`.`fecha_recoleccion` AS `fecha_recoleccion`, `v`.`cantidad` AS `cantidad_vendida`, `v`.`precio_total` AS `precio_total`, `v`.`fecha_venta` AS `fecha_venta`, `v`.`comprador` AS `comprador` FROM ((`produccion` `p` left join `ventas` `v` on(`p`.`id_produccion` = `v`.`id_produccion`)) left join `animales` `a` on(`p`.`id_animal` = `a`.`id_animal`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_reportes_salud`
--
DROP TABLE IF EXISTS `vista_reportes_salud`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_reportes_salud`  AS SELECT `r`.`id_reporte` AS `id_reporte`, `u`.`nombre` AS `usuario`, CASE WHEN `r`.`tipo` = 'Animal' THEN `a`.`nombre_comun` ELSE `p`.`nombre_comun` END AS `nombre`, `r`.`tipo` AS `tipo`, `r`.`diagnostico` AS `diagnostico`, `r`.`fecha_reporte` AS `fecha_reporte` FROM (((`reportes` `r` left join `usuarios` `u` on(`r`.`id_usuario` = `u`.`id_usuario`)) left join `animales` `a` on(`r`.`id_animal` = `a`.`id_animal`)) left join `plantas` `p` on(`r`.`id_planta` = `p`.`id_planta`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_tratamientos_reportes`
--
DROP TABLE IF EXISTS `vista_tratamientos_reportes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_tratamientos_reportes`  AS SELECT `t`.`id_tratamiento` AS `id_tratamiento`, `r`.`tipo` AS `tipo`, CASE WHEN `r`.`tipo` = 'Animal' THEN `a`.`nombre_comun` ELSE `p`.`nombre_comun` END AS `nombre`, `t`.`descripcion` AS `descripcion`, `t`.`fecha_inicio` AS `fecha_inicio`, `t`.`fecha_fin` AS `fecha_fin`, `t`.`resultado` AS `resultado` FROM (((`tratamientos` `t` join `reportes` `r` on(`t`.`id_reporte` = `r`.`id_reporte`)) left join `animales` `a` on(`r`.`id_animal` = `a`.`id_animal`)) left join `plantas` `p` on(`r`.`id_planta` = `p`.`id_planta`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_vacunaciones`
--
DROP TABLE IF EXISTS `vista_vacunaciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_vacunaciones`  AS SELECT `v`.`id_vacunacion` AS `id_vacunacion`, `a`.`nombre_comun` AS `animal`, `vac`.`nombre` AS `vacuna`, `v`.`fecha_aplicacion` AS `fecha_aplicacion`, `v`.`proxima_dosis` AS `proxima_dosis`, `v`.`dosis` AS `dosis`, `v`.`id_empleado` AS `id_empleado`, `v`.`observaciones` AS `observaciones` FROM ((`vacunacion` `v` join `animales` `a` on(`v`.`id_animal` = `a`.`id_animal`)) join `vacunas` `vac` on(`v`.`id_vacuna` = `vac`.`id_vacuna`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id_alerta`,`categoria`);

--
-- Indices de la tabla `alimentacion`
--
ALTER TABLE `alimentacion`
  ADD PRIMARY KEY (`id_alimentacion`),
  ADD KEY `id_especie` (`id_especie`);

--
-- Indices de la tabla `animales`
--
ALTER TABLE `animales`
  ADD PRIMARY KEY (`id_animal`),
  ADD KEY `id_especie` (`id_especie`),
  ADD KEY `idx_animales_estado` (`estado`);

--
-- Indices de la tabla `auditorias`
--
ALTER TABLE `auditorias`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `idx_auditorias_fecha` (`fecha_hora`),
  ADD KEY `idx_auditorias_tabla` (`tabla_afectada`),
  ADD KEY `idx_auditorias_accion` (`accion`);

--
-- Indices de la tabla `bloqueo_usuarios`
--
ALTER TABLE `bloqueo_usuarios`
  ADD PRIMARY KEY (`id_bloqueo`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `carrito_compras`
--
ALTER TABLE `carrito_compras`
  ADD PRIMARY KEY (`id_item`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `costos`
--
ALTER TABLE `costos`
  ADD PRIMARY KEY (`id_costo`),
  ADD KEY `id_empleado` (`id_empleado`),
  ADD KEY `id_animal` (`id_animal`),
  ADD KEY `id_especie` (`id_especie`);

--
-- Indices de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id_empleado`);

--
-- Indices de la tabla `especies`
--
ALTER TABLE `especies`
  ADD PRIMARY KEY (`id_especie`);

--
-- Indices de la tabla `historial_estado_salud`
--
ALTER TABLE `historial_estado_salud`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_animal` (`id_animal`),
  ADD KEY `id_planta` (`id_planta`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `inventario_productos`
--
ALTER TABLE `inventario_productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_disponible` (`disponible`);

--
-- Indices de la tabla `log_accesos`
--
ALTER TABLE `log_accesos`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `log_accesos_clientes`
--
ALTER TABLE `log_accesos_clientes`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `id_usuario` (`id_cliente`);

--
-- Indices de la tabla `plantas`
--
ALTER TABLE `plantas`
  ADD PRIMARY KEY (`id_planta`),
  ADD KEY `idx_plantas_ubicacion` (`ubicacion`);

--
-- Indices de la tabla `produccion`
--
ALTER TABLE `produccion`
  ADD PRIMARY KEY (`id_produccion`),
  ADD KEY `id_animal` (`id_animal`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_planta` (`id_planta`),
  ADD KEY `id_animal` (`id_animal`);

--
-- Indices de la tabla `tratamientos`
--
ALTER TABLE `tratamientos`
  ADD PRIMARY KEY (`id_tratamiento`),
  ADD KEY `id_reporte` (`id_reporte`);

--
-- Indices de la tabla `ubicacion_georreferenciada`
--
ALTER TABLE `ubicacion_georreferenciada`
  ADD PRIMARY KEY (`id_ubicacion`),
  ADD KEY `id_animal` (`id_animal`),
  ADD KEY `id_planta` (`id_planta`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `idx_usuarios_correo` (`correo`);

--
-- Indices de la tabla `vacunacion`
--
ALTER TABLE `vacunacion`
  ADD PRIMARY KEY (`id_vacunacion`),
  ADD KEY `id_animal` (`id_animal`),
  ADD KEY `id_vacuna` (`id_vacuna`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `vacunas`
--
ALTER TABLE `vacunas`
  ADD PRIMARY KEY (`id_vacuna`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id_alerta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT de la tabla `alimentacion`
--
ALTER TABLE `alimentacion`
  MODIFY `id_alimentacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `animales`
--
ALTER TABLE `animales`
  MODIFY `id_animal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `auditorias`
--
ALTER TABLE `auditorias`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `bloqueo_usuarios`
--
ALTER TABLE `bloqueo_usuarios`
  MODIFY `id_bloqueo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `carrito_compras`
--
ALTER TABLE `carrito_compras`
  MODIFY `id_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `costos`
--
ALTER TABLE `costos`
  MODIFY `id_costo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de la tabla `especies`
--
ALTER TABLE `especies`
  MODIFY `id_especie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `historial_estado_salud`
--
ALTER TABLE `historial_estado_salud`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `inventario_productos`
--
ALTER TABLE `inventario_productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `log_accesos`
--
ALTER TABLE `log_accesos`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT de la tabla `log_accesos_clientes`
--
ALTER TABLE `log_accesos_clientes`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `plantas`
--
ALTER TABLE `plantas`
  MODIFY `id_planta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `produccion`
--
ALTER TABLE `produccion`
  MODIFY `id_produccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id_reporte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `tratamientos`
--
ALTER TABLE `tratamientos`
  MODIFY `id_tratamiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `ubicacion_georreferenciada`
--
ALTER TABLE `ubicacion_georreferenciada`
  MODIFY `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `vacunacion`
--
ALTER TABLE `vacunacion`
  MODIFY `id_vacunacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `vacunas`
--
ALTER TABLE `vacunas`
  MODIFY `id_vacuna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alimentacion`
--
ALTER TABLE `alimentacion`
  ADD CONSTRAINT `alimentacion_ibfk_1` FOREIGN KEY (`id_especie`) REFERENCES `especies` (`id_especie`);

--
-- Filtros para la tabla `animales`
--
ALTER TABLE `animales`
  ADD CONSTRAINT `animales_ibfk_1` FOREIGN KEY (`id_especie`) REFERENCES `especies` (`id_especie`);

--
-- Filtros para la tabla `bloqueo_usuarios`
--
ALTER TABLE `bloqueo_usuarios`
  ADD CONSTRAINT `bloqueo_usuarios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `carrito_compras`
--
ALTER TABLE `carrito_compras`
  ADD CONSTRAINT `carrito_compras_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `inventario_productos` (`id_producto`) ON DELETE CASCADE,
  ADD CONSTRAINT `carrito_compras_ibfk_3` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);

--
-- Filtros para la tabla `costos`
--
ALTER TABLE `costos`
  ADD CONSTRAINT `costos_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE SET NULL,
  ADD CONSTRAINT `costos_ibfk_2` FOREIGN KEY (`id_animal`) REFERENCES `animales` (`id_animal`) ON DELETE SET NULL,
  ADD CONSTRAINT `costos_ibfk_3` FOREIGN KEY (`id_especie`) REFERENCES `especies` (`id_especie`) ON DELETE SET NULL;

--
-- Filtros para la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD CONSTRAINT `detalles_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalles_pedido_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `inventario_productos` (`id_producto`);

--
-- Filtros para la tabla `historial_estado_salud`
--
ALTER TABLE `historial_estado_salud`
  ADD CONSTRAINT `historial_estado_salud_ibfk_1` FOREIGN KEY (`id_animal`) REFERENCES `animales` (`id_animal`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_estado_salud_ibfk_2` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE SET NULL;

--
-- Filtros para la tabla `log_accesos`
--
ALTER TABLE `log_accesos`
  ADD CONSTRAINT `log_accesos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `log_accesos_clientes`
--
ALTER TABLE `log_accesos_clientes`
  ADD CONSTRAINT `log_accesos_clientes_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);

--
-- Filtros para la tabla `produccion`
--
ALTER TABLE `produccion`
  ADD CONSTRAINT `produccion_ibfk_1` FOREIGN KEY (`id_animal`) REFERENCES `animales` (`id_animal`) ON DELETE SET NULL;

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `reportes_ibfk_2` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportes_ibfk_3` FOREIGN KEY (`id_animal`) REFERENCES `animales` (`id_animal`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tratamientos`
--
ALTER TABLE `tratamientos`
  ADD CONSTRAINT `tratamientos_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reportes` (`id_reporte`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ubicacion_georreferenciada`
--
ALTER TABLE `ubicacion_georreferenciada`
  ADD CONSTRAINT `ubicacion_georreferenciada_ibfk_1` FOREIGN KEY (`id_animal`) REFERENCES `animales` (`id_animal`) ON DELETE CASCADE,
  ADD CONSTRAINT `ubicacion_georreferenciada_ibfk_2` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`) ON DELETE CASCADE;

--
-- Filtros para la tabla `vacunacion`
--
ALTER TABLE `vacunacion`
  ADD CONSTRAINT `vacunacion_ibfk_1` FOREIGN KEY (`id_animal`) REFERENCES `animales` (`id_animal`) ON DELETE CASCADE,
  ADD CONSTRAINT `vacunacion_ibfk_2` FOREIGN KEY (`id_vacuna`) REFERENCES `vacunas` (`id_vacuna`) ON DELETE CASCADE,
  ADD CONSTRAINT `vacunacion_ibfk_3` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
