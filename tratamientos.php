<?php
session_start();
require_once 'conexion/conexion.php';



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
/* tratamientos.css */
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

.btn-sm {
    padding: 5px 10px;
    font-size: 14px;
}

.filter-buttons {
    margin-bottom: 20px;
}

.filter-buttons .btn {
    margin-right: 8px;
    margin-bottom: 8px;
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

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.bg-success {
    background-color: #C8E6C9;
    color:rgb(236, 242, 237);
}

.bg-danger {
    background-color: #FFCDD2;
    color:rgb(247, 238, 238);
}

.bg-warning {
    background-color: #FFF9C4;
    color:rgb(255, 251, 248);
}

.action-btn {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #f0f0f0;
    color: #555;
    border: none;
    cursor: pointer;
    transition: var(--transition);
}

.action-btn:hover {
    background-color: var(--primary-color);
    color: white;
}

.action-btn.edit {
    background-color: #BBDEFB;
    color: #1976D2;
}

.action-btn.edit:hover {
    background-color: #1976D2;
    color: white;
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
    
    .filter-buttons .btn {
        width: 100%;
        margin-right: 0;
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