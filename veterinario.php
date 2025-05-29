<?php
session_start();
require_once 'conexion/conexion.php';

// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener estadísticas relevantes
$animales_enfermos_result = $db->query("SELECT COUNT(*) as total FROM animales WHERE estado = 'Enfermo'");
$animales_enfermos = $animales_enfermos_result ? $animales_enfermos_result->fetch_assoc()['total'] : 0;

$vacunas_pendientes_result = $db->query("SELECT COUNT(*) as total FROM vacunacion WHERE proxima_dosis <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$vacunas_pendientes = $vacunas_pendientes_result ? $vacunas_pendientes_result->fetch_assoc()['total'] : 0;

$alertas_animales_result = $db->query("SELECT COUNT(*) as total FROM alertas WHERE categoria IN ('animal', 'salud', 'vacunacion')");
$alertas_animales = $alertas_animales_result ? $alertas_animales_result->fetch_assoc()['total'] : 0;

// Obtener últimas alertas relevantes
$ultimas_alertas_result = $db->query("SELECT * FROM alertas 
                                      WHERE categoria IN ('animal', 'salud', 'vacunacion') 
                                      ORDER BY fecha DESC LIMIT 5");
$ultimas_alertas = $ultimas_alertas_result ? $ultimas_alertas_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener animales enfermos
$animales_enfermos_lista_result = $db->query("SELECT a.*, e.nombre_especie 
                                              FROM animales a 
                                              JOIN especies e ON a.id_especie = e.id_especie 
                                              WHERE a.estado = 'Enfermo' 
                                              ORDER BY a.fecha_registro DESC LIMIT 5");
$animales_enfermos_lista = $animales_enfermos_lista_result ? $animales_enfermos_lista_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener próximas vacunaciones
$proximas_vacunas_result = $db->query("SELECT v.*, a.nombre_comun, vac.nombre as nombre_vacuna 
                                       FROM vacunacion v 
                                       JOIN animales a ON v.id_animal = a.id_animal 
                                       JOIN vacunas vac ON v.id_vacuna = vac.id_vacuna 
                                       WHERE v.proxima_dosis <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                                       ORDER BY v.proxima_dosis ASC LIMIT 5");
$proximas_vacunas = $proximas_vacunas_result ? $proximas_vacunas_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Veterinario</title>
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
        background-color: var(--bg-dark);
        color: white;
        min-height: 100vh;
        padding: 20px;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
    }

    .sidebar h4 {
        color: white;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 20px;
        text-align: center;
    }

    .sidebar .nav-link {
        color: rgba(255,255,255,0.8);
        padding: 10px 15px;
        margin-bottom: 5px;
        border-radius: var(--border-radius);
        transition: var(--transition);
        display: flex;
        align-items: center;
    }

    .sidebar .nav-link:hover {
        color: white;
        background-color: rgba(255,255,255,0.1);
    }

    .sidebar .nav-link.active {
        background-color: var(--primary-color);
        color: white;
    }

    .sidebar .nav-link i {
        margin-right: 10px;
        font-size: 18px;
    }

    .main-content {
        margin-left: 250px;
        padding: 20px;
    }

    /* Header styles */
    .border-bottom {
        padding-bottom: 15px;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    /* Card styles */
    .card {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-bottom: 20px;
        transition: var(--transition);
        border: none;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        background-color: white;
        border-bottom: 1px solid #eee;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--primary-dark);
        margin: 0;
    }

    .card-body {
        padding: 20px;
    }

    /* Stat card styles */
    .stat-card {
        border-left: 4px solid var(--primary-color);
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    /* Table styles */
    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th, .table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table th {
        background-color: var(--primary-light);
        color: var(--primary-dark);
        font-weight: 600;
    }

    .table tr:hover {
        background-color: #f5f5f5;
    }

    /* Alert item styles */
    .alert-item {
        border-left: 3px solid var(--primary-color);
        padding: 15px;
    }

    .alert-item.critical {
        border-left-color: #f44336;
    }

    /* Button styles */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        font-size: 14px;
    }

    .btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        color: white;
    }

    .btn i {
        font-size: 16px;
    }

    .btn-outline-primary {
        background-color: transparent;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-outline-danger {
        background-color: transparent;
        border: 1px solid #f44336;
        color: #f44336;
    }

    .btn-outline-danger:hover {
        background-color: #f44336;
        color: white;
    }

    .btn-outline-success {
        background-color: transparent;
        border: 1px solid #4CAF50;
        color: #4CAF50;
    }

    .btn-outline-success:hover {
        background-color: #4CAF50;
        color: white;
    }

    .btn-danger {
        background-color: #f44336;
        color: white;
    }

    .btn-danger:hover {
        background-color: #d32f2f;
        color: white;
    }

    /* Badge styles */
    .badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .bg-danger {
        background-color: #f44336;
        color: white;
    }

    .bg-warning {
        background-color: #FFC107;
        color: var(--text-dark);
    }

    /* List group styles */
    .list-group-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            position: relative;
            min-height: auto;
        }
        
        .main-content {
            margin-left: 0;
        }
        
        .table {
            display: block;
            overflow-x: auto;
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
                case 'Administrador':
                    include 'includes/sidebar_admin.php';
                    break;
                case 'Veterinario':
                    include 'includes/sidebar_veterinario.php';
                    break;
                case 'Investigador':
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
                    <h1 class="h2">Dashboard Veterinario</h1>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Animales Enfermos</h6>
                                <h3 class="mb-0"><?php echo $animales_enfermos; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Vacunas Pendientes</h6>
                                <h3 class="mb-0"><?php echo $vacunas_pendientes; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Alertas Recientes</h6>
                                <h3 class="mb-0"><?php echo $alertas_animales; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts and Sick Animals -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Alertas Recientes</h5>
                                <a href="alertas.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($ultimas_alertas as $alerta): ?>
                                        <div class="list-group-item alert-item <?php echo ($alerta['categoria'] == 'salud') ? 'critical' : ''; ?>">
                                            <h6 class="mb-1"><?php echo ucfirst($alerta['categoria']); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($alerta['mensaje'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <small><?php echo date('d/m/Y H:i', strtotime($alerta['fecha'])); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Animales Enfermos</h5>
                                <a href="animales.php?filter=Enfermo" class="btn btn-sm btn-outline-primary">Ver todos</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Especie</th>
                                                <th>Edad</th>
                                                <th>Ubicación</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($animales_enfermos_lista as $animal): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($animal['nombre_comun'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($animal['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo $animal['edad']; ?></td>
                                                    <td><?php echo htmlspecialchars($animal['ubicacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Vaccinations -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Próximas Vacunaciones</h5>
                                <a href="vacunacion.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Animal</th>
                                                <th>Vacuna</th>
                                                <th>Próxima Dosis</th>
                                                <th>Días Restantes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($proximas_vacunas as $vacuna): 
                                                $dias_restantes = floor((strtotime($vacuna['proxima_dosis']) - time()) / (60 * 60 * 24)); ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($vacuna['nombre_comun'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($vacuna['nombre_vacuna'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($vacuna['proxima_dosis'])); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $dias_restantes <= 3 ? 'bg-danger' : 'bg-warning'; ?>">
                                                            <?php echo $dias_restantes; ?> días
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="reportes_salud.php?action=add" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Nuevo Reporte
                                    </a>
                                    <a href="vacunacion.php?action=add" class="btn btn-outline-success">
                                        <i class="bi bi-plus-circle me-2"></i>Registrar Vacuna
                                    </a>
                                    <a href="animales.php?filter=Enfermo" class="btn btn-outline-danger">
                                        <i class="bi bi-clipboard-pulse me-2"></i>Ver Enfermos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>