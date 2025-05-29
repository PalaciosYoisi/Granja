<?php
session_start();
require_once 'conexion/conexion.php';


// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener reportes de salud de animales
$query = "SELECT r.*, a.nombre_comun as animal, u.nombre as usuario 
          FROM reportes r
          JOIN usuarios u ON r.id_usuario = u.id_usuario
          JOIN animales a ON r.id_animal = a.id_animal
          WHERE r.tipo = 'Animal'
          ORDER BY r.fecha_reporte DESC";

$reportes_result = $db->query($query);
$reportes = $reportes_result ? $reportes_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener tratamientos asociados a reportes de animales
$tratamientos_query = "SELECT t.*, r.id_animal, a.nombre_comun as animal
                       FROM tratamientos t
                       JOIN reportes r ON t.id_reporte = r.id_reporte
                       JOIN animales a ON r.id_animal = a.id_animal
                       WHERE r.tipo = 'Animal'
                       ORDER BY t.fecha_inicio DESC";

$tratamientos_result = $db->query($tratamientos_query);
$tratamientos = $tratamientos_result ? $tratamientos_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener alertas relacionadas con animales
$alertas_query = "SELECT * FROM alertas WHERE categoria = 'animal' OR categoria = 'salud' 
                  ORDER BY fecha DESC LIMIT 10";
$alertas_result = $db->query($alertas_query);
$alertas = $alertas_result ? $alertas_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener historial de cambios de estado de salud de animales
$historial_query = "SELECT h.*, a.nombre_comun as animal 
                    FROM historial_estado_salud h
                    JOIN animales a ON h.id_animal = a.id_animal
                    ORDER BY h.fecha_cambio DESC LIMIT 10";
$historial_result = $db->query($historial_query);
$historial = $historial_result ? $historial_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener animales para formulario
$animales_result = $db->query("SELECT id_animal, nombre_comun FROM animales ORDER BY nombre_comun");
$animales = $animales_result ? $animales_result->fetch_all(MYSQLI_ASSOC) : [];

