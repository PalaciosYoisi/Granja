<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "granja"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$filtro = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["categoria"])) {
    $categoria = $conn->real_escape_string($_POST["categoria"]);
    if (strtolower($categoria) !== "todas" && $categoria !== "") {
        $filtro = "WHERE categoria = '$categoria'";
    }
}

$sql = "SELECT * FROM vista_auditoria $filtro";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Resultados</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/resultados.css">
</head>
<body>
  <div class="container">
    <h1>Resultados</h1>

    <?php if ($result && $result->num_rows > 0): ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead>
            <tr>
              <?php while ($fieldinfo = $result->fetch_field()): ?>
                <th><?php echo htmlspecialchars($fieldinfo->name); ?></th>
              <?php endwhile; ?>
            </tr>
          </thead>
          <tbody>
            <?php $result->data_seek(0); while ($row = $result->fetch_assoc()): ?>
              <tr>
                <?php foreach ($row as $value): ?>
                  <td><?php echo htmlspecialchars($value); ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-warning mt-4">No se encontraron resultados para esta categoría.</div>
    <?php endif; ?>

    <a href="../auditorias.html" class="btn-volver d-inline-block">← Volver al menú</a>
  </div>
</body>
</html>