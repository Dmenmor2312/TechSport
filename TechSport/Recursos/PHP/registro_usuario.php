<?php
require_once 'C:/xampp/htdocs/TechSport/LogicaPHP/basededatos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';
    $rol_id = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : 2; // jugador por defecto

    // Validaciones
    if (strlen($nombre) < 3) die("El nombre debe tener al menos 3 caracteres.");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) die("Correo electrónico no válido.");
    if (strlen($password) < 1) die("Debes ingresar una contraseña.");
    if ($password !== $confirmPassword) die("Las contraseñas no coinciden.");
    if ($rol_id < 1 || $rol_id > 3) die("Rol de usuario inválido.");

    // Verificar si el email ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) die("El correo electrónico ya está registrado.");
    $stmt->close();

    // Insertar usuario con contraseña en texto plano
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $nombre, $email, $password, $rol_id);

    if ($stmt->execute()) {
        echo "Registro exitoso. <a href='/TechSport/Páginas/Públicas/Jugador/login.html'>Inicia sesión</a>.";
    } else {
        echo "Error al registrar usuario: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Acceso no permitido.";
}
