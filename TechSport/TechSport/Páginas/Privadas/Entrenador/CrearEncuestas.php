<?php
session_start();

// Verificar que el usuario esté logueado y sea Entrenador (1)
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    header("Location: /TechSport/LogicaPHP/login.php");
    exit();
}

$rol_id = $_SESSION['rol_id'];
if ($rol_id != 1) { // Solo Entrenador
    header("Location: /TechSport/Páginas/Privadas/" . ($rol_id == 2 ? "Jugador" : "Profesional") . "/inicio.php");
    exit();
}

// Obtener datos del usuario
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];

// Incluir conexión
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';

// Obtener equipo del entrenador
$stmt = $conn->prepare("SELECT id, nombre, temporada_id FROM equipos WHERE entrenador_id = ? LIMIT 1");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$equipo = $result->fetch_assoc();
$stmt->close();

if (!$equipo) {
    header("Location: /TechSport/Páginas/Privadas/Entrenador/inicio.php");
    exit();
}

// Obtener eventos futuros del equipo (excluyendo partidos)
$stmt = $conn->prepare("
    SELECT id, titulo, fecha, tipo 
    FROM eventos 
    WHERE equipo_id = ? 
    AND temporada_id = ?
    AND tipo != 'partido'
    AND fecha > NOW()
    ORDER BY fecha ASC
");
$stmt->bind_param("ii", $equipo['id'], $equipo['temporada_id']);
$stmt->execute();
$result = $stmt->get_result();
$eventos_futuros = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Procesar creación de encuesta
$mensaje = '';
$error = '';

// Procesar borrado de encuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'borrar_encuesta') {
    $encuesta_id = intval($_POST['encuesta_id'] ?? 0);
    
    if ($encuesta_id > 0) {
        // Verificar que la encuesta pertenece al equipo del entrenador
        $stmt = $conn->prepare("SELECT id FROM encuestas WHERE id = ? AND equipo_id = ?");
        $stmt->bind_param("ii", $encuesta_id, $equipo['id']);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            // Primero eliminar los votos
            $stmt_votos = $conn->prepare("DELETE FROM encuesta_votos WHERE encuesta_id = ?");
            $stmt_votos->bind_param("i", $encuesta_id);
            $stmt_votos->execute();
            $stmt_votos->close();
            
            // Luego eliminar las opciones
            $stmt_opciones = $conn->prepare("DELETE FROM encuesta_opciones WHERE encuesta_id = ?");
            $stmt_opciones->bind_param("i", $encuesta_id);
            $stmt_opciones->execute();
            $stmt_opciones->close();
            
            // Finalmente eliminar la encuesta
            $stmt_encuesta = $conn->prepare("DELETE FROM encuestas WHERE id = ?");
            $stmt_encuesta->bind_param("i", $encuesta_id);
            
            if ($stmt_encuesta->execute()) {
                $mensaje = "✅ Encuesta eliminada exitosamente.";
            } else {
                $error = "Error al eliminar la encuesta: " . $conn->error;
            }
            $stmt_encuesta->close();
        } else {
            $error = "No tienes permisos para eliminar esta encuesta.";
        }
        $stmt->close();
    }
}

