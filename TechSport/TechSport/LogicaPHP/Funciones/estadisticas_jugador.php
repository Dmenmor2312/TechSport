<?php
// Funciones para calcular estadísticas de jugador

/**
 * Obtiene estadísticas del jugador en el equipo actual
 */
function obtenerEstadisticasJugadorEquipo($conn, $jugador_id, $equipo_id) {
    $stats = [
        'partidos_titular' => 0,
        'partidos_suplente' => 0,
        'no_convocado' => 0,
        'porterias_cero' => 0,
        'goles' => 0,
        'asistencias' => 0,
        'minutos_jugados' => 0,
        'amarillas' => 0,
        'rojas' => 0,
        'partidos_jugados' => 0
    ];

    // Obtener convocatorias del jugador para este equipo
    $stmt = $conn->prepare("
        SELECT 
            c.id as convocatoria_id,
            c.estado,
            ep.goles,
            ep.asistencias,
            ep.minutos_jugados,
            ep.tarjeta_amarilla,
            ep.tarjeta_roja,
            ep.porteria_cero,
            e.id as evento_id,
            e.titulo,
            e.fecha,
            e.resultado,
            e.estado as estado_evento
        FROM convocatorias c
        INNER JOIN eventos e ON c.evento_id = e.id
        LEFT JOIN estadisticas_partido ep ON c.id = ep.convocatoria_id
        WHERE c.jugador_id = ? 
        AND c.equipo_id = ?
        AND e.tipo = 'partido'
        AND e.estado = 'finalizado'
        ORDER BY e.fecha DESC
    ");
    $stmt->bind_param("ii", $jugador_id, $equipo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        // Contar estado en convocatoria
        switch($row['estado']) {
            case 'titular':
                $stats['partidos_titular']++;
                $stats['partidos_jugados']++;
                break;
            case 'suplente':
                $stats['partidos_suplente']++;
                $stats['partidos_jugados']++;
                break;
            case 'no_convocado':
                $stats['no_convocado']++;
                break;
        }
        
        // Sumar estadísticas del partido si existen
        if ($row['convocatoria_id'] && $row['estado'] != 'no_convocado') {
            $stats['goles'] += $row['goles'] ?? 0;
            $stats['asistencias'] += $row['asistencias'] ?? 0;
            $stats['minutos_jugados'] += $row['minutos_jugados'] ?? 0;
            $stats['amarillas'] += $row['tarjeta_amarilla'] ?? 0;
            $stats['rojas'] += $row['tarjeta_roja'] ?? 0;
            
            // Verificar portería a cero (solo para porteros)
            if ($row['porteria_cero'] == 1) {
                $stats['porterias_cero']++;
            }
        }
    }
    $stmt->close();
    
    return $stats;
}

/**
 * Obtiene estadísticas totales del jugador en toda la temporada actual
 */
function obtenerEstadisticasTemporadaActual($conn, $jugador_id, $temporada_id) {
    $stats = [
        'goles' => 0,
        'asistencias' => 0,
        'minutos_jugados' => 0,
        'amarillas' => 0,
        'rojas' => 0,
        'partidos_jugados' => 0,
        'porterias_cero' => 0
    ];

    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(ep.goles), 0) as total_goles,
            COALESCE(SUM(ep.asistencias), 0) as total_asistencias,
            COALESCE(SUM(ep.minutos_jugados), 0) as total_minutos,
            COALESCE(SUM(ep.tarjeta_amarilla), 0) as total_amarillas,
            COALESCE(SUM(ep.tarjeta_roja), 0) as total_rojas,
            COALESCE(SUM(ep.porteria_cero), 0) as total_porterias_cero,
            COUNT(DISTINCT e.id) as partidos_jugados
        FROM convocatorias c
        INNER JOIN eventos e ON c.evento_id = e.id
        LEFT JOIN estadisticas_partido ep ON c.id = ep.convocatoria_id
        WHERE c.jugador_id = ?
        AND e.temporada_id = ?
        AND e.tipo = 'partido'
        AND e.estado = 'finalizado'
        AND c.estado IN ('titular', 'suplente')
    ");
    $stmt->bind_param("ii", $jugador_id, $temporada_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stats['goles'] = $row['total_goles'];
        $stats['asistencias'] = $row['total_asistencias'];
        $stats['minutos_jugados'] = $row['total_minutos'];
        $stats['amarillas'] = $row['total_amarillas'];
        $stats['rojas'] = $row['total_rojas'];
        $stats['porterias_cero'] = $row['total_porterias_cero'];
        $stats['partidos_jugados'] = $row['partidos_jugados'];
    }
    $stmt->close();
    
    return $stats;
}

/**
 * Obtiene estadísticas totales del jugador en toda su carrera (todos los equipos)
 */
