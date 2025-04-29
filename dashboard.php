<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Granja - Admin</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
<!-- En dashboard.php, modificar el navbar -->
<nav class="navbar">
    <a href="#" class="navbar-brand">
        <i class="fas fa-user-shield"></i> Panel de Administración
    </a>
    <div class="nav-links">
        <span class="welcome-message">
            <i class="fas fa-user"></i> Bienvenido, <?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario'; ?>
        </span>
        <a href="conexion/dashboard_controller.php" class="nav-link active">
            <i class="fas fa-gauge-high"></i> Resumen
        </a>
        <a href="dashboard-usuarios.html" class="nav-link">
                <i class="fas fa-users-gear"></i> Usuarios
            </a>
            <a href="dashboard-alertas.html" class="nav-link">
                <i class="fas fa-bell-exclamation"></i> Alertas
            </a>
            <a href="inicio.html" class="nav-link">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
    </div>
</nav>

    <!-- Contenido Principal -->
    <div class="container">
        <h1><i class="fas fa-gauge-high"></i> Dashboard</h1>
        
        <!-- Tarjetas de Métricas -->
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <div class="dashboard-card">
                <i class="fas fa-user-clock"></i>
                <h3>Actividad Hoy</h3>
                <p><?php echo isset($eventos['eventos_hoy']) ? $eventos['eventos_hoy'] : '0'; ?> eventos registrados</p>
                <small>Último: <?php echo isset($accesos[0]['fecha_intento']) ? date('H:i', strtotime($accesos[0]['fecha_intento'])) : 'N/A'; ?></small>
            </div>
            <div class="dashboard-card">
                <i class="fas fa-cow"></i>
                <h3>Animales Activos</h3>
                <p><?php echo isset($animales['total_animales']) ? $animales['total_animales'] : '0'; ?> en producción</p>
                <small><?php echo isset($animales['enfermos']) ? $animales['enfermos'] : '0'; ?> en tratamiento</small>
            </div>
            <div class="dashboard-card">
                <i class="fas fa-wheat-awn"></i>
                <h3>Inventario</h3>
                <p><?php echo isset($inventario['porcentaje_stock']) ? round($inventario['porcentaje_stock']) : '100'; ?>% en stock</p>
                <small><?php echo (isset($inventario['porcentaje_stock']) && $inventario['porcentaje_stock'] < 100) ? 'Algunos productos bajos' : 'Todo en orden'; ?></small>
            </div>
        </div>

        <!-- Sección de Monitorización -->
        <div class="form-section">
            <h2><i class="fas fa-computer"></i> Monitor de Actividad</h2>
            <div class="resultado">
                <h3><i class="fas fa-door-open"></i> Últimos Accesos</h3>
                <ul>
                    <?php if(isset($accesos) && !empty($accesos)): ?>
                        <?php foreach ($accesos as $acceso): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($acceso['nombre']); ?></strong> - 
                            <?php echo $acceso['exito'] ? 'Acceso exitoso' : 'Acceso fallido'; ?> 
                            (<?php echo date('H:i', strtotime($acceso['fecha_intento'])); ?>)
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No hay registros de acceso recientes</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Alertas Recientes -->
        <div class="form-section">
            <h2><i class="fas fa-bell"></i> Alertas Recientes</h2>
            <div class="resultado">
                <?php if(isset($alertas) && !empty($alertas)): ?>
                    <?php foreach ($alertas as $alerta): ?>
                    <div class="alerta" style="border-left: 4px solid 
                        <?php echo ($alerta['categoria'] == 'animal' || $alerta['categoria'] == 'salud') ? 'var(--danger-color)' : 'var(--warning-color)'; ?>">
                        <p><strong><?php echo ucfirst($alerta['categoria']); ?></strong> - <?php echo htmlspecialchars($alerta['mensaje']); ?></p>
                        <small><?php echo date('Y-m-d H:i', strtotime($alerta['fecha'])); ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alerta" style="border-left: 4px solid var(--success-color)">
                        <p>No hay alertas recientes</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="form-section">
            <h2><i class="fas fa-chart-pie"></i> Resumen Semanal</h2>
            <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="resultado">
                    <h3><i class="fas fa-egg"></i> Producción</h3>
                    <p>Huevos: <?php echo isset($produccion['huevos']) ? round($produccion['huevos']) : '0'; ?> unidades</p>
                    <p>Leche: <?php echo isset($produccion['leche']) ? round($produccion['leche']) : '0'; ?> litros</p>
                </div>
                <div class="resultado">
                    <h3><i class="fas fa-syringe"></i> Salud</h3>
                    <p>Vacunas aplicadas: <?php echo isset($salud['vacunas']) ? $salud['vacunas'] : '0'; ?></p>
                    <p>Tratamientos activos: <?php echo isset($salud['tratamientos']) ? $salud['tratamientos'] : '0'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>Granja &copy; <?php echo date('Y'); ?> | <i class="fas fa-database"></i> v2.1.0</p>
    </footer>
</body>
</html>