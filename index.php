<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/estilo_login.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Iniciar Sesión</h2>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    switch($_GET['error']) {
                        case 'blocked':
                            $tiempo = isset($_GET['time']) ? (int)$_GET['time'] : 30;
                            echo "Cuenta bloqueada temporalmente. Por favor intente nuevamente en $tiempo minutos.";
                            break;
                        case 'credentials':
                            $intentos = isset($_GET['attempts']) ? (int)$_GET['attempts'] : 3;
                            if ($intentos > 0) {
                                echo "Credenciales incorrectas. Te quedan $intentos intentos antes de que tu cuenta sea bloqueada.";
                            } else {
                                echo "Credenciales incorrectas. Tu cuenta ha sido bloqueada por 30 minutos.";
                            }
                            break;
                        case 'db':
                            echo "Error de conexión con el servidor. Por favor intente más tarde.";
                            break;
                        default:
                            echo "Ocurrió un error al procesar su solicitud.";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <form action="conexion/login.php" method="POST">
                <div class="form-group">
                    <label for="correo"><i class="fa-solid fa-envelope"></i> Correo electrónico</label>
                    <input type="email" class="form-control" id="correo" name="correo" placeholder="Ingrese su correo" required
                           value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="clave"><i class="fa-solid fa-lock"></i> Contraseña</label>
                    <input type="password" class="form-control" id="clave" name="clave" placeholder="Ingrese su contraseña" required>
                </div>
                
                <button type="submit" class="btn btn-login btn-block w-100">
                    <i class="fa-solid fa-right-to-bracket"></i> Ingresar
                </button>
            </form>

            <div class="text-center mt-3">
                <p>¿No tienes cuenta? <a href="registro.html">Regístrate aquí</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>