// Procesar creación de encuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_encuesta') {
    $evento_id = intval($_POST['evento_id'] ?? 0);
    $pregunta = trim($_POST['pregunta'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $opciones = $_POST['opciones'] ?? [];

    if (empty($pregunta) || empty($fecha_inicio) || empty($fecha_fin) || $evento_id <= 0) {
        $error = "Todos los campos son obligatorios.";
    } elseif (count($opciones) < 2) {
        $error = "Debes agregar al menos 2 opciones.";
    } elseif (strtotime($fecha_inicio) >= strtotime($fecha_fin)) {
        $error = "La fecha de fin debe ser posterior a la fecha de inicio.";
    } else {
        // Verificar que el evento pertenece al equipo
        $stmt = $conn->prepare("SELECT id FROM eventos WHERE id = ? AND equipo_id = ?");
        $stmt->bind_param("ii", $evento_id, $equipo['id']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $error = "Evento no válido.";
        } else {
            // Crear la encuesta
            $stmt = $conn->prepare("
                INSERT INTO encuestas (equipo_id, temporada_id, evento_id, pregunta, fecha_inicio, fecha_fin, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 'activa')
            ");
            $stmt->bind_param("iiisss", $equipo['id'], $equipo['temporada_id'], $evento_id, $pregunta, $fecha_inicio, $fecha_fin);

            if ($stmt->execute()) {
                $encuesta_id = $stmt->insert_id;

                // Crear las opciones
                $orden = 1;
                foreach ($opciones as $opcion_texto) {
                    $opcion_texto = trim($opcion_texto);
                    if (!empty($opcion_texto)) {
                        $stmt_op = $conn->prepare("INSERT INTO encuesta_opciones (encuesta_id, opcion_texto, orden) VALUES (?, ?, ?)");
                        $stmt_op->bind_param("isi", $encuesta_id, $opcion_texto, $orden);
                        $stmt_op->execute();
                        $stmt_op->close();
                        $orden++;
                    }
                }

                $mensaje = "✅ Encuesta creada exitosamente.";
            } else {
                $error = "Error al crear la encuesta: " . $conn->error;
            }
        }
        $stmt->close();
    }
}

// Obtener encuestas activas del equipo (corregido - usando encuesta_votos en lugar de encuesta_respuestas)
$stmt_encuestas = $conn->prepare("
    SELECT
        e.id,
        e.pregunta,
        e.fecha_inicio,
        e.fecha_fin,
        ev.titulo as evento_titulo,
        ev.fecha as evento_fecha,
        COUNT(DISTINCT v.usuario_id) as total_votos,
        e.estado
    FROM encuestas e
    LEFT JOIN eventos ev ON e.evento_id = ev.id
    LEFT JOIN encuesta_votos v ON e.id = v.encuesta_id
    WHERE e.equipo_id = ?
    AND e.temporada_id = ?
    AND e.estado = 'activa'
    GROUP BY e.id
    ORDER BY e.fecha_inicio DESC
");
$stmt_encuestas->bind_param("ii", $equipo['id'], $equipo['temporada_id']);
$stmt_encuestas->execute();
$result_encuestas = $stmt_encuestas->get_result();
$encuestas_activas = $result_encuestas->fetch_all(MYSQLI_ASSOC);
$stmt_encuestas->close();

// Obtener resultados detallados de cada encuesta (corregido - usando encuesta_votos)
$encuestas_con_resultados = [];
foreach ($encuestas_activas as $encuesta) {
    $stmt_resultados = $conn->prepare("
        SELECT
            eo.id as opcion_id,
            eo.opcion_texto,
            eo.orden,
            COUNT(v.id) as votos
        FROM encuesta_opciones eo
        LEFT JOIN encuesta_votos v ON eo.id = v.opcion_id AND v.encuesta_id = ?
        WHERE eo.encuesta_id = ?
        GROUP BY eo.id, eo.opcion_texto, eo.orden
        ORDER BY eo.orden
    ");
    $stmt_resultados->bind_param("ii", $encuesta['id'], $encuesta['id']);
    $stmt_resultados->execute();
    $result_resultados = $stmt_resultados->get_result();
    $resultados = $result_resultados->fetch_all(MYSQLI_ASSOC);
    $stmt_resultados->close();

    $encuesta['resultados'] = $resultados;
    $encuestas_con_resultados[] = $encuesta;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TechSport - Crear Encuesta</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Compartidos/estilos.css" />
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Compartidos/estilos_inicio.css">
    <style>
        .form-crear-encuesta {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background-color: #f8fafc;
        }

        .form-control:focus {
            outline: none;
            border-color: #4299e1;
            background-color: white;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .opciones-container {
            margin: 20px 0;
        }

        .opcion-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }

        .opcion-input {
            flex-grow: 1;
        }

        .btn-agregar-opcion {
            padding: 8px 15px;
            background: #38a169;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-eliminar-opcion {
            padding: 8px 12px;
            background: #e53e3e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-submit {
            padding: 15px 30px;
            background: #1a2a6c;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 42, 108, 0.3);
        }

        .evento-info {
            background: #ebf8ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #4299e1;
        }

        .no-eventos {
            text-align: center;
            padding: 30px;
            color: #a0aec0;
        }

        /* Estilos para la sección de encuestas activas */
        .encuestas-activas-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .encuestas-activas-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #1a2a6c;
            font-size: 1.8rem;
        }

        .encuestas-grid {
            display: grid;
            gap: 25px;
        }

        .encuesta-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 5px solid #4299e1;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .encuesta-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .encuesta-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .encuesta-header h3 {
            color: #1a2a6c;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .encuesta-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: #718096;
            font-size: 0.9rem;
        }

        .encuesta-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .encuesta-resultados h4 {
            color: #4a5568;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .resultado-item {
            margin-bottom: 15px;
        }

        .resultado-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .opcion-texto {
            color: #4a5568;
            font-weight: 500;
        }

        .opcion-votos {
            color: #4299e1;
            font-weight: 600;
        }

        .barra-progreso {
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progreso {
            height: 100%;
            background: linear-gradient(90deg, #4299e1, #38b2ac);
            border-radius: 5px;
            transition: width 1s ease;
        }

        .resumen-participacion {
            margin-top: 20px;
            padding: 15px;
            background: #ebf8ff;
            border-radius: 8px;
            text-align: center;
        }

        .resumen-participacion p {
            margin: 0;
            color: #2d3748;
        }

        .opcion-ganadora {
            font-size: 0.9rem;
            color: #718096;
            display: block;
            margin-top: 5px;
        }

        .no-encuestas {
            text-align: center;
            padding: 40px;
            background: #f8fafc;
            border-radius: 10px;
            color: #718096;
        }

        .no-encuestas h3 {
            color: #a0aec0;
            margin-bottom: 15px;
        }

        /* Botón de borrar encuesta */
        .btn-borrar-encuesta {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 12px;
            background: #e53e3e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s ease;
        }

        .btn-borrar-encuesta:hover {
            background: #c53030;
        }

        /* Modal de confirmación */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .modal h3 {
            color: #1a2a6c;
            margin-bottom: 15px;
        }

        .modal p {
            color: #4a5568;
            margin-bottom: 20px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-modal-cancelar {
            padding: 10px 20px;
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-modal-confirmar {
            padding: 10px 20px;
            background: #e53e3e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .encuesta-meta {
                flex-direction: column;
                gap: 8px;
            }
            
            .encuesta-card {
                padding: 20px 15px;
            }
            
            .encuesta-header h3 {
                font-size: 1.1rem;
            }
            
            .resultado-header {
                flex-direction: column;
                gap: 5px;
            }
            
            .opcion-votos {
                font-size: 0.9rem;
            }
            
            .btn-borrar-encuesta {
                position: relative;
                top: 0;
                right: 0;
                margin-top: 15px;
                width: 100%;
            }
        }
    </style>
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
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/Estadisticas.html">Estadísticas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipos Rivales</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/CrearEncuestas.php">Crear Encuesta</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/Convocatoria.php">Convocatoria</a></li>
                <li><a class="nav-link" href="/TechSport/LogicaPHP/logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="form-crear-encuesta">
            <!-- Sección de bienvenida -->
            <div class="welcome-section">
                <h1>📝 Crear Nueva Encuesta</h1>
                <p>Crea encuestas para eventos de tu equipo</p>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (empty($eventos_futuros)): ?>
                <div class="no-eventos">
                    <h3>📭 No hay eventos futuros disponibles</h3>
                    <p>Primero debes crear eventos de entrenamiento, reuniones u otras actividades (excepto partidos) en el calendario.</p>
                    <a href="/TechSport/Páginas/Privadas/EditarCalendario.php" class="btn-action btn-primary">
                        🗓️ Ir al Calendario
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="crear_encuesta">

                    <!-- Selección de evento -->
                    <div class="form-group">
                        <label for="evento_id">📅 Seleccionar Evento *</label>
                        <select id="evento_id" name="evento_id" class="form-control" required>
                            <option value="">Selecciona un evento...</option>
                            <?php foreach ($eventos_futuros as $evento): ?>
                                <option value="<?php echo $evento['id']; ?>">
                                    <?php echo htmlspecialchars($evento['titulo']); ?> -
                                    <?php echo date('d/m/Y H:i', strtotime($evento['fecha'])); ?> -
                                    <?php echo htmlspecialchars($evento['tipo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Pregunta -->
                    <div class="form-group">
                        <label for="pregunta">❓ Pregunta de la Encuesta *</label>
                        <input type="text" id="pregunta" name="pregunta" class="form-control"
                            placeholder="Ej: ¿A qué hora prefieres el entrenamiento?" required>
                    </div>

                    <!-- Fechas -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_inicio">⏰ Fecha Inicio *</label>
                            <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_fin">⏰ Fecha Fin *</label>
                            <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control" required>
                        </div>
                    </div>

                    <!-- Opciones -->
                    <div class="form-group">
                        <label>🗳️ Opciones de Respuesta (mínimo 2) *</label>
                        <div class="opciones-container" id="opciones-container">
                            <div class="opcion-item">
                                <input type="text" name="opciones[]" class="form-control opcion-input"
                                    placeholder="Opción 1" required>
                                <button type="button" class="btn-eliminar-opcion" onclick="eliminarOpcion(this)" disabled>🗑️</button>
                            </div>
                            <div class="opcion-item">
                                <input type="text" name="opciones[]" class="form-control opcion-input"
                                    placeholder="Opción 2" required>
                                <button type="button" class="btn-eliminar-opcion" onclick="eliminarOpcion(this)">🗑️</button>
                            </div>
                        </div>
                        <button type="button" class="btn-agregar-opcion" onclick="agregarOpcion()">➕ Agregar Opción</button>
                    </div>

                    <button type="submit" class="btn-submit">📊 Crear Encuesta</button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- SECCIÓN DE ENCUESTAS ACTIVAS (RESULTADOS EN VIVO) -->
        <div class="encuestas-activas-container">
            <h2>📊 Encuestas Activas (Resultados en Vivo)</h2>
            
            <?php if (empty($encuestas_con_resultados)): ?>
                <div class="no-encuestas">
                    <h3>📭 No hay encuestas activas</h3>
                    <p>Crea tu primera encuesta usando el formulario superior</p>
                </div>
            <?php else: ?>
                <div class="encuestas-grid">
                    <?php foreach ($encuestas_con_resultados as $encuesta): 
                        // Calcular total de votos de esta encuesta
                        $total_votos_encuesta = $encuesta['total_votos'];
                    ?>
                        <div class="encuesta-card">
                            <!-- Botón para borrar encuesta -->
                            <button type="button" class="btn-borrar-encuesta" onclick="mostrarModalBorrar(<?php echo $encuesta['id']; ?>, '<?php echo htmlspecialchars(addslashes($encuesta['pregunta'])); ?>')">
                                🗑️ Borrar Encuesta
                            </button>
                            
                            <!-- Encabezado de la encuesta -->
                            <div class="encuesta-header">
                                <h3><?php echo htmlspecialchars($encuesta['pregunta']); ?></h3>
                                <div class="encuesta-meta">
                                    <span>📅 Evento: <strong><?php echo htmlspecialchars($encuesta['evento_titulo']); ?></strong></span>
                                    <span>🗓️ Fecha evento: <strong><?php echo date('d/m/Y H:i', strtotime($encuesta['evento_fecha'])); ?></strong></span>
                                    <span>⏰ Encuesta activa hasta: <strong><?php echo date('d/m/Y H:i', strtotime($encuesta['fecha_fin'])); ?></strong></span>
                                    <span>👥 Total votos: <strong><?php echo $total_votos_encuesta; ?></strong></span>
                                </div>
                            </div>
                            
                            <!-- Resultados en vivo -->
                            <div class="encuesta-resultados">
                                <h4>📊 Resultados en vivo:</h4>
                                
                                <?php if (!empty($encuesta['resultados'])): ?>
                                    <?php foreach ($encuesta['resultados'] as $resultado): 
                                        $porcentaje = $total_votos_encuesta > 0 ? ($resultado['votos'] / $total_votos_encuesta) * 100 : 0;
                                    ?>
                                        <div class="resultado-item">
                                            <div class="resultado-header">
                                                <span class="opcion-texto">
                                                    <?php echo htmlspecialchars($resultado['opcion_texto']); ?>
                                                </span>
                                                <span class="opcion-votos">
                                                    <?php echo $resultado['votos']; ?> votos (<?php echo number_format($porcentaje, 1); ?>%)
                                                </span>
                                            </div>
                                            <div class="barra-progreso">
                                                <div class="progreso" style="width: <?php echo $porcentaje; ?>%;"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Resumen -->
                                    <div class="resumen-participacion">
                                        <p>
                                            <strong>Total de participantes:</strong> <?php echo $total_votos_encuesta; ?> jugadores
                                            <?php if ($total_votos_encuesta > 0): 
                                                // Encontrar la opción más votada
                                                $max_votos = max(array_column($encuesta['resultados'], 'votos'));
                                                $opciones_ganadoras = array_filter($encuesta['resultados'], function($r) use ($max_votos) {
                                                    return $r['votos'] == $max_votos;
                                                });
                                                $opciones_texto = array_column($opciones_ganadoras, 'opcion_texto');
                                            ?>
                                                <br>
                                                <span class="opcion-ganadora">
                                                    Opción más votada: 
                                                    <strong><?php echo htmlspecialchars(implode(', ', $opciones_texto)); ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <p style="text-align: center; color: #718096; padding: 20px;">
                                        No hay votos registrados todavía.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de confirmación para borrar encuesta -->
    <div id="modalBorrar" class="modal-overlay">
        <div class="modal">
            <h3>⚠️ Confirmar Borrado</h3>
            <p id="modalTexto">¿Estás seguro de que quieres borrar esta encuesta?</p>
            <p><strong>Esta acción no se puede deshacer.</strong> Se eliminarán todos los votos y opciones asociadas.</p>
            <div class="modal-buttons">
                <button type="button" class="btn-modal-cancelar" onclick="cerrarModal()">Cancelar</button>
                <form id="formBorrar" method="POST" action="">
                    <input type="hidden" name="accion" value="borrar_encuesta">
                    <input type="hidden" name="encuesta_id" id="encuestaId">
                    <button type="submit" class="btn-modal-confirmar">Sí, Borrar Encuesta</button>
                </form>
            </div>
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
        // Configurar fechas por defecto (ahora y mañana a la misma hora)
        document.addEventListener('DOMContentLoaded', function() {
            const ahora = new Date();
            const mañana = new Date(ahora);
            mañana.setDate(mañana.getDate() + 1);

            // Formatear para input datetime-local
            const formatearFecha = (fecha) => {
                return fecha.toISOString().slice(0, 16);
            };

            document.getElementById('fecha_inicio').value = formatearFecha(ahora);
            document.getElementById('fecha_fin').value = formatearFecha(mañana);
        });

        // Manejar opciones dinámicas
        let contadorOpciones = 3;

        function agregarOpcion() {
            const container = document.getElementById('opciones-container');
            const nuevaOpcion = document.createElement('div');
            nuevaOpcion.className = 'opcion-item';
            nuevaOpcion.innerHTML = `
                <input type="text" name="opciones[]" class="form-control opcion-input" 
                       placeholder="Opción ${contadorOpciones}" required>
                <button type="button" class="btn-eliminar-opcion" onclick="eliminarOpcion(this)">🗑️</button>
            `;
            container.appendChild(nuevaOpcion);
            contadorOpciones++;

            // Habilitar botones de eliminar en las primeras opciones si hay más de 2
            const botonesEliminar = document.querySelectorAll('.btn-eliminar-opcion');
            if (botonesEliminar.length > 2) {
                botonesEliminar[0].disabled = false;
                botonesEliminar[1].disabled = false;
            }
        }

        function eliminarOpcion(boton) {
            const opcionItem = boton.parentElement;
            const container = document.getElementById('opciones-container');

            // No permitir eliminar si solo quedan 2 opciones
            if (container.children.length > 2) {
                opcionItem.remove();
                contadorOpciones--;

                // Actualizar placeholders
                const opciones = container.querySelectorAll('.opcion-input');
                opciones.forEach((input, index) => {
                    input.placeholder = `Opción ${index + 1}`;
                });

                // Deshabilitar botones de eliminar si solo quedan 2 opciones
                const botonesEliminar = document.querySelectorAll('.btn-eliminar-opcion');
                if (botonesEliminar.length === 2) {
                    botonesEliminar[0].disabled = true;
                    botonesEliminar[1].disabled = true;
                }
            }
        }

        // Validar formulario antes de enviar
        document.querySelector('form').addEventListener('submit', function(e) {
            const opciones = document.querySelectorAll('.opcion-input');
            const opcionesValidas = Array.from(opciones).filter(input => input.value.trim() !== '');

            if (opcionesValidas.length < 2) {
                e.preventDefault();
                alert('❌ Debes tener al menos 2 opciones válidas.');
                return false;
            }

            const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
            const fechaFin = new Date(document.getElementById('fecha_fin').value);

            if (fechaInicio >= fechaFin) {
                e.preventDefault();
                alert('❌ La fecha de fin debe ser posterior a la fecha de inicio.');
                return false;
            }

            return true;
        });

        // Funciones para el modal de borrar encuesta
        function mostrarModalBorrar(encuestaId, pregunta) {
            document.getElementById('encuestaId').value = encuestaId;
            document.getElementById('modalTexto').textContent = `¿Estás seguro de que quieres borrar la encuesta: "${pregunta}"?`;
            document.getElementById('modalBorrar').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('modalBorrar').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalBorrar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        // Confirmar borrado con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });
    </script>
</body>

</html>