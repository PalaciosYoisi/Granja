<?php
session_start();
require_once 'conexion/conexion.php';

$conexion = new Conexion();
$db = $conexion->getConexion();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Obtener lista de animales para el formulario
$animales = $db->query("SELECT id_animal, nombre_comun FROM animales ORDER BY nombre_comun")->fetch_all(MYSQLI_ASSOC);

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        $stmt = $db->prepare("INSERT INTO produccion (id_animal, tipo_produccion, cantidad, fecha_recoleccion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds",
            $_POST['id_animal'],
            $_POST['tipo_produccion'],
            $_POST['cantidad'],
            $_POST['fecha_recoleccion']
        );
        $stmt->execute();
        header("Location: produccion.php?success=added");
        exit();
    } elseif ($action == 'edit') {
        $stmt = $db->prepare("UPDATE produccion SET id_animal=?, tipo_produccion=?, cantidad=?, fecha_recoleccion=? WHERE id_produccion=?");
        $stmt->bind_param("isdsi",
            $_POST['id_animal'],
            $_POST['tipo_produccion'],
            $_POST['cantidad'],
            $_POST['fecha_recoleccion'],
            $_POST['id_produccion']
        );
        $stmt->execute();
        header("Location: produccion.php?success=updated");
        exit();
    }
}

// Eliminar registro
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM produccion WHERE id_produccion=?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header("Location: produccion.php?success=deleted");
    exit();
}

// Obtener datos para edición
$produccion_edit = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $result = $db->query("SELECT * FROM produccion WHERE id_produccion = " . intval($_GET['id']));
    $produccion_edit = $result->fetch_assoc();
}

// Obtener lista de producción
$produccion = $db->query("SELECT p.*, a.nombre_comun 
                          FROM produccion p 
                          LEFT JOIN animales a ON p.id_animal = a.id_animal 
                          ORDER BY fecha_recoleccion DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Producción - Granja</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
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
            display: flex;
            min-height: 100vh;
        }
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
        .main-content {
            flex: 1;
            margin-left: 250px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .page-title h2 {
            font-size: 22px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
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
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
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
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
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
        .actions {
            display: flex;
            gap: 8px;
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
        .action-btn.delete {
            background-color: #FFCDD2;
            color: #C62828;
        }
        .action-btn.delete:hover {
            background-color: #C62828;
            color: white;
        }
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
        }
        .alert-error {
            background-color: #FFCDD2;
            color: #C62828;
        }
        .alert i {
            font-size: 20px;
        }
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
        }
    </style>
</head>
<body>
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
    <div class="main-content">
        <div class="container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success fade-in">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                        if ($_GET['success'] == 'added') echo 'Registro de producción agregado exitosamente!';
                        elseif ($_GET['success'] == 'updated') echo 'Registro actualizado exitosamente!';
                        elseif ($_GET['success'] == 'deleted') echo 'Registro eliminado exitosamente!';
                    ?>
                </div>
            <?php endif; ?>

            <div class="page-title">
                <h2><i class="bi bi-graph-up"></i> Gestión de Producción</h2>
                <a href="produccion.php?action=add" class="btn pulse">
                    <i class="fas fa-plus"></i> Nuevo Registro
                </a>
            </div>

            <?php if ($action == 'list'): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-graph-up"></i> Registros de Producción</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Animal</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($produccion) > 0): ?>
                                    <?php foreach ($produccion as $prod): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prod['nombre_comun']); ?></td>
                                            <td><?php echo htmlspecialchars($prod['tipo_produccion']); ?></td>
                                            <td><?php echo $prod['cantidad']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($prod['fecha_recoleccion'])); ?></td>
                                            <td class="actions">
                                                <a href="produccion.php?action=edit&id=<?php echo $prod['id_produccion']; ?>" class="action-btn edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="produccion.php?delete=<?php echo $prod['id_produccion']; ?>" class="action-btn delete" title="Eliminar" onclick="return confirm('¿Eliminar este registro?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No hay registros de producción</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($action == 'add' || $action == 'edit'): ?>
                <div class="card fade-in">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-graph-up"></i>
                            <?php echo $action == 'add' ? 'Nuevo Registro de Producción' : 'Editar Registro'; ?>
                        </h3>
                        <a href="produccion.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="produccion.php?action=<?php echo $action; ?><?php echo $action == 'edit' ? '&id='.$produccion_edit['id_produccion'] : ''; ?>">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id_produccion" value="<?php echo $produccion_edit['id_produccion']; ?>">
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="id_animal">Animal</label>
                                <select class="form-control" id="id_animal" name="id_animal" required>
                                    <option value="">Seleccione un animal</option>
                                    <?php foreach ($animales as $animal): ?>
                                        <option value="<?php echo $animal['id_animal']; ?>"
                                            <?php
                                            $selected = '';
                                            if ($action == 'edit' && $animal['id_animal'] == $produccion_edit['id_animal']) $selected = 'selected';
                                            elseif ($action == 'add' && isset($_POST['id_animal']) && $_POST['id_animal'] == $animal['id_animal']) $selected = 'selected';
                                            echo $selected;
                                            ?>>
                                            <?php echo htmlspecialchars($animal['nombre_comun']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="tipo_produccion">Tipo de Producción</label>
                                <select class="form-control" id="tipo_produccion" name="tipo_produccion" required>
                                    <option value="">Seleccione tipo</option>
                                    <?php
                                    $tipos = ['Leche', 'Huevos', 'Carne', 'Otro'];
                                    foreach ($tipos as $tipo) {
                                        $selected = '';
                                        if ($action == 'edit' && $produccion_edit['tipo_produccion'] == $tipo) $selected = 'selected';
                                        elseif ($action == 'add' && isset($_POST['tipo_produccion']) && $_POST['tipo_produccion'] == $tipo) $selected = 'selected';
                                        echo "<option value=\"$tipo\" $selected>$tipo</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="cantidad">Cantidad</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="cantidad" name="cantidad"
                                    value="<?php echo $action == 'edit' ? $produccion_edit['cantidad'] : (isset($_POST['cantidad']) ? $_POST['cantidad'] : ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="fecha_recoleccion">Fecha de Recolección</label>
                                <input type="date" class="form-control" id="fecha_recoleccion" name="fecha_recoleccion"
                                    value="<?php echo $action == 'edit' ? $produccion_edit['fecha_recoleccion'] : (isset($_POST['fecha_recoleccion']) ? $_POST['fecha_recoleccion'] : ''); ?>" required>
                            </div>
                            <div class="form-group" style="text-align: right;">
                                <button type="submit" class="btn pulse">
                                    <i class="fas fa-save"></i>
                                    <?php echo $action == 'add' ? 'Guardar Registro' : 'Actualizar Registro'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>