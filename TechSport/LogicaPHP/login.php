<?php
/**
 * login.php - Único archivo de login para los 3 tipos de usuario
 * Ubicación: /TechSport/LogicaPHP/login.php
 */

session_start();

// Incluir conexión a base de datos
require_once dirname(__FILE__) . '/basededatos.php';

// Obtener tipo de usuario (de URL, POST o sesión)
$tipo = 'jugador'; // Valor por defecto

// 1. Primero verificar si viene por GET (desde eleccion.html)
if (isset($_GET['tipo']) && in_array($_GET['tipo'], ['jugador', 'entrenador', 'profesional'])) {
    $tipo = $_GET['tipo'];
    $_SESSION['tipo_seleccionado'] = $tipo;
} 
// 2. Si no, verificar si viene por POST (del formulario)
elseif (isset($_POST['tipo']) && in_array($_POST['tipo'], ['jugador', 'entrenador', 'profesional'])) {
    $tipo = $_POST['tipo'];
}
// 3. Si no, verificar si está guardado en sesión
elseif (isset($_SESSION['tipo_seleccionado'])) {
    $tipo = $_SESSION['tipo_seleccionado'];
}

// Mapear tipo a rol_id
$rol_ids = [
    'jugador' => 2,
    'entrenador' => 1,
    'profesional' => 3
];
$rol_id_esperado = $rol_ids[$tipo];

// Variable para mensajes de error
$error = '';

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validaciones
    if (empty($email) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingresa un correo electrónico válido.";
    } else {
        // Verificar conexión
        if (!isset($conn) || $conn->connect_error) {
            $error = "Error de conexión con el servidor. Intenta más tarde.";
        } else {
            // Buscar usuario
            $stmt = $conn->prepare("SELECT id, nombre, rol_id, password_hash FROM usuarios WHERE email = ?");
            
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows === 0) {
                    $error = "Correo o contraseña incorrectos.";
                } else {
                    $stmt->bind_result($usuario_id, $usuario_nombre, $usuario_rol_id, $password_hash);
                    $stmt->fetch();
                    $stmt->close();
                    
                    // Verificar contraseña
                    if (password_verify($password, $password_hash)) {
                        // Verificar que el usuario tenga el rol correcto
                        if ($usuario_rol_id != $rol_id_esperado) {
                            // Determinar qué tipo de usuario es realmente
                            $tipos_reales = array_flip($rol_ids);
                            $tipo_real = isset($tipos_reales[$usuario_rol_id]) ? $tipos_reales[$usuario_rol_id] : 'usuario';
                            $error = "Este correo está registrado como <strong>" . ucfirst($tipo_real) . "</strong>. ";
                            $error .= "Por favor, selecciona <strong>" . ucfirst($tipo_real) . "</strong> en la página de elección.";
                        } else {
                            // Login exitoso
                            $_SESSION['usuario_id'] = $usuario_id;
                            $_SESSION['nombre'] = $usuario_nombre;
                            $_SESSION['rol_id'] = $usuario_rol_id;
                            $_SESSION['email'] = $email;
                            $_SESSION['tipo_usuario'] = $tipo;
                            
                            // Redirigir según el rol
                            switch ($usuario_rol_id) {
                                case 1: // Entrenador
                                    header("Location: /TechSport/Páginas/Privadas/Entrenador/inicio.php");
                                    exit();
                                case 2: // Jugador
                                    header("Location: /TechSport/Páginas/Privadas/Jugador/inicio.php");
                                    exit();
                                case 3: // Profesional
                                    header("Location: /TechSport/Páginas/Privadas/Profesional/inicio.php");
                                    exit();
                                default:
                                    header("Location: /TechSport/Páginas/Publica/Principal/inicio.html");
                                    exit();
                            }
                        }
                    } else {
                        $error = "Correo o contraseña incorrectos.";
                    }
                }
            } else {
                $error = "Error en el sistema. Intenta más tarde.";
            }
        }
    }
}

