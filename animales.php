<?php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$conexion = new Conexion();
$db = $conexion->getConexion();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        // Insertar nuevo animal
        $stmt = $db->prepare("CALL InsertarAnimal(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississs", 
            $_POST['id_especie'],
            $_POST['nombre_cientifico'],
            $_POST['nombre_comun'],
            $_POST['edad'],
            $_POST['ubicacion'],
            $_POST['estado'],
            $_POST['descripcion']
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

        <button><a href="dashboard.php">Volver</a></button>
        <?php else: ?>
            <h2><?php echo $action == 'add' ? 'Agregar Animal' : 'Editar Animal'; ?></h2>
            
            <form method="POST" class="mt-4">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="id_animal" value="<?php echo $animal['id_animal']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Nombre Científico</label>
                    <input type="text" class="form-control" name="nombre_cientifico" 
                           value="<?php echo $animal ? $animal['nombre_cientifico'] : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nombre Común</label>
                    <input type="text" class="form-control" name="nombre_comun" 
                           value="<?php echo $animal ? $animal['nombre_comun'] : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Especie</label>
                    <select class="form-select" name="id_especie" required>
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
                        <input type="number" class="form-control" name="edad" 
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
                    <input type="text" class="form-control" name="ubicacion" 
                           value="<?php echo $animal ? $animal['ubicacion'] : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="3"><?php echo $animal ? $animal['descripcion'] : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="animales.php" class="btn btn-secondary">Cancelar</a>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>