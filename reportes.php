<?php
session_start();
require_once 'conexion/conexion.php';


// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener reportes
$reportes_result = $db->query("
    SELECT r.*, u.nombre as usuario_nombre, 
           COALESCE(a.nombre_comun, p.nombre_comun) as nombre_entidad,
           CASE WHEN r.tipo = 'Animal' THEN a.estado ELSE p.estado END as estado_entidad
    FROM reportes r
    LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
    LEFT JOIN animales a ON r.id_animal = a.id_animal
    LEFT JOIN plantas p ON r.id_planta = p.id_planta
    ORDER BY r.fecha_reporte DESC
");
$reportes = $reportes_result ? $reportes_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener estadísticas de reportes
$stats = [
    'total' => count($reportes),
    'animales' => $db->query("SELECT COUNT(*) as total FROM reportes WHERE tipo = 'Animal'")->fetch_assoc()['total'],
    'plantas' => $db->query("SELECT COUNT(*) as total FROM reportes WHERE tipo = 'Planta'")->fetch_assoc()['total'],
    'sin_tratamiento' => $db->query("
        SELECT COUNT(r.id_reporte) as total 
        FROM reportes r
        LEFT JOIN tratamientos t ON r.id_reporte = t.id_reporte
        WHERE t.id_tratamiento IS NULL
    ")->fetch_assoc()['total']
];

// Obtener tratamientos
$tratamientos_result = $db->query("
    SELECT t.*, r.tipo, 
           COALESCE(a.nombre_comun, p.nombre_comun) as nombre_entidad
    FROM tratamientos t
    JOIN reportes r ON t.id_reporte = r.id_reporte
    LEFT JOIN animales a ON r.id_animal = a.id_animal
    LEFT JOIN plantas p ON r.id_planta = p.id_planta
    ORDER BY t.fecha_inicio DESC
");
$tratamientos = $tratamientos_result ? $tratamientos_result->fetch_all(MYSQLI_ASSOC) : [];

// Procesar formulario de nuevo reporte
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_reporte'])) {
    $tipo = $_POST['tipo'];
    $diagnostico = $_POST['diagnostico'];
    $id_entidad = $_POST['id_entidad'];
    $id_usuario = $_SESSION['usuario_id'];
    
    if ($tipo == 'Animal') {
        $query = "INSERT INTO reportes (id_usuario, id_animal, tipo, diagnostico, fecha_reporte) 
                  VALUES (?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iiss", $id_usuario, $id_entidad, $tipo, $diagnostico);
    } else {
        $query = "INSERT INTO reportes (id_usuario, id_planta, tipo, diagnostico, fecha_reporte) 
                  VALUES (?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iiss", $id_usuario, $id_entidad, $tipo, $diagnostico);
    }
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Reporte creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
        header("Location: reportes.php");
        exit();
    } else {
        $_SESSION['mensaje'] = "Error al crear el reporte";
        $_SESSION['tipo_mensaje'] = "danger";
    }
}

// Obtener animales y plantas para el formulario
$animales = $db->query("SELECT id_animal, nombre_comun FROM animales ORDER BY nombre_comun")->fetch_all(MYSQLI_ASSOC);
$plantas = $db->query("SELECT id_planta, nombre_comun FROM plantas ORDER BY nombre_comun")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Granja San José</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
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

    /* Sidebar */
    .sidebar {
        min-height: 100vh;
        background-color: var(--bg-dark);
        color: white;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 0.75rem 1rem;
        border-radius: var(--border-radius);
        margin-bottom: 0.25rem;
        transition: var(--transition);
        display: flex;
        align-items: center;
    }

    .sidebar .nav-link:hover, .sidebar .nav-link.active {
        color: white;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .sidebar .nav-link i {
        margin-right: 0.5rem;
    }

    /* Cards */
    .card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        transition: var(--transition);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }

    .stat-card {
        border-left: 4px solid var(--secondary-color);
    }

    .stat-card .icon {
        font-size: 2rem;
        color: var(--secondary-color);
    }

    .report-card {
        border-left: 4px solid;
    }

    .report-card.animal {
        border-left-color: var(--secondary-color);
    }

    .report-card.planta {
        border-left-color: var(--primary-color);
    }

    /* Badges */
    .badge-animal {
        background-color: var(--secondary-color);
        color: white;
    }

    .badge-planta {
        background-color: var(--primary-color);
        color: white;
    }

    .badge-pendiente {
        background-color: #FFC107;
        color: var(--text-dark);
    }

    .badge-proceso {
        background-color: #2196F3;
        color: white;
    }

    .badge-exitoso {
        background-color: #4CAF50;
        color: white;
    }

    .badge-fallido {
        background-color: #F44336;
        color: white;
    }

    /* Charts */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* Buttons */
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .btn-danger {
        background-color: #F44336;
        border-color: #F44336;
    }

    .btn-danger:hover {
        background-color: #D32F2F;
        border-color: #D32F2F;
    }

    .btn-outline-primary {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-outline-danger {
        border-color: #F44336;
        color: #F44336;
    }

    .btn-outline-danger:hover {
        background-color: #F44336;
        color: white;
    }

    /* Navbar */
    .navbar-brand {
        font-weight: 600;
        color: white;
    }

    /* Section titles */
    .section-title {
        position: relative;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
        color: var(--primary-dark);
    }

    .section-title:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 50px;
        height: 3px;
        background-color: var(--primary-color);
    }

    /* Tables */
    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th {
        background-color: var(--primary-light);
        color: var(--primary-dark);
        font-weight: 600;
    }

    .table th, .table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table tr:hover {
        background-color: #f5f5f5;
    }

    /* Modal */
    .modal-header {
        border-bottom: 1px solid #eee;
    }

    .modal-footer {
        border-top: 1px solid #eee;
    }

    /* Form elements */
    .form-control {
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        padding: 10px 15px;
        transition: var(--transition);
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px var(--primary-light);
    }

    textarea.form-control {
        min-height: 100px;
    }

    /* Alerts */
    .alert {
        padding: 15px;
        border-radius: var(--border-radius);
    }

    .alert-warning {
        background-color: #FFF3E0;
        color: #E65100;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .chart-container {
            height: 250px;
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
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-clipboard-data text-primary me-2"></i>
                        Sistema de Reportes
                    </h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoReporteModal">
                        <i class="bi bi-plus-circle me-1"></i>Nuevo Reporte
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <div class="icon mb-2">
                                    <i class="bi bi-file-text"></i>
                                </div>
                                <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                <p class="text-muted mb-0">Total Reportes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <div class="icon mb-2">
                                    <i class="bi bi-egg-fried"></i>
                                </div>
                                <h3 class="mb-0"><?php echo $stats['animales']; ?></h3>
                                <p class="text-muted mb-0">Reportes Animales</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <div class="icon mb-2">
                                    <i class="bi bi-flower2"></i>
                                </div>
                                <h3 class="mb-0"><?php echo $stats['plantas']; ?></h3>
                                <p class="text-muted mb-0">Reportes Plantas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <div class="icon mb-2">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                                <h3 class="mb-0"><?php echo $stats['sin_tratamiento']; ?></h3>
                                <p class="text-muted mb-0">Pendientes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="section-title">Distribución de Reportes</h5>
                                <div class="chart-container">
                                    <canvas id="tipoReportesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="section-title">Estados de Tratamientos</h5>
                                <div class="chart-container">
                                    <canvas id="estadoTratamientosChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reportes Recientes -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="section-title">Reportes Recientes</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Entidad</th>
                                        <th>Diagnóstico</th>
                                        <th>Reportado por</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportes as $reporte): ?>
                                        <tr>
                                            <td><?php echo $reporte['id_reporte']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $reporte['tipo'] == 'Animal' ? 'badge-animal' : 'badge-planta'; ?>">
                                                    <?php echo $reporte['tipo']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $reporte['nombre_entidad'] ?? 'N/A'; ?>
                                                <br>
                                                <small class="text-muted">Estado: <?php echo $reporte['estado_entidad'] ?? 'N/A'; ?></small>
                                            </td>
                                            <td><?php echo substr($reporte['diagnostico'], 0, 50) . (strlen($reporte['diagnostico']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo $reporte['usuario_nombre']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($reporte['fecha_reporte'])); ?></td>
                                            <td>
                                                <?php 
                                                $tiene_tratamiento = false;
                                                foreach ($tratamientos as $tratamiento) {
                                                    if ($tratamiento['id_reporte'] == $reporte['id_reporte']) {
                                                        $tiene_tratamiento = true;
                                                        $estado = $tratamiento['resultado'];
                                                        echo '<span class="badge ' . 
                                                             ($estado == 'Exitoso' ? 'badge-exitoso' : 
                                                              ($estado == 'Fallido' ? 'badge-fallido' : 'badge-proceso')) . '">' . 
                                                             $estado . '</span>';
                                                        break;
                                                    }
                                                }
                                                if (!$tiene_tratamiento) {
                                                    echo '<span class="badge badge-pendiente">Pendiente</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#detalleReporteModal"
                                                        data-id="<?php echo $reporte['id_reporte']; ?>"
                                                        data-tipo="<?php echo $reporte['tipo']; ?>"
                                                        data-entidad="<?php echo $reporte['nombre_entidad']; ?>"
                                                        data-diagnostico="<?php echo htmlspecialchars($reporte['diagnostico'], ENT_QUOTES); ?>"
                                                        data-usuario="<?php echo $reporte['usuario_nombre']; ?>"
                                                        data-fecha="<?php echo date('d/m/Y H:i', strtotime($reporte['fecha_reporte'])); ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <a href="conexion/eliminar_reporte.php?id=<?php echo $reporte['id_reporte']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('¿Estás seguro de eliminar este reporte?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tratamientos Recientes -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="section-title">Tratamientos Recientes</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Reporte</th>
                                        <th>Tipo</th>
                                        <th>Entidad</th>
                                        <th>Descripción</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tratamientos as $tratamiento): ?>
                                        <tr>
                                            <td><?php echo $tratamiento['id_tratamiento']; ?></td>
                                            <td>#<?php echo $tratamiento['id_reporte']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $tratamiento['tipo'] == 'Animal' ? 'badge-animal' : 'badge-planta'; ?>">
                                                    <?php echo $tratamiento['tipo']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $tratamiento['nombre_entidad']; ?></td>
                                            <td><?php echo substr($tratamiento['descripcion'], 0, 50) . (strlen($tratamiento['descripcion']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($tratamiento['fecha_inicio'])); ?></td>
                                            <td><?php echo $tratamiento['fecha_fin'] ? date('d/m/Y', strtotime($tratamiento['fecha_fin'])) : 'En curso'; ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $tratamiento['resultado'] == 'Exitoso' ? 'badge-exitoso' : 
                                                           ($tratamiento['resultado'] == 'Fallido' ? 'badge-fallido' : 'badge-proceso'); ?>">
                                                    <?php echo $tratamiento['resultado']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Nuevo Reporte -->
    <div class="modal fade" id="nuevoReporteModal" tabindex="-1" aria-labelledby="nuevoReporteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevoReporteModalLabel">
                        <i class="bi bi-plus-circle me-1"></i>Nuevo Reporte
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="reportes.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="Animal">Animal</option>
                                <option value="Planta">Planta</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="animal-field" style="display: none;">
                            <label for="id_animal" class="form-label">Animal</label>
                            <select class="form-select" id="id_animal" name="id_entidad">
                                <option value="">Seleccionar animal...</option>
                                <?php foreach ($animales as $animal): ?>
                                    <option value="<?php echo $animal['id_animal']; ?>">
                                        <?php echo $animal['nombre_comun']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="planta-field" style="display: none;">
                            <label for="id_planta" class="form-label">Planta</label>
                            <select class="form-select" id="id_planta" name="id_entidad">
                                <option value="">Seleccionar planta...</option>
                                <?php foreach ($plantas as $planta): ?>
                                    <option value="<?php echo $planta['id_planta']; ?>">
                                        <?php echo $planta['nombre_comun']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="diagnostico" class="form-label">Diagnóstico</label>
                            <textarea class="form-control" id="diagnostico" name="diagnostico" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_reporte" class="btn btn-primary">Guardar Reporte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Reporte -->
    <div class="modal fade" id="detalleReporteModal" tabindex="-1" aria-labelledby="detalleReporteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detalleReporteModalLabel">Detalle del Reporte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> <span id="detalle-id"></span></p>
                            <p><strong>Tipo:</strong> <span id="detalle-tipo" class="badge"></span></p>
                            <p><strong>Entidad:</strong> <span id="detalle-entidad"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Reportado por:</strong> <span id="detalle-usuario"></span></p>
                            <p><strong>Fecha:</strong> <span id="detalle-fecha"></span></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6>Diagnóstico:</h6>
                        <div class="card card-body bg-light">
                            <p id="detalle-diagnostico"></p>
                        </div>
                    </div>
                    
                    <div id="tratamiento-section">
                        <h5 class="section-title">Tratamiento</h5>
                        <div id="sin-tratamiento" class="alert alert-warning">
                            No se ha registrado un tratamiento para este reporte.
                        </div>
                        <div id="con-tratamiento" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Descripción:</strong> <span id="tratamiento-descripcion"></span></p>
                                    <p><strong>Fecha Inicio:</strong> <span id="tratamiento-inicio"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Fecha Fin:</strong> <span id="tratamiento-fin"></span></p>
                                    <p><strong>Resultado:</strong> <span id="tratamiento-resultado" class="badge"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btn-crear-tratamiento" style="display: none;">
                        <i class="bi bi-plus-circle me-1"></i>Crear Tratamiento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Mostrar/ocultar campos según tipo de reporte
        document.getElementById('tipo').addEventListener('change', function() {
            const tipo = this.value;
            document.getElementById('animal-field').style.display = tipo === 'Animal' ? 'block' : 'none';
            document.getElementById('planta-field').style.display = tipo === 'Planta' ? 'block' : 'none';
        });

        // Configurar modal de detalle
        const detalleReporteModal = document.getElementById('detalleReporteModal');
        if (detalleReporteModal) {
            detalleReporteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const tipo = button.getAttribute('data-tipo');
                const entidad = button.getAttribute('data-entidad');
                const diagnostico = button.getAttribute('data-diagnostico');
                const usuario = button.getAttribute('data-usuario');
                const fecha = button.getAttribute('data-fecha');
                
                document.getElementById('detalle-id').textContent = id;
                document.getElementById('detalle-tipo').textContent = tipo;
                document.getElementById('detalle-tipo').className = tipo === 'Animal' ? 'badge badge-animal' : 'badge badge-planta';
                document.getElementById('detalle-entidad').textContent = entidad;
                document.getElementById('detalle-usuario').textContent = usuario;
                document.getElementById('detalle-fecha').textContent = fecha;
                document.getElementById('detalle-diagnostico').textContent = diagnostico;
                
                // Verificar si tiene tratamiento
                fetch(`conexion/obtener_tratamiento.php?id_reporte=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.existe) {
                            document.getElementById('sin-tratamiento').style.display = 'none';
                            document.getElementById('con-tratamiento').style.display = 'block';
                            document.getElementById('tratamiento-descripcion').textContent = data.descripcion;
                            document.getElementById('tratamiento-inicio').textContent = data.fecha_inicio;
                            document.getElementById('tratamiento-fin').textContent = data.fecha_fin || 'En curso';
                            document.getElementById('tratamiento-resultado').textContent = data.resultado;
                            document.getElementById('tratamiento-resultado').className = 
                                data.resultado === 'Exitoso' ? 'badge badge-exitoso' : 
                                (data.resultado === 'Fallido' ? 'badge badge-fallido' : 'badge badge-proceso');
                            document.getElementById('btn-crear-tratamiento').style.display = 'none';
                        } else {
                            document.getElementById('sin-tratamiento').style.display = 'block';
                            document.getElementById('con-tratamiento').style.display = 'none';
                            document.getElementById('btn-crear-tratamiento').style.display = 'block';
                        }
                    });
            });
        }

        // Gráfico de distribución de reportes
        const tipoReportesCtx = document.getElementById('tipoReportesChart').getContext('2d');
        const tipoReportesChart = new Chart(tipoReportesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Animales', 'Plantas'],
                datasets: [{
                    data: [<?php echo $stats['animales']; ?>, <?php echo $stats['plantas']; ?>],
                    backgroundColor: ['#3498db', '#2ecc71'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de estados de tratamientos
        const estadoTratamientosCtx = document.getElementById('estadoTratamientosChart').getContext('2d');
        const estadoTratamientosChart = new Chart(estadoTratamientosCtx, {
            type: 'bar',
            data: {
                labels: ['Exitosos', 'En Proceso', 'Fallidos'],
                datasets: [{
                    label: 'Tratamientos',
                    data: [
                        <?php 
                        $exitosos = 0;
                        $en_proceso = 0;
                        $fallidos = 0;
                        
                        foreach ($tratamientos as $tratamiento) {
                            if ($tratamiento['resultado'] == 'Exitoso') $exitosos++;
                            elseif ($tratamiento['resultado'] == 'Fallido') $fallidos++;
                            else $en_proceso++;
                        }
                        
                        echo "$exitosos, $en_proceso, $fallidos";
                        ?>
                    ],
                    backgroundColor: ['#2ecc71', '#3498db', '#e74c3c'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>