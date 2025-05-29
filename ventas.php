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
        // Insertar nueva venta
        $stmt = $db->prepare("CALL InsertarVenta(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiddss", 
            $_POST['id_produccion'],
            $_POST['id_animal'],
            $_POST['cantidad'],
            $_POST['precio_total'],
            $_POST['fecha_venta'],
            $_POST['comprador']
        );
        $stmt->execute();
        header("Location: ventas.php?success=added");
        exit();
    } elseif ($action == 'process_order') {
        // Procesar pedido
        $stmt = $db->prepare("CALL RealizarCompra(?, ?, ?)");
        $stmt->bind_param("iss", 
            $_POST['id_cliente'],
            $_POST['direccion_envio'],
            $_POST['metodo_pago']
        );
        $stmt->execute();
        header("Location: ventas.php?success=order_processed");
        exit();
    }
}

// Eliminar venta
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM ventas WHERE id_venta = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header("Location: ventas.php?success=deleted");
    exit();
}

// Obtener datos para reportes
$ventas = $db->query("SELECT v.*, p.nombre as producto_nombre, c.nombre as cliente_nombre 
                     FROM ventas v
                     JOIN inventario_productos p ON v.id_producto = p.id_producto
                     LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                     ORDER BY v.fecha_venta DESC")->fetch_all(MYSQLI_ASSOC);

$pedidos = $db->query("SELECT p.*, c.nombre as cliente_nombre 
                      FROM pedidos p
                      JOIN clientes c ON p.id_cliente = c.id_cliente
                      ORDER BY p.fecha_pedido DESC")->fetch_all(MYSQLI_ASSOC);

$productos = $db->query("SELECT * FROM inventario_productos WHERE disponible = 1")->fetch_all(MYSQLI_ASSOC);
$clientes = $db->query("SELECT * FROM clientes")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - Granja</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
                    /* Estilos del dashboard existentes */
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

            /* Estilos generales */
            body {
                font-family: 'Poppins', sans-serif;
                background-color: var(--bg-light);
                margin: 0;
                padding: 0;
                color: var(--text-dark);
            }

            .main-content {
                margin-left: 250px;
                padding: 20px;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 15px;
            }

            /* Header styles */
            header {
                background-color: white;
                padding: 15px 0;
                box-shadow: var(--box-shadow);
                margin-bottom: 20px;
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
                color: var(--primary-color);
                font-size: 24px;
            }

            .logo h1 {
                margin: 0;
                font-size: 20px;
                color: var(--primary-dark);
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
                text-align: center;
            }

            .nav-link {
                color: rgba(255,255,255,0.8);
                padding: 10px 15px;
                margin-bottom: 5px;
                border-radius: var(--border-radius);
                transition: var(--transition);
                display: flex;
                align-items: center;
                text-decoration: none;
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

            /* Page title */
            .page-title {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .page-title h2 {
                display: flex;
                align-items: center;
                gap: 10px;
                color: var(--primary-dark);
                margin: 0;
            }

            .btn {
                background-color: var(--primary-color);
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: var(--border-radius);
                cursor: pointer;
                transition: var(--transition);
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }

            .btn:hover {
                background-color: var(--primary-dark);
            }

            .btn-outline {
                background-color: transparent;
                border: 1px solid var(--primary-color);
                color: var(--primary-color);
            }

            .btn-outline:hover {
                background-color: var(--primary-light);
            }

            /* Card styles */
            .card {
                background-color: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                margin-bottom: 20px;
            }

            .card-header {
                padding: 15px 20px;
                border-bottom: 1px solid #eee;
            }

            .card-title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 500;
                color: var(--primary-dark);
            }

            .card-body {
                padding: 20px;
            }

            /* Form styles */
            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }

            .form-control {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: var(--border-radius);
                font-family: inherit;
            }

            .select-control {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: var(--border-radius);
                background-color: white;
                font-family: inherit;
            }

            .text-right {
                text-align: right;
            }

            /* Alert styles */
            .alert {
                padding: 10px 15px;
                border-radius: var(--border-radius);
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .alert-success {
                background-color: var(--primary-light);
                color: var(--primary-dark);
                border: 1px solid var(--primary-color);
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
                background-color: var(--bg-light);
                font-weight: 500;
            }

            /* Status styles */
            .status {
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 500;
            }

            .status-pendiente {
                background-color: #FFF9C4;
                color: #F57F17;
            }

            .status-completada {
                background-color: #C8E6C9;
                color: #2E7D32;
            }

            .status-cancelada {
                background-color: #FFCDD2;
                color: #C62828;
            }

            /* Sale card styles */
            .sale-card {
                background-color: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                padding: 15px;
                margin-bottom: 15px;
                transition: var(--transition);
            }

            .sale-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            }

            .sale-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }

            .sale-title {
                font-weight: 600;
                color: var(--primary-dark);
            }

            .sale-details {
                display: flex;
                justify-content: space-between;
                font-size: 14px;
                color: #666;
            }

            .sale-total {
                font-weight: 600;
                color: var(--primary-dark);
                font-size: 16px;
                text-align: right;
                margin-top: 10px;
            }

            .product-list {
                margin-top: 10px;
            }

            .product-item {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px dashed #eee;
            }

            .product-name {
                flex: 2;
            }

            .product-qty {
                flex: 1;
                text-align: center;
            }

            .product-price {
                flex: 1;
                text-align: right;
            }

            .actions {
                display: flex;
                gap: 10px;
                margin-top: 10px;
            }

            .action-btn {
                color: #666;
                cursor: pointer;
                transition: var(--transition);
            }

            .action-btn:hover {
                color: var(--primary-color);
            }

            .action-btn.delete:hover {
                color: #C62828;
            }

            /* Summary card styles */
            .summary-card {
                background-color: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                padding: 20px;
                margin-bottom: 20px;
            }

            .summary-title {
                font-size: 18px;
                font-weight: 600;
                color: var(--primary-dark);
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .summary-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #f0f0f0;
            }

            .summary-label {
                color: #666;
            }

            .summary-value {
                font-weight: 600;
            }

            .summary-total {
                font-size: 18px;
                color: var(--primary-dark);
                margin-top: 10px;
                padding-top: 10px;
                border-top: 2px solid var(--primary-light);
            }

            /* Tab styles */
            .tab-container {
                display: flex;
                margin-bottom: 20px;
                border-bottom: 1px solid #ddd;
            }

            .tab {
                padding: 10px 20px;
                cursor: pointer;
                border-bottom: 3px solid transparent;
                transition: var(--transition);
            }

            .tab.active {
                border-bottom-color: var(--primary-color);
                color: var(--primary-color);
                font-weight: 500;
            }

            .tab-content {
                display: none;
            }

            .tab-content.active {
                display: block;
            }

            /* Grid layout */
            .grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
            }

            /* Modal styles */
            .modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 20px;
                border-radius: 8px;
                width: 60%;
                max-width: 600px;
            }

            .close {
                float: right;
                font-size: 28px;
                cursor: pointer;
            }

            /* Animation */
            .fade-in {
                animation: fadeIn 0.5s;
            }

            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
    </style>
