<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    header("Location: /TechSport/LogicaPHP/login.php");
    exit();
}

$rol_id = $_SESSION['rol_id'];
$usuario_id = $_SESSION['usuario_id'];

// Solo Entrenador (1) o Profesional (3) pueden ver esta página
if ($rol_id != 1 && $rol_id != 3) {
    // Si es jugador (2), redirigir a su inicio
    header("Location: /TechSport/Páginas/Privadas/Jugador/inicio.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';

// Función para obtener el equipo del usuario según su rol
function obtenerEquipoUsuario($conn, $usuario_id, $rol_id) {
    if ($rol_id == 1) { // Entrenador
        $stmt = $conn->prepare("
            SELECT e.id, e.nombre, e.temporada_id 
            FROM equipos e
            WHERE e.entrenador_id = ? 
            OR EXISTS (SELECT 1 FROM equipo_entrenadores ee WHERE ee.equipo_id = e.id AND ee.entrenador_id = ?)
            LIMIT 1
        ");
        $stmt->bind_param("ii", $usuario_id, $usuario_id);
    } elseif ($rol_id == 3) { // Profesional
        $stmt = $conn->prepare("
            SELECT e.id, e.nombre, e.temporada_id 
            FROM equipos e
            INNER JOIN equipo_profesionales ep ON e.id = ep.equipo_id
            WHERE ep.profesional_id = ? 
            LIMIT 1
        ");
        $stmt->bind_param("i", $usuario_id);
    } else {
        return null;
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

// Obtener equipo del usuario
$equipo = obtenerEquipoUsuario($conn, $usuario_id, $rol_id);

if (!$equipo) {
    echo "<script>alert('No tienes un equipo asignado.'); window.location.href = '/TechSport/Páginas/Privadas/" . ($rol_id == 1 ? "Entrenador" : "Profesional") . "/inicio.php';</script>";
    $conn->close();
    exit();
}

// Obtener jugadores del equipo
$stmt = $conn->prepare("
    SELECT 
        j.id as jugador_id,
        j.usuario_id,
        j.dorsal,
        j.posicion,
        j.altura,
        j.peso,
        j.fecha_nacimiento,
        u.nombre as nombre_jugador,
        u.email
    FROM jugadores j
    INNER JOIN usuarios u ON j.usuario_id = u.id
    INNER JOIN equipo_jugadores ej ON j.id = ej.jugador_id
    WHERE ej.equipo_id = ? AND ej.activo = TRUE
    ORDER BY u.nombre
");
$stmt->bind_param("i", $equipo['id']);
$stmt->execute();
$jugadores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener estadísticas resumidas para cada jugador
$estadisticas_jugadores = [];
foreach ($jugadores as $jugador) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as partidos_jugados,
            SUM(goles) as goles_totales,
            SUM(asistencias) as asistencias_totales,
            SUM(minutos_jugados) as minutos_totales
        FROM estadisticas_partido
        WHERE jugador_id = ? AND equipo_id = ?
    ");
    $stmt->bind_param("ii", $jugador['jugador_id'], $equipo['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $estadisticas = $result->fetch_assoc();
    } else {
        $estadisticas = [
            'partidos_jugados' => 0,
            'goles_totales' => 0,
            'asistencias_totales' => 0,
            'minutos_totales' => 0
        ];
    }
    
    $estadisticas_jugadores[$jugador['jugador_id']] = $estadisticas;
    $stmt->close();
}

$conn->close();

// Determinar tipo de usuario para el header
$tipo_usuario = ($rol_id == 1) ? 'entrenador' : 'profesional';
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TechSport - Estadísticas del Equipo</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Compartidos/estilos.css" />
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <style>
        .estadisticas-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-title {
            text-align: center;
            color: #2D3047;
            margin-bottom: 30px;
            font-size: 2.2rem;
        }
        
        .equipo-info {
            background: linear-gradient(135deg, #2D3047 0%, #3D405B 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .equipo-info h2 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
        }
        
        .equipo-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .jugadores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .jugador-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .jugador-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
            border-color: #2D3047;
        }
        
        .jugador-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .avatar-jugador {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
        }
        
        .avatar-jugador.portero { background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%); }
        .avatar-jugador.defensa { background: linear-gradient(135deg, #06D6A0 0%, #048A81 100%); }
        .avatar-jugador.centrocampista { background: linear-gradient(135deg, #FFD166 0%, #F8961E 100%); }
        .avatar-jugador.delantero { background: linear-gradient(135deg, #EF476F 0%, #9D4EDD 100%); }
        
        .jugador-info h3 {
            margin: 0 0 5px 0;
            font-size: 1.3rem;
            color: #2D3047;
        }
        
        .jugador-info p {
            margin: 0;
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .estadisticas-resumen {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .estadistica-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        
        .estadistica-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .estadistica-valor {
            font-size: 1.1rem;
            font-weight: bold;
            color: #2D3047;
        }
        
        .ver-detalles-btn {
            display: block;
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            background: #2D3047;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .ver-detalles-btn:hover {
            background: #3D405B;
        }
        
        .sin-jugadores {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .sin-jugadores h3 {
            margin-bottom: 10px;
        }
        
        .volver-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .volver-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" id="logo-interactivo" />
        </div>
        <nav class="navbar">
            <ul class="nav-menu">
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/<?= ucfirst($tipo_usuario) ?>/inicio.php">Inicio</a></li>
                <li><a class="nav-link active" href="/TechSport/Páginas/Privadas/Estadisticas.php">Estadísticas</a></li>
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

    <main class="estadisticas-container">
        <h1 class="page-title">📊 Estadísticas del Equipo</h1>
        
        <div class="equipo-info">
            <h2><?= htmlspecialchars($equipo['nombre']) ?></h2>
            <p>Lista de jugadores y sus estadísticas</p>
        </div>
        
        <?php if (empty($jugadores)): ?>
            <div class="sin-jugadores">
                <h3>👥 No hay jugadores en el equipo</h3>
                <p>No se han encontrado jugadores activos en este equipo.</p>
                <a href="/TechSport/Páginas/Privadas/<?= ucfirst($tipo_usuario) ?>/inicio.php" class="volver-btn">
                    ← Volver al inicio
                </a>
            </div>
        <?php else: ?>
            <div class="jugadores-grid">
                <?php foreach ($jugadores as $jugador): 
                    $estadisticas = $estadisticas_jugadores[$jugador['jugador_id']];
                    $posicion_clase = strtolower(str_replace([' ', '/'], '', $jugador['posicion']));
                    
                    // Clasificar posición para CSS
                    if (stripos($posicion_clase, 'portero') !== false) {
                        $clase_css = 'portero';
                    } elseif (stripos($posicion_clase, 'defensa') !== false || stripos($posicion_clase, 'central') !== false || stripos($posicion_clase, 'lateral') !== false) {
                        $clase_css = 'defensa';
                    } elseif (stripos($posicion_clase, 'medio') !== false || stripos($posicion_clase, 'centrocampista') !== false || stripos($posicion_clase, 'interior') !== false) {
                        $clase_css = 'centrocampista';
                    } elseif (stripos($posicion_clase, 'delantero') !== false || stripos($posicion_clase, 'ataque') !== false || stripos($posicion_clase, 'extremo') !== false) {
                        $clase_css = 'delantero';
                    } else {
                        $clase_css = 'centrocampista'; // valor por defecto
                    }
                ?>
                    <div class="jugador-card">
                        <div class="jugador-header">
                            <div class="avatar-jugador <?= $clase_css ?>">
                                <?= $jugador['dorsal'] ? $jugador['dorsal'] : '?' ?>
                            </div>
                            <div class="jugador-info">
                                <h3><?= htmlspecialchars($jugador['nombre_jugador']) ?></h3>
                                <p><?= htmlspecialchars($jugador['posicion']) ?></p>
                            </div>
                        </div>
                        
                        <a href="/TechSport/Páginas/Privadas/estadisticas_jugador.php?jugador_id=<?= $jugador['jugador_id'] ?>" class="ver-detalles-btn">
                            Ver estadísticas detalladas →
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
</body>
</html>