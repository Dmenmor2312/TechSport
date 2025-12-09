<?php
session_start();

// Configuración
$admin_email = "dmmorata.contact@gmail.com";
$site_name = "TechSport";

// Procesar formulario si se envió
$mensaje_enviado = false;
$errores = [];
$datos_formulario = [
    'nombre' => '',
    'email' => '',
    'asunto' => '',
    'mensaje' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar y validar datos
    $datos_formulario['nombre'] = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $datos_formulario['email'] = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $datos_formulario['asunto'] = htmlspecialchars(trim($_POST['asunto'] ?? ''));
    $datos_formulario['mensaje'] = htmlspecialchars(trim($_POST['mensaje'] ?? ''));
    
    // Validaciones
    if (empty($datos_formulario['nombre']) || strlen($datos_formulario['nombre']) < 3) {
        $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    }
    
    if (empty($datos_formulario['email']) || !filter_var($datos_formulario['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Por favor ingresa un email válido';
    }
    
    if (empty($datos_formulario['asunto'])) {
        $errores['asunto'] = 'Por favor selecciona un asunto';
    }
    
    if (empty($datos_formulario['mensaje']) || strlen($datos_formulario['mensaje']) < 10) {
        $errores['mensaje'] = 'El mensaje debe tener al menos 10 caracteres';
    }
    
    // Si no hay errores, simular envío y guardar
    if (empty($errores)) {
        // SIMULAR ÉXITO (sin enviar correo real)
        $mensaje_enviado = true;
        
        // Guardar en base de datos (si existe)
        guardarContactoEnBD($datos_formulario);
        
        // También guardar en archivo log para desarrollo
        guardarContactoEnArchivo($datos_formulario);
        
        // Limpiar datos del formulario
        $datos_formulario = [
            'nombre' => '',
            'email' => '',
            'asunto' => '',
            'mensaje' => ''
        ];
        
        // Mostrar mensaje de éxito por 5 segundos
        $_SESSION['contacto_exitoso'] = true;
        
        // Mostrar datos del "correo" en consola PHP (para desarrollo)
        mostrarDatosEnConsola($datos_formulario);
    }
}

// Función para mostrar datos en consola PHP
function mostrarDatosEnConsola($datos) {
    echo "<!-- \n";
    echo "=== CORREO SIMULADO ===\n";
    echo "Para: dmmorata.contact@gmail.com\n";
    echo "De: " . $datos['nombre'] . " <" . $datos['email'] . ">\n";
    echo "Asunto: Contacto TechSport - " . ucfirst($datos['asunto']) . "\n";
    echo "Mensaje: " . substr($datos['mensaje'], 0, 100) . "...\n";
    echo "Fecha: " . date('d/m/Y H:i:s') . "\n";
    echo "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    echo "=== FIN SIMULACIÓN ===\n";
    echo "-->\n";
}

// Función para guardar en archivo log
function guardarContactoEnArchivo($datos) {
    $log_file = $_SERVER['DOCUMENT_ROOT'] . '/TechSport/contactos_log.txt';
    
    $log_entry = date('Y-m-d H:i:s') . " | ";
    $log_entry .= "IP: " . $_SERVER['REMOTE_ADDR'] . " | ";
    $log_entry .= "Nombre: " . $datos['nombre'] . " | ";
    $log_entry .= "Email: " . $datos['email'] . " | ";
    $log_entry .= "Asunto: " . $datos['asunto'] . " | ";
    $log_entry .= "Mensaje: " . substr($datos['mensaje'], 0, 100) . "...\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Función para guardar en base de datos (opcional)
function guardarContactoEnBD($datos) {
    // Solo ejecutar si existe la conexión
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';
        
        global $conn;
        
        if ($conn) {
            // Verificar si la tabla existe
            $tabla_existe = $conn->query("SHOW TABLES LIKE 'contactos'");
            
            if ($tabla_existe->num_rows == 0) {
                // Crear tabla si no existe
                $sql_crear = "CREATE TABLE contactos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    asunto VARCHAR(50) NOT NULL,
                    mensaje TEXT NOT NULL,
                    ip_address VARCHAR(45),
                    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    leido BOOLEAN DEFAULT FALSE
                )";
                
                $conn->query($sql_crear);
            }
            
            // Insertar el contacto
            $stmt = $conn->prepare("INSERT INTO contactos (nombre, email, asunto, mensaje, ip_address) VALUES (?, ?, ?, ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("sssss", 
                $datos['nombre'], 
                $datos['email'], 
                $datos['asunto'], 
                $datos['mensaje'], 
                $ip
            );
            
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Verificar si hay mensaje de éxito en sesión
if (isset($_SESSION['contacto_exitoso']) && $_SESSION['contacto_exitoso']) {
    $mensaje_enviado = true;
    unset($_SESSION['contacto_exitoso']);
}
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto | TechSport</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Inicio/estilos.css">
    <style>
        /* ESTILOS ESPECÍFICOS PARA ESTA PÁGINA */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --gray-light: #f3f4f6;
            --gray-medium: #9ca3af;
            --gray-dark: #4b5563;
        }

        main {
            padding-top: 120px;
            min-height: calc(100vh - 200px);
        }

        /* Sección Hero */
        .contact-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 80px 5%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .contact-hero h1 {
            font-size: 2.8rem;
            margin-bottom: 20px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .contact-hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            line-height: 1.6;
        }

        /* Contenedor principal */
        .contact-container {
            max-width: 1000px;
            margin: -40px auto 60px;
            padding: 0 5%;
            position: relative;
            z-index: 2;
        }

        /* Tarjeta de información */
        .contact-info-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .info-item {
            text-align: center;
            padding: 25px;
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: var(--gray-light);
        }

        .info-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .info-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .info-item h3 {
            margin-bottom: 10px;
            color: var(--primary-dark);
            font-size: 1.3rem;
        }

        .info-item p {
            color: var(--gray-dark);
            line-height: 1.5;
        }

        /* Formulario */
        .contact-form {
            background: white;
            border-radius: 16px;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: var(--primary-dark);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--gray-dark);
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            min-height: 180px;
            resize: vertical;
            line-height: 1.5;
        }

        /* Mensajes de error */
        .error-message {
            color: var(--error-color);
            font-size: 0.9rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .error-message::before {
            content: "⚠️";
        }

        /* Mensaje de éxito */
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            animation: slideIn 0.5s ease;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 2px solid #10b981;
            color: #065f46;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 2px solid #ef4444;
            color: #7f1d1d;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            color: #92400e;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Botón de enviar */
        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* FAQ Section */
        .faq-section {
            margin-top: 80px;
            padding: 60px 5%;
            background: var(--gray-light);
            border-radius: 20px;
        }

        .faq-section h2 {
            text-align: center;
            color: var(--primary-dark);
            font-size: 2.2rem;
            margin-bottom: 40px;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .faq-item {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .faq-item:hover {
            transform: translateY(-5px);
        }

        .faq-item h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .faq-item p {
            color: var(--gray-dark);
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            main {
                padding-top: 100px;
            }

            .contact-hero {
                padding: 60px 5%;
            }

            .contact-hero h1 {
                font-size: 2rem;
            }

            .contact-container {
                margin-top: -20px;
            }

            .contact-info-card {
                padding: 25px;
                grid-template-columns: 1fr;
            }

            .contact-form {
                padding: 30px;
            }

            .form-header h2 {
                font-size: 1.6rem;
            }

            .faq-section {
                padding: 40px 5%;
            }

            .faq-section h2 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .contact-hero h1 {
                font-size: 1.6rem;
            }

            .contact-form {
                padding: 20px;
            }

            .submit-btn {
                padding: 15px;
            }
        }

        /* Animación para campos con error */
        .has-error {
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .footer-link {
            color: #ccc;
            text-decoration: none;
            font-size: 13px;
        }

        .footer-link:hover {
            text-decoration: underline;
            color: white;
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
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/caracteristicas.php">Características</a></li>
                <li><a class="nav-link active" href="/TechSport/Páginas/Publica/Principal/contacto.php">Contacto</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/EleccionUsuario/eleccion.html">Iniciar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Sección Hero -->
        <section class="contact-hero">
            <h1>Contacta con el Administrador</h1>
            <p>¿Tienes preguntas, sugerencias o necesitas ayuda? Estamos aquí para escucharte y ayudarte en lo que necesites.</p>
        </section>

        <!-- Contenedor de contacto -->
        <div class="contact-container">
            <!-- Tarjeta de información -->
            <div class="contact-info-card">
                <div class="info-item">
                    <div class="info-icon">📧</div>
                    <h3>Email de Contacto</h3>
                    <p><?php echo htmlspecialchars($admin_email); ?></p>
                </div>
                <div class="info-item">
                    <div class="info-icon">⏰</div>
                    <h3>Tiempo de Respuesta</h3>
                    <p>24-48 horas hábiles</p>
                </div>
                <div class="info-item">
                    <div class="info-icon">📍</div>
                    <h3>Soporte 24/7</h3>
                    <p>Asistencia técnica disponible</p>
                </div>
            </div>

            <!-- Mensajes de alerta -->
            <?php if ($mensaje_enviado): ?>
                <div class="alert alert-success">
                    <h3>✅ ¡Mensaje Enviado Exitosamente!</h3>
                    <p>Gracias por contactarnos. Hemos recibido tu mensaje y nos pondremos en contacto contigo en las próximas 24-48 horas hábiles.</p>
                    <p><small>Te hemos enviado una copia a: <?php echo htmlspecialchars($datos_formulario['email']); ?></small></p>
                </div>
            <?php elseif (isset($errores['general'])): ?>
                <div class="alert alert-error">
                    <h3>❌ Error al Enviar</h3>
                    <p><?php echo htmlspecialchars($errores['general']); ?></p>
                    <p><small>Por favor, intenta de nuevo o contacta directamente a <?php echo htmlspecialchars($admin_email); ?></small></p>
                </div>
            <?php endif; ?>

            <!-- Formulario de contacto -->
            <form class="contact-form" method="POST" action="" id="contactForm">
                <div class="form-header">
                    <h2>Envíanos un Mensaje</h2>
                    <p>Completa el formulario y nos pondremos en contacto contigo lo antes posible.</p>
                </div>

                <div class="form-group <?php echo isset($errores['nombre']) ? 'has-error' : ''; ?>">
                    <label for="nombre">Nombre completo *</label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars($datos_formulario['nombre']); ?>"
                           placeholder="Ingresa tu nombre completo"
                           required>
                    <?php if (isset($errores['nombre'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errores['nombre']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group <?php echo isset($errores['email']) ? 'has-error' : ''; ?>">
                    <label for="email">Correo electrónico *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($datos_formulario['email']); ?>"
                           placeholder="tucorreo@ejemplo.com"
                           required>
                    <?php if (isset($errores['email'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errores['email']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group <?php echo isset($errores['asunto']) ? 'has-error' : ''; ?>">
                    <label for="asunto">Asunto *</label>
                    <select id="asunto" name="asunto" required>
                        <option value="" disabled <?php echo empty($datos_formulario['asunto']) ? 'selected' : ''; ?>>Selecciona un asunto</option>
                        <option value="consulta" <?php echo $datos_formulario['asunto'] == 'consulta' ? 'selected' : ''; ?>>Consulta general</option>
                        <option value="soporte" <?php echo $datos_formulario['asunto'] == 'soporte' ? 'selected' : ''; ?>>Soporte técnico</option>
                        <option value="sugerencia" <?php echo $datos_formulario['asunto'] == 'sugerencia' ? 'selected' : ''; ?>>Sugerencia</option>
                        <option value="reporte" <?php echo $datos_formulario['asunto'] == 'reporte' ? 'selected' : ''; ?>>Reporte de problema</option>
                        <option value="colaboracion" <?php echo $datos_formulario['asunto'] == 'colaboracion' ? 'selected' : ''; ?>>Colaboración</option>
                        <option value="otro" <?php echo $datos_formulario['asunto'] == 'otro' ? 'selected' : ''; ?>>Otro</option>
                    </select>
                    <?php if (isset($errores['asunto'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errores['asunto']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group <?php echo isset($errores['mensaje']) ? 'has-error' : ''; ?>">
                    <label for="mensaje">Mensaje *</label>
                    <textarea id="mensaje" name="mensaje" 
                              placeholder="Describe tu consulta, sugerencia o problema con detalle..."
                              required><?php echo htmlspecialchars($datos_formulario['mensaje']); ?></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                        <small id="charCount">0/500 caracteres</small>
                        <?php if (isset($errores['mensaje'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errores['mensaje']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Campos de seguridad -->
                <input type="hidden" name="token" id="token" value="<?php echo bin2hex(random_bytes(16)); ?>">
                <input type="hidden" name="timestamp" value="<?php echo time(); ?>">

                <button type="submit" class="submit-btn" id="submitBtn">
                    <span id="btnText">📤 Enviar Mensaje</span>
                    <span class="loading" id="loadingSpinner" style="display: none;"></span>
                </button>

                <div style="text-align: center; margin-top: 20px; color: var(--gray-medium); font-size: 0.9rem;">
                    <p>Los campos marcados con * son obligatorios</p>
                    <p>Al enviar este formulario, aceptas nuestra <a href="/TechSport/Páginas/Publica/Principal/politica_privacidad.php" style="color: var(--primary-color);">Política de Privacidad</a></p>
                </div>
            </form>
        </div>

        <!-- Sección de Preguntas Frecuentes -->
        <section class="faq-section">
            <h2>❓ Preguntas Frecuentes</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <h3>¿Cuánto tardan en responder?</h3>
                    <p>Normalmente respondemos en 24-48 horas hábiles. Para emergencias técnicas, intentamos responder en menos de 12 horas.</p>
                </div>
                <div class="faq-item">
                    <h3>¿Puedo solicitar una demostración?</h3>
                    <p>Sí, puedes solicitar una demostración personalizada seleccionando "Colaboración" en el asunto del formulario.</p>
                </div>
                <div class="faq-item">
                    <h3>¿Ofrecen soporte técnico?</h3>
                    <p>Sí, ofrecemos soporte técnico completo para todos nuestros usuarios. Selecciona "Soporte técnico" en el formulario.</p>
                </div>
                <div class="faq-item">
                    <h3>¿Cómo reporto un error?</h3>
                    <p>Selecciona "Reporte de problema" en el asunto y describe el error con todos los detalles posibles.</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
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
                <p>Contacto: <?php echo htmlspecialchars($admin_email); ?></p>
                <p><a href="/TechSport/Páginas/Publica/Principal/politica_cookies.php" class="footer-link">Política de
                        Cookies</a></p>
                <p><a href="/TechSport/Páginas/Publica/Principal/politica_privacidad.php" class="footer-link">Política de
                        Privacidad</a></p>
                
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const mensajeTextarea = document.getElementById('mensaje');
            const charCount = document.getElementById('charCount');
            
            // Contador de caracteres
            mensajeTextarea.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = `${length}/500 caracteres`;
                
                if (length > 500) {
                    charCount.style.color = '#ef4444';
                } else if (length > 400) {
                    charCount.style.color = '#f59e0b';
                } else {
                    charCount.style.color = '#6b7280';
                }
            });
            
            // Actualizar contador al cargar la página
            mensajeTextarea.dispatchEvent(new Event('input'));
            
            // Validación en tiempo real
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                input.addEventListener('input', function() {
                    // Remover clases de error al empezar a escribir
                    this.classList.remove('has-error');
                    const errorDiv = this.parentNode.querySelector('.error-message');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                });
            });
            
            // Envío del formulario
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Validar todos los campos
                let isValid = true;
                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    // Enfocar el primer campo con error
                    const firstError = form.querySelector('.has-error');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }
                
                // Mostrar loading
                submitBtn.disabled = true;
                btnText.textContent = 'Enviando...';
                loadingSpinner.style.display = 'inline-block';
                
                // Simular delay para ver el loading (en producción quitar esto)
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                // Enviar formulario
                this.submit();
            });
            
            // Función de validación
            function validateField(field) {
                const value = field.value.trim();
                const fieldName = field.name;
                let isValid = true;
                let errorMessage = '';
                
                field.classList.remove('has-error');
                const errorDiv = field.parentNode.querySelector('.error-message');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }
                
                // Validaciones específicas por campo
                switch(fieldName) {
                    case 'nombre':
                        if (value.length < 3) {
                            isValid = false;
                            errorMessage = 'El nombre debe tener al menos 3 caracteres';
                        }
                        break;
                        
                    case 'email':
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            isValid = false;
                            errorMessage = 'Por favor ingresa un email válido';
                        }
                        break;
                        
                    case 'asunto':
                        if (!value) {
                            isValid = false;
                            errorMessage = 'Por favor selecciona un asunto';
                        }
                        break;
                        
                    case 'mensaje':
                        if (value.length < 10) {
                            isValid = false;
                            errorMessage = 'El mensaje debe tener al menos 10 caracteres';
                        } else if (value.length > 500) {
                            isValid = false;
                            errorMessage = 'El mensaje no puede exceder los 500 caracteres';
                        }
                        break;
                }
                
                if (!isValid) {
                    field.classList.add('has-error');
                    
                    if (!errorDiv) {
                        const newErrorDiv = document.createElement('div');
                        newErrorDiv.className = 'error-message';
                        newErrorDiv.textContent = errorMessage;
                        field.parentNode.appendChild(newErrorDiv);
                    } else {
                        errorDiv.textContent = errorMessage;
                        errorDiv.style.display = 'flex';
                    }
                    
                    // Efecto de shake para campos con error
                    field.style.animation = 'none';
                    setTimeout(() => {
                        field.style.animation = 'shake 0.5s ease';
                    }, 10);
                }
                
                return isValid;
            }
            
            // Efecto hover para tarjetas de info
            const infoItems = document.querySelectorAll('.info-item');
            infoItems.forEach(item => {
                item.addEventListener('mouseenter', () => {
                    item.style.transform = 'translateY(-8px)';
                    item.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.12)';
                });
                
                item.addEventListener('mouseleave', () => {
                    item.style.transform = 'translateY(0)';
                    item.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.08)';
                });
            });
            
            // Smooth scroll para enlaces internos
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId !== '#') {
                        const targetElement = document.querySelector(targetId);
                        if (targetElement) {
                            targetElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>