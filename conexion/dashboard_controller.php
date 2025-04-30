<?php
// Iniciar sesión al principio
session_start();

$host = 'localhost';
$dbname = 'granja';
$username = 'root';
$password = '';

try {
    // Crear conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $data = [];
    
    // Consulta de accesos
    $queryAccesos = "SELECT u.nombre, l.fecha_intento, l.exito 
                    FROM log_accesos l
                    JOIN usuarios u ON l.id_usuario = u.id_usuario
                    ORDER BY l.fecha_intento DESC LIMIT 5";
    $stmt = $pdo->query($queryAccesos);
    $data['accesos'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Consulta de animales
    $queryAnimales = "SELECT 
                        COUNT(*) as total_animales,
                        SUM(CASE WHEN estado = 'Enfermo' THEN 1 ELSE 0 END) as enfermos,
                        SUM(CASE WHEN estado = 'Recuperación' THEN 1 ELSE 0 END) as recuperacion
                      FROM animales";
    $stmt = $pdo->query($queryAnimales);
    $data['animales'] = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : ['total_animales' => 0, 'enfermos' => 0, 'recuperacion' => 0];
    
    // Consulta de alertas
    $queryAlertas = "SELECT * FROM alertas 
                    ORDER BY fecha DESC LIMIT 5";
    $stmt = $pdo->query($queryAlertas);
    $data['alertas'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Consulta de producción
    $queryProduccion = "SELECT 
                          SUM(CASE WHEN tipo_produccion = 'Huevos' THEN cantidad ELSE 0 END) as huevos,
                          SUM(CASE WHEN tipo_produccion = 'Leche' THEN cantidad ELSE 0 END) as leche,
                          SUM(CASE WHEN tipo_produccion = 'Carne' THEN cantidad ELSE 0 END) as carne
                        FROM produccion";
    $stmt = $pdo->query($queryProduccion);
    $data['produccion'] = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : ['huevos' => 0, 'leche' => 0, 'carne' => 0];
    
    // Consulta de salud
    $querySalud = "SELECT 
                     (SELECT COUNT(*) FROM vacunacion) as vacunas,
                     (SELECT COUNT(*) FROM tratamientos WHERE resultado = 'En Proceso') as tratamientos";
    $stmt = $pdo->query($querySalud);
    $data['salud'] = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : ['vacunas' => 0, 'tratamientos' => 0];
    
    // Consulta de inventario
    $queryInventario = "SELECT 
                          (SUM(CASE WHEN cantidad > 0 THEN 1 ELSE 0 END) / COUNT(*) * 100) as porcentaje_stock
                        FROM inventario";
    $stmt = $pdo->query($queryInventario);
    $data['inventario'] = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : ['porcentaje_stock' => 0];
    
    // Consulta de eventos
    $queryEventos = "SELECT COUNT(*) as eventos_hoy FROM log_accesos 
                    WHERE DATE(fecha_intento) = CURDATE()";
    $stmt = $pdo->query($queryEventos);
    $data['eventos'] = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : ['eventos_hoy' => 0];
    
    // Cerrar conexión
    $pdo = null;
    
    // Extraer variables para la vista
    extract($data);
    
} catch (PDOException $e) {
    // Manejar error sin detener completamente la ejecución
    $error = "Error de conexión a la base de datos: " . $e->getMessage();
    // Establecer valores por defecto
    $accesos = [];
    $animales = ['total_animales' => 0, 'enfermos' => 0, 'recuperacion' => 0];
    $alertas = [];
    $produccion = ['huevos' => 0, 'leche' => 0, 'carne' => 0];
    $salud = ['vacunas' => 0, 'tratamientos' => 0];
    $inventario = ['porcentaje_stock' => 0];
    $eventos = ['eventos_hoy' => 0];
}

// Incluir la vista del dashboard
require __DIR__ . '../dashboard.php';
?>