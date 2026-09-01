<?php

session_start();

// ==================================================
// 1. SESIÓN Y ROL
// ==================================================

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

if (!in_array($_SESSION["rol"], ["PROFESOR", "PROFESOR_PRIMARIA", "PROFESOR_SECUNDARIO"], true)) {
    die("Acceso no autorizado.");
}

require_once "../backend/config/database.php";
require_once "../backend/config/seguridad_csrf.php";
require_once "../backend/config/flash.php";
require_once "../backend/config/materiales_archivos.php";
require_once "../backend/config/materiales_cursos.php";
require_once "../backend/config/profesor_grados.php";

// ==================================================
// 2. DATOS DEL PROFESOR
// ==================================================

$stmt = $conexion->prepare("
    SELECT p.id_profesor, u.nombres, u.apellidos
    FROM profesores p
    INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
    WHERE p.id_usuario = ?
");
$stmt->execute([$_SESSION["id_usuario"]]);
$profesor = $stmt->fetch();

if (!$profesor) {
    die("No se encontró el perfil del profesor.");
}

$idProfesor = (int) $profesor["id_profesor"];

// ==================================================
// 2.1 GRADO (NIVEL+GRADO) ACTUAL
//
// SEGURIDAD: nunca se confía en el id_nivel_grado que llega por
// GET o POST; siempre se verifica contra las aulas realmente
// asignadas al profesor antes de leer o escribir nada.
// ==================================================

$idNivelGrado = (int) ($_GET["id_nivel_grado"] ?? $_POST["id_nivel_grado"] ?? 0);
$nivelGrado = profesor_verificar_grado($conexion, $idProfesor, $idNivelGrado);

if (!$nivelGrado) {
    http_response_code(403);
    die("No tiene un aula asignada de ese nivel y grado.");
}

$etiqueta = nivel_grado_label($nivelGrado);
$idNivelGradoSidebar = $idNivelGrado; // usado por sidebar.php

// Cursos/áreas de ESTE nivel únicamente (Primaria nunca ve cursos
// de Secundaria y viceversa), ya en el orden alfabético correcto.
$cursosDelNivel = materiales_cursos_por_nivel($nivelGrado["nivel"]);

// ==================================================
// 3. ACCIONES: SUBIR/REEMPLAZAR Y ELIMINAR POR CURSO
//
// Todas las consultas de escritura llevan
// "AND id_profesor = ?" con el id_profesor de la sesión
// actual (nunca uno enviado por el formulario).
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"])) {

    csrf_verificar();

    $accion = $_POST["accion"];
    $unidad = (int) ($_POST["unidad"] ?? 0);
    $semana = (int) ($_POST["semana"] ?? 0);
    $curso = (string) ($_POST["curso"] ?? "");

    $redireccion = "unidades.php?id_nivel_grado=" . $idNivelGrado . "&unidad=" . $unidad . "&semana=" . $semana;

    $unidadValida = $unidad >= 1 && $unidad <= MATERIALES_TOTAL_UNIDADES;
    $semanaValida = $semana >= 1 && $semana <= MATERIALES_TOTAL_SEMANAS;
    $cursoValido = materiales_curso_nombre($curso, $nivelGrado["nivel"]) !== null;

    if (!$unidadValida || !$semanaValida) {

        flash_set("Datos de curso/unidad/semana inválidos.", "error");
        header("Location: unidades.php");
        exit;

    }

    // SEGURIDAD: una clave de curso válida en el OTRO nivel (p.ej.
    // "ept" en un grado de Primaria) nunca se acepta ni se confunde
    // con datos inválidos: se corta con 403, igual que un
    // id_nivel_grado manipulado.
    if (!$cursoValido) {

        http_response_code(403);
        die("El curso indicado no corresponde al nivel de este grado.");

    }

    if ($accion === "guardar_curso") {

        try {

            $archivoInfo = materiales_guardar_archivo(
                $_FILES["archivo"] ?? [],
                MATERIALES_DIRECTORIO_UNIDADES,
                "backend/uploads/materiales/unidades/"
            );

            $existente = $conexion->prepare("
                SELECT id_material, ruta_archivo FROM profesor_unidad_archivos
                WHERE id_profesor = ? AND id_nivel_grado = ? AND unidad = ? AND semana = ? AND curso = ?
            ");
            $existente->execute([$idProfesor, $idNivelGrado, $unidad, $semana, $curso]);
            $existente = $existente->fetch();

            if ($existente) {

                materiales_eliminar_archivo_fisico($existente["ruta_archivo"]);

                $conexion->prepare("
                    UPDATE profesor_unidad_archivos
                    SET nombre_archivo = ?, ruta_archivo = ?, extension = ?, tamano_bytes = ?
                    WHERE id_material = ? AND id_profesor = ? AND id_nivel_grado = ?
                ")->execute([
                    $archivoInfo["nombre_archivo"],
                    $archivoInfo["ruta_archivo"],
                    $archivoInfo["extension"],
                    $archivoInfo["tamano_bytes"],
                    $existente["id_material"],
                    $idProfesor,
                    $idNivelGrado,
                ]);

                flash_set("Archivo de " . materiales_curso_nombre($curso, $nivelGrado["nivel"]) . " reemplazado correctamente.", "success");

            } else {

                $conexion->prepare("
                    INSERT INTO profesor_unidad_archivos
                        (id_profesor, id_nivel_grado, unidad, semana, curso, nombre_archivo, ruta_archivo, extension, tamano_bytes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                    $idProfesor,
                    $idNivelGrado,
                    $unidad,
                    $semana,
                    $curso,
                    $archivoInfo["nombre_archivo"],
                    $archivoInfo["ruta_archivo"],
                    $archivoInfo["extension"],
                    $archivoInfo["tamano_bytes"],
                ]);

                flash_set("Archivo de " . materiales_curso_nombre($curso, $nivelGrado["nivel"]) . " subido correctamente.", "success");

            }

        } catch (RuntimeException $e) {

            flash_set($e->getMessage(), "error");

        }

    } elseif ($accion === "eliminar_curso") {

        $existente = $conexion->prepare("
            SELECT id_material, ruta_archivo FROM profesor_unidad_archivos
            WHERE id_profesor = ? AND id_nivel_grado = ? AND unidad = ? AND semana = ? AND curso = ?
        ");
        $existente->execute([$idProfesor, $idNivelGrado, $unidad, $semana, $curso]);
        $existente = $existente->fetch();

        if (!$existente) {

            flash_set("No se pudo eliminar: el archivo no existe o no le pertenece.", "error");

        } else {

            $conexion->prepare("
                DELETE FROM profesor_unidad_archivos WHERE id_material = ? AND id_profesor = ? AND id_nivel_grado = ?
            ")->execute([$existente["id_material"], $idProfesor, $idNivelGrado]);

            materiales_eliminar_archivo_fisico($existente["ruta_archivo"]);

            flash_set("Archivo de " . materiales_curso_nombre($curso, $nivelGrado["nivel"]) . " eliminado correctamente.", "success");

        }

    }

    header("Location: " . $redireccion);
    exit;

}

[$mensaje, $mensajeTipo] = flash_get();

// ==================================================
// 4. NAVEGACIÓN: ¿en qué nivel estamos?
// ==================================================

$unidadSel = isset($_GET["unidad"]) ? (int) $_GET["unidad"] : 0;
$semanaSel = isset($_GET["semana"]) ? (int) $_GET["semana"] : 0;

if ($unidadSel < 1 || $unidadSel > MATERIALES_TOTAL_UNIDADES) {
    $unidadSel = 0;
}

if ($semanaSel < 1 || $semanaSel > MATERIALES_TOTAL_SEMANAS) {
    $semanaSel = 0;
}

// Si hay semana seleccionada pero no unidad válida, se ignora.
if ($unidadSel === 0) {
    $semanaSel = 0;
}

// Archivos por curso, solo se consultan cuando corresponde (unidad + semana).
$archivosPorCurso = [];

if ($unidadSel && $semanaSel) {

    $stmt = $conexion->prepare("
        SELECT * FROM profesor_unidad_archivos
        WHERE id_profesor = ? AND id_nivel_grado = ? AND unidad = ? AND semana = ?
    ");
    $stmt->execute([$idProfesor, $idNivelGrado, $unidadSel, $semanaSel]);

    foreach ($stmt->fetchAll() as $fila) {
        $archivosPorCurso[$fila["curso"]] = $fila;
    }

}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades · <?= htmlspecialchars($etiqueta) ?> · Panel del Profesor - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">

<header>
    <div>
        <div class="page-eyebrow">
            <span class="badge-nivel-grado">🎒 <?= htmlspecialchars($etiqueta) ?></span>
        </div>
        <h1>🗂️ Unidades</h1>
        <p>Panel del Profesor · I.E.P. 88044 Abraham Valdelomar</p>
    </div>
    <div class="header-actions">
        <a href="grado.php?id_nivel_grado=<?= $idNivelGrado ?>" class="btn-secondary">← <?= htmlspecialchars($etiqueta) ?></a>
        <a href="../backend/auth/logout.php">Cerrar sesión</a>
    </div>
</header>

<main>

    <?php if ($mensaje): ?>
        <div class="panel-alert panel-alert-<?= $mensajeTipo ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <!-- ========== MIGAS DE PAN ========== -->
    <nav class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a>
        <span>›</span>
        <a href="grado.php?id_nivel_grado=<?= $idNivelGrado ?>"><?= htmlspecialchars($etiqueta) ?></a>
        <span>›</span>
        <a href="unidades.php?id_nivel_grado=<?= $idNivelGrado ?>">Unidades</a>
        <?php if ($unidadSel): ?>
            <span>›</span>
            <?php if ($semanaSel): ?>
                <a href="unidades.php?id_nivel_grado=<?= $idNivelGrado ?>&unidad=<?= $unidadSel ?>">Unidad <?= $unidadSel ?></a>
                <span>›</span>
                <span class="crumb-current"><?= htmlspecialchars(materiales_semana_label($semanaSel)) ?></span>
            <?php else: ?>
                <span class="crumb-current">Unidad <?= $unidadSel ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <?php if (!$unidadSel): ?>

        <!-- ========== NIVEL 1: LISTA DE UNIDADES ========== -->

        <div class="cards">
            <?php for ($u = 1; $u <= MATERIALES_TOTAL_UNIDADES; $u++): ?>
                <div class="card">
                    <h3>Unidad <?= $u ?></h3>
                    <p><?= MATERIALES_TOTAL_SEMANAS ?> semanas</p>
                    <a href="unidades.php?id_nivel_grado=<?= $idNivelGrado ?>&unidad=<?= $u ?>">Abrir unidad</a>
                </div>
            <?php endfor; ?>
        </div>

    <?php elseif (!$semanaSel): ?>

        <!-- ========== NIVEL 2: LISTA DE SEMANAS DE LA UNIDAD ========== -->

        <div class="cards">
            <?php for ($s = 1; $s <= MATERIALES_TOTAL_SEMANAS; $s++): ?>
                <div class="card">
                    <h3><?= htmlspecialchars(materiales_semana_label($s)) ?></h3>
                    <p><?= htmlspecialchars(materiales_sem_label($s)) ?> · <?= count($cursosDelNivel) ?> cursos</p>
                    <a href="unidades.php?id_nivel_grado=<?= $idNivelGrado ?>&unidad=<?= $unidadSel ?>&semana=<?= $s ?>">Abrir semana</a>
                </div>
            <?php endfor; ?>
        </div>

    <?php else: ?>

        <!-- ========== NIVEL 3: SEM-0X — CURSOS ========== -->

        <h2><?= htmlspecialchars(materiales_sem_label($semanaSel)) ?></h2>

        <div class="materiales-grid">

            <?php foreach ($cursosDelNivel as $claveCurso => $nombreCurso): ?>

                <?php $archivo = $archivosPorCurso[$claveCurso] ?? null; ?>

                <div class="material-card">

                    <div class="material-card-head">
                        <h3><?= htmlspecialchars($nombreCurso) ?></h3>
                        <?php if ($archivo): ?>
                            <span class="status-badge status-aprobado">Cargado</span>
                        <?php else: ?>
                            <span class="status-badge status-pendiente">Sin archivo</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($archivo): ?>

                        <p class="material-filename" title="<?= htmlspecialchars($archivo["nombre_archivo"]) ?>">
                            📄 <?= htmlspecialchars($archivo["nombre_archivo"]) ?>
                        </p>

                        <div class="row-actions">
                            <a class="btn-mini" target="_blank" rel="noopener"
                               href="../backend/materiales/archivo.php?modulo=unidad&id=<?= (int) $archivo["id_material"] ?>&accion=ver">
                                Ver
                            </a>
                            <a class="btn-mini" href="../backend/materiales/archivo.php?modulo=unidad&id=<?= (int) $archivo["id_material"] ?>&accion=descargar">
                                Descargar
                            </a>
                            <form method="POST" onsubmit="return confirm('¿Eliminar el archivo de <?= htmlspecialchars($nombreCurso) ?>?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="eliminar_curso">
                                <input type="hidden" name="unidad" value="<?= $unidadSel ?>">
                                <input type="hidden" name="semana" value="<?= $semanaSel ?>">
                                <input type="hidden" name="curso" value="<?= htmlspecialchars($claveCurso) ?>">
                                <input type="hidden" name="id_nivel_grado" value="<?= $idNivelGrado ?>">
                                <button type="submit" class="btn-mini btn-mini-reject">Eliminar</button>
                            </form>
                        </div>

                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="material-upload-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="guardar_curso">
                        <input type="hidden" name="unidad" value="<?= $unidadSel ?>">
                        <input type="hidden" name="semana" value="<?= $semanaSel ?>">
                        <input type="hidden" name="curso" value="<?= htmlspecialchars($claveCurso) ?>">
                        <input type="hidden" name="id_nivel_grado" value="<?= $idNivelGrado ?>">
                        <input type="file" name="archivo" required>
                        <button type="submit" class="btn-mini">
                            <?= $archivo ? "Reemplazar" : "Subir" ?>
                        </button>
                    </form>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js"></script>

</body>
</html>
