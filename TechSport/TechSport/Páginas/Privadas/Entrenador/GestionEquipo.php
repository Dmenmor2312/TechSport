<?php
session_start();

// Verificar que el usuario esté logueado y sea Entrenador (rol_id = 1)
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    header("Location: /TechSport/LogicaPHP/login.php");
    exit();
}

$rol_id = $_SESSION['rol_id'];
if ($rol_id != 1) { // Entrenador es rol_id = 1
    header("Location: /TechSport/Páginas/Privadas/" . ($rol_id == 2 ? "Jugador" : "Profesional") . "/inicio.php");
    exit();
}

// Obtener datos del usuario
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];

// Incluir conexión a base de datos
require_once $_SERVER['DOCUMENT_ROOT'] . '/TechSport/LogicaPHP/basededatos.php';

// Verificar si la conexión se estableció correctamente
if (!isset($conn) || $conn->connect_error) {
    die("Error de conexión a la base de datos");
}

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// Verificar si el usuario tiene ficha de entrenador
$entrenador_ficha = null;
$sql = "SELECT * FROM entrenadores WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $entrenador_ficha = $result->fetch_assoc();
    $entrenador_id = $entrenador_ficha['id'];
}

// Obtener temporadas activas para el selector
$temporadas = [];
$sql = "SELECT id, nombre, fecha_inicio, fecha_fin FROM temporadas ORDER BY fecha_inicio DESC";
$result = $conn->query($sql);
if ($result) {
    $temporadas = $result->fetch_all(MYSQLI_ASSOC);
}

// Procesar creación de ficha del entrenador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_ficha_entrenador'])) {
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    $titulo = trim($_POST['titulo']);

    if (!empty($fecha_nacimiento) && !empty($titulo)) {
        // Verificar si ya tiene ficha
        if (!$entrenador_ficha) {
            $sql = "INSERT INTO entrenadores (usuario_id, fecha_nacimiento, titulo) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $usuario_id, $fecha_nacimiento, $titulo);

            if ($stmt->execute()) {
                $entrenador_id = $conn->insert_id;
                $entrenador_ficha = ['id' => $entrenador_id, 'usuario_id' => $usuario_id, 'fecha_nacimiento' => $fecha_nacimiento, 'titulo' => $titulo];
                $mensaje = "Ficha de entrenador creada exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al crear ficha: " . $conn->error;
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Ya tienes una ficha de entrenador creada";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Por favor completa todos los campos";
        $tipo_mensaje = "error";
    }
}

// Procesar creación de equipo (solo si tiene ficha de entrenador)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_equipo'])) {
    if (!$entrenador_ficha) {
        $mensaje = "Primero debes crear tu ficha de entrenador";
        $tipo_mensaje = "error";
    } else {
        $nombre_equipo = trim($_POST['nombre_equipo']);
        $temporada_id = trim($_POST['temporada_id']);

        if (!empty($nombre_equipo) && !empty($temporada_id)) {
            // Verificar si el entrenador ya tiene un equipo en esta temporada
            $sql = "SELECT id FROM equipos WHERE entrenador_id = ? AND temporada_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $usuario_id, $temporada_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Crear equipo
                $sql = "INSERT INTO equipos (nombre, entrenador_id, temporada_id) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $nombre_equipo, $usuario_id, $temporada_id);

                if ($stmt->execute()) {
                    $equipo_id = $conn->insert_id;

                    // Añadir entrenador a equipo_entrenadores como principal
                    $sql = "INSERT INTO equipo_entrenadores (equipo_id, entrenador_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $equipo_id, $entrenador_id);
                    $stmt->execute();

                    $mensaje = "Equipo creado exitosamente. Eres el entrenador principal.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al crear el equipo: " . $conn->error;
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "Ya tienes un equipo en esta temporada";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Por favor completa todos los campos";
            $tipo_mensaje = "error";
        }
    }
}

// Procesar añadir entrenador al equipo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['añadir_entrenador'])) {
    $email_entrenador = trim($_POST['email_entrenador']);

    if (!empty($email_entrenador)) {
        // Verificar que el entrenador tenga un equipo y sea entrenador principal
        $sql = "SELECT e.id as equipo_id 
                FROM equipos e 
                INNER JOIN equipo_entrenadores ee ON e.id = ee.equipo_id 
                WHERE e.entrenador_id = ? AND ee.entrenador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $usuario_id, $entrenador_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $equipo = $result->fetch_assoc();
            $equipo_id = $equipo['equipo_id'];

            // Buscar usuario por email (debe ser entrenador - rol_id = 1)
            $sql = "SELECT id, nombre FROM usuarios WHERE email = ? AND rol_id = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email_entrenador);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $usuario_entrenador = $result->fetch_assoc();
                $nuevo_entrenador_user_id = $usuario_entrenador['id'];
                $nuevo_entrenador_nombre = $usuario_entrenador['nombre'];

                // Verificar si ya tiene ficha de entrenador
                $sql = "SELECT id FROM entrenadores WHERE usuario_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $nuevo_entrenador_user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $entrenador_data = $result->fetch_assoc();
                    $nuevo_entrenador_id = $entrenador_data['id'];

                    // Verificar si ya está en el equipo
                    $sql = "SELECT id FROM equipo_entrenadores WHERE equipo_id = ? AND entrenador_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $equipo_id, $nuevo_entrenador_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows == 0) {
                        // Añadir entrenador al equipo
                        $sql = "INSERT INTO equipo_entrenadores (equipo_id, entrenador_id) VALUES (?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ii", $equipo_id, $nuevo_entrenador_id);

                        if ($stmt->execute()) {
                            $mensaje = "Entrenador '$nuevo_entrenador_nombre' añadido al equipo exitosamente";
                            $tipo_mensaje = "success";
                        } else {
                            $mensaje = "Error al añadir entrenador al equipo: " . $conn->error;
                            $tipo_mensaje = "error";
                        }
                    } else {
                        $mensaje = "Este entrenador ya está en el equipo";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Este usuario no tiene ficha de entrenador. Debe crear su ficha primero.";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "No se encontró un entrenador con ese email o no tiene el rol correcto";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "No tienes permisos para añadir entrenadores o no eres entrenador principal";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Por favor introduce el email del entrenador";
        $tipo_mensaje = "error";
    }
}

