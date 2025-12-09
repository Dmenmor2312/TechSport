<?php
/**
 * registro.php - Único archivo de registro para los 3 tipos de usuario
 * Ubicación: /TechSport/LogicaPHP/registro.php
 */

session_start();

// Incluir conexión a base de datos
require_once dirname(__FILE__) . '/basededatos.php';

// Obtener tipo de usuario
$tipo = 'jugador';
if (isset($_GET['tipo']) && in_array($_GET['tipo'], ['jugador', 'entrenador', 'profesional'])) {
    $tipo = $_GET['tipo'];
    $_SESSION['tipo_seleccionado'] = $tipo;
}

// Mapear tipo a rol_id
$rol_ids = [
    'jugador' => 2,
    'entrenador' => 1,
    'profesional' => 3
];
$rol_id = $rol_ids[$tipo];

// Variables para mensajes y datos
$error = '';
$success = '';
$form_data = [
    'nombre' => '',
    'email' => '',
    'rol_id' => $rol_id
];

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'])) {
    $form_data['nombre'] = trim($_POST['nombre']);
    $form_data['email'] = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    
    // Validaciones
    $errors = [];
    
    if (empty($form_data['nombre'])) {
        $errors[] = "El nombre completo es obligatorio.";
    } elseif (strlen($form_data['nombre']) < 3) {
        $errors[] = "El nombre debe tener al menos 3 caracteres.";
    }
    
    if (empty($form_data['email'])) {
        $errors[] = "El correo electrónico es obligatorio.";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Por favor, ingresa un correo electrónico válido.";
    }
    
    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria.";
    } elseif (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una mayúscula y un número.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden.";
    }
    
    // Si no hay errores, proceder con registro
    if (empty($errors)) {
        if (!isset($conn) || $conn->connect_error) {
            $error = "Error de conexión con el servidor. Intenta más tarde.";
        } else {
            // Verificar si el email ya existe
            $check_stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            
            if ($check_stmt) {
                $check_stmt->bind_param("s", $form_data['email']);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows > 0) {
                    $errors[] = "Este correo electrónico ya está registrado.";
                }
                $check_stmt->close();
            }
            
            // Si no hay errores, proceder con el registro
            if (empty($errors)) {
                // Hashear la contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertar nuevo usuario
                $insert_stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol_id) VALUES (?, ?, ?, ?)");
                
                if ($insert_stmt) {
                    $insert_stmt->bind_param("sssi", 
                        $form_data['nombre'], 
                        $form_data['email'], 
                        $password_hash, 
                        $rol_id
                    );
                    
                    if ($insert_stmt->execute()) {
                        $success = "✅ Registro exitoso como " . ucfirst($tipo) . ". Ahora puedes iniciar sesión.";
                        // Limpiar formulario
                        $form_data = ['nombre' => '', 'email' => '', 'rol_id' => $rol_id];
                        
                        // Obtener el ID del usuario recién registrado
                        $usuario_id = $insert_stmt->insert_id;
                        
                        // Iniciar sesión automáticamente (opcional)
                        $_SESSION['usuario_id'] = $usuario_id;
                        $_SESSION['nombre'] = $form_data['nombre'];
                        $_SESSION['rol_id'] = $rol_id;
                        $_SESSION['email'] = $form_data['email'];
                        $_SESSION['tipo_usuario'] = $tipo;
                        
                    } else {
                        $errors[] = "Error al registrar usuario. Intenta nuevamente.";
                    }
                    $insert_stmt->close();
                } else {
                    $errors[] = "Error en el sistema de registro.";
                }
            }
            $conn->close();
        }
    }
    
    // Convertir errores a mensaje único
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}

// Configurar título según tipo
$titulos = [
    'jugador' => 'Jugador',
    'entrenador' => 'Entrenador',
    'profesional' => 'Profesional'
];

$titulo_pagina = isset($titulos[$tipo]) ? 'Registro ' . $titulos[$tipo] : 'Registro';

