<?php
session_start();
require_once 'conexion/conexion.php';

// Verificar sesión y rol
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] != 'Administrador') {
    header("Location: index.php");
    exit();
}

// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener estadísticas generales
$animales = $db->query("SELECT COUNT(*) as total FROM animales");
$animales = $animales ? $animales->fetch_assoc()['total'] : 0;

$plantas = $db->query("SELECT COUNT(*) as total FROM plantas");
$plantas = $plantas ? $plantas->fetch_assoc()['total'] : 0;

$ventas = $db->query("SELECT COUNT(*) as total FROM ventas");
$ventas = $ventas ? $ventas->fetch_assoc()['total'] : 0;

$alertas = $db->query("SELECT COUNT(*) as total FROM alertas");
$alertas = $alertas ? $alertas->fetch_assoc()['total'] : 0;

// Obtener últimas alertas
$ultimas_alertas_result = $db->query("SELECT * FROM alertas ORDER BY fecha DESC LIMIT 5");
$ultimas_alertas = $ultimas_alertas_result ? $ultimas_alertas_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener producción reciente
$produccion_reciente_result = $db->query("SELECT p.*, a.nombre_comun 
                                          FROM produccion p 
                                          JOIN animales a ON p.id_animal = a.id_animal 
                                          ORDER BY fecha_recoleccion DESC LIMIT 5");
$produccion_reciente = $produccion_reciente_result ? $produccion_reciente_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener ventas recientes
$ventas_recientes_result = $db->query("SELECT v.*, p.nombre 
                                       FROM ventas v 
                                       JOIN inventario_productos p ON v.id_producto = p.id_producto 
                                       ORDER BY fecha_venta DESC LIMIT 5");
$ventas_recientes = $ventas_recientes_result ? $ventas_recientes_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
        }
        .sidebar .nav-link:hover {
            color: rgba(255, 255, 255, 1);
        }
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .stat-card {
            border-left: 4px solid #0d6efd;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .alert-item {
            border-left: 3px solid #0d6efd;
        }
        .alert-item.critical {
            border-left-color: #dc3545;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar py-3">
                <div class="text-center mb-4">
                    <h4 class="text-white">Granja San José</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard - Administrador
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="animales.php">
                            <i class="bi bi-egg-fried me-2"></i>Animales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="plantas.php">
                            <i class="bi bi-flower2 me-2"></i>Plantas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventario.php">
                            <i class="bi bi-box-seam me-2"></i>Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ventas.php">
                            <i class="bi bi-cash-coin me-2"></i>Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="alertas.php">
                            <i class="bi bi-exclamation-triangle me-2"></i>Alertas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportes.php">
                            <i class="bi bi-clipboard-data me-2"></i>Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">
                            <i class="bi bi-people me-2"></i>Usuarios
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="conexion/logout2.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Animales</h6>
                                <h3 class="mb-0"><?php echo $animales; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Plantas</h6>
                                <h3 class="mb-0"><?php echo $plantas; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Ventas</h6>
                                <h3 class="mb-0"><?php echo $ventas; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Alertas</h6>
                                <h3 class="mb-0"><?php echo $alertas; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>Últimas Alertas</h5>
                        <ul>
                            <?php foreach ($ultimas_alertas as $alerta): ?>
                                <li><?php echo htmlspecialchars($alerta['mensaje'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>