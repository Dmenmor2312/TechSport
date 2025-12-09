<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    header("Location: /TechSport/LogicaPHP/login.php");
    exit();
}

$rol_id = $_SESSION['rol_id'];
$usuario_id = $_SESSION['usuario_id'];

// Solo jugadores (2) y profesionales (3) pueden ver esta página
if (!in_array($rol_id, [2, 3])) {
    header("Location: /TechSport/Páginas/Privadas/Entrenador/inicio.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';

// AÑADE ESTA FUNCIÓN SI NO EXISTE
function obtenerEquipoUsuario($conn, $usuario_id, $rol_id)
{
    if ($rol_id == 2) { // Jugador
        $stmt = $conn->prepare("
            SELECT e.id, e.nombre 
            FROM equipos e
            JOIN equipo_jugadores ej ON e.id = ej.equipo_id
            JOIN jugadores j ON ej.jugador_id = j.id
            WHERE j.usuario_id = ? AND ej.activo = TRUE
        ");
        $stmt->bind_param("i", $usuario_id);
    } elseif ($rol_id == 3) { // Profesional
        $stmt = $conn->prepare("
        SELECT e.id, e.nombre, e.temporada_id 
        FROM equipos e
        JOIN equipo_profesionales ep ON e.id = ep.equipo_id
        WHERE ep.profesional_id = ? 
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
    $conn->close();
    echo "<script>alert('No tienes un equipo asignado.'); window.location.href = '/TechSport/Páginas/Privadas/" . ($rol_id == 2 ? "Jugador" : "Profesional") . "/inicio.php';</script>";
    exit();
}

// Si es jugador, verificar si está convocado en la convocatoria específica
$jugador_convocado = false;
$mi_tipo = null;
$mi_dorsal = null;
$mi_posicion = null;

if ($rol_id == 2 && isset($_GET['id'])) {
    $convocatoria_id = intval($_GET['id']);
    
    // Obtener ID del jugador a partir del usuario_id
    $stmt = $conn->prepare("SELECT id FROM jugadores WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        $jugador_id = $result['id'];
        
        // Verificar si está en la convocatoria
        $stmt = $conn->prepare("SELECT tipo, dorsal, posicion FROM convocados WHERE convocatoria_id = ? AND jugador_id = ?");
        $stmt->bind_param("ii", $convocatoria_id, $jugador_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            $jugador_convocado = true;
            $mi_tipo = $result['tipo'];
            $mi_dorsal = $result['dorsal'];
            $mi_posicion = $result['posicion'];
        }
    }
}

// Obtener convocatorias publicadas del equipo
$stmt = $conn->prepare("
    SELECT c.*, e.titulo as partido_titulo, e.fecha as partido_fecha,
           t.nombre as temporada_nombre
    FROM convocatorias c
    JOIN eventos e ON c.evento_id = e.id
    JOIN temporadas t ON c.temporada_id = t.id
    WHERE c.equipo_id = ?
    AND c.estado = 'publicada'
    AND e.fecha > NOW()
    ORDER BY e.fecha ASC
    LIMIT 5
");
$stmt->bind_param("i", $equipo['id']);
$stmt->execute();
$convocatorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Si hay una convocatoria específica solicitada
$convocatoria_id = intval($_GET['id'] ?? 0);
$convocatoria_detalle = null;

if ($convocatoria_id > 0) {
    $stmt = $conn->prepare("
        SELECT c.*, e.titulo as partido_titulo, e.fecha as partido_fecha,
               t.nombre as temporada_nombre
        FROM convocatorias c
        JOIN eventos e ON c.evento_id = e.id
        JOIN temporadas t ON c.temporada_id = t.id
        WHERE c.id = ? 
        AND c.equipo_id = ?
        AND c.estado = 'publicada'
    ");
    $stmt->bind_param("ii", $convocatoria_id, $equipo['id']);
    $stmt->execute();
    $convocatoria_detalle = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($convocatoria_detalle) {
        // Si es jugador, solo ver si está convocado
        if ($rol_id == 2) {
            // Ya tenemos la información del jugador más arriba
        } 
        // Si es profesional, ver todos los jugadores convocados
        elseif ($rol_id == 3) {
            $stmt = $conn->prepare("
                SELECT cj.*, j.*, u.nombre as usuario_nombre, u.apellidos
                FROM convocados cj
                JOIN jugadores j ON cj.jugador_id = j.id
                JOIN usuarios u ON j.usuario_id = u.id
                WHERE cj.convocatoria_id = ?
                ORDER BY 
                    CASE cj.tipo 
                        WHEN 'titular' THEN 1
                        WHEN 'suplente' THEN 2
                        WHEN 'reserva' THEN 3
                        ELSE 4
                    END,
                    cj.dorsal,
                    u.nombre
            ");
            $stmt->bind_param("i", $convocatoria_id);
            $stmt->execute();
            $convocatoria_detalle['jugadores'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TechSport - Convocatorias</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Compartidos/estilos.css" />
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <style>
        .convocatoria-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }

        .page-title {
            color: #2d3748;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #1a2a6c;
            transition: all 0.3s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }

        .card-subtitle {
            color: #718096;
            margin: 5px 0 15px 0;
        }

        .card-date {
            background: #ebf8ff;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #2b6cb0;
            font-weight: 600;
        }

        .btn-primary {
            background: #1a2a6c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 42, 108, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .convocados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .convocado-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            border: 2px solid #e2e8f0;
        }

        .convocado-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .convocado-nombre {
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }

        .tipo-badge {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .tipo-titular {
            background: #d1fae5;
            color: #065f46;
        }

        .tipo-suplente {
            background: #fef3c7;
            color: #92400e;
        }

        .tipo-reserva {
            background: #e0e7ff;
            color: #3730a3;
        }

        .convocado-dorsal {
            background: #1a2a6c;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .convocado-posicion {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .notas-box {
            background: #fff7ed;
            border-left: 4px solid #ed8936;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .notas-box h4 {
            margin: 0 0 10px 0;
            color: #9c4221;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4a5568;
            text-decoration: none;
        }

        .back-link:hover {
            color: #1a2a6c;
            text-decoration: underline;
        }

        .mi-convocatoria {
            background: #f0f9ff;
            border: 2px solid #38bdf8;
        }

        .mi-info-convocado {
            background: #1a2a6c;
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
        }

        .mi-info-convocado h3 {
            margin: 0 0 10px 0;
            font-size: 1.5rem;
        }

        .mi-info-convocado p {
            margin: 5px 0;
            font-size: 1.1rem;
        }

        .no-convocado-box {
            background: #fef2f2;
            border: 2px solid #fecaca;
            color: #991b1b;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .convocados-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .card-header {
                flex-direction: column;
            }
        }
    </style>
</head>

<body data-rol="<?php echo $rol_id == 2 ? 'jugador' : 'profesional'; ?>">
    <!-- HEADER -->
    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" id="logo-interactivo" />
        </div>
        <nav class="navbar">
            <ul class="nav-menu">
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/<?php echo $rol_id == 2 ? 'Jugador' : 'Profesional'; ?>/inicio.php">Inicio</a></li>
                <?php if ($rol_id == 2): ?>
                    <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Jugador/Estadisticas.php">Estadísticas</a></li>
                    <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Jugador/Encuestas.php">Encuestas</a></li>
                <?php endif; ?>
                <?php if ($rol_id == 3): ?>
                    <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Estadisticas.php">Estadísticas</a></li>
                <?php endif; ?>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipos Rivales</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Convocatoria.php">Convocatoria</a></li>
                <li><a class="nav-link" href="/TechSport/LogicaPHP/logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main class="convocatoria-container">
        <h1 class="page-title">📋 Convocatorias del Equipo</h1>

        <?php if ($convocatoria_detalle): ?>
            <!-- Vista detallada de una convocatoria -->
            <a href="Convocatoria.php" class="back-link">← Volver a todas las convocatorias</a>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title"><?php echo htmlspecialchars($convocatoria_detalle['partido_titulo']); ?></h2>
                        <p class="card-subtitle">
                            <?php echo (new DateTime($convocatoria_detalle['partido_fecha']))->format('d/m/Y H:i'); ?> •
                            Temporada: <?php echo htmlspecialchars($convocatoria_detalle['temporada_nombre']); ?>
                        </p>
                    </div>
                    <div class="card-date">
                        Convocatoria publicada
                    </div>
                </div>

                <?php if ($convocatoria_detalle['notas']): ?>
                    <div class="notas-box">
                        <h4>📝 Notas del entrenador:</h4>
                        <p><?php echo nl2br(htmlspecialchars($convocatoria_detalle['notas'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($rol_id == 2): // JUGADOR ?>
                    <h3 style="margin-top: 25px;">👤 Mi Convocatoria</h3>
                    
                    <?php if ($jugador_convocado): ?>
                        <div class="mi-info-convocado">
                            <h3>🎉 ¡Estás convocado!</h3>
                            <p><strong>Fecha del partido:</strong> <?php echo (new DateTime($convocatoria_detalle['partido_fecha']))->format('d/m/Y H:i'); ?></p>
                            <p><strong>Estado:</strong> CONVOCADO</p>
                            <p><em>Tu rol específico (titular, suplente o reserva) te lo comunicará el entrenador el día del partido.</em></p>
                        </div>
                    <?php else: ?>
                        <div class="no-convocado-box">
                            <h3>😔 No estás convocado</h3>
                            <p>No apareces en la lista de convocados para este partido.</p>
                            <p><em>Sigue entrenando duro para las próximas convocatorias.</em></p>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($rol_id == 3): // PROFESIONAL ?>
                    <h3 style="margin-top: 25px;">👥 Jugadores Convocados</h3>

                    <?php if (empty($convocatoria_detalle['jugadores'])): ?>
                        <div class="empty-state">
                            <p>No hay jugadores convocados en esta convocatoria.</p>
                        </div>
                    <?php else: ?>
                        <div class="convocados-grid">
                            <?php
                            // Separar por tipo
                            $titulares = array_filter($convocatoria_detalle['jugadores'], function ($j) {
                                return $j['tipo'] == 'titular';
                            });
                            $suplentes = array_filter($convocatoria_detalle['jugadores'], function ($j) {
                                return $j['tipo'] == 'suplente';
                            });
                            $reservas = array_filter($convocatoria_detalle['jugadores'], function ($j) {
                                return $j['tipo'] == 'reserva';
                            });
                            ?>

                            <?php if (!empty($titulares)): ?>
                                <div style="grid-column: 1 / -1;">
                                    <h4 style="color: #065f46; margin-bottom: 10px;">🏆 Titulares</h4>
                                </div>
                                <?php foreach ($titulares as $jugador): ?>
                                    <div class="convocado-item">
                                        <div class="convocado-header">
                                            <h5 class="convocado-nombre"><?php echo htmlspecialchars($jugador['usuario_nombre']); ?></h5>
                                            <?php if ($jugador['dorsal']): ?>
                                                <div class="convocado-dorsal"><?php echo $jugador['dorsal']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="tipo-badge tipo-titular">Titular</span>
                                        <?php if ($jugador['posicion']): ?>
                                            <p class="convocado-posicion"><?php echo htmlspecialchars($jugador['posicion']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($suplentes)): ?>
                                <div style="grid-column: 1 / -1; margin-top: 20px;">
                                    <h4 style="color: #92400e; margin-bottom: 10px;">🔄 Suplentes</h4>
                                </div>
                                <?php foreach ($suplentes as $jugador): ?>
                                    <div class="convocado-item">
                                        <div class="convocado-header">
                                            <h5 class="convocado-nombre"><?php echo htmlspecialchars($jugador['usuario_nombre']); ?></h5>
                                            <?php if ($jugador['dorsal']): ?>
                                                <div class="convocado-dorsal"><?php echo $jugador['dorsal']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="tipo-badge tipo-suplente">Suplente</span>
                                        <?php if ($jugador['posicion']): ?>
                                            <p class="convocado-posicion"><?php echo htmlspecialchars($jugador['posicion']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($reservas)): ?>
                                <div style="grid-column: 1 / -1; margin-top: 20px;">
                                    <h4 style="color: #3730a3; margin-bottom: 10px;">🛡️ Reservas</h4>
                                </div>
                                <?php foreach ($reservas as $jugador): ?>
                                    <div class="convocado-item">
                                        <div class="convocado-header">
                                            <h5 class="convocado-nombre"><?php echo htmlspecialchars($jugador['usuario_nombre']); ?></h5>
                                            <?php if ($jugador['dorsal']): ?>
                                                <div class="convocado-dorsal"><?php echo $jugador['dorsal']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="tipo-badge tipo-reserva">Reserva</span>
                                        <?php if ($jugador['posicion']): ?>
                                            <p class="convocado-posicion"><?php echo htmlspecialchars($jugador['posicion']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 25px; padding: 15px; background: #f1f5f9; border-radius: 8px;">
                            <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
                                <strong>Total convocados:</strong> <?php echo count($convocatoria_detalle['jugadores']); ?> jugadores
                            </p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Lista de convocatorias -->
            <?php if (empty($convocatorias)): ?>
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;">📭</div>
                    <h3>No hay convocatorias disponibles</h3>
                    <p>El entrenador aún no ha publicado ninguna convocatoria para partidos próximos.</p>
                </div>
            <?php else: ?>
                <?php foreach ($convocatorias as $conv):
                    $fecha_partido = new DateTime($conv['partido_fecha']);
                ?>
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title"><?php echo htmlspecialchars($conv['partido_titulo']); ?></h2>
                                <p class="card-subtitle">
                                    Temporada: <?php echo htmlspecialchars($conv['temporada_nombre']); ?>
                                </p>
                            </div>
                            <div class="card-date">
                                <?php echo $fecha_partido->format('d/m/Y H:i'); ?>
                            </div>
                        </div>

                        <?php if ($conv['notas']): ?>
                            <p style="color: #4a5568; margin: 15px 0;">
                                <?php echo htmlspecialchars(substr($conv['notas'], 0, 150)); ?>
                                <?php if (strlen($conv['notas']) > 150): ?>...<?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <a href="?id=<?php echo $conv['id']; ?>" class="btn-primary">
                            👁️ Ver convocatoria completa
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
</body>

</html>