<?php
ob_start(); // Iniciar buffer de salida
session_start();

// Verificar que el usuario esté logueado y sea Entrenador (1)
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    ob_end_clean(); // Limpiar buffer
    header("Location: /TechSport/LogicaPHP/login.php");
    exit();
}

$rol_id = $_SESSION['rol_id'];
if ($rol_id != 1) { // Solo Entrenador
    ob_end_clean(); // Limpiar buffer
    header("Location: /TechSport/Páginas/Privadas/" . ($rol_id == 2 ? "Jugador" : "Profesional") . "/inicio.php");
    exit();
}

// Obtener datos del usuario
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];

// Incluir conexión
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/Funciones/calendario.php';

// Obtener información detallada del entrenador
$info_entrenador = obtenerInfoEntrenadorDetallada($conn, $usuario_id);

if (!$info_entrenador) {
    $conn->close();
    ob_end_clean(); // Limpiar buffer
    echo "<script>alert('No tienes un equipo asignado.'); window.location.href = '/TechSport/Páginas/Privadas/Entrenador/inicio.php';</script>";
    exit();
}

// Variables para mensajes y acción actual
$mensaje = '';
$error = '';
$accion = $_GET['accion'] ?? 'listar';
$convocatoria_id = intval($_GET['id'] ?? 0);

// CONSTANTES - MOVIDO ARRIBA DEL HTML
define('MAX_JUGADORES_CONVOCATORIA', 18);

