<?php
session_start();
require_once 'conexion/conexion.php';

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
        --primary-color: #4CAF50;
        --primary-dark: #388E3C;
        --primary-light: #C8E6C9;
        --secondary-color: #8BC34A;
        --accent-color: #FFC107;
        --text-dark: #333;
        --text-light: #f5f5f5;
        --bg-light: #f9f9f9;
        --bg-dark: #2E7D32;
        --border-radius: 8px;
        --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--bg-light);
        color: var(--text-dark);
        line-height: 1.6;
    }

    .sidebar {
        min-height: 100vh;
        background-color: var(--bg-dark);
        color: white;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        border-radius: var(--border-radius);
        margin: 5px 10px;
        transition: var(--transition);
        display: flex;
        align-items: center;
    }

    .sidebar .nav-link:hover {
        color: white;
        background-color: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
    }

    .sidebar .nav-link.active {
        color: white;
        background-color: var(--primary-color);
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
        background-color: var(--bg-light);
    }

    .header {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 20px;
        margin-bottom: 30px;
    }

    .alert-card {
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        margin-bottom: 20px;
        overflow: hidden;
        border: none;
        position: relative;
        background-color: white;
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
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .alert-card.unread {
        border-left: 5px solid var(--primary-color);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
        100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
    }

    .alert-icon {
        font-size: 1.8rem;
        background: linear-gradient(135deg, var(--card-color-1), var(--card-color-2));
        background-clip: text;
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
        color: #666;
    }

    .alert-priority {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .priority-baja { background-color: var(--primary-color); }
    .priority-media { background-color: var(--secondary-color); }
    .priority-alta { background-color: var(--accent-color); }
    .priority-critica { background-color: #f44336; animation: blink 1s infinite; }

    @keyframes blink {
        50% { opacity: 0.5; }
    }

    .filter-btn {
        border: none;
        background: white;
        color: var(--text-dark);
        border-radius: 50px;
        padding: 8px 15px;
        margin: 5px;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        font-weight: 500;
    }

    .filter-btn:hover, .filter-btn.active {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
    }

    .stats-card {
        border-radius: var(--border-radius);
        padding: 20px;
        color: white;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
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

    .stats-card.total { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
    .stats-card.unread { background: linear-gradient(135deg, #f44336, #d32f2f); }
    .stats-card.today { background: linear-gradient(135deg, #00bcd4, #0097a7); }
    .stats-card.critical { background: linear-gradient(135deg, #ff5722, #e64a19); }

    .stats-icon {
        font-size: 2.5rem;
        opacity: 0.3;
        position: absolute;
        top: 20px;
        right: 20px;
    }

    .pagination .page-item .page-link {
        border: none;
        color: var(--text-dark);
        border-radius: 50% !important;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 5px;
        transition: var(--transition);
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
    }

    .pagination .page-item:not(.active) .page-link:hover {
        background: rgba(76, 175, 80, 0.1);
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }

    .empty-state i {
        font-size: 5rem;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 20px;
    }

    .mark-all-btn {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border: none;
        border-radius: 50px;
        padding: 10px 20px;
        color: white;
        font-weight: 500;
        box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        transition: var(--transition);
    }

    .mark-all-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(76, 175, 80, 0.4);
    }

    /* Asignar colores dinámicos según categoría */
    .animal {
        --card-color-1: #4CAF50;
        --card-color-2: #8BC34A;
    }

    .planta {
        --card-color-1: #8BC34A;
        --card-color-2: #CDDC39;
    }

    .venta {
        --card-color-1: #2196F3;
        --card-color-2: #64B5F6;
    }

    .empleado {
        --card-color-1: #FFC107;
        --card-color-2: #FFD54F;
    }

    .usuario {
        --card-color-1: #9C27B0;
        --card-color-2: #BA68C8;
    }

    .inventario {
        --card-color-1: #607D8B;
        --card-color-2: #90A4AE;
    }

    .salud {
        --card-color-1: #00BCD4;
        --card-color-2: #80DEEA;
    }

    .vacunas {
        --card-color-1: #FF5722;
        --card-color-2: #FF8A65;
    }

    /* Animaciones */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in {
        animation: fadeIn 0.5s ease forwards;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            min-height: auto;
            position: relative;
        }
        
        .main-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
    }
</style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            
                       <?php
            // Mostrar sidebar según el tipo de usuario
            switch ($_SESSION['tipo_usuario']) {
                case 'Administrador':
                    include 'includes/sidebar_admin.php';
                    break;
                case 'Veterinario':
                    include 'includes/sidebar_veterinario.php';
                    break;
                case 'Investigador':
                    include 'includes/sidebar_investigador.php';
                    break;
                // Agrega más casos según tus tipos de usuario
                default:
                    include 'includes/sidebar.php';
                    break;
            }
            ?>
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

                }
            });

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