// Procesar eliminación de equipo
// Procesar eliminación de equipo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_equipo'])) {
    $equipo_id = $_POST['equipo_id'];

    // Verificar que sea el entrenador principal
    $sql = "SELECT id, entrenador_id FROM equipos WHERE id = ? AND entrenador_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $equipo_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $equipo_data = $result->fetch_assoc();
        $entrenador_principal_id = $equipo_data['entrenador_id'];

        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // 1. Eliminar eventos del equipo
            $sql = "DELETE FROM eventos WHERE equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();

            // 2. Eliminar convocatorias del equipo
            $sql = "DELETE FROM convocatorias WHERE equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();

            // 3. Eliminar encuestas del equipo (si existe tabla encuestas)
            // Primero verificar si la tabla existe
            $sql = "SHOW TABLES LIKE 'encuestas'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $sql = "DELETE FROM encuestas WHERE equipo_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $equipo_id);
                $stmt->execute();
            }

            // 4. Obtener IDs de entrenadores secundarios del equipo (no el principal)
            $entrenadores_secundarios_ids = [];
            $sql = "SELECT ee.entrenador_id FROM equipo_entrenadores ee 
                    INNER JOIN entrenadores e ON ee.entrenador_id = e.id 
                    WHERE ee.equipo_id = ? AND e.usuario_id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $equipo_id, $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $entrenadores_secundarios_ids[] = $row['entrenador_id'];
            }

            // 5. Obtener IDs de jugadores del equipo
            $jugadores_ids = [];
            $sql = "SELECT ej.jugador_id FROM equipo_jugadores ej 
                    WHERE ej.equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $jugadores_ids[] = $row['jugador_id'];
            }

            // 6. Obtener IDs de profesionales del equipo
            $profesionales_ids = [];
            $sql = "SELECT ep.profesional_id FROM equipo_profesionales ep 
                    WHERE ep.equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $profesionales_ids[] = $row['profesional_id'];
            }

            // 7. Eliminar relaciones del equipo
            // 7a. Eliminar de equipo_entrenadores
            $sql = "DELETE FROM equipo_entrenadores WHERE equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();

            // 7b. Eliminar de equipo_jugadores
            $sql = "DELETE FROM equipo_jugadores WHERE equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();

            // 7c. Eliminar de equipo_profesionales
            $sql = "DELETE FROM equipo_profesionales WHERE equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();

            // 8. Eliminar fichas individuales (solo si no están en otros equipos)
            // 8a. Eliminar entrenadores secundarios (NO el principal)
            foreach ($entrenadores_secundarios_ids as $entrenador_id) {
                // Verificar si el entrenador está en otros equipos
                $sql = "SELECT COUNT(*) as total FROM equipo_entrenadores 
                        WHERE entrenador_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $entrenador_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['total'] == 0) {
                    $sql = "DELETE FROM entrenadores WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $entrenador_id);
                    $stmt->execute();
                }
            }

            // 8b. Eliminar jugadores (solo si no están en otros equipos)
            foreach ($jugadores_ids as $jugador_id) {
                $sql = "SELECT COUNT(*) as total FROM equipo_jugadores 
                        WHERE jugador_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $jugador_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['total'] == 0) {
                    $sql = "DELETE FROM jugadores WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $jugador_id);
                    $stmt->execute();
                }
            }

            // 8c. Eliminar profesionales (solo si no están en otros equipos)
            foreach ($profesionales_ids as $profesional_id) {
                $sql = "SELECT COUNT(*) as total FROM equipo_profesionales 
                        WHERE profesional_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $profesional_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['total'] == 0) {
                    $sql = "DELETE FROM profesionales WHERE usuario_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $profesional_id);
                    $stmt->execute();
                }
            }

            // 9. Finalmente eliminar el equipo
            $sql = "DELETE FROM equipos WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();

            // Confirmar transacción
            $conn->commit();

            $mensaje = "Equipo eliminado exitosamente. Se han eliminado todos los eventos, convocatorias, encuestas y fichas de miembros (excepto la tuya).";
            $tipo_mensaje = "success";

            // Resetear variables para actualizar la vista
            $equipo = null;
            $jugadores = [];
            $profesionales = [];
            $entrenadores_equipo = [];
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error al eliminar el equipo: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "No tienes permisos para eliminar este equipo";
        $tipo_mensaje = "error";
    }
}