// Procesar formulario de nuevo reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_reporte'])) {
    $id_animal = $_POST['id_animal'];
    $diagnostico = $_POST['diagnostico'];
    $id_usuario = $_SESSION['usuario_id'];

    $stmt = $db->prepare("INSERT INTO reportes (id_usuario, id_animal, tipo, diagnostico) 
                          VALUES (?, ?, 'Animal', ?)");
    $stmt->bind_param("iis", $id_usuario, $id_animal, $diagnostico);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Reporte de salud registrado correctamente";
        $_SESSION['tipo_mensaje'] = "success";
        header("Location: reportes_salud.php");
        exit();
    } else {
        $_SESSION['mensaje'] = "Error al registrar el reporte";
        $_SESSION['tipo_mensaje'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Salud - Granja San José</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>

        
    /* styles.css */
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

.wrapper {
    display: flex;
    min-height: 100vh;
}

/* Sidebar styles */
.sidebar {
    background-color: var(--bg-dark);
    color: white;
    width: 250px;
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
}

.sidebar .nav-link {
    color: rgba(255,255,255,0.8);
    padding: 10px 15px;
    margin-bottom: 5px;
    border-radius: var(--border-radius);
    transition: var(--transition);
    display: flex;
    align-items: center;
    text-decoration: none;
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
    flex: 1;
    margin-left: 250px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

header.bg-success {
    background-color: var(--primary-color) !important;
    color: white;
    padding: 20px 0;
    box-shadow: var(--box-shadow);
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo i {
    font-size: 28px;
}

.logo h1 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-info span {
    font-weight: 500;
}

.user-info i {
    font-size: 24px;
}

/* Tabs */
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.tab {
    padding: 10px 20px;
    cursor: pointer;
    border-radius: var(--border-radius);
    font-weight: 500;
    transition: var(--transition);
    color: #666;
}

.tab.active {
    background-color: var(--primary-color);
    color: white;
}

.tab:hover:not(.active) {
    background-color: #f0f0f0;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.5s ease;
}

/* Cards */
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
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--primary-dark);
    margin: 0;
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

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
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

.btn-sm {
    padding: 5px 10px;
    font-size: 14px;
}

/* Badges */
.badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.bg-success {
    background-color: #C8E6C9 !important;
    color: #2E7D32 !important;
}

.bg-danger {
    background-color: #FFCDD2 !important;
    color: #C62828 !important;
}

.bg-warning {
    background-color: #FFF9C4 !important;
    color: #F57F17 !important;
}

/* Forms */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--primary-dark);
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: var(--border-radius);
    font-family: 'Poppins', sans-serif;
    transition: var(--transition);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px var(--primary-light);
}

textarea.form-control {
    min-height: 100px;
    resize: vertical;
}

.select-control {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 16px;
}

/* Alerts */
.alert {
    padding: 15px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background-color: #C8E6C9;
    color: #2E7D32;
    border: none;
}

.alert-danger {
    background-color: #FFCDD2;
    color: #C62828;
    border: none;
}

.alert i {
    font-size: 20px;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.empty-state i {
    font-size: 50px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h4 {
    color: #555;
    margin-bottom: 10px;
}

.empty-state p {
    color: #777;
    margin-bottom: 20px;
}

/* List group */
.list-group-item {
    padding: 15px;
    border: 1px solid #eee;
    margin-bottom: 10px;
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.list-group-item:hover {
    background-color: #f9f9f9;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
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
    
    .tabs {
        flex-wrap: wrap;
    }
    
    .tab {
        flex: 1;
        text-align: center;
    }
}
    </style>
</head>
<body>
    <div class="wrapper d-flex">
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

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <header class="bg-success">
                <div class="container">
                    <div class="header-content d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <i class="bi bi-clipboard-pulse"></i>
                            <h1>Reportes de Salud - Animales</h1>
                        </div>
                        <div class="user-info text-white">
                            <span>Bienvenido, <?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?></span>
                            <i class="bi bi-person-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </header>

            <div class="container mt-4">
                <!-- Mensajes de alerta -->
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] === 'success' ? 'success' : 'danger'; ?>">
                        <?php echo $_SESSION['mensaje']; ?>
                        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
                    </div>
                <?php endif; ?>

                <!-- Pestañas -->
                <div class="tabs mb-4">
                    <div class="tab active" data-tab="reportes">Reportes</div>
                    <div class="tab" data-tab="tratamientos">Tratamientos</div>
                    <div class="tab" data-tab="historial">Historial</div>
                    <div class="tab" data-tab="alertas">Alertas</div>
                </div>

                <!-- Contenido de pestañas -->
                <div class="tab-content active" id="reportes">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Nuevo Reporte de Salud</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_animal">Animal</label>
                                            <select class="form-control select-control" id="id_animal" name="id_animal" required>
                                                <option value="">Seleccione un animal</option>
                                                <?php foreach ($animales as $animal): ?>
                                                    <option value="<?php echo $animal['id_animal']; ?>">
                                                        <?php echo htmlspecialchars($animal['nombre_comun']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="diagnostico">Diagnóstico</label>
                                            <textarea class="form-control" id="diagnostico" name="diagnostico" rows="3" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="agregar_reporte" class="btn btn-success mt-3">
                                    <i class="bi bi-plus-circle"></i> Registrar Reporte
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Reportes de Salud Recientes</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($reportes)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-clipboard-x"></i>
                                    <h4>No hay reportes registrados</h4>
                                    <p>No se han encontrado reportes de salud para animales.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Animal</th>
                                                <th>Diagnóstico</th>
                                                <th>Reportado por</th>
                                                <th>Fecha</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reportes as $reporte): ?>
                                                <tr>
                                                    <td><?php echo $reporte['id_reporte']; ?></td>
                                                    <td><?php echo htmlspecialchars($reporte['animal']); ?></td>
                                                    <td><?php echo htmlspecialchars($reporte['diagnostico']); ?></td>
                                                    <td><?php echo htmlspecialchars($reporte['usuario']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($reporte['fecha_reporte'])); ?></td>
                                                    <td>
                                                        <a href="detalle_reporte.php?id=<?php echo $reporte['id_reporte']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i> Ver
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="tratamientos">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tratamientos Aplicados</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($tratamientos)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-clipboard-pulse"></i>
                                    <h4>No hay tratamientos registrados</h4>
                                    <p>No se han encontrado tratamientos aplicados a animales.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Animal</th>
                                                <th>Descripción</th>
                                                <th>Fecha Inicio</th>
                                                <th>Fecha Fin</th>
                                                <th>Resultado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tratamientos as $tratamiento): ?>
                                                <tr>
                                                    <td><?php echo $tratamiento['id_tratamiento']; ?></td>
                                                    <td><?php echo htmlspecialchars($tratamiento['animal']); ?></td>
                                                    <td><?php echo htmlspecialchars($tratamiento['descripcion']); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($tratamiento['fecha_inicio'])); ?></td>
                                                    <td>
                                                        <?php echo $tratamiento['fecha_fin'] ? date('d/m/Y', strtotime($tratamiento['fecha_fin'])) : 'En curso'; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php echo $tratamiento['resultado'] === 'Exitoso' ? 'bg-success' : 
                                                                  ($tratamiento['resultado'] === 'Fallido' ? 'bg-danger' : 'bg-warning'); ?>">
                                                            <?php echo $tratamiento['resultado']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="historial">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Historial de Cambios de Estado</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($historial)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-clock-history"></i>
                                    <h4>No hay historial registrado</h4>
                                    <p>No se han encontrado cambios de estado en animales.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Animal</th>
                                                <th>Estado Anterior</th>
                                                <th>Estado Nuevo</th>
                                                <th>Fecha Cambio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historial as $cambio): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($cambio['animal']); ?></td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php echo $cambio['estado_anterior'] === 'Sano' ? 'bg-success' : 
                                                                  ($cambio['estado_anterior'] === 'Enfermo' ? 'bg-danger' : 'bg-warning'); ?>">
                                                            <?php echo $cambio['estado_anterior']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php echo $cambio['estado_nuevo'] === 'Sano' ? 'bg-success' : 
                                                                  ($cambio['estado_nuevo'] === 'Enfermo' ? 'bg-danger' : 'bg-warning'); ?>">
                                                            <?php echo $cambio['estado_nuevo']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($cambio['fecha_cambio'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="alertas">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Alertas Recientes</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($alertas)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-bell-slash"></i>
                                    <h4>No hay alertas recientes</h4>
                                    <p>No se han encontrado alertas relacionadas con animales.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($alertas as $alerta): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1">
                                                    <i class="bi bi-exclamation-triangle-fill text-<?php echo $alerta['categoria'] === 'salud' ? 'warning' : 'danger'; ?>"></i>
                                                    <?php echo ucfirst($alerta['categoria']); ?>
                                                </h5>
                                                <small><?php echo date('d/m/Y H:i', strtotime($alerta['fecha'])); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($alerta['mensaje']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Manejo de pestañas
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remover clase active de todas las pestañas y contenidos
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Agregar clase active a la pestaña clickeada
                    this.classList.add('active');
                    
                    // Mostrar el contenido correspondiente
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>