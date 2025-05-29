<?php
session_start();
require_once 'conexion/conexion.php';

// Validar que se recibe el parámetro id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: tratamientos.php');
    exit;
}

$id_tratamiento = intval($_GET['id']);

// Conexión a la base de datos
$conexion = new Conexion();
$db = $conexion->getConexion();

// Obtener detalles del tratamiento
$stmt = $db->prepare(
    "SELECT t.*, p.nombre_comun, p.nombre_cientifico, r.fecha_reporte, r.diagnostico, r.id_reporte
     FROM tratamientos t
     JOIN reportes r ON t.id_reporte = r.id_reporte
     JOIN plantas p ON r.id_planta = p.id_planta
     WHERE t.id_tratamiento = ?"
);
$stmt->bind_param('i', $id_tratamiento);
$stmt->execute();
$result = $stmt->get_result();
$tratamiento = $result->fetch_assoc();

if (!$tratamiento) {
    header('Location: tratamientos.php');
    exit;
}

// Formatear fechas
$fecha_inicio = date('d/m/Y', strtotime($tratamiento['fecha_inicio']));
$fecha_fin = $tratamiento['fecha_fin'] ? date('d/m/Y', strtotime($tratamiento['fecha_fin'])) : '--';
$fecha_reporte = $tratamiento['fecha_reporte'] ? date('d/m/Y', strtotime($tratamiento['fecha_reporte'])) : '--';

// Badge de resultado
switch ($tratamiento['resultado']) {
    case 'Exitoso': $badge = 'bg-success'; break;
    case 'Fallido': $badge = 'bg-danger'; break;
    default: $badge = 'bg-warning'; break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Tratamiento</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
<div class="container py-4">
    <a href="tratamientos.php" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Volver a tratamientos
    </a>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Detalle del Tratamiento</h4>
            <a href="reportes.php?action=edit&id=<?php echo $tratamiento['id_reporte']; ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Planta</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($tratamiento['nombre_comun'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if (!empty($tratamiento['nombre_cientifico'])): ?>
                        <small class="text-muted">(<?php echo htmlspecialchars($tratamiento['nombre_cientifico'], ENT_QUOTES, 'UTF-8'); ?>)</small>
                    <?php endif; ?>
                </dd>

                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($tratamiento['descripcion'], ENT_QUOTES, 'UTF-8')); ?></dd>

                <dt class="col-sm-3">Fecha de inicio</dt>
                <dd class="col-sm-9"><?php echo $fecha_inicio; ?></dd>

                <dt class="col-sm-3">Fecha de fin</dt>
                <dd class="col-sm-9"><?php echo $fecha_fin; ?></dd>

                <dt class="col-sm-3">Resultado</dt>
                <dd class="col-sm-9">
                    <span class="badge <?php echo $badge; ?>">
                        <?php echo htmlspecialchars($tratamiento['resultado'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </dd>

                <dt class="col-sm-3">Fecha de reporte</dt>
                <dd class="col-sm-9"><?php echo $fecha_reporte; ?></dd>

                <dt class="col-sm-3">Diagnóstico</dt>
                <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($tratamiento['diagnostico'], ENT_QUOTES, 'UTF-8')); ?></dd>
            </dl>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>