// Procesar añadir jugador al equipo (VERSIÓN SIMPLIFICADA Y CORREGIDA)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['añadir_jugador'])) {
    $email_jugador = trim($_POST['email_jugador']);
    $dorsal = trim($_POST['dorsal']);
    $posicion = $_POST['posicion']; // Directamente del POST
    $fecha_nacimiento_jugador = trim($_POST['fecha_nacimiento_jugador']);
    $nacionalidad_jugador = trim($_POST['nacionalidad_jugador']);
    $altura = $_POST['altura'];
    $peso = $_POST['peso'];

    // Depuración
    error_log("=== DEPURACIÓN AÑADIR JUGADOR ===");
    error_log("Posición recibida en POST: " . $posicion);
    error_log("Tipo de posición: " . gettype($posicion));
    error_log("Valor crudo de posición: " . var_export($posicion, true));

    if (empty($posicion) || $posicion == "0") {
        $mensaje = "ERROR: La posición está vacía o es 0. Valor recibido: " . $posicion;
        $tipo_mensaje = "error";
        error_log("ERROR POSICIÓN: " . $mensaje);
    } elseif (
        !empty($email_jugador) && !empty($dorsal) &&
        !empty($fecha_nacimiento_jugador) && !empty($altura) && !empty($peso)
    ) {
        // Convertir altura y peso
        $altura_float = floatval(str_replace(',', '.', $altura));
        $peso_float = floatval(str_replace(',', '.', $peso));

        // Verificar que el entrenador pertenezca al equipo
        $sql = "SELECT e.id as equipo_id, e.nombre as equipo_nombre
                FROM equipos e 
                INNER JOIN equipo_entrenadores ee ON e.id = ee.equipo_id 
                INNER JOIN entrenadores en ON ee.entrenador_id = en.id 
                WHERE en.usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $equipo = $result->fetch_assoc();
            $equipo_id = $equipo['equipo_id'];

            // Verificar número actual de jugadores
            $sql = "SELECT COUNT(*) as total FROM equipo_jugadores WHERE equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['total'] < 25) {
                // Buscar usuario por email
                $sql = "SELECT id, nombre FROM usuarios WHERE email = ? AND rol_id = 2";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email_jugador);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $usuario_jugador = $result->fetch_assoc();
                    $jugador_user_id = $usuario_jugador['id'];
                    $jugador_nombre = $usuario_jugador['nombre'];

                    // Verificar si ya existe como jugador
                    $sql = "SELECT id FROM jugadores WHERE usuario_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $jugador_user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        // Actualizar jugador existente
                        $jugador = $result->fetch_assoc();
                        $jugador_id = $jugador['id'];

                        $sql = "UPDATE jugadores SET dorsal = ?, posicion = ?, fecha_nacimiento = ?, 
                                nacionalidad = ?, altura = ?, peso = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);

                        error_log("Actualizando jugador ID $jugador_id con posición: '$posicion'");

                        $stmt->bind_param(
                            "isssddi",
                            $dorsal,
                            $posicion,
                            $fecha_nacimiento_jugador,
                            $nacionalidad_jugador,
                            $altura_float,
                            $peso_float,
                            $jugador_id
                        );

                        if ($stmt->execute()) {
                            error_log("UPDATE ejecutado correctamente");

                            // Verificar si ya está en el equipo
                            $sql = "SELECT id FROM equipo_jugadores WHERE equipo_id = ? AND jugador_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("ii", $equipo_id, $jugador_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows == 0) {
                                $sql = "INSERT INTO equipo_jugadores (equipo_id, jugador_id) VALUES (?, ?)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("ii", $equipo_id, $jugador_id);

                                if ($stmt->execute()) {
                                    $mensaje = "Jugador '$jugador_nombre' actualizado y añadido al equipo";
                                    $tipo_mensaje = "success";
                                } else {
                                    $mensaje = "Error al añadir al equipo: " . $conn->error;
                                    $tipo_mensaje = "error";
                                }
                            } else {
                                $mensaje = "Este jugador ya está en el equipo";
                                $tipo_mensaje = "error";
                            }
                        } else {
                            $mensaje = "Error al actualizar jugador: " . $conn->error;
                            $tipo_mensaje = "error";
                            error_log("Error en UPDATE: " . $conn->error);
                        }
                    } else {
                        // Insertar nuevo jugador
                        $sql = "INSERT INTO jugadores (usuario_id, dorsal, posicion, fecha_nacimiento, 
                                nacionalidad, altura, peso) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);

                        error_log("Insertando nuevo jugador con posición: '$posicion'");

                        $stmt->bind_param(
                            "iisssdd",
                            $jugador_user_id,
                            $dorsal,
                            $posicion,
                            $fecha_nacimiento_jugador,
                            $nacionalidad_jugador,
                            $altura_float,
                            $peso_float
                        );

                        if ($stmt->execute()) {
                            $jugador_id = $conn->insert_id;
                            error_log("INSERT ejecutado correctamente, ID: $jugador_id");

                            $sql = "INSERT INTO equipo_jugadores (equipo_id, jugador_id) VALUES (?, ?)";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("ii", $equipo_id, $jugador_id);

                            if ($stmt->execute()) {
                                $mensaje = "Jugador '$jugador_nombre' creado y añadido al equipo";
                                $tipo_mensaje = "success";
                            } else {
                                $mensaje = "Error al añadir al equipo: " . $conn->error;
                                $tipo_mensaje = "error";
                            }
                        } else {
                            $mensaje = "Error al crear jugador: " . $conn->error;
                            $tipo_mensaje = "error";
                            error_log("Error en INSERT: " . $conn->error);
                        }
                    }
                } else {
                    $mensaje = "No se encontró un jugador con ese email";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "El equipo ya tiene 25 jugadores";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "No perteneces a ningún equipo";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Por favor completa todos los campos";
        $tipo_mensaje = "error";
    }
}