function obtenerEstadisticasTotalesCarrera($conn, $jugador_id) {
    $stats = [
        'goles' => 0,
        'asistencias' => 0,
        'minutos_jugados' => 0,
        'amarillas' => 0,
        'rojas' => 0,
        'partidos_jugados' => 0,
        'porterias_cero' => 0
    ];

    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(ep.goles), 0) as total_goles,
            COALESCE(SUM(ep.asistencias), 0) as total_asistencias,
            COALESCE(SUM(ep.minutos_jugados), 0) as total_minutos,
            COALESCE(SUM(ep.tarjeta_amarilla), 0) as total_amarillas,
            COALESCE(SUM(ep.tarjeta_roja), 0) as total_rojas,
            COALESCE(SUM(ep.porteria_cero), 0) as total_porterias_cero,
            COUNT(DISTINCT e.id) as partidos_jugados
        FROM convocatorias c
        INNER JOIN eventos e ON c.evento_id = e.id
        LEFT JOIN estadisticas_partido ep ON c.id = ep.convocatoria_id
        WHERE c.jugador_id = ?
        AND e.tipo = 'partido'
        AND e.estado = 'finalizado'
        AND c.estado IN ('titular', 'suplente')
    ");
    $stmt->bind_param("i", $jugador_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stats['goles'] = $row['total_goles'];
        $stats['asistencias'] = $row['total_asistencias'];
        $stats['minutos_jugados'] = $row['total_minutos'];
        $stats['amarillas'] = $row['total_amarillas'];
        $stats['rojas'] = $row['total_rojas'];
        $stats['porterias_cero'] = $row['total_porterias_cero'];
        $stats['partidos_jugados'] = $row['partidos_jugados'];
    }
    $stmt->close();
    
    return $stats;
}

/**
 * Obtiene el historial de partidos del jugador
 */
function obtenerHistorialPartidos($conn, $jugador_id, $equipo_id = null) {
    $partidos = [];
    
    $sql = "
        SELECT 
            e.id as evento_id,
            e.fecha,
            e.titulo,
            e.resultado,
            e.descripcion,
            eq.nombre as equipo_nombre,
            c.estado,
            ep.goles,
            ep.asistencias,
            ep.minutos_jugados,
            ep.tarjeta_amarilla,
            ep.tarjeta_roja,
            ep.porteria_cero
        FROM convocatorias c
        INNER JOIN eventos e ON c.evento_id = e.id
        INNER JOIN equipos eq ON c.equipo_id = eq.id
        LEFT JOIN estadisticas_partido ep ON c.id = ep.convocatoria_id
        WHERE c.jugador_id = ?
        AND e.tipo = 'partido'
        AND e.estado = 'finalizado'
    ";
    
    if ($equipo_id) {
        $sql .= " AND c.equipo_id = ?";
    }
    
    $sql .= " ORDER BY e.fecha DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($equipo_id) {
        $stmt->bind_param("ii", $jugador_id, $equipo_id);
    } else {
        $stmt->bind_param("i", $jugador_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $partidos[] = [
            'fecha' => $row['fecha'],
            'titulo' => $row['titulo'],
            'resultado' => $row['resultado'],
            'equipo' => $row['equipo_nombre'],
            'estado' => $row['estado'],
            'goles' => $row['goles'] ?? 0,
            'asistencias' => $row['asistencias'] ?? 0,
            'minutos_jugados' => $row['minutos_jugados'] ?? 0,
            'amarilla' => $row['tarjeta_amarilla'] ?? 0,
            'roja' => $row['tarjeta_roja'] ?? 0,
            'porteria_cero' => $row['porteria_cero'] ?? 0
        ];
    }
    $stmt->close();
    
    return $partidos;
}

/**
 * Obtiene los equipos en los que ha jugado el jugador
 */
function obtenerEquiposHistorial($conn, $jugador_id) {
    $equipos = [];
    
    $stmt = $conn->prepare("
        SELECT DISTINCT
            eq.id,
            eq.nombre,
            eq.temporada_id,
            t.nombre as temporada_nombre
        FROM convocatorias c
        INNER JOIN equipos eq ON c.equipo_id = eq.id
        INNER JOIN temporadas t ON eq.temporada_id = t.id
        INNER JOIN eventos e ON c.evento_id = e.id
        WHERE c.jugador_id = ?
        AND e.tipo = 'partido'
        AND e.estado = 'finalizado'
        ORDER BY t.fecha_inicio DESC
    ");
    $stmt->bind_param("i", $jugador_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $equipos[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'temporada_id' => $row['temporada_id'],
            'temporada_nombre' => $row['temporada_nombre']
        ];
    }
    $stmt->close();
    
    return $equipos;
}
?>