<?php
session_start();
require_once 'conexion/conexion.php';


// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener estadísticas relevantes
$plantas_enfermas_result = $db->query("SELECT COUNT(*) as total FROM plantas WHERE estado = 'Enfermo'");
$plantas_enfermas = $plantas_enfermas_result ? $plantas_enfermas_result->fetch_assoc()['total'] : 0;

$plantas_total_result = $db->query("SELECT COUNT(*) as total FROM plantas");
$plantas_total = $plantas_total_result ? $plantas_total_result->fetch_assoc()['total'] : 0;

$alertas_plantas_result = $db->query("SELECT COUNT(*) as total FROM alertas WHERE categoria IN ('planta', 'salud')");
$alertas_plantas = $alertas_plantas_result ? $alertas_plantas_result->fetch_assoc()['total'] : 0;

// Obtener últimas alertas relevantes
$ultimas_alertas_result = $db->query("SELECT * FROM alertas 
                                      WHERE categoria IN ('planta', 'salud') 
                                      ORDER BY fecha DESC LIMIT 5");
$ultimas_alertas = $ultimas_alertas_result ? $ultimas_alertas_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener plantas enfermas
$plantas_enfermas_lista_result = $db->query("SELECT * FROM plantas 
                                             WHERE estado = 'Enfermo' 
                                             ORDER BY fecha_registro DESC LIMIT 5");
$plantas_enfermas_lista = $plantas_enfermas_lista_result ? $plantas_enfermas_lista_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener tratamientos recientes
$tratamientos_recientes_result = $db->query("SELECT t.*, p.nombre_comun 
                                             FROM tratamientos t 
                                             JOIN reportes r ON t.id_reporte = r.id_reporte 
                                             JOIN plantas p ON r.id_planta = p.id_planta 
                                             WHERE r.tipo = 'Planta' 
                                             ORDER BY t.fecha_inicio DESC LIMIT 5");
$tratamientos_recientes = $tratamientos_recientes_result ? $tratamientos_recientes_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Botánico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
            <style>
/* botanico.css */
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

.stat-card {
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 20px;
    margin-bottom: 20px;
    transition: var(--transition);
    border-left: 4px solid var(--primary-color);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.alert-item {
    background-color: white;
    border-radius: var(--border-radius);
    margin-bottom: 10px;
    padding: 15px;
    border-left: 3px solid var(--primary-color);
    transition: var(--transition);
}

.alert-item.critical {
    border-left-color:rgb(228, 99, 99);
}

.treatment-card {
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 15px;
    margin-bottom: 15px;
    border-left: 4px solid var(--secondary-color);
    transition: var(--transition);
}

.treatment-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

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

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.bg-success {
    background-color:rgb(50, 214, 140);
    color:rgb(238, 246, 238);
}

.bg-danger {
    background-color:rgb(234, 92, 92);
    color:rgb(239, 230, 230);
}

.bg-warning {
    background-color:rgb(7, 7, 6);
    color:rgb(255, 252, 249);
}

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

.card {
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 20px;
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
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.card-body {
    padding: 20px;
}

.list-group {
    list-style: none;
    padding: 0;
    margin: 0;
}

.list-group-item {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    transition: var(--transition);
}

.list-group-item:hover {
    background-color: #f9f9f9;
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        min-height: auto;
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
                    <h1 class="h2">Dashboard Botánico</h1>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Plantas Enfermas</h6>
                                <h3 class="mb-0"><?php echo $plantas_enfermas; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Total Plantas</h6>
                                <h3 class="mb-0"><?php echo $plantas_total; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Alertas Recientes</h6>
                                <h3 class="mb-0"><?php echo $alertas_plantas; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts and Sick Plants -->
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
                                <h5 class="mb-0">Plantas Enfermas</h5>
                                <a href="plantas.php?filter=Enfermo" class="btn btn-sm btn-outline-primary">Ver todas</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Ubicación</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($plantas_enfermas_lista as $planta): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($planta['nombre_comun'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($planta['ubicacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td>
                                                        <span class="badge bg-danger"><?php echo htmlspecialchars($planta['estado'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Treatments -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Tratamientos Recientes</h5>
                                <a href="tratamientos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Planta</th>
                                                <th>Descripción</th>
                                                <th>Resultado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tratamientos_recientes as $tratamiento): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($tratamiento['nombre_comun'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($tratamiento['descripcion'], 0, 30) . '...', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php 
                                                            switch ($tratamiento['resultado']) {
                                                                case 'Exitoso': echo 'bg-success'; break;
                                                                case 'Fallido': echo 'bg-danger'; break;
                                                                default: echo 'bg-warning';
                                                            }
                                                            ?>">
                                                            <?php echo htmlspecialchars($tratamiento['resultado'], ENT_QUOTES, 'UTF-8'); ?>
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
                                    <a href="plantas.php?action=add" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Agregar Planta
                                    </a>
                                    <a href="reportes.php?action=add&tipo=Planta" class="btn btn-outline-success">
                                        <i class="bi bi-plus-circle me-2"></i>Nuevo Reporte
                                    </a>
                                    <a href="plantas.php?filter=Enfermo" class="btn btn-outline-danger">
                                        <i class="bi bi-clipboard-pulse me-2"></i>Ver Enfermas
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