<?php require_once 'conexion/auth_functions.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Granja San José</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="home-container">
    <nav class="navbar">
        <a href="../index.html" class="navbar-brand">
            <i class="fas fa-leaf"></i> Granja San José
        </a>
    </nav>

    <div class="container form-container">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'] ?? '';
            $email = $_POST['email'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $direccion = $_POST['direccion'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (registrar_cliente($nombre, $email, $telefono, $direccion, $password)) {
                echo '<div class="result" style="color:var(--success-color)">Registro exitoso. <a href="login.php">Inicia sesión</a></div>';
            } else {
                echo '<div class="result" style="color:var(--danger-color)">Error al registrar. El correo ya existe.</div>';
            }
        }
        ?>
        
        <h1>Registro de Cliente</h1>
        <form method="POST" class="form-section">
            <div class="form-group">
                <label for="nombre">Nombre Completo</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono">
            </div>
            <div class="form-group">
                <label for="direccion">Dirección</label>
                <textarea id="direccion" name="direccion" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Registrarse</button>
            <p style="margin-top:1rem">¿Ya tienes cuenta? <a href="login_clientes.php">Inicia sesión</a></p>
        </form>
    </div>
</body>
</html>