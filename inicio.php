<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Granja - Inicio</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="home-container">
    <nav class="navbar">

        <div class="nav-links">

            <span class="welcome-message">
                <i class="fas fa-user"></i> Bienvenido, <?php echo isset($_SESSION['nombre_usuario']) ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Usuario'; ?>
            </span>
            
            <a href="inicio.php" class="nav-link active">
                <i class="fas fa-home"></i> Inicio
            </a>
            <a href="funciones.html" class="nav-link">
                <i class="fas fa-calculator"></i> Consultas Rápidas
            </a>
            <a href="procedimientos.html" class="nav-link">
                <i class="fas fa-tasks"></i> Operaciones
            </a>
            <a href="vistas.html" class="nav-link">
                <i class="fas fa-table"></i> Monitoreo
            </a>
            <a href="auditorias.html" class="nav-link">
                <i class="fas fa-clipboard-check"></i> Auditorías
            </a>
        </div>
    </nav>

    <div class="home-content">
        <h1 class="dashboard-title">Gestión de la Granja</h1>
        <div class="dashboard-grid">
            <a href="funciones.html" class="dashboard-card">
                <i class="fas fa-calculator"></i>
                <h3>Consultas Rápidas</h3>
                <p>Consultas rápidas del estado de la granja</p>
            </a>
            <a href="procedimientos.html" class="dashboard-card">
                <i class="fas fa-tasks"></i>
                <h3>Operaciones</h3>
                <p>Gestión completa de operaciones</p>
            </a>
            <a href="vistas.html" class="dashboard-card">
                <i class="fas fa-table"></i>
                <h3>Monitoreo</h3>
                <p>Reportes y análisis de datos</p>
            </a>
            <a href="auditorias.html" class="dashboard-card">
                <i class="fas fa-clipboard-check"></i>
                <h3>Auditorías</h3>
                <p>Control y seguimiento de procesos</p>
            </a>
        </div>
    </div>

    <footer class="footer">
        <p>Sistema de Gestión de Granja &copy; 2023</p>
    </footer>
</body>
</html>
</html>
