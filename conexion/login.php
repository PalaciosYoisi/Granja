<?php 
#1 paso, establecer conexion con la base de datos
$conexion = mysqli_connect("localhost","root","","granja");
#2 paso, verificar la conexion
if(!$conexion){
    echo "error de conexion".mysqli_connect_error();
    exit();

}
#3 establecer comando sql

$sql = "select correo, clave from usuarios where correo='".$_POST['correo']."' and clave='".$_POST['clave']."'";

#4 ejecutar el comando
$resultado = mysqli_query($conexion, $sql);
$registro = mysqli_fetch_array($resultado);

if($registro){
    header("HTTP/1.1 302 Moved Temporarily"); 
    header("Location: ../inicio.html");
}else {
    echo 'El correo o clave es incorrecto ';
}

?>