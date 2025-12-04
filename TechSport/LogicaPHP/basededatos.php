<?php
$servidor = "localhost";
$usuario = "root";
$contraseña = "";
$nombre_base_datos = "techsport";

// Crear conexión
$conn = mysqli_connect($servidor, $usuario, $contraseña, $nombre_base_datos);

// Verificar conexión
if (!$conn) {
    die("La conexión falló: " . mysqli_connect_error());
}
echo "Conexión exitosa"; // Opcional, para verificar que funciona
?>