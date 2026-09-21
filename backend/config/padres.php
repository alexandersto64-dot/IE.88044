<?php

// ==========================================
// Funciones compartidas del módulo Padre.
//
// Mismo criterio que profesor_grados.php: centraliza aquí las
// consultas que se repiten en varias páginas (dashboard.php,
// notas.php, comportamiento.php, preinscripcion.php) para no
// duplicarlas. No crea ninguna tabla nueva — usa exactamente las
// que trae migracion_portal_padres.sql (padres_alumnos, notas,
// comportamiento, solicitudes_matricula) más las ya existentes
// (alumnos, matriculas, grados_secciones, periodos_academicos,
// cursos, usuarios).
// ==========================================


/**
 * Hijos vinculados a un usuario PADRE (vía padres_alumnos), con su
 * matrícula ACTIVA más reciente si la tiene. Un alumno sin matrícula
 * activa igual aparece (con los campos de matrícula en null) para
 * que el padre lo vea, aunque de momento no tenga notas/comportamiento
 * que consultar.
 */
function padre_hijos(PDO $conexion, int $idUsuarioPadre): array
{
    $sql = "
        SELECT
            a.id_alumno, a.nombres, a.apellidos, a.dni, a.estado,
            m.id_matricula, m.id_periodo,
            gs.id_grado_seccion, gs.nivel, gs.grado, gs.seccion, gs.nombre AS grado_nombre,
            per.nombre AS periodo_nombre

        FROM padres_alumnos pa

        INNER JOIN alumnos a
            ON a.id_alumno = pa.id_alumno

        LEFT JOIN matriculas m
            ON m.id_alumno = a.id_alumno AND m.estado = 'ACTIVA'

        LEFT JOIN grados_secciones gs
            ON gs.id_grado_seccion = m.id_grado_seccion

        LEFT JOIN periodos_academicos per
            ON per.id_periodo = m.id_periodo

        WHERE pa.id_usuario_padre = ?

        ORDER BY a.apellidos, a.nombres
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$idUsuarioPadre]);

    return $stmt->fetchAll();
}


/**
 * Comprueba que el alumno realmente sea hijo del padre en sesión,
 * antes de mostrarle notas/comportamiento de nadie más. Nunca se
 * confía solo en el id_alumno que llega por GET.
 */
function padre_verificar_hijo(PDO $conexion, int $idUsuarioPadre, int $idAlumno): bool
{
    $stmt = $conexion->prepare("
        SELECT 1 FROM padres_alumnos
        WHERE id_usuario_padre = ? AND id_alumno = ?
        LIMIT 1
    ");
    $stmt->execute([$idUsuarioPadre, $idAlumno]);

    return (bool) $stmt->fetchColumn();
}


/**
 * Notas de un alumno para un periodo dado, agrupadas por curso y
 * bimestre. Si no se indica periodo, usa el de su matrícula activa
 * (si tiene). Devuelve las filas ya con el nombre del curso.
 */
function padre_notas(PDO $conexion, int $idAlumno, ?int $idPeriodo = null): array
{
    $sql = "
        SELECT
            n.id_nota, n.id_curso, c.nombre AS curso_nombre,
            n.bimestre, n.nota_vigesimal, n.nota_literal,
            n.actualizado_en

        FROM notas n

        INNER JOIN cursos c
            ON c.id_curso = n.id_curso

        WHERE n.id_alumno = ?
    ";

    $params = [$idAlumno];

    if ($idPeriodo !== null) {
        $sql .= " AND n.id_periodo = ?";
        $params[] = $idPeriodo;
    }

    $sql .= " ORDER BY n.bimestre, c.nombre";

    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}


/**
 * Último bimestre con al menos una nota registrada, para el
 * resumen del Dashboard (evita mostrar de entrada un bimestre
 * vacío cuando ya hay varios cargados).
 */
function padre_ultimo_bimestre_con_notas(array $notas): ?int
{
    if (count($notas) === 0) {
        return null;
    }

    return (int) max(array_column($notas, "bimestre"));
}


/**
 * Registros de comportamiento de un alumno, más recientes primero.
 * $limite = null trae todos (para comportamiento.php); con número
 * trae solo esa cantidad (para el adelanto del Dashboard).
 */
function padre_comportamiento(PDO $conexion, int $idAlumno, ?int $limite = null): array
{
    $sql = "
        SELECT
            cp.id_comportamiento, cp.tipo, cp.descripcion, cp.creado_en,
            u.nombres AS registrado_por_nombres, u.apellidos AS registrado_por_apellidos,
            per.nombre AS periodo_nombre

        FROM comportamiento cp

        INNER JOIN usuarios u
            ON u.id_usuario = cp.id_usuario_registro

        INNER JOIN periodos_academicos per
            ON per.id_periodo = cp.id_periodo

        WHERE cp.id_alumno = ?

        ORDER BY cp.creado_en DESC
    ";

    if ($limite !== null) {
        $sql .= " LIMIT " . (int) $limite;
    }

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$idAlumno]);

    return $stmt->fetchAll();
}


