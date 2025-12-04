<?php
session_start();
include 'basededatos.php';
include 'temporada.php';

// Verificar si el usuario es entrenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'entrenador') {
    header('Location: /TechSport/Páginas/Publica/Entrenador/login.html');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$conexion = $conn(); // Tu función de conexión

// Obtener temporada actual
$temporada_actual = temporadaActual($conexion);
$temporada_id = $temporada_actual['id'];

// Verificar si el entrenador ya tiene un equipo en esta temporada
$sql = "SELECT * FROM equipos WHERE entrenador_id = ? AND temporada_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $usuario_id, $temporada_id);
$stmt->execute();
$equipo_result = $stmt->get_result();
$equipo = $equipo_result->fetch_assoc();

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// Procesar creación de equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_equipo'])) {
    $nombre_equipo = trim($_POST['nombre_equipo']);

    if (!empty($nombre_equipo)) {
        // Si ya tiene equipo, eliminarlo primero (con todos sus miembros)
        if ($equipo) {
            eliminarEquipoCompleto($conexion, $equipo['id']);
        }

        // Crear nuevo equipo
        $sql = "INSERT INTO equipos (nombre, entrenador_id, temporada_id) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sii", $nombre_equipo, $usuario_id, $temporada_id);

        if ($stmt->execute()) {
            $equipo_id = $conexion->insert_id;
            $equipo = ['id' => $equipo_id, 'nombre' => $nombre_equipo];
            $mensaje = "Equipo creado exitosamente";
            $tipo_mensaje = "exito";
        } else {
            $mensaje = "Error al crear el equipo";
            $tipo_mensaje = "error";
        }
    }
}

// Procesar adición de jugador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['añadir_jugador']) && $equipo) {
    $email_jugador = trim($_POST['email_jugador']);

    if (!empty($email_jugador)) {
        // Buscar jugador por email
        $sql = "SELECT u.id, u.nombre 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.email = ? AND r.nombre = 'jugador'";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email_jugador);
        $stmt->execute();
        $jugador = $stmt->get_result()->fetch_assoc();

        if ($jugador) {
            // Verificar si ya está en el equipo
            $sql = "SELECT * FROM equipo_jugadores 
                    WHERE equipo_id = ? AND jugador_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $equipo['id'], $jugador['id']);
            $stmt->execute();

            if ($stmt->get_result()->num_rows === 0) {
                // Añadir jugador al equipo
                $sql = "INSERT INTO equipo_jugadores (equipo_id, jugador_id, activo) VALUES (?, ?, TRUE)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ii", $equipo['id'], $jugador['id']);

                if ($stmt->execute()) {
                    $mensaje = "Jugador añadido exitosamente";
                    $tipo_mensaje = "exito";
                } else {
                    $mensaje = "Error al añadir jugador";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "El jugador ya está en el equipo";
                $tipo_mensaje = "advertencia";
            }
        } else {
            $mensaje = "No se encontró un jugador con ese email";
            $tipo_mensaje = "error";
        }
    }
}

// Procesar adición de profesional
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['añadir_profesional']) && $equipo) {
    $email_profesional = trim($_POST['email_profesional']);

    if (!empty($email_profesional)) {
        // Buscar profesional por email
        $sql = "SELECT u.id, u.nombre 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.email = ? AND r.nombre = 'profesional'";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email_profesional);
        $stmt->execute();
        $profesional = $stmt->get_result()->fetch_assoc();

        if ($profesional) {
            // Verificar si ya está en el equipo
            $sql = "SELECT * FROM equipo_profesionales 
                    WHERE equipo_id = ? AND profesional_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $equipo['id'], $profesional['id']);
            $stmt->execute();

            if ($stmt->get_result()->num_rows === 0) {
                // Añadir profesional al equipo
                $sql = "INSERT INTO equipo_profesionales (equipo_id, profesional_id) VALUES (?, ?)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ii", $equipo['id'], $profesional['id']);

                if ($stmt->execute()) {
                    $mensaje = "Profesional añadido exitosamente";
                    $tipo_mensaje = "exito";
                } else {
                    $mensaje = "Error al añadir profesional";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "El profesional ya está en el equipo";
                $tipo_mensaje = "advertencia";
            }
        } else {
            $mensaje = "No se encontró un profesional con ese email";
            $tipo_mensaje = "error";
        }
    }
}

// Procesar eliminación de jugador
if (isset($_GET['eliminar_jugador']) && $equipo) {
    $jugador_id = intval($_GET['eliminar_jugador']);

    $sql = "DELETE FROM equipo_jugadores WHERE equipo_id = ? AND jugador_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $equipo['id'], $jugador_id);

    if ($stmt->execute()) {
        $mensaje = "Jugador eliminado del equipo";
        $tipo_mensaje = "exito";
    }
}

// Procesar eliminación de profesional
if (isset($_GET['eliminar_profesional']) && $equipo) {
    $profesional_id = intval($_GET['eliminar_profesional']);

    $sql = "DELETE FROM equipo_profesionales WHERE equipo_id = ? AND profesional_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $equipo['id'], $profesional_id);

    if ($stmt->execute()) {
        $mensaje = "Profesional eliminado del equipo";
        $tipo_mensaje = "exito";
    }
}

