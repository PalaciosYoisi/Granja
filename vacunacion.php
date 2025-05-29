<?php
session_start();
require_once 'conexion/conexion.php';

// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener todas las vacunaciones
$query = "SELECT v.*, a.nombre_comun as animal, vac.nombre as vacuna 
          FROM vacunacion v
          JOIN animales a ON v.id_animal = a.id_animal
          JOIN vacunas vac ON v.id_vacuna = vac.id_vacuna
          ORDER BY v.proxima_dosis ASC";

$vacunaciones_result = $db->query($query);
$vacunaciones = $vacunaciones_result ? $vacunaciones_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener animales para el formulario
$animales_result = $db->query("SELECT id_animal, nombre_comun FROM animales ORDER BY nombre_comun");
$animales = $animales_result ? $animales_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener vacunas para el formulario
$vacunas_result = $db->query("SELECT id_vacuna, nombre FROM vacunas ORDER BY nombre");
$vacunas = $vacunas_result ? $vacunas_result->fetch_all(MYSQLI_ASSOC) : [];

// Procesar formulario de agregar vacunación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_vacunacion'])) {
    $id_animal = $_POST['id_animal'];
    $id_vacuna = $_POST['id_vacuna'];
    $fecha_aplicacion = $_POST['fecha_aplicacion'];
    $proxima_dosis = $_POST['proxima_dosis'];
    $dosis = $_POST['dosis'];
    $id_empleado = $_POST['id_empleado'];
    $observaciones = $_POST['observaciones'];

    $stmt = $db->prepare("INSERT INTO vacunacion (id_animal, id_vacuna, fecha_aplicacion, proxima_dosis, dosis, id_empleado, observaciones) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssis", $id_animal, $id_vacuna, $fecha_aplicacion, $proxima_dosis, $dosis, $id_empleado, $observaciones);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Vacunación registrada correctamente";
        $_SESSION['tipo_mensaje'] = "success";
        header("Location: vacunacion.php");
        exit();
    } else {
        $_SESSION['mensaje'] = "Error al registrar la vacunación";
        $_SESSION['tipo_mensaje'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacunación - Granja San José</title>
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

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
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

        .bg-success {
            background-color: var(--primary-color);
            color: white;
        }

        /* Form styles */
        .form-label {
            font-weight: 500;
            color: var(--primary-dark);
        }

        .form-control {
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
            padding: 10px 15px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        /* Alert styles */
        .alert {
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            border-left: 4px solid var(--primary-color);
        }

        .alert-danger {
            background-color: #FFCDD2;
            color: #C62828;
            border-left: 4px solid #f44336;
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
                    <h1 class="h2">Gestión de Vacunación</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agregarVacunacionModal">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Vacunación
                    </button>
                </div>

                <!-- Mostrar mensajes -->
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?>">
                        <i class="bi <?php echo $_SESSION['tipo_mensaje'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
                        <?php echo $_SESSION['mensaje']; ?>
                    </div>
                    <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
                <?php endif; ?>

                <!-- Tabla de vacunaciones -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Registro de Vacunaciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Animal</th>
                                        <th>Vacuna</th>
                                        <th>Fecha Aplicación</th>
                                        <th>Próxima Dosis</th>
                                        <th>Días Restantes</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vacunaciones as $vacunacion): 
                                        $dias_restantes = floor((strtotime($vacunacion['proxima_dosis']) - time()) / (60 * 60 * 24));
                                        $clase_badge = $dias_restantes <= 0 ? 'bg-danger' : ($dias_restantes <= 7 ? 'bg-warning' : 'bg-success');
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($vacunacion['animal'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($vacunacion['vacuna'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($vacunacion['fecha_aplicacion'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($vacunacion['proxima_dosis'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo $clase_badge; ?>">
                                                    <?php echo $dias_restantes <= 0 ? 'VENCIDA' : $dias_restantes . ' días'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($vacunacion['observaciones'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($vacunaciones)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No hay registros de vacunación</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para agregar vacunación -->
    <div class="modal fade" id="agregarVacunacionModal" tabindex="-1" aria-labelledby="agregarVacunacionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarVacunacionModalLabel">Registrar Nueva Vacunación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="vacunacion.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="id_animal" class="form-label">Animal</label>
                            <select class="form-select" id="id_animal" name="id_animal" required>
                                <option value="">Seleccionar animal...</option>
                                <?php foreach ($animales as $animal): ?>
                                    <option value="<?php echo $animal['id_animal']; ?>"><?php echo htmlspecialchars($animal['nombre_comun'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_vacuna" class="form-label">Vacuna</label>
                            <select class="form-select" id="id_vacuna" name="id_vacuna" required>
                                <option value="">Seleccionar vacuna...</option>
                                <?php foreach ($vacunas as $vacuna): ?>
                                    <option value="<?php echo $vacuna['id_vacuna']; ?>"><?php echo htmlspecialchars($vacuna['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_aplicacion" class="form-label">Fecha de Aplicación</label>
                            <input type="date" class="form-control" id="fecha_aplicacion" name="fecha_aplicacion" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <!-- Agregar estos campos al formulario en el modal -->
                        <div class="mb-3">
                            <label for="dosis" class="form-label">Dosis</label>
                            <select class="form-select" id="dosis" name="dosis" required>
                                <option value="Primera">Primera</option>
                                <option value="Segunda">Segunda</option>
                                <option value="Refuerzo">Refuerzo</option>
                                <option value="Anual">Anual</option>
                            </select>
                        </div>
                        <input type="hidden" name="id_empleado" value="<?php echo isset($_SESSION['id_usuario']) ? htmlspecialchars($_SESSION['id_usuario'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <div class="mb-3">
                            <label for="proxima_dosis" class="form-label">Próxima Dosis</label>
                            <input type="date" class="form-control" id="proxima_dosis" name="proxima_dosis" required>
                        </div>
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" name="agregar_vacunacion">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para calcular automáticamente la próxima dosis basada en la fecha de aplicación
        document.getElementById('fecha_aplicacion').addEventListener('change', function() {
            const fechaAplicacion = new Date(this.value);
            if (!isNaN(fechaAplicacion.getTime())) {
                // Calcula 30 días después (esto debería ajustarse según el tipo de vacuna)
                const proximaDosis = new Date(fechaAplicacion);
                proximaDosis.setDate(proximaDosis.getDate() + 30);
                
                // Formatea la fecha como YYYY-MM-DD
                const formattedDate = proximaDosis.toISOString().split('T')[0];
                document.getElementById('proxima_dosis').value = formattedDate;
            }
        });
    </script>
</body>
</html>