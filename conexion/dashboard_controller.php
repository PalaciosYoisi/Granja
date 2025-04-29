<?php
// dashboard_controller.php

// Configuración de la conexión a la base de datos
$host = 'localhost';
$dbname = 'granja';
$username = 'root';
$password = '';

try {
    // Crear conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener todos los datos necesarios
    $data = [];
    
    // 1. Actividad reciente (accesos)
    $queryAccesos = "SELECT u.nombre, l.fecha_intento, l.exito 
                    FROM log_accesos l
                    JOIN usuarios u ON l.id_usuario = u.id_usuario
                    ORDER BY l.fecha_intento DESC LIMIT 3";
    $data['accesos'] = $pdo->query($queryAccesos)->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Animales activos
    $queryAnimales = "SELECT 
                        COUNT(*) as total_animales,
                        SUM(CASE WHEN estado = 'Enfermo' THEN 1 ELSE 0 END) as enfermos
                      FROM animales";
    $data['animales'] = $pdo->query($queryAnimales)->fetch(PDO::FETCH_ASSOC);
    
    // 3. Alertas recientes
    $queryAlertas = "SELECT * FROM alertas 
                    ORDER BY fecha DESC LIMIT 2";
    $data['alertas'] = $pdo->query($queryAlertas)->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Producción semanal
    $queryProduccion = "SELECT 
                          SUM(CASE WHEN tipo_produccion = 'Huevos' THEN cantidad ELSE 0 END) as huevos,
                          SUM(CASE WHEN tipo_produccion = 'Leche' THEN cantidad ELSE 0 END) as leche
                        FROM produccion
                        WHERE fecha_recoleccion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $data['produccion'] = $pdo->query($queryProduccion)->fetch(PDO::FETCH_ASSOC);
    
    // 5. Salud (vacunas y tratamientos)
    $querySalud = "SELECT 
                     (SELECT COUNT(*) FROM vacunacion 
                      WHERE fecha_aplicacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as vacunas,
                     (SELECT COUNT(*) FROM tratamientos 
                      WHERE resultado = 'En Proceso') as tratamientos";
    $data['salud'] = $pdo->query($querySalud)->fetch(PDO::FETCH_ASSOC);
    
    // 6. Inventario (porcentaje en stock)
    $queryInventario = "SELECT 
                          (SUM(CASE WHEN cantidad > 0 THEN 1 ELSE 0 END) / COUNT(*) * 100 as porcentaje_stock
                        FROM inventario";
    $data['inventario'] = $pdo->query($queryInventario)->fetch(PDO::FETCH_ASSOC);
    
    // 7. Eventos recientes
    $queryEventos = "SELECT COUNT(*) as eventos_hoy FROM log_accesos 
                    WHERE DATE(fecha_intento) = CURDATE()";
    $data['eventos'] = $pdo->query($queryEventos)->fetch(PDO::FETCH_ASSOC);
    
    // Extraer todas las variables del array $data al ámbito global
    extract($data);
    
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Incluir la vista del dashboard
include '../dashboard.php';
?>