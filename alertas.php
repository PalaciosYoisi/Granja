<?php
session_start();
require_once 'conexion/conexion.php';

// Verificar sesión y rol
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] != 'Administrador') {
    header("Location: iniciar_sesion.php");
    exit();
}

// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Asegurar que existe el campo 'leido'
$db->query("ALTER TABLE alertas ADD COLUMN IF NOT EXISTS leido TINYINT(1) DEFAULT 0");
$db->query("ALTER TABLE alertas ADD COLUMN IF NOT EXISTS prioridad ENUM('baja','media','alta','critica') DEFAULT 'media'");

// Marcar alertas como leídas
if (isset($_GET['marcar_leidas'])) {
    $db->query("UPDATE alertas SET leido = 1");
    header("Location: alertas.php");
    exit();
}

// Filtrar por categoría
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'todas';
$where = $filtro_categoria != 'todas' ? "WHERE categoria = '$filtro_categoria'" : "";

// Obtener estadísticas
$total_alertas = $db->query("SELECT COUNT(*) as total FROM alertas")->fetch_assoc()['total'];
$alertas_no_leidas = $db->query("SELECT COUNT(*) as total FROM alertas WHERE leido = 0")->fetch_assoc()['total'];

// Paginación
$por_pagina = 8;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $por_pagina;

$query_alertas = "SELECT a.*, 
                 DATE_FORMAT(a.fecha, '%d/%m/%Y %H:%i') as fecha_formateada
                 FROM alertas a
                 $where
                 ORDER BY a.fecha DESC
                 LIMIT $inicio, $por_pagina";

$alertas_result = $db->query($query_alertas);
$alertas = $alertas_result ? $alertas_result->fetch_all(MYSQLI_ASSOC) : [];

// Total para paginación
$total_result = $db->query("SELECT COUNT(*) as total FROM alertas $where");
$total_alertas_filtradas = $total_result ? $total_result->fetch_assoc()['total'] : 0;
$total_paginas = ceil($total_alertas_filtradas / $por_pagina);