// Si llegamos aquí, mostrar el formulario de login
// Configurar título según tipo
$titulos = [
    'jugador' => 'Jugador',
    'entrenador' => 'Entrenador',
    'profesional' => 'Profesional'
];

$titulo_pagina = isset($titulos[$tipo]) ? 'Acceso ' . $titulos[$tipo] : 'Acceso';

// Generar URL para registro manteniendo el tipo
$url_registro = "/TechSport/LogicaPHP/registro.php?tipo=" . $tipo;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login <?php echo ucfirst($tipo); ?> | TechSport</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Login/estilos.css">
    <style>
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
            animation: fadeIn 0.3s ease;
        }
        .register-section, .login-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .register-section a, .login-section a {
            color: #1a2a6c;
            text-decoration: none;
            font-weight: 600;
            margin: 0 5px;
        }
        .register-section a:hover, .login-section a:hover {
            text-decoration: underline;
        }
        .nav-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }
        .nav-links a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .nav-links a:hover {
            color: #1a2a6c;
            text-decoration: underline;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" id="logo-interactivo" />
        </div>
        <nav class="navbar">
            <ul class="nav-menu">
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/inicio.html">Inicio</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/caracteristicas.html">Características</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/contacto.html">Contacto</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/EleccionUsuario/eleccion.html">Iniciar sesión</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="main-content">
        <div class="login-wrapper">
            <div class="login-container">
                <div class="login-card">                    
                    <h2><?php echo $titulo_pagina; ?></h2>
                    
                    <!-- Mensaje de error -->
                    <?php if ($error): ?>
                    <div id="errorMessage" class="error-message">
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form id="loginForm" class="login-form" method="POST" action="" novalidate>
                        <!-- Campo oculto con el tipo de usuario -->
                        <input type="hidden" name="tipo" value="<?php echo $tipo; ?>">
                        
                        <div class="form-group">
                            <label for="email">Correo electrónico</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="ejemplo@correo.com" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   required>
                            <div id="emailError" class="input-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input type="password" id="password" name="password" 
                                   placeholder="••••••••" required>
                            <div id="passwordError" class="input-error"></div>
                        </div>
                        
                        <button type="submit">Ingresar</button>
                    </form>
                    
                    <!-- Sección de registro (dentro de login) -->
                    <div class="register-section">
                        <p>¿No tienes cuenta? 
                            <a href="<?php echo $url_registro; ?>">Regístrate como <?php echo ucfirst($tipo); ?></a>
                        </p>
                    </div>
                    
                    <!-- Navegación entre tipos de usuario -->
                    <div class="nav-links">
                        <a href="/TechSport/Páginas/Publica/EleccionUsuario/eleccion.html">
                            ← Volver a elección
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="/TechSport/Recursos/img/Logo.png" alt="TechSport">
                </div>
                <h3>TechSport</h3>
                <p>Gestión Deportiva Inteligente</p>
            </div>
            <div class="footer-bottom">
                <p>© 2025 TechSport. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Validación básica del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            if (!form) return;
            
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Limpiar errores anteriores
                document.getElementById('emailError').textContent = '';
                document.getElementById('passwordError').textContent = '';
                
                // Validar email
                if (!emailInput.value.trim()) {
                    document.getElementById('emailError').textContent = 'El email es obligatorio';
                    valid = false;
                } else if (!isValidEmail(emailInput.value)) {
                    document.getElementById('emailError').textContent = 'Email no válido';
                    valid = false;
                }
                
                // Validar contraseña
                if (!passwordInput.value) {
                    document.getElementById('passwordError').textContent = 'La contraseña es obligatoria';
                    valid = false;
                } else if (passwordInput.value.length < 6) {
                    document.getElementById('passwordError').textContent = 'Mínimo 6 caracteres';
                    valid = false;
                }
                
                if (!valid) {
                    e.preventDefault();
                }
            });
            
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
        });
    </script>
</body>
</html>