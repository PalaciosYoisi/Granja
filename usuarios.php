<?php
session_start();
require_once 'conexion/conexion.php';

// Verificar sesión y rol
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] != 'Administrador') {
    header("Location: index.php");
    exit();
}

// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener lista de usuarios
$usuarios = $db->query("SELECT u.*, 
                       (SELECT COUNT(*) FROM log_accesos la WHERE la.id_usuario = u.id_usuario AND la.exito = 1) as accesos_exitosos,
                       (SELECT COUNT(*) FROM log_accesos la WHERE la.id_usuario = u.id_usuario AND la.exito = 0) as accesos_fallidos,
                       IFNULL(bu.intentos_fallidos, 0) as intentos_fallidos,
                       IF(bu.bloqueado_hasta IS NOT NULL AND bu.bloqueado_hasta > NOW(), 'Bloqueado', 'Activo') as estado_bloqueo
                       FROM usuarios u
                       LEFT JOIN bloqueo_usuarios bu ON u.id_usuario = bu.id_usuario
                       ORDER BY u.id_usuario DESC");
$usuarios = $usuarios ? $usuarios->fetch_all(MYSQLI_ASSOC) : [];

// Procesar acciones (bloquear/desbloquear)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && isset($_POST['id_usuario'])) {
    $id_usuario = intval($_POST['id_usuario']);
    $accion = $_POST['accion'];
    
    if ($accion == 'bloquear') {
        $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+3 minutes'));
        $db->query("INSERT INTO bloqueo_usuarios (id_usuario, intentos_fallidos, bloqueado_desde, bloqueado_hasta) 
                   VALUES ($id_usuario, 3, NOW(), '$bloqueado_hasta')
                   ON DUPLICATE KEY UPDATE intentos_fallidos = 3, bloqueado_desde = NOW(), bloqueado_hasta = '$bloqueado_hasta'");
        
        // Registrar alerta
        $db->query("INSERT INTO alertas (categoria, mensaje, fecha) 
                   VALUES ('usuario', CONCAT('Usuario ID ', $id_usuario, ' bloqueado manualmente por el administrador.'), NOW())");
        
        $_SESSION['mensaje'] = "Usuario bloqueado exitosamente por 3 minutos.";
    } elseif ($accion == 'desbloquear') {
        $db->query("DELETE FROM bloqueo_usuarios WHERE id_usuario = $id_usuario");
        
        // Registrar alerta
        $db->query("INSERT INTO alertas (categoria, mensaje, fecha) 
                   VALUES ('usuario', CONCAT('Usuario ID ', $id_usuario, ' desbloqueado manualmente por el administrador.'), NOW())");
        
        $_SESSION['mensaje'] = "Usuario desbloqueado exitosamente.";
    } elseif ($accion == 'eliminar') {
        // Verificar que no sea el propio administrador
        if ($id_usuario == $_SESSION['usuario_id']) {
            $_SESSION['error'] = "No puedes eliminarte a ti mismo.";
        } else {
            // Eliminar registros relacionados primero
            $db->query("DELETE FROM bloqueo_usuarios WHERE id_usuario = $id_usuario");
            $db->query("DELETE FROM log_accesos WHERE id_usuario = $id_usuario");
            
            // Luego eliminar el usuario
            $db->query("DELETE FROM usuarios WHERE id_usuario = $id_usuario");
            
            // Registrar alerta
            $db->query("INSERT INTO alertas (categoria, mensaje, fecha) 
                       VALUES ('usuario', CONCAT('Usuario ID ', $id_usuario, ' eliminado por el administrador.'), NOW())");
            
            $_SESSION['mensaje'] = "Usuario eliminado exitosamente.";
        }
    }
    
    header("Location: usuarios.php");
    exit();
}

// Procesar creación de nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = $db->real_escape_string($_POST['nombre']);
    $correo = $db->real_escape_string($_POST['correo']);
    $telefono = $db->real_escape_string($_POST['telefono']);
    $tipo_usuario = $db->real_escape_string($_POST['tipo_usuario']);
    $clave = password_hash($db->real_escape_string($_POST['clave']), PASSWORD_DEFAULT);
    
    // Verificar si el correo ya existe
    $existe = $db->query("SELECT id_usuario FROM usuarios WHERE correo = '$correo'");
    if ($existe && $existe->num_rows > 0) {
        $_SESSION['error'] = "El correo electrónico ya está registrado.";
    } else {
        $db->query("INSERT INTO usuarios (nombre, correo, telefono, tipo_usuario, clave) 
                   VALUES ('$nombre', '$correo', '$telefono', '$tipo_usuario', '$clave')");
        
        // Registrar alerta
        $db->query("INSERT INTO alertas (categoria, mensaje, fecha) 
                   VALUES ('usuario', CONCAT('Nuevo usuario creado por administrador: ', '$nombre', ' (', '$correo', ')'), NOW())");
        
        $_SESSION['mensaje'] = "Usuario creado exitosamente.";
    }
    
    header("Location: usuarios.php");
    exit();
}

// Procesar edición de usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_usuario'])) {
    $id_usuario = intval($_POST['id_usuario']);
    $nombre = $db->real_escape_string($_POST['nombre']);
    $correo = $db->real_escape_string($_POST['correo']);
    $telefono = $db->real_escape_string($_POST['telefono']);
    $tipo_usuario = $db->real_escape_string($_POST['tipo_usuario']);
    $clave = !empty($_POST['clave']) ? password_hash($db->real_escape_string($_POST['clave']), PASSWORD_DEFAULT) : null;
    
    // Verificar si el correo ya existe en otro usuario
    $existe = $db->query("SELECT id_usuario FROM usuarios WHERE correo = '$correo' AND id_usuario != $id_usuario");
    if ($existe && $existe->num_rows > 0) {
        $_SESSION['error'] = "El correo electrónico ya está registrado por otro usuario.";
    } else {
        if ($clave) {
            $db->query("UPDATE usuarios SET 
                       nombre = '$nombre',
                       correo = '$correo',
                       telefono = '$telefono',
                       tipo_usuario = '$tipo_usuario',
                       clave = '$clave'
                       WHERE id_usuario = $id_usuario");
        } else {
            $db->query("UPDATE usuarios SET 
                       nombre = '$nombre',
                       correo = '$correo',
                       telefono = '$telefono',
                       tipo_usuario = '$tipo_usuario'
                       WHERE id_usuario = $id_usuario");
        }
        
        // Registrar alerta
        $db->query("INSERT INTO alertas (categoria, mensaje, fecha) 
                   VALUES ('usuario', CONCAT('Usuario ID ', $id_usuario, ' actualizado por el administrador.'), NOW())");
        
        $_SESSION['mensaje'] = "Usuario actualizado exitosamente.";
    }
    
    header("Location: usuarios.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios - Granja San José</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --dark-color: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: all 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 700;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: #000;
        }
        
        .badge-success {
            background-color: var(--secondary-color);
        }
        
        .badge-danger {
            background-color: var(--danger-color);
        }
        
        .badge-warning {
            background-color: var(--warning-color);
            color: #000;
        }
        
        .badge-primary {
            background-color: var(--primary-color);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .table-responsive {
            border-radius: 0.35rem;
            overflow: hidden;
        }
        
        .table th {
            background-color: #f8f9fc;
            color: var(--dark-color);
            font-weight: 700;
            border-bottom-width: 1px;
        }
        
        .modal-content {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            font-weight: 700;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .nav-tabs .nav-link {
            color: var(--dark-color);
        }
        
        .stats-card {
            border-left: 4px solid var(--primary-color);
        }
        
        .stats-card .card-body {
            padding: 1rem 1.5rem;
        }
        
        .stats-card .text-primary {
            color: var(--primary-color) !important;
        }
        
        .stats-card .text-success {
            color: var(--secondary-color) !important;
        }
        
        .stats-card .text-danger {
            color: var(--danger-color) !important;
        }
        
        .alert-danger {
            background-color: rgba(231, 74, 59, 0.1);
            border-color: rgba(231, 74, 59, 0.2);
            color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-people me-2"></i>Administración de Usuarios
                    </h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
                        <i class="bi bi-plus-circle me-1"></i> Nuevo Usuario
                    </button>
                </div>
                
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-success alert-dismissible fade show animate_animated animate_fadeIn" role="alert">
                        <?php echo $_SESSION['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate_animated animate_fadeIn" role="alert">
                        <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Usuarios</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo count($usuarios); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people fs-2 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Usuarios Activos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo count(array_filter($usuarios, function($u) { return $u['estado_bloqueo'] == 'Activo'; })); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-check-circle fs-2 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Usuarios Bloqueados</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo count(array_filter($usuarios, function($u) { return $u['estado_bloqueo'] == 'Bloqueado'; })); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-exclamation-triangle fs-2 text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Intentos Fallidos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo array_sum(array_column($usuarios, 'intentos_fallidos')); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-shield-lock fs-2 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Lista de Usuarios</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots-vertical text-gray-400"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                        <li><a class="dropdown-item" href="#">Exportar a Excel</a></li>
                        <li><a class="dropdown-item" href="#">Imprimir Lista</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="location.reload()">Actualizar</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Tipo</th>
                                <th>Accesos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id_usuario']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2">
                                                <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                                                <div class="text-muted small"><?php echo htmlspecialchars($usuario['telefono']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                switch($usuario['tipo_usuario']) {
                                                    case 'Administrador': echo 'bg-primary'; break;
                                                    case 'Veterinario': echo 'bg-success'; break;
                                                    case 'Investigador': echo 'bg-info'; break;
                                                    case 'Botánico': echo 'bg-warning text-dark'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                            ?>">
                                            <?php echo $usuario['tipo_usuario']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <div class="me-3 text-center">
                                                <div class="text-success fw-bold"><?php echo $usuario['accesos_exitosos']; ?></div>
                                                <div class="small text-muted">Éxito</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-danger fw-bold"><?php echo $usuario['accesos_fallidos']; ?></div>
                                                <div class="small text-muted">Fallidos</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($usuario['estado_bloqueo'] == 'Bloqueado'): ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-lock-fill me-1"></i> Bloqueado
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-unlock-fill me-1"></i> Activo
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($usuario['intentos_fallidos'] > 0): ?>
                                            <div class="small text-muted mt-1">
                                                Intentos: <?php echo $usuario['intentos_fallidos']; ?>/3
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <?php if ($usuario['estado_bloqueo'] == 'Bloqueado'): ?>
                                                <form method="POST" class="me-2">
                                                    <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                                    <input type="hidden" name="accion" value="desbloquear">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-unlock"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="me-2">
                                                    <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                                    <input type="hidden" name="accion" value="bloquear">
                                                    <button type="submit" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-lock"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-sm btn-primary me-2" 
                                                    onclick="editarUsuario(
                                                        <?php echo $usuario['id_usuario']; ?>, 
                                                        '<?php echo addslashes($usuario['nombre']); ?>',
                                                        '<?php echo addslashes($usuario['correo']); ?>',
                                                        '<?php echo addslashes($usuario['telefono']); ?>',
                                                        '<?php echo addslashes($usuario['tipo_usuario']); ?>'
                                                    )">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <?php if ($usuario['id_usuario'] != $_SESSION['usuario_id']): ?>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="confirmarEliminar(<?php echo $usuario['id_usuario']; ?>, '<?php echo addslashes($usuario['nombre']); ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" disabled title="No puedes eliminarte a ti mismo">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Nuevo Usuario Modal -->
    <div class="modal fade" id="nuevoUsuarioModal" tabindex="-1" aria-labelledby="nuevoUsuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" onsubmit="return validarNuevoUsuario()">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="nuevoUsuarioModalLabel">
                            <i class="bi bi-person-plus me-1"></i> Crear Nuevo Usuario
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="tipo_usuario" class="form-label">Tipo de Usuario</label>
                            <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                <option value="Administrador">Administrador</option>
                                <option value="Veterinario">Veterinario</option>
                                <option value="Investigador">Investigador</option>
                                <option value="Botánico">Botánico</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="clave" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="clave" name="clave" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label for="confirmar_clave" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave" required minlength="6">
                            <div id="claveError" class="text-danger small d-none">Las contraseñas no coinciden</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_usuario" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Editar Usuario Modal -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" onsubmit="return validarEditarUsuario()">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editarUsuarioModalLabel">
                            <i class="bi bi-person-gear me-1"></i> Editar Usuario
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id_usuario" name="id_usuario">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="edit_correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="edit_telefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="edit_tipo_usuario" class="form-label">Tipo de Usuario</label>
                            <select class="form-select" id="edit_tipo_usuario" name="tipo_usuario" required>
                                <option value="Administrador">Administrador</option>
                                <option value="Veterinario">Veterinario</option>
                                <option value="Investigador">Investigador</option>
                                <option value="Botánico">Botánico</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_clave" class="form-label">Nueva Contraseña (opcional)</label>
                            <input type="password" class="form-control" id="edit_clave" name="clave">
                            <div class="form-text">Dejar en blanco para mantener la contraseña actual</div>
                            <div id="edit_claveError" class="text-danger small d-none">Las contraseñas no coinciden</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_confirmar_clave" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="edit_confirmar_clave">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="editar_usuario" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Eliminar Usuario Modal -->
    <div class="modal fade" id="eliminarUsuarioModal" tabindex="-1" aria-labelledby="eliminarUsuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" id="delete_id_usuario" name="id_usuario">
                    <input type="hidden" name="accion" value="eliminar">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="eliminarUsuarioModalLabel">
                            <i class="bi bi-exclamation-triangle me-1"></i> Confirmar Eliminación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar al usuario <strong id="delete_nombre_usuario"></strong>?</p>
                        <p class="text-danger">Esta acción no se puede deshacer y eliminará todos los registros asociados al usuario.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para editar usuario
        function editarUsuario(id, nombre, correo, telefono, tipo_usuario) {
            document.getElementById('edit_id_usuario').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_correo').value = correo;
            document.getElementById('edit_telefono').value = telefono;
            document.getElementById('edit_tipo_usuario').value = tipo_usuario;
            
            // Limpiar campos de contraseña
            document.getElementById('edit_clave').value = '';
            document.getElementById('edit_confirmar_clave').value = '';
            document.getElementById('edit_claveError').classList.add('d-none');
            
            var modal = new bootstrap.Modal(document.getElementById('editarUsuarioModal'));
            modal.show();
        }
        
        // Función para confirmar eliminación
        function confirmarEliminar(id, nombre) {
            document.getElementById('delete_id_usuario').value = id;
            document.getElementById('delete_nombre_usuario').textContent = nombre;
            
            var modal = new bootstrap.Modal(document.getElementById('eliminarUsuarioModal'));
            modal.show();
        }
        
        // Validación para nuevo usuario
        function validarNuevoUsuario() {
            const clave = document.getElementById('clave').value;
            const confirmar_clave = document.getElementById('confirmar_clave').value;
            const claveError = document.getElementById('claveError');
            
            if (clave !== confirmar_clave) {
                claveError.classList.remove('d-none');
                return false;
            } else {
                claveError.classList.add('d-none');
                return true;
            }
        }
        
        // Validación para editar usuario
        function validarEditarUsuario() {
            const clave = document.getElementById('edit_clave').value;
            const confirmar_clave = document.getElementById('edit_confirmar_clave').value;
            const claveError = document.getElementById('edit_claveError');
            
            // Solo validar si se está cambiando la contraseña
            if (clave && clave !== confirmar_clave) {
                claveError.classList.remove('d-none');
                return false;
            } else {
                claveError.classList.add('d-none');
                return true;
            }
        }
        
        // Validación en tiempo real para nuevo usuario
        document.getElementById('confirmar_clave').addEventListener('input', function() {
            const clave = document.getElementById('clave').value;
            const confirmar_clave = this.value;
            const claveError = document.getElementById('claveError');
            
            if (clave && confirmar_clave && clave !== confirmar_clave) {
                claveError.classList.remove('d-none');
            } else {
                claveError.classList.add('d-none');
            }
        });
        
        // Validación en tiempo real para editar usuario
        document.getElementById('edit_confirmar_clave').addEventListener('input', function() {
            const clave = document.getElementById('edit_clave').value;
            const confirmar_clave = this.value;
            const claveError = document.getElementById('edit_claveError');
            
            if (clave && confirmar_clave && clave !== confirmar_clave) {
                claveError.classList.remove('d-none');
            } else {
                claveError.classList.add('d-none');
            }
        });
    </script>
</body>
</html>