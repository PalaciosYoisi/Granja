<?php
require_once 'conexion/auth_functions.php';

// Verificar sesión
if (!isset($_SESSION['cliente_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener items del carrito
$carrito = [];
$total = 0;
$stmt = $conexion->prepare("
    SELECT c.id_item, c.cantidad, p.id_producto, p.nombre, p.precio, p.cantidad as stock
    FROM carrito_compras c
    JOIN inventario_productos p ON c.id_producto = p.id_producto
    WHERE c.id_cliente = ?
");
$stmt->bind_param("i", $_SESSION['cliente_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($item = $result->fetch_assoc()) {
    $carrito[] = $item;
    $total += $item['precio'] * $item['cantidad'];
}

// Manejar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actualizar_carrito'])) {
        foreach ($_POST['cantidad'] as $id_item => $cantidad) {
            $cantidad = intval($cantidad);
            $id_item = intval($id_item);
            
            if ($cantidad <= 0) {
                // Eliminar item
                $stmt = $conexion->prepare("DELETE FROM carrito_compras WHERE id_item = ? AND id_usuario = ?");
                $stmt->bind_param("ii", $id_item, $_SESSION['cliente_id']);
                $stmt->execute();
            } else {
                // Verificar stock
                $stmt = $conexion->prepare("
                    SELECT p.cantidad 
                    FROM carrito_compras c
                    JOIN inventario_productos p ON c.id_producto = p.id_producto
                    WHERE c.id_item = ? AND c.id_usuario = ?
                ");
                $stmt->bind_param("ii", $id_item, $_SESSION['cliente_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $producto = $result->fetch_assoc();
                    if ($producto['cantidad'] >= $cantidad) {
                        // Actualizar cantidad
                        $stmt = $conexion->prepare("UPDATE carrito_compras SET cantidad = ? WHERE id_item = ? AND id_usuario = ?");
                        $stmt->bind_param("iii", $cantidad, $id_item, $_SESSION['cliente_id']);
                        $stmt->execute();
                    } else {
                        $_SESSION['error'] = "No hay suficiente stock para algunos productos";
                    }
                }
            }
        }
        
        header("Location: carrito.php");
        exit();
    } elseif (isset($_POST['realizar_pedido'])) {
        // Verificar que el carrito no esté vacío
        if (empty($carrito)) {
            $_SESSION['error'] = "El carrito está vacío";
            header("Location: carrito.php");
            exit();
        }
        
        // Verificar stock nuevamente antes de procesar
        $stock_ok = true;
        foreach ($carrito as $item) {
            if ($item['stock'] < $item['cantidad']) {
                $stock_ok = false;
                break;
            }
        }
        
        if (!$stock_ok) {
            $_SESSION['error'] = "Algunos productos no tienen suficiente stock disponible";
            header("Location: carrito.php");
            exit();
        }
        
        // Realizar pedido
        $direccion = $_POST['direccion'] ?? '';
        $metodo_pago = $_POST['metodo_pago'] ?? 'contraentrega';
        
        $conexion->query("CALL RealizarCompra({$_SESSION['cliente_id']}, '$direccion', '$metodo_pago')");
        
        $_SESSION['mensaje'] = "Pedido realizado con éxito. Gracias por su compra!";
        header("Location: pedidos.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - Granja San José</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .cart-items {
            margin-bottom: 30px;
        }
        
        .cart-item {
            display: flex;
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
            align-items: center;
        }
        
        .cart-item-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 2em;
            color: #4CAF50;
        }
        
        .cart-item-details {
            flex-grow: 1;
        }
        
        .cart-item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            color: #2e7d32;
        }
        
        .cart-item-quantity {
            width: 60px;
            padding: 5px;
        }
        
        .cart-summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }
        
        .cart-total {
            font-size: 1.5em;
            font-weight: bold;
            margin: 20px 0;
            color: #2e7d32;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #388E3C;
        }
        
        .btn-danger {
            background-color: #f44336;
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .alert-error {
            background-color: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body class="home-container">
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
            <i class="fas fa-leaf"></i> Granja San José
        </a>
        <div class="nav-links">
            <span class="welcome-message">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['cliente_nombre']) ?>
            </span>
            <a href="inicio.php" class="nav-link">
                <i class="fas fa-home"></i> Inicio
            </a>
            <a href="carrito.php" class="nav-link active">
                <i class="fas fa-shopping-cart"></i> Carrito
            </a>
            <a href="conexion/logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </nav>

    <div class="cart-container">
        <h1>Tu Carrito de Compras</h1>
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($carrito)): ?>
            <p>Tu carrito está vacío. <a href="inicio.php">Ver productos</a></p>
        <?php else: ?>
            <form method="post" action="carrito.php">
                <div class="cart-items">
                    <?php foreach ($carrito as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-icon">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        
                        <div class="cart-item-details">
                            <div class="cart-item-name"><?= htmlspecialchars($item['nombre']) ?></div>
                            <div class="cart-item-price">$<?= number_format($item['precio'], 0, ',', '.') ?> COP c/u</div>
                        </div>
                        
                        <div style="margin: 0 20px;">
                            <input type="number" name="cantidad[<?= $item['id_item'] ?>]" value="<?= $item['cantidad'] ?>" min="1" max="<?= $item['stock'] ?>" class="cart-item-quantity">
                        </div>
                        
                        <div style="margin-right: 20px; font-weight: bold;">
                            $<?= number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?> COP
                        </div>
                        
                        <a href="eliminar_item.php?id=<?= $item['id_item'] ?>" class="btn btn-danger">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <button type="submit" name="actualizar_carrito" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Actualizar Carrito
                    </button>
                    <a href="inicio.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Seguir Comprando
                    </a>
                </div>
                
                <div class="cart-summary">
                    <h2>Resumen del Pedido</h2>
                    <div class="cart-total">
                        Total: $<?= number_format($total, 0, ',', '.') ?> COP
                    </div>
                    
                    <h3>Información de Envío</h3>
                    <div class="form-group">
                        <label for="direccion">Dirección de Envío:</label>
                        <input type="text" id="direccion" name="direccion" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="metodo_pago">Método de Pago:</label>
                        <select id="metodo_pago" name="metodo_pago" class="form-control" required>
                            <option value="contraentrega">Contraentrega</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="realizar_pedido" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.2em;">
                        <i class="fas fa-check"></i> Realizar Pedido
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>