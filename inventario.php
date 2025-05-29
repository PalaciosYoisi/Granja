<?php
session_start();
require_once 'conexion/conexion.php';


$conexion = new Conexion();
$db = $conexion->getConexion();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        // Insertar nuevo producto en inventario
        $stmt = $db->prepare("CALL InsertarInventario(?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssi", 
            $_POST['nombre_producto'],
            $_POST['cantidad'],
            $_POST['unidad_medida'],
            $_POST['fecha_ingreso'],
            $_POST['id_proveedor']
        );
        $stmt->execute();
        header("Location: inventario.php?success=added");
        exit();
    } elseif ($action == 'edit') {
        // Actualizar producto en inventario
        $stmt = $db->prepare("CALL actualizar_inventario(?, ?, ?, ?)");
        $stmt->bind_param("isis", 
            $_POST['id_inventario'],
            $_POST['producto'],
            $_POST['cantidad'],
            $_POST['fecha_ingreso']
        );
        $stmt->execute();
        header("Location: inventario.php?success=updated");
        exit();
    } elseif ($action == 'add_product') {
        // Insertar nuevo producto en inventario_productos
        $stmt = $db->prepare("CALL InsertarInventarioProducto(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsss", 
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['cantidad'],
            $_POST['categoria'],
            $_POST['disponible']
        );
        $stmt->execute();
        header("Location: inventario.php?tab=productos&success=added");
        exit();
    } elseif ($action == 'edit_product') {
        // Actualizar producto en inventario_productos
        $stmt = $db->prepare("CALL ActualizarInventarioProducto(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdiss", 
            $_POST['id_producto'],
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['cantidad'],
            $_POST['categoria'],
            $_POST['disponible']
        );
        $stmt->execute();
        header("Location: inventario.php?tab=productos&success=updated");
        exit();
    }
}

// Eliminar producto
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("CALL EliminarInventario(?)");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header("Location: inventario.php?success=deleted");
    exit();
}

// Eliminar producto de venta
if (isset($_GET['delete_product'])) {
    $stmt = $db->prepare("CALL EliminarInventarioProducto(?)");
    $stmt->bind_param("i", $_GET['delete_product']);
    $stmt->execute();
    header("Location: inventario.php?tab=productos&success=deleted");
    exit();
}

// Obtener datos para edición
$producto = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $result = $db->query("SELECT * FROM inventario WHERE id_producto = " . $_GET['id']);
    $producto = $result->fetch_assoc();
}

$producto_venta = null;
if ($action == 'edit_product' && isset($_GET['id_producto'])) {
    $result = $db->query("SELECT * FROM inventario_productos WHERE id_producto = " . $_GET['id_producto']);
    $producto_venta = $result->fetch_assoc();
}