/**
 * Conteo de comportamiento POSITIVO/NEGATIVO/NEUTRO de un alumno,
 * para las tarjetas de resumen del Dashboard. Una sola consulta
 * agrupada en vez de tres.
 */
function padre_comportamiento_conteo(PDO $conexion, int $idAlumno): array
{
    $conteo = ["POSITIVO" => 0, "NEGATIVO" => 0, "NEUTRO" => 0];

    $stmt = $conexion->prepare("
        SELECT tipo, COUNT(*) AS total
        FROM comportamiento
        WHERE id_alumno = ?
        GROUP BY tipo
    ");
    $stmt->execute([$idAlumno]);

    foreach ($stmt->fetchAll() as $fila) {
        $conteo[$fila["tipo"]] = (int) $fila["total"];
    }

    return $conteo;
}


/**
 * Solicitudes de preinscripción de matrícula hechas por este padre
 * (tabla solicitudes_matricula), más recientes primero.
 */
function padre_solicitudes_matricula(PDO $conexion, int $idUsuarioPadre): array
{
    $stmt = $conexion->prepare("
        SELECT
            sm.id_solicitud_matricula, sm.alumno_nombres, sm.alumno_apellidos,
            sm.alumno_dni, sm.estado, sm.observacion_admin, sm.creado_en,
            gs.nombre AS grado_nombre, gs.nivel,
            per.nombre AS periodo_nombre

        FROM solicitudes_matricula sm

        INNER JOIN grados_secciones gs
            ON gs.id_grado_seccion = sm.id_grado_seccion_deseado

        INNER JOIN periodos_academicos per
            ON per.id_periodo = sm.id_periodo

        WHERE sm.id_usuario_padre = ?

        ORDER BY sm.creado_en DESC
    ");
    $stmt->execute([$idUsuarioPadre]);

    return $stmt->fetchAll();
}


/**
 * Grados/secciones disponibles para el formulario de preinscripción,
 * agrupados por nivel (mismo dato que ya usa admin/matriculas.php).
 */
function padre_grados_secciones_disponibles(PDO $conexion): array
{
    return $conexion
        ->query("SELECT id_grado_seccion, nivel, grado, seccion, nombre FROM grados_secciones ORDER BY nivel, grado, seccion")
        ->fetchAll();
}


/**
 * Periodo académico "actual" para el formulario de preinscripción:
 * el de mayor id (el más reciente creado), mismo criterio simple
 * que ya usa el resto del panel donde no hay una bandera explícita
 * de "periodo activo" en la tabla.
 */
function padre_periodo_actual(PDO $conexion): ?array
{
    $fila = $conexion
        ->query("SELECT id_periodo, nombre FROM periodos_academicos ORDER BY id_periodo DESC LIMIT 1")
        ->fetch();

    return $fila ?: null;
}
