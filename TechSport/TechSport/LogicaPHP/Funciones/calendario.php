<?php
// Funciones para el calendario

// Obtener equipo del usuario
function obtenerEquipoUsuario($conn, $usuario_id, $rol_id)
{
    if ($rol_id == 1) { // Entrenador
        // 1. PRIMERO: Verificar si es entrenador principal
        $stmt = $conn->prepare("
            SELECT e.id, e.nombre, e.temporada_id 
            FROM equipos e
            WHERE e.entrenador_id = ? 
            LIMIT 1
        ");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $equipo = $result->fetch_assoc();
        $stmt->close();

        if ($equipo) {
            $equipo['tipo_entrenador'] = 'principal';
            return $equipo;
        }

        // 2. SEGUNDO: Verificar si es entrenador auxiliar
        // Primero obtener el ID de la tabla entrenadores
        $stmt = $conn->prepare("SELECT id FROM entrenadores WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $entrenador = $result->fetch_assoc();
            $entrenador_id = $entrenador['id'];
            $stmt->close();

            // Buscar en equipo_entrenadores usando el id de entrenador
            $stmt = $conn->prepare("
                SELECT e.id, e.nombre, e.temporada_id, ee.activo
                FROM equipos e 
                INNER JOIN equipo_entrenadores ee ON e.id = ee.equipo_id 
                WHERE ee.entrenador_id = ? 
                AND ee.activo = TRUE
                LIMIT 1
            ");
            $stmt->bind_param("i", $entrenador_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $equipo = $result->fetch_assoc();
            $stmt->close();

            if ($equipo) {
                $equipo['tipo_entrenador'] = 'auxiliar';
                $equipo['entrenador_id_tabla'] = $entrenador_id;
                return $equipo;
            }
        } else {
            $stmt->close();
        }

        return null; // No se encontró equipo

    } elseif ($rol_id == 3) { // Profesional
        $stmt = $conn->prepare("
        SELECT e.id, e.nombre, e.temporada_id
        FROM equipos e 
        INNER JOIN equipo_profesionales ep ON e.id = ep.equipo_id 
        WHERE ep.profesional_id = ?  -- ¡Directamente usuario_id!
        LIMIT 1
    ");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $equipo = $result->fetch_assoc();
        $stmt->close();

        return $equipo;
    } elseif ($rol_id == 2) { // Jugador
        $stmt = $conn->prepare("
            SELECT e.id, e.nombre, e.temporada_id, ej.activo
            FROM equipos e 
            INNER JOIN equipo_jugadores ej ON e.id = ej.equipo_id 
            INNER JOIN jugadores j ON ej.jugador_id = j.id
            WHERE j.usuario_id = ?
            AND ej.activo = TRUE
            LIMIT 1
        ");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $equipo = $result->fetch_assoc();
        $stmt->close();

        return $equipo;
    } else {
        return null;
    }
}

// Nueva función: Obtener información detallada del entrenador
function obtenerInfoEntrenadorDetallada($conn, $usuario_id)
{
    $info = [];

    // 1. Verificar si es entrenador principal
    $stmt = $conn->prepare("
        SELECT e.id as equipo_id, e.nombre as equipo_nombre, e.temporada_id,
               'principal' as tipo_entrenador, u.nombre as nombre_entrenador,
               e.entrenador_id as usuario_entrenador_id
        FROM equipos e
        INNER JOIN usuarios u ON e.entrenador_id = u.id
        WHERE e.entrenador_id = ?
    ");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $info = $result->fetch_assoc();
        $info['es_principal'] = true;
    }
    $stmt->close();

    // 2. Si no es principal, verificar si es auxiliar
    if (empty($info)) {
        // Primero obtener el ID de la tabla entrenadores
        $stmt = $conn->prepare("SELECT id, titulo FROM entrenadores WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $entrenador = $result->fetch_assoc();
            $entrenador_id = $entrenador['id'];
            $stmt->close();

            // Buscar en equipo_entrenadores
            $stmt = $conn->prepare("
                SELECT e.id as equipo_id, e.nombre as equipo_nombre, e.temporada_id,
                       'auxiliar' as tipo_entrenador, u.nombre as nombre_entrenador,
                       ee.activo, e.entrenador_id as id_usuario_principal,
                       ent.titulo as titulo_entrenador
                FROM equipo_entrenadores ee
                INNER JOIN equipos e ON ee.equipo_id = e.id
                INNER JOIN entrenadores ent ON ee.entrenador_id = ent.id
                INNER JOIN usuarios u ON ent.usuario_id = u.id
                WHERE ee.entrenador_id = ?
                AND ee.activo = TRUE
                LIMIT 1
            ");
            $stmt->bind_param("i", $entrenador_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $info = $result->fetch_assoc();
                $info['es_principal'] = false;
                $info['entrenador_id_tabla'] = $entrenador_id;

                // Obtener nombre del entrenador principal
                if ($info['id_usuario_principal']) {
                    $stmt2 = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ?");
                    $stmt2->bind_param("i", $info['id_usuario_principal']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($row2 = $result2->fetch_assoc()) {
                        $info['nombre_entrenador_principal'] = $row2['nombre'];
                    }
                    $stmt2->close();
                }
            }
            $stmt->close();
        } else {
            $stmt->close();
        }
    }

    return $info;
}

// Función para verificar si usuario puede gestionar equipo completo
function puedeGestionarEquipoCompleto($conn, $usuario_id, $equipo_id)
{
    // Solo el entrenador principal puede gestionar el equipo completo
    $stmt = $conn->prepare("
        SELECT id FROM equipos 
        WHERE id = ? AND entrenador_id = ?
    ");
    $stmt->bind_param("ii", $equipo_id, $usuario_id);
    $stmt->execute();
    $stmt->store_result();
    $es_principal = $stmt->num_rows > 0;
    $stmt->close();

    return $es_principal;
}

// Función para obtener entrenador principal del equipo
function obtenerEntrenadorPrincipalEquipo($conn, $equipo_id)
{
    $stmt = $conn->prepare("
        SELECT u.id, u.nombre, u.email
        FROM usuarios u
        INNER JOIN equipos e ON u.id = e.entrenador_id
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $equipo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $entrenador = $result->fetch_assoc();
    $stmt->close();

    return $entrenador;
}

// Función para obtener entrenadores auxiliares del equipo
function obtenerEntrenadoresAuxiliaresEquipo($conn, $equipo_id)
{
    $entrenadores = [];

    $stmt = $conn->prepare("
        SELECT u.id, u.nombre, u.email, ent.titulo, ee.activo, ee.fecha_asignacion
        FROM equipo_entrenadores ee
        INNER JOIN entrenadores ent ON ee.entrenador_id = ent.id
        INNER JOIN usuarios u ON ent.usuario_id = u.id
        WHERE ee.equipo_id = ?
        ORDER BY u.nombre
    ");
    $stmt->bind_param("i", $equipo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $entrenadores[] = $row;
    }
    $stmt->close();

    return $entrenadores;
}

// Resto de funciones (sin cambios)
function obtenerEventosEquipo($conn, $equipo_id, $temporada_id)
{
    $eventos = [];
    $stmt = $conn->prepare("SELECT id, equipo_id, titulo, descripcion, fecha, tipo FROM eventos WHERE equipo_id = ? AND temporada_id = ? ORDER BY fecha");
    $stmt->bind_param("ii", $equipo_id, $temporada_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $fecha_obj = new DateTime($row['fecha']);
        $row['fecha_solo'] = $fecha_obj->format('Y-m-d');
        $row['hora_inicio'] = $fecha_obj->format('H:i');
        $row['hora_fin'] = $fecha_obj->modify('+1 hour')->format('H:i');
        $row['color'] = obtenerColorPorTipo($row['tipo']);
        $eventos[] = $row;
    }
    $stmt->close();
    return $eventos;
}

function obtenerColorPorTipo($tipo)
{
    $colores = [
        'reunion' => '#bee3f8',
        'entrenamiento' => '#c6f6d5',
        'partido' => '#fed7d7',
        'gimnasio' => '#e9d8fd',
        'fisio' => '#fefcbf',
        'nutricionista' => '#fed7e2',
        'psicologo' => '#c6f6d5'
    ];
    return $colores[$tipo] ?? '#e2e8f0';
}

function obtenerNombreTipo($tipo)
{
    $nombres = [
        'reunion' => 'Reunión',
        'entrenamiento' => 'Entrenamiento',
        'partido' => 'Partido',
        'gimnasio' => 'Gimnasio',
        'fisio' => 'Fisioterapia',
        'nutricionista' => 'Nutrición',
        'psicologo' => 'Psicología'
    ];
    return $nombres[$tipo] ?? $tipo;
}

function obtenerEventosProximos($eventos)
{
    $hoy = new DateTime();
    $limite = (clone $hoy)->modify('+7 days');

    return array_filter($eventos, function ($evento) use ($hoy, $limite) {
        $fechaEvento = new DateTime($evento['fecha']);
        return $fechaEvento >= $hoy && $fechaEvento <= $limite;
    });
}
