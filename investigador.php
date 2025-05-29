<?php
session_start();
require_once 'conexion/conexion.php';

// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener estadísticas generales
$animales_result = $db->query("SELECT COUNT(*) as total FROM animales");
$animales = $animales_result ? $animales_result->fetch_assoc()['total'] : 0;

$plantas_result = $db->query("SELECT COUNT(*) as total FROM plantas");
$plantas = $plantas_result ? $plantas_result->fetch_assoc()['total'] : 0;

$especies_result = $db->query("SELECT COUNT(*) as total FROM especies");
$especies = $especies_result ? $especies_result->fetch_assoc()['total'] : 0;

$alertas_result = $db->query("SELECT COUNT(*) as total FROM alertas");
$alertas = $alertas_result ? $alertas_result->fetch_assoc()['total'] : 0;

// Obtener últimas alertas
$ultimas_alertas_result = $db->query("SELECT * FROM alertas ORDER BY fecha DESC LIMIT 5");
$ultimas_alertas = $ultimas_alertas_result ? $ultimas_alertas_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener producción reciente
$produccion_reciente_result = $db->query("SELECT p.*, a.nombre_comun 
                                          FROM produccion p 
                                          JOIN animales a ON p.id_animal = a.id_animal 
                                          ORDER BY fecha_recoleccion DESC LIMIT 5");
$produccion_reciente = $produccion_reciente_result ? $produccion_reciente_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener especies con más animales
$especies_populares_result = $db->query("SELECT e.nombre_especie, COUNT(a.id_animal) as cantidad 
                                         FROM especies e 
                                         LEFT JOIN animales a ON e.id_especie = a.id_especie 
                                         GROUP BY e.id_especie 
                                         ORDER BY cantidad DESC LIMIT 5");
$especies_populares = $especies_populares_result ? $especies_populares_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Investigador</title>
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

    /* Main content adjustments */
    main {
        background-color: var(--bg-light);
        padding: 20px;
    }

    /* Stats Cards */
    .stat-card {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 15px;
        transition: var(--transition);
        border-left: 4px solid var(--primary-color);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    /* Alert items */
    .alert-item {
        border-left: 3px solid var(--primary-color);
        padding: 10px 15px;
        margin-bottom: 5px;
        background-color: white;
        border-radius: var(--border-radius);
    }

    .alert-item.critical {
        border-left-color: #f44336;
    }

    /* Buttons */
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
    }

    .btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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

    .btn-outline-success {
        background-color: transparent;
        border: 1px solid var(--secondary-color);
        color: var(--secondary-color);
    }

    .btn-outline-success:hover {
        background-color: var(--secondary-color);
        color: white;
    }

    .btn-outline-info {
        background-color: transparent;
        border: 1px solid var(--accent-color);
        color: var(--accent-color);
    }

    .btn-outline-info:hover {
        background-color: var(--accent-color);
        color: var(--text-dark);
    }

    /* Cards */
    .card {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        transition: var(--transition);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #eee;
    }

    /* Tables */
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

    /* Progress bars */
    .progress {
        height: 24px;
        border-radius: 12px;
        background-color: #f0f0f0;
        overflow: hidden;
    }

    .progress-bar {
        background-color: var(--primary-color);
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        font-weight: 500;
    }

    /* Quick actions */
    .d-grid.gap-2 {
        display: grid;
        gap: 10px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            min-height: auto;
            position: relative;
        }
        
        main {
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
                default:
                    include 'includes/sidebar.php';
                    break;
            }
            ?> 
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Investigador</h1>
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
                                <h6 class="text-muted mb-2">Especies</h6>
                                <h3 class="mb-0"><?php echo $especies; ?></h3>
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

                <!-- Alerts and Production -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Últimas Alertas</h5>
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
                                <h5 class="mb-0">Producción Reciente</h5>
                                <a href="produccion.php" class="btn btn-sm btn-outline-primary">Ver todo</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Animal</th>
                                                <th>Tipo</th>
                                                <th>Cantidad</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($produccion_reciente as $prod): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($prod['nombre_comun'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($prod['tipo_produccion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo $prod['cantidad']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($prod['fecha_recoleccion'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Species Distribution -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Distribución de Especies</h5>
                                <a href="especies.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Especie</th>
                                                <th>Cantidad</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($especies_populares as $especie): 
                                                $porcentaje = $animales > 0 ? round(($especie['cantidad'] / $animales) * 100, 2) : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo $especie['cantidad']; ?></td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="width: <?php echo $porcentaje; ?>%" 
                                                                 aria-valuenow="<?php echo $porcentaje; ?>" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <?php echo $porcentaje; ?>%
                                                            </div>
                                                        </div>
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
                                    <a href="reportes.php?action=add" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Nuevo Reporte
                                    </a>
                                    <a href="especies.php" class="btn btn-outline-success">
                                        <i class="bi bi-diagram-3 me-2"></i>Ver Especies
                                    </a>
                                    <a href="produccion.php" class="btn btn-outline-info">
                                        <i class="bi bi-graph-up me-2"></i>Ver Producción
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