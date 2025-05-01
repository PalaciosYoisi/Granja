<?php
session_start();
require_once 'conexion/conexion.php';

// Verificar sesión y rol
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] != 'Veterinario') {
    header("Location: index.php");
    exit();
}

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
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="animales.php">
                            <i class="bi bi-egg-fried me-2"></i>Animales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="vacunacion.php">
                            <i class="bi bi-eyedropper me-2"></i>Vacunación
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportes_salud.php">
                            <i class="bi bi-clipboard-pulse me-2"></i>Reportes Salud
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventario.php">
                            <i class="bi bi-box-seam me-2"></i>Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="alertas.php">
                            <i class="bi bi-exclamation-triangle me-2"></i>Alertas
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