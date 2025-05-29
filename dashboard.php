<?php
session_start();
require_once 'conexion/conexion.php';

// Verificar sesión y rol
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] != 'Administrador') {
    header("Location: iniciar_sesion.php");
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
    :root {
        --primary-color: #4CAF50;
        --primary-dark: #388E3C;
        --primary-light: #C8E6C9;
        --secondary-color: #8BC34A;
        --accent-color: #FFC107;
        --text-dark: #333;
        --text-light: #f5f5f5;
        --bg-light: #f9f9f9;
        --bg-dark: #2E7D32;
        --border-radius: 8px;
        --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--bg-light);
        color: var(--text-dark);
        line-height: 1.6;
    }

    /* Sidebar styles */
    .sidebar {
        min-height: 100vh;
        background-color: var(--bg-dark);
        padding: 20px 0;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.75);
        padding: 10px 15px;
        margin: 0 10px 5px 10px;
        border-radius: var(--border-radius);
        transition: var(--transition);
        display: flex;
        align-items: center;
    }

    .sidebar .nav-link:hover {
        color: rgba(255, 255, 255, 1);
        background-color: rgba(255, 255, 255, 0.1);
    }

    .sidebar .nav-link.active {
        color: white;
        background-color: var(--primary-color);
    }

    .sidebar .nav-link i {
        margin-right: 10px;
    }

    /* Main content */
    main {
        padding: 20px;
    }

    /* Stats Cards */
    .stat-card {
        background-color: white;
        border-left: 4px solid var(--primary-color);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        padding: 15px;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    /* Alerts */
    .alert-item {
        background-color: white;
        border-left: 3px solid var(--primary-color);
        padding: 10px 15px;
        margin-bottom: 10px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }

    .alert-item.critical {
        border-left-color: #dc3545;
    }

    /* Tables */
    .table-responsive {
        max-height: 400px;
        overflow-y: auto;
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 15px;
    }

    /* Page header */
    .border-bottom {
        border-bottom: 1px solid #dee2e6 !important;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sidebar {
            min-height: auto;
            position: relative;
        }
        
        .stat-card {
            margin-bottom: 15px;
        }
    }
</style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
    <!-- Sidebar -->
            <?php
            // Mostrar sidebar según el tipo de usuario
            switch ($_SESSION['tipo_usuario']) {
                case 'administrador':
                    include 'includes/sidebar_admin.php';
                    break;
                case 'veterinario':
                    include 'includes/sidebar_veterinario.php';
                    break;
                case 'empleado':
                    include 'includes/sidebar_investigador.php';
                    break;
                // Agrega más casos según tus tipos de usuario
                default:
                    include 'includes/sidebar.php';
                    break;
            }
            ?>

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