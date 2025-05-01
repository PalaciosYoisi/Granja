<?php
session_start();
$conexion = new mysqli("localhost", "root", "", "granja");

// Función para validar login
function login($email, $password) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT id_cliente, nombre, password FROM clientes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $cliente = $result->fetch_assoc();
        if (password_verify($password, $cliente['password'])) {
            $_SESSION['cliente_id'] = $cliente['id_cliente'];
            $_SESSION['cliente_nombre'] = $cliente['nombre'];
            return true;
        }
    }
    return false;
}

// Función para registrar nuevo cliente
function registrar_cliente($nombre, $email, $telefono, $direccion, $password) {
    global $conexion;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conexion->prepare("INSERT INTO clientes (nombre, email, telefono, direccion, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nombre, $email, $telefono, $direccion, $hashed_password);
    return $stmt->execute();
}
?>