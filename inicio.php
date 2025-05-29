<?php 
require_once 'conexion/auth_functions.php';

// Verificar sesión
if (!isset($_SESSION['cliente_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener productos
$productos = [];
$result = $conexion->query("
    SELECT * FROM inventario_productos 
    WHERE disponible = 1 
    ORDER BY categoria, nombre
");
if ($result) {
    $productos = $result->fetch_all(MYSQLI_ASSOC);
}

// Manejar agregar al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_carrito'])) {
    $id_producto = intval($_POST['id_producto']);
    $cantidad = intval($_POST['cantidad']);
    
    // Verificar si el producto existe y tiene stock
    $stmt = $conexion->prepare("SELECT cantidad FROM inventario_productos WHERE id_producto = ? AND disponible = 1");
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $producto = $result->fetch_assoc();
        if ($producto['cantidad'] >= $cantidad) {
            // Verificar si ya está en el carrito
            $stmt = $conexion->prepare("SELECT id_item, cantidad FROM carrito_compras WHERE id_cliente = ? AND id_producto = ?");
            $stmt->bind_param("ii", $_SESSION['cliente_id'], $id_producto);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                // Actualizar cantidad
                $item = $result->fetch_assoc();
                $nueva_cantidad = $item['cantidad'] + $cantidad;
                $stmt = $conexion->prepare("UPDATE carrito_compras SET cantidad = ? WHERE id_item = ?");
                $stmt->bind_param("ii", $nueva_cantidad, $item['id_item']);
                $stmt->execute();
            } else {
                // Agregar nuevo item
                $stmt = $conexion->prepare("INSERT INTO carrito_compras (id_cliente, id_producto, cantidad) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $_SESSION['cliente_id'], $id_producto, $cantidad);
                $stmt->execute();
            }
            
            $_SESSION['mensaje'] = "Producto agregado al carrito";
        } else {
            $_SESSION['error'] = "No hay suficiente stock disponible";
        }
    } else {
        $_SESSION['error'] = "Producto no disponible";
    }
    
    header("Location: inicio.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Granja San José</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>

        /* Agregar/modificar estos estilos en estilos.css */

/* Estilo para el contenedor principal de productos */
.product-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 20px;
    overflow-x: auto; /* Permite scroll horizontal si hay muchos productos */
    scrollbar-width: thin; /* Para navegadores modernos */
    scrollbar-color: var(--secondary-color) var(--light-color);
}

/* Estilo para la barra de scroll */
.product-grid::-webkit-scrollbar {
    height: 8px;
}

.product-grid::-webkit-scrollbar-track {
    background: var(--light-color);
    border-radius: 10px;
}

.product-grid::-webkit-scrollbar-thumb {
    background-color: var(--secondary-color);
    border-radius: 10px;
}

/* Estilo para las tarjetas de producto */
.product-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    transition: transform 0.3s;
    background: white;
    min-width: 250px; /* Ancho mínimo para cada tarjeta */
    flex: 0 0 auto; /* Evita que las tarjetas se estiren */
    box-shadow: var(--box-shadow);
}

/* Estilo para los títulos de categoría */
.category-title {
    width: 100%;
    font-size: 1.5em;
    margin-top: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--accent-color);
    color: var(--primary-color);
}
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-icon {
            text-align: center;
            font-size: 4em;
            color: #4CAF50;
            margin-bottom: 15px;
        }
        
        .product-price {
            font-weight: bold;
            color: #2e7d32;
            font-size: 1.2em;
            margin: 10px 0;
        }
        
        .product-stock {
            color: #666;
            font-size: 0.9em;
        }
        
        .btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        
        .btn:hover {
            background-color: #388E3C;
        }
        
        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .category-title {
            grid-column: 1 / -1;
            font-size: 1.5em;
            margin-top: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4CAF50;
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
        
        .quantity-controls {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        
        .quantity-controls button {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            width: 30px;
            height: 30px;
            cursor: pointer;
        }
        
        .quantity-controls input {
            width: 50px;
            text-align: center;
            margin: 0 5px;
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
            <a href="inicio.php" class="nav-link active">
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

    <div class="container">
        <h1>Nuestros Productos</h1>
        
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
        
        <div class="product-grid">
            <?php 
            $categoria_actual = '';
            foreach ($productos as $producto): 
                if ($producto['categoria'] != $categoria_actual) {
                    $categoria_actual = $producto['categoria'];
                    echo '<h2 class="category-title">' . htmlspecialchars(ucfirst($categoria_actual)) . '</h2>';
                }
            ?>
            <div class="product-card">
                <div class="product-icon">
                    <i class="fas fa-<?= 
                        $producto['categoria'] == 'Huevos' ? 'egg' : 
                        ($producto['categoria'] == 'Leche' ? 'wine-bottle' : 
                        ($producto['categoria'] == 'Carne' ? 'drumsticke' : 
                        ($producto['categoria'] == 'Pollo' ? 'drumstick-bit' : 
                        ($producto['categoria'] == 'Frutas' ? 'apple-alt' : 'carrot')))) 
                    ?>"></i>
                </div>
                
                <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                <p class="product-price">$<?= number_format($producto['precio'], 0, ',', '.') ?> COP</p>
                <p class="product-stock">Disponible: <?= $producto['cantidad'] ?> unidades</p>
                
                <form method="post" action="inicio.php">
                    <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
                    <div class="quantity-controls">
                        <button type="button" class="decrement">-</button>
                        <input type="number" id="cantidad_<?= $producto['id_producto'] ?>" name="cantidad" min="1" max="<?= $producto['cantidad'] ?>" value="1">
                        <button type="button" class="increment">+</button>
                    </div>
                    <button type="submit" name="agregar_carrito" class="btn" <?= $producto['cantidad'] <= 0 ? 'disabled' : '' ?>>
                        <?= $producto['cantidad'] <= 0 ? 'Agotado' : 'Agregar al carrito' ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Script para manejar los botones de incremento/decremento
        document.querySelectorAll('.increment').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('input[type=number]');
                const max = parseInt(input.getAttribute('max'));
                if (parseInt(input.value) < max) {
                    input.value = parseInt(input.value) + 1;
                }
            });
        });

        document.querySelectorAll('.decrement').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('input[type=number]');
                if (parseInt(input.value) > 1) {
                    input.value = parseInt(input.value) - 1;
                }
            });
        });
    </script>
</body>
</html>