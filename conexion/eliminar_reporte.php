<?php
require_once 'conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $conexion = new Conexion();
    $db = $conexion->getConexion();
    
    // Primero eliminamos los tratamientos asociados
    $query = "DELETE FROM tratamientos WHERE id_reporte = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Luego eliminamos el reporte
    $query = "DELETE FROM reportes WHERE id_reporte = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        session_start();
        $_SESSION['mensaje'] = "Reporte eliminado correctamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        session_start();
        $_SESSION['mensaje'] = "Error al eliminar el reporte";
        $_SESSION['tipo_mensaje'] = "danger";
    }
    
    header("Location: ../reportes.php");
    exit();
}
?>