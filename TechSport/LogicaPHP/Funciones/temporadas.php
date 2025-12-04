<?php

function temporadaActual($conexion)
{
    $hoy = new DateTime();
    $anio = (int)$hoy->format("Y");
    $mes = (int)$hoy->format("m");

    // Determinar año de temporada
    if ($mes >= 9) {
        $anio_inicio = $anio;
        $anio_fin = $anio + 1;
    } else {
        $anio_inicio = $anio - 1;
        $anio_fin = $anio;
    }

    $nombre_temporada = "$anio_inicio-$anio_fin";
    $fecha_inicio = "$anio_inicio-09-01";
    $fecha_fin = "$anio_fin-08-31";

    // =============== CHECK TEMPORADA =================
    $sql = $conexion->prepare("SELECT id FROM temporadas WHERE nombre = ?");
    $sql->bind_param("s", $nombre_temporada);
    $sql->execute();
    $result = $sql->get_result();
    $temp = $result->fetch_assoc();

    if ($temp) {
        $temporada_id = $temp['id'];
    } else {
        // Insertar automática
        $sql = $conexion->prepare(
            "INSERT INTO temporadas (nombre, fecha_inicio, fecha_fin) VALUES (?, ?, ?)"
        );
        $sql->bind_param("sss", $nombre_temporada, $fecha_inicio, $fecha_fin);
        $sql->execute();
        $temporada_id = $conexion->insert_id;
    }

    return [
        'id' => $temporada_id,
        'nombre' => $nombre_temporada,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin
    ];
}

 /*
 En el php que vaya utilizar una temporada
    require_once "../../Lógica PHP/Funciones/temporadas.php";

    // Obtener temporada actual
    $temporada = temporadaActual($conexion);
    $temporada_id = $temporada['id'];

    // Ahora puedes usar $temporada_id para filtrar estadísticas, eventos, etc.
    $sqlTotales = $conexion->prepare("
        SELECT SUM(partidos_jugados) as partidos_jugados
        FROM estadisticas_partido
        WHERE jugador_id = ? AND temporada_id = ?
    ");
    $sqlTotales->execute([$jugador_id, $temporada_id]);
    $totales = $sqlTotales->fetch(PDO::FETCH_ASSOC);
 */