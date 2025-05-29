<?php
require_once 'conexion.php';

header('Content-Type: application/json');

$conexion = new Conexion();
$db = $conexion->getConexion();

$orderId = $_GET['id'];

// Obtener información básica del pedido
$pedido = $db->query("SELECT id_cliente, direccion_envio FROM pedidos WHERE id_pedido = $orderId")->fetch_assoc();

echo json_encode($pedido);
?>