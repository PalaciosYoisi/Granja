<?php
require_once 'conexion.php';

$conexion = new Conexion();
$db = $conexion->getConexion();

$orderId = $_GET['id'];

// Obtener información del pedido
$pedido = $db->query("SELECT p.*, c.nombre as cliente_nombre 
                     FROM pedidos p
                     JOIN clientes c ON p.id_cliente = c.id_cliente
                     WHERE p.id_pedido = $orderId")->fetch_assoc();

// Obtener detalles del pedido
$detalles = $db->query("SELECT d.*, p.nombre 
                       FROM detalles_pedido d
                       JOIN inventario_productos p ON d.id_producto = p.id_producto
                       WHERE d.id_pedido = $orderId");

echo '<div class="sale-card">';
echo '<div class="sale-header">';
echo '<div class="sale-title">Pedido #' . $pedido['id_pedido'] . '</div>';
echo '<span class="status status-' . strtolower($pedido['estado']) . '">' . $pedido['estado'] . '</span>';
echo '</div>';
echo '<div class="sale-details">';
echo '<div><i class="fas fa-calendar-alt"></i> ' . date('d/m/Y', strtotime($pedido['fecha_pedido'])) . '</div>';
echo '<div><i class="fas fa-user"></i> ' . $pedido['cliente_nombre'] . '</div>';
echo '</div>';
echo '<div class="product-list">';

while($detalle = $detalles->fetch_assoc()) {
    echo '<div class="product-item">';
    echo '<div class="product-name">' . $detalle['nombre'] . '</div>';
    echo '<div class="product-qty">' . $detalle['cantidad'] . '</div>';
    echo '<div class="product-price">$' . number_format($detalle['precio_unitario'], 2) . '</div>';
    echo '</div>';
}

echo '</div>';
echo '<div class="sale-total text-right">';
echo 'Total: $' . number_format($pedido['total'], 2);
echo '</div>';
echo '</div>';
?>