// Categorías para filtro
$categorias_result = $db->query("SELECT DISTINCT categoria FROM alertas");
$categorias = $categorias_result ? $categorias_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas - Granja San José</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #6c5ce7;
            --color-secondary: #a29bfe;
            --color-success: #00b894;
            --color-info: #0984e3;
            --color-warning: #fdcb6e;
            --color-danger: #d63031;
            --color-light: #f8f9fa;
            --color-dark: #343a40;
            --color-text: #2d3436;
            --color-bg: #f5f6fa;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #2c3e50, #4ca1af);
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 5px;
            margin: 5px 10px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            font-weight: 500;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .header {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .alert-card {
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            margin-bottom: 20px;
            overflow: hidden;
            border: none;
            position: relative;
        }
        
        .alert-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--card-color-1), var(--card-color-2));
        }
        
        .alert-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .alert-card.unread {
            border-left: 5px solid var(--color-primary);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(108, 92, 231, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(108, 92, 231, 0); }
            100% { box-shadow: 0 0 0 0 rgba(108, 92, 231, 0); }
        }
        
        .alert-icon {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--card-color-1), var(--card-color-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 15px;
        }
        
        .alert-category {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 50px;
            background: linear-gradient(135deg, var(--card-color-1), var(--card-color-2));
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .alert-time {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .alert-priority {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .priority-baja { background-color: var(--color-success); }
        .priority-media { background-color: var(--color-info); }
        .priority-alta { background-color: var(--color-warning); }
        .priority-critica { background-color: var(--color-danger); animation: blink 1s infinite; }
        
        @keyframes blink {
            50% { opacity: 0.5; }
        }
        
        .filter-btn {
            border: none;
            background: white;
            color: var(--color-text);
            border-radius: 50px;
            padding: 8px 15px;
            margin: 5px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
        }
        
        .stats-card {
            border-radius: 15px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stats-card::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .stats-card.total { background: linear-gradient(135deg, #6c5ce7, #a29bfe); }
        .stats-card.unread { background: linear-gradient(135deg, #fd79a8, #e84393); }
        .stats-card.today { background: linear-gradient(135deg, #00cec9, #00b894); }
        .stats-card.critical { background: linear-gradient(135deg, #ff7675, #d63031); }
        
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .pagination .page-item .page-link {
            border: none;
            color: var(--color-text);
            border-radius: 50% !important;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
        }
        
        .pagination .page-item:not(.active) .page-link:hover {
            background: rgba(108, 92, 231, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 5rem;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        
        .mark-all-btn {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            color: white;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
            transition: all 0.3s;
        }
        
        .mark-all-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(108, 92, 231, 0.4);
        }
        
        /* Asignar colores dinámicos según categoría */
        .animal {
            --card-color-1: #6c5ce7;
            --card-color-2: #a29bfe;
        }
        
        .planta {
            --card-color-1: #00b894;
            --card-color-2: #55efc4;
        }
        
        .venta {
            --card-color-1: #0984e3;
            --card-color-2: #74b9ff;
        }
        
        .empleado {
            --card-color-1: #fdcb6e;
            --card-color-2: #ffeaa7;
        }
        
        .usuario {
            --card-color-1: #e84393;
            --card-color-2: #fd79a8;
        }
        
        .inventario {
            --card-color-1: #636e72;
            --card-color-2: #b2bec3;
        }
        
        .salud {
            --card-color-1: #00cec9;
            --card-color-2: #81ecec;
        }
        
        .vacunas {
            --card-color-1: #e17055;
            --card-color-2: #fab1a0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar py-4">
                <div class="text-center mb-5">
                    <h4 class="text-white"><i class="fas fa-leaf me-2"></i>Granja San José</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="animales.php">
                            <i class="fas fa-paw"></i> Animales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="plantas.php">
                            <i class="fas fa-leaf"></i> Plantas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventario.php">
                            <i class="fas fa-box-open"></i> Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ventas.php">
                            <i class="fas fa-shopping-cart"></i> Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="alertas.php">
                            <i class="fas fa-bell"></i> Alertas
                            <?php if ($alertas_no_leidas > 0): ?>
                                <span class="badge bg-danger rounded-pill float-end"><?= $alertas_no_leidas ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportes.php">
                            <i class="fas fa-chart-bar"></i> Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item mt-5">
                        <a class="nav-link text-white" href="../conexion/logout2.php">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 main-content">
                <div class="header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0"><i class="fas fa-bell me-2" style="background: linear-gradient(135deg, #6c5ce7, #a29bfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i> Sistema de Alertas</h2>
                            <p class="text-muted mb-0">Monitoriza todas las actividades importantes de tu granja</p>
                        </div>
                        <a href="alertas.php?marcar_leidas=1" class="mark-all-btn">
                            <i class="fas fa-check-circle me-1"></i> Marcar todas como leídas
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-filter me-2"></i>Filtrar por categoría</h5>
                        <div class="d-flex flex-wrap">
                            <a href="alertas.php?categoria=todas" class="filter-btn <?= $filtro_categoria == 'todas' ? 'active' : '' ?>">
                                <i class="fas fa-layer-group me-1"></i> Todas (<?= $total_alertas ?>)
                            </a>
                            <?php foreach ($categorias as $cat): ?>
                                <?php 
                                $count_result = $db->query("SELECT COUNT(*) as total FROM alertas WHERE categoria = '{$cat['categoria']}'");
                                $count = $count_result ? $count_result->fetch_assoc()['total'] : 0;
                                ?>
                                <a href="alertas.php?categoria=<?= $cat['categoria'] ?>" class="filter-btn <?= $filtro_categoria == $cat['categoria'] ? 'active' : '' ?>">
                                    <i class="fas 
                                        <?= $cat['categoria'] == 'animal' ? 'fa-paw' : '' ?>
                                        <?= $cat['categoria'] == 'planta' ? 'fa-leaf' : '' ?>
                                        <?= $cat['categoria'] == 'venta' ? 'fa-shopping-cart' : '' ?>
                                        <?= $cat['categoria'] == 'empleado' ? 'fa-user-tie' : '' ?>
                                        <?= $cat['categoria'] == 'usuario' ? 'fa-user' : '' ?>
                                        <?= $cat['categoria'] == 'inventario' ? 'fa-boxes' : '' ?>
                                        <?= $cat['categoria'] == 'salud' ? 'fa-heartbeat' : '' ?>
                                        <?= $cat['categoria'] == 'vacunas' ? 'fa-syringe' : '' ?>
                                        me-1"></i>
                                    <?= ucfirst($cat['categoria']) ?> (<?= $count ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card total">
                            <h5>Total Alertas</h5>
                            <h2><?= $total_alertas ?></h2>
                            <i class="fas fa-bell stats-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card unread">
                            <h5>No leídas</h5>
                            <h2><?= $alertas_no_leidas ?></h2>
                            <i class="fas fa-exclamation-circle stats-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card today">
                            <h5>Hoy</h5>
                            <h2><?= $db->query("SELECT COUNT(*) as total FROM alertas WHERE DATE(fecha) = CURDATE()")->fetch_assoc()['total'] ?></h2>
                            <i class="fas fa-calendar-day stats-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card critical">
                            <h5>Críticas</h5>
                            <h2><?= $db->query("SELECT COUNT(*) as total FROM alertas WHERE prioridad = 'critica'")->fetch_assoc()['total'] ?></h2>
                            <i class="fas fa-exclamation-triangle stats-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Lista de Alertas -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0"><i class="fas fa-list-ul me-2"></i> Listado de Alertas</h5>
                            <small class="text-muted">Mostrando <?= count($alertas) ?> de <?= $total_alertas_filtradas ?></small>
                        </div>
                        
                        <?php if (count($alertas) > 0): ?>
                            <div class="row">
                                <?php foreach ($alertas as $alerta): ?>
                                    <div class="col-md-6">
                                        <div class="alert-card <?= $alerta['categoria'] ?> <?= $alerta['leido'] == 0 ? 'unread' : '' ?>">
                                            <div class="card-body">
                                                <span class="alert-category"><?= ucfirst($alerta['categoria']) ?></span>
                                                <div class="d-flex align-items-start">
                                                    <div class="alert-icon">
                                                        <i class="fas 
                                                            <?= $alerta['categoria'] == 'animal' ? 'fa-paw' : '' ?>
                                                            <?= $alerta['categoria'] == 'planta' ? 'fa-leaf' : '' ?>
                                                            <?= $alerta['categoria'] == 'venta' ? 'fa-shopping-cart' : '' ?>
                                                            <?= $alerta['categoria'] == 'empleado' ? 'fa-user-tie' : '' ?>
                                                            <?= $alerta['categoria'] == 'usuario' ? 'fa-user' : '' ?>
                                                            <?= $alerta['categoria'] == 'inventario' ? 'fa-boxes' : '' ?>
                                                            <?= $alerta['categoria'] == 'salud' ? 'fa-heartbeat' : '' ?>
                                                            <?= $alerta['categoria'] == 'vacunas' ? 'fa-syringe' : '' ?>">
                                                        </i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-1"><?= htmlspecialchars($alerta['mensaje']) ?></h5>
                                                        <div class="d-flex align-items-center mt-2">
                                                            <span class="alert-priority priority-<?= $alerta['prioridad'] ?? 'media' ?>"></span>
                                                            <small class="alert-time me-3"><i class="far fa-clock me-1"></i> <?= $alerta['fecha_formateada'] ?></small>
                                                            <?php if ($alerta['leido'] == 0): ?>
                                                                <span class="badge bg-primary">Nueva</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Paginación -->
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagina > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="alertas.php?pagina=<?= $pagina-1 ?>&categoria=<?= $filtro_categoria ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php 
                                    $inicio_pag = max(1, $pagina - 2);
                                    $fin_pag = min($total_paginas, $pagina + 2);
                                    
                                    if ($inicio_pag > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="alertas.php?pagina=1&categoria=<?= $filtro_categoria ?>">1</a>
                                        </li>
                                        <?php if ($inicio_pag > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                                        <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="alertas.php?pagina=<?= $i ?>&categoria=<?= $filtro_categoria ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($fin_pag < $total_paginas): ?>
                                        <?php if ($fin_pag < $total_paginas - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="alertas.php?pagina=<?= $total_paginas ?>&categoria=<?= $filtro_categoria ?>"><?= $total_paginas ?></a>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($pagina < $total_paginas): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="alertas.php?pagina=<?= $pagina+1 ?>&categoria=<?= $filtro_categoria ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="far fa-bell-slash"></i>
                                <h3>No hay alertas para mostrar</h3>
                                <p class="text-muted">No se encontraron alertas con los filtros seleccionados</p>
                                <a href="alertas.php?categoria=todas" class="btn btn-primary mt-3">
                                    <i class="fas fa-sync-alt me-1"></i> Reiniciar filtros
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Efecto al hacer clic en una alerta
        $(document).ready(function() {
            $('.alert-card').click(function() {
                if ($(this).hasClass('unread')) {
                    $(this).removeClass('unread');
                    
                    // Aquí podrías agregar AJAX para marcar como leída en la base de datos
                    // $.post('marcar_leida.php', { id: alertaId }, function(response) {
                    //     console.log('Alerta marcada como leída');
                    // });
                }
            });
            
            // Efecto hover en las tarjetas de filtro
            $('.filter-btn').hover(
                function() {
                    if (!$(this).hasClass('active')) {
                        $(this).css('transform', 'translateY(-3px)');
                    }
                },
                function() {
                    if (!$(this).hasClass('active')) {
                        $(this).css('transform', 'translateY(0)');
                    }
                }
            );
        });
    </script>
</body>
</html>