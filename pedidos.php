<?php
require_once 'conexion/auth_functions.php';

// Verificar sesión
if (!isset($_SESSION['cliente_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener pedidos del cliente
$pedidos = [];
$stmt = $conexion->prepare("
    SELECT p.id_pedido, p.fecha_pedido, p.total, p.estado, 
           COUNT(d.id_detalle) as num_productos
    FROM pedidos p
    LEFT JOIN detalles_pedido d ON p.id_pedido = d.id_pedido
    WHERE p.id_cliente = ?
    GROUP BY p.id_pedido
    ORDER BY p.fecha_pedido DESC
");
$stmt->bind_param("i", $_SESSION['cliente_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($pedido = $result->fetch_assoc()) {
    $pedidos[] = $pedido;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Granja San José</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            transition: transform 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .order-id {
            font-weight: bold;
            font-size: 1.2em;
            color: #333;
        }
        
        .order-date {
            color: #666;
        }
        
        .order-status {
            padding: 5px 10px;
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
        
        .order-details {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .order-total {
            font-weight: bold;
            font-size: 1.1em;
            color: #2e7d32;
        }
        
        .order-products {
            color: #666;
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
        
        .empty-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-orders i {
            font-size: 3em;
            margin-bottom: 20px;
            color: #ddd;
        }
    </style>
</head>
<body class="home-container">
    <nav class="navbar">
        <a href="inicio.php" class="navbar-brand">
            <i class="fas fa-leaf"></i> Granja San José
        </a>
        <div class="nav-links">
            <span class="welcome-message">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['cliente_nombre']) ?>
            </span>
            <a href="index.php" class="nav-link">
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

    <div class="orders-container">
        <h1>Mis Pedidos</h1>
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($pedidos)): ?>
            <div class="empty-orders">
                <i class="fas fa-box-open"></i>
                <h2>Aún no has realizado ningún pedido</h2>
                <p>Explora nuestros productos y realiza tu primer pedido</p>
                <a href="inicio.php" class="btn btn-primary">Ver productos</a>
            </div>
        <?php else: ?>
            <?php foreach ($pedidos as $pedido): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <span class="order-id">Pedido #<?= $pedido['id_pedido'] ?></span>
                        <span class="order-date"> - <?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></span>
                    </div>
                    <span class="order-status status-<?= $pedido['estado'] ?>">
                        <?= $pedido['estado'] ?>
                    </span>
                </div>
                
                <div class="order-details">
                    <div class="order-products">
                        <?= $pedido['num_productos'] ?> producto<?= $pedido['num_productos'] != 1 ? 's' : '' ?>
                    </div>
                    <div class="order-total">
                        Total: $<?= number_format($pedido['total'], 0, ',', '.') ?> COP
                    </div>
                </div>
                
                <div style="margin-top: 15px; text-align: right;">
                    <a href="detalle_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i> Ver detalle
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>