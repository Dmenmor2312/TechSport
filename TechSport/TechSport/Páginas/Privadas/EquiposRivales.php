<?php
// EquiposRivales.php
session_start();

// Incluir conexión a base de datos - según tu estructura
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario'])) {
    header("Location: ../Publica/login.php");
    exit();
}

// Obtener información del usuario desde la sesión
$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['tipo_usuario'];
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
$email_usuario = $_SESSION['email'] ?? '';

// Obtener parámetros de búsqueda
$equipo_buscado_id = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;
$termino_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Inicializar variables
$equipo_usuario = null;
$mensaje_exito = '';
$mensaje_error = '';

// Obtener la temporada actual (la más reciente por fecha de inicio)
$sql_temporada = "SELECT id FROM temporadas ORDER BY fecha_inicio DESC LIMIT 1";
$result_temporada = $conn->query($sql_temporada);
if ($result_temporada && $result_temporada->num_rows > 0) {
    $temporada_actual = $result_temporada->fetch_assoc()['id'];
} else {
    $temporada_actual = 1; // Valor por defecto
}

// Obtener el equipo del usuario actual (si es entrenador)
if ($tipo_usuario == 'entrenador') {
    // Primero verificar si hay tabla equipos_entrenadores
    $sql_check = "SHOW TABLES LIKE 'equipos_entrenadores'";
    $result_check = $conn->query($sql_check);

    if ($result_check && $result_check->num_rows > 0) {
        // Usar equipos_entrenadores
        $sql_equipo_usuario = "SELECT e.id, e.nombre 
                              FROM equipos_entrenadores ee
                              JOIN equipos e ON ee.equipo_id = e.id
                              WHERE ee.entrenador_id = ? AND ee.activo = TRUE
                              AND e.temporada_id = ?";
    } else {
        // Usar equipos directamente
        $sql_equipo_usuario = "SELECT e.id, e.nombre 
                              FROM equipos e 
                              WHERE e.entrenador_id = ? 
                              AND e.temporada_id = ?";
    }

    $stmt = $conn->prepare($sql_equipo_usuario);
    if ($stmt) {
        $stmt->bind_param("ii", $usuario_id, $temporada_actual);
        $stmt->execute();
        $equipo_usuario_result = $stmt->get_result();
        if ($equipo_usuario_result && $equipo_usuario_result->num_rows > 0) {
            $equipo_usuario = $equipo_usuario_result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Procesar envío de email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_email'])) {
    $mensaje_exito = "Mensaje preparado para enviar a: " . htmlspecialchars($_POST['para_email'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechSport - Scouting de Equipos Rivales</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <style>
        /* Estilos generales */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            background: lwhite;
            color: black;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .logo p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .user-panel {
            text-align: right;
        }

        .user-panel p {
            margin: 3px 0;
            font-size: 0.9rem;
        }

        .logout-btn {
            display: inline-block;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            margin-top: 8px;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* Sección de búsqueda */
        .search-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .search-section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
        }

        .search-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: background 0.3s;
        }

        .search-btn:hover {
            background: #2980b9;
        }

        /* RESULTADOS DE BÚSQUEDA */
        .search-results {
            margin-bottom: 30px;
        }

        .results-title {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.3rem;
            padding-left: 10px;
        }

        /* Grid de tarjetas compactas para equipos */
        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        /* Tarjeta de equipo - MODIFICADA */
        .team-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            /* REMOVER: height: 120px; */
            min-height: 120px;
            /* Cambiar a min-height */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .team-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
            border-color: #3498db;
        }

        .team-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.1rem;
            line-height: 1.3;

        }

        .team-info {
            color: #666;
            font-size: 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .team-info span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Detalles del equipo */
        .team-details {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .team-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f3f5;
            flex-wrap: wrap;
            gap: 15px;
        }

        .team-title h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .team-title p {
            color: #666;
            font-size: 1rem;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background 0.3s;
            white-space: nowrap;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        /* Secciones */
        .section-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 12px;
            margin: 40px 0 25px 0;
            font-size: 1.4rem;
            font-weight: 600;
        }

        /* Stats Grid para entrenador */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            background: #f1f3f5;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }

        /* TABLA DE JUGADORES - MÁS ESPACIADA */
        .players-table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin: 25px 0;
            border: 1px solid #e9ecef;
        }

        .players-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .players-table thead {
            background: #495057;
        }

        .players-table th {
            color: white;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            border-bottom: 3px solid #3498db;
        }

        .players-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .players-table tbody tr:hover {
            background: #f8f9fa;
            transition: background 0.2s;
        }

        .players-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Información del jugador - MÁS ESPACIADA */
        .player-info {
            min-width: 250px;
        }

        .player-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
            display: block;
        }

        .player-details {
            font-size: 0.9rem;
            color: #6c757d;
            line-height: 1.5;
        }

        .player-details span {
            display: inline-block;
            margin-right: 15px;
        }

        /* Badges de posición - TAMAÑO NORMAL */
        .position-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            min-width: 110px;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .portero {
            background: #f39c12;
            color: white;
        }

        .defensa {
            background: #3498db;
            color: white;
        }

        .centrocampista {
            background: #2ecc71;
            color: white;
        }

        .delantero {
            background: #e74c3c;
            color: white;
        }

        /* Estadísticas del jugador - MEJOR FORMATEADO */
        .player-stats {
            min-width: 200px;
        }

        .stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 8px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }

        .stat-label-small {
            font-weight: 600;
            color: #495057;
            min-width: 25px;
        }

        .stat-value-small {
            color: #2c3e50;
            font-weight: 500;
        }

        /* Botones de contacto */
        .contact-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .contact-btn:hover {
            background: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .contact-btn i {
            font-size: 0.9rem;
        }

        .email-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .email-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        /* Alertas */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 0.95rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .teams-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .user-panel {
                text-align: center;
            }

            .search-form {
                flex-direction: column;
            }

            .search-input {
                min-width: 100%;
            }

            .teams-grid {
                grid-template-columns: 1fr;
            }

            .team-card {
                height: auto;
                min-height: 110px;
            }

            .team-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .back-btn {
                align-self: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            /* Tabla responsive para móviles */
            .players-table-container {
                overflow-x: auto;
            }

            .players-table {
                min-width: 800px;
                /* Para que sea scrollable en móviles */
            }

            .players-table th,
            .players-table td {
                padding: 14px 16px;
            }

            .contact-btn {
                padding: 8px 14px;
                font-size: 0.85rem;
            }

            .position-badge {
                padding: 5px 12px;
                font-size: 0.85rem;
                min-width: 100px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            .search-section {
                padding: 20px;
            }

            .team-details {
                padding: 20px;
            }

            .section-title {
                font-size: 1.2rem;
                margin: 30px 0 20px 0;
            }

            .players-table th,
            .players-table td {
                padding: 12px 14px;
                font-size: 0.9rem;
            }
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
                <?php
                // Detectar página actual para resaltar
                $pagina_actual = $_SERVER['PHP_SELF'];

                // Función para verificar si es la página activa
                function esActivo($url, $pagina_actual)
                {
                    return strpos($pagina_actual, $url) !== false ? 'active' : '';
                }
                ?>

                <!-- Jugador -->
                <?php if ($tipo_usuario == 'jugador'): ?>
                    <li><a class="nav-link <?php echo esActivo('/Jugador/inicio.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Jugador/inicio.php">Inicio</a></li>
                    <li><a class="nav-link <?php echo esActivo('/Jugador/Estadisticas.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Jugador/Estadisticas.php">Estadísticas</a></li>
                    <li><a class="nav-link <?php echo esActivo('/Jugador/Encuestas.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Jugador/Encuestas.php">Encuestas</a></li>
                    <li><a class="nav-link <?php echo esActivo('/EquiposRivales.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipos Rivales</a></li>
                    <li><a class="nav-link <?php echo esActivo('/Convocatoria.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Convocatoria.php">Convocatoria</a></li>

                    <!-- Profesional -->
                <?php elseif ($tipo_usuario == 'profesional'): ?>
                    <li><a class="nav-link <?php echo esActivo('/Profesional/inicio.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Profesional/inicio.php">Inicio</a></li>
                    <li><a class="nav-link <?php echo esActivo('/Estadisticas.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Estadisticas.php">Estadísticas</a></li>
                    <li><a class="nav-link <?php echo esActivo('/EquipoRivales.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/EquipoRivales.php">Equipos Rivales</a></li>
                    <li><a class="nav-link <?php echo esActivo('/Convocatoria.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Convocatoria.php">Convocatoria</a></li>

                    <!-- Entrenador -->
                <?php elseif ($tipo_usuario == 'entrenador'): ?>
                    <li><a class="nav-link <?php echo esActivo('/Entrenador/inicio.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Entrenador/inicio.php">Inicio</a></li>
                    <li><a class="nav-link <?php echo esActivo('/Estadisticas.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Estadisticas.php">Estadísticas</a></li>
                    <li><a class="nav-link <?php echo esActivo('/EquiposRivales.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipos Rivales</a></li>
                    <li><a class="nav-link <?php echo esActivo('/CrearEncuestas.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Entrenador/CrearEncuestas.php">Crear Encuesta</a></li>
                    <li><a class="nav-link <?php echo esActivo('/Convocatoria.php', $pagina_actual); ?>"
                            href="/TechSport/Páginas/Privadas/Entrenador/Convocatoria.php">Convocatoria</a></li>
                <?php endif; ?>

                <!-- Enlace común para todos -->
                <li><a class="nav-link" href="/TechSport/LogicaPHP/logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <h1>🔍 Scouting</h1>
                <p>Sistema de análisis de equipos rivales</p>
            </div>
            <div class="user-panel">
                <p><strong>Usuario:</strong> <?php echo htmlspecialchars($nombre_usuario); ?></p>
                <p><strong>Rol:</strong> <?php echo htmlspecialchars($tipo_usuario); ?></p>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Mostrar alertas -->
        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success">
                <?php echo $mensaje_exito; ?>
            </div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
            <div class="alert alert-error">
                <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>

        <!-- Sección de búsqueda -->
        <div class="search-section">
            <h2>Buscar Equipos Rivales</h2>
            <p>Ingresa el nombre del equipo o del entrenador para buscar</p>

            <form method="GET" action="" class="search-form">
                <input type="text"
                    name="busqueda"
                    class="search-input"
                    placeholder="Ej: Real Madrid o nombre del entrenador..."
                    value="<?php echo htmlspecialchars($termino_busqueda); ?>"
                    required>
                <button type="submit" class="search-btn">
                    🔍 Buscar Equipo
                </button>
            </form>

            <?php if ($equipo_usuario): ?>
                <p style="margin-top: 15px; color: #666; font-style: italic;">
                    ⓘ Tu equipo actual: <strong><?php echo htmlspecialchars($equipo_usuario['nombre']); ?></strong>
                </p>
            <?php endif; ?>
        </div>

        <?php
        // Mostrar resultados de búsqueda
        if (!empty($termino_busqueda) && !$equipo_buscado_id):
            // Buscar equipos que no sean el del usuario
            $excluir_id = $equipo_usuario ? $equipo_usuario['id'] : 0;

            $sql = "SELECT e.id, e.nombre, u.nombre as entrenador_nombre, 
                   u.email as entrenador_email,
                   t.nombre as temporada
            FROM equipos e
            JOIN usuarios u ON e.entrenador_id = u.id
            JOIN temporadas t ON e.temporada_id = t.id
            WHERE (e.nombre LIKE ? OR u.nombre LIKE ?)";

            if ($excluir_id > 0) {
                $sql .= " AND e.id != ?";
            }

            $sql .= " ORDER BY e.nombre";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $termino_like = "%" . $termino_busqueda . "%";

                if ($excluir_id > 0) {
                    $stmt->bind_param("ssi", $termino_like, $termino_like, $excluir_id);
                } else {
                    $stmt->bind_param("ss", $termino_like, $termino_like);
                }

                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0):
        ?>
                    <div class="search-results">
                        <h3 class="results-title">Resultados de búsqueda (<?php echo $result->num_rows; ?> equipos)</h3>

                        <div class="teams-grid">
                            <?php while ($equipo = $result->fetch_assoc()): ?>
                                <div class="team-card" onclick="window.location.href='?busqueda=<?php echo urlencode($termino_busqueda); ?>&equipo_id=<?php echo $equipo['id']; ?>'">
                                    <h3><?php echo htmlspecialchars($equipo['nombre']); ?></h3>
                                    <div class="team-info">
                                        <span>👤 <?php echo htmlspecialchars($equipo['entrenador_nombre']); ?></span>
                                        <span>📅 <?php echo htmlspecialchars($equipo['temporada']); ?></span>
                                        <span>📧 <?php echo htmlspecialchars($equipo['entrenador_email']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="search-results">
                        <div class="alert" style="background: #f8f9fa; padding: 30px; text-align: center; border-radius: 8px;">
                            <p style="color: #666; font-size: 1.1rem;">
                                🔍 No se encontraron equipos con el criterio: <strong>"<?php echo htmlspecialchars($termino_busqueda); ?>"</strong>
                            </p>
                            <p style="margin-top: 15px; color: #7f8c8d;">
                                Intenta con otro nombre.
                            </p>
                        </div>
                    </div>
        <?php
                endif;
                $stmt->close();
            } else {
                echo '<div class="alert alert-error">Error en la consulta de búsqueda.</div>';
            }
        endif;
        ?>

        <?php
        // Mostrar detalles del equipo seleccionado
        if ($equipo_buscado_id > 0):
            // Obtener información completa del equipo
            $sql_equipo = "SELECT e.id, e.nombre as nombre_equipo, 
                      u.id as id_entrenador, u.nombre, u.email as email_entrenador,
                      t.nombre as temporada,
                      ent.titulo, 
                      DATE_FORMAT(ent.fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento_entrenador
               FROM equipos e
               JOIN usuarios u ON e.entrenador_id = u.id
               LEFT JOIN entrenadores ent ON u.id = ent.usuario_id
               JOIN temporadas t ON e.temporada_id = t.id
               WHERE e.id = ?";

            $stmt = $conn->prepare($sql_equipo);
            if ($stmt) {
                $stmt->bind_param("i", $equipo_buscado_id);
                $stmt->execute();
                $equipo_info_result = $stmt->get_result();

                if ($equipo_info_result && $equipo_info_result->num_rows > 0):
                    $equipo_info = $equipo_info_result->fetch_assoc();
        ?>

                    <div class="team-details">
                        <div class="team-header">
                            <div class="team-title">
                                <h2><?php echo htmlspecialchars($equipo_info['nombre_equipo']); ?></h2>
                                <p style="color: #666; margin-top: 5px;">
                                    📅 Temporada: <?php echo htmlspecialchars($equipo_info['temporada']); ?>
                                </p>
                            </div>
                            <a href="?busqueda=<?php echo urlencode($termino_busqueda); ?>" class="back-btn">
                                ↩️ Volver a resultados
                            </a>
                        </div>

                        <!-- Información del Entrenador -->
                        <h3 class="section-title">Información del Entrenador</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo htmlspecialchars($equipo_info['nombre']); ?></div>
                                <div class="stat-label">Nombre del entrenador</div>
                            </div>

                            <?php if (!empty($equipo_info['titulo'])): ?>
                                <div class="stat-card">
                                    <div class="stat-value">🎓 <?php echo htmlspecialchars($equipo_info['titulo']); ?></div>
                                    <div class="stat-label">Título profesional</div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($equipo_info['fecha_nacimiento_entrenador'])): ?>
                                <div class="stat-card">
                                    <div class="stat-value">🎂 <?php echo htmlspecialchars($equipo_info['fecha_nacimiento_entrenador']); ?></div>
                                    <div class="stat-label">Fecha de nacimiento</div>
                                </div>
                            <?php endif; ?>

                            <div class="stat-card">
                                <div class="stat-value">📧 <?php echo htmlspecialchars($equipo_info['email_entrenador']); ?></div>
                                <div class="stat-label">Email de contacto</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-value">
                                    <button class="contact-btn"
                                        onclick="openModal('<?php echo htmlspecialchars($equipo_info['email_entrenador']); ?>', '<?php echo htmlspecialchars($equipo_info['nombre']); ?>')">
                                        📧 Contactar Entrenador
                                    </button>
                                </div>
                                <div class="stat-label">Enviar mensaje</div>
                            </div>
                        </div>
                        <!-- Profesionales del Equipo -->
                        <h3 class="section-title">Cuerpo Técnico y Profesionales</h3>

                        <?php
                        // Obtener profesionales del equipo
                        $sql_profesionales = "SELECT up.id, up.nombre, up.email,
                                         p.cargo, DATE_FORMAT(p.fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento,
                                         p.nacionalidad
                                  FROM equipo_profesionales ep
                                  JOIN usuarios up ON ep.profesional_id = up.id
                                  JOIN profesionales p ON up.id = p.usuario_id
                                  WHERE ep.equipo_id = ?
                                  ORDER BY p.cargo";

                        $stmt3 = $conn->prepare($sql_profesionales);
                        if ($stmt3) {
                            $stmt3->bind_param("i", $equipo_buscado_id);
                            $stmt3->execute();
                            $profesionales = $stmt3->get_result();

                            if ($profesionales && $profesionales->num_rows > 0):
                        ?>

                                <div class="stats-grid">
                                    <?php while ($profesional = $profesionales->fetch_assoc()): ?>
                                        <div class="stat-card">
                                            <div class="stat-value">👨‍⚕️ <?php echo htmlspecialchars($profesional['nombre']); ?></div>
                                            <div class="stat-label"><strong><?php echo htmlspecialchars($profesional['cargo']); ?></strong></div>
                                            <div style="margin-top: 10px; font-size: 0.9rem; color: #666;">
                                                <p>🎂 <?php echo htmlspecialchars($profesional['fecha_nacimiento']); ?></p>
                                                <p>🌍 <?php echo htmlspecialchars($profesional['nacionalidad']); ?></p>
                                                <p>📧 <?php echo htmlspecialchars($profesional['email']); ?></p>
                                                <p style="margin-top: 10px;">
                                                    <button class="contact-btn" style="padding: 5px 10px; font-size: 0.8rem;"
                                                        onclick="openModal('<?php echo htmlspecialchars($profesional['email']); ?>', '<?php echo htmlspecialchars($profesional['nombre']); ?>')">
                                                        📧 Contactar
                                                    </button>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>

                            <?php
                            else: ?>
                                <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px;">
                                    <p style="color: #666;">
                                        🩺 No hay profesionales registrados en este equipo.
                                    </p>
                                </div>
                        <?php
                            endif;
                            $stmt3->close();
                        } else {
                            echo '<div class="alert alert-error">Error al cargar profesionales.</div>';
                        }
                        ?>
                        <!-- Jugadores del Equipo -->
                        <h3 class="section-title">Plantilla de Jugadores</h3>

                        <?php
                        // Obtener jugadores del equipo para esta temporada
                        $sql_jugadores = "SELECT j.id, j.usuario_id, j.posicion, j.dorsal, 
                                     DATE_FORMAT(j.fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento,
                                     j.nacionalidad, j.altura, j.peso,
                                     u.nombre, u.email,
                                     COALESCE(ep.partidos_jugados, 0) as partidos_jugados,
                                     COALESCE(ep.goles, 0) as goles,
                                     COALESCE(ep.asistencias, 0) as asistencias,
                                     COALESCE(ep.amarillas, 0) as amarillas,
                                     COALESCE(ep.rojas, 0) as rojas,
                                     COALESCE(ep.porterias_cero, 0) as porterias_cero,
                                     COALESCE(ep.minutos_jugados, 0) as minutos_jugados
                              FROM equipo_jugadores ej
                              JOIN jugadores j ON ej.jugador_id = j.id
                              JOIN usuarios u ON j.usuario_id = u.id
                              LEFT JOIN estadisticas_partido ep ON j.id = ep.jugador_id 
                                  AND ep.equipo_id = ? 
                                  AND ep.temporada_id = ?
                              WHERE ej.equipo_id = ? AND ej.activo = TRUE
                              ORDER BY 
                                  CASE j.posicion
                                      WHEN 'Portero' THEN 1
                                      WHEN 'Defensa' THEN 2
                                      WHEN 'Centrocampista' THEN 3
                                      WHEN 'Delantero' THEN 4
                                      ELSE 5
                                  END,
                                  j.dorsal";

                        $stmt2 = $conn->prepare($sql_jugadores);
                        if ($stmt2) {
                            $stmt2->bind_param("iii", $equipo_buscado_id, $temporada_actual, $equipo_buscado_id);
                            $stmt2->execute();
                            $jugadores = $stmt2->get_result();

                            if ($jugadores && $jugadores->num_rows > 0):
                        ?>

                                <table class="players-table">
                                    <thead>
                                        <tr>
                                            <th>Dorsal</th>
                                            <th>Jugador</th>
                                            <th>Posición</th>
                                            <th>Estadísticas</th>
                                            <th>Contacto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($jugador = $jugadores->fetch_assoc()):
                                            $clase_posicion = strtolower($jugador['posicion']);
                                            if ($clase_posicion == 'mc') $clase_posicion = 'centrocampista';
                                        ?>
                                            <tr>
                                                <td style="font-weight: bold; text-align: center; width: 70px;">
                                                    <?php echo $jugador['dorsal'] ?: '-'; ?>
                                                </td>

                                                <td class="player-info">
                                                    <span class="player-name"><?php echo htmlspecialchars($jugador['nombre']); ?></span>
                                                    <div class="player-details">
                                                        <span>🎂 <?php echo htmlspecialchars($jugador['fecha_nacimiento']); ?></span>
                                                        <?php if (!empty($jugador['nacionalidad'])): ?>
                                                            <span>🌍 <?php echo htmlspecialchars($jugador['nacionalidad']); ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($jugador['altura'])): ?>
                                                            <span>📏 <?php echo htmlspecialchars($jugador['altura']); ?>m</span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($jugador['peso'])): ?>
                                                            <span>⚖️ <?php echo htmlspecialchars($jugador['peso']); ?>kg</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>

                                                <td style="width: 130px;">
                                                    <span class="position-badge <?php echo $clase_posicion; ?>">
                                                        <?php echo htmlspecialchars($jugador['posicion']); ?>
                                                    </span>
                                                </td>

                                                <td class="player-stats">
                                                    <div class="stats-row">
                                                        <div class="stat-item">
                                                            <span class="stat-label-small">PJ:</span>
                                                            <span class="stat-value-small"><?php echo $jugador['partidos_jugados']; ?></span>
                                                        </div>
                                                        <div class="stat-item">
                                                            <span class="stat-label-small">G:</span>
                                                            <span class="stat-value-small"><?php echo $jugador['goles']; ?></span>
                                                        </div>
                                                        <div class="stat-item">
                                                            <span class="stat-label-small">A:</span>
                                                            <span class="stat-value-small"><?php echo $jugador['asistencias']; ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="stats-row">
                                                        <div class="stat-item">
                                                            <span class="stat-label-small">🟡:</span>
                                                            <span class="stat-value-small"><?php echo $jugador['amarillas']; ?></span>
                                                        </div>
                                                        <div class="stat-item">
                                                            <span class="stat-label-small">🔴:</span>
                                                            <span class="stat-value-small"><?php echo $jugador['rojas']; ?></span>
                                                        </div>
                                                        <?php if (in_array($jugador['posicion'], ['Portero', 'Defensa'])): ?>
                                                            <div class="stat-item">
                                                                <span class="stat-label-small">PC:</span>
                                                                <span class="stat-value-small"><?php echo $jugador['porterias_cero']; ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($jugador['minutos_jugados'] > 0): ?>
                                                        <div class="stats-row">
                                                            <div class="stat-item">
                                                                <span class="stat-label-small">⏱️ Min:</span>
                                                                <span class="stat-value-small"><?php echo number_format($jugador['minutos_jugados']); ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>

                                                <td style="width: 120px;">
                                                    <button class="contact-btn contact-player"
                                                        onclick="openModal('<?php echo htmlspecialchars($jugador['email']); ?>', '<?php echo htmlspecialchars($jugador['nombre']); ?>')">
                                                        📧 Contactar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>

                            <?php
                            else: ?>
                                <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px;">
                                    <p style="color: #666;">
                                        👥 No hay jugadores registrados en este equipo para la temporada actual.
                                    </p>
                                </div>
                        <?php
                            endif;
                            $stmt2->close();
                        } else {
                            echo '<div class="alert alert-error">Error al cargar jugadores.</div>';
                        }
                        ?>

                    </div>

                <?php
                else: ?>
                    <div class="alert alert-error">
                        <p>Error: No se encontró el equipo solicitado.</p>
                        <a href="?busqueda=<?php echo urlencode($termino_busqueda); ?>" style="color: #721c24; text-decoration: underline;">
                            ↩️ Volver a resultados de búsqueda
                        </a>
                    </div>
        <?php
                endif;
                $stmt->close();
            } else {
                echo '<div class="alert alert-error">Error en la consulta del equipo.</div>';
            }
        endif;

        // Cerrar conexión
        $conn->close();
        ?>
    </div>

    <!-- Modal de Contacto -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">📧 Enviar Email de Contacto</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="contactForm" method="POST">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="de_nombre">Tu Nombre:</label>
                    <input type="text" id="de_nombre" name="de_nombre" required
                        value="<?php echo htmlspecialchars($nombre_usuario); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="de_email">Tu Email:</label>
                    <input type="email" id="de_email" name="de_email" required
                        value="<?php echo htmlspecialchars($email_usuario); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="para_email">Destinatario:</label>
                    <input type="email" id="para_email" name="para_email" readonly required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="para_nombre">Nombre del destinatario:</label>
                    <input type="text" id="para_nombre" name="para_nombre" readonly required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="asunto">Asunto:</label>
                    <input type="text" id="asunto" name="asunto" required
                        value="Consulta desde TechSport Scouting" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="mensaje">Mensaje:</label>
                    <textarea id="mensaje" name="mensaje" rows="6" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">Estimado/a,

Me pongo en contacto con usted a través del sistema de scouting de TechSport.

Tras revisar la ficha técnica de su equipo, me gustaría...

Atentamente,
<?php echo htmlspecialchars($nombre_usuario); ?></textarea>
                </div>

                <input type="hidden" name="enviar_email" value="1">
                <button type="submit" class="submit-btn" style="background: #3498db; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold;">
                    📤 Preparar Email para Envío
                </button>
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
            <div class="footer-bottom">
                <p>© 2025 TechSport. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
    <script>
        // Funciones para el modal de contacto
        function openModal(email, nombre) {
            document.getElementById('para_email').value = email;
            document.getElementById('para_nombre').value = nombre;
            document.getElementById('modalTitle').textContent = '📧 Contactar a: ' + nombre;
            document.getElementById('contactModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('contactModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('contactModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Manejar envío del formulario
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Mostrar mensaje de confirmación
            alert('✅ Email preparado para enviar a:\n\n' +
                'Destinatario: ' + document.getElementById('para_nombre').value + '\n' +
                'Email: ' + document.getElementById('para_email').value + '\n\n' +
                'En un entorno de producción, este email se enviaría automáticamente.\n' +
                'Para desarrollo, puedes copiar el contenido y enviarlo manualmente.');

            // Cerrar modal
            closeModal();
        });
    </script>
</body>

</html>