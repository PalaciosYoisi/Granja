<?php 
// 1. Establecer conexión con la base de datos
$conexion = mysqli_connect("localhost", "root", "", "granja");

// 2. Verificar la conexión
if(!$conexion) {
    header("Location: ../index.html?error=db");
    exit();
}

// 3. Obtener y sanitizar datos del formulario
$correo = mysqli_real_escape_string($conexion, $_POST['correo']);
$clave = mysqli_real_escape_string($conexion, $_POST['clave']);

// 4. Verificar estado de bloqueo primero
$sql_bloqueo = "SELECT bu.*, u.id_usuario 
                FROM bloqueo_usuarios bu 
                RIGHT JOIN usuarios u ON bu.id_usuario = u.id_usuario 
                WHERE u.correo = '$correo'";
$result_bloqueo = mysqli_query($conexion, $sql_bloqueo);

if(mysqli_num_rows($result_bloqueo) > 0) {
    $bloqueo = mysqli_fetch_assoc($result_bloqueo);
    
    // Si existe registro de bloqueo y aún está vigente
    if($bloqueo['bloqueado_hasta'] && strtotime($bloqueo['bloqueado_hasta']) > time()) {
        $tiempo_restante = ceil((strtotime($bloqueo['bloqueado_hasta']) - time()) / 60);
        header("Location: ../index.html?error=blocked&time=".$tiempo_restante);
        exit();
    }
}

// 5. Verificar credenciales del usuario
$sql_usuario = "SELECT id_usuario, correo, clave FROM usuarios WHERE correo = '$correo'";
$resultado = mysqli_query($conexion, $sql_usuario);

if(mysqli_num_rows($resultado) == 1) {
    $usuario = mysqli_fetch_assoc($resultado);
    
    // Verificar contraseña (en producción usar password_verify() con contraseñas hasheadas)
    if($clave == $usuario['clave']) {
        // Login exitoso - resetear intentos fallidos
        resetear_intentos($conexion, $usuario['id_usuario']);
        
        // Registrar acceso exitoso
        registrar_acceso($conexion, $usuario['id_usuario'], true);
        
        // Redirigir al inicio
        header("Location: ../inicio.html");
        exit();
    } else {
        // Contraseña incorrecta - registrar intento fallido
        registrar_intento_fallido($conexion, $usuario['id_usuario']);
        registrar_acceso($conexion, $usuario['id_usuario'], false);
        
        header("Location: ../index.html?error=credentials");
        exit();
    }
} else {
    // Usuario no existe
    header("Location: ../index.html?error=credentials");
    exit();
}

// Función para registrar intentos fallidos y bloquear si es necesario
function registrar_intento_fallido($conexion, $id_usuario) {
    // Verificar si existe registro en bloqueo_usuarios
    $sql_check = "SELECT * FROM bloqueo_usuarios WHERE id_usuario = $id_usuario";
    $result = mysqli_query($conexion, $sql_check);
    
    if(mysqli_num_rows($result) == 0) {
        // Crear registro si no existe
        $sql_insert = "INSERT INTO bloqueo_usuarios (id_usuario, intentos_fallidos) 
                       VALUES ($id_usuario, 1)";
        mysqli_query($conexion, $sql_insert);
    } else {
        // Incrementar intentos fallidos
        $sql_update = "UPDATE bloqueo_usuarios 
                      SET intentos_fallidos = intentos_fallidos + 1 
                      WHERE id_usuario = $id_usuario";
        mysqli_query($conexion, $sql_update);
        
        // Verificar si debe bloquearse (3 o más intentos)
        $sql_block = "UPDATE bloqueo_usuarios 
                     SET bloqueado_desde = NOW(), 
                         bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                     WHERE id_usuario = $id_usuario 
                     AND intentos_fallidos >= 3";
        mysqli_query($conexion, $sql_block);
    }
}

// Función para resetear intentos fallidos después de un login exitoso
function resetear_intentos($conexion, $id_usuario) {
    $sql_reset = "UPDATE bloqueo_usuarios 
                 SET intentos_fallidos = 0, 
                     bloqueado_desde = NULL, 
                     bloqueado_hasta = NULL 
                 WHERE id_usuario = $id_usuario";
    mysqli_query($conexion, $sql_reset);
}

// Función para registrar acceso (éxito/fracaso) en log_accesos
function registrar_acceso($conexion, $id_usuario, $exito) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $exito_int = $exito ? 1 : 0;
    
    $sql_log = "INSERT INTO log_accesos (id_usuario, exito, direccion_ip) 
                VALUES ($id_usuario, $exito_int, '$ip')";
    mysqli_query($conexion, $sql_log);
}

// Cerrar conexión
mysqli_close($conexion);
?>