// Procesar añadir profesional al equipo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['añadir_profesional'])) {
    $email_profesional = trim($_POST['email_profesional']);
    $cargo = trim($_POST['cargo']);
    $fecha_nacimiento_prof = trim($_POST['fecha_nacimiento_prof']);
    $nacionalidad_prof = trim($_POST['nacionalidad_prof']);

    if (!empty($email_profesional) && !empty($cargo) && !empty($fecha_nacimiento_prof)) {
        // Verificar que el entrenador pertenezca al equipo
        $sql = "SELECT e.id as equipo_id 
                FROM equipos e 
                INNER JOIN equipo_entrenadores ee ON e.id = ee.equipo_id 
                INNER JOIN entrenadores en ON ee.entrenador_id = en.id 
                WHERE en.usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $equipo = $result->fetch_assoc();
            $equipo_id = $equipo['equipo_id'];

            // Buscar usuario por email (debe ser profesional - rol_id = 3)
            $sql = "SELECT id, nombre FROM usuarios WHERE email = ? AND rol_id = 3";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email_profesional);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $usuario_profesional = $result->fetch_assoc();
                $profesional_user_id = $usuario_profesional['id'];
                $profesional_nombre = $usuario_profesional['nombre'];

                // Verificar si ya existe en la tabla profesionales
                $sql = "SELECT id FROM profesionales WHERE usuario_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $profesional_user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $profesional = $result->fetch_assoc();
                    $profesional_id = $profesional['id'];

                    // Actualizar todos los datos del profesional
                    $sql = "UPDATE profesionales SET cargo = ?, fecha_nacimiento = ?, nacionalidad = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $cargo, $fecha_nacimiento_prof, $nacionalidad_prof, $profesional_id);
                    $stmt->execute();
                } else {
                    // Insertar nuevo profesional con todos los datos
                    $sql = "INSERT INTO profesionales (usuario_id, cargo, fecha_nacimiento, nacionalidad) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isss", $profesional_user_id, $cargo, $fecha_nacimiento_prof, $nacionalidad_prof);

                    if ($stmt->execute()) {
                        $profesional_id = $conn->insert_id;
                    } else {
                        $mensaje = "Error al crear profesional: " . $conn->error;
                        $tipo_mensaje = "error";
                    }
                }

                // Verificar si el profesional ya está en el equipo
                $sql = "SELECT id FROM equipo_profesionales WHERE equipo_id = ? AND profesional_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $equipo_id, $profesional_user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 0) {
                    // Añadir profesional al equipo_profesionales
                    $sql = "INSERT INTO equipo_profesionales (equipo_id, profesional_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $equipo_id, $profesional_user_id);

                    if ($stmt->execute()) {
                        $mensaje = "Profesional '$profesional_nombre' añadido al equipo exitosamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al añadir profesional al equipo: " . $conn->error;
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Este profesional ya está en el equipo";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "No se encontró un profesional con ese email o no tiene el rol correcto";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "No perteneces a ningún equipo o no eres entrenador del equipo";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Por favor completa todos los campos obligatorios";
        $tipo_mensaje = "error";
    }
}

// Procesar eliminar jugador del equipo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_jugador'])) {
    $jugador_id = $_POST['jugador_id'];
    $equipo_id = $_POST['equipo_id'];

    // Verificar que el entrenador pertenezca al equipo
    $sql = "SELECT ee.id FROM equipo_entrenadores ee 
                INNER JOIN entrenadores en ON ee.entrenador_id = en.id 
                WHERE ee.equipo_id = ? AND en.usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $equipo_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // 1. Eliminar de equipo_jugadores
            $sql = "DELETE FROM equipo_jugadores WHERE jugador_id = ? AND equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $jugador_id, $equipo_id);
            $stmt->execute();

            // 2. Verificar si el jugador está en otros equipos
            $sql = "SELECT COUNT(*) as total FROM equipo_jugadores WHERE jugador_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $jugador_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            // 3. Si no está en otros equipos, eliminar de jugadores
            if ($row['total'] == 0) {
                $sql = "DELETE FROM jugadores WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $jugador_id);
                $stmt->execute();
            }

            $conn->commit();
            $mensaje = "Jugador eliminado del equipo exitosamente";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error al eliminar jugador: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "No tienes permisos para eliminar jugadores de este equipo";
        $tipo_mensaje = "error";
    }
}

// Procesar eliminar profesional del equipo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_profesional'])) {
    $profesional_id = $_POST['profesional_id']; // Este es el usuario_id
    $equipo_id = $_POST['equipo_id'];

    // Verificar que el entrenador pertenezca al equipo
    $sql = "SELECT ee.id FROM equipo_entrenadores ee 
                INNER JOIN entrenadores en ON ee.entrenador_id = en.id 
                WHERE ee.equipo_id = ? AND en.usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $equipo_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // 1. Eliminar de equipo_profesionales
            $sql = "DELETE FROM equipo_profesionales WHERE profesional_id = ? AND equipo_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $profesional_id, $equipo_id);
            $stmt->execute();

            // 2. Verificar si el profesional está en otros equipos
            $sql = "SELECT COUNT(*) as total FROM equipo_profesionales WHERE profesional_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $profesional_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            // 3. Si no está en otros equipos, eliminar de profesionales
            if ($row['total'] == 0) {
                $sql = "DELETE FROM profesionales WHERE usuario_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $profesional_id);
                $stmt->execute();
            }

            $conn->commit();
            $mensaje = "Profesional eliminado del equipo exitosamente";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error al eliminar profesional: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "No tienes permisos para eliminar profesionales de este equipo";
        $tipo_mensaje = "error";
    }
}

