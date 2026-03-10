<?php
// CORREGIR PROBLEMA DE ZONA HORARIA Y CARÁCTER "s"
ob_start(); // Iniciar buffer de salida
session_start();

// Configurar zona horaria de España
date_default_timezone_set('Europe/Madrid');

// Verificar que el usuario esté logueado y sea Entrenador (1) o Profesional (3)
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    ob_end_clean(); // Limpiar buffer
    header("Location: /TechSport/LogicaPHP/login.php");
    exit();
}

$rol_id = $_SESSION['rol_id'];
if ($rol_id != 1 && $rol_id != 3) { // Solo Entrenador y Profesional
    ob_end_clean(); // Limpiar buffer
    header("Location: /TechSport/Páginas/Privadas/" . ($rol_id == 2 ? "Jugador" : "Entrenador") . "/inicio.php");
    exit();
}

// Obtener datos del usuario
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];
$tipo_usuario = $_SESSION['tipo_usuario'] ?? ($rol_id == 1 ? 'entrenador' : 'profesional');

// Incluir conexión a base de datos
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';

// IMPORTANTE: Incluir las funciones corregidas de calendario.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/Funciones/calendario.php';

// Obtener equipo del usuario usando la función CORREGIDA de calendario.php
$equipo = obtenerEquipoUsuario($conn, $usuario_id, $rol_id);

// Para diagnóstico
$info_entrenador = obtenerInfoEntrenadorDetallada($conn, $usuario_id);

// Obtener temporada del equipo
$temporada_id = $equipo ? $equipo['temporada_id'] : null;

