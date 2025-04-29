<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Conexión a la base de datos
$conexion = mysqli_connect("localhost", "root", "", "granja");
if (!$conexion) {
    header("Location: ../index.php?error=db");
    exit();
}

// 2. Obtener y limpiar datos del formulario
$correo = mysqli_real_escape_string($conexion, $_POST['correo']);
$clave = $_POST['clave'];

// 3. Buscar usuario en la base de datos (ahora incluyendo el tipo de usuario)
// En login.php, modificar la consulta SQL para incluir el nombre:
    $sql_usuario = "SELECT id_usuario, clave, tipo_usuario, nombre FROM usuarios WHERE correo = '$correo' LIMIT 1";

    // Y luego guardar el nombre en la sesión:
    $_SESSION['nombre'] = $usuario['nombre'];

$resultado = mysqli_query($conexion, $sql_usuario);

if (mysqli_num_rows($resultado) == 1) {
    $usuario = mysqli_fetch_assoc($resultado); 
    $id_usuario = $usuario['id_usuario'];
    $tipo_usuario = $usuario['tipo_usuario'];
    
    // 4. Verificar si el usuario está actualmente bloqueado
    $sql_bloqueo = "SELECT bloqueado_hasta FROM bloqueo_usuarios 
                   WHERE id_usuario = $id_usuario 
                   AND bloqueado_hasta > NOW()";
    $result_bloqueo = mysqli_query($conexion, $sql_bloqueo);
    
    if (mysqli_num_rows($result_bloqueo) > 0) {
        $bloqueo = mysqli_fetch_assoc($result_bloqueo);
        $tiempo_restante = ceil((strtotime($bloqueo['bloqueado_hasta']) - time()) / 60);
        mysqli_close($conexion);
        header("Location: ../index.php?error=blocked&time=".$tiempo_restante);
        exit();
    }
    
    // 5. Verificar credenciales (compatibilidad con ambos tipos de contraseña)
    $clave_valida = false;
    
    // Primero intentar con contraseña en texto plano (para usuarios antiguos)
    if ($clave === $usuario['clave']) {
        $clave_valida = true;
    } 
    // Si no coincide, verificar si es una contraseña encriptada
    else if (password_verify($clave, $usuario['clave'])) {
        $clave_valida = true;
    }
    
    if ($clave_valida) {
        // Credenciales correctas - resetear bloqueo
        mysqli_query($conexion, "UPDATE bloqueo_usuarios 
                               SET intentos_fallidos = 0, 
                                   bloqueado_desde = NULL, 
                                   bloqueado_hasta = NULL 
                               WHERE id_usuario = $id_usuario");
        
        // Registrar acceso exitoso
        mysqli_query($conexion, "INSERT INTO log_accesos (id_usuario, exito) 
                               VALUES ($id_usuario, 1)");
        
        // Iniciar sesión
        session_start();
        $_SESSION['usuario_id'] = $id_usuario;
        $_SESSION['correo'] = $correo;
        $_SESSION['tipo_usuario'] = $tipo_usuario;
        
        mysqli_close($conexion);
        
        // Redirigir según el tipo de usuario
        if ($tipo_usuario == 'Administrador') {
            header("Location: ../dashboard.php");
        } else {
            header("Location: ../inicio.php");
        }
        exit();
    } else {
        // Credenciales incorrectas - manejar intento fallido
        manejarIntentoFallido($conexion, $id_usuario);
    }
} else {
    // Usuario no existe
    mysqli_close($conexion);
    header("Location: ../index.php?error=credentials");
    exit();
}

function manejarIntentoFallido($conexion, $id_usuario) {
    // 1. Incrementar contador de intentos fallidos
    mysqli_query($conexion, "INSERT INTO bloqueo_usuarios (id_usuario, intentos_fallidos) 
                           VALUES ($id_usuario, 1) 
                           ON DUPLICATE KEY UPDATE 
                           intentos_fallidos = intentos_fallidos + 1");
    
    // 2. Obtener número actual de intentos
    $sql_intentos = "SELECT intentos_fallidos FROM bloqueo_usuarios 
                    WHERE id_usuario = $id_usuario";
    $result_intentos = mysqli_query($conexion, $sql_intentos);
    $intentos = mysqli_fetch_assoc($result_intentos);
    $intentos_fallidos = $intentos['intentos_fallidos'] ?? 0;
    
    // 3. Bloquear si alcanza 3 intentos fallidos
    if ($intentos_fallidos >= 3) {
        $ahora = date('Y-m-d H:i:s');
        $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+3 minutes'));
        
        mysqli_query($conexion, "UPDATE bloqueo_usuarios 
                               SET bloqueado_desde = '$ahora', 
                                   bloqueado_hasta = '$bloqueado_hasta' 
                               WHERE id_usuario = $id_usuario");
        
        // Registrar alerta de bloqueo
        mysqli_query($conexion, "INSERT INTO alertas (categoria, mensaje, fecha)
                               VALUES ('usuario', 'Usuario ID $id_usuario bloqueado temporalmente', NOW())");
        
        mysqli_close($conexion);
        header("Location: ../index.php?error=blocked&time=3");
        exit();
    }
    
    // 4. Mostrar intentos restantes
    $intentos_restantes = 3 - $intentos_fallidos;
    mysqli_close($conexion);
    header("Location: ../index.php?error=credentials&attempts=".$intentos_restantes);
    exit();
}
?>