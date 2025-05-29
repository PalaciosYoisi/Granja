<?php
session_start();
require_once 'conexion/conexion.php';

// Verificar sesión y rol
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener tratamientos de plantas
$tratamientos_result = $db->query("SELECT t.*, p.nombre_comun 
                                  FROM tratamientos t 
                                  JOIN reportes r ON t.id_reporte = r.id_reporte 
                                  JOIN plantas p ON r.id_planta = p.id_planta 
                                  WHERE r.tipo = 'Planta' 
                                  ORDER BY t.fecha_inicio DESC");
$tratamientos = $tratamientos_result ? $tratamientos_result->fetch_all(MYSQLI_ASSOC) : [];

// Filtrar por estado si se especifica
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
if ($filter) {
    $tratamientos_result = $db->query("SELECT t.*, p.nombre_comun 
                                      FROM tratamientos t 
                                      JOIN reportes r ON t.id_reporte = r.id_reporte 
                                      JOIN plantas p ON r.id_planta = p.id_planta 
                                      WHERE r.tipo = 'Planta' AND t.resultado = '$filter'
                                      ORDER BY t.fecha_inicio DESC");
    $tratamientos = $tratamientos_result ? $tratamientos_result->fetch_all(MYSQLI_ASSOC) : [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tratamientos - Botánico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Estilos compartidos con botanico.php */
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
        
        /* Estilos específicos para tratamientos */
        .treatment-card {
            border-left: 4px solid #28a745;
            transition: all 0.3s ease;
        }
        .treatment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .badge-success {
            background-color: #28a745;
        }
        .badge-warning {
            background-color: #ffc107;
        }
        .badge-danger {
            background-color: #dc3545;
        }
        .filter-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
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
                    <h1 class="h2">Tratamientos de Plantas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="reportes.php?action=add&tipo=Planta" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i> Nuevo Tratamiento
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="mb-4 filter-buttons">
                    <a href="tratamientos.php" class="btn btn-sm <?php echo $filter == '' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Todos
                    </a>
                    <a href="tratamientos.php?filter=Exitoso" class="btn btn-sm <?php echo $filter == 'Exitoso' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Exitosos
                    </a>
                    <a href="tratamientos.php?filter=En%20proceso" class="btn btn-sm <?php echo $filter == 'En proceso' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        En Proceso
                    </a>
                    <a href="tratamientos.php?filter=Fallido" class="btn btn-sm <?php echo $filter == 'Fallido' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Fallidos
                    </a>
                </div>

                <!-- Lista de tratamientos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Registro de Tratamientos</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Planta</th>
                                        <th>Tratamiento</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Resultado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tratamientos)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No hay tratamientos registrados</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tratamientos as $tratamiento): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($tratamiento['nombre_comun'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(substr($tratamiento['descripcion'], 0, 30) . (strlen($tratamiento['descripcion']) > 30 ? '...' : ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($tratamiento['fecha_inicio'])); ?></td>
                                                <td><?php echo $tratamiento['fecha_fin'] ? date('d/m/Y', strtotime($tratamiento['fecha_fin'])) : '--'; ?></td>
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
                                                <td>
                                                    <a href="tratamiento_detalle.php?id=<?php echo $tratamiento['id_tratamiento']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="reportes.php?action=edit&id=<?php echo $tratamiento['id_reporte']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>