// Procesar eliminar entrenador del equipo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_entrenador'])) {
    $entrenador_eliminar_id = $_POST['entrenador_id']; // ID de la tabla entrenadores
    $equipo_id = $_POST['equipo_id'];

    // Verificar que el usuario sea entrenador principal del equipo
    $sql = "SELECT e.id FROM equipos e WHERE e.id = ? AND e.entrenador_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $equipo_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Verificar que no sea el entrenador principal (no puede eliminarse a sí mismo)
        $sql = "SELECT usuario_id FROM entrenadores WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $entrenador_eliminar_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $entrenador_data = $result->fetch_assoc();

            if ($entrenador_data['usuario_id'] != $usuario_id) {
                // Iniciar transacción
                $conn->begin_transaction();

                try {
                    // 1. Eliminar de equipo_entrenadores
                    $sql = "DELETE FROM equipo_entrenadores WHERE entrenador_id = ? AND equipo_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $entrenador_eliminar_id, $equipo_id);
                    $stmt->execute();

                    // 2. Verificar si el entrenador está en otros equipos
                    $sql = "SELECT COUNT(*) as total FROM equipo_entrenadores WHERE entrenador_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $entrenador_eliminar_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();

                    // 3. Si no está en otros equipos, eliminar de entrenadores
                    if ($row['total'] == 0) {
                        $sql = "DELETE FROM entrenadores WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $entrenador_eliminar_id);
                        $stmt->execute();
                    }

                    $conn->commit();
                    $mensaje = "Entrenador eliminado del equipo exitosamente";
                    $tipo_mensaje = "success";
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensaje = "Error al eliminar entrenador: " . $e->getMessage();
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "No puedes eliminarte a ti mismo como entrenador principal";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Entrenador no encontrado";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "No tienes permisos para eliminar entrenadores de este equipo";
        $tipo_mensaje = "error";
    }
}

// Obtener equipo del entrenador (si pertenece a alguno)
$equipo = null;
$jugadores = [];
$profesionales = [];
$entrenadores_equipo = [];
$es_entrenador_principal = false;