// Generar URL para login manteniendo el tipo
$url_login = "/TechSport/LogicaPHP/login.php?tipo=" . $tipo;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro <?php echo ucfirst($tipo); ?> | TechSport</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Login/estilos.css">
    <style>
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
            animation: fadeIn 0.3s ease;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            border-left-color: #c62828;
        }
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left-color: #2e7d32;
        }
        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        .login-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .login-section a {
            color: #1a2a6c;
            text-decoration: none;
            font-weight: 600;
            margin: 0 5px;
        }
        .login-section a:hover {
            text-decoration: underline;
        }
        .nav-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
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
        .nav-links a.active {
            color: #1a2a6c;
            font-weight: bold;
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
                    
                    <!-- Mensajes -->
                    <?php if ($error): ?>
                    <div id="errorMessage" class="message error-message">
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div id="successMessage" class="message success-message">
                        <?php echo $success; ?>
                        <?php if (!isset($_SESSION['usuario_id'])): ?>
                        <p>Redirigiendo automáticamente en 3 segundos...</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$success): ?>
                    <form id="registerForm" class="login-form" method="POST" action="" novalidate>
                        <!-- Campo oculto con el tipo de usuario -->
                        <input type="hidden" name="tipo" value="<?php echo $tipo; ?>">
                        <input type="hidden" name="rol_id" value="<?php echo $rol_id; ?>">
                        
                        <div class="form-group">
                            <label for="nombre">Nombre completo</label>
                            <input type="text" id="nombre" name="nombre" 
                                   placeholder="Juan Pérez"
                                   value="<?php echo htmlspecialchars($form_data['nombre']); ?>"
                                   required>
                            <div id="nombreError" class="input-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Correo electrónico</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="ejemplo@correo.com"
                                   value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                   required>
                            <div id="emailError" class="input-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input type="password" id="password" name="password" 
                                   placeholder="••••••••" 
                                   required>
                            <div class="password-requirements">
                                Mínimo 6 caracteres, al menos una mayúscula y un número
                            </div>
                            <div id="passwordError" class="input-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirmar contraseña</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" 
                                   placeholder="••••••••" 
                                   required>
                            <div id="confirmPasswordError" class="input-error"></div>
                        </div>
                        
                        <button type="submit">Crear Cuenta</button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Sección de login (dentro de registro) -->
                    <div class="login-section">
                        <p>¿Ya tienes cuenta? 
                            <a href="<?php echo $url_login; ?>">Inicia sesión como <?php echo ucfirst($tipo); ?></a>
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
        // Validación del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            if (!form) return;
            
            const passwordInput = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');
            
            // Validación en tiempo real de la contraseña
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const requirements = document.querySelector('.password-requirements');
                
                if (password.length >= 6 && /[A-Z]/.test(password) && /\d/.test(password)) {
                    requirements.style.color = '#2e7d32';
                } else {
                    requirements.style.color = '#666';
                }
            });
            
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Limpiar errores anteriores
                document.querySelectorAll('.input-error').forEach(el => el.textContent = '');
                
                // Validar nombre
                const nombre = document.getElementById('nombre').value.trim();
                if (!nombre) {
                    document.getElementById('nombreError').textContent = 'El nombre es obligatorio';
                    valid = false;
                } else if (nombre.length < 3) {
                    document.getElementById('nombreError').textContent = 'Mínimo 3 caracteres';
                    valid = false;
                }
                
                // Validar email
                const email = document.getElementById('email').value.trim();
                if (!email) {
                    document.getElementById('emailError').textContent = 'El email es obligatorio';
                    valid = false;
                } else if (!isValidEmail(email)) {
                    document.getElementById('emailError').textContent = 'Email no válido';
                    valid = false;
                }
                
                // Validar contraseña
                const password = passwordInput.value;
                if (!password) {
                    passwordError.textContent = 'La contraseña es obligatoria';
                    valid = false;
                } else if (password.length < 6) {
                    passwordError.textContent = 'Mínimo 6 caracteres';
                    valid = false;
                } else if (!/(?=.*[A-Z])(?=.*\d)/.test(password)) {
                    passwordError.textContent = 'Debe tener una mayúscula y un número';
                    valid = false;
                }
                
                // Validar confirmación
                const confirmPassword = document.getElementById('confirmPassword').value;
                if (!confirmPassword) {
                    document.getElementById('confirmPasswordError').textContent = 'Confirma tu contraseña';
                    valid = false;
                } else if (password !== confirmPassword) {
                    document.getElementById('confirmPasswordError').textContent = 'Las contraseñas no coinciden';
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