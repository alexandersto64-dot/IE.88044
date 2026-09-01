<?php

session_start();


// ==================================================
// 1. COMPROBAR QUE EL USUARIO ESTÉ LOGUEADO
// ==================================================

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.html");
    exit;

}


// ==================================================
// 2. COMPROBAR QUE SEA PROFESOR
// ==================================================

if (!in_array($_SESSION["rol"], ["PROFESOR", "PROFESOR_PRIMARIA", "PROFESOR_SECUNDARIO"], true)) {

    die("Acceso no autorizado.");

}


// ==================================================
// 3. CONECTAR CON LA BASE DE DATOS
// ==================================================

require_once "../backend/config/database.php";
require_once "../backend/config/seguridad_csrf.php";
require_once "../backend/config/notificaciones.php";
require_once "../backend/config/subida_archivos.php";
require_once "../backend/config/flash.php";
require_once "../backend/config/profesor_grados.php";

// ==================================================
// 4. OBTENER LOS DATOS DEL PROFESOR
// ==================================================

$sql = "
    SELECT
        p.id_profesor,
        u.nombres,
        u.apellidos,
        u.correo,
        p.especialidad

    FROM profesores p

    INNER JOIN usuarios u
        ON p.id_usuario = u.id_usuario

    WHERE p.id_usuario = ?
";


$stmt = $conexion->prepare($sql);

$stmt->execute([
    $_SESSION["id_usuario"]
]);

$profesor = $stmt->fetch();


// ==================================================
// 5. COMPROBAR QUE EXISTA EL PROFESOR
// ==================================================

if (!$profesor) {

    die("No se encontró el perfil del profesor.");

}


// ==================================================
// 5.2 MIS ALUMNOS (aulas asignadas al profesor)
// ==================================================

