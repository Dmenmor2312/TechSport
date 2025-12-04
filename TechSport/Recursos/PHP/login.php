<?php
session_start();
include 'basededatos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $passwordFormulario = isset($_POST['password']) ? $_POST['password'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) die("Correo electrónico no válido.");
    if ($passwordFormulario === '') die("Debes ingresar la contraseña.");

    // Buscar usuario por email y contraseña directamente
    $stmt = $conn->prepare("SELECT id, nombre, rol_id FROM usuarios WHERE email = ? AND password_hash = ?");
    $stmt->bind_param("ss", $email, $passwordFormulario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        die("Correo o contraseña incorrectos.");
    }

    $stmt->bind_result($usuario_id, $usuario_nombre, $usuario_rol_id);
    $stmt->fetch();

    $_SESSION['usuario_id'] = $usuario_id;
    $_SESSION['nombre'] = $usuario_nombre;
    $_SESSION['rol_id'] = $usuario_rol_id;

    // Redirigir según rol
    if ($usuario_rol_id == 1) {
        header("Location: /TechSport/Páginas/Privadas/Entrenador/inicio.html");
    } elseif ($usuario_rol_id == 2) {
        header("Location: /TechSport/Páginas/Privadas/Jugador/inicio.html");
    } elseif ($usuario_rol_id == 3) {
        header("Location: /TechSport/Páginas/Privadas/Profesional/inicio.html");
    } else {
        echo "Rol de usuario no válido.";
    }

    $stmt->close();
    $conn->close();

} else {
    echo "Acceso no permitido.";
}
?>
