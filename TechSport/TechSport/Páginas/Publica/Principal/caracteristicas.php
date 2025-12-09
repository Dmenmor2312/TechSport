<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Características | TechSport - Gestión de Fútbol Profesional</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Inicio/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ESTILOS ESPECÍFICOS PARA ESTA PÁGINA */
        :root {
            --primary-color: #1e40af;
            --secondary-color: #2563eb;
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --football-green: #166534;
            --grass-green: #22c55e;
            --dark-color: #1f2937;
            --text-color: #374151;
            --light-gray: #f3f4f6;
            --field-color: #22c55e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: linear-gradient(to bottom, #f0f9ff, #ffffff);
            background-attachment: fixed;
        }

        main {
            padding-top: 120px;
        }

        /* HERO SECTION CON TEMÁTICA DE FÚTBOL */
        .features-hero {
            background: linear-gradient(rgba(30, 64, 175, 0.9), rgba(22, 101, 52, 0.9)),
                url('https://images.unsplash.com/photo-1575361204480-aadea25e6e68?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 5% 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
        }

        .features-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(34, 197, 94, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(30, 64, 175, 0.2) 0%, transparent 50%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 1000px;
            margin: 0 auto;
        }

        .features-hero h1 {
            font-size: 3.2rem;
            margin-bottom: 20px;
            font-weight: 800;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            background: linear-gradient(to right, #ffffff, #dbeafe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .features-hero p {
            font-size: 1.4rem;
            max-width: 800px;
            margin: 0 auto 40px;
            opacity: 0.95;
            font-weight: 300;
            line-height: 1.8;
        }

        .football-icon {
            font-size: 4rem;
            margin-bottom: 30px;
            color: white;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 16px 35px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--grass-green), var(--football-green));
            color: white;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-3px);
        }

        /* FEATURES CONTAINER */
        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 5% 40px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 70px;
            position: relative;
        }

        .section-header h2 {
            color: var(--dark-color);
            font-size: 2.5rem;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
        }

        .section-header h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--grass-green));
            border-radius: 3px;
        }

        .section-header p {
            color: var(--text-color);
            max-width: 700px;
            margin: 0 auto;
            font-size: 1.2rem;
            line-height: 1.7;
        }

        /* FEATURE SECTIONS */
        .feature-section {
            margin-bottom: 100px;
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .feature-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color) 0%, transparent 70%);
            border-radius: 0 0 0 100%;
            opacity: 0.1;
        }

        .feature-section:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .feature-section h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
        }

        .feature-section h2 i {
            background: linear-gradient(135deg, var(--primary-color), var(--grass-green));
            padding: 15px;
            border-radius: 12px;
            color: white;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.2);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 35px;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 35px 30px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .feature-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--grass-green));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            border-color: transparent;
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.1);
        }

        .feature-card:hover::after {
            transform: scaleX(1);
        }

        .feature-icon {
            background: linear-gradient(135deg, #dbeafe, #bbf7d0);
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            color: var(--primary-color);
            font-size: 1.8rem;
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.1);
        }

        .feature-card h3 {
            color: var(--dark-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .feature-card p {
            color: var(--text-color);
            line-height: 1.7;
            margin-bottom: 25px;
            flex-grow: 1;
            font-size: 1.05rem;
        }

        .feature-list {
            list-style: none;
            margin-top: 20px;
        }

        .feature-list li {
            margin-bottom: 12px;
            padding-left: 30px;
            position: relative;
            color: var(--text-color);
            line-height: 1.6;
        }

        .feature-list li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--grass-green);
            font-weight: bold;
            font-size: 1.2rem;
            background: #dcfce7;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ROLES SECTION */
        .roles-section {
            background: linear-gradient(135deg, #f0f9ff, #dcfce7);
            padding: 80px 5%;
            border-radius: 25px;
            margin: 80px auto;
            max-width: 1200px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .roles-section::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(34, 197, 94, 0.1) 0%, transparent 70%);
        }

        .roles-section::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(30, 64, 175, 0.1) 0%, transparent 70%);
        }

        .roles-container {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            justify-content: center;
            margin-top: 60px;
            position: relative;
            z-index: 1;
        }

        .role-card {
            background: white;
            border-radius: 20px;
            padding: 45px 35px;
            text-align: center;
            width: 100%;
            max-width: 350px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .role-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
        }

        .role-card.entrenador::before {
            background: linear-gradient(to right, var(--primary-color), #3b82f6);
        }

        .role-card.jugador::before {
            background: linear-gradient(to right, var(--grass-green), #22c55e);
        }

        .role-card.profesional::before {
            background: linear-gradient(to right, #8b5cf6, #a78bfa);
        }

        .role-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .role-icon {
            font-size: 3.5rem;
            margin-bottom: 25px;
            display: inline-block;
            background: linear-gradient(135deg, currentColor, transparent 70%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .role-card.entrenador .role-icon {
            color: var(--primary-color);
        }

        .role-card.jugador .role-icon {
            color: var(--grass-green);
        }

        .role-card.profesional .role-icon {
            color: #8b5cf6;
        }

        .role-card h3 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: var(--dark-color);
            font-weight: 700;
        }

        .role-card p {
            color: var(--text-color);
            margin-bottom: 25px;
            line-height: 1.7;
        }

        .role-card ul {
            list-style: none;
            text-align: left;
            margin-top: 25px;
        }

        .role-card ul li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
            line-height: 1.6;
        }

        .role-card ul li:before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* DEMO SECTION */
        .demo-section {
            text-align: center;
            padding: 80px 5%;
            background: white;
            border-radius: 25px;
            margin: 80px auto;
            max-width: 1100px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            border: 2px solid #e5e7eb;
        }

        .demo-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(to right, var(--primary-color), var(--grass-green), var(--primary-color));
        }

        .demo-section h2 {
            color: var(--dark-color);
            font-size: 2.5rem;
            margin-bottom: 25px;
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
        }

        .demo-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--grass-green));
            border-radius: 2px;
        }

        .demo-section p {
            color: var(--text-color);
            max-width: 800px;
            margin: 0 auto 50px;
            font-size: 1.2rem;
            line-height: 1.8;
        }

        .demo-image {
            max-width: 100%;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            margin: 30px 0;
            border: 3px solid white;
            transition: transform 0.3s ease;
        }

        .demo-image:hover {
            transform: scale(1.02);
        }

        .image-caption {
            color: #6b7280;
            font-style: italic;
            margin-top: 15px;
            font-size: 0.95rem;
        }

        /* FOOTER ENHANCEMENT */
        .footer {
            margin-top: 100px;
            background: var(--dark-color);
            color: white;
            padding: 60px 5% 30px;
            clip-path: polygon(0 10%, 100% 0, 100% 100%, 0 100%);
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 992px) {
            .features-hero h1 {
                font-size: 2.5rem;
            }

            .features-hero p {
                font-size: 1.2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                max-width: 350px;
                justify-content: center;
            }

            .feature-section {
                padding: 40px 25px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .features-hero {
                padding: 80px 5% 60px;
                clip-path: polygon(0 0, 100% 0, 100% 95%, 0 100%);
            }

            .features-hero h1 {
                font-size: 2.2rem;
            }

            .features-hero p {
                font-size: 1.1rem;
            }

            .section-header h2 {
                font-size: 2rem;
            }

            .feature-section h2 {
                font-size: 1.7rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .feature-section h2 i {
                width: 55px;
                height: 55px;
                font-size: 1.5rem;
            }

            .roles-container {
                flex-direction: column;
                align-items: center;
                gap: 30px;
            }

            .demo-section h2 {
                font-size: 2rem;
            }
        }

        /* ANIMATIONS */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-card,
        .role-card,
        .demo-section {
            animation: fadeInUp 0.7s ease forwards;
            opacity: 0;
        }

        .feature-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .feature-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        /* SCROLL ANIMATION */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" alt="TechSport">
        </div>

        <nav class="navbar">
            <ul class="nav-menu">
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/inicio.html">Inicio</a></li>
                <li><a class="nav-link active" href="/TechSport/Páginas/Publica/Principal/caracteristicas.php">Características</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/Principal/contacto.php">Contacto</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Publica/EleccionUsuario/eleccion.html">Iniciar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Sección Hero -->
        <section class="features-hero">
            <div class="hero-content">
                <div class="football-icon">
                    <i class="fas fa-futbol"></i>
                </div>
                <h1>TechSport: La Revolución en Gestión de Fútbol</h1>
                <p>La plataforma definitiva para la gestión profesional de equipos de fútbol. Diseñada específicamente para entrenadores, jugadores y profesionales del deporte que buscan maximizar el rendimiento y la organización.</p>
                <div class="cta-buttons">
                    <a href="/TechSport/Páginas/Publica/EleccionUsuario/eleccion.html" class="btn-primary">
                        <i class="fas fa-play"></i> Comenzar ahora
                    </a>
                    <a href="#roles" class="btn-secondary">
                        <i class="fas fa-users"></i> Ver roles disponibles
                    </a>
                </div>
            </div>
        </section>

        <!-- Contenedor de características -->
        <div class="features-container">
            <!-- Gestión de Estadísticas -->
            <section class="feature-section reveal" id="estadisticas">
                <h2><i class="fas fa-chart-line"></i> Estadísticas Avanzadas de Fútbol</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-running"></i>
                        </div>
                        <h3>Estadísticas Físicas</h3>
                        <p>Monitorización detallada del rendimiento físico de cada jugador con métricas específicas para fútbol.</p>
                        <ul class="feature-list">
                            <li>Velocidad promedio</li>
                            <li>Fuerza</li>
                            <li>Resistencia</li>
                            <li>Peso y altura</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-futbol"></i>
                        </div>
                        <h3>Estadísticas de Partido</h3>
                        <p>Registro completo de todas las métricas relevantes durante los partidos oficiales y amistosos.</p>
                        <ul class="feature-list">
                            <li>Goles y asistencias</li>
                            <li>Porterías a cero para porteros/defensas</li>
                            <li>Tarjetas amarillas y rojas</li>
                            <li>Minutos jugados por posición</li>
                            <li>Estados: Titular, Suplente, No Convocado</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3>Análisis de Rendimiento</h3>
                        <p>Herramientas avanzadas para analizar y mejorar el rendimiento individual y colectivo.</p>
                        <ul class="feature-list">
                            <li>Comparativas por temporada</li>
                            <li>Evolución del rendimiento</li>
                            <li>Análisis por posición específica</li>
                            <li>Estadísticas vs equipos rivales</li>
                            <li>Reportes personalizados por jugador</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Calendario y Eventos -->
            <section class="feature-section reveal" id="calendario">
                <h2><i class="fas fa-calendar-alt"></i> Calendario y Eventos</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <h3>Calendario de Eventos</h3>
                        <p>Gestión completa de todos los eventos relacionados con el equipo de fútbol.</p>
                        <ul class="feature-list">
                            <li>Partidos oficiales y amistosos</li>
                            <li>Entrenamientos regulares</li>
                            <li>Sesiones de recuperación</li>
                            <li>Reuniones</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h3>Editor de Calendario</h3>
                        <p>Herramientas exclusivas para entrenadores y profesionales para gestionar el calendario.</p>
                        <ul class="feature-list">
                            <li>Creación y modificación de eventos</li>
                            <li>Asignación de ubicaciones y horarios</li>
                            <li>Notificaciones automáticas a jugadores</li>
                            <li>Visualización por mes</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Recordatorios y Alertas</h3>
                        <p>Sistema de notificaciones para mantener a todos informados sobre próximos eventos.</p>
                        <ul class="feature-list">
                            <li>Alertas de partidos próximos</li>
                            <li>Recordatorios de entrenamientos</li>
                            <li>Notificaciones de cambios de horario</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Gestión del Equipo -->
            <section class="feature-section reveal" id="gestion">
                <h2><i class="fas fa-users-cog"></i> Gestión Completa del Equipo</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3>Panel de Entrenador</h3>
                        <p>Control total sobre todos los aspectos del equipo por parte del entrenador.</p>
                        <ul class="feature-list">
                            <li>Gestión completa de plantilla</li>
                            <li>Asignación de números y posiciones</li>
                            <li>Control de estadísticas individuales</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>Convocatorias</h3>
                        <p>Sistema profesional para gestionar convocatorias a partidos y entrenamientos.</p>
                        <ul class="feature-list">
                            <li>Creación rápida de convocatorias</li>
                            <li>Selección de jugadores por posición</li>
                            <li>Notificación automática a convocados</li>
                            <li>Historial de convocatorias pasadas</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-poll"></i>
                        </div>
                        <h3>Encuestas de Asistencia</h3>
                        <p>Sistema interactivo para gestionar la asistencia a entrenamientos y eventos.</p>
                        <ul class="feature-list">
                            <li>Creación de encuestas por entrenador</li>
                            <li>Respuestas en tiempo real</li>
                            <li>Visualización de asistencia confirmada</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Equipos Rivales -->
            <section class="feature-section reveal" id="rivales">
                <h2><i class="fas fa-user-friends"></i> Análisis de Equipos Rivales</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Información de Rivales</h3>
                        <p>Base de datos completa con información detallada de todos los equipos rivales.</p>
                        <ul class="feature-list">
                            <li>Plantillas completas de rivales</li>
                            <li>Posiciones y números de jugadores</li>
                            <li>Estadísticas individuales de rivales</li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sección de Roles -->
        <section class="roles-section reveal" id="roles">
            <div class="section-header">
                <h2>Roles en la Plataforma</h2>
                <p>Cada perfil tiene acceso a herramientas específicas diseñadas para optimizar su trabajo en el equipo de fútbol</p>
            </div>

            <div class="roles-container">
                <div class="role-card entrenador">
                    <div class="role-icon">
                        <i class="fas fa-whistle"></i>
                    </div>
                    <h3>Entrenador</h3>
                    <p>Control total sobre la gestión del equipo y acceso completo a todas las herramientas.</p>
                    <ul>
                        <li>Gestión completa de plantilla</li>
                        <li>Creación de convocatorias</li>
                        <li>Editor de calendario y eventos</li>
                        <li>Registro de estadísticas</li>
                        <li>Creación de encuestas</li>
                        <li>Análisis de equipos rivales</li>
                    </ul>
                </div>

                <div class="role-card jugador">
                    <div class="role-icon">
                        <i class="fas fa-running"></i>
                    </div>
                    <h3>Jugador</h3>
                    <p>Acceso a información personalizada y herramientas para mejorar el rendimiento.</p>
                    <ul>
                        <li>Consulta de estadísticas propias</li>
                        <li>Visualización de calendario</li>
                        <li>Confirmación de asistencia</li>
                        <li>Historial de partidos</li>
                        <li>Datos físicos personales</li>
                        <li>Estadísticas vs rivales</li>
                    </ul>
                </div>

                <div class="role-card profesional">
                    <div class="role-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3>Profesional</h3>
                    <p>Herramientas especializadas para preparadores físicos y médicos.</p>
                    <ul>
                        <li>Acceso a datos físicos</li>
                        <li>Editor de calendario</li>
                        <li>Análisis de rendimiento</li>
                        <li>Seguimiento de recuperación</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Sección Demostración -->
        <section class="demo-section reveal" id="demo">
            <div class="section-header">
                <h2>Vista Previa de la Plataforma</h2>
                <p>Descubre cómo TechSport transforma la gestión de tu equipo de fútbol con una interfaz intuitiva y herramientas poderosas</p>
            </div>

            <div class="demo-content">
                <img src="https://images.unsplash.com/photo-1552667466-07770ae110d0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80"
                    alt="Dashboard de TechSport" class="demo-image">
                <p class="image-caption">Panel de control principal con estadísticas en tiempo real y calendario integrado</p>

                <div class="cta-buttons" style="margin-top: 40px;">
                    <a href="/TechSport/Páginas/Publica/EleccionUsuario/eleccion.html" class="btn-primary">
                        <i class="fas fa-user-plus"></i> Crear cuenta
                    </a>
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
            </div>
        </div>
    </footer>

    <script>
        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const revealElements = document.querySelectorAll('.reveal');

            const revealOnScroll = () => {
                revealElements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const windowHeight = window.innerHeight;

                    if (elementTop < windowHeight - 100) {
                        element.classList.add('active');
                    }
                });
            };

            window.addEventListener('scroll', revealOnScroll);
            revealOnScroll(); // Initial check

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();

                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;

                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>