// Función para verificar si el usuario pertenece a un equipo (modificada)
function perteneceAEquipo($conn, $usuario_id, $rol_id)
{
    if ($rol_id == 1) { // Entrenador
        // 1. Verificar si es entrenador principal
        $stmt = $conn->prepare("SELECT id FROM equipos WHERE entrenador_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->store_result();
        $es_principal = $stmt->num_rows > 0;
        $stmt->close();

        if ($es_principal) {
            return true;
        }

        // 2. Verificar si es entrenador auxiliar
        // Primero obtener el ID de la tabla entrenadores
        $stmt = $conn->prepare("SELECT id FROM entrenadores WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $entrenador = $result->fetch_assoc();
            $entrenador_id = $entrenador['id'];
            $stmt->close();

            // Buscar en equipo_entrenadores
            $stmt = $conn->prepare("
                SELECT ee.id 
                FROM equipo_entrenadores ee 
                WHERE ee.entrenador_id = ? 
                AND ee.activo = TRUE
            ");
            $stmt->bind_param("i", $entrenador_id);
            $stmt->execute();
            $stmt->store_result();
            $es_auxiliar = $stmt->num_rows > 0;
            $stmt->close();

            return $es_auxiliar;
        }
        $stmt->close();

        return false;
    } elseif ($rol_id == 3) { // Profesional
        // SOLUCIÓN: Verificar directamente por usuario_id en equipo_profesionales
        // Porque equipo_profesionales.profesional_id = usuarios.id

        $stmt = $conn->prepare("
        SELECT ep.id 
        FROM equipo_profesionales ep 
        WHERE ep.profesional_id = ?  -- ¡Este es el usuario_id!
    ");
        $stmt->bind_param("i", $usuario_id);  // Usar $usuario_id directamente
        $stmt->execute();
        $stmt->store_result();
        $pertenece = $stmt->num_rows > 0;
        $stmt->close();
        return $pertenece;
    }
    return false;
}

// Función para verificar si el usuario puede editar eventos
function puedeEditarEventos($rol_id)
{
    // Todos los entrenadores y profesionales pueden editar
    return $rol_id == 1 || $rol_id == 3;
}

// Variables para mensajes
$mensaje = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if (!$equipo) {
        $error = "Debes pertenecer a un equipo para gestionar eventos.";
    } elseif (!$temporada_id) {
        $error = "Tu equipo no tiene una temporada asignada. Contacta con el administrador.";
    } elseif (!puedeEditarEventos($rol_id)) {
        $error = "No tienes permiso para editar eventos.";
    } else {
        switch ($accion) {
            case 'crear_evento':
                $titulo = trim($_POST['titulo'] ?? '');
                $fecha = $_POST['fecha'] ?? '';
                $hora_inicio = $_POST['hora_inicio'] ?? '';
                $tipo = $_POST['tipo'] ?? '';
                $descripcion = trim($_POST['descripcion'] ?? '');

                if (empty($titulo) || empty($fecha) || empty($hora_inicio) || empty($tipo)) {
                    $error = "Todos los campos marcados con * son obligatorios.";
                } else {
                    // CORREGIR PROBLEMA DE FECHA - Crear DateTime object con zona horaria
                    try {
                        // Crear fecha y hora en formato correcto
                        $fecha_hora_str = $fecha . ' ' . $hora_inicio . ':00';
                        $dateTime = new DateTime($fecha_hora_str, new DateTimeZone('Europe/Madrid'));
                        $fecha_completa = $dateTime->format('Y-m-d H:i:s');

                        $stmt = $conn->prepare("INSERT INTO eventos (equipo_id, temporada_id, titulo, descripcion, fecha, tipo) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iissss", $equipo['id'], $temporada_id, $titulo, $descripcion, $fecha_completa, $tipo);

                        if ($stmt->execute()) {
                            $mensaje = "✅ Evento creado exitosamente.";
                        } else {
                            $error = "Error al crear evento: " . $conn->error;
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        $error = "Error en formato de fecha/hora: " . $e->getMessage();
                    }
                }
                break;

            case 'editar_evento':
                $evento_id = intval($_POST['evento_id'] ?? 0);
                $titulo = trim($_POST['titulo'] ?? '');
                $fecha = $_POST['fecha'] ?? '';
                $hora_inicio = $_POST['hora_inicio'] ?? '';
                $tipo = $_POST['tipo'] ?? '';
                $descripcion = trim($_POST['descripcion'] ?? '');

                if ($evento_id <= 0) {
                    $error = "ID de evento inválido.";
                } elseif (empty($titulo) || empty($fecha) || empty($hora_inicio) || empty($tipo)) {
                    $error = "Todos los campos marcados con * son obligatorios.";
                } else {
                    // Verificar que el evento pertenece al equipo del usuario
                    $stmt = $conn->prepare("SELECT id FROM eventos WHERE id = ? AND equipo_id = ?");
                    $stmt->bind_param("ii", $evento_id, $equipo['id']);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows === 0) {
                        $error = "No tienes permiso para editar este evento.";
                    } else {
                        // CORREGIR PROBLEMA DE FECHA
                        try {
                            $fecha_hora_str = $fecha . ' ' . $hora_inicio . ':00';
                            $dateTime = new DateTime($fecha_hora_str, new DateTimeZone('Europe/Madrid'));
                            $fecha_completa = $dateTime->format('Y-m-d H:i:s');

                            $stmt = $conn->prepare("UPDATE eventos SET titulo = ?, descripcion = ?, fecha = ?, tipo = ? WHERE id = ?");
                            $stmt->bind_param("ssssi", $titulo, $descripcion, $fecha_completa, $tipo, $evento_id);

                            if ($stmt->execute()) {
                                $mensaje = "✅ Evento actualizado exitosamente.";
                            } else {
                                $error = "Error al actualizar evento: " . $conn->error;
                            }
                        } catch (Exception $e) {
                            $error = "Error en formato de fecha/hora: " . $e->getMessage();
                        }
                    }
                    $stmt->close();
                }
                break;

            case 'eliminar_evento':
                $evento_id = intval($_POST['evento_id'] ?? 0);

                if ($evento_id <= 0) {
                    $error = "ID de evento inválido.";
                } else {
                    // Verificar que el evento pertenece al equipo del usuario
                    $stmt = $conn->prepare("SELECT id FROM eventos WHERE id = ? AND equipo_id = ?");
                    $stmt->bind_param("ii", $evento_id, $equipo['id']);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows === 0) {
                        $error = "No tienes permiso para eliminar este evento.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM eventos WHERE id = ?");
                        $stmt->bind_param("i", $evento_id);

                        if ($stmt->execute()) {
                            $mensaje = "🗑️ Evento eliminado exitosamente.";
                        } else {
                            $error = "Error al eliminar evento: " . $conn->error;
                        }
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// Verificar si pertenece a un equipo
$pertenece_equipo = perteneceAEquipo($conn, $usuario_id, $rol_id);

// Obtener eventos si hay equipo y temporada - USANDO LA FUNCIÓN DE calendario.php
$eventos = ($equipo && $temporada_id) ? obtenerEventosEquipo($conn, $equipo['id'], $temporada_id) : [];

$conn->close();

// Limpiar buffer y enviar salida
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TechSport - Editar Calendario</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Compartidos/calendario-editor.css" />
</head>

<body data-rol="<?php echo $tipo_usuario; ?>">
    <!-- HEADER DINÁMICO según tipo de usuario -->
    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" id="logo-interactivo" />
        </div>
        <nav class="navbar">
            <ul class="nav-menu">
                <!-- Enlaces dinámicos según tipo de usuario -->
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/<?php echo ucfirst($tipo_usuario); ?>/inicio.php">Inicio</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Estadisticas.php">Estadísticas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipo Rivales</a></li>
                <?php if ($tipo_usuario == 'entrenador'): ?>
                    <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/CrearEncuestas.php">Crear Encuesta</a></li>
                    <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/Convocatoria.php">Convocatoria</a></li>
                <?php endif; ?>
                <?php if ($tipo_usuario == 'profesional'): ?>
                    <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Convocatoria.php">Convocatoria</a></li>
                <?php endif; ?>
                <li><a class="nav-link" href="/TechSport/LogicaPHP/logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <!-- Cabecera de página -->
        <div class="page-header">
            <div class="header-left">
                <h1>📅 Editor de Calendario</h1>
                <p class="subtitle">Gestiona los eventos de tu equipo</p>
            </div>
        </div>

        <!-- Mensajes -->
        <div class="messages">
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($equipo && !$temporada_id): ?>
                <div class="alert alert-warning">
                    ⚠️ Tu equipo no tiene una temporada asignada. Contacta con el administrador.
                </div>
            <?php endif; ?>

            <?php if (!$pertenece_equipo): ?>
                <div class="alert alert-warning">
                    <h3>⚠️ No tienes acceso al calendario</h3>
                    <p>Para gestionar eventos necesitas:</p>
                    <ul style="text-align: left; margin: 10px 0 10px 20px;">
                        <li>Ser entrenador principal o auxiliar de un equipo</li>
                        <li>O ser profesional asignado a un equipo</li>
                    </ul>

                    <?php if ($rol_id == 1 && !empty($info_entrenador)): ?>
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px;">
                            <p><strong>Tu situación actual:</strong></p>
                            <ul style="text-align: left; margin: 5px 0 5px 20px;">
                                <?php if (isset($info_entrenador['equipo_nombre'])): ?>
                                    <li>Equipo encontrado: <strong><?php echo htmlspecialchars($info_entrenador['equipo_nombre']); ?></strong></li>
                                <?php endif; ?>
                                <?php if (isset($info_entrenador['tipo_entrenador'])): ?>
                                    <li>Tipo: <strong><?php echo $info_entrenador['tipo_entrenador'] == 'principal' ? 'Entrenador Principal' : 'Entrenador Auxiliar'; ?></strong></li>
                                <?php endif; ?>
                                <?php if (isset($info_entrenador['titulo_entrenador'])): ?>
                                    <li>Título: <?php echo htmlspecialchars($info_entrenador['titulo_entrenador']); ?></li>
                                <?php endif; ?>
                            </ul>
                            <p>Contacta con el administrador si crees que deberías tener acceso.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$pertenece_equipo || ($equipo && !$temporada_id)): ?>
            <!-- Sin acceso al calendario -->
            <div class="no-team">
                <h3>🔒 Acceso restringido</h3>
                <p>Para acceder al calendario y gestionar eventos, necesitas:</p>
                <ul style="text-align: left; max-width: 400px; margin: 15px auto; color: #718096;">
                    <?php if (!$pertenece_equipo): ?><li>✅ Pertenecer a un equipo como entrenador o profesional</li><?php endif; ?>
                    <?php if ($equipo && !$temporada_id): ?><li>✅ Que tu equipo tenga una temporada asignada</li><?php endif; ?>
                </ul>
                <?php if ($tipo_usuario == 'entrenador'): ?>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <a href="/TechSport/Páginas/Privadas/Entrenador/GestionEquipo.php" class="btn btn-primary">
                            Crear nuevo equipo
                        </a>
                        <a href="/TechSport/Páginas/Privadas/Entrenador/inicio.php" class="btn btn-secondary">
                            Volver al inicio
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Contenido principal cuando tiene equipo y temporada -->
            <div class="main-content">
                <!-- Sidebar - Formulario y leyenda -->
                <div class="sidebar">
                    <!-- Formulario para nuevo evento -->
                    <div class="event-form">
                        <h3 class="section-title">➕ Nuevo Evento</h3>
                        <form id="formNuevoEvento" method="POST" action="">
                            <input type="hidden" name="accion" value="crear_evento">

                            <div class="form-group required">
                                <label for="titulo">Título del evento</label>
                                <input type="text" id="titulo" name="titulo" class="form-control"
                                    placeholder="Ej: Entrenamiento técnico" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group required">
                                    <label for="fecha">Fecha</label>
                                    <input type="date" id="fecha" name="fecha" class="form-control"
                                        value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="form-group required">
                                    <label for="hora_inicio">Hora inicio</label>
                                    <input type="time" id="hora_inicio" name="hora_inicio"
                                        class="form-control" value="18:00" required>
                                </div>
                            </div>

                            <div class="form-group required">
                                <label for="tipo">Tipo</label>
                                <select id="tipo" name="tipo" class="form-control" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="reunion">Reunión</option>
                                    <option value="entrenamiento">Entrenamiento</option>
                                    <option value="partido">Partido</option>
                                    <option value="gimnasio">Gimnasio</option>
                                    <option value="fisio">Fisioterapia</option>
                                    <option value="nutricionista">Nutrición</option>
                                    <option value="psicologo">Psicología</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Descripción (opcional)</label>
                                <textarea id="descripcion" name="descripcion" class="form-control"
                                    rows="3" placeholder="Descripción detallada del evento..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px; background: #1a2a6c;">
                                💾 Guardar Evento
                            </button>
                        </form>
                    </div>

                    <!-- Información del usuario -->
                    <div class="user-info">
                        <h4 class="legend-title">👤 Tu información</h4>
                        <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($nombre_usuario); ?></p>
                            <p><strong>Rol:</strong>
                                <?php
                                if (isset($equipo['tipo_entrenador'])) {
                                    echo $equipo['tipo_entrenador'] == 'principal' ? 'Entrenador Principal' : 'Entrenador Auxiliar';
                                } else {
                                    echo ucfirst($tipo_usuario);
                                }
                                ?>
                            </p>
                            <p><strong>Equipo:</strong> <?php echo htmlspecialchars($equipo['nombre']); ?></p>
                            <p><strong>Permisos:</strong> ✅ Puede editar calendario</p>
                        </div>
                    </div>

                    <!-- Leyenda de colores -->
                    <div class="legend">
                        <h4 class="legend-title">🎨 Leyenda de Eventos</h4>
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
                </div>

                <!-- Calendario y eventos -->
                <div class="calendar-container">
                    <!-- Navegación del calendario -->
                    <div class="calendar-header">
                        <h2 class="calendar-title" id="calendarTitle">
                            <?php
                            setlocale(LC_TIME, 'es_ES.UTF-8');
                            echo strftime('%B %Y');
                            ?>
                        </h2>
                        <div class="calendar-nav">
                            <button class="btn btn-secondary" id="prevMonth">◀ Mes anterior</button>
                            <button class="btn btn-secondary" id="today">Hoy</button>
                            <button class="btn btn-secondary" id="nextMonth">Mes siguiente ▶</button>
                        </div>
                    </div>

                    <!-- Calendario -->
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

                    <!-- Lista de eventos del mes -->
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
                                $eventos_mes = array_filter($eventos, function ($evento) use ($mes_actual) {
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
                                                <span style="margin-left: 10px; color: #718096; font-size: 0.9rem;">
                                                    <?php echo date('d/m/Y', strtotime($evento['fecha'])); ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($evento['descripcion'])): ?>
                                                <div class="event-description">
                                                    <?php echo nl2br(htmlspecialchars($evento['descripcion'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="event-actions">
                                                <button class="btn btn-icon btn-secondary"
                                                    onclick="editarEvento(<?php echo $evento['id']; ?>)">
                                                    ✏️ Editar
                                                </button>
                                                <button class="btn btn-icon btn-danger"
                                                    onclick="eliminarEvento(<?php echo $evento['id']; ?>, '<?php echo addslashes($evento['titulo']); ?>')">
                                                    🗑️ Eliminar
                                                </button>
                                            </div>
                                        </div>
                            <?php endforeach;
                                endif;
                            endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para editar evento -->
    <div id="modalEditar" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Editar Evento</h3>
                <button class="close-modal" onclick="cerrarModal()">&times;</button>
            </div>
            <form id="formEditarEvento" method="POST" action="">
                <input type="hidden" name="accion" value="editar_evento">
                <input type="hidden" name="evento_id" id="eventoId">

                <div class="form-group required">
                    <label for="edit_titulo">Título del evento</label>
                    <input type="text" id="edit_titulo" name="titulo" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="edit_fecha">Fecha</label>
                        <input type="date" id="edit_fecha" name="fecha" class="form-control" required>
                    </div>

                    <div class="form-group required">
                        <label for="edit_hora_inicio">Hora inicio</label>
                        <input type="time" id="edit_hora_inicio" name="hora_inicio" class="form-control" required>
                    </div>
                </div>

                <div class="form-group required">
                    <label for="edit_tipo">Tipo</label>
                    <select id="edit_tipo" name="tipo" class="form-control" required>
                        <option value="reunion">Reunión</option>
                        <option value="entrenamiento">Entrenamiento</option>
                        <option value="partido">Partido</option>
                        <option value="gimnasio">Gimnasio</option>
                        <option value="fisio">Fisioterapia</option>
                        <option value="nutricionista">Nutrición</option>
                        <option value="psicologo">Psicología</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_descripcion">Descripción (opcional)</label>
                    <textarea id="edit_descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">💾 Actualizar Evento</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

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
            <div class="footer-copyright">
                <p>© 2025 TechSport.</p>
            </div>
        </div>
    </footer>

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
            const opciones = {
                month: 'long',
                year: 'numeric'
            };
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
                        const hora = new Date(evento.fecha).toLocaleTimeString('es-ES', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        miniEvent.title = `${evento.titulo} (${hora})`;
                        miniEvent.textContent = evento.titulo.substring(0, 15) + (evento.titulo.length > 15 ? '...' : '');
                        eventosContainer.appendChild(miniEvent);
                    });

                    diaCell.appendChild(eventosContainer);

                    // Hacer clic para mostrar opciones
                    diaCell.addEventListener('click', function(e) {
                        if (e.target.classList.contains('mini-event')) return;
                        mostrarOpcionesDia(dia, fecha.getMonth(), fecha.getFullYear(), eventosDia);
                    });
                } else {
                    // Permitir crear evento en día vacío - CORREGIDO
                    diaCell.addEventListener('click', function() {
                        // CORRECCIÓN: Usar formato manual en lugar de toISOString()
                        const año = fecha.getFullYear();
                        const mes = fecha.getMonth() + 1; // JavaScript: 0-11, necesitamos 1-12
                        const diaNum = parseInt(dayNumber.textContent);

                        // Formato manual: YYYY-MM-DD
                        const mesFormateado = mes.toString().padStart(2, '0');
                        const diaFormateado = diaNum.toString().padStart(2, '0');
                        const fechaISO = `${año}-${mesFormateado}-${diaFormateado}`;

                        console.log(`Día clickeado: ${diaNum}/${mes}/${año}`);
                        console.log(`Fecha para formulario: ${fechaISO}`);

                        // Asignar al campo de fecha
                        document.getElementById('fecha').value = fechaISO;

                        // Enfocar el título
                        document.getElementById('titulo').focus();

                        // Desplazar al formulario
                        document.getElementById('titulo').scrollIntoView({
                            behavior: 'smooth'
                        });
                    });
                }

                calendarGrid.appendChild(diaCell);
            }
        }

        // Mostrar opciones para un día con eventos
        function mostrarOpcionesDia(dia, mes, año, eventosDia) {
            const fecha = new Date(año, mes, dia);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            let mensaje = `📅 ${fechaFormateada}\n\n`;

            if (eventosDia.length === 0) {
                mensaje += 'No hay eventos programados para este día.\n\n';
                mensaje += '¿Quieres crear un nuevo evento?';

                if (confirm(mensaje)) {
                    // CORRECCIÓN: Usar formato manual
                    const mesFormateado = (mes + 1).toString().padStart(2, '0');
                    const diaFormateado = dia.toString().padStart(2, '0');
                    const fechaISO = `${año}-${mesFormateado}-${diaFormateado}`;

                    document.getElementById('fecha').value = fechaISO;
                    document.getElementById('titulo').focus();
                    document.getElementById('titulo').scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            } else {
                mensaje += `Eventos programados (${eventosDia.length}):\n\n`;

                eventosDia.forEach((evento, index) => {
                    const hora = new Date(evento.fecha).toLocaleTimeString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    mensaje += `${index + 1}. ${hora}\n`;
                    mensaje += `   ${evento.titulo}\n`;
                    mensaje += `   Tipo: ${obtenerNombreTipo(evento.tipo)}\n\n`;
                });

                mensaje += '¿Qué quieres hacer?\n';
                mensaje += '1. Ver detalles de un evento\n';
                mensaje += '2. Crear nuevo evento\n';
                mensaje += '3. Cancelar';

                const opcion = prompt(mensaje);

                switch (opcion) {
                    case '1':
                        const numEvento = prompt(`¿Qué evento quieres ver? (1-${eventosDia.length}):`);
                        const eventoIndex = parseInt(numEvento) - 1;
                        if (eventoIndex >= 0 && eventoIndex < eventosDia.length) {
                            mostrarDetallesEvento(eventosDia[eventoIndex]);
                        }
                        break;
                    case '2':
                        // CORRECCIÓN: Usar formato manual
                        const mesFormateado = (mes + 1).toString().padStart(2, '0');
                        const diaFormateado = dia.toString().padStart(2, '0');
                        const fechaISO = `${año}-${mesFormateado}-${diaFormateado}`;

                        document.getElementById('fecha').value = fechaISO;
                        document.getElementById('titulo').focus();
                        document.getElementById('titulo').scrollIntoView({
                            behavior: 'smooth'
                        });
                        break;
                }
            }
        }

        // Mostrar detalles del evento
        function mostrarDetallesEvento(evento) {
            const fecha = new Date(evento.fecha);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const hora = fecha.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });

            let mensaje = `📋 DETALLES DEL EVENTO\n\n`;
            mensaje += `📝 Título: ${evento.titulo}\n`;
            mensaje += `📅 Fecha: ${fechaFormateada}\n`;
            mensaje += `🕒 Hora: ${hora} (1h de duración)\n`;
            mensaje += `🏷️ Tipo: ${obtenerNombreTipo(evento.tipo)}\n`;
            mensaje += `🆔 ID: ${evento.id}\n`;

            if (evento.descripcion) {
                mensaje += `\n📄 Descripción:\n${evento.descripcion}\n`;
            }

            const opcion = prompt(mensaje + `\n¿Qué quieres hacer?\n1. Editar este evento\n2. Eliminar este evento\n3. Volver`);

            switch (opcion) {
                case '1':
                    editarEvento(evento.id);
                    break;
                case '2':
                    eliminarEvento(evento.id, evento.titulo);
                    break;
            }
        }

        // Editar evento
        function editarEvento(eventoId) {
            // Buscar el evento
            const evento = eventos.find(e => e.id == eventoId);

            if (!evento) {
                alert('❌ Evento no encontrado');
                return;
            }

            // Llenar formulario modal
            document.getElementById('eventoId').value = evento.id;
            document.getElementById('edit_titulo').value = evento.titulo;

            // Extraer fecha y hora del DATETIME - CORREGIDO
            const fechaObj = new Date(evento.fecha);

            // CORRECCIÓN: Usar toLocaleDateString para obtener fecha local
            const año = fechaObj.getFullYear();
            const mes = fechaObj.getMonth() + 1;
            const dia = fechaObj.getDate();
            const mesFormateado = mes.toString().padStart(2, '0');
            const diaFormateado = dia.toString().padStart(2, '0');
            const fechaISO = `${año}-${mesFormateado}-${diaFormateado}`;

            // Hora
            const hora = fechaObj.toTimeString().substring(0, 5);

            document.getElementById('edit_fecha').value = fechaISO;
            document.getElementById('edit_hora_inicio').value = hora;
            document.getElementById('edit_tipo').value = evento.tipo;
            document.getElementById('edit_descripcion').value = evento.descripcion || '';

            // Mostrar modal
            document.getElementById('modalEditar').style.display = 'flex';
        }

        // Eliminar evento
        function eliminarEvento(eventoId, titulo) {
            if (!confirm(`¿Estás seguro de que quieres eliminar el evento?\n\n"${titulo}"\n\nEsta acción no se puede deshacer.`)) {
                return;
            }

            // Crear formulario para eliminar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const inputAccion = document.createElement('input');
            inputAccion.type = 'hidden';
            inputAccion.name = 'accion';
            inputAccion.value = 'eliminar_evento';

            const inputEventoId = document.createElement('input');
            inputEventoId.type = 'hidden';
            inputEventoId.name = 'evento_id';
            inputEventoId.value = eventoId;

            form.appendChild(inputAccion);
            form.appendChild(inputEventoId);
            document.body.appendChild(form);
            form.submit();
        }

        // Cerrar modal
        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
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

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalEditar');
            if (event.target === modal) {
                cerrarModal();
            }
        };
    </script>
</body>

</html>