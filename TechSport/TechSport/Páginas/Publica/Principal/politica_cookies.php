<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Cookies | TechSport</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <style>
        /* Estilos específicos para la política de cookies */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: white;
            min-height: 100vh;
        }

        .cookies-container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .cookies-header {
            background: linear-gradient(135deg, #4dabf7 0%, #3b5bdb 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .cookies-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .cookies-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .cookies-content {
            padding: 40px;
        }

        .section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #4dabf7;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section h2 i {
            color: #4dabf7;
        }

        .section h3 {
            color: #34495e;
            margin: 20px 0 10px;
            font-size: 1.3rem;
        }

        .section p {
            margin-bottom: 15px;
            color: #555;
            font-size: 1.05rem;
        }

        .section ul,
        .section ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .section li {
            margin-bottom: 10px;
            padding-left: 10px;
            color: #555;
        }

        .highlight-box {
            background: #e3f2fd;
            border: 2px solid #bbdefb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .cookie-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .cookie-table th {
            background: #4dabf7;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .cookie-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .cookie-table tr:hover {
            background: #f5f5f5;
        }

        .cookie-table tr:last-child td {
            border-bottom: none;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4dabf7 0%, #3b5bdb 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(77, 171, 247, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(77, 171, 247, 0.6);
        }

        .btn-secondary {
            background: white;
            color: #4dabf7;
            border: 2px solid #4dabf7;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            transform: translateY(-3px);
        }

        .cookie-icon {
            font-size: 1.5rem;
        }

        .last-updated {
            text-align: center;
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        /* Estilos responsive */
        @media (max-width: 768px) {
            .cookies-container {
                margin: 20px;
                border-radius: 15px;
            }

            .cookies-header {
                padding: 30px 20px;
            }

            .cookies-header h1 {
                font-size: 2rem;
            }

            .cookies-content {
                padding: 20px;
            }

            .section {
                padding: 20px;
            }

            .cookie-table {
                font-size: 0.9rem;
            }

            .cookie-table th,
            .cookie-table td {
                padding: 10px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .cookies-header h1 {
                font-size: 1.8rem;
            }

            .section h2 {
                font-size: 1.5rem;
            }

            .cookies-content {
                padding: 15px;
            }
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
    <!-- Overlay para animación inicial -->
    <div class="overlay-logo" id="overlayLogo">
        <div class="logo-animado" id="logoAnimado">
            <img src="/TechSport/Recursos/img/Logo.png" alt="Logo TechSport" class="logo-inicial">
            <img src="/TechSport/Recursos/img/Nombre.png" alt="TechSport" class="nombre-inicial">
        </div>
    </div>

    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" id="logo-interactivo" />
        </div>

        <nav class="navbar">
            <ul class="nav-menu">
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/inicio.html">Inicio</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/caracteristicas.php">Características</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/contacto.php">Contacto</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/EleccionUsuario/eleccion.html">Iniciar sesión</a></li>
                <li>
                    <button id="toggleAnimationBtn" class="cookie-btn">
                        <span id="animationStatus">❌ Desactivar animación</span>
                    </button>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="cookies-container">
            <div class="cookies-header">
                <h1><i class="fas fa-cookie-bite cookie-icon"></i> Política de Cookies</h1>
                <p>Transparencia y control sobre tus datos en TechSport</p>
            </div>

            <div class="cookies-content">
                <!-- Introducción -->
                <section class="section">
                    <h2><i class="fas fa-info-circle"></i> ¿Qué son las cookies?</h2>
                    <p>Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo cuando visitas nuestro sitio web. Son herramientas fundamentales que nos ayudan a:</p>
                    <ul>
                        <li>Recordar tus preferencias y configuraciones</li>
                        <li>Mejorar la experiencia de navegación</li>
                        <li>Analizar el uso del sitio para optimizar nuestros servicios</li>
                        <li>Personalizar contenido según tus intereses</li>
                    </ul>
                    <p>En TechSport, utilizamos cookies para ofrecerte la mejor experiencia posible en nuestra plataforma de gestión deportiva.</p>
                </section>

                <!-- Tipos de cookies que utilizamos -->
                <section class="section">
                    <h2><i class="fas fa-cookie"></i> Tipos de cookies que utilizamos</h2>

                    <div class="highlight-box">
                        <h3><i class="fas fa-shield-alt"></i> Cookies esenciales</h3>
                        <p>Son necesarias para el funcionamiento básico del sitio. Permiten la navegación y el uso de funciones como el acceso a áreas seguras.</p>
                    </div>

                    <div class="highlight-box">
                        <h3><i class="fas fa-chart-line"></i> Cookies de rendimiento</h3>
                        <p>Recopilan información sobre cómo los visitantes usan nuestro sitio (páginas más visitadas, errores, etc.) para mejorar el rendimiento.</p>
                    </div>

                    <div class="highlight-box">
                        <h3><i class="fas fa-user-cog"></i> Cookies de preferencias</h3>
                        <p>Permiten recordar tus elecciones (como idioma, región o preferencias de visualización) para ofrecerte una experiencia personalizada.</p>
                    </div>

                    <div class="highlight-box">
                        <h3><i class="fas fa-ad"></i> Cookies de funcionalidad</h3>
                        <p>Hacen posible funciones avanzadas como compartir contenido en redes sociales o reproducir videos.</p>
                    </div>
                </section>

                <!-- Tabla de cookies específicas -->
                <section class="section">
                    <h2><i class="fas fa-list-alt"></i> Cookies específicas de TechSport</h2>

                    <table class="cookie-table">
                        <thead>
                            <tr>
                                <th>Nombre de la cookie</th>
                                <th>Finalidad</th>
                                <th>Duración</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>techsport_session</strong></td>
                                <td>Gestionar la sesión del usuario</td>
                                <td>Sesión</td>
                                <td>Esencial</td>
                            </tr>
                            <tr>
                                <td><strong>animation_preference</strong></td>
                                <td>Recordar tu preferencia sobre la animación inicial</td>
                                <td>1 año</td>
                                <td>Preferencias</td>
                            </tr>
                            <tr>
                                <td><strong>cookies_accepted</strong></td>
                                <td>Guardar tu consentimiento sobre cookies</td>
                                <td>1 año</td>
                                <td>Esencial</td>
                            </tr>
                            <tr>
                                <td><strong>user_language</strong></td>
                                <td>Recordar tu idioma preferido</td>
                                <td>1 año</td>
                                <td>Preferencias</td>
                            </tr>
                            <tr>
                                <td><strong>site_analytics</strong></td>
                                <td>Analizar uso del sitio (anonimizado)</td>
                                <td>30 días</td>
                                <td>Rendimiento</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <!-- Control de cookies -->
                <section class="section">
                    <h2><i class="fas fa-sliders-h"></i> Control de cookies</h2>
                    <p>Tienes control total sobre las cookies que utilizamos. Puedes:</p>

                    <h3><i class="fas fa-check-circle"></i> Aceptar todas las cookies</h3>
                    <p>Al hacer clic en "Aceptar" en el banner de cookies, consientes el uso de todas las cookies según se describe en esta política.</p>

                    <h3><i class="fas fa-times-circle"></i> Rechazar cookies no esenciales</h3>
                    <p>Puedes rechazar las cookies no esenciales manteniendo las necesarias para el funcionamiento del sitio.</p>

                    <h3><i class="fas fa-cog"></i> Configurar preferencias</h3>
                    <p>Desde el botón en el menú de navegación puedes controlar específicamente la animación inicial y otras preferencias.</p>

                    <h3><i class="fas fa-trash-alt"></i> Eliminar cookies existentes</h3>
                    <p>Puedes eliminar las cookies ya instaladas a través de la configuración de tu navegador:</p>
                    <ol>
                        <li><strong>Chrome:</strong> Configuración → Privacidad y seguridad → Cookies y datos del sitio</li>
                        <li><strong>Firefox:</strong> Opciones → Privacidad y seguridad → Cookies y datos del sitio</li>
                        <li><strong>Safari:</strong> Preferencias → Privacidad → Administrar datos del sitio web</li>
                        <li><strong>Edge:</strong> Configuración → Cookies y permisos del sitio → Administrar y eliminar cookies</li>
                    </ol>

                    <div class="highlight-box">
                        <h3><i class="fas fa-exclamation-triangle"></i> Importante</h3>
                        <p>Si desactivas las cookies esenciales, algunas funciones de TechSport pueden no estar disponibles o no funcionar correctamente.</p>
                    </div>
                </section>

                <!-- Derechos del usuario -->
                <section class="section">
                    <h2><i class="fas fa-balance-scale"></i> Tus derechos</h2>
                    <p>De acuerdo con la Ley de Protección de Datos Personales y el RGPD, tienes derecho a:</p>
                    <ul>
                        <li><strong>Acceso:</strong> Saber qué datos personales tratamos</li>
                        <li><strong>Rectificación:</strong> Corregir datos inexactos</li>
                        <li><strong>Supresión:</strong> Solicitar la eliminación de tus datos</li>
                        <li><strong>Oposición:</strong> Oponerte al tratamiento de tus datos</li>
                        <li><strong>Limitación:</strong> Limitar el uso de tus datos en ciertas circunstancias</li>
                        <li><strong>Portabilidad:</strong> Recibir tus datos en formato estructurado</li>
                    </ul>
                    <p>Para ejercer estos derechos, puedes contactarnos a través de nuestra página de contacto.</p>
                </section>

                <!-- Contacto y actualizaciones -->
                <section class="section">
                    <h2><i class="fas fa-envelope"></i> Contacto y actualizaciones</h2>

                    <h3>¿Tienes preguntas?</h3>
                    <p>Si tienes alguna duda sobre nuestra política de cookies, contáctanos:</p>
                    <ul>
                        <li><strong>Email:</strong> privacidad@techsport.com</li>
                        <li><strong>Teléfono:</strong> +34 900 123 456</li>
                        <li><strong>Dirección:</strong> Calle Deportiva 123, 28001 Madrid, España</li>
                    </ul>

                    <h3>Actualizaciones de la política</h3>
                    <p>Esta política de cookies puede actualizarse periódicamente. Te notificaremos cualquier cambio significativo a través de nuestro sitio web.</p>

                    <p class="last-updated">
                        <strong>Última actualización:</strong> <?php echo date('d/m/Y'); ?>
                    </p>
                </section>

                <!-- Botones de acción -->
                <div class="action-buttons">
                    <a href="/TechSport/Páginas/Publica/Principal/inicio.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Volver al inicio
                    </a>

                    <button id="manageCookies" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> Gestionar mis cookies
                    </button>

                    <a href="/TechSport/Páginas/Publica/Principal/contacto.php" class="btn btn-secondary">
                        <i class="fas fa-question-circle"></i> Contactar soporte
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
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
                <p><a href="/TechSport/Páginas/Publica/Principal/politica_cookies.php" class="footer-link">Política de
                        Cookies</a></p>
                <p><a href="/TechSport/Páginas/Publica/Principal/politica_privacidad.php" class="footer-link">Política de
                        Privacidad</a></p>

            </div>
        </div>
    </footer>


    <script>
        // Funciones para manejar cookies (las mismas que en la página principal)
        function setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + date.toUTCString();
            document.cookie = name + "=" + value + ";" + expires + ";path=/";
        }

        function getCookie(name) {
            const cookieName = name + "=";
            const decodedCookie = decodeURIComponent(document.cookie);
            const cookieArray = decodedCookie.split(';');

            for (let i = 0; i < cookieArray.length; i++) {
                let cookie = cookieArray[i];
                while (cookie.charAt(0) === ' ') {
                    cookie = cookie.substring(1);
                }
                if (cookie.indexOf(cookieName) === 0) {
                    return cookie.substring(cookieName.length, cookie.length);
                }
            }
            return "";
        }

        function deleteCookie(name) {
            document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }

        function checkAnimationPreference() {
            const animationDisabled = getCookie("animationDisabled");
            const cookiesAccepted = getCookie("cookiesAccepted");

            // Si la animación está desactivada o no hay cookies aceptadas, ocultamos inmediatamente
            if (animationDisabled === "true" || !cookiesAccepted) {
                const overlayLogo = document.getElementById('overlayLogo');
                overlayLogo.style.display = 'none';
                updateAnimationButton(true);
            } else {
                // Mostrar animación normal
                const overlayLogo = document.getElementById('overlayLogo');
                setTimeout(() => {
                    overlayLogo.style.opacity = '0';
                    setTimeout(() => {
                        overlayLogo.style.display = 'none';
                    }, 800);
                }, 2500);
                updateAnimationButton(false);
            }
        }

        function updateAnimationButton(isDisabled) {
            const button = document.getElementById('toggleAnimationBtn');
            const statusText = document.getElementById('animationStatus');

            if (isDisabled) {
                statusText.textContent = "✅ Activar animación";
                button.title = "Haz clic para activar la animación de inicio";
            } else {
                statusText.textContent = "❌ Desactivar animación";
                button.title = "Haz clic para desactivar la animación de inicio";
            }
        }

        function toggleAnimationPreference() {
            const currentValue = getCookie("animationDisabled");
            const cookiesAccepted = getCookie("cookiesAccepted");

            if (!cookiesAccepted || cookiesAccepted !== "true") {
                alert("Por favor, acepta las cookies primero para poder guardar tus preferencias.");
                return;
            }

            if (currentValue === "true") {
                deleteCookie("animationDisabled");
                updateAnimationButton(false);
                alert("Animación activada. Se mostrará en tu próxima visita.");
            } else {
                setCookie("animationDisabled", "true", 365);
                updateAnimationButton(true);
                alert("Animación desactivada. No se mostrará en tus futuras visitas.");
            }
        }

        // Función para mostrar panel de gestión de cookies
        function showCookieManager() {
            const cookiesAccepted = getCookie("cookiesAccepted");
            const animationDisabled = getCookie("animationDisabled");

            let message = "Estado actual de tus cookies:\n\n";

            if (cookiesAccepted === "true") {
                message += "✅ Cookies aceptadas\n";
            } else {
                message += "❌ Cookies no aceptadas\n";
            }

            if (animationDisabled === "true") {
                message += "❌ Animación desactivada\n";
            } else {
                message += "✅ Animación activada\n";
            }

            message += "\n¿Qué deseas hacer?";

            const action = confirm(message + "\n\nHaz clic en Aceptar para ver opciones detalladas.");

            if (action) {
                const choice = prompt(
                    "Elige una opción:\n" +
                    "1 - Eliminar todas las cookies\n" +
                    "2 - Eliminar solo preferencias\n" +
                    "3 - Cambiar preferencia de animación\n" +
                    "4 - Cancelar"
                );

                switch (choice) {
                    case "1":
                        deleteCookie("cookiesAccepted");
                        deleteCookie("animationDisabled");
                        alert("Todas las cookies han sido eliminadas.");
                        location.reload();
                        break;
                    case "2":
                        deleteCookie("animationDisabled");
                        alert("Preferencias eliminadas. Se restaurarán valores por defecto.");
                        location.reload();
                        break;
                    case "3":
                        toggleAnimationPreference();
                        break;
                    default:
                        // Cancelar
                        break;
                }
            }
        }

        // Inicialización cuando el DOM está cargado
        document.addEventListener('DOMContentLoaded', function() {
            checkAnimationPreference();

            // Configurar botón de alternar animación
            const toggleBtn = document.getElementById('toggleAnimationBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleAnimationPreference);
            }

            // Configurar botón de gestión de cookies
            const manageCookiesBtn = document.getElementById('manageCookies');
            if (manageCookiesBtn) {
                manageCookiesBtn.addEventListener('click', showCookieManager);
            }

            // Configurar logo interactivo
            const logoInteractivo = document.getElementById('logo-interactivo');
            if (logoInteractivo) {
                logoInteractivo.addEventListener('click', function() {
                    window.location.href = "/TechSport/Páginas/Publica/Principal/inicio.php";
                });
            }
        });
    </script>
</body>

</html>