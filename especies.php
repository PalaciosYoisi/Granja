<?php
session_start();
require_once 'conexion/conexion.php';

// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener todas las especies
$especies_result = $db->query("SELECT * FROM especies ORDER BY nombre_especie ASC");
$especies = $especies_result ? $especies_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Especies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --accent-color: #FFC107;
            --text-dark: #333;
            --bg-light: #f9f9f9;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        body {
            background: var(--bg-light);
            font-family: 'Poppins', Arial, sans-serif;
            color: var(--text-dark);
        }
        .page-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 2rem;
            color: var(--primary-dark);
            margin-bottom: 30px;
        }
        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
        }
        .table th {
            background: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
        }
        .table tr:hover {
            background: #f5f5f5;
        }
        .btn-back {
            background: var(--primary-color);
            color: #fff;
            border-radius: var(--border-radius);
            border: none;
            padding: 8px 18px;
            margin-bottom: 18px;
            transition: var(--transition);
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        .btn-back:hover {
            background: var(--primary-dark);
            color: #fff;
            transform: translateY(-2px);
        }
        .especie-icon {
            color: var(--primary-color);
            margin-right: 8px;
            font-size: 1.2em;
        }
        .empty-state {
            text-align: center;
            color: #888;
            padding: 40px 0;
        }
        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="page-title">
            <i class="fas fa-dna"></i> Listado de Especies
        </div>
        <a href="investigador.php" class="btn btn-back mb-3">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
        <div class="card">
            <div class="card-body">
                <?php if (!empty($especies)): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th><i class="fas fa-leaf especie-icon"></i>Nombre de Especie</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($especies as $especie): ?>
                            <tr>
                                <td><?php echo $especie['id_especie']; ?></td>
                                <td>
                                    <i class="fas fa-leaf especie-icon"></i>
                                    <?php echo htmlspecialchars($especie['nombre_especie'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($especie['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-dna"></i>
                    <h4>No hay especies registradas.</h4>
                    <p>Agrega nuevas especies para comenzar a gestionar tu granja.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>