if ($entrenador_ficha) {
    // Verificar si el entrenador pertenece a algún equipo
    $sql = "SELECT e.*, t.nombre as temporada_nombre,
                       CASE WHEN e.entrenador_id = ? THEN 1 ELSE 0 END as es_principal
                FROM equipos e 
                INNER JOIN temporadas t ON e.temporada_id = t.id 
                INNER JOIN equipo_entrenadores ee ON e.id = ee.equipo_id 
                INNER JOIN entrenadores en ON ee.entrenador_id = en.id 
                WHERE en.usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $usuario_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $equipo = $result->fetch_assoc();
        $equipo_id = $equipo['id'];
        $es_entrenador_principal = ($equipo['es_principal'] == 1);

        // Obtener jugadores del equipo (CONSULTA CORREGIDA - SIN CONFLICTOS)
        $sql = "SELECT 
                    j.id,
                    j.usuario_id,
                    j.dorsal,
                    j.posicion,
                    j.fecha_nacimiento,
                    j.nacionalidad,
                    j.altura,
                    j.peso,
                    u.nombre,
                    u.email
                FROM equipo_jugadores ej
                INNER JOIN jugadores j ON ej.jugador_id = j.id
                INNER JOIN usuarios u ON j.usuario_id = u.id
                WHERE ej.equipo_id = ? 
                ORDER BY j.dorsal";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $equipo_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Depuración de la consulta
        error_log("=== DEPURACIÓN CONSULTA JUGADORES ===");
        error_log("SQL: " . $sql);
        error_log("Equipo ID: " . $equipo_id);

        $jugadores = [];
        while ($row = $result->fetch_assoc()) {
            error_log("Jugador obtenido: " . $row['nombre'] . " - Posición: '" . $row['posicion'] . "'");
            $jugadores[] = $row;
        }
        error_log("Total jugadores: " . count($jugadores));

        // Obtener profesionales del equipo
        $sql = "SELECT p.*, u.nombre, u.email, u.id as usuario_id
                FROM equipo_profesionales ep
                INNER JOIN usuarios u ON ep.profesional_id = u.id
                LEFT JOIN profesionales p ON u.id = p.usuario_id
                WHERE ep.equipo_id = ? 
                ORDER BY p.cargo";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $equipo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profesionales = $result->fetch_all(MYSQLI_ASSOC);

        // Obtener entrenadores del equipo
        $sql = "SELECT en.*, u.nombre, u.email, u.id as usuario_id,
                       CASE WHEN e.entrenador_id = u.id THEN 1 ELSE 0 END as es_principal
                FROM equipo_entrenadores ee
                INNER JOIN entrenadores en ON ee.entrenador_id = en.id
                INNER JOIN usuarios u ON en.usuario_id = u.id
                INNER JOIN equipos e ON ee.equipo_id = e.id
                WHERE ee.equipo_id = ? 
                ORDER BY es_principal DESC, u.nombre";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $equipo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $entrenadores_equipo = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechSport - Gestión de Equipo</title>
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/HeaderFooter/estilos.css">
    <link rel="stylesheet" href="/TechSport/Recursos/CSS/Entrenador/gestion_equipo.css">
    <style>
        .altura-peso-container {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .altura-peso-container .form-group {
            flex: 1;
        }

        .unidad-input {
            display: flex;
            align-items: center;
        }

        .unidad-input input {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .unidad {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-left: 0;
            padding: 8px 12px;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
            color: #718096;
        }

        .range-info {
            font-size: 12px;
            color: #718096;
            margin-top: 4px;
        }

        /* Estilo para depuración */
        .debug-info {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            display: none;
            /* Oculto por defecto */
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
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/inicio.php">Inicio</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/Estadisticas.html">Estadísticas</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/EquiposRivales.php">Equipo Rivales</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/CrearEncuestas.php">Crear Encuesta</a></li>
                <li><a class="nav-link" href="/TechSport/Páginas/Privadas/Entrenador/Convocatoria.php">Convocatoria</a></li>
                <li><a class="nav-link" href="/TechSport/LogicaPHP/logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>


    <main class="main-container">
        <div class="welcome-message">
            <h1>Gestión de Equipo</h1>
            <p>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?> (Entrenador)</p>
            <?php if ($entrenador_ficha): ?>
                <div class="role-indicator">
                    <span class="entrenador-badge">Ficha creada</span>
                    <?php if ($equipo): ?>
                        <?php if ($es_entrenador_principal): ?>
                            <span class="principal-badge">Entrenador Principal</span>
                        <?php else: ?>
                            <span class="entrenador-badge">Entrenador</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <span><?php echo htmlspecialchars($mensaje); ?></span>
                <button class="close" onclick="this.parentElement.style.display='none'">×</button>
            </div>
        <?php endif; ?>

        <!-- Información de depuración (solo para desarrollo) -->
        <div class="debug-info">
            <h3>Información de Depuración:</h3>
            <p>Total jugadores: <?php echo count($jugadores); ?></p>
            <?php if (count($jugadores) > 0): ?>
                <p>Primer jugador: <?php echo htmlspecialchars($jugadores[0]['nombre'] ?? 'N/A'); ?></p>
                <p>Posición primer jugador: "<?php echo htmlspecialchars($jugadores[0]['posicion'] ?? 'N/A'); ?>"</p>
                <p>Tipo de posición: <?php echo gettype($jugadores[0]['posicion'] ?? 'null'); ?></p>
            <?php endif; ?>
        </div>

        <div class="dashboard-grid">
            <!-- Ficha del Entrenador -->
            <?php if (!$entrenador_ficha): ?>
                <div class="card team-info">
                    <h2>Crear Ficha de Entrenador</h2>
                    <p>Antes de crear o unirte a un equipo, debes completar tu ficha personal</p>
                    <form method="POST">
                        <div class="form-group">
                            <label for="fecha_nacimiento" class="required">Fecha de Nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" required
                                max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="titulo" class="required">Título/Certificación</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" required
                                placeholder="Ej: UEFA Pro License, Entrenador Nacional...">
                        </div>
                        <button type="submit" name="crear_ficha_entrenador" class="btn">Crear Ficha</button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Información del Equipo -->
                <div class="card team-info">
                    <h2>Información del Equipo</h2>
                    <?php if ($equipo): ?>
                        <div class="team-details">
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($equipo['nombre']); ?></p>
                            <p><strong>Temporada:</strong> <?php echo htmlspecialchars($equipo['temporada_nombre']); ?></p>

                            <div class="stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo count($jugadores); ?></span>
                                    <span class="stat-label">Jugadores</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo count($profesionales); ?></span>
                                    <span class="stat-label">Profesionales</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo count($entrenadores_equipo); ?></span>
                                    <span class="stat-label">Entrenadores</span>
                                </div>
                            </div>

                            <?php if ($es_entrenador_principal): ?>
                                <form method="POST" style="margin-top: 20px;">
                                    <input type="hidden" name="equipo_id" value="<?php echo $equipo['id']; ?>">
                                    <button type="submit" name="eliminar_equipo" class="btn btn-danger"
                                        onclick="return confirm('¿Estás seguro de eliminar el equipo? Se eliminarán TODOS los miembros asociados. Esta acción no se puede deshacer.')">
                                        Eliminar Equipo
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No tienes un equipo creado</p>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="nombre_equipo" class="required">Nombre del Equipo</label>
                                    <input type="text" placeholder="Nombre 'Categoria'" id="nombre_equipo" name="nombre_equipo" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="temporada_id" class="required">Temporada</label>
                                    <select id="temporada_id" name="temporada_id" class="form-control" required>
                                        <option value="">Seleccionar temporada</option>
                                        <?php foreach ($temporadas as $temporada): ?>
                                            <option value="<?php echo $temporada['id']; ?>">
                                                <?php echo htmlspecialchars($temporada['nombre'] . ' (' . $temporada['fecha_inicio'] . ' - ' . $temporada['fecha_fin'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="crear_equipo" class="btn">Crear Equipo</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Añadir Entrenador (solo entrenador principal) -->
                <?php if ($equipo && $es_entrenador_principal): ?>
                    <div class="card">
                        <h2>Añadir Entrenador</h2>
                        <form method="POST">
                            <div class="form-group">
                                <label for="email_entrenador" class="required">Email del Entrenador</label>
                                <input type="email" id="email_entrenador" name="email_entrenador" class="form-control" required
                                    placeholder="entrenador@ejemplo.com">
                                <small class="text-muted">El usuario debe tener rol de Entrenador (rol_id=1) y ficha creada</small>
                            </div>
                            <button type="submit" name="añadir_entrenador" class="btn">Añadir Entrenador</button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Añadir Jugador -->
                <?php if ($equipo): ?>
                    <div class="card">
                        <h2>Añadir Jugador</h2>
                        <form method="POST" id="form-jugador">
                            <div class="form-group">
                                <label for="email_jugador" class="required">Email del Jugador</label>
                                <input type="email" id="email_jugador" name="email_jugador" class="form-control" required
                                    placeholder="jugador@ejemplo.com">
                                <small class="text-muted">El usuario debe tener rol de Jugador (rol_id=2)</small>
                            </div>

                            <div class="two-columns">
                                <div class="form-group">
                                    <label for="dorsal" class="required">Dorsal</label>
                                    <input type="number" id="dorsal" name="dorsal" class="form-control" required
                                        min="1" max="99" placeholder="Ej: 10">
                                </div>
                                <div class="form-group">
                                    <label for="posicion" class="required">Posición</label>
                                    <select id="posicion" name="posicion" class="form-control" required>
                                        <option value="">Seleccionar posición</option>
                                        <option value="Portero">Portero</option>
                                        <option value="Defensa">Defensa</option>
                                        <option value="Centrocampista">Centrocampista</option>
                                        <option value="Delantero">Delantero</option>
                                    </select>
                                </div>
                            </div>

                            <div class="two-columns">
                                <div class="form-group">
                                    <label for="fecha_nacimiento_jugador" class="required">Fecha Nacimiento</label>
                                    <input type="date" id="fecha_nacimiento_jugador" name="fecha_nacimiento_jugador"
                                        class="form-control" required max="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="nacionalidad_jugador">Nacionalidad</label>
                                    <input type="text" id="nacionalidad_jugador" name="nacionalidad_jugador"
                                        class="form-control" placeholder="Ej: España">
                                </div>
                            </div>

                            <!-- Altura y Peso -->
                            <div class="altura-peso-container">
                                <div class="form-group">
                                    <label for="altura" class="required">Altura</label>
                                    <div class="unidad-input">
                                        <input type="text" id="altura" name="altura"
                                            class="form-control" required placeholder="1.80">
                                        <span class="unidad">m</span>
                                    </div>
                                    <div class="range-info">Ej: 1.80 (1 metro 80 cm)</div>
                                </div>

                                <div class="form-group">
                                    <label for="peso" class="required">Peso</label>
                                    <div class="unidad-input">
                                        <input type="text" id="peso" name="peso"
                                            class="form-control" required placeholder="75.5">
                                        <span class="unidad">kg</span>
                                    </div>
                                    <div class="range-info">Ej: 75.5 kg</div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="añadir_jugador" class="btn">Añadir Jugador</button>
                                <small style="color: #718096; align-self: center;">
                                    <?php echo count($jugadores); ?>/25 jugadores
                                </small>
                            </div>
                        </form>
                    </div>

                    <!-- Añadir Profesional -->
                    <div class="card">
                        <h2>Añadir Profesional</h2>
                        <form method="POST">
                            <div class="form-group">
                                <label for="email_profesional" class="required">Email del Profesional</label>
                                <input type="email" id="email_profesional" name="email_profesional" class="form-control" required
                                    placeholder="profesional@ejemplo.com">
                                <small class="text-muted">El usuario debe tener rol de Profesional (rol_id=3)</small>
                            </div>

                            <div class="two-columns">
                                <div class="form-group">
                                    <label for="cargo" class="required">Cargo</label>
                                    <select id="cargo" name="cargo" class="form-control" required>
                                        <option value="">Seleccionar cargo</option>
                                        <option value="Fisioterapeuta">Fisioterapeuta</option>
                                        <option value="Preparador Físico">Preparador Físico</option>
                                        <option value="Médico">Médico</option>
                                        <option value="Nutricionista">Nutricionista</option>
                                        <option value="Psicólogo">Psicólogo</option>
                                        <option value="Utilero">Utilero</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="fecha_nacimiento_prof" class="required">Fecha Nacimiento</label>
                                    <input type="date" id="fecha_nacimiento_prof" name="fecha_nacimiento_prof"
                                        class="form-control" required max="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="nacionalidad_prof">Nacionalidad</label>
                                <input type="text" id="nacionalidad_prof" name="nacionalidad_prof"
                                    class="form-control" placeholder="Ej: España">
                            </div>

                            <button type="submit" name="añadir_profesional" class="btn">Añadir Profesional</button>
                        </form>
                    </div>

                    <!-- Lista de Entrenadores -->
                    <div class="card">
                        <h2>Entrenadores del Equipo (<?php echo count($entrenadores_equipo); ?>)</h2>
                        <div class="list-container">
                            <?php if (count($entrenadores_equipo) > 0): ?>
                                <?php foreach ($entrenadores_equipo as $entrenador): ?>
                                    <div class="list-item">
                                        <div class="list-item-info">
                                            <h4>
                                                <?php echo htmlspecialchars($entrenador['nombre']); ?>
                                                <?php if ($entrenador['es_principal'] == 1): ?>
                                                    <span class="principal-badge">Principal</span>
                                                <?php else: ?>
                                                    <span class="entrenador-badge">Entrenador</span>
                                                <?php endif; ?>
                                            </h4>
                                            <p>Email: <?php echo htmlspecialchars($entrenador['email']); ?></p>
                                            <p>Título: <?php echo htmlspecialchars($entrenador['titulo']); ?></p>
                                            <?php if (!empty($entrenador['fecha_nacimiento'])): ?>
                                                <p>Nacimiento: <?php echo date('d/m/Y', strtotime($entrenador['fecha_nacimiento'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($es_entrenador_principal && $entrenador['es_principal'] == 0): ?>
                                            <form method="POST" style="margin: 0;">
                                                <input type="hidden" name="entrenador_id" value="<?php echo $entrenador['id']; ?>">
                                                <input type="hidden" name="equipo_id" value="<?php echo $equipo['id']; ?>">
                                                <button type="submit" name="eliminar_entrenador" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('¿Eliminar a <?php echo addslashes($entrenador['nombre']); ?> del equipo? Se borrará su ficha si no está en otros equipos.')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <p>No hay entrenadores en el equipo</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Lista de Jugadores -->
                    <div class="card">
                        <h2>Jugadores del Equipo (<?php echo count($jugadores); ?>/25)</h2>
                        <div class="list-container">
                            <?php if (count($jugadores) > 0): ?>
                                <?php foreach ($jugadores as $jugador): ?>
                                    <div class="list-item">
                                        <div class="list-item-info">
                                            <h4>
                                                <span class="dorsal-badge"><?php echo htmlspecialchars($jugador['dorsal']); ?></span>
                                                <?php echo htmlspecialchars($jugador['nombre']); ?>
                                            </h4>
                                            <p>Email: <?php echo htmlspecialchars($jugador['email']); ?></p>
                                            <p>Posición:
                                                <?php
                                                // Depuración de la posición
                                                $posicion_display = $jugador['posicion'];
                                                error_log("Mostrando jugador " . $jugador['nombre'] . " - Posición en BD: '" . $posicion_display . "'");

                                                if (empty($posicion_display) || $posicion_display == "0") {
                                                    echo '<span style="color: red">' . htmlspecialchars($posicion_display) . ' (ERROR)</span>';
                                                } else {
                                                    echo htmlspecialchars($posicion_display);
                                                }
                                                ?>
                                                | Nacimiento: <?php echo date('d/m/Y', strtotime($jugador['fecha_nacimiento'])); ?>
                                            </p>
                                            <?php if (!empty($jugador['nacionalidad'])): ?>
                                                <p>Nacionalidad: <?php echo htmlspecialchars($jugador['nacionalidad']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($jugador['altura'])): ?>
                                                <p>Altura: <?php echo number_format($jugador['altura'], 2); ?> m</p>
                                            <?php endif; ?>
                                            <?php if (!empty($jugador['peso'])): ?>
                                                <p>Peso: <?php echo number_format($jugador['peso'], 1); ?> kg</p>
                                            <?php endif; ?>
                                        </div>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="jugador_id" value="<?php echo $jugador['id']; ?>">
                                            <input type="hidden" name="equipo_id" value="<?php echo $equipo['id']; ?>">
                                            <button type="submit" name="eliminar_jugador" class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Eliminar a <?php echo addslashes($jugador['nombre']); ?> del equipo? Se borrará su ficha completa si no está en otros equipos.')">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <p>No hay jugadores en el equipo</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Lista de Profesionales -->
                    <div class="card">
                        <h2>Profesionales del Equipo (<?php echo count($profesionales); ?>)</h2>
                        <div class="list-container">
                            <?php if (count($profesionales) > 0): ?>
                                <?php foreach ($profesionales as $profesional): ?>
                                    <div class="list-item">
                                        <div class="list-item-info">
                                            <h4>
                                                <?php echo htmlspecialchars($profesional['nombre']); ?>
                                                <?php if (!empty($profesional['cargo'])): ?>
                                                    <span class="cargo-badge"><?php echo htmlspecialchars($profesional['cargo']); ?></span>
                                                <?php endif; ?>
                                            </h4>
                                            <p>Email: <?php echo htmlspecialchars($profesional['email']); ?></p>
                                            <?php if (!empty($profesional['cargo'])): ?>
                                                <p>Cargo: <?php echo htmlspecialchars($profesional['cargo']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($profesional['fecha_nacimiento'])): ?>
                                                <p>Nacimiento: <?php echo date('d/m/Y', strtotime($profesional['fecha_nacimiento'])); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($profesional['nacionalidad'])): ?>
                                                <p>Nacionalidad: <?php echo htmlspecialchars($profesional['nacionalidad']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="profesional_id" value="<?php echo $profesional['usuario_id']; ?>">
                                            <input type="hidden" name="equipo_id" value="<?php echo $equipo['id']; ?>">
                                            <button type="submit" name="eliminar_profesional" class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Eliminar a <?php echo addslashes($profesional['nombre']); ?> del equipo? Se borrará su ficha completa si no está en otros equipos.')">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <p>No hay profesionales en el equipo</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
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
        // Mostrar/ocultar información de depuración
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                const debugInfo = document.querySelector('.debug-info');
                debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
            }
        });

        // Cerrar alertas automáticamente
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);

        // Validación del formulario
        document.getElementById('form-jugador')?.addEventListener('submit', function(e) {
            const posicion = document.getElementById('posicion');
            const altura = document.getElementById('altura');
            const peso = document.getElementById('peso');

            if (posicion.value === '') {
                e.preventDefault();
                alert('Por favor, selecciona una posición');
                posicion.focus();
                return false;
            }

            if (!altura.value || isNaN(parseFloat(altura.value))) {
                e.preventDefault();
                alert('Por favor, introduce una altura válida (ej: 1.80)');
                altura.focus();
                return false;
            }

            if (!peso.value || isNaN(parseFloat(peso.value))) {
                e.preventDefault();
                alert('Por favor, introduce un peso válido (ej: 75.5)');
                peso.focus();
                return false;
            }

            return true;
        });

        // Establecer fecha máxima
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.max = today;
        });
    </script>
</body>

</html>