// FUNCIONES AUXILIARES PARA CONVOCATORIAS
function obtenerJugadoresEquipo($conn, $equipo_id)
{
    $stmt = $conn->prepare("
        SELECT j.id as jugador_id, u.nombre, j.posicion, j.dorsal,
               ej.activo
        FROM jugadores j 
        JOIN usuarios u ON j.usuario_id = u.id 
        JOIN equipo_jugadores ej ON j.id = ej.jugador_id
        WHERE ej.equipo_id = ? 
        AND ej.activo = TRUE
        ORDER BY u.nombre
    ");
    $stmt->bind_param("i", $equipo_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function obtenerConvocatoriasEquipo($conn, $equipo_id)
{
    $stmt = $conn->prepare("
        SELECT c.*, ev.titulo as partido_titulo, ev.fecha as partido_fecha,
               COUNT(cj.jugador_id) as total_convocados,
               t.nombre as temporada_nombre
        FROM convocatorias c
        JOIN eventos ev ON c.evento_id = ev.id
        JOIN equipos e ON c.equipo_id = e.id
        JOIN temporadas t ON c.temporada_id = t.id
        LEFT JOIN convocados cj ON c.id = cj.convocatoria_id
        WHERE c.equipo_id = ?
        GROUP BY c.id
        ORDER BY ev.fecha DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $equipo_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function obtenerConvocatoriaCompleta($conn, $convocatoria_id, $equipo_id)
{
    // Obtener convocatoria básica
    $stmt = $conn->prepare("
        SELECT c.*, ev.titulo as partido_titulo, ev.fecha as partido_fecha,
               ev.descripcion as partido_descripcion,
               t.nombre as temporada_nombre,
               e.nombre as equipo_nombre
        FROM convocatorias c
        JOIN eventos ev ON c.evento_id = ev.id
        JOIN temporadas t ON c.temporada_id = t.id
        JOIN equipos e ON c.equipo_id = e.id
        WHERE c.id = ? AND c.equipo_id = ?
    ");
    $stmt->bind_param("ii", $convocatoria_id, $equipo_id);
    $stmt->execute();
    $convocatoria = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$convocatoria) return null;

    // Obtener jugadores convocados con información completa
    $stmt = $conn->prepare("
        SELECT cj.*, j.id as jugador_id, u.nombre, 
               j.posicion, j.dorsal as dorsal_habitual,
               ej.activo
        FROM convocados cj
        JOIN jugadores j ON cj.jugador_id = j.id
        JOIN usuarios u ON j.usuario_id = u.id
        JOIN equipo_jugadores ej ON j.id = ej.jugador_id AND ej.equipo_id = ?
        WHERE cj.convocatoria_id = ?
        ORDER BY u.nombre
    ");
    $stmt->bind_param("ii", $equipo_id, $convocatoria_id);
    $stmt->execute();
    $convocatoria['jugadores'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $convocatoria;
}

function contarJugadoresConvocados($conn, $convocatoria_id)
{
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM convocados WHERE convocatoria_id = ?");
    $stmt->bind_param("i", $convocatoria_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['total'] ?? 0;
}

function verificarFechaLimite($fecha_partido, $tipo = 'editar')
{
    $fecha_partido = new DateTime($fecha_partido);
    $ahora = new DateTime();

    if ($tipo == 'editar') {
        // Para editar: 10 horas antes del partido
        $limite = clone $fecha_partido;
        $limite->modify('-10 hours');
        return $ahora < $limite;
    } else if ($tipo == 'estadisticas') {
        // Para estadísticas: 
        // - MÍNIMO: 10 horas después del partido (no antes)
        // - MÁXIMO: 2 días después del partido
        $limite_minimo = clone $fecha_partido;
        $limite_minimo->modify('+10 hours');
        
        $limite_maximo = clone $fecha_partido;
        $limite_maximo->modify('+2 days');
        
        // Debe haber pasado al menos 10 horas y no más de 2 días
        return ($ahora >= $limite_minimo && $ahora <= $limite_maximo);
    }
    
    return false;
}

// Verificar si el entrenador puede gestionar convocatorias completas
$puede_gestionar_completo = puedeGestionarEquipoCompleto($conn, $usuario_id, $info_entrenador['equipo_id']);

// PROCESAR FORMULARIOS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear_convocatoria':
                if (!$puede_gestionar_completo) {
                    $error = "Solo el entrenador principal puede crear convocatorias.";
                    break;
                }

                $evento_id = intval($_POST['evento_id'] ?? 0);
                $notas = trim($_POST['notas'] ?? '');

                if ($evento_id <= 0) {
                    $error = "Selecciona un partido válido.";
                } else {
                    // Obtener información del evento
                    $stmt = $conn->prepare("
                        SELECT ev.fecha, ev.titulo, t.id as temporada_id
                        FROM eventos ev
                        JOIN temporadas t ON ev.temporada_id = t.id
                        WHERE ev.id = ? AND ev.equipo_id = ?
                    ");
                    $stmt->bind_param("ii", $evento_id, $info_entrenador['equipo_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $evento = $result->fetch_assoc();
                    $stmt->close();

                    if (!$evento) {
                        $error = "Evento no encontrado o no pertenece a tu equipo.";
                    } else {
                        // Calcular fecha límite (2 días antes del partido)
                        $fecha_partido = new DateTime($evento['fecha']);
                        $fecha_limite = clone $fecha_partido;
                        $fecha_limite->modify('-2 days');
                        $fecha_limite->setTime(14, 0, 0); // 2:00 PM del día -2

                        // Verificar si ya pasó la fecha límite
                        $ahora = new DateTime();
                        if ($ahora > $fecha_limite) {
                            $error = "La fecha límite para crear la convocatoria ya pasó. Debe crearse al menos 2 días antes del partido.";
                        } else {
                            // Verificar si ya existe convocatoria
                            $stmt = $conn->prepare("SELECT id FROM convocatorias WHERE evento_id = ?");
                            $stmt->bind_param("i", $evento_id);
                            $stmt->execute();
                            $stmt->store_result();

                            if ($stmt->num_rows > 0) {
                                $error = "Ya existe una convocatoria para este partido.";
                            } else {
                                // Crear convocatoria
                                $stmt = $conn->prepare("
                                    INSERT INTO convocatorias 
                                    (equipo_id, temporada_id, evento_id, fecha_limite, estado, notas) 
                                    VALUES (?, ?, ?, ?, 'pendiente', ?)
                                ");
                                $stmt->bind_param(
                                    "iiiss",
                                    $info_entrenador['equipo_id'],
                                    $evento['temporada_id'],
                                    $evento_id,
                                    $fecha_limite->format('Y-m-d H:i:s'),
                                    $notas
                                );

                                if ($stmt->execute()) {
                                    $convocatoria_id = $stmt->insert_id;
                                    $mensaje = "✅ Convocatoria creada correctamente.";
                                    $accion = 'editar';
                                } else {
                                    $error = "Error al crear convocatoria: " . $conn->error;
                                }
                            }
                            $stmt->close();
                        }
                    }
                }
                break;

            case 'añadir_jugador':
                if (!$puede_gestionar_completo) {
                    $error = "Solo el entrenador principal puede añadir jugadores a la convocatoria.";
                    break;
                }

                $convocatoria_id = intval($_POST['convocatoria_id']);
                $jugador_id = intval($_POST['jugador_id']);
                $dorsal = intval($_POST['dorsal'] ?? 0);
                $posicion = trim($_POST['posicion'] ?? '');

                // Verificar límite de jugadores (máximo 18)
                $total_convocados = contarJugadoresConvocados($conn, $convocatoria_id);
                if ($total_convocados >= MAX_JUGADORES_CONVOCATORIA) {
                    $error = "No se pueden añadir más jugadores. Máximo " . MAX_JUGADORES_CONVOCATORIA . " jugadores por convocatoria.";
                    break;
                }

                // Verificar límite de tiempo para editar
                $stmt = $conn->prepare("
                    SELECT ev.fecha 
                    FROM convocatorias c
                    JOIN eventos ev ON c.evento_id = ev.id
                    WHERE c.id = ? AND c.equipo_id = ?
                ");
                $stmt->bind_param("ii", $convocatoria_id, $info_entrenador['equipo_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $convocatoria_data = $result->fetch_assoc();
                $stmt->close();

                if (!$convocatoria_data) {
                    $error = "Convocatoria no encontrada o no pertenece a tu equipo.";
                } elseif (!verificarFechaLimite($convocatoria_data['fecha'], 'editar')) {
                    $error = "Ya pasó el límite para añadir jugadores (10 horas antes del partido).";
                } else {
                    // Verificar que el jugador pertenece al equipo
                    $stmt = $conn->prepare("
                        SELECT 1 FROM equipo_jugadores 
                        WHERE jugador_id = ? AND equipo_id = ? AND activo = TRUE
                    ");
                    $stmt->bind_param("ii", $jugador_id, $info_entrenador['equipo_id']);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows == 0) {
                        $error = "El jugador no pertenece a tu equipo activo.";
                    } else {
                        // Añadir jugador a la convocatoria
                        $stmt = $conn->prepare("
                            INSERT INTO convocados (convocatoria_id, jugador_id, dorsal, posicion)
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                            dorsal = VALUES(dorsal),
                            posicion = VALUES(posicion)
                        ");
                        $stmt->bind_param("iiis", $convocatoria_id, $jugador_id, $dorsal, $posicion);

                        if ($stmt->execute()) {
                            $mensaje = "✅ Jugador añadido a la convocatoria.";
                        } else {
                            $error = "Error al añadir jugador: " . $conn->error;
                        }
                    }
                    $stmt->close();
                }
                break;

            case 'eliminar_jugador':
                if (!$puede_gestionar_completo) {
                    $error = "Solo el entrenador principal puede eliminar jugadores de la convocatoria.";
                    break;
                }

                $convocatoria_id = intval($_POST['convocatoria_id']);
                $jugador_id = intval($_POST['jugador_id']);

                // Verificar límite de tiempo
                $stmt = $conn->prepare("
                    SELECT ev.fecha 
                    FROM convocatorias c
                    JOIN eventos ev ON c.evento_id = ev.id
                    WHERE c.id = ? AND c.equipo_id = ?
                ");
                $stmt->bind_param("ii", $convocatoria_id, $info_entrenador['equipo_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $convocatoria_data = $result->fetch_assoc();
                $stmt->close();

                if (!$convocatoria_data) {
                    $error = "Convocatoria no encontrada o no pertenece a tu equipo.";
                } elseif (!verificarFechaLimite($convocatoria_data['fecha'], 'editar')) {
                    $error = "Ya pasó el límite para modificar jugadores (10 horas antes del partido).";
                } else {
                    // Eliminar jugador
                    $stmt = $conn->prepare("DELETE FROM convocados WHERE convocatoria_id = ? AND jugador_id = ?");
                    $stmt->bind_param("ii", $convocatoria_id, $jugador_id);

                    if ($stmt->execute()) {
                        $mensaje = "✅ Jugador eliminado de la convocatoria.";
                    } else {
                        $error = "Error al eliminar jugador: " . $conn->error;
                    }
                    $stmt->close();
                }
                break;

            case 'publicar_convocatoria':
                if (!$puede_gestionar_completo) {
                    $error = "Solo el entrenador principal puede publicar convocatorias.";
                    break;
                }

                $convocatoria_id = intval($_POST['convocatoria_id']);

                // Verificar que hay al menos algún jugador convocado
                $total_convocados = contarJugadoresConvocados($conn, $convocatoria_id);
                if ($total_convocados == 0) {
                    $error = "No puedes publicar una convocatoria sin jugadores.";
                    break;
                }

                $stmt = $conn->prepare("
                    UPDATE convocatorias 
                    SET estado = 'publicada', updated_at = NOW()
                    WHERE id = ? AND equipo_id = ?
                ");
                $stmt->bind_param("ii", $convocatoria_id, $info_entrenador['equipo_id']);

                if ($stmt->execute()) {
                    $mensaje = "✅ Convocatoria publicada. Los jugadores ya pueden verla.";
                } else {
                    $error = "Error al publicar convocatoria: " . $conn->error;
                }
                $stmt->close();
                break;

            case 'guardar_estadisticas':
                $convocatoria_id = intval($_POST['convocatoria_id']);
                $jugador_id = intval($_POST['jugador_id']);
                $tipo = $_POST['tipo'] ?? 'suplente';

                // Verificar que se pueden editar estadísticas (10 horas después hasta 2 días después)
                $stmt = $conn->prepare("
                    SELECT ev.fecha 
                    FROM convocatorias c
                    JOIN eventos ev ON c.evento_id = ev.id
                    WHERE c.id = ? AND c.equipo_id = ?
                ");
                $stmt->bind_param("ii", $convocatoria_id, $info_entrenador['equipo_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $convocatoria_data = $result->fetch_assoc();
                $stmt->close();

                if (!$convocatoria_data) {
                    $error = "Convocatoria no encontrada.";
                } elseif (!verificarFechaLimite($convocatoria_data['fecha'], 'estadisticas')) {
                    // Obtener fechas exactas para el mensaje de error
                    $fecha_partido = new DateTime($convocatoria_data['fecha']);
                    $fecha_minima = clone $fecha_partido;
                    $fecha_minima->modify('+10 hours');
                    $fecha_maxima = clone $fecha_partido;
                    $fecha_maxima->modify('+2 days');
                    $ahora = new DateTime();
                    
                    if ($ahora < $fecha_minima) {
                        $error = "No puedes editar estadísticas aún. Podrás hacerlo desde " . $fecha_minima->format('d/m/Y H:i') . " (10 horas después del partido).";
                    } else {
                        $error = "Ya pasó el plazo para editar estadísticas (hasta " . $fecha_maxima->format('d/m/Y H:i') . ").";
                    }
                } else {
                    // Actualizar estadísticas
                    $stmt = $conn->prepare("
                        INSERT INTO estadisticas_partido 
                        (jugador_id, equipo_id, temporada_id, partidos_jugados, goles, asistencias, amarillas, rojas, porterias_cero, minutos_jugados)
                        VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        partidos_jugados = partidos_jugados + 1,
                        goles = goles + VALUES(goles),
                        asistencias = asistencias + VALUES(asistencias),
                        amarillas = amarillas + VALUES(amarillas),
                        rojas = rojas + VALUES(rojas),
                        porterias_cero = porterias_cero + VALUES(porterias_cero),
                        minutos_jugados = minutos_jugados + VALUES(minutos_jugados),
                        fecha_actualizacion = NOW()
                    ");

                    $goles = intval($_POST['goles'] ?? 0);
                    $asistencias = intval($_POST['asistencias'] ?? 0);
                    $amarillas = intval($_POST['amarillas'] ?? 0);
                    $rojas = intval($_POST['rojas'] ?? 0);
                    $porterias_cero = intval($_POST['porterias_cero'] ?? 0);
                    $minutos_jugados = intval($_POST['minutos_jugados'] ?? 0);

                    // Necesitamos obtener la temporada_id
                    $stmt_temp = $conn->prepare("
                        SELECT temporada_id FROM convocatorias WHERE id = ?
                    ");
                    $stmt_temp->bind_param("i", $convocatoria_id);
                    $stmt_temp->execute();
                    $temp_result = $stmt_temp->get_result();
                    $temp_data = $temp_result->fetch_assoc();
                    $temporada_id = $temp_data['temporada_id'] ?? $info_entrenador['temporada_id'];
                    $stmt_temp->close();

                    $stmt->bind_param(
                        "iiiiiiiii",
                        $jugador_id,
                        $info_entrenador['equipo_id'],
                        $temporada_id,
                        $goles,
                        $asistencias,
                        $amarillas,
                        $rojas,
                        $porterias_cero,
                        $minutos_jugados
                    );

                    if ($stmt->execute()) {
                        // Actualizar el tipo en la tabla convocados para las estadísticas
                        $stmt_tipo = $conn->prepare("
                            UPDATE convocados 
                            SET tipo = ?
                            WHERE convocatoria_id = ? AND jugador_id = ?
                        ");
                        $stmt_tipo->bind_param("sii", $tipo, $convocatoria_id, $jugador_id);
                        $stmt_tipo->execute();
                        $stmt_tipo->close();
                        
                        $mensaje = "✅ Estadísticas actualizadas correctamente.";
                    } else {
                        $error = "Error al guardar estadísticas: " . $conn->error;
                    }
                    $stmt->close();
                }
                break;

            case 'eliminar_convocatoria':
                if (!$puede_gestionar_completo) {
                    $error = "Solo el entrenador principal puede eliminar convocatorias.";
                    break;
                }

                $convocatoria_id = intval($_POST['convocatoria_id']);

                // Verificar que la convocatoria pertenece al equipo
                $stmt = $conn->prepare("SELECT estado FROM convocatorias WHERE id = ? AND equipo_id = ?");
                $stmt->bind_param("ii", $convocatoria_id, $info_entrenador['equipo_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $convocatoria_data = $result->fetch_assoc();
                $stmt->close();

                if (!$convocatoria_data) {
                    $error = "Convocatoria no encontrada o no pertenece a tu equipo.";
                } elseif ($convocatoria_data['estado'] == 'publicada') {
                    $error = "No puedes eliminar una convocatoria que ya ha sido publicada.";
                } else {
                    // Primero eliminar los jugadores convocados
                    $stmt = $conn->prepare("DELETE FROM convocados WHERE convocatoria_id = ?");
                    $stmt->bind_param("i", $convocatoria_id);
                    $stmt->execute();
                    $stmt->close();

                    // Luego eliminar la convocatoria
                    $stmt = $conn->prepare("DELETE FROM convocatorias WHERE id = ? AND equipo_id = ?");
                    $stmt->bind_param("ii", $convocatoria_id, $info_entrenador['equipo_id']);

                    if ($stmt->execute()) {
                        $mensaje = "✅ Convocatoria eliminada correctamente.";
                        $accion = 'listar';
                        $convocatoria_id = 0;
                    } else {
                        $error = "Error al eliminar convocatoria: " . $conn->error;
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// Obtener datos según la acción
$jugadores = obtenerJugadoresEquipo($conn, $info_entrenador['equipo_id']);
$convocatorias = obtenerConvocatoriasEquipo($conn, $info_entrenador['equipo_id']);

if ($convocatoria_id > 0 && ($accion == 'editar' || $accion == 'estadisticas')) {
    $convocatoria_actual = obtenerConvocatoriaCompleta($conn, $convocatoria_id, $info_entrenador['equipo_id']);
    if (!$convocatoria_actual) {
        $error = "Convocatoria no encontrada.";
        $accion = 'listar';
    }
}

// Obtener partidos disponibles para nuevas convocatorias
$hoy = date('Y-m-d');
$limite = date('Y-m-d', strtotime('+7 days'));

$stmt = $conn->prepare("
    SELECT ev.*, t.nombre as temporada_nombre
    FROM eventos ev
    JOIN temporadas t ON ev.temporada_id = t.id
    WHERE ev.equipo_id = ?
    AND ev.tipo = 'partido'
    AND DATE(ev.fecha) BETWEEN ? AND ?
    AND ev.fecha > NOW()
    AND NOT EXISTS (
        SELECT 1 FROM convocatorias c 
        WHERE c.evento_id = ev.id
    )
    ORDER BY ev.fecha ASC
");
$stmt->bind_param("iss", $info_entrenador['equipo_id'], $hoy, $limite);
$stmt->execute();
$partidos_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Si estamos en estadísticas, calcular las fechas límite para mostrar
$fecha_minima_stats = null;
$fecha_maxima_stats = null;
$ahora = new DateTime();
if (isset($convocatoria_actual) && $accion == 'estadisticas') {
    $fecha_partido_obj = new DateTime($convocatoria_actual['partido_fecha']);
    $fecha_minima_stats = clone $fecha_partido_obj;
    $fecha_minima_stats->modify('+10 hours');
    
    $fecha_maxima_stats = clone $fecha_partido_obj;
    $fecha_maxima_stats->modify('+2 days');
}

$conn->close();

// Limpiar buffer antes de enviar HTML
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TechSport - Gestión de Convocatorias</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Compartidos/estilos.css" />
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Entrenador/convocatoria.css" />
</head>

<body data-rol="entrenador">
    <!-- HEADER -->
    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" id="logo-interactivo" />
        </div>
        <nav class="navbar">
            <ul class="nav-menu">
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/inicio.php">Inicio</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Estadisticas.php">Estadísticas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipo Rivales</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/CrearEncuestas.php">Crear Encuesta</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/Convocatoria.php">Convocatoria</a></li>
                <li><a class="nav-link" href="/TechSport/LogicaPHP/logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main class="convocatoria-container">
        <h1 class="page-title">📋 Gestión de Convocatorias</h1>

        <!-- Información del entrenador -->
        <div class="convocatoria-info" style="margin-bottom: 20px;">
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Equipo:</span>
                    <span class="info-value"><?php echo htmlspecialchars($info_entrenador['equipo_nombre'] ?? 'Sin nombre'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tipo de Entrenador:</span>
                    <span class="info-value">
                        <?php
                        echo ($info_entrenador['tipo_entrenador'] ?? 'principal') == 'principal' ? '🏆 Principal' : '👥 Auxiliar';
                        if (!$puede_gestionar_completo) {
                            echo ' <span style="color: #e53e3e; font-size: 0.9rem;">(Solo lectura)</span>';
                        }
                        ?>
                    </span>
                </div>
                <?php if (($info_entrenador['tipo_entrenador'] ?? '') == 'auxiliar' && isset($info_entrenador['nombre_entrenador_principal'])): ?>
                    <div class="info-item">
                        <span class="info-label">Entrenador Principal:</span>
                        <span class="info-value"><?php echo htmlspecialchars($info_entrenador['nombre_entrenador_principal']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pestañas de navegación -->
        <div class="nav-tabs">
            <a href="?accion=listar" class="nav-tab <?php echo $accion == 'listar' ? 'active' : ''; ?>">
                📋 Lista de Convocatorias
            </a>
            <?php if ($puede_gestionar_completo): ?>
                <a href="?accion=nueva" class="nav-tab <?php echo $accion == 'nueva' ? 'active' : ''; ?>">
                    🆕 Nueva Convocatoria
                </a>
            <?php endif; ?>
            <?php if ($convocatoria_id > 0 && $accion == 'editar'): ?>
                <a href="?accion=editar&id=<?php echo $convocatoria_id; ?>" class="nav-tab active">
                    ✏️ Editar Convocatoria
                </a>
            <?php endif; ?>
            <?php if ($convocatoria_id > 0 && $accion == 'estadisticas'): ?>
                <a href="?accion=estadisticas&id=<?php echo $convocatoria_id; ?>" class="nav-tab active">
                    📊 Estadísticas del Partido
                </a>
            <?php endif; ?>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success">
                <span>✅</span>
                <span><?php echo $mensaje; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>❌</span>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Contenido de las pestañas -->
        <div class="tab-content <?php echo $accion == 'listar' ? 'active' : ''; ?>" id="tab-listar">
            <h2>📋 Convocatorias del Equipo</h2>

            <?php if (empty($convocatorias)): ?>
                <div class="empty-state">
                    <div>📭</div>
                    <h3>No hay convocatorias</h3>
                    <p><?php echo $puede_gestionar_completo ? 'Crea tu primera convocatoria para un partido próximo.' : 'El entrenador principal aún no ha creado convocatorias.'; ?></p>
                    <?php if ($puede_gestionar_completo): ?>
                        <a href="?accion=nueva" class="btn btn-primary">Crear Convocatoria</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($convocatorias as $conv):
                        $fecha_partido = new DateTime($conv['partido_fecha']);
                        $fecha_limite = new DateTime($conv['fecha_limite']);
                        $ahora = new DateTime();
                    ?>
                        <div class="card">
                            <?php if ($puede_gestionar_completo && $conv['estado'] != 'publicada'): ?>
                                <form method="POST" style="position: absolute; top: 15px; right: 15px;">
                                    <input type="hidden" name="accion" value="eliminar_convocatoria">
                                    <input type="hidden" name="convocatoria_id" value="<?php echo $conv['id']; ?>">
                                    <button type="submit" class="delete-convocatoria-btn"
                                        onclick="return confirm('¿Estás seguro de eliminar esta convocatoria? Esta acción no se puede deshacer.')">
                                        ×
                                    </button>
                                </form>
                            <?php endif; ?>

                            <div class="card-header">
                                <h3 class="card-title"><?php echo htmlspecialchars($conv['partido_titulo']); ?></h3>
                                <span class="badge badge-<?php echo $conv['estado']; ?>">
                                    <?php echo ucfirst($conv['estado']); ?>
                                </span>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <p><strong>📅 Partido:</strong> <?php echo $fecha_partido->format('d/m/Y H:i'); ?></p>
                                <p><strong>⏰ Límite edición:</strong> <?php echo $fecha_limite->format('d/m/Y H:i'); ?></p>
                                <p><strong>👥 Jugadores:</strong> <?php echo $conv['total_convocados']; ?>/<?php echo MAX_JUGADORES_CONVOCATORIA; ?></p>
                                <p><strong>📝 Notas:</strong> <?php echo htmlspecialchars(substr($conv['notas'] ?? 'Sin notas', 0, 50)) . '...'; ?></p>
                                <?php if ($conv['temporada_nombre']): ?>
                                    <p><strong>🏆 Temporada:</strong> <?php echo htmlspecialchars($conv['temporada_nombre']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="btn-group">
                                <a href="?accion=editar&id=<?php echo $conv['id']; ?>" class="btn btn-secondary btn-small">
                                    ✏️ Editar
                                </a>
                                <a href="?accion=estadisticas&id=<?php echo $conv['id']; ?>" class="btn btn-secondary btn-small">
                                    📊 Estadísticas
                                </a>
                                <?php if ($conv['estado'] == 'pendiente' && $puede_gestionar_completo): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="accion" value="publicar_convocatoria">
                                        <input type="hidden" name="convocatoria_id" value="<?php echo $conv['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-small">
                                            📢 Publicar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pestaña Nueva Convocatoria (solo para entrenador principal) -->
        <?php if ($puede_gestionar_completo): ?>
            <div class="tab-content <?php echo $accion == 'nueva' ? 'active' : ''; ?>" id="tab-nueva">
                <div class="form-section">
                    <h2>🏆 Crear Nueva Convocatoria</h2>

                    <?php if (empty($partidos_disponibles)): ?>
                        <div class="empty-state">
                            <div>📭</div>
                            <h3>No hay partidos disponibles</h3>
                            <p>No hay partidos programados para los próximos 7 días o ya tienen convocatoria.</p>
                            <a href="/TechSport/Páginas/Privadas/EditarCalendario.php" class="btn btn-primary">
                                🗓️ Ir al Calendario
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="accion" value="crear_convocatoria">

                            <div class="form-group">
                                <label for="evento_id">Seleccionar Partido:</label>
                                <select id="evento_id" name="evento_id" class="form-control" required>
                                    <option value="">-- Seleccionar partido --</option>
                                    <?php foreach ($partidos_disponibles as $partido):
                                        $fecha = new DateTime($partido['fecha']);
                                        $fecha_limite = clone $fecha;
                                        $fecha_limite->modify('-2 days');
                                        $fecha_limite->setTime(14, 0, 0);
                                        $ahora = new DateTime();
                                    ?>
                                        <option value="<?php echo $partido['id']; ?>">
                                            <?php echo htmlspecialchars($partido['titulo']); ?> -
                                            <?php echo $fecha->format('d/m/Y H:i'); ?> -
                                            Límite: <?php echo $fecha_limite->format('d/m/Y H:i'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="notas">Notas para la convocatoria (opcional):</label>
                                <textarea id="notas" name="notas" class="form-control"
                                    placeholder="Instrucciones especiales, lugar de concentración, hora de llegada..."
                                    rows="4"></textarea>
                            </div>

                            <div class="alert" style="background: #ebf8ff; color: #2b6cb0;">
                                <strong>⚠️ Información importante:</strong>
                                <ul>
                                    <li>La convocatoria debe crearse al menos <strong>2 días antes</strong> del partido</li>
                                    <li>Podrás editar los jugadores hasta <strong>10 horas antes</strong> del partido</li>
                                    <li>Las estadísticas pueden editarse <strong>10 horas después</strong> hasta <strong>2 días después</strong> del partido</li>
                                    <li>Solo se pueden convocar <strong>máximo <?php echo MAX_JUGADORES_CONVOCATORIA; ?> jugadores</strong> por partido</li>
                                    <li>El tipo de jugador (titular/suplente/reserva) se define al añadir estadísticas</li>
                                </ul>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                🏆 Crear Convocatoria
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Pestaña Editar Convocatoria -->
        <?php if ($accion == 'editar' && isset($convocatoria_actual)): ?>
            <div class="tab-content active" id="tab-editar">
                <div class="form-section">
                    <div class="convocatoria-info">
                        <h2>✏️ Editar Convocatoria</h2>
                        <?php if ($puede_gestionar_completo && $convocatoria_actual['estado'] != 'publicada'): ?>
                            <div style="text-align: right; margin-bottom: 15px;">
                                <form method="POST">
                                    <input type="hidden" name="accion" value="eliminar_convocatoria">
                                    <input type="hidden" name="convocatoria_id" value="<?php echo $convocatoria_actual['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small"
                                        onclick="return confirm('¿Estás seguro de eliminar esta convocatoria? Se eliminarán todos los jugadores convocados. Esta acción no se puede deshacer.')">
                                        🗑️ Eliminar Convocatoria
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <div class="info-item">
                                <span class="info-label">Partido:</span>
                                <span class="info-value"><?php echo htmlspecialchars($convocatoria_actual['partido_titulo']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Fecha del partido:</span>
                                <span class="info-value">
                                    <?php echo (new DateTime($convocatoria_actual['partido_fecha']))->format('d/m/Y H:i'); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Límite para editar:</span>
                                <span class="info-value">
                                    <?php
                                    $fecha_limite_edicion = new DateTime($convocatoria_actual['partido_fecha']);
                                    $fecha_limite_edicion->modify('-10 hours');
                                    echo $fecha_limite_edicion->format('d/m/Y H:i');
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-item">
                                <span class="info-label">Estado:</span>
                                <span class="info-value">
                                    <span class="badge badge-<?php echo $convocatoria_actual['estado']; ?>">
                                        <?php echo ucfirst($convocatoria_actual['estado']); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Jugadores convocados:</span>
                                <span class="info-value">
                                    <?php
                                    $total_convocados = count($convocatoria_actual['jugadores']);
                                    $counter_class = '';
                                    if ($total_convocados >= MAX_JUGADORES_CONVOCATORIA) {
                                        $counter_class = 'style="background: #e53e3e;"';
                                    } elseif ($total_convocados >= MAX_JUGADORES_CONVOCATORIA - 3) {
                                        $counter_class = 'style="background: #ed8936;"';
                                    }
                                    ?>
                                    <span class="jugador-counter" <?php echo $counter_class; ?>>
                                        <?php echo $total_convocados; ?>/<?php echo MAX_JUGADORES_CONVOCATORIA; ?>
                                    </span>
                                </span>
                            </div>
                            <?php if ($convocatoria_actual['temporada_nombre']): ?>
                                <div class="info-item">
                                    <span class="info-label">Temporada:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($convocatoria_actual['temporada_nombre']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($convocatoria_actual['notas']): ?>
                            <div class="info-row">
                                <div class="info-item">
                                    <span class="info-label">Notas:</span>
                                    <span class="info-value"><?php echo nl2br(htmlspecialchars($convocatoria_actual['notas'])); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Añadir jugador (solo si puede gestionar) -->
                    <?php if ($puede_gestionar_completo): ?>
                        <h3>➕ Añadir Jugador</h3>

                        <?php
                        $puede_editar = verificarFechaLimite($convocatoria_actual['partido_fecha'], 'editar');
                        $total_convocados = count($convocatoria_actual['jugadores']);
                        $limite_alcanzado = $total_convocados >= MAX_JUGADORES_CONVOCATORIA;

                        if (!$puede_editar): ?>
                            <div class="alert alert-error">
                                ❌ Ya pasó el límite para editar la convocatoria (10 horas antes del partido).
                            </div>
                        <?php elseif ($limite_alcanzado): ?>
                            <div class="alert alert-error">
                                ❌ Has alcanzado el límite de <?php echo MAX_JUGADORES_CONVOCATORIA; ?> jugadores convocados.
                            </div>
                        <?php endif; ?>

                        <?php if ($puede_editar && !$limite_alcanzado): ?>
                            <form method="POST" action="" id="form-añadir-jugador">
                                <input type="hidden" name="accion" value="añadir_jugador">
                                <input type="hidden" name="convocatoria_id" value="<?php echo $convocatoria_actual['id']; ?>">

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                    <div class="form-group">
                                        <label for="jugador_id">Jugador:</label>
                                        <select id="jugador_id" name="jugador_id" class="form-control" required>
                                            <option value="">-- Seleccionar jugador --</option>
                                            <?php foreach ($jugadores as $jugador):
                                                // Verificar si ya está convocado
                                                $ya_convocado = false;
                                                foreach ($convocatoria_actual['jugadores'] as $conv) {
                                                    if ($conv['jugador_id'] == $jugador['jugador_id']) {
                                                        $ya_convocado = true;
                                                        break;
                                                    }
                                                }

                                                $dorsal_info = $jugador['dorsal'] ? ' #' . $jugador['dorsal'] : '';
                                            ?>
                                                <option value="<?php echo $jugador['jugador_id']; ?>" <?php echo $ya_convocado ? 'disabled' : ''; ?>>
                                                    <?php echo htmlspecialchars($jugador['nombre'] . $dorsal_info); ?>
                                                    <?php if ($jugador['posicion']): ?>
                                                        - <?php echo htmlspecialchars($jugador['posicion']); ?>
                                                    <?php endif; ?>
                                                    <?php if ($ya_convocado): ?> (Ya convocado) <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="dorsal">Dorsal:</label>
                                        <input type="number" id="dorsal" name="dorsal" class="form-control"
                                            min="1" max="99" placeholder="Nº" style="width: 100%;">
                                    </div>

                                    <div class="form-group">
                                        <label for="posicion">Posición:</label>
                                        <input type="text" id="posicion" name="posicion" class="form-control"
                                            placeholder="Ej: Delantero, Medio, Defensa, Portero...">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    ➕ Añadir a Convocatoria
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Lista de jugadores convocados -->
                    <h3 style="margin-top: 30px;">👥 Jugadores Convocados</h3>

                    <?php if (empty($convocatoria_actual['jugadores'])): ?>
                        <div class="empty-state">
                            <div>👥</div>
                            <p>No hay jugadores convocados aún.</p>
                            <?php if ($puede_gestionar_completo && $puede_editar && !$limite_alcanzado): ?>
                                <p>Usa el formulario de arriba para añadir jugadores.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="convocados-grid">
                            <?php foreach ($convocatoria_actual['jugadores'] as $jugador): ?>
                                <div class="convocado-card">
                                    <?php if ($puede_gestionar_completo && $puede_editar): ?>
                                        <form method="POST" class="convocado-eliminar-form">
                                            <input type="hidden" name="accion" value="eliminar_jugador">
                                            <input type="hidden" name="convocatoria_id" value="<?php echo $convocatoria_actual['id']; ?>">
                                            <input type="hidden" name="jugador_id" value="<?php echo $jugador['jugador_id']; ?>">
                                            <button type="submit" class="convocado-eliminar"
                                                onclick="return confirm('¿Eliminar a <?php echo htmlspecialchars($jugador['nombre']); ?> de la convocatoria?')">
                                                ×
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <div class="convocado-header">
                                        <div>
                                            <h4 style="margin: 0; color: #2d3748; font-size: 1.1rem;">
                                                <?php echo htmlspecialchars($jugador['nombre']); ?>
                                            </h4>
                                        </div>
                                        <?php if ($jugador['dorsal']): ?>
                                            <div class="convocado-dorsal"><?php echo $jugador['dorsal']; ?></div>
                                        <?php elseif ($jugador['dorsal_habitual']): ?>
                                            <div class="convocado-dorsal"><?php echo $jugador['dorsal_habitual']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($jugador['posicion']): ?>
                                        <p style="margin: 8px 0 5px 0; color: #4a5568; font-size: 0.95rem;">
                                            <strong>Posición:</strong> <?php echo htmlspecialchars($jugador['posicion']); ?>
                                        </p>
                                    <?php elseif ($jugador['posicion_habitual']): ?>
                                        <p style="margin: 8px 0 5px 0; color: #4a5568; font-size: 0.95rem;">
                                            <strong>Posición:</strong> <?php echo htmlspecialchars($jugador['posicion_habitual']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-top: 25px; padding: 15px; background: #f1f5f9; border-radius: 12px; text-align: center;">
                            <p style="margin: 0; color: #4a5568; font-size: 1rem; font-weight: 600;">
                                📊 <strong>Total convocados:</strong> <?php echo $total_convocados; ?> jugadores
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Botón para publicar (solo entrenador principal) -->
                    <?php if ($puede_gestionar_completo && $convocatoria_actual['estado'] == 'pendiente' && $total_convocados > 0): ?>
                        <div style="margin-top: 30px; text-align: center; padding: 20px; background: #ebf8ff; border-radius: 12px;">
                            <p style="margin: 0 0 15px 0; color: #2b6cb0; font-weight: 600;">
                                📢 La convocatoria está lista para ser publicada. Una vez publicada, los jugadores podrán verla.
                            </p>
                            <form method="POST">
                                <input type="hidden" name="accion" value="publicar_convocatoria">
                                <input type="hidden" name="convocatoria_id" value="<?php echo $convocatoria_actual['id']; ?>">
                                <button type="submit" class="btn btn-primary"
                                    onclick="return confirm('¿Publicar la convocatoria? Los jugadores podrán verla.')">
                                    📢 Publicar Convocatoria
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Pestaña Estadísticas -->
        <?php if ($accion == 'estadisticas' && isset($convocatoria_actual)): ?>
            <div class="tab-content active" id="tab-estadisticas">
                <div class="form-section">
                    <div class="convocatoria-info">
                        <h2>📊 Estadísticas del Partido</h2>
                        <div class="info-row">
                            <div class="info-item">
                                <span class="info-label">Partido:</span>
                                <span class="info-value"><?php echo htmlspecialchars($convocatoria_actual['partido_titulo']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Fecha del partido:</span>
                                <span class="info-value">
                                    <?php echo (new DateTime($convocatoria_actual['partido_fecha']))->format('d/m/Y H:i'); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Rango para estadísticas:</span>
                                <span class="info-value">
                                    Desde: <?php echo $fecha_minima_stats->format('d/m/Y H:i'); ?><br>
                                    Hasta: <?php echo $fecha_maxima_stats->format('d/m/Y H:i'); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php
                    $puede_editar_stats = verificarFechaLimite($convocatoria_actual['partido_fecha'], 'estadisticas');
                    if (!$puede_editar_stats):
                        $ahora = new DateTime();
                        
                        if ($ahora < $fecha_minima_stats):
                    ?>
                        <div class="alert alert-error">
                            ❌ Aún no puedes editar estadísticas. Podrás hacerlo <?php echo $fecha_minima_stats->format('d/m/Y H:i'); ?> (10 horas después del partido).
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            ❌ Ya pasó el plazo para editar estadísticas (hasta <?php echo $fecha_maxima_stats->format('d/m/Y H:i'); ?>).
                        </div>
                    <?php
                        endif;
                    endif;
                    ?>

                    <h3>Estadísticas por Jugador</h3>

                    <?php if (empty($convocatoria_actual['jugadores'])): ?>
                        <div class="empty-state">
                            <div>📊</div>
                            <p>No hay jugadores convocados en este partido.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Jugador</th>
                                        <th>Tipo</th>
                                        <th>Goles</th>
                                        <th>Asistencias</th>
                                        <th>Amarillas</th>
                                        <th>Rojas</th>
                                        <th>Porterías 0</th>
                                        <th>Minutos</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($convocatoria_actual['jugadores'] as $jugador): ?>
                                        <tr>
                                            <td style="min-width: 200px;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <?php if ($jugador['dorsal']): ?>
                                                        <div style="background: #1a2a6c; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem;">
                                                            <?php echo $jugador['dorsal']; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($jugador['nombre']); ?></strong>
                                                        <?php if ($jugador['posicion']): ?>
                                                            <div style="font-size: 0.85rem; color: #718096;"><?php echo htmlspecialchars($jugador['posicion']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <select name="tipo" style="padding: 6px; border-radius: 4px; border: 1px solid #ddd;">
                                                    <option value="titular" <?php echo ($jugador['tipo'] ?? 'suplente') == 'titular' ? 'selected' : ''; ?>>Titular</option>
                                                    <option value="suplente" <?php echo ($jugador['tipo'] ?? 'suplente') == 'suplente' ? 'selected' : ''; ?>>Suplente</option>
                                                    <option value="reserva" <?php echo ($jugador['tipo'] ?? 'suplente') == 'reserva' ? 'selected' : ''; ?>>Reserva</option>
                                                </select>
                                            </td>
                                            <form method="POST" action="">
                                                <input type="hidden" name="accion" value="guardar_estadisticas">
                                                <input type="hidden" name="convocatoria_id" value="<?php echo $convocatoria_actual['id']; ?>">
                                                <input type="hidden" name="jugador_id" value="<?php echo $jugador['jugador_id']; ?>">
                                                <input type="hidden" name="tipo" id="tipo_<?php echo $jugador['jugador_id']; ?>">

                                                <td>
                                                    <input type="number" name="goles" class="stat-input"
                                                        min="0" max="20" value="0" <?php echo !$puede_editar_stats ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" name="asistencias" class="stat-input"
                                                        min="0" max="20" value="0" <?php echo !$puede_editar_stats ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" name="amarillas" class="stat-input"
                                                        min="0" max="2" value="0" <?php echo !$puede_editar_stats ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" name="rojas" class="stat-input"
                                                        min="0" max="1" value="0" <?php echo !$puede_editar_stats ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" name="porterias_cero" class="stat-input"
                                                        min="0" max="1" value="0" <?php echo !$puede_editar_stats ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" name="minutos_jugados" class="stat-input"
                                                        min="0" max="120" value="0" <?php echo !$puede_editar_stats ? 'disabled' : ''; ?>>
                                                </td>
                                                <td>
                                                    <?php if ($puede_editar_stats): ?>
                                                        <button type="submit" class="btn btn-secondary btn-small" onclick="document.getElementById('tipo_<?php echo $jugador['jugador_id']; ?>').value = this.parentElement.parentElement.querySelector('select[name=\"tipo\"]').value;">
                                                            💾 Guardar
                                                        </button>
                                                    <?php else: ?>
                                                        <span style="color: #94a3b8; font-size: 0.9rem;">Edición cerrada</span>
                                                    <?php endif; ?>
                                                </td>
                                            </form>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert" style="margin-top: 20px; background: #fff7ed; color: #9c4221;">
                            <strong>📝 Notas sobre estadísticas:</strong>
                            <ul>
                                <li><strong>Portería 0:</strong> Solo para porteros (1 si mantuvo la portería a cero)</li>
                                <li><strong>Minutos jugados:</strong> Tiempo total en el campo (máximo 120 minutos)</li>
                                <li><strong>Tarjetas amarillas:</strong> Máximo 2 por jugador</li>
                                <li><strong>Tarjeta roja:</strong> 1 si fue expulsado (no se aplica amarilla)</li>
                                <li><strong>Asistencias:</strong> Pase que genera un gol directamente</li>
                                <li><strong>Tipo:</strong> Define si el jugador fue titular, suplente o reserva</li>
                                <li><strong>Plazo:</strong> Las estadísticas solo pueden editarse 10 horas después del partido y hasta 2 días después</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
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
            <div class="footer-copyright">
                <p>© 2025 TechSport.</p>
            </div>
        </div>
    </footer>

    <script>
        // Auto-llenar dorsal y posición basado en jugador seleccionado
        const selectJugador = document.getElementById('jugador_id');
        const inputDorsal = document.getElementById('dorsal');
        const inputPosicion = document.getElementById('posicion');

        if (selectJugador && inputDorsal && inputPosicion) {
            selectJugador.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const text = selectedOption.text;

                // Buscar dorsal (#número)
                const dorsalMatch = text.match(/#(\d+)/);
                if (dorsalMatch && dorsalMatch[1]) {
                    inputDorsal.value = dorsalMatch[1];
                } else {
                    inputDorsal.value = '';
                }

                // Buscar posición después del guión
                if (text.includes('-')) {
                    const partes = text.split('-');
                    if (partes.length > 1) {
                        const posicion = partes[1].trim().split('(')[0].trim();
                        if (posicion) {
                            inputPosicion.value = posicion;
                        } else {
                            inputPosicion.value = '';
                        }
                    } else {
                        inputPosicion.value = '';
                    }
                } else {
                    inputPosicion.value = '';
                }
            });
        }

        // Confirmación para eliminar jugadores
        document.querySelectorAll('.convocado-eliminar').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de eliminar a este jugador de la convocatoria?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>