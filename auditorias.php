<?php
session_start();
require_once 'conexion/conexion.php';

$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener lista de auditorías
$auditorias = $db->query("SELECT * FROM auditorias ORDER BY fecha_hora DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditorías - Sistema Granja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->

    <?php include 'includes/sidebar_admin.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div class="container header-content">
                <div class="logo">
                    <i class="fas fa-clipboard-list"></i>
                    <h1>Auditorías del Sistema</h1>
                </div>
                <div class="user-info">
                    <span><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nombre'] ?? 'U'); ?>" alt="Usuario">
                </div>
            </div>
        </header>

        <div class="container">
            <!-- Mostrar mensajes de éxito/error -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success fade-in">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                        switch($_GET['success']) {
                            case 'added': echo 'Registro de auditoría agregado correctamente'; break;
                            case 'updated': echo 'Registro de auditoría actualizado correctamente'; break;
                            case 'deleted': echo 'Registro de auditoría eliminado correctamente'; break;
                        }
                    ?>
                </div>
            <?php endif; ?>

            <div class="card fade-in">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-history"></i>
                        Registros de Auditoría
                    </div>
                </div>

                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th>Tabla</th>
                                <th>Acción</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditorias as $auditoria): ?>
                                <tr>
                                    <td><?php echo $auditoria['id_auditoria']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($auditoria['fecha_hora'])); ?></td>
                                    <td><?php echo $auditoria['usuario']; ?></td>
                                    <td><?php echo $auditoria['tabla_afectada']; ?></td>
                                    <td>
                                        <span class="status <?php 
                                            echo $auditoria['accion'] == 'INSERT' ? 'status-sano' : 
                                                 ($auditoria['accion'] == 'UPDATE' ? 'status-recuperacion' : 'status-enfermo'); 
                                        ?>">
                                            <?php echo $auditoria['accion']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $auditoria['detalles']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            background-color: var(--bg-dark);
            color: white;
            width: 250px;
            min-height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
        }

        .sidebar h4 {
            color: white;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }

        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-link i {
            margin-right: 10px;
            font-size: 18px;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px 0;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 28px;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-title h2 {
            font-size: 22px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn i {
            font-size: 16px;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-danger {
            background-color: #f44336;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        .btn-accent {
            background-color: var(--accent-color);
            color: var(--text-dark);
        }

        .btn-accent:hover {
            background-color: #FFA000;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
        }

        .table tr:hover {
            background-color: #f5f5f5;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-sano {
            background-color: #C8E6C9;
            color: #2E7D32;
        }

        .status-enfermo {
            background-color: #FFCDD2;
            color: #C62828;
        }

        .status-recuperacion {
            background-color: #FFF9C4;
            color: #F57F17;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f0f0f0;
            color: #555;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .action-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .action-btn.edit {
            background-color: #BBDEFB;
            color: #1976D2;
        }

        .action-btn.edit:hover {
            background-color: #1976D2;
            color: white;
        }

        .action-btn.delete {
            background-color: #FFCDD2;
            color: #C62828;
        }

        .action-btn.delete:hover {
            background-color: #C62828;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-dark);
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .select-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
        }

        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #C8E6C9;
            color: #2E7D32;
        }

        .alert-error {
            background-color: #FFCDD2;
            color: #C62828;
        }

        .alert i {
            font-size: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .plant-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            position: relative;
        }

        .plant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .plant-img {
            height: 180px;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 60px;
        }

        .plant-body {
            padding: 15px;
        }

        .plant-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-dark);
        }

        .plant-scientific {
            font-size: 14px;
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
        }

        .plant-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 14px;
        }

        .plant-detail {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .plant-detail i {
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        .plant-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state p {
            color: #777;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .table {
                display: block;
                overflow-x: auto;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse:hover {
            animation: pulse 1s infinite;
        }

        /* Toggle para vista de tarjetas/tabla */
        .view-toggle {
            display: flex;
            background-color: #f0f0f0;
            border-radius: 30px;
            padding: 5px;
            margin-left: auto;
        }

        .view-btn {
            background: none;
            border: none;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            color: #666;
            transition: var(--transition);
        }

        .view-btn.active {
            background-color: white;
            color: var(--primary-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .view-btn:hover {
            color: var(--primary-dark);
        }

        /* Efecto de carga */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
    </style>
</body>
</html>