$misAlumnos = $conexion->prepare("
    SELECT
        a.nombres, a.apellidos, a.dni, a.estado,
        gs.nombre AS grado_seccion, gs.nivel

    FROM asignaciones_docentes ad

    INNER JOIN grados_secciones gs ON gs.id_grado_seccion = ad.id_grado_seccion
    INNER JOIN matriculas m ON m.id_grado_seccion = gs.id_grado_seccion AND m.estado = 'ACTIVA'
    INNER JOIN alumnos a ON a.id_alumno = m.id_alumno

    WHERE ad.id_profesor = ?

    ORDER BY gs.nivel, gs.grado, gs.seccion, a.apellidos, a.nombres
");

$misAlumnos->execute([$profesor["id_profesor"]]);
$misAlumnos = $misAlumnos->fetchAll();


// ==================================================
// 5.1 CREAR / EDITAR / ELIMINAR TRABAJOS
//
// IMPORTANTE: todas las consultas de edición/eliminación
// llevan "AND id_profesor = ?" (con el id_profesor de la
// sesión actual, nunca uno enviado por el formulario), para
// que un profesor no pueda modificar ni eliminar trabajos de
// otro aunque manipule el id_trabajo en la petición.
// ==================================================

$mensaje = null;
$mensajeTipo = null;
$mensajeEnvio = null;
$mensajeEnvioTipo = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"])) {

    csrf_verificar();

    $accion = $_POST["accion"];

    if ($accion === "crear") {

        $titulo = trim($_POST["titulo"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $fecha_limite = trim($_POST["fecha_limite"] ?? "");
        $id_curso = $_POST["id_curso"] ?? "";
        $id_tipo_trabajo = $_POST["id_tipo_trabajo"] ?? "";
        $id_periodo = $_POST["id_periodo"] ?? "";

        if ($titulo === "" || $fecha_limite === "" || $id_curso === "" || $id_tipo_trabajo === "" || $id_periodo === "") {

            $mensaje = "Debe completar todos los campos obligatorios.";
            $mensajeTipo = "error";

        } else {

            $stmt = $conexion->prepare("
                INSERT INTO trabajos (titulo, descripcion, fecha_limite, estado, id_curso, id_tipo_trabajo, id_periodo, id_profesor)
                VALUES (?, ?, ?, 'PENDIENTE', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $titulo,
                $descripcion !== "" ? $descripcion : null,
                $fecha_limite,
                $id_curso,
                $id_tipo_trabajo,
                $id_periodo,
                $profesor["id_profesor"],
            ]);

            $mensaje = "Trabajo creado correctamente.";
            $mensajeTipo = "success";

        }

    } elseif ($accion === "guardar_edicion") {

        $id_trabajo = (int)($_POST["id_trabajo"] ?? 0);
        $titulo = trim($_POST["titulo"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $fecha_limite = trim($_POST["fecha_limite"] ?? "");
        $estado = $_POST["estado"] ?? "PENDIENTE";
        $id_curso = $_POST["id_curso"] ?? "";
        $id_tipo_trabajo = $_POST["id_tipo_trabajo"] ?? "";
        $id_periodo = $_POST["id_periodo"] ?? "";

        if ($id_trabajo === 0 || $titulo === "" || $fecha_limite === "" || $id_curso === "" || $id_tipo_trabajo === "" || $id_periodo === "") {

            $mensaje = "Debe completar todos los campos obligatorios.";
            $mensajeTipo = "error";

        } else {

            $stmt = $conexion->prepare("
                UPDATE trabajos
                SET titulo = ?, descripcion = ?, fecha_limite = ?, estado = ?,
                    id_curso = ?, id_tipo_trabajo = ?, id_periodo = ?
                WHERE id_trabajo = ? AND id_profesor = ?
            ");
            $stmt->execute([
                $titulo,
                $descripcion !== "" ? $descripcion : null,
                $fecha_limite,
                $estado,
                $id_curso,
                $id_tipo_trabajo,
                $id_periodo,
                $id_trabajo,
                $profesor["id_profesor"],
            ]);

            if ($stmt->rowCount() === 0) {

                $mensaje = "No se pudo editar: el trabajo no existe o no le pertenece.";
                $mensajeTipo = "error";

            } else {

                $mensaje = "Trabajo actualizado correctamente.";
                $mensajeTipo = "success";

            }

        }

        header("Location: dashboard.php");
        exit;

    } elseif ($accion === "eliminar") {

        $id_trabajo = (int)($_POST["id_trabajo"] ?? 0);

        $stmt = $conexion->prepare("DELETE FROM trabajos WHERE id_trabajo = ? AND id_profesor = ?");
        $stmt->execute([$id_trabajo, $profesor["id_profesor"]]);

        if ($stmt->rowCount() === 0) {

            $mensaje = "No se pudo eliminar: el trabajo no existe o no le pertenece.";
            $mensajeTipo = "error";

        } else {

            $mensaje = "Trabajo eliminado correctamente.";
            $mensajeTipo = "success";

        }

    } elseif ($accion === "enviar_trabajo") {

        $titulo = trim($_POST["envio_titulo"] ?? "");
        $descripcion = trim($_POST["envio_descripcion"] ?? "");

        if ($titulo === "") {

            $mensajeEnvio = "Debe indicar un título para el trabajo que envía a Subdirección.";
            $mensajeEnvioTipo = "error";

        } else {

            try {

                $archivoInfo = envios_guardar_archivo($_FILES["envio_archivo"] ?? []);

                $conexion->beginTransaction();

                $stmt = $conexion->prepare("
                    INSERT INTO envios_trabajo (id_profesor, titulo, descripcion, estado, version_actual)
                    VALUES (?, ?, ?, 'ENVIADO', 1)
                ");
                $stmt->execute([$profesor["id_profesor"], $titulo, $descripcion !== "" ? $descripcion : null]);
                $idEnvio = (int) $conexion->lastInsertId();

                $stmt = $conexion->prepare("
                    INSERT INTO envios_trabajo_historial
                        (id_envio, version, nombre_archivo, ruta_archivo, extension, tamano_bytes, estado)
                    VALUES (?, 1, ?, ?, ?, ?, 'ENVIADO')
                ");
                $stmt->execute([
                    $idEnvio,
                    $archivoInfo["nombre_archivo"],
                    $archivoInfo["ruta_archivo"],
                    $archivoInfo["extension"],
                    $archivoInfo["tamano_bytes"],
                ]);

                $conexion->commit();

                $mensajeEnvio = "Trabajo enviado a Subdirección correctamente.";
                $mensajeEnvioTipo = "success";

            } catch (RuntimeException $e) {

                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                $mensajeEnvio = $e->getMessage();
                $mensajeEnvioTipo = "error";

            }

        }

        flash_set($mensajeEnvio, $mensajeEnvioTipo);
        header("Location: dashboard.php");
        exit;

    } elseif ($accion === "subir_correccion") {

        $idEnvio = (int) ($_POST["id_envio"] ?? 0);

        $envio = $conexion->prepare("SELECT * FROM envios_trabajo WHERE id_envio = ? AND id_profesor = ?");
        $envio->execute([$idEnvio, $profesor["id_profesor"]]);
        $envio = $envio->fetch();

        if (!$envio) {

            $mensajeEnvio = "No se pudo subir la corrección: el trabajo no existe o no le pertenece.";
            $mensajeEnvioTipo = "error";

        } elseif ($envio["estado"] !== "REQUIERE_CAMBIOS") {

            $mensajeEnvio = "Este trabajo ya no está esperando una corrección.";
            $mensajeEnvioTipo = "error";

        } else {

            try {

                $archivoInfo = envios_guardar_archivo($_FILES["correccion_archivo"] ?? []);
                $nuevaVersion = (int) $envio["version_actual"] + 1;

                $conexion->beginTransaction();

                $conexion->prepare("
                    UPDATE envios_trabajo SET estado = 'CORREGIDO', version_actual = ? WHERE id_envio = ?
                ")->execute([$nuevaVersion, $idEnvio]);

                $stmt = $conexion->prepare("
                    INSERT INTO envios_trabajo_historial
                        (id_envio, version, nombre_archivo, ruta_archivo, extension, tamano_bytes, estado)
                    VALUES (?, ?, ?, ?, ?, ?, 'CORREGIDO')
                ");
                $stmt->execute([
                    $idEnvio,
                    $nuevaVersion,
                    $archivoInfo["nombre_archivo"],
                    $archivoInfo["ruta_archivo"],
                    $archivoInfo["extension"],
                    $archivoInfo["tamano_bytes"],
                ]);

                $conexion->commit();

                $mensajeEnvio = "Versión corregida enviada a Subdirección.";
                $mensajeEnvioTipo = "success";

            } catch (RuntimeException $e) {

                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                $mensajeEnvio = $e->getMessage();
                $mensajeEnvioTipo = "error";

            }

        }

        flash_set($mensajeEnvio, $mensajeEnvioTipo);
        header("Location: dashboard.php");
        exit;

    }

    header("Location: dashboard.php");
    exit;

}

if ($mensajeEnvio === null) {
    [$mensajeEnvio, $mensajeEnvioTipo] = flash_get();
}


// ==================================================
// 5.2 LISTAS PARA LOS FORMULARIOS (cursos, tipos, periodos)
// ==================================================

$cursos = $conexion->query("SELECT id_curso, nombre FROM cursos ORDER BY nombre")->fetchAll();
$tiposTrabajo = $conexion->query("SELECT id_tipo_trabajo, nombre FROM tipos_trabajo ORDER BY nombre")->fetchAll();
$periodos = $conexion->query("SELECT id_periodo, nombre FROM periodos_academicos ORDER BY id_periodo")->fetchAll();


// ==================================================
// 5.3 TRABAJO A EDITAR (si corresponde)
//
// También filtrado por id_profesor: un profesor no puede ni
// siquiera cargar en el formulario un trabajo que no es suyo.
// ==================================================

$trabajoEditar = null;

if (isset($_GET["editar"]) && (int)$_GET["editar"] > 0) {

    $stmt = $conexion->prepare("SELECT * FROM trabajos WHERE id_trabajo = ? AND id_profesor = ?");
    $stmt->execute([(int)$_GET["editar"], $profesor["id_profesor"]]);
    $trabajoEditar = $stmt->fetch();

}


// ==================================================
// 6. OBTENER LOS TRABAJOS DEL PROFESOR
// ==================================================

$sql = "
    SELECT
        t.id_trabajo,
        t.titulo,
        t.descripcion,
        t.fecha_limite,
        t.estado,

        c.nombre AS curso,

        tt.nombre AS tipo_trabajo,

        p.nombre AS periodo

    FROM trabajos t

    INNER JOIN cursos c
        ON t.id_curso = c.id_curso

    INNER JOIN tipos_trabajo tt
        ON t.id_tipo_trabajo = tt.id_tipo_trabajo

    INNER JOIN periodos_academicos p
        ON t.id_periodo = p.id_periodo

    WHERE t.id_profesor = ?

    ORDER BY t.fecha_limite ASC
";


$stmt = $conexion->prepare($sql);

$stmt->execute([
    $profesor["id_profesor"]
]);

$trabajos = $stmt->fetchAll();


// ==================================================
// 7. MIS ENVÍOS A SUBDIRECCIÓN (envios_trabajo)
// ==================================================

$misEnvios = $conexion->prepare("
    SELECT
        e.id_envio, e.titulo, e.descripcion, e.estado, e.version_actual,
        h.nombre_archivo, h.extension, h.creado_en AS fecha_version,
        h.observacion AS observacion_actual
    FROM envios_trabajo e
    INNER JOIN envios_trabajo_historial h
        ON h.id_envio = e.id_envio AND h.version = e.version_actual
    WHERE e.id_profesor = ?
    ORDER BY e.actualizado_en DESC
");
$misEnvios->execute([$profesor["id_profesor"]]);
$misEnvios = $misEnvios->fetchAll();

// ==================================================
// 7.1 GRADOS (NIVEL + GRADO) ASIGNADOS AL PROFESOR
// ==================================================

$gradosAsignados = profesor_grados_asignados($conexion, $profesor["id_profesor"]);

$notificacionesProfesor = notificaciones_listar($conexion, $_SESSION["id_usuario"], 5);
$notificacionesNoLeidas = notificaciones_no_leidas($conexion, $_SESSION["id_usuario"]);

if (isset($_GET["ver_notificaciones"])) {
    notificaciones_marcar_leidas($conexion, $_SESSION["id_usuario"]);
    header("Location: dashboard.php");
    exit;
}

?>



<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Panel del Profesor - I.E.P. 88044 Abraham Valdelomar
    </title>

    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">

</head>


<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">



<!-- ==================================================
     ENCABEZADO
================================================== -->

<header>

    <h1>
        🏠 Panel del Profesor
    </h1>

    <p>

        Bienvenido,

        <?= htmlspecialchars($profesor["nombres"]) ?>

        <?= htmlspecialchars($profesor["apellidos"]) ?>

    </p>

    <a href="../backend/auth/logout.php">
        Cerrar sesión
    </a>

</header>



<!-- ==================================================
     CONTENIDO PRINCIPAL
================================================== -->

<main>


    <!-- ==================================================
         MENÚ PRINCIPAL DEL PROFESOR
    ================================================== -->

    <section>

        <h2>
            Grados asignados
        </h2>

        <?php if (count($gradosAsignados) === 0): ?>

            <div class="empty-state">
                <span class="empty-state-icon" aria-hidden="true">🎒</span>
                <h2>Todavía no tienes grados asignados</h2>
                <p>
                    Pide al administrador que te asigne un aula desde «Gestionar profesores»
                    para poder organizar tu PCA, Unidades, Sesiones y Documentos Institucionales.
                </p>
            </div>

        <?php else: ?>

            <p class="placeholder-text" style="margin-bottom:16px;">
                Selecciona un grado para organizar su PCA, Unidades, Sesiones y
                Documentos Institucionales.
            </p>

            <div class="cards-grados">
                <?php foreach ($gradosAsignados as $ng): ?>
                    <div class="card">
                        <h3><?= htmlspecialchars(strtoupper($ng["nombre"])) ?> <?= htmlspecialchars(strtoupper(ucfirst(strtolower($ng["nivel"])))) ?></h3>

                        <?php if (count($ng["cursos"]) === 0): ?>
                            <p class="placeholder-text">Sin curso asignado todavía.</p>
                        <?php else: ?>
                            <p>
                                <?= htmlspecialchars(implode(", ", array_map(fn($c) => $c["nombre"], $ng["cursos"]))) ?>
                            </p>
                        <?php endif; ?>

                        <p class="placeholder-text">
                            <?= count($ng["secciones"]) ?> <?= count($ng["secciones"]) === 1 ? "sección" : "secciones" ?>
                        </p>

                        <a href="grado.php?id_nivel_grado=<?= (int) $ng["id_nivel_grado"] ?>">
                            Ingresar
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </section>


    <!-- INFORMACIÓN DEL PROFESOR -->

    <section class="profile-card">

        <h2>
            Mi información
        </h2>

        <p>

            <strong>
                Nombre:
            </strong>

            <?= htmlspecialchars($profesor["nombres"]) ?>

            <?= htmlspecialchars($profesor["apellidos"]) ?>

        </p>


        <p>

            <strong>
                Correo:
            </strong>

            <?= htmlspecialchars($profesor["correo"]) ?>

        </p>


        <p>

            <strong>
                Especialidad:
            </strong>

            <?= htmlspecialchars($profesor["especialidad"]) ?>

        </p>

    </section>


    <!-- ==================================================
         NOTIFICACIONES
    ================================================== -->

    <section>

        <h2>
            🔔 Notificaciones
            <?php if ($notificacionesNoLeidas > 0): ?>
                <span class="status-badge status-requiere_cambios"><?= $notificacionesNoLeidas ?> nueva(s)</span>
            <?php endif; ?>
        </h2>

        <?php if (count($notificacionesProfesor) === 0): ?>

            <p class="placeholder-text">No tienes notificaciones todavía.</p>

        <?php else: ?>

            <?php foreach ($notificacionesProfesor as $n): ?>
                <div class="notif-item <?= $n["leido"] ? "leida" : "" ?>">
                    <?= htmlspecialchars($n["mensaje"]) ?>
                    <span class="placeholder-text"> · <?= htmlspecialchars($n["creado_en"]) ?></span>
                </div>
            <?php endforeach; ?>

            <?php if ($notificacionesNoLeidas > 0): ?>
                <p><a href="dashboard.php?ver_notificaciones=1">Marcar todas como leídas</a></p>
            <?php endif; ?>

        <?php endif; ?>

    </section>


    <!-- ==================================================
         ENVÍO DE TRABAJOS A SUBDIRECCIÓN
    ================================================== -->

    <section>

        <h2>
            📤 Envío de trabajos a Subdirección
        </h2>

        <?php if ($mensajeEnvio): ?>
            <div class="panel-alert panel-alert-<?= $mensajeEnvioTipo ?>"><?= htmlspecialchars($mensajeEnvio) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?= count($misEnvios) ?></span>
                <span class="stat-label">Trabajos enviados</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= count(array_filter($misEnvios, fn($e) => in_array($e["estado"], ["ENVIADO", "EN_REVISION", "CORREGIDO"], true))) ?></span>
                <span class="stat-label">Pendientes de revisión</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= count(array_filter($misEnvios, fn($e) => $e["estado"] === "REQUIERE_CAMBIOS")) ?></span>
                <span class="stat-label">Requieren cambios</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= count(array_filter($misEnvios, fn($e) => $e["estado"] === "APROBADO")) ?></span>
                <span class="stat-label">Aprobados</span>
            </div>
        </div>

        <form class="panel-form" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
            <h3>Subir nuevo trabajo</h3>
            <input type="hidden" name="accion" value="enviar_trabajo">
            <div class="field">
                <label for="envio_titulo">Título</label>
                <input type="text" name="envio_titulo" id="envio_titulo" placeholder="Ej. Planificación Anual" required>
            </div>
            <div class="field">
                <label for="envio_descripcion">Descripción (opcional)</label>
                <textarea name="envio_descripcion" id="envio_descripcion" placeholder="Detalle del trabajo"></textarea>
            </div>
            <div class="field">
                <label for="envio_archivo">Archivo</label>
                <input type="file" name="envio_archivo" id="envio_archivo" required>
                <p class="placeholder-text">Formatos permitidos: PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, TXT, ZIP, RAR, JPG, PNG. Tamaño máximo 20&nbsp;MB.</p>
            </div>
            <button type="submit" class="btn-submit">Enviar a Subdirección</button>
        </form>

        <?php if (count($misEnvios) === 0): ?>

            <p class="placeholder-text">Todavía no has enviado trabajos a Subdirección.</p>

        <?php else: ?>

            <div class="trabajos-container">
                <?php foreach ($misEnvios as $e): ?>
                    <div class="trabajo-card">
                        <h3><?= htmlspecialchars($e["titulo"]) ?></h3>

                        <p><strong>Archivo:</strong> <?= htmlspecialchars($e["nombre_archivo"]) ?> (v<?= (int) $e["version_actual"] ?>)</p>
                        <p><strong>Enviado:</strong> <?= htmlspecialchars($e["fecha_version"]) ?></p>

                        <p>
                            <strong>Estado:</strong>
                            <span class="status-badge status-<?= strtolower($e["estado"]) ?>"><?= htmlspecialchars($e["estado"]) ?></span>
                        </p>

                        <?php if ($e["estado"] === "REQUIERE_CAMBIOS"): ?>

                            <div class="panel-alert panel-alert-error">
                                ⚠️ Se requieren cambios<br>
                                <strong>Observación de Subdirección:</strong>
                                "<?= htmlspecialchars($e["observacion_actual"] ?? "") ?>"
                            </div>

                            <form method="POST" enctype="multipart/form-data" class="panel-form" style="margin-top:10px;">
                            <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="subir_correccion">
                                <input type="hidden" name="id_envio" value="<?= (int) $e["id_envio"] ?>">
                                <div class="field">
                                    <label>Subir versión corregida</label>
                                    <input type="file" name="correccion_archivo" required>
                                </div>
                                <button type="submit" class="btn-submit">Subir corrección</button>
                            </form>

                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </section>


    <!-- ==================================================
         MIS ALUMNOS
    ================================================== -->

    <section>

        <h2>
            Mis alumnos (<?= count($misAlumnos) ?>)
        </h2>

        <?php if (count($misAlumnos) === 0): ?>

            <p class="placeholder-text">
                Todavía no tienes aulas asignadas, o las aulas asignadas no tienen alumnos matriculados.
                Pide al administrador que te asigne un aula desde «Gestionar profesores».
            </p>

        <?php else: ?>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Nombre</th><th>DNI</th><th>Aula</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($misAlumnos as $al): ?>
                            <tr>
                                <td><?= htmlspecialchars($al["nombres"] . " " . $al["apellidos"]) ?></td>
                                <td><?= htmlspecialchars($al["dni"]) ?></td>
                                <td><?= htmlspecialchars(ucfirst(strtolower($al["nivel"]))) ?> · <?= htmlspecialchars($al["grado_seccion"]) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($al["estado"]) ?>">
                                        <?= htmlspecialchars($al["estado"]) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>



    <!-- ==================================================
         TRABAJOS
    ================================================== -->

    <section>

        <h2>
            Mis trabajos
        </h2>

        <?php if ($mensaje): ?>
            <div class="panel-alert panel-alert-<?= $mensajeTipo ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($trabajoEditar): ?>

            <form class="panel-form" method="POST">
            <?= csrf_field() ?>
                <h3>Editar trabajo</h3>
                <input type="hidden" name="accion" value="guardar_edicion">
                <input type="hidden" name="id_trabajo" value="<?= (int)$trabajoEditar["id_trabajo"] ?>">
                <div class="field">
                    <label for="t_titulo">Título</label>
                    <input type="text" name="titulo" id="t_titulo" value="<?= htmlspecialchars($trabajoEditar["titulo"]) ?>" required>
                </div>
                <div class="field">
                    <label for="t_descripcion">Descripción</label>
                    <textarea name="descripcion" id="t_descripcion"><?= htmlspecialchars($trabajoEditar["descripcion"] ?? "") ?></textarea>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label for="t_curso">Curso</label>
                        <select name="id_curso" id="t_curso" required>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= (int)$c["id_curso"] ?>" <?= (int)$c["id_curso"] === (int)$trabajoEditar["id_curso"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($c["nombre"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="t_tipo">Tipo de trabajo</label>
                        <select name="id_tipo_trabajo" id="t_tipo" required>
                            <?php foreach ($tiposTrabajo as $t): ?>
                                <option value="<?= (int)$t["id_tipo_trabajo"] ?>" <?= (int)$t["id_tipo_trabajo"] === (int)$trabajoEditar["id_tipo_trabajo"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($t["nombre"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label for="t_periodo">Periodo</label>
                        <select name="id_periodo" id="t_periodo" required>
                            <?php foreach ($periodos as $p): ?>
                                <option value="<?= (int)$p["id_periodo"] ?>" <?= (int)$p["id_periodo"] === (int)$trabajoEditar["id_periodo"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($p["nombre"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="t_fecha">Fecha límite</label>
                        <input type="date" name="fecha_limite" id="t_fecha" value="<?= htmlspecialchars($trabajoEditar["fecha_limite"]) ?>" required>
                    </div>
                </div>
                <div class="field">
                    <label for="t_estado">Estado</label>
                    <select name="estado" id="t_estado">
                        <option value="PENDIENTE" <?= $trabajoEditar["estado"] === "PENDIENTE" ? "selected" : "" ?>>PENDIENTE</option>
                        <option value="ENTREGADO" <?= $trabajoEditar["estado"] === "ENTREGADO" ? "selected" : "" ?>>ENTREGADO</option>
                        <option value="CERRADO" <?= $trabajoEditar["estado"] === "CERRADO" ? "selected" : "" ?>>CERRADO</option>
                    </select>
                </div>
                <div class="row-actions">
                    <button type="submit" class="btn-submit">Guardar cambios</button>
                    <a href="dashboard.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>

        <?php else: ?>

            <form class="panel-form" method="POST">
            <?= csrf_field() ?>
                <h3>Crear trabajo</h3>
                <input type="hidden" name="accion" value="crear">
                <div class="field">
                    <label for="n_titulo">Título</label>
                    <input type="text" name="titulo" id="n_titulo" placeholder="Ej. Tarea de Matemática" required>
                </div>
                <div class="field">
                    <label for="n_descripcion">Descripción</label>
                    <textarea name="descripcion" id="n_descripcion" placeholder="Detalle del trabajo"></textarea>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label for="n_curso">Curso</label>
                        <select name="id_curso" id="n_curso" required>
                            <option value="">Seleccione…</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= (int)$c["id_curso"] ?>"><?= htmlspecialchars($c["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="n_tipo">Tipo de trabajo</label>
                        <select name="id_tipo_trabajo" id="n_tipo" required>
                            <option value="">Seleccione…</option>
                            <?php foreach ($tiposTrabajo as $t): ?>
                                <option value="<?= (int)$t["id_tipo_trabajo"] ?>"><?= htmlspecialchars($t["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label for="n_periodo">Periodo</label>
                        <select name="id_periodo" id="n_periodo" required>
                            <option value="">Seleccione…</option>
                            <?php foreach ($periodos as $p): ?>
                                <option value="<?= (int)$p["id_periodo"] ?>"><?= htmlspecialchars($p["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="n_fecha">Fecha límite</label>
                        <input type="date" name="fecha_limite" id="n_fecha" required>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Crear trabajo</button>
            </form>

        <?php endif; ?>


        <?php if (count($trabajos) === 0): ?>

            <div class="empty-state">
                <span class="empty-state-icon" aria-hidden="true">📋</span>
                <h2>No tienes trabajos registrados</h2>
                <p>Usa el formulario de arriba para crear tu primer trabajo.</p>
            </div>


        <?php else: ?>


            <div class="trabajos-container">


                <?php foreach ($trabajos as $trabajo): ?>


                    <div class="trabajo-card">


                        <h3>

                            <?= htmlspecialchars(
                                $trabajo["titulo"]
                            ) ?>

                        </h3>


                        <p>

                            <strong>
                                Descripción:
                            </strong>

                            <?= htmlspecialchars(
                                $trabajo["descripcion"]
                            ) ?>

                        </p>


                        <p>

                            <strong>
                                Curso:
                            </strong>

                            <?= htmlspecialchars(
                                $trabajo["curso"]
                            ) ?>

                        </p>


                        <p>

                            <strong>
                                Tipo:
                            </strong>

                            <?= htmlspecialchars(
                                $trabajo["tipo_trabajo"]
                            ) ?>

                        </p>


                        <p>

                            <strong>
                                Fecha límite:
                            </strong>

                            <?= htmlspecialchars(
                                $trabajo["fecha_limite"]
                            ) ?>

                        </p>


                        <p>

                            <strong>
                                Periodo:
                            </strong>

                            <?= htmlspecialchars(
                                $trabajo["periodo"]
                            ) ?>

                        </p>


                        <strong>

                            Estado:

                            <?= htmlspecialchars(
                                $trabajo["estado"]
                            ) ?>

                        </strong>

                        <div class="row-actions">
                            <a href="dashboard.php?editar=<?= (int)$trabajo["id_trabajo"] ?>" class="btn-mini">Editar</a>
                            <form method="POST" onsubmit="return confirm('¿Eliminar este trabajo?');">
                            <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_trabajo" value="<?= (int)$trabajo["id_trabajo"] ?>">
                                <button type="submit" class="btn-mini btn-mini-reject">Eliminar</button>
                            </form>
                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>


</main>


</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js"></script>

</body>

</html>
