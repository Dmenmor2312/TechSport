<?php
session_start();

// Verificar que el usuario esté logueado y sea Jugador (2)
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    header("Location: /TechSport/LogicaPHP/login.php");
    exit();
}

$rol_id = $_SESSION['rol_id'];
if ($rol_id != 2) { // Solo Jugador
    header("Location: /TechSport/Páginas/Privadas/" . ($rol_id == 1 ? "Entrenador" : "Profesional") . "/inicio.php");
    exit();
}

// Obtener datos del usuario
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];
$tipo_usuario = 'jugador';

// Incluir conexión y funciones
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/Funciones/calendario.php';

// Obtener equipo y eventos
$equipo = obtenerEquipoUsuario($conn, $usuario_id, $rol_id);

// Para diagnóstico adicional si no hay equipo
if (!$equipo) {
    // Verificar si el usuario existe como jugador
    $stmt = $conn->prepare("SELECT id FROM jugadores WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $stmt->store_result();
    $es_jugador = $stmt->num_rows > 0;
    $stmt->close();
    
    // Si es jugador, verificar si está en equipo_jugadores
    if ($es_jugador) {
        $stmt = $conn->prepare("
            SELECT j.id as jugador_id, ej.equipo_id, ej.activo, e.nombre as equipo_nombre,
                   e.temporada_id, t.nombre as temporada_nombre
            FROM jugadores j
            LEFT JOIN equipo_jugadores ej ON j.id = ej.jugador_id
            LEFT JOIN equipos e ON ej.equipo_id = e.id
            LEFT JOIN temporadas t ON e.temporada_id = t.id
            WHERE j.usuario_id = ?
        ");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $info_jugador = $result->fetch_assoc();
        $stmt->close();
        
        if ($info_jugador) {
            if (!$info_jugador['equipo_id']) {
                $mensaje_error = "Eres un jugador registrado pero no estás asignado a ningún equipo.";
            } elseif (!$info_jugador['activo']) {
                $mensaje_error = "Estás asignado al equipo '" . $info_jugador['equipo_nombre'] . "' pero tu estado no está activo.";
            } else {
                // Tiene equipo y está activo, pero no aparece en la función principal
                $mensaje_error = "Tienes ficha activa en el equipo '" . $info_jugador['equipo_nombre'] . 
                                "' (Temporada: " . ($info_jugador['temporada_nombre'] ?: 'Sin temporada') . 
                                "). Contacta con el administrador para revisar la configuración.";
            }
        }
    }
}

$temporada_id = $equipo ? $equipo['temporada_id'] : null;
$eventos = ($equipo && $temporada_id) ? obtenerEventosEquipo($conn, $equipo['id'], $temporada_id) : [];
$eventos_proximos = ($equipo && $temporada_id) ? obtenerEventosProximos($eventos) : [];

$conn->close();
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TechSport - Inicio Jugador</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Compartidos/estilos.css" />
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Compartidos/estilos_inicio.css">
</head>

<body data-rol="jugador">
    <!-- HEADER -->
    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" id="logo-interactivo" />
        </div>
        <nav class="navbar">
            <ul class="nav-menu">
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Jugador/inicio.php">Inicio</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Jugador/Estadisticas.php">Estadísticas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Jugador/Encuestas.php">Encuestas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipos Rivales</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Convocatoria.php">Convocatoria</a></li>
                <li><a class="nav-link" href="/TechSport/LogicaPHP/logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Sección de bienvenida -->
        <div class="welcome-section">
            <h1>👋 ¡Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?>!</h1>
            <p>Panel de control del jugador</p>
        </div>

        <?php if (!$equipo): ?>
            <!-- Mensaje cuando no tiene equipo -->
            <div class="alert alert-warning">
                <h3>⚠️ Sin equipo asignado</h3>
                <p><?php echo isset($mensaje_error) ? $mensaje_error : "No estás asignado a ningún equipo en este momento."; ?></p>
                
                <?php if (isset($info_jugador) && $info_jugador['equipo_id'] && !$info_jugador['activo']): ?>
                    <p><strong>Tu situación:</strong> Tienes ficha en el equipo <strong><?php echo htmlspecialchars($info_jugador['equipo_nombre']); ?></strong> pero tu estado está marcado como INACTIVO.</p>
                    <p>📞 Contacta con tu entrenador para que active tu ficha en el equipo.</p>
                <?php elseif (isset($es_jugador) && $es_jugador && !isset($info_jugador['equipo_id'])): ?>
                    <p><strong>Tu situación:</strong> Tienes ficha de jugador pero no estás asignado a ningún equipo.</p>
                    <p>📞 Contacta con un entrenador para que te añada a su equipo.</p>
                <?php elseif (!isset($es_jugador) || !$es_jugador): ?>
                    <p><strong>Tu situación:</strong> No tienes ficha de jugador creada.</p>
                    <p>📞 Contacta con un entrenador para que cree tu ficha de jugador y te asigne a un equipo.</p>
                <?php endif; ?>
                
                <div style="margin-top: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #6c757d;">
                    <h4>📋 ¿Qué necesitas para acceder al calendario?</h4>
                    <ol style="text-align: left; margin: 15px 0 0 30px;">
                        <li>Tener ficha de jugador creada por un entrenador</li>
                        <li>Ser asignado a un equipo activo</li>
                        <li>Tener estado ACTIVO en el equipo</li>
                    </ol>
                </div>
            </div>
        <?php else: ?>
            <!-- Información del equipo -->
            <div class="team-info-card">
                <h2>🏆 Tu Equipo: <?php echo htmlspecialchars($equipo['nombre']); ?></h2>
                <p>🔢 Temporada ID: <?php echo htmlspecialchars($equipo['temporada_id']); ?></p>
                <p>📊 Eventos programados: <?php echo count($eventos); ?></p>
                <p>✅ Estado: <strong>ACTIVO</strong></p>
            </div>

            <!-- Calendario y eventos -->
            <div class="calendar-section">
                <!-- Leyenda -->
                <div class="legend-sidebar">
                    <h3 class="section-title">🎨 Leyenda de Eventos</h3>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="color-sample" style="background: #bee3f8;"></div>
                            <span>Reunión</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-sample" style="background: #c6f6d5;"></div>
                            <span>Entrenamiento</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-sample" style="background: #fed7d7;"></div>
                            <span>Partido</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-sample" style="background: #e9d8fd;"></div>
                            <span>Gimnasio</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-sample" style="background: #fefcbf;"></div>
                            <span>Fisioterapia</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-sample" style="background: #fed7e2;"></div>
                            <span>Nutrición</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-sample" style="background: #c6f6d5;"></div>
                            <span>Psicología</span>
                        </div>
                    </div>
                </div>

                <!-- Calendario -->
                <div class="calendar-container">
                    <div class="calendar-header">
                        <h2 class="calendar-title" id="calendarTitle">
                            <?php 
                            setlocale(LC_TIME, 'es_ES.UTF-8');
                            echo strftime('%B %Y');
                            ?>
                        </h2>
                        <div class="calendar-nav">
                            <button class="btn-action btn-secondary" id="prevMonth">◀</button>
                            <button class="btn-action btn-secondary" id="today">Hoy</button>
                            <button class="btn-action btn-secondary" id="nextMonth">▶</button>
                        </div>
                    </div>
                    
                    <div class="calendar-grid" id="calendarGrid">
                        <!-- Días de la semana -->
                        <div class="day-header">Lun</div>
                        <div class="day-header">Mar</div>
                        <div class="day-header">Mié</div>
                        <div class="day-header">Jue</div>
                        <div class="day-header">Vie</div>
                        <div class="day-header">Sáb</div>
                        <div class="day-header">Dom</div>
                        
                        <!-- Los días se generarán con JavaScript -->
                    </div>

                    <!-- Eventos del mes -->
                    <div class="events-list">
                        <h3>📋 Eventos del mes</h3>
                        <div id="monthEvents">
                            <?php if (empty($eventos)): ?>
                                <div class="no-events">
                                    No hay eventos programados para este mes.
                                </div>
                            <?php else: 
                                // Filtrar eventos del mes actual
                                $mes_actual = date('Y-m');
                                $eventos_mes = array_filter($eventos, function($evento) use ($mes_actual) {
                                    return substr($evento['fecha_solo'], 0, 7) === $mes_actual;
                                });
                                
                                if (empty($eventos_mes)): ?>
                                    <div class="no-events">
                                        No hay eventos programados para este mes.
                                    </div>
                                <?php else: 
                                    foreach ($eventos_mes as $evento): ?>
                                    <div class="event-item" style="border-left-color: <?php echo $evento['color']; ?>;">
                                        <div class="event-header">
                                            <div class="event-title"><?php echo htmlspecialchars($evento['titulo']); ?></div>
                                            <div class="event-time">
                                                <?php echo date('H:i', strtotime($evento['fecha'])); ?> 
                                                <small>(1h de duración)</small>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="event-type-badge">
                                                <?php echo obtenerNombreTipo($evento['tipo']); ?>
                                            </span>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?php echo date('d/m/Y', strtotime($evento['fecha'])); ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($evento['descripcion'])): ?>
                                        <div class="event-description">
                                            <?php echo nl2br(htmlspecialchars($evento['descripcion'])); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach;
                                endif;
                            endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notificaciones -->
            <div class="notifications-card">
                <h3>🔔 Eventos próximos (7 días)</h3>
                <ul class="notifications-list" id="lista-notificaciones">
                    <?php if (empty($eventos_proximos)): ?>
                        <li class="no-notifications">No hay eventos programados para los próximos 7 días.</li>
                    <?php else: 
                        foreach ($eventos_proximos as $evento): 
                            $fecha = new DateTime($evento['fecha']);
                            $hoy = new DateTime();
                            $diferencia = $hoy->diff($fecha);
                            $dias = $diferencia->days;
                            
                            if ($dias == 0) {
                                $texto_dias = 'Hoy';
                            } elseif ($dias == 1) {
                                $texto_dias = 'Mañana';
                            } else {
                                $texto_dias = "En $dias días";
                            }
                        ?>
                        <li class="notification-item">
                            <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                            <span class="notification-date">
                                📅 <?php echo $texto_dias; ?> - 
                                <?php echo date('d/m/Y H:i', strtotime($evento['fecha'])); ?> - 
                                <?php echo obtenerNombreTipo($evento['tipo']); ?>
                            </span>
                        </li>
                        <?php endforeach;
                    endif; ?>
                </ul>
            </div>
        <?php endif; ?>
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
            </div>
        </div>
    </footer>

    <?php if ($equipo): ?>
    <script>
        // Variables globales
        let fechaActual = new Date();
        let eventos = <?php echo json_encode($eventos); ?>;
        
        // Función para obtener nombre del tipo de evento
        function obtenerNombreTipo(tipo) {
            const nombres = {
                'reunion': 'Reunión',
                'entrenamiento': 'Entrenamiento',
                'partido': 'Partido',
                'gimnasio': 'Gimnasio',
                'fisio': 'Fisioterapia',
                'nutricionista': 'Nutrición',
                'psicologo': 'Psicología'
            };
            return nombres[tipo] || tipo;
        }
        
        // Función para obtener color por tipo
        function obtenerColorPorTipo(tipo) {
            const colores = {
                'reunion': '#bee3f8',
                'entrenamiento': '#c6f6d5',
                'partido': '#fed7d7',
                'gimnasio': '#e9d8fd',
                'fisio': '#fefcbf',
                'nutricionista': '#fed7e2',
                'psicologo': '#c6f6d5'
            };
            return colores[tipo] || '#e2e8f0';
        }
        
        // Función para formatear fecha en español
        function formatearFechaES(fecha) {
            const opciones = { month: 'long', year: 'numeric' };
            return fecha.toLocaleDateString('es-ES', opciones);
        }
        
        // Generar calendario
        function generarCalendario(fecha) {
            const calendarGrid = document.getElementById('calendarGrid');
            const calendarTitle = document.getElementById('calendarTitle');
            
            // Actualizar título
            calendarTitle.textContent = formatearFechaES(fecha);
            
            // Obtener primer y último día del mes
            const primerDia = new Date(fecha.getFullYear(), fecha.getMonth(), 1);
            const ultimoDia = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0);
            
            // Obtener día de la semana del primer día (0=Domingo, 1=Lunes...)
            let primerDiaSemana = primerDia.getDay();
            primerDiaSemana = primerDiaSemana === 0 ? 6 : primerDiaSemana - 1;
            
            // Limpiar calendario (mantener encabezados)
            const diasActuales = calendarGrid.querySelectorAll('.day-cell');
            diasActuales.forEach(dia => dia.remove());
            
            // Añadir días vacíos al inicio
            for (let i = 0; i < primerDiaSemana; i++) {
                const diaVacio = document.createElement('div');
                diaVacio.className = 'day-cell';
                calendarGrid.appendChild(diaVacio);
            }
            
            // Añadir días del mes
            const totalDias = ultimoDia.getDate();
            const hoy = new Date();
            
            for (let dia = 1; dia <= totalDias; dia++) {
                const diaCell = document.createElement('div');
                diaCell.className = 'day-cell';
                
                // Verificar si es hoy
                const fechaCelda = new Date(fecha.getFullYear(), fecha.getMonth(), dia);
                if (fechaCelda.toDateString() === hoy.toDateString()) {
                    diaCell.classList.add('today');
                }
                
                // Añadir número del día
                const dayNumber = document.createElement('div');
                dayNumber.className = 'day-number';
                dayNumber.textContent = dia;
                diaCell.appendChild(dayNumber);
                
                // Verificar si hay eventos en este día
                const eventosDia = eventos.filter(evento => {
                    const fechaEvento = new Date(evento.fecha);
                    return fechaEvento.getDate() === dia &&
                           fechaEvento.getMonth() === fecha.getMonth() &&
                           fechaEvento.getFullYear() === fecha.getFullYear();
                });
                
                if (eventosDia.length > 0) {
                    diaCell.classList.add('has-events');
                    
                    const eventosContainer = document.createElement('div');
                    eventosContainer.className = 'day-events';
                    
                    // Mostrar mini eventos
                    eventosDia.forEach(evento => {
                        const miniEvent = document.createElement('div');
                        miniEvent.className = 'mini-event';
                        miniEvent.style.backgroundColor = evento.color;
                        const hora = new Date(evento.fecha).toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'});
                        miniEvent.title = `${evento.titulo} (${hora}) - ${obtenerNombreTipo(evento.tipo)}`;
                        miniEvent.textContent = evento.titulo.substring(0, 10) + (evento.titulo.length > 10 ? '...' : '');
                        eventosContainer.appendChild(miniEvent);
                    });
                    
                    diaCell.appendChild(eventosContainer);
                }
                
                calendarGrid.appendChild(diaCell);
            }
        }
        
        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            generarCalendario(fechaActual);
            
            // Configurar navegación del calendario
            document.getElementById('prevMonth').addEventListener('click', () => {
                fechaActual.setMonth(fechaActual.getMonth() - 1);
                generarCalendario(fechaActual);
            });
            
            document.getElementById('nextMonth').addEventListener('click', () => {
                fechaActual.setMonth(fechaActual.getMonth() + 1);
                generarCalendario(fechaActual);
            });
            
            document.getElementById('today').addEventListener('click', () => {
                fechaActual = new Date();
                generarCalendario(fechaActual);
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>