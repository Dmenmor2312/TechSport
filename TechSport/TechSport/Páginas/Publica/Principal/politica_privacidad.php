<?php
session_start();
$page_title = "Política de Privacidad | TechSport";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Inicio/estilos.css">
    <style>
        /* ESTILOS SIMPLIFICADOS PARA POLÍTICA DE PRIVACIDAD */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --border-color: #e5e7eb;
        }

        main {
            padding-top: 120px;
            min-height: calc(100vh - 200px);
            background: #f8fafc;
        }

        /* Hero Section */
        .privacy-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 80px 5%;
            text-align: center;
        }

        .privacy-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .privacy-hero p {
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .last-updated {
            background: rgba(255, 255, 255, 0.1);
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        /* Main Container */
        .privacy-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 5%;
            display: flex;
            gap: 40px;
        }

        /* Table of Contents */
        .toc-sidebar {
            width: 300px;
            flex-shrink: 0;
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 140px;
        }

        .toc-sidebar h3 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            font-size: 1.3rem;
        }

        .toc-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .toc-nav li {
            margin-bottom: 10px;
        }

        .toc-nav a {
            color: var(--text-color);
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .toc-nav a:hover {
            background: var(--bg-light);
            color: var(--primary-color);
        }

        /* Privacy Content */
        .privacy-content {
            flex: 1;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        /* Section Styles */
        .privacy-section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .privacy-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .section-header h2 {
            color: var(--primary-dark);
            font-size: 1.6rem;
            margin: 0;
            font-weight: 700;
        }

        .section-content {
            line-height: 1.7;
            color: var(--text-color);
        }

        .section-content p {
            margin-bottom: 15px;
            font-size: 1.05rem;
        }

        .section-content ul,
        .section-content ol {
            margin: 15px 0;
            padding-left: 20px;
        }

        .section-content li {
            margin-bottom: 8px;
        }

        /* Important Notes */
        .important-note {
            background: #fff7ed;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .important-note strong {
            color: #92400e;
        }

        .legal-note {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 0.95rem;
            color: var(--text-light);
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .data-table th {
            background: var(--primary-color);
            color: white;
            text-align: left;
            padding: 12px;
            font-weight: 600;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table tr:hover {
            background: var(--bg-light);
        }

        /* Contact Section */
        .contact-section {
            background: #f0f9ff;
            border-radius: 12px;
            padding: 30px;
            margin-top: 40px;
            text-align: center;
        }

        .contact-section h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .contact-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .privacy-container {
                flex-direction: column;
            }
            
            .toc-sidebar {
                width: 100%;
                position: static;
            }
        }

        @media (max-width: 768px) {
            main {
                padding-top: 100px;
            }

            .privacy-hero {
                padding: 60px 5%;
            }

            .privacy-hero h1 {
                font-size: 2rem;
            }

            .privacy-content {
                padding: 25px;
            }

            .section-header h2 {
                font-size: 1.4rem;
            }

            .contact-info {
                flex-direction: column;
                align-items: center;
            }
        }

        @media (max-width: 480px) {
            .privacy-hero h1 {
                font-size: 1.6rem;
            }

            .privacy-content {
                padding: 20px;
            }

            .data-table {
                font-size: 0.9rem;
            }

            .data-table th,
            .data-table td {
                padding: 8px;
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
    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" />
        </div>

        <nav class="navbar">
            <ul class="nav-menu">
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/inicio.html">Inicio</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/caracteristicas.php">Características</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/contacto.php">Contacto</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/EleccionUsuario/eleccion.html">Iniciar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="privacy-hero">
            <h1>🔒 Política de Privacidad</h1>
            <p>En TechSport nos comprometemos a proteger y respetar tu privacidad. Esta política explica cómo recopilamos, usamos y protegemos tu información personal.</p>
            <div class="last-updated">
                Última actualización: <?php echo date('d/m/Y'); ?>
            </div>
        </section>

        <!-- Main Container -->
        <div class="privacy-container">
            <!-- Table of Contents -->
            <aside class="toc-sidebar">
                <h3>📑 Índice de Contenidos</h3>
                <nav class="toc-nav">
                    <ul>
                        <li><a href="#introduccion">1. Introducción</a></li>
                        <li><a href="#datos-recopilados">2. Datos que Recopilamos</a></li>
                        <li><a href="#uso-datos">3. Uso de la Información</a></li>
                        <li><a href="#proteccion-datos">4. Protección de Datos</a></li>
                        <li><a href="#cookies">5. Cookies</a></li>
                        <li><a href="#derechos">6. Tus Derechos</a></li>
                        <li><a href="#menores">7. Menores de Edad</a></li>
                        <li><a href="#cambios">8. Cambios en la Política</a></li>
                        <li><a href="#contacto">9. Contacto</a></li>
                    </ul>
                </nav>
            </aside>

            <!-- Privacy Content -->
            <div class="privacy-content">
                <!-- Section 1: Introducción -->
                <section id="introduccion" class="privacy-section">
                    <div class="section-header">
                        <h2>Introducción</h2>
                    </div>
                    <div class="section-content">
                        <p>TechSport ("nosotros", "nuestro", "nos") opera la plataforma TechSport (en lo sucesivo, el "Servicio"). Esta página te informa sobre nuestras políticas respecto a la recopilación, uso y divulgación de datos personales cuando utilizas nuestro Servicio y las opciones que tienes asociadas a esos datos.</p>
                        
                        <div class="important-note">
                            <p><strong>⚠️ Importante:</strong> Al utilizar nuestro Servicio, aceptas la recopilación y uso de información de acuerdo con esta política. A menos que se defina lo contrario en esta Política de Privacidad, los términos utilizados tienen el mismo significado que en nuestros Términos y Condiciones.</p>
                        </div>
                        
                        <p>Esta Política de Privacidad se basa en los principios de transparencia, seguridad y respeto por tu privacidad, cumpliendo con las regulaciones aplicables.</p>
                    </div>
                </section>

                <!-- Section 2: Datos Recopilados -->
                <section id="datos-recopilados" class="privacy-section">
                    <div class="section-header">
                        <h2>Datos Personales que Recopilamos</h2>
                    </div>
                    <div class="section-content">
                        <p>Mientras utilizas nuestro Servicio, podemos pedirte que nos proporciones cierta información de identificación personal que se puede utilizar para contactarte o identificarte ("Datos Personales"). La información de identificación personal puede incluir, entre otras:</p>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Categoría de Datos</th>
                                    <th>Ejemplos</th>
                                    <th>Finalidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Datos de Identificación</strong></td>
                                    <td>Nombre completo, email, teléfono</td>
                                    <td>Creación de cuenta y comunicación</td>
                                </tr>
                                <tr>
                                    <td><strong>Datos de Perfil</strong></td>
                                    <td>Fecha de nacimiento, posición deportiva</td>
                                    <td>Personalización del servicio</td>
                                </tr>
                                <tr>
                                    <td><strong>Datos de Actividad</strong></td>
                                    <td>Estadísticas deportivas, rendimiento físico</td>
                                    <td>Análisis de rendimiento</td>
                                </tr>
                                <tr>
                                    <td><strong>Datos Técnicos</strong></td>
                                    <td>Dirección IP, tipo de navegador</td>
                                    <td>Seguridad y optimización</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="legal-note">
                            <p><strong>🔍 Nota Legal:</strong> Solo recopilamos datos personales que son necesarios para proporcionar nuestros servicios.</p>
                        </div>
                    </div>
                </section>

                <!-- Section 3: Uso de Datos -->
                <section id="uso-datos" class="privacy-section">
                    <div class="section-header">
                        <h2>Uso de la Información</h2>
                    </div>
                    <div class="section-content">
                        <p>TechSport utiliza los datos recopilados para varios propósitos:</p>
                        
                        <ul>
                            <li><strong>Proporcionar y mantener nuestro Servicio</strong></li>
                            <li><strong>Gestión de Cuentas</strong></li>
                            <li><strong>Análisis Deportivo</strong></li>
                            <li><strong>Comunicación</strong></li>
                            <li><strong>Mejora del Servicio</strong></li>
                            <li><strong>Seguridad</strong></li>
                            <li><strong>Cumplimiento Legal</strong></li>
                        </ul>
                        
                        <div class="important-note">
                            <p><strong>🎯 Base Legal:</strong> El tratamiento de tus datos se basa en tu consentimiento, la necesidad de ejecutar un contrato contigo o cumplir con nuestras obligaciones legales.</p>
                        </div>
                    </div>
                </section>

                <!-- Section 4: Protección de Datos -->
                <section id="proteccion-datos" class="privacy-section">
                    <div class="section-header">
                        <h2>Protección de Datos</h2>
                    </div>
                    <div class="section-content">
                        <p>La seguridad de tus datos es importante para nosotros. Implementamos medidas de seguridad como:</p>
                        
                        <ul>
                            <li>Encriptación SSL/TLS</li>
                            <li>Almacenamiento en servidores seguros</li>
                            <li>Control de acceso restringido</li>
                            <li>Auditorías regulares de seguridad</li>
                            <li>Copias de seguridad</li>
                        </ul>
                        
                        <p>A pesar de nuestras medidas de seguridad, debes tener en cuenta que ninguna transmisión de datos por Internet es 100% segura.</p>
                    </div>
                </section>

                <!-- Section 5: Cookies -->
                <section id="cookies" class="privacy-section">
                    <div class="section-header">
                        <h2>Uso de Cookies</h2>
                    </div>
                    <div class="section-content">
                        <p>Utilizamos cookies y tecnologías similares para mejorar tu experiencia en nuestro sitio web.</p>
                        
                        <h3 style="color: var(--primary-dark); margin-top: 20px; margin-bottom: 15px;">Tipos de Cookies que Utilizamos:</h3>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tipo de Cookie</th>
                                    <th>Propósito</th>
                                    <th>Duración</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Esenciales</strong></td>
                                    <td>Funcionamiento básico del sitio</td>
                                    <td>Sesión</td>
                                </tr>
                                <tr>
                                    <td><strong>Funcionales</strong></td>
                                    <td>Recordar preferencias</td>
                                    <td>Hasta 1 año</td>
                                </tr>
                                <tr>
                                    <td><strong>Analíticas</strong></td>
                                    <td>Analizar uso del sitio</td>
                                    <td>Hasta 2 años</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Section 6: Tus Derechos -->
                <section id="derechos" class="privacy-section">
                    <div class="section-header">
                        <h2>Tus Derechos de Protección de Datos</h2>
                    </div>
                    <div class="section-content">
                        <p>De acuerdo con la legislación aplicable, tienes los siguientes derechos sobre tus datos personales:</p>
                        
                        <ul>
                            <li><strong>Derecho de Acceso</strong></li>
                            <li><strong>Derecho de Rectificación</strong></li>
                            <li><strong>Derecho de Supresión</strong></li>
                            <li><strong>Derecho a la Limitación del Tratamiento</strong></li>
                            <li><strong>Derecho de Portabilidad</strong></li>
                            <li><strong>Derecho de Oposición</strong></li>
                            <li><strong>Derecho a Retirar el Consentimiento</strong></li>
                        </ul>
                        
                        <p>Para ejercer cualquiera de estos derechos, puedes contactarnos a través de los medios proporcionados en la sección de Contacto.</p>
                    </div>
                </section>

                <!-- Section 7: Menores de Edad -->
                <section id="menores" class="privacy-section">
                    <div class="section-header">
                        <h2>Menores de Edad</h2>
                    </div>
                    <div class="section-content">
                        <p>Nuestro Servicio no está dirigido a menores de 14 años. No recopilamos a sabiendas información de identificación personal de menores de 14 años.</p>
                        
                        <p>Si tenemos conocimiento de que hemos recopilado Datos Personales de menores sin la verificación del consentimiento de los padres, tomaremos medidas para eliminar esa información.</p>
                    </div>
                </section>

                <!-- Section 8: Cambios en la Política -->
                <section id="cambios" class="privacy-section">
                    <div class="section-header">
                        <h2>Cambios en esta Política de Privacidad</h2>
                    </div>
                    <div class="section-content">
                        <p>Podemos actualizar nuestra Política de Privacidad periódicamente. Te notificaremos cualquier cambio publicando la nueva Política de Privacidad en esta página.</p>
                        
                        <p>Te recomendamos que revises periódicamente esta Política de Privacidad para cualquier cambio.</p>
                    </div>
                </section>

                <!-- Section 9: Contacto -->
                <section id="contacto" class="privacy-section">
                    <div class="section-header">
                        <h2>Contacto</h2>
                    </div>
                    <div class="section-content">
                        <p>Si tienes alguna pregunta sobre esta Política de Privacidad, por favor contáctanos:</p>
                        
                        <div class="contact-section">
                            <h3>📞 Información de Contacto</h3>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <span>📧</span>
                                    <span>Email: privacidad@techsport.com</span>
                                </div>
                                <div class="contact-item">
                                    <span>📍</span>
                                    <span>Dirección: [Tu dirección aquí]</span>
                                </div>
                            </div>
                            <p style="margin-top: 20px; color: var(--text-light);">Horario de atención: Lunes a Viernes de 9:00 a 18:00</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
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
                <p><a href="/TechSport/Páginas/Publica/Principal/politica_cookies.php" class="footer-link">Política de
                        Cookies</a></p>
                <p><a href="/TechSport/Páginas/Publica/Principal/politica_privacidad.php" class="footer-link">Política de
                        Privacidad</a></p>
                
            </div>
        </div>
    </footer>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scroll for TOC links
            const tocLinks = document.querySelectorAll('.toc-nav a');
            
            tocLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        // Remove active class from all links
                        tocLinks.forEach(l => l.classList.remove('active'));
                        // Add active class to clicked link
                        this.classList.add('active');
                        
                        // Smooth scroll to target
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Highlight active section on scroll
            const sections = document.querySelectorAll('.privacy-section');
            
            function highlightActiveSection() {
                let currentSection = '';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    
                    if (window.pageYOffset >= sectionTop - 150) {
                        currentSection = section.getAttribute('id');
                    }
                });
                
                // Update TOC links
                tocLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + currentSection) {
                        link.classList.add('active');
                    }
                });
            }
            
            window.addEventListener('scroll', highlightActiveSection);
            highlightActiveSection(); // Initial call
        });
    </script>
</body>
</html>