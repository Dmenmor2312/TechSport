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

// Incluir conexión
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';
// Incluir funciones del calendario
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/Funciones/calendario.php';

// Obtener equipo del jugador usando la función corregida
$equipo = obtenerEquipoUsuario($conn, $usuario_id, $rol_id);

if (!$equipo) {
    $conn->close();
    header("Location: /TechSport/Páginas/Privadas/Jugador/inicio.php");
    exit();
}

// Obtener encuestas activas del equipo (excluyendo eventos tipo 'partido')
$stmt = $conn->prepare("
    SELECT e.*, ev.titulo AS evento_titulo, ev.fecha AS evento_fecha, ev.tipo AS evento_tipo
    FROM encuestas e
    JOIN eventos ev ON e.evento_id = ev.id
    WHERE e.equipo_id = ? 
    AND e.temporada_id = ?
    AND ev.tipo != 'partido'
    AND e.estado = 'activa'
    AND NOW() BETWEEN e.fecha_inicio AND e.fecha_fin
    ORDER BY e.fecha_fin ASC
");
$stmt->bind_param("ii", $equipo['id'], $equipo['temporada_id']);
$stmt->execute();
$result = $stmt->get_result();
$encuestas_activas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener encuestas finalizadas (últimas 5)
$stmt = $conn->prepare("
    SELECT e.*, ev.titulo AS evento_titulo, ev.fecha AS evento_fecha, ev.tipo AS evento_tipo
    FROM encuestas e
    JOIN eventos ev ON e.evento_id = ev.id
    WHERE e.equipo_id = ? 
    AND e.temporada_id = ?
    AND ev.tipo != 'partido'
    AND (e.estado = 'finalizada' OR e.fecha_fin < NOW())
    ORDER BY e.fecha_fin DESC
    LIMIT 5
");
$stmt->bind_param("ii", $equipo['id'], $equipo['temporada_id']);
$stmt->execute();
$result = $stmt->get_result();
$encuestas_finalizadas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Procesar votos
$mensaje = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'votar') {
    $encuesta_id = intval($_POST['encuesta_id'] ?? 0);
    $opcion_id = intval($_POST['opcion_id'] ?? 0);

    if ($encuesta_id > 0 && $opcion_id > 0) {
        // Verificar que el usuario no haya votado ya
        $stmt = $conn->prepare("SELECT id FROM encuesta_votos WHERE encuesta_id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $encuesta_id, $usuario_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            // Registrar el voto
            $stmt = $conn->prepare("INSERT INTO encuesta_votos (encuesta_id, opcion_id, usuario_id) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $encuesta_id, $opcion_id, $usuario_id);
            $stmt->execute();
            $mensaje = "✅ Tu voto ha sido registrado correctamente.";

            // Recargar la página para actualizar resultados
            header("Refresh: 0");
        } else {
            $error = "⚠️ Ya has votado en esta encuesta.";
        }
        $stmt->close();
    }
}

// Obtener resultados de encuestas
function obtenerResultadosEncuesta($conn, $encuesta_id)
{
    $resultados = [];

    // Obtener opciones y conteo de votos
    $stmt = $conn->prepare("
        SELECT o.id, o.opcion_texto, o.orden, 
               COUNT(v.id) AS total_votos
        FROM encuesta_opciones o
        LEFT JOIN encuesta_votos v ON o.id = v.opcion_id AND v.encuesta_id = ?
        WHERE o.encuesta_id = ?
        GROUP BY o.id, o.opcion_texto, o.orden
        ORDER BY o.orden ASC
    ");
    $stmt->bind_param("ii", $encuesta_id, $encuesta_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $total_votos = 0;
    while ($row = $result->fetch_assoc()) {
        $resultados[] = $row;
        $total_votos += $row['total_votos'];
    }
    $stmt->close();

    // Agregar porcentajes
    foreach ($resultados as &$resultado) {
        $resultado['porcentaje'] = $total_votos > 0 ? round(($resultado['total_votos'] / $total_votos) * 100, 1) : 0;
    }

    return ['resultados' => $resultados, 'total_votos' => $total_votos];
}

// Verificar si el usuario ya votó en cada encuesta activa
$votos_usuario = [];
foreach ($encuestas_activas as $encuesta) {
    $stmt = $conn->prepare("SELECT opcion_id FROM encuesta_votos WHERE encuesta_id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $encuesta['id'], $usuario_id);
    $stmt->execute();
    $stmt->store_result();
    $votos_usuario[$encuesta['id']] = $stmt->num_rows > 0;
    $stmt->close();
}

// Obtener resultados para encuestas finalizadas
$resultados_finalizadas = [];
foreach ($encuestas_finalizadas as $encuesta) {
    $resultados_finalizadas[$encuesta['id']] = obtenerResultadosEncuesta($conn, $encuesta['id']);
}

// Obtener opciones para encuestas activas (solo si no ha votado)
$opciones_encuestas = [];
foreach ($encuestas_activas as $encuesta) {
    if (!$votos_usuario[$encuesta['id']]) {
        $stmt = $conn->prepare("SELECT id, opcion_texto, orden FROM encuesta_opciones WHERE encuesta_id = ? ORDER BY orden ASC");
        $stmt->bind_param("i", $encuesta['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $opciones_encuestas[$encuesta['id']] = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        // Si ya votó, obtener resultados
        $opciones_encuestas[$encuesta['id']] = obtenerResultadosEncuesta($conn, $encuesta['id']);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TechSport - Encuestas</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Jugador/encuesta.css" />
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
                <li><a class="nav-link active" href="/TechSport/Páginas/Privadas/Jugador/inicio.php">Inicio</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Jugador/Estadisticas.php">Estadísticas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Jugador/Encuestas.php">Encuestas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipos Rivales</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Convocatoria.php">Convocatoria</a></li>
                <li><a class="nav-link" href="/TechSport/LogicaPHP/logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main class="encuestas-container">
        <!-- Sección de bienvenida -->
        <div class="welcome-section">
            <h1>📊 Sistema de Encuestas</h1>
            <p>Vota en las encuestas activas de tu equipo</p>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Encuestas activas -->
        <div class="encuestas-section">
            <h2>📝 Encuestas Activas</h2>

            <?php if (empty($encuestas_activas)): ?>
                <div class="no-encuestas">
                    <i>📭</i>
                    <p>No hay encuestas activas en este momento.</p>
                    <p>Las encuestas aparecen para eventos de entrenamiento, reuniones y otras actividades (excepto partidos).</p>
                </div>
            <?php else: ?>
                <?php foreach ($encuestas_activas as $encuesta):
                    $ya_voto = $votos_usuario[$encuesta['id']];
                    $datos_opciones = $opciones_encuestas[$encuesta['id']];
                ?>
                    <div class="encuesta-card">
                        <div class="encuesta-header">
                            <h3><?php echo htmlspecialchars($encuesta['pregunta']); ?></h3>
                            <div class="encuesta-info">
                                <span>📅 Evento: <?php echo htmlspecialchars($encuesta['evento_titulo']); ?></span>
                                <span>🏷️ Tipo: <?php echo htmlspecialchars($encuesta['evento_tipo']); ?></span>
                                <span>⏰ Fecha evento: <?php echo date('d/m/Y H:i', strtotime($encuesta['evento_fecha'])); ?></span>
                                <span>🗳️ Vence: <?php echo date('d/m/Y H:i', strtotime($encuesta['fecha_fin'])); ?></span>
                            </div>
                        </div>

                        <?php if ($ya_voto): ?>
                            <!-- Mostrar resultados -->
                            <?php if (isset($datos_opciones['resultados'])): ?>
                                <div class="resultado-container">
                                    <h4>📊 Resultados:</h4>
                                    <?php foreach ($datos_opciones['resultados'] as $opcion): ?>
                                        <div class="resultado-item">
                                            <div class="resultado-header">
                                                <span class="resultado-texto"><?php echo htmlspecialchars($opcion['opcion_texto']); ?></span>
                                                <span class="resultado-porcentaje"><?php echo $opcion['porcentaje']; ?>% (<?php echo $opcion['total_votos']; ?> votos)</span>
                                            </div>
                                            <div class="barra-progreso">
                                                <div class="barra-progreso-fill" style="width: <?php echo $opcion['porcentaje']; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="total-votos">
                                        Total de votos: <?php echo $datos_opciones['total_votos']; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="ya-votado">
                                ✅ Ya has votado en esta encuesta
                            </div>
                        <?php else: ?>
                            <!-- Formulario para votar -->
                            <form method="POST" action="">
                                <input type="hidden" name="accion" value="votar">
                                <input type="hidden" name="encuesta_id" value="<?php echo $encuesta['id']; ?>">

                                <div class="opciones-container">
                                    <?php if (isset($datos_opciones) && is_array($datos_opciones)): ?>
                                        <?php foreach ($datos_opciones as $opcion): ?>
                                            <label class="opcion-item">
                                                <input type="radio" name="opcion_id" value="<?php echo $opcion['id']; ?>" required>
                                                <span class="opcion-texto"><?php echo htmlspecialchars($opcion['opcion_texto']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="no-encuestas">No hay opciones disponibles para esta encuesta.</p>
                                    <?php endif; ?>
                                </div>

                                <button type="submit" class="btn-votar">🗳️ Enviar Voto</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Encuestas finalizadas -->
        <?php if (!empty($encuestas_finalizadas)): ?>
            <div class="encuestas-section">
                <h2>📋 Encuestas Finalizadas</h2>

                <?php foreach ($encuestas_finalizadas as $encuesta):
                    $resultados = $resultados_finalizadas[$encuesta['id']] ?? null;
                ?>
                    <div class="encuesta-card encuesta-finalizada">
                        <div class="encuesta-header">
                            <h3><?php echo htmlspecialchars($encuesta['pregunta']); ?></h3>
                            <div class="encuesta-info">
                                <span>📅 Evento: <?php echo htmlspecialchars($encuesta['evento_titulo']); ?></span>
                                <span>🏷️ Tipo: <?php echo htmlspecialchars($encuesta['evento_tipo']); ?></span>
                                <span>⏰ Finalizó: <?php echo date('d/m/Y H:i', strtotime($encuesta['fecha_fin'])); ?></span>
                            </div>
                        </div>

                        <?php if ($resultados): ?>
                            <div class="resultado-container">
                                <?php foreach ($resultados['resultados'] as $opcion): ?>
                                    <div class="resultado-item">
                                        <div class="resultado-header">
                                            <span class="resultado-texto"><?php echo htmlspecialchars($opcion['opcion_texto']); ?></span>
                                            <span class="resultado-porcentaje"><?php echo $opcion['porcentaje']; ?>% (<?php echo $opcion['total_votos']; ?> votos)</span>
                                        </div>
                                        <div class="barra-progreso">
                                            <div class="barra-progreso-fill" style="width: <?php echo $opcion['porcentaje']; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="total-votos">
                                    Total de votos: <?php echo $resultados['total_votos']; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-encuestas">No hay resultados disponibles para esta encuesta.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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
</body>

</html>