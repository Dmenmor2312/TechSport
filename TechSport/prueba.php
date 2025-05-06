<?php
    $servidor = "localhost";
    $usuario = "root";
    $clave = "";
    $dbname = "techsport";
    $conexion = mysqli_connect($servidor, $usuario, $clave, $dbname);
    if ($conexion->connect_error) {
        die("Conexión fallida: " . $conexion->connect_error);
    }
    mysqli_close($conexion);
?>