// Procesar eliminación de equipo completo
if (isset($_POST['eliminar_equipo']) && $equipo) {
    eliminarEquipoCompleto($conexion, $equipo['id']);
    $equipo = null;
    $mensaje = "Equipo eliminado exitosamente. Puedes crear uno nuevo.";
    $tipo_mensaje = "exito";
}

// Función para eliminar equipo completo con todos sus miembros
function eliminarEquipoCompleto($conexion, $equipo_id)
{
    // Eliminar jugadores del equipo
    $sql = "DELETE FROM equipo_jugadores WHERE equipo_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $equipo_id);
    $stmt->execute();

    // Eliminar profesionales del equipo
    $sql = "DELETE FROM equipo_profesionales WHERE equipo_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $equipo_id);
    $stmt->execute();

    // Eliminar el equipo
    $sql = "DELETE FROM equipos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $equipo_id);
    $stmt->execute();
}

// Obtener jugadores del equipo actual
$jugadores_equipo = [];
if ($equipo) {
    $sql = "SELECT u.id, u.nombre, u.email, j.dorsal, j.posicion 
            FROM equipo_jugadores ej 
            INNER JOIN usuarios u ON ej.jugador_id = u.id 
            INNER JOIN jugadores j ON u.id = j.usuario_id 
            WHERE ej.equipo_id = ? AND ej.activo = TRUE 
            ORDER BY j.dorsal";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $equipo['id']);
    $stmt->execute();
    $jugadores_equipo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Obtener profesionales del equipo actual
$profesionales_equipo = [];
if ($equipo) {
    $sql = "SELECT u.id, u.nombre, u.email 
            FROM equipo_profesionales ep 
            INNER JOIN usuarios u ON ep.profesional_id = u.id 
            WHERE ep.equipo_id = ? 
            ORDER BY u.nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $equipo['id']);
    $stmt->execute();
    $profesionales_equipo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Obtener información del entrenador
$sql = "SELECT nombre, email FROM usuarios WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$entrenador = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipo - Entrenador</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .temporadaActual {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .temporadaActual h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .temporada-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4CAF50;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #4a5568;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        }

        .btn-danger:hover {
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
        }

        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .member-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }

        .member-card:hover {
            transform: translateY(-3px);
        }

        .member-card.jugador {
            border-left-color: #4CAF50;
        }

        .member-card.profesional {
            border-left-color: #ff9800;
        }

        .member-card h4 {
            color: #2d3748;
            margin-bottom: 8px;
        }

        .member-card p {
            color: #718096;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .eliminar-btn {
            background-color: #fed7d7;
            color: #c53030;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            transition: background-color 0.2s;
        }

        .eliminar-btn:hover {
            background-color: #feb2b2;
        }

        .mensaje {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .mensaje.exito {
            background-color: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .mensaje.error {
            background-color: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .mensaje.advertencia {
            background-color: #feebc8;
            color: #744210;
            border: 1px solid #fbd38d;
        }

        .equipo-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .equipo-info h3 {
            font-size: 1.8rem;
            margin: 0;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stat-card h4 {
            color: #4a5568;
            margin-bottom: 10px;
        }

        .stat-card .numero {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
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
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/inicio.html">Inicio</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/Estadisticas.html">Estadisticas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/Equipo.php">Equipo</a></li>
                <li><a class="nav-link" href="#">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <div class="container">
        <div class="temporadaActual">
            <h1>🏀 Gestión de Equipo</h1>
            <p>Temporada <?php echo $temporada_actual['nombre']; ?></p>
        </div>

        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="temporada-info">
            <p><strong>Temporada Actual:</strong> <?php echo $temporada_actual['nombre']; ?>
                (<?php echo date('d/m/Y', strtotime($temporada_actual['fecha_inicio'])); ?> -
                <?php echo date('d/m/Y', strtotime($temporada_actual['fecha_fin'])); ?>)</p>
        </div>

        <?php if (!$equipo): ?>
            <!-- Formulario para crear equipo -->
            <div class="card">
                <h2>Crear Nuevo Equipo</h2>
                <p>No tienes un equipo para esta temporada. Crea uno nuevo para empezar.</p>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nombre_equipo">Nombre del Equipo:</label>
                        <input type="text" id="nombre_equipo" name="nombre_equipo"
                            required placeholder="Ej: Toros del Norte" maxlength="100">
                    </div>

                    <button type="submit" name="crear_equipo" class="btn">
                        🏁 Crear Equipo
                    </button>
                </form>
            </div>

        <?php else: ?>
            <!-- Información del equipo existente -->
            <div class="equipo-info">
                <div>
                    <h3><?php echo htmlspecialchars($equipo['nombre']); ?></h3>
                    <p>Temporada: <?php echo $temporada_actual['nombre']; ?></p>
                </div>

                <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de eliminar el equipo completo? Se perderán todos los jugadores y profesionales asociados.');">
                    <button type="submit" name="eliminar_equipo" class="btn btn-danger">
                        🗑️ Eliminar Equipo
                    </button>
                </form>
            </div>

            <!-- Estadísticas -->
            <div class="stats">
                <div class="stat-card">
                    <h4>Entrenador</h4>
                    <div class="numero">1</div>
                    <p><?php echo htmlspecialchars($entrenador['nombre']); ?></p>
                </div>

                <div class="stat-card">
                    <h4>Jugadores</h4>
                    <div class="numero"><?php echo count($jugadores_equipo); ?></div>
                    <p>Activos en el equipo</p>
                </div>

                <div class="stat-card">
                    <h4>Profesionales</h4>
                    <div class="numero"><?php echo count($profesionales_equipo); ?></div>
                    <p>Apoyando al equipo</p>
                </div>
            </div>

            <!-- Sección Entrenador -->
            <div class="card">
                <h2>👨‍🏫 Entrenador del Equipo</h2>
                <div class="member-card">
                    <h4><?php echo htmlspecialchars($entrenador['nombre']); ?></h4>
                    <p>📧 <?php echo htmlspecialchars($entrenador['email']); ?></p>
                    <p>👑 Rol: Entrenador Principal</p>
                </div>
            </div>

            <!-- Formulario para añadir jugador -->
            <div class="card">
                <h2>➕ Añadir Jugador al Equipo</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email_jugador">Email del Jugador:</label>
                        <input type="email" id="email_jugador" name="email_jugador"
                            required placeholder="jugador@ejemplo.com">
                        <small>Introduce el email del jugador que quieres añadir al equipo.</small>
                    </div>

                    <button type="submit" name="añadir_jugador" class="btn">
                        ⚽ Añadir Jugador
                    </button>
                </form>
            </div>

            <!-- Formulario para añadir profesional -->
            <div class="card">
                <h2>➕ Añadir Profesional al Equipo</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email_profesional">Email del Profesional:</label>
                        <input type="email" id="email_profesional" name="email_profesional"
                            required placeholder="profesional@ejemplo.com">
                        <small>Introduce el email del profesional que quieres añadir al equipo.</small>
                    </div>

                    <button type="submit" name="añadir_profesional" class="btn">
                        👨‍⚕️ Añadir Profesional
                    </button>
                </form>
            </div>

            <!-- Lista de jugadores -->
            <div class="card">
                <h2>⚽ Jugadores del Equipo (<?php echo count($jugadores_equipo); ?>)</h2>

                <?php if (count($jugadores_equipo) > 0): ?>
                    <div class="members-grid">
                        <?php foreach ($jugadores_equipo as $jugador): ?>
                            <div class="member-card jugador">
                                <h4><?php echo htmlspecialchars($jugador['nombre']); ?></h4>
                                <p>📧 <?php echo htmlspecialchars($jugador['email']); ?></p>
                                <?php if ($jugador['dorsal']): ?>
                                    <p>🔢 Dorsal: <?php echo htmlspecialchars($jugador['dorsal']); ?></p>
                                <?php endif; ?>
                                <?php if ($jugador['posicion']): ?>
                                    <p>📍 Posición: <?php echo htmlspecialchars($jugador['posicion']); ?></p>
                                <?php endif; ?>

                                <button onclick="confirmarEliminacion(<?php echo $jugador['id']; ?>, 'jugador')"
                                    class="eliminar-btn">
                                    ✗ Eliminar del equipo
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #718096; padding: 20px;">
                        No hay jugadores en el equipo. Añade jugadores usando el formulario superior.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Lista de profesionales -->
            <div class="card">
                <h2>👨‍⚕️ Profesionales del Equipo (<?php echo count($profesionales_equipo); ?>)</h2>

                <?php if (count($profesionales_equipo) > 0): ?>
                    <div class="members-grid">
                        <?php foreach ($profesionales_equipo as $profesional): ?>
                            <div class="member-card profesional">
                                <h4><?php echo htmlspecialchars($profesional['nombre']); ?></h4>
                                <p>📧 <?php echo htmlspecialchars($profesional['email']); ?></p>
                                <p>🎯 Rol: Profesional de apoyo</p>

                                <button onclick="confirmarEliminacion(<?php echo $profesional['id']; ?>, 'profesional')"
                                    class="eliminar-btn">
                                    ✗ Eliminar del equipo
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #718096; padding: 20px;">
                        No hay profesionales en el equipo. Añade profesionales usando el formulario superior.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

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
        function confirmarEliminacion(id, tipo) {
            if (confirm(`¿Estás seguro de eliminar este ${tipo} del equipo?`)) {
                if (tipo === 'jugador') {
                    window.location.href = `?eliminar_jugador=${id}`;
                } else {
                    window.location.href = `?eliminar_profesional=${id}`;
                }
            }
        }

        // Auto-ocultar mensajes después de 5 segundos
        setTimeout(() => {
            const mensajes = document.querySelectorAll('.mensaje');
            mensajes.forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.5s';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>

</html>

<?php
$conexion->close();
?>