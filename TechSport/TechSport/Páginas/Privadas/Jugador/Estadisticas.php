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

// Obtener información del jugador
$stmt = $conn->prepare("
    SELECT 
        j.id as jugador_id,
        j.dorsal,
        j.posicion,
        j.altura,
        j.peso,
        j.nacionalidad,
        j.fecha_nacimiento,
        ej.equipo_id,
        eq.nombre as equipo_nombre,
        eq.temporada_id,
        t.nombre as temporada_nombre
    FROM jugadores j
    LEFT JOIN equipo_jugadores ej ON j.id = ej.jugador_id AND ej.activo = 1
    LEFT JOIN equipos eq ON ej.equipo_id = eq.id
    LEFT JOIN temporadas t ON eq.temporada_id = t.id
    WHERE j.usuario_id = ?
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$jugador = $result->fetch_assoc();
$stmt->close();

if (!$jugador) {
    header("Location: /TechSport/LogicaPHP/login.php");
    exit();
}

// Calcular edad
$edad = '';
if ($jugador['fecha_nacimiento']) {
    $nacimiento = new DateTime($jugador['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento)->y;
}

// Obtener estadísticas físicas
$stmt = $conn->prepare("
    SELECT 
        velocidad,
        fuerza,
        resistencia,
        fecha_medicion
    FROM estadisticas_fisicas 
    WHERE jugador_id = ? 
    ORDER BY fecha_medicion DESC 
    LIMIT 1
");
$stmt->bind_param("i", $jugador['jugador_id']);
$stmt->execute();
$result = $stmt->get_result();
$fisico = $result->fetch_assoc();
$stmt->close();

// Si no hay estadísticas físicas, inicializar con valores por defecto
if (!$fisico) {
    $fisico = [
        'velocidad' => 0,
        'fuerza' => 0,
        'resistencia' => 0,
        'fecha_medicion' => null
    ];
}

// Obtener estadísticas acumuladas del equipo actual (de estadisticas_partido)
$stats_equipo_actual = [
    'partidos_jugados' => 0,
    'goles' => 0,
    'asistencias' => 0,
    'amarillas' => 0,
    'rojas' => 0,
    'porterias_cero' => 0,
    'minutos_jugados' => 0
];

if ($jugador['equipo_id'] && $jugador['temporada_id']) {
    $stmt = $conn->prepare("
        SELECT 
            partidos_jugados,
            goles,
            asistencias,
            amarillas,
            rojas,
            porterias_cero,
            minutos_jugados
        FROM estadisticas_partido 
        WHERE jugador_id = ? 
        AND equipo_id = ? 
        AND temporada_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("iii", $jugador['jugador_id'], $jugador['equipo_id'], $jugador['temporada_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats_equipo_actual = $row;
    }
    $stmt->close();
}

// Obtener conteo de estados en convocatorias (titular, suplente)
$conteo_estados = [
    'titular' => 0,
    'suplente' => 0,
    'no_convocado' => 0
];

if ($jugador['equipo_id'] && $jugador['temporada_id']) {
    // Contar partidos donde fue convocado (titular o suplente)
    $stmt = $conn->prepare("
        SELECT 
            cd.tipo,
            COUNT(*) as cantidad
        FROM convocados cd
        INNER JOIN convocatorias c ON cd.convocatoria_id = c.id
        WHERE cd.jugador_id = ?
        AND c.equipo_id = ?
        AND c.temporada_id = ?
        GROUP BY cd.tipo
    ");
    $stmt->bind_param(
        "iii",
        $jugador['jugador_id'],
        $jugador['equipo_id'],
        $jugador['temporada_id']
    );
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['tipo'] == 'titular') {
            $conteo_estados['titular'] = $row['cantidad'];
        } elseif ($row['tipo'] == 'suplente') {
            $conteo_estados['suplente'] = $row['cantidad'];
        }
    }
    $stmt->close();

    // Contar total de convocatorias de partidos para calcular "no convocado"
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT c.evento_id) as total_convocatorias
        FROM convocatorias c
        INNER JOIN eventos e ON c.evento_id = e.id
        WHERE c.equipo_id = ?
        AND c.temporada_id = ?
        AND e.tipo = 'partido'
    ");
    $stmt->bind_param("ii", $jugador['equipo_id'], $jugador['temporada_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_convocatorias = $row['total_convocatorias'];
        $total_convocado = $conteo_estados['titular'] + $conteo_estados['suplente'];
        $conteo_estados['no_convocado'] = max(0, $total_convocatorias - $total_convocado);
    }
    $stmt->close();
}

// Obtener estadísticas de partidos individuales (para ver detalle)
$partidos_detalle = [];
if ($jugador['equipo_id'] && $jugador['temporada_id']) {
    $stmt = $conn->prepare("
        SELECT 
            e.id as evento_id,
            e.titulo,
            e.fecha,
            e.descripcion,
            cd.dorsal,
            cd.posicion as posicion_partido,
            cd.tipo as tipo_convocado,
            ep.goles,
            ep.asistencias,
            ep.amarillas,
            ep.rojas,
            ep.porterias_cero,
            ep.minutos_jugados
        FROM eventos e
        LEFT JOIN convocatorias c ON e.id = c.evento_id 
            AND c.equipo_id = ? 
            AND c.temporada_id = ?
        LEFT JOIN convocados cd ON c.id = cd.convocatoria_id 
            AND cd.jugador_id = ?
        LEFT JOIN estadisticas_partido ep ON ep.jugador_id = ? 
            AND ep.equipo_id = ? 
            AND ep.temporada_id = ?
        WHERE e.tipo = 'partido'
        AND e.equipo_id = ?
        AND e.temporada_id = ?
        ORDER BY e.fecha DESC
    ");
    $stmt->bind_param(
        "iiiiiiii",
        $jugador['equipo_id'],
        $jugador['temporada_id'],
        $jugador['jugador_id'],
        $jugador['jugador_id'],
        $jugador['equipo_id'],
        $jugador['temporada_id'],
        $jugador['equipo_id'],
        $jugador['temporada_id']
    );
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Solo incluir partidos donde el jugador fue convocado
        if ($row['tipo_convocado']) {
            $partidos_detalle[] = [
                'fecha' => $row['fecha'],
                'titulo' => $row['titulo'],
                'descripcion' => $row['descripcion'],
                'dorsal' => $row['dorsal'],
                'posicion_partido' => $row['posicion_partido'],
                'tipo_convocado' => $row['tipo_convocado'],
                'goles' => $row['goles'] ?? 0,
                'asistencias' => $row['asistencias'] ?? 0,
                'minutos_jugados' => $row['minutos_jugados'] ?? 0,
                'amarillas' => $row['amarillas'] ?? 0,
                'rojas' => $row['rojas'] ?? 0,
                'porterias_cero' => $row['porterias_cero'] ?? 0
            ];
        }
    }
    $stmt->close();
}

// Obtener estadísticas de carrera total (sumando todas las temporadas)
$stats_carrera_total = [
    'partidos_jugados' => 0,
    'goles' => 0,
    'asistencias' => 0,
    'amarillas' => 0,
    'rojas' => 0,
    'porterias_cero' => 0,
    'minutos_jugados' => 0
];

$stmt = $conn->prepare("
    SELECT 
        SUM(partidos_jugados) as total_partidos,
        SUM(goles) as total_goles,
        SUM(asistencias) as total_asistencias,
        SUM(amarillas) as total_amarillas,
        SUM(rojas) as total_rojas,
        SUM(porterias_cero) as total_porterias_cero,
        SUM(minutos_jugados) as total_minutos
    FROM estadisticas_partido 
    WHERE jugador_id = ?
");
$stmt->bind_param("i", $jugador['jugador_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats_carrera_total = [
        'partidos_jugados' => $row['total_partidos'] ?? 0,
        'goles' => $row['total_goles'] ?? 0,
        'asistencias' => $row['total_asistencias'] ?? 0,
        'amarillas' => $row['total_amarillas'] ?? 0,
        'rojas' => $row['total_rojas'] ?? 0,
        'porterias_cero' => $row['total_porterias_cero'] ?? 0,
        'minutos_jugados' => $row['total_minutos'] ?? 0
    ];
}
$stmt->close();

// Obtener historial de equipos (todas las temporadas)
$historial_equipos = [];
$stmt = $conn->prepare("
    SELECT 
        eq.id,
        eq.nombre as equipo_nombre,
        ep.temporada_id,
        t.nombre as temporada_nombre,
        t.fecha_inicio,
        t.fecha_fin,
        ep.partidos_jugados,
        ep.goles,
        ep.asistencias,
        ep.minutos_jugados,
        ep.amarillas,
        ep.rojas,
        ep.porterias_cero
    FROM estadisticas_partido ep
    INNER JOIN equipos eq ON ep.equipo_id = eq.id
    INNER JOIN temporadas t ON ep.temporada_id = t.id
    WHERE ep.jugador_id = ?
    AND ep.partidos_jugados > 0
    ORDER BY t.fecha_inicio DESC, eq.nombre ASC
");
$stmt->bind_param("i", $jugador['jugador_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $historial_equipos[] = [
        'id' => $row['id'],
        'nombre' => $row['equipo_nombre'],
        'temporada_nombre' => $row['temporada_nombre'],
        'fecha_inicio' => $row['fecha_inicio'],
        'fecha_fin' => $row['fecha_fin'],
        'partidos_jugados' => $row['partidos_jugados'],
        'goles' => $row['goles'],
        'asistencias' => $row['asistencias'],
        'minutos_jugados' => $row['minutos_jugados'],
        'amarillas' => $row['amarillas'],
        'rojas' => $row['rojas'],
        'porterias_cero' => $row['porterias_cero']
    ];
}
$stmt->close();

// Cerrar conexión
$conn->close();
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TechSport - Estadísticas Jugador</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">

    <style>
        /* ==================== ESTADÍSTICAS JUGADOR - CSS INLINE ==================== */

        /* Reset para esta página */
        .estadisticas-container,
        .estadisticas-container * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .estadisticas-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: calc(100vh - 80px);
        }

        /* ==================== HEADER DEL JUGADOR ==================== */
        .info-jugador {
            background: linear-gradient(135deg, #2D3047 0%, #3D405B 100%);
            color: white;
            margin-bottom: 30px;
            border-radius: 20px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .jugador-header {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .jugador-avatar {
            flex-shrink: 0;
        }

        .camiseta {
            width: 150px;
            height: 180px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            transform: rotate(-5deg);
            transition: transform 0.3s ease;
        }

        .camiseta:hover {
            transform: rotate(0deg) scale(1.05);
        }

        .dorsal {
            font-size: 4.5rem;
            font-weight: 900;
            color: white;
            text-shadow: 3px 5px 10px rgba(0, 0, 0, 0.4);
        }

        /* Colores por posición */
        .camiseta.portero {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
            border: 4px solid #118ab2;
        }

        .camiseta.defensa {
            background: linear-gradient(135deg, #06D6A0 0%, #048A81 100%);
            border: 4px solid #008000;
        }

        .camiseta.centrocampista,
        .camiseta.mc {
            background: linear-gradient(135deg, #FFD166 0%, #F8961E 100%);
            border: 4px solid #F3722C;
        }

        .camiseta.delantero {
            background: linear-gradient(135deg, #EF476F 0%, #9D4EDD 100%);
            border: 4px solid #7209B7;
        }

        .jugador-info {
            flex: 1;
        }

        .jugador-info h1 {
            margin: 0 0 15px 0;
            font-size: 3rem;
            font-weight: 800;
            color: white;
        }

        .jugador-detalles {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .posicion {
            background: #FF6B35;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .equipo,
        .temporada {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 18px;
            border-radius: 25px;
            font-weight: 600;
        }

        .separador {
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.2rem;
        }

        .jugador-caracteristicas {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .jugador-caracteristicas span {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 18px;
            border-radius: 25px;
            font-weight: 500;
        }

        /* ==================== LEYENDA DE COLORES ==================== */
        .leyenda-colores {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            max-width: 800px;
        }

        .color-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .color-muestra {
            width: 25px;
            height: 25px;
            border-radius: 6px;
        }

        /* ==================== PANELES GENERALES ==================== */
        .panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .panel h2 {
            color: #2D3047;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #FF6B35;
        }

        .fecha-medicion {
            color: #6c757d;
            font-style: italic;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        /* ==================== ESTADÍSTICAS FÍSICAS ==================== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .stat-block {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid #e9ecef;
        }

        .stat-block span {
            display: block;
            font-size: 1rem;
            color: #2D3047;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .bar {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            margin: 15px 0;
            overflow: hidden;
        }

        .fill {
            height: 100%;
            width: 0;
            transition: width 1.5s ease;
            border-radius: 10px;
            background: linear-gradient(90deg, #FF6B35, #004E89);
        }

        .stat-block strong {
            display: block;
            font-size: 1.6rem;
            font-weight: 700;
            color: #2D3047;
            margin-top: 10px;
        }

        /* ==================== CONVOCATORIAS ==================== */
        .convocatorias-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .conv-item {
            text-align: center;
            padding: 25px 15px;
            border-radius: 12px;
        }

        .conv-titular {
            background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
            border: 2px solid #A5D6A7;
        }

        .conv-suplente {
            background: linear-gradient(135deg, #FFF3E0, #FFE0B2);
            border: 2px solid #FFCC80;
        }

        .conv-no-convocado {
            background: linear-gradient(135deg, #F5F5F5, #EEEEEE);
            border: 2px solid #E0E0E0;
        }

        .conv-label {
            display: block;
            font-size: 0.9rem;
            color: #2D3047;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .conv-value {
            display: block;
            font-size: 2.5rem;
            font-weight: 800;
            color: #2D3047;
        }

        /* ==================== ESTADÍSTICAS DEL EQUIPO ==================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid #e9ecef;
        }

        .stat-label {
            display: block;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: #2D3047;
        }

        /* ==================== ESTADÍSTICAS TEMPORADA ==================== */
        .stats-temporada {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .item-t {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid #e9ecef;
        }

        .item-t span {
            display: block;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .item-t strong {
            display: block;
            font-size: 1.8rem;
            font-weight: 700;
            color: #2D3047;
        }

        /* ==================== HISTORIAL DE EQUIPOS ==================== */
        .equipos-historial {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .equipo-historial-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            border-left: 5px solid #FF6B35;
        }

        .equipo-historial-card h3 {
            margin: 0 0 8px 0;
            color: #2D3047;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .temporada-hist {
            color: #FF6B35;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .temporada-fechas {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .equipo-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .equipo-stats span {
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #2D3047;
        }

        /* ==================== PARTIDOS ==================== */
        .partido-card {
            background: white;
            margin: 15px 0;
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
        }

        .partido-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .partido-header h3 {
            margin: 0;
            color: #2D3047;
            font-size: 1.2rem;
            font-weight: 700;
            flex: 1;
        }

        .fecha {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 600;
            background: #f8f9fa;
            padding: 5px 12px;
            border-radius: 20px;
        }

        .descripcion {
            color: #6c757d;
            margin: 15px 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .partido-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .stat-line {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .stat-line span {
            color: #6c757d;
            font-weight: 600;
        }

        .stat-line strong {
            color: #2D3047;
            font-weight: 700;
        }

        .stat-line.highlight {
            background: #E3F2FD;
            padding: 6px 12px;
            border-radius: 8px;
        }

        /* Estados de convocatoria */
        .estado-titular {
            background: #C8E6C9;
            color: #1B5E20;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .estado-suplente {
            background: #FFECB3;
            color: #F57C00;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        /* Iconos especiales */
        .goles {
            color: #E63946;
            font-weight: 700;
        }

        .asistencias {
            color: #1A936F;
            font-weight: 700;
        }

        .porteria-cero {
            color: #004E89;
            font-weight: 700;
        }

        .tarjeta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .tarjeta.amarilla {
            background: #FFD166;
            color: #9D4B00;
        }

        .tarjeta.roja {
            background: #EF476F;
            color: white;
        }

        /* ==================== NO DATA ==================== */
        .no-data {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
            font-size: 1.1rem;
            background: white;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1024px) {

            .stats-row,
            .convocatorias-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .equipos-historial {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .jugador-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .jugador-info h1 {
                font-size: 2.2rem;
            }

            .camiseta {
                width: 120px;
                height: 150px;
                transform: rotate(0deg);
            }

            .dorsal {
                font-size: 3.5rem;
            }

            .leyenda-colores {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .stats-row,
            .convocatorias-stats,
            .stats-grid,
            .stats-temporada {
                grid-template-columns: 1fr;
            }

            .partido-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .panel {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .estadisticas-container {
                padding: 15px;
            }

            .jugador-detalles {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .jugador-caracteristicas {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .separador {
                display: none;
            }

            .equipos-historial {
                grid-template-columns: 1fr;
            }

            .partido-stats {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>

<body data-rol="jugador">

    <header class="header">
        <div class="header-top">
            <img src="/TechSport/Recursos/img/Nombre.png" class="logo-header" id="logo-interactivo" />
        </div>
        <nav class="navbar">
            <ul class="nav-menu">
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Jugador/inicio.php">Inicio</a></li>
                <li><a class="nav-link active" href="/TechSport/Páginas/Privadas/Jugador/Estadisticas.php">Estadísticas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Jugador/Encuestas.php">Encuestas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipos Rivales</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Convocatoria.php">Convocatoria</a></li>
                <li><a class="nav-link" href="/TechSport/LogicaPHP/logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main class="estadisticas-container">

        <!-- ================= Información del jugador ================= -->
        <section class="panel info-jugador">
            <div class="jugador-header">
                <div class="jugador-avatar">
                    <div class="camiseta <?= strtolower($jugador['posicion']) ?>">
                        <span class="dorsal"><?= $jugador['dorsal'] ?? '?' ?></span>
                    </div>
                </div>
                <div class="jugador-info">
                    <h1><?= htmlspecialchars($nombre_usuario) ?></h1>
                    <div class="jugador-detalles">
                        <span class="posicion"><?= $jugador['posicion'] ?></span>
                        <span class="separador">•</span>
                        <span class="equipo"><?= $jugador['equipo_nombre'] ?? 'Sin equipo' ?></span>
                        <span class="separador">•</span>
                        <span class="temporada"><?= $jugador['temporada_nombre'] ?? 'Sin temporada' ?></span>
                    </div>
                    <div class="jugador-caracteristicas">
                        <?php if ($edad): ?>
                            <span>Edad: <?= $edad ?> años</span>
                            <span class="separador">•</span>
                        <?php endif; ?>
                        <span>Altura: <?= $jugador['altura'] ?? '0' ?> m</span>
                        <span class="separador">•</span>
                        <span>Peso: <?= $jugador['peso'] ?? '0' ?> kg</span>
                        <?php if ($jugador['nacionalidad']): ?>
                            <span class="separador">•</span>
                            <span>Nacionalidad: <?= $jugador['nacionalidad'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Leyenda de colores para posiciones -->
        <div class="leyenda-colores">
            <div class="color-item">
                <div class="color-muestra" style="background:#4ECDC4;"></div>
                <span>Portero</span>
            </div>
            <div class="color-item">
                <div class="color-muestra" style="background:#06D6A0;"></div>
                <span>Defensa</span>
            </div>
            <div class="color-item">
                <div class="color-muestra" style="background:#FFD166;"></div>
                <span>Centrocampista</span>
            </div>
            <div class="color-item">
                <div class="color-muestra" style="background:#EF476F;"></div>
                <span>Delantero</span>
            </div>
        </div>

        <!-- ================= Estadísticas físicas ================= -->
        <section class="panel">
            <h2>Rendimiento Físico</h2>
            <?php if ($fisico['fecha_medicion']): ?>
                <p class="fecha-medicion">Última medición: <?= date('d/m/Y', strtotime($fisico['fecha_medicion'])) ?></p>
            <?php endif; ?>
            <div class="stats-row">
                <div class="stat-block" data-value="<?= $fisico['velocidad'] ?>">
                    <span>Velocidad</span>
                    <div class="bar">
                        <div class="fill"></div>
                    </div>
                    <strong><?= $fisico['velocidad'] ?>/100</strong>
                </div>
                <div class="stat-block" data-value="<?= $fisico['fuerza'] ?>">
                    <span>Fuerza</span>
                    <div class="bar">
                        <div class="fill"></div>
                    </div>
                    <strong><?= $fisico['fuerza'] ?>/100</strong>
                </div>
                <div class="stat-block" data-value="<?= $fisico['resistencia'] ?>">
                    <span>Resistencia</span>
                    <div class="bar">
                        <div class="fill"></div>
                    </div>
                    <strong><?= $fisico['resistencia'] ?>/100</strong>
                </div>
            </div>
        </section>

        <!-- ================= Estadísticas de convocatorias ================= -->
        <section class="panel">
            <h2>Participación esta Temporada</h2>
            <div class="convocatorias-stats">
                <div class="conv-item conv-titular">
                    <span class="conv-label">Partidos de Titular</span>
                    <span class="conv-value"><?= $conteo_estados['titular'] ?></span>
                </div>
                <div class="conv-item conv-suplente">
                    <span class="conv-label">Partidos de Suplente</span>
                    <span class="conv-value"><?= $conteo_estados['suplente'] ?></span>
                </div>
                <div class="conv-item conv-no-convocado">
                    <span class="conv-label">No Convocado</span>
                    <span class="conv-value"><?= $conteo_estados['no_convocado'] ?></span>
                </div>
            </div>
        </section>

        <!-- ================= Estadísticas del equipo actual ================= -->
        <section class="panel">
            <h2>Estadísticas en <?= $jugador['equipo_nombre'] ?? 'Equipo Actual' ?> (Temporada <?= $jugador['temporada_nombre'] ?? '' ?>)</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Partidos Jugados</span>
                    <span class="stat-value"><?= $stats_equipo_actual['partidos_jugados'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Minutos Jugados</span>
                    <span class="stat-value"><?= $stats_equipo_actual['minutos_jugados'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Goles</span>
                    <span class="stat-value"><?= $stats_equipo_actual['goles'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Asistencias</span>
                    <span class="stat-value"><?= $stats_equipo_actual['asistencias'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Amarillas</span>
                    <span class="stat-value"><?= $stats_equipo_actual['amarillas'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Rojas</span>
                    <span class="stat-value"><?= $stats_equipo_actual['rojas'] ?></span>
                </div>
                <?php if (strtolower($jugador['posicion']) == 'portero'): ?>
                    <div class="stat-item">
                        <span class="stat-label">Porterías Cero</span>
                        <span class="stat-value"><?= $stats_equipo_actual['porterias_cero'] ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ================= Estadísticas carrera total ================= -->
        <section class="panel">
            <h2>Estadísticas Totales de Carrera</h2>
            <div class="stats-temporada">
                <div class="item-t">
                    <span>Partidos Totales</span>
                    <strong><?= $stats_carrera_total['partidos_jugados'] ?></strong>
                </div>
                <div class="item-t">
                    <span>Minutos Totales</span>
                    <strong><?= $stats_carrera_total['minutos_jugados'] ?></strong>
                </div>
                <div class="item-t">
                    <span>Goles Totales</span>
                    <strong><?= $stats_carrera_total['goles'] ?></strong>
                </div>
                <div class="item-t">
                    <span>Asistencias Totales</span>
                    <strong><?= $stats_carrera_total['asistencias'] ?></strong>
                </div>
                <div class="item-t">
                    <span>Amarillas Totales</span>
                    <strong><?= $stats_carrera_total['amarillas'] ?></strong>
                </div>
                <div class="item-t">
                    <span>Rojas Totales</span>
                    <strong><?= $stats_carrera_total['rojas'] ?></strong>
                </div>
                <?php if (strtolower($jugador['posicion']) == 'portero'): ?>
                    <div class="item-t">
                        <span>Porterías Cero Totales</span>
                        <strong><?= $stats_carrera_total['porterias_cero'] ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ================= Historial de equipos ================= -->
        <?php if (!empty($historial_equipos)): ?>
            <section class="panel">
                <h2>Historial de Equipos</h2>
                <div class="equipos-historial">
                    <?php foreach ($historial_equipos as $equipo_hist): ?>
                        <div class="equipo-historial-card">
                            <h3><?= htmlspecialchars($equipo_hist['nombre']) ?></h3>
                            <p class="temporada-hist">Temporada: <?= htmlspecialchars($equipo_hist['temporada_nombre']) ?></p>
                            <?php
                            $fecha_inicio = $equipo_hist['fecha_inicio'] ? date('Y', strtotime($equipo_hist['fecha_inicio'])) : '';
                            $fecha_fin = $equipo_hist['fecha_fin'] ? date('Y', strtotime($equipo_hist['fecha_fin'])) : '';
                            ?>
                            <?php if ($fecha_inicio && $fecha_fin): ?>
                                <p class="temporada-fechas"><?= $fecha_inicio ?> - <?= $fecha_fin ?></p>
                            <?php endif; ?>
                            <div class="equipo-stats">
                                <span>Partidos: <?= $equipo_hist['partidos_jugados'] ?></span>
                                <span>Minutos: <?= $equipo_hist['minutos_jugados'] ?></span>
                                <span>Goles: <?= $equipo_hist['goles'] ?></span>
                                <span>Asistencias: <?= $equipo_hist['asistencias'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- ================= Partidos disputados ================= -->
        <section class="panel">
            <h2>Partidos Disputados esta Temporada</h2>
            <?php if (empty($partidos_detalle)): ?>
                <p class="no-data">Aún no has disputado partidos esta temporada.</p>
            <?php else: ?>
                <?php foreach ($partidos_detalle as $p): ?>
                    <div class="partido-card">
                        <div class="partido-header">
                            <h3><?= htmlspecialchars($p['titulo']) ?></h3>
                            <p class="fecha"><?= date("d/m/Y", strtotime($p['fecha'])) ?></p>
                        </div>
                        <?php if (!empty($p['descripcion'])): ?>
                            <p class="descripcion"><?= htmlspecialchars($p['descripcion']) ?></p>
                        <?php endif; ?>
                        <div class="partido-stats">
                            <div class="stat-line">
                                <span>Estado:</span>
                                <strong class="estado-<?= $p['tipo_convocado'] ?>">
                                    <?= ucfirst($p['tipo_convocado']) ?>
                                </strong>
                            </div>
                            <?php if ($p['dorsal']): ?>
                                <div class="stat-line">
                                    <span>Dorsal:</span>
                                    <strong><?= $p['dorsal'] ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if ($p['posicion_partido']): ?>
                                <div class="stat-line">
                                    <span>Posición:</span>
                                    <strong><?= $p['posicion_partido'] ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if ($p['minutos_jugados'] > 0): ?>
                                <div class="stat-line">
                                    <span>Minutos:</span>
                                    <strong><?= $p['minutos_jugados'] ?>'</strong>
                                </div>
                            <?php endif; ?>
                            <?php if ($p['goles'] > 0): ?>
                                <div class="stat-line highlight">
                                    <span>Goles:</span>
                                    <strong class="goles">⚽ <?= $p['goles'] ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if ($p['asistencias'] > 0): ?>
                                <div class="stat-line highlight">
                                    <span>Asistencias:</span>
                                    <strong class="asistencias">🎯 <?= $p['asistencias'] ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if ($p['amarillas'] > 0): ?>
                                <div class="tarjeta amarilla">🟨 <?= $p['amarillas'] ?></div>
                            <?php endif; ?>
                            <?php if ($p['rojas'] > 0): ?>
                                <div class="tarjeta roja">🟥 <?= $p['rojas'] ?></div>
                            <?php endif; ?>
                            <?php if ($p['porterias_cero'] > 0 && strtolower($jugador['posicion']) == 'portero'): ?>
                                <div class="stat-line highlight">
                                    <span>Portería Cero:</span>
                                    <strong class="porteria-cero">🧤 Sí</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
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

    <script>
        // Animar las barras de estadísticas físicas
        document.addEventListener('DOMContentLoaded', function() {
            const statBlocks = document.querySelectorAll('.stat-block');
            statBlocks.forEach(block => {
                const value = parseInt(block.getAttribute('data-value'));
                const fill = block.querySelector('.fill');
                // Animar la barra después de un pequeño retraso
                setTimeout(() => {
                    fill.style.width = value + '%';

                    // Color basado en el valor
                    if (value < 33) {
                        fill.style.background = '#f56565';
                    } else if (value < 66) {
                        fill.style.background = '#ed8936';
                    } else {
                        fill.style.background = '#48bb78';
                    }
                }, 300);
            });

            // Añadir efectos hover
            const statItems = document.querySelectorAll('.stat-item, .item-t, .conv-item');
            statItems.forEach((item) => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.1)';
                });

                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Efecto para tarjetas de partidos
            const partidoCards = document.querySelectorAll('.partido-card');
            partidoCards.forEach((card) => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.1)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>