// Obtener lista de productos
$inventario = $db->query("SELECT i.*, p.nombre as proveedor_nombre 
                         FROM inventario i 
                         LEFT JOIN proveedores p ON i.id_proveedor = p.id_proveedor 
                         ORDER BY nombre_producto")->fetch_all(MYSQLI_ASSOC);

$productos_venta = $db->query("SELECT * FROM inventario_productos ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$proveedores = $db->query("SELECT * FROM proveedores ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Determinar pestaña activa
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'inventario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Granja</title>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
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

        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }

        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-link i {
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

        header {
            background-color: var(--primary-color);
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
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
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

        .btn i {
            font-size: 16px;
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

        .btn-accent {
            background-color: var(--accent-color);
            color: var(--text-dark);
        }

        .btn-accent:hover {
            background-color: #FFA000;
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

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-disponible {
            background-color: #C8E6C9;
            color: #2E7D32;
        }

        .status-agotado {
            background-color: #FFCDD2;
            color: #C62828;
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

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .product-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .product-img {
            height: 180px;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 60px;
        }

        .product-body {
            padding: 15px;
        }

        .product-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-dark);
        }

        .product-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 14px;
        }

        .product-detail {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-detail i {
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        .product-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

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

        .empty-state p {
            color: #777;
            margin-bottom: 20px;
        }

        /* Pestañas */
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: var(--transition);
        }

        .tab:hover {
            background-color: #f5f5f5;
        }

        .tab.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-dark);
            font-weight: 500;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab {
                flex: 1;
                text-align: center;
                padding: 10px;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse:hover {
            animation: pulse 1s infinite;
        }

        /* Efecto de carga */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
    </style>
</head>
<body>
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

    <div class="main-content">
        <header>
            <div class="container header-content">
                <div class="logo">
                    <i class="fas fa-leaf"></i>
                    <h1>Granja San josé</h1>
                </div>
                <div class="user-info">
                    <span>Bienvenido, <?php echo $_SESSION['nombre']; ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nombre']); ?>&background=4CAF50&color=fff" alt="Usuario">
                </div>
            </div>
        </header>

        <div class="container">
            <!-- Mensajes de éxito/error -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success fade-in">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                        switch($_GET['success']) {
                            case 'added': echo 'Producto agregado correctamente'; break;
                            case 'updated': echo 'Producto actualizado correctamente'; break;
                            case 'deleted': echo 'Producto eliminado correctamente'; break;
                        }
                    ?>
                </div>
            <?php endif; ?>

            <div class="page-title">
                <h2><i class="bi bi-box-seam"></i> Gestión de Inventario</h2>
                <div>
                    <?php if ($action == 'list' && $tab == 'inventario'): ?>
                        <a href="inventario.php?action=add" class="btn">
                            <i class="fas fa-plus"></i> Agregar Producto
                        </a>
                    <?php elseif ($action == 'list' && $tab == 'productos'): ?>
                        <a href="inventario.php?action=add_product&tab=productos" class="btn">
                            <i class="fas fa-plus"></i> Agregar Producto
                        </a>
                    <?php else: ?>
                        <a href="inventario.php<?php echo $tab == 'productos' ? '?tab=productos' : ''; ?>" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pestañas -->
            <div class="tabs">
                <div class="tab <?php echo $tab == 'inventario' ? 'active' : ''; ?>" onclick="location.href='inventario.php'">
                    <i class="fas fa-boxes"></i> Inventario General
                </div>
                <div class="tab <?php echo $tab == 'productos' ? 'active' : ''; ?>" onclick="location.href='inventario.php?tab=productos'">
                    <i class="fas fa-shopping-basket"></i> Productos para Venta
                </div>
            </div>

            <!-- Contenido de las pestañas -->
            <div class="tab-content <?php echo $tab == 'inventario' ? 'active' : ''; ?>">
                <?php if ($action == 'list'): ?>
                    <!-- Listado de productos de inventario -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Productos en Inventario</div>
                        </div>
                        <div class="card-body">
                            <?php if (count($inventario) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Cantidad</th>
                                            <th>Unidad</th>
                                            <th>Fecha Ingreso</th>
                                            <th>Proveedor</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inventario as $item): ?>
                                            <tr>
                                                <td><?php echo $item['id_producto']; ?></td>
                                                <td><?php echo $item['nombre_producto']; ?></td>
                                                <td><?php echo $item['cantidad']; ?></td>
                                                <td><?php echo $item['unidad_medida']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($item['fecha_ingreso'])); ?></td>
                                                <td><?php echo $item['proveedor_nombre'] ?? 'N/A'; ?></td>
                                                <td class="actions">
                                                    <a href="inventario.php?action=edit&id=<?php echo $item['id_producto']; ?>" class="action-btn edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="inventario.php?delete=<?php echo $item['id_producto']; ?>" class="action-btn delete" onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <p>No hay productos registrados en el inventario</p>
                                    <a href="inventario.php?action=add" class="btn">
                                        <i class="fas fa-plus"></i> Agregar Producto
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($action == 'add' || $action == 'edit'): ?>
                    <!-- Formulario para agregar/editar producto de inventario -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i>
                                <?php echo $action == 'add' ? 'Agregar Producto' : 'Editar Producto'; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="inventario.php?action=<?php echo $action; ?>">
                                <?php if ($action == 'edit'): ?>
                                    <input type="hidden" name="id_inventario" value="<?php echo $producto['id_producto']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label for="nombre_producto">Nombre del Producto</label>
                                    <input type="text" class="form-control" id="nombre_producto" name="nombre_producto" 
                                           value="<?php echo $producto['nombre_producto'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cantidad">Cantidad</label>
                                    <input type="number" step="0.01" class="form-control" id="cantidad" name="cantidad" 
                                           value="<?php echo $producto['cantidad'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="unidad_medida">Unidad de Medida</label>
                                    <input type="text" class="form-control" id="unidad_medida" name="unidad_medida" 
                                           value="<?php echo $producto['unidad_medida'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="fecha_ingreso">Fecha de Ingreso</label>
                                    <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" 
                                           value="<?php echo $producto['fecha_ingreso'] ?? date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="id_proveedor">Proveedor</label>
                                    <select class="form-control select-control" id="id_proveedor" name="id_proveedor">
                                        <option value="">Seleccione un proveedor</option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?php echo $proveedor['id_proveedor']; ?>"
                                                <?php if (isset($producto['id_proveedor']) && $producto['id_proveedor'] == $proveedor['id_proveedor']) echo 'selected'; ?>>
                                                <?php echo $proveedor['nombre']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contenido de productos para venta -->
            <div class="tab-content <?php echo $tab == 'productos' ? 'active' : ''; ?>">
                <?php if ($action == 'list'): ?>
                    <!-- Listado de productos para venta -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Productos para Venta</div>
                        </div>
                        <div class="card-body">
                            <?php if (count($productos_venta) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th>Precio</th>
                                            <th>Cantidad</th>
                                            <th>Categoría</th>
                                            <th>Disponible</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos_venta as $producto): ?>
                                            <tr>
                                                <td><?php echo $producto['id_producto']; ?></td>
                                                <td><?php echo $producto['nombre']; ?></td>
                                                <td><?php echo substr($producto['descripcion'] ?? '', 0, 30) . '...'; ?></td>
                                                <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                                                <td><?php echo $producto['cantidad']; ?></td>
                                                <td><?php echo $producto['categoria']; ?></td>
                                                <td>
                                                    <span class="status status-<?php echo $producto['disponible'] ? 'disponible' : 'agotado'; ?>">
                                                        <?php echo $producto['disponible'] ? 'Disponible' : 'Agotado'; ?>
                                                    </span>
                                                </td>
                                                <td class="actions">
                                                    <a href="inventario.php?action=edit_product&id_producto=<?php echo $producto['id_producto']; ?>&tab=productos" class="action-btn edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="inventario.php?delete_product=<?php echo $producto['id_producto']; ?>&tab=productos" class="action-btn delete" onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-shopping-basket"></i>
                                    <p>No hay productos registrados para venta</p>
                                    <a href="inventario.php?action=add_product&tab=productos" class="btn">
                                        <i class="fas fa-plus"></i> Agregar Producto
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($action == 'add_product' || $action == 'edit_product'): ?>
                    <!-- Formulario para agregar/editar producto para venta -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-<?php echo $action == 'add_product' ? 'plus' : 'edit'; ?>"></i>
                                <?php echo $action == 'add_product' ? 'Agregar Producto para Venta' : 'Editar Producto para Venta'; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="inventario.php?action=<?php echo $action; ?>&tab=productos">
                                <?php if ($action == 'edit_product'): ?>
                                    <input type="hidden" name="id_producto" value="<?php echo $producto_venta['id_producto']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label for="nombre">Nombre del Producto</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo $producto_venta['nombre'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo $producto_venta['descripcion'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="precio">Precio</label>
                                    <input type="number" step="0.01" class="form-control" id="precio" name="precio" 
                                           value="<?php echo $producto_venta['precio'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cantidad">Cantidad en Stock</label>
                                    <input type="number" class="                                    <input type="number" class="form-control" id="cantidad" name="cantidad" 
                                           value="<?php echo $producto_venta['cantidad'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="categoria">Categoría</label>
                                    <input type="text" class="form-control" id="categoria" name="categoria" 
                                           value="<?php echo $producto_venta['categoria'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="disponible">Disponibilidad</label>
                                    <select class="form-control select-control" id="disponible" name="disponible" required>
                                        <option value="1" <?php if (isset($producto_venta['disponible']) && $producto_venta['disponible'] == 1) echo 'selected'; ?>>Disponible</option>
                                        <option value="0" <?php if (isset($producto_venta['disponible']) && $producto_venta['disponible'] == 0) echo 'selected'; ?>>Agotado</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Mostrar mensajes de éxito/error con animación
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
            
            // Validación de formularios
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const inputs = this.querySelectorAll('[required]');
                    let valid = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            input.style.borderColor = '#f44336';
                            valid = false;
                        } else {
                            input.style.borderColor = '#ddd';
                        }
                    });
                    
                    if (!valid) {
                        e.preventDefault();
                        alert('Por favor complete todos los campos requeridos');
                    }
                });
            });
        });
    </script>
</body>
</html>