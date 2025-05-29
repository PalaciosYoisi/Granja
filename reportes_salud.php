<?php
session_start();
require_once 'conexion/conexion.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: iniciar_sesion.php");
    exit();
}

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
</head>
<body>
    <div class="wrapper d-flex">
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