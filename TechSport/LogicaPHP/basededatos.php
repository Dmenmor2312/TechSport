<?php

/**
 * Archivo de conexión a la base de datos
 * Ubicación: C:/xampp/htdocs/TechSport/LogicaPHP/basededatos.php
 */

// Configuración de la conexión
$host = "localhost";
$usuario = "root";
$password = ""; // Por defecto en XAMPP está vacía
$base_datos = "techsport";
$puerto = 3306;

// Intentar conexión
$conn = @new mysqli($host, $usuario, $password, $base_datos, $puerto);

// Verificar conexión
if ($conn->connect_error) {
    // Crear mensaje de error detallado
    $error_mensaje = "❌ ERROR DE CONEXIÓN A LA BASE DE DATOS\n\n";

    switch ($conn->connect_errno) {
        case 1045:
            $error_mensaje .= "Acceso denegado. Usuario o contraseña incorrectos.\n";
            $error_mensaje .= "Usuario: $usuario\n";
            break;
        case 1049:
            $error_mensaje .= "La base de datos '$base_datos' no existe.\n";
            $error_mensaje .= "Crea la base de datos en phpMyAdmin.\n";
            break;
        case 2002:
            $error_mensaje .= "No se puede conectar al servidor MySQL.\n";
            $error_mensaje .= "Verifica que XAMPP esté ejecutándose.\n";
            break;
        default:
            $error_mensaje .= "Error #{$conn->connect_errno}: {$conn->connect_error}\n";
    }

    $error_mensaje .= "\nSOLUCIÓN:\n";
    $error_mensaje .= "1. Abre XAMPP y asegúrate que MySQL está en VERDE\n";
    $error_mensaje .= "2. Abre phpMyAdmin (http://localhost/phpmyadmin)\n";
    $error_mensaje .= "3. Crea la base de datos 'techsport' si no existe\n";

    // Guardar error en sesión para mostrar en alerta
    session_start();
    $_SESSION['db_error'] = $error_mensaje;

    // Redirigir a página de error o mantener la variable para alerta
    die("ERROR_CONEXION_BD");
}

// Configurar charset para caracteres especiales
$conn->set_charset("utf8mb4");

// Mensaje de éxito (solo en desarrollo - comentar en producción)
// error_log("✅ Conexión a MySQL establecida correctamente");

// Retornar conexión exitosa
return true;
