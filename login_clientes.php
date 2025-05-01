<?php require_once 'conexion/auth_functions.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Granja San José</title>
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
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (login($email, $password)) {
                header("Location: inicio.php");
                exit();
            } else {
                echo '<div class="result" style="color:var(--danger-color)">Credenciales incorrectas</div>';
            }
        }
        ?>
        
        <h1>Iniciar Sesión</h1>
        <form method="POST" class="form-section">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Ingresar</button>
            <p style="margin-top:1rem">¿No tienes cuenta? <a href="registro_clientes.php">Regístrate aquí</a></p>
        </form>
    </div>
</body>
</html>