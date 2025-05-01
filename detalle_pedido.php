<?php
require_once 'conexion/auth_functions.php';

// Verificar sesión
if (!isset($_SESSION['cliente_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener ID del pedido
if (!isset($_GET['id'])) {
    header("Location: pedidos.php");
    exit();
}

$id_pedido = intval($_GET['id']);

// Verificar que el pedido pertenece al cliente
$stmt = $conexion->prepare("
    SELECT p.* 
    FROM pedidos p
    WHERE p.id_pedido = ? AND p.id_cliente = ?
");
$stmt->bind_param("ii", $id_pedido, $_SESSION['cliente_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: pedidos.php");
    exit();
}

$pedido = $result->fetch_assoc();

// Obtener detalles del pedido
$detalles = [];
$stmt = $conexion->prepare("
    SELECT d.*, p.nombre as producto_nombre
    FROM detalles_pedido d
    JOIN inventario_productos p ON d.id_producto = p.id_producto
    WHERE d.id_pedido = ?
");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result = $stmt->get_result();

while ($detalle = $result->fetch_assoc()) {
    $detalles[] = $detalle;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Pedido - Granja San José</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .order-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .order-summary {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .order-title {
            font-size: 1.5em;
            color: #333;
        }
        
        .order-status {
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: capitalize;
        }
        
        .status-pendiente {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-procesando {
            background-color: #CCE5FF;
            color: #004085;
        }
        
        .status-enviado {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-entregado {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .status-cancelado {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
        }
        
        .info-box h3 {
            margin-top: 0;
            color: #555;
            font-size: 1em;
        }
        
        .info-box p {
            margin-bottom: 0;
            font-size: 1.1em;
        }
        
        .order-items {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .item-name {
            flex-grow: 1;
        }
        
        .item-price {
            width: 120px;
            text-align: right;
        }
        
        .item-quantity {
            width: 80px;
            text-align: center;
        }
        
        .item-total {
            width: 120px;
            text-align: right;
            font-weight: bold;
        }
        
        .order-total {
            text-align: right;
            margin-top: 20px;
            font-size: 1.3em;
            font-weight: bold;
            color: #2e7d32;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
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
            <a href="carrito.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Carrito
            </a>
            <a href="conexion/logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </nav>

    <div class="order-detail-container">
        <a href="pedidos.php" class="btn btn-primary" style="margin-bottom: 20px;">
            <i class="fas fa-arrow-left"></i> Volver a mis pedidos
        </a>
        
        <div class="order-summary">
            <div class="order-header">
                <h1 class="order-title">Pedido #<?= $pedido['id_pedido'] ?></h1>
                <span class="order-status status-<?= $pedido['estado'] ?>">
                    <?= $pedido['estado'] ?>
                </span>
            </div>
            
            <div class="order-info">
                <div class="info-box">
                    <h3>Fecha del pedido</h3>
                    <p><?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></p>
                </div>
                
                <div class="info-box">
                    <h3>Método de pago</h3>
                    <p>
                        <?= match($pedido['metodo_pago']) {
                            'contraentrega' => 'Contraentrega',
                            'transferencia' => 'Transferencia Bancaria',
                            'tarjeta' => 'Tarjeta de Crédito/Débito',
                            default => ucfirst($pedido['metodo_pago'])
                        } ?>
                    </p>
                </div>
                
                <div class="info-box">
                    <h3>Dirección de envío</h3>
                    <p><?= htmlspecialchars($pedido['direccion_envio']) ?></p>
                </div>
            </div>
        </div>
        
        <div class="order-items">
            <h2>Productos</h2>
            
            <?php foreach ($detalles as $detalle): ?>
            <div class="order-item">
                <div class="item-name"><?= htmlspecialchars($detalle['producto_nombre']) ?></div>
                <div class="item-quantity"><?= $detalle['cantidad'] ?> x</div>
                <div class="item-price">$<?= number_format($detalle['precio_unitario'], 0, ',', '.') ?> COP</div>
                <div class="item-total">$<?= number_format($detalle['precio_unitario'] * $detalle['cantidad'], 0, ',', '.') ?> COP</div>
            </div>
            <?php endforeach; ?>
            
            <div class="order-total">
                Total: $<?= number_format($pedido['total'], 0, ',', '.') ?> COP
            </div>
        </div>
    </div>
</body>
</html>