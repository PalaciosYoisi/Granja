<?php
session_start();
require_once 'conexion/conexion.php';


$conexion = new Conexion();
$db = $conexion->getConexion();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        // Insertar nuevo animal
        $stmt = $db->prepare("CALL InsertarAnimal(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississs", 
            $_POST['p_id_especie'],
            $_POST['p_nombre_cientifico'],
            $_POST['p_nombre_comun'],
            $_POST['p_edad'],
            $_POST['p_ubicacion'],
            $_POST['p_estado'],
            $_POST['p_descripcion']
        );
        $stmt->execute();
        header("Location: animales.php?success=added");
        exit();
    } elseif ($action == 'edit') {
        // Actualizar animal
        $stmt = $db->prepare("CALL actualizar_animal(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiisss", 
            $_POST['id_animal'],
            $_POST['nombre_cientifico'],
            $_POST['nombre_comun'],
            $_POST['id_especie'],
            $_POST['edad'],
            $_POST['ubicacion'],
            $_POST['estado'],
            $_POST['descripcion']
        );
        $stmt->execute();
        header("Location: animales.php?success=updated");
        exit();
    }
}

// Eliminar animal
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("CALL EliminarAnimal(?)");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header("Location: animales.php?success=deleted");
    exit();
}

// Obtener especies para formularios
$especies = $db->query("SELECT * FROM especies")->fetch_all(MYSQLI_ASSOC);

// Obtener datos para edición
$animal = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $result = $db->query("SELECT * FROM animales WHERE id_animal = " . $_GET['id']);
    $animal = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Animales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

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

    .container {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 30px;
        margin-top: 20px;
        margin-bottom: 20px;
    }

    /* Alert styles */
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
        border-left: 4px solid var(--primary-color);
    }

    /* Header styles */
    .d-flex.justify-content-between.align-items-center.mb-4 {
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
        margin-bottom: 25px;
    }

    h2 {
        color: var(--primary-dark);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Button styles */
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

    .btn-secondary {
        background-color: #6c757d;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    .btn-warning {
        background-color: var(--accent-color);
        color: var(--text-dark);
    }

    .btn-warning:hover {
        background-color: #FFA000;
    }

    .btn-danger {
        background-color: #f44336;
    }

    .btn-danger:hover {
        background-color: #d32f2f;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 14px;
    }

    /* Table styles */
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
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

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0,0,0,0.02);
    }

    .table tr:hover {
        background-color: rgba(0,0,0,0.05);
    }

    /* Badge styles */
    .badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .bg-success {
        background-color: var(--primary-color) !important;
    }

    .bg-danger {
        background-color: #f44336 !important;
    }

    .bg-warning {
        background-color: var(--accent-color) !important;
        color: var(--text-dark) !important;
    }

    /* Form styles */
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--primary-dark);
    }

    .form-control, .form-select {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        font-family: 'Poppins', sans-serif;
        transition: var(--transition);
        margin-bottom: 15px;
    }

    .form-control:focus, .form-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px var(--primary-light);
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .form-select {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 16px;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }

    .col-md-6 {
        padding: 0 10px;
        flex: 0 0 50%;
        max-width: 50%;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .container {
            padding: 20px;
        }
        
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
        }
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in {
        animation: fadeIn 0.5s ease forwards;
    }

    /* Confirmation dialog */
    .confirm-dialog {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .confirm-content {
        background-color: white;
        padding: 20px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        max-width: 400px;
        width: 100%;
    }
</style>
    
    
    <div class="container mt-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                switch ($_GET['success']) {
                    case 'added': echo 'Animal agregado correctamente'; break;
                    case 'updated': echo 'Animal actualizado correctamente'; break;
                    case 'deleted': echo 'Animal eliminado correctamente'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Lista de Animales</h2>
                <a href="?action=add" class="btn btn-primary">Agregar Animal</a>
            </div>
            
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Especie</th>
                        <th>Edad</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $result = $db->query("SELECT a.*, e.nombre_especie 
                                         FROM animales a 
                                         JOIN especies e ON a.id_especie = e.id_especie");
                    while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_animal']; ?></td>
                            <td><?php echo $row['nombre_comun']; ?></td>
                            <td><?php echo $row['nombre_especie']; ?></td>
                            <td><?php echo $row['edad']; ?> años</td>
                            <td>
                                <span class="badge 
                                    <?php 
                                    switch ($row['estado']) {
                                        case 'Sano': echo 'bg-success'; break;
                                        case 'Enfermo': echo 'bg-danger'; break;
                                        case 'Recuperación': echo 'bg-warning'; break;
                                    }
                                    ?>">
                                    <?php echo $row['estado']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="?action=edit&id=<?php echo $row['id_animal']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                <a href="?delete=<?php echo $row['id_animal']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

       <!-- <button><a href="dashboard.php">Volver</a></button> -->
        <?php else: ?>
            <h2><?php echo $action == 'add' ? 'Agregar Animal' : 'Editar Animal'; ?></h2>
            
            <form method="POST" class="mt-4">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="id_animal" value="<?php echo $animal['id_animal']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Nombre Científico</label>
                    <input type="text" class="form-control" name="p_nombre_cientifico" 
                           value="<?php echo $animal ? $animal['nombre_cientifico'] : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nombre Común</label>
                    <input type="text" class="form-control" name="p_nombre_comun" 
                           value="<?php echo $animal ? $animal['nombre_comun'] : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Especie</label>
                    <select class="form-select" name="p_id_especie" required>
                        <option value="">Seleccionar especie</option>
                        <?php foreach ($especies as $especie): ?>
                            <option value="<?php echo $especie['id_especie']; ?>"
                                <?php if ($animal && $animal['id_especie'] == $especie['id_especie']) echo 'selected'; ?>>
                                <?php echo $especie['nombre_especie']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Edad (años)</label>
                        <input type="number" class="form-control" name="p_edad" 
                               value="<?php echo $animal ? $animal['edad'] : ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado" required>
                            <option value="Sano" <?php if ($animal && $animal['estado'] == 'Sano') echo 'selected'; ?>>Sano</option>
                            <option value="Enfermo" <?php if ($animal && $animal['estado'] == 'Enfermo') echo 'selected'; ?>>Enfermo</option>
                            <option value="Recuperación" <?php if ($animal && $animal['estado'] == 'Recuperación') echo 'selected'; ?>>Recuperación</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Ubicación</label>
                    <input type="text" class="form-control" name="p_ubicacion" 
                           value="<?php echo $animal ? $animal['ubicacion'] : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" name="p_descripcion" rows="3"><?php echo $animal ? $animal['descripcion'] : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="animales.php" class="btn btn-secondary">Cancelar</a>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>