</head>
<body>
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

    <div class="main-content">
        <header>
            <div class="container header-content">
                <div class="logo">
                    <i class="fas fa-leaf"></i>
                    <h1>Granja Verde</h1>
                </div>
                <div class="user-info">
                    <span>Bienvenido, <?php echo $_SESSION['nombre']; ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nombre']); ?>&background=4CAF50&color=fff" alt="Usuario">
                </div>
            </div>
        </header>

        <div class="container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success fade-in">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                        switch($_GET['success']) {
                            case 'added': echo "Venta registrada correctamente"; break;
                            case 'updated': echo "Venta actualizada correctamente"; break;
                            case 'deleted': echo "Venta eliminada correctamente"; break;
                            case 'order_processed': echo "Pedido procesado correctamente"; break;
                        }
                    ?>
                </div>
            <?php endif; ?>

            <div class="page-title">
                <h2><i class="bi bi-cash-coin"></i> Gestión de Ventas</h2>
                <div>
                    <a href="ventas.php?action=add" class="btn">
                        <i class="fas fa-plus"></i> Nueva Venta
                    </a>
                </div>
            </div>

            <div class="tab-container">
                <div class="tab active" onclick="changeTab('sales')">Ventas</div>
                <div class="tab" onclick="changeTab('orders')">Pedidos</div>
                <div class="tab" onclick="changeTab('reports')">Reportes</div>
            </div>

            <!-- Pestaña de Ventas -->
            <div id="sales" class="tab-content active">
                <?php if ($action == 'add' || $action == 'edit'): ?>
                    <div class="card fade-in">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i>
                                <?php echo $action == 'add' ? 'Nueva Venta' : 'Editar Venta'; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="id_produccion">Producción</label>
                                    <select class="form-control select-control" id="id_produccion" name="id_produccion" required>
                                        <option value="">Seleccionar producción</option>
                                        <?php 
                                            $producciones = $db->query("SELECT p.*, a.nombre_comun 
                                                                      FROM produccion p
                                                                      JOIN animales a ON p.id_animal = a.id_animal");
                                            while($prod = $producciones->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $prod['id_produccion']; ?>"
                                                <?php if(isset($venta) && $venta['id_produccion'] == $prod['id_produccion']) echo 'selected'; ?>>
                                                <?php echo $prod['nombre_comun'] . ' - ' . $prod['tipo_produccion'] . ' (' . $prod['cantidad'] . ')'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="id_animal">Animal</label>
                                    <select class="form-control select-control" id="id_animal" name="id_animal" required>
                                        <option value="">Seleccionar animal</option>
                                        <?php 
                                            $animales = $db->query("SELECT * FROM animales");
                                            while($animal = $animales->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $animal['id_animal']; ?>"
                                                <?php if(isset($venta) && $venta['id_animal'] == $animal['id_animal']) echo 'selected'; ?>>
                                                <?php echo $animal['nombre_comun']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cantidad">Cantidad</label>
                                    <input type="number" step="0.01" class="form-control" id="cantidad" name="cantidad" 
                                           value="<?php echo isset($venta) ? $venta['cantidad'] : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="precio_total">Precio Total</label>
                                    <input type="number" step="0.01" class="form-control" id="precio_total" name="precio_total" 
                                           value="<?php echo isset($venta) ? $venta['precio_total'] : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="fecha_venta">Fecha de Venta</label>
                                    <input type="date" class="form-control" id="fecha_venta" name="fecha_venta" 
                                           value="<?php echo isset($venta) ? $venta['fecha_venta'] : date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comprador">Comprador</label>
                                    <input type="text" class="form-control" id="comprador" name="comprador" 
                                           value="<?php echo isset($venta) ? $venta['comprador'] : ''; ?>" required>
                                </div>
                                
                                <div class="form-group text-right">
                                    <a href="ventas.php" class="btn btn-outline">Cancelar</a>
                                    <button type="submit" class="btn">Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach($ventas as $venta): ?>
                            <div class="sale-card fade-in">
                                <div class="sale-header">
                                    <div class="sale-title">Venta #<?php echo $venta['id_venta']; ?></div>
                                    <span class="status status-<?php echo strtolower($venta['estado']); ?>">
                                        <?php echo $venta['estado']; ?>
                                    </span>
                                </div>
                                <div class="sale-details">
                                    <div>
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo date('d/m/Y', strtotime($venta['fecha_venta'])); ?>
                                    </div>
                                    <div>
                                        <?php if($venta['cliente_nombre']): ?>
                                            <i class="fas fa-user"></i> <?php echo $venta['cliente_nombre']; ?>
                                        <?php else: ?>
                                            <i class="fas fa-store"></i> <?php echo $venta['comprador']; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-list">
                                    <div class="product-item">
                                        <div class="product-name"><?php echo $venta['producto_nombre']; ?></div>
                                        <div class="product-qty"><?php echo $venta['cantidad']; ?></div>
                                        <div class="product-price">$<?php echo number_format($venta['precio_unitario'], 2); ?></div>
                                    </div>
                                </div>
                                <div class="sale-total text-right">
                                    Total: $<?php echo number_format($venta['precio_total'], 2); ?>
                                </div>
                                <div class="actions">
                                    <a href="ventas.php?action=edit&id=<?php echo $venta['id_venta']; ?>" class="action-btn edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="ventas.php?delete=<?php echo $venta['id_venta']; ?>" class="action-btn delete" 
                                       onclick="return confirm('¿Estás seguro de eliminar esta venta?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pestaña de Pedidos -->
            <div id="orders" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-shopping-cart"></i> Pedidos de Clientes
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pedidos as $pedido): ?>
                                    <tr>
                                        <td><?php echo $pedido['id_pedido']; ?></td>
                                        <td><?php echo $pedido['cliente_nombre']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($pedido['fecha_pedido'])); ?></td>
                                        <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                        <td>
                                            <span class="status status-<?php echo strtolower($pedido['estado']); ?>">
                                                <?php echo $pedido['estado']; ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button class="action-btn edit" onclick="showOrderDetails(<?php echo $pedido['id_pedido']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if($pedido['estado'] == 'pendiente'): ?>
                                                <button class="action-btn" onclick="processOrder(<?php echo $pedido['id_pedido']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pestaña de Reportes -->
            <div id="reports" class="tab-content">
                <div class="grid">
                    <div class="summary-card">
                        <div class="summary-title">
                            <i class="fas fa-chart-line"></i> Resumen de Ventas
                        </div>
                        <?php 
                            $totalVentas = $db->query("SELECT SUM(precio_total) as total FROM ventas")->fetch_assoc();
                            $totalPedidos = $db->query("SELECT SUM(total) as total FROM pedidos")->fetch_assoc();
                            $ventasHoy = $db->query("SELECT SUM(precio_total) as total FROM ventas WHERE DATE(fecha_venta) = CURDATE()")->fetch_assoc();
                            $pedidosHoy = $db->query("SELECT SUM(total) as total FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()")->fetch_assoc();
                        ?>
                        <div class="summary-item">
                            <span class="summary-label">Ventas totales:</span>
                            <span class="summary-value">$<?php echo number_format($totalVentas['total'] ?? 0, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Pedidos totales:</span>
                            <span class="summary-value">$<?php echo number_format($totalPedidos['total'] ?? 0, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Ventas hoy:</span>
                            <span class="summary-value">$<?php echo number_format($ventasHoy['total'] ?? 0, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Pedidos hoy:</span>
                            <span class="summary-value">$<?php echo number_format($pedidosHoy['total'] ?? 0, 2); ?></span>
                        </div>
                        <div class="summary-item summary-total">
                            <span>Total ingresos:</span>
                            <span>$<?php echo number_format(($totalVentas['total'] ?? 0) + ($totalPedidos['total'] ?? 0), 2); ?></span>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-title">
                            <i class="fas fa-star"></i> Productos más vendidos
                        </div>
                        <?php 
                            $topProductos = $db->query("SELECT p.nombre, SUM(v.cantidad) as total_vendido 
                                                       FROM ventas v
                                                       JOIN inventario_productos p ON v.id_producto = p.id_producto
                                                       GROUP BY v.id_producto
                                                       ORDER BY total_vendido DESC
                                                       LIMIT 5");
                            while($producto = $topProductos->fetch_assoc()):
                        ?>
                            <div class="summary-item">
                                <span class="summary-label"><?php echo $producto['nombre']; ?></span>
                                <span class="summary-value"><?php echo $producto['total_vendido']; ?> unidades</span>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="summary-card">
                        <div class="summary-title">
                            <i class="fas fa-users"></i> Mejores clientes
                        </div>
                        <?php 
                            $topClientes = $db->query("SELECT c.nombre, SUM(p.total) as total_gastado 
                                                      FROM pedidos p
                                                      JOIN clientes c ON p.id_cliente = c.id_cliente
                                                      GROUP BY p.id_cliente
                                                      ORDER BY total_gastado DESC
                                                      LIMIT 5");
                            while($cliente = $topClientes->fetch_assoc()):
                        ?>
                            <div class="summary-item">
                                <span class="summary-label"><?php echo $cliente['nombre']; ?></span>
                                <span class="summary-value">$<?php echo number_format($cliente['total_gastado'], 2); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles de pedido -->
    <div id="orderModal" class="modal" style="display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color:#fefefe; margin:5% auto; padding:20px; border-radius:8px; width:60%; max-width:600px;">
            <span class="close" onclick="closeModal()" style="float:right; font-size:28px; cursor:pointer;">&times;</span>
            <h3 id="modalTitle">Detalles del Pedido</h3>
            <div id="orderDetails"></div>
        </div>
    </div>

    <!-- Modal para procesar pedido -->
    <div id="processModal" class="modal" style="display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color:#fefefe; margin:5% auto; padding:20px; border-radius:8px; width:60%; max-width:500px;">
            <span class="close" onclick="closeModal()" style="float:right; font-size:28px; cursor:pointer;">&times;</span>
            <h3>Procesar Pedido</h3>
            <form id="processForm" method="POST">
                <input type="hidden" name="action" value="process_order">
                <input type="hidden" id="process_cliente_id" name="id_cliente">
                
                <div class="form-group">
                    <label for="direccion_envio">Dirección de Envío</label>
                    <input type="text" class="form-control" id="direccion_envio" name="direccion_envio" required>
                </div>
                
                <div class="form-group">
                    <label for="metodo_pago">Método de Pago</label>
                    <select class="form-control select-control" id="metodo_pago" name="metodo_pago" required>
                        <option value="">Seleccionar método</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>
                
                <div class="form-group text-right">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn">Procesar Pedido</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Cambiar entre pestañas
        function changeTab(tabId) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            document.querySelector(`.tab[onclick="changeTab('${tabId}')"]`).classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        // Mostrar detalles del pedido
        function showOrderDetails(orderId) {
            fetch(`conexion/get_order_details.php?id=${orderId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetails').innerHTML = data;
                    document.getElementById('orderModal').style.display = 'block';
                });
        }

        // Procesar pedido
        function processOrder(orderId) {
            fetch(`conexion/get_order_info.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('process_cliente_id').value = data.id_cliente;
                    document.getElementById('direccion_envio').value = data.direccion_envio || '';
                    document.getElementById('processModal').style.display = 'block';
                });
        }

        // Cerrar modal
        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
            document.getElementById('processModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                closeModal();
            }
        }
    </script>
</body>
</html>