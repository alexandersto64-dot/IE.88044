<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

if ($_SESSION["rol"] !== "ADMIN") {
    die("Acceso no autorizado.");
}

require_once "../backend/config/database.php";
require_once "../backend/config/seguridad_csrf.php";
require_once "../backend/config/flash.php";
[$mensaje, $mensajeTipo] = flash_get();

// ==========================================
// REGISTRAR ESPECIALIDAD DE UN PROFESOR
// (solo para usuarios que ya tienen rol PROFESOR
// y todavía no tienen perfil en la tabla `profesores`)
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_verificar();

    $accion = $_POST["accion"] ?? "crear";

    if ($accion === "guardar_edicion") {

        $id_profesor = (int)($_POST["id_profesor"] ?? 0);
        $especialidad = trim($_POST["especialidad"] ?? "");

        if ($id_profesor === 0 || $especialidad === "") {

            $mensaje = "Debe indicar la especialidad.";
            $mensajeTipo = "error";

        } else {

            $stmt = $conexion->prepare("UPDATE profesores SET especialidad = ? WHERE id_profesor = ?");
            $stmt->execute([$especialidad, $id_profesor]);

            $mensaje = "Profesor actualizado correctamente.";
            $mensajeTipo = "success";

        }

        if ($mensaje !== null) {
            flash_set($mensaje, $mensajeTipo);
        }
        header("Location: profesores.php");
        exit;

    } elseif ($accion === "eliminar") {

        $id_profesor = (int)($_POST["id_profesor"] ?? 0);

        try {

            $stmt = $conexion->prepare("DELETE FROM profesores WHERE id_profesor = ?");
            $stmt->execute([$id_profesor]);

            $mensaje = "Profesor eliminado correctamente.";
            $mensajeTipo = "success";

        } catch (PDOException $e) {

            $mensaje = "No se pudo eliminar: el profesor tiene trabajos asociados.";
            $mensajeTipo = "error";

        }

        if ($mensaje !== null) {
            flash_set($mensaje, $mensajeTipo);
        }
        header("Location: profesores.php");
        exit;

    } elseif ($accion === "asignar_aula") {

        $id_profesor = (int)($_POST["id_profesor"] ?? 0);
        $id_grado_seccion = (int)($_POST["id_grado_seccion"] ?? 0);

        if ($id_profesor === 0 || $id_grado_seccion === 0) {

            $mensaje = "Debe seleccionar un aula.";
            $mensajeTipo = "error";

        } else {

            try {

                $stmt = $conexion->prepare("INSERT INTO asignaciones_docentes (id_profesor, id_grado_seccion) VALUES (?, ?)");
                $stmt->execute([$id_profesor, $id_grado_seccion]);

                $mensaje = "Aula asignada correctamente.";
                $mensajeTipo = "success";

            } catch (PDOException $e) {

                $mensaje = "Esa aula ya estaba asignada a este profesor.";
                $mensajeTipo = "error";

            }

        }

        if ($mensaje !== null) {
            flash_set($mensaje, $mensajeTipo);
        }
        header("Location: profesores.php");
        exit;

    } elseif ($accion === "quitar_aula") {

        $id_asignacion = (int)($_POST["id_asignacion"] ?? 0);

        $stmt = $conexion->prepare("DELETE FROM asignaciones_docentes WHERE id_asignacion = ?");
        $stmt->execute([$id_asignacion]);

        $mensaje = "Aula retirada del profesor.";
        $mensajeTipo = "success";

        flash_set($mensaje, $mensajeTipo);
        header("Location: profesores.php");
        exit;

    } else {

        $id_usuario = $_POST["id_usuario"] ?? "";
        $especialidad = trim($_POST["especialidad"] ?? "");

        if ($id_usuario === "" || $especialidad === "") {

            $mensaje = "Debe seleccionar un usuario e indicar la especialidad.";
            $mensajeTipo = "error";

        } else {

            try {
                $stmt = $conexion->prepare("INSERT INTO profesores (id_usuario, especialidad) VALUES (?, ?)");
                $stmt->execute([(int)$id_usuario, $especialidad]);

                $mensaje = "Profesor registrado correctamente.";
                $mensajeTipo = "success";

            } catch (PDOException $e) {

                $mensaje = "No se pudo registrar (verifique que el usuario no tenga ya un perfil de profesor).";
                $mensajeTipo = "error";

            }

        }

        // Evita el reenvío del formulario al recargar (PRG)
        if ($mensaje !== null) {
            flash_set($mensaje, $mensajeTipo);
        }
        header("Location: profesores.php");
        exit;

    }

}

// ==========================================
// LISTA DE PROFESORES YA REGISTRADOS
// ==========================================

$profesores = $conexion->query("
    SELECT
        p.id_profesor,
        u.nombres,
        u.apellidos,
        u.correo,
        p.especialidad

    FROM profesores p

    INNER JOIN usuarios u
        ON p.id_usuario = u.id_usuario

    ORDER BY u.apellidos, u.nombres
")->fetchAll();

// ==========================================
// USUARIOS CON ROL PROFESOR SIN PERFIL AÚN
// ==========================================

$pendientes = $conexion->query("
    SELECT u.id_usuario, u.nombres, u.apellidos
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id_rol
    WHERE r.nombre = 'PROFESOR'
      AND u.id_usuario NOT IN (SELECT id_usuario FROM profesores)
    ORDER BY u.apellidos, u.nombres
")->fetchAll();

// ==========================================
// AULAS ASIGNADAS POR PROFESOR + CATÁLOGO DE AULAS
// ==========================================

$gradosSecciones = $conexion->query("SELECT id_grado_seccion, nivel, nombre FROM grados_secciones ORDER BY nivel, grado, seccion")->fetchAll();

$asignacionesPorProfesor = [];
$filasAsig = $conexion->query("
    SELECT ad.id_asignacion, ad.id_profesor, gs.nombre AS grado_seccion, gs.nivel
    FROM asignaciones_docentes ad
    INNER JOIN grados_secciones gs ON gs.id_grado_seccion = ad.id_grado_seccion
    ORDER BY gs.nivel, gs.grado, gs.seccion
")->fetchAll();

foreach ($filasAsig as $fila) {
    $asignacionesPorProfesor[$fila["id_profesor"]][] = $fila;
}

// ==========================================
// PROFESOR A EDITAR (si corresponde)
// ==========================================

$profesorEditar = null;

if (isset($_GET["editar"]) && (int)$_GET["editar"] > 0) {

    $stmt = $conexion->prepare("
        SELECT p.id_profesor, p.especialidad, u.nombres, u.apellidos
        FROM profesores p
        INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE p.id_profesor = ?
    ");
    $stmt->execute([(int)$_GET["editar"]]);
    $profesorEditar = $stmt->fetch();

}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesores · Panel de Administración - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">


<header>
    <div>
        <h1>Gestionar profesores</h1>
        <p>Panel de Administración · I.E.P. 88044 Abraham Valdelomar</p>
    </div>
    <div class="header-actions">
        <a href="../backend/auth/logout.php">Cerrar sesión</a>
    </div>
</header>

<main>

    <?php if ($mensaje): ?>
        <div class="panel-alert panel-alert-<?= $mensajeTipo ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($profesorEditar): ?>
        <form class="panel-form" method="POST">
        <?= csrf_field() ?>
            <h3>Editar especialidad — <?= htmlspecialchars($profesorEditar['nombres'] . ' ' . $profesorEditar['apellidos']) ?></h3>
            <input type="hidden" name="accion" value="guardar_edicion">
            <input type="hidden" name="id_profesor" value="<?= (int)$profesorEditar['id_profesor'] ?>">
            <div class="field">
                <label for="especialidad_edit">Especialidad / área</label>
                <input type="text" name="especialidad" id="especialidad_edit" value="<?= htmlspecialchars($profesorEditar['especialidad']) ?>" required>
            </div>
            <div class="row-actions">
                <button type="submit" class="btn-submit">Guardar cambios</button>
                <a href="profesores.php" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    <?php elseif (count($pendientes) > 0): ?>
        <form class="panel-form" method="POST">
        <?= csrf_field() ?>
            <h3>Registrar especialidad de un profesor</h3>
            <input type="hidden" name="accion" value="crear">
            <div class="form-row">
                <div class="field">
                    <label for="id_usuario">Usuario (rol PROFESOR, sin perfil aún)</label>
                    <select name="id_usuario" id="id_usuario" required>
                        <option value="">Seleccione…</option>
                        <?php foreach ($pendientes as $p): ?>
                            <option value="<?= (int)$p['id_usuario'] ?>">
                                <?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="especialidad">Especialidad / área</label>
                    <input type="text" name="especialidad" id="especialidad" placeholder="Ej. Matemática" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Registrar</button>
        </form>
    <?php else: ?>
        <p class="placeholder-text" style="margin-bottom:20px">
            No hay usuarios con rol PROFESOR pendientes de completar su perfil.
            Primero crea el usuario en «Gestionar usuarios» con rol PROFESOR.
        </p>
    <?php endif; ?>

    <h2>Profesores registrados (<?= count($profesores) ?>)</h2>

    <?php if (count($profesores) === 0): ?>
        <p class="placeholder-text">Todavía no hay profesores registrados.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Nombre</th><th>Correo</th><th>Especialidad</th><th>Aulas asignadas</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($profesores as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']) ?></td>
                            <td><?= htmlspecialchars($p['correo']) ?></td>
                            <td><?= htmlspecialchars($p['especialidad']) ?></td>
                            <td>
                                <?php foreach (($asignacionesPorProfesor[$p['id_profesor']] ?? []) as $asig): ?>
                                    <form method="POST" style="display:inline-block;margin:0 4px 4px 0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="accion" value="quitar_aula">
                                        <input type="hidden" name="id_asignacion" value="<?= (int)$asig['id_asignacion'] ?>">
                                        <button type="submit" class="role-badge" style="border:0;cursor:pointer" title="Quitar aula">
                                            <?= htmlspecialchars(ucfirst(strtolower($asig['nivel']))) ?> · <?= htmlspecialchars($asig['grado_seccion']) ?> ✕
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                                <form method="POST" style="margin-top:6px">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="accion" value="asignar_aula">
                                    <input type="hidden" name="id_profesor" value="<?= (int)$p['id_profesor'] ?>">
                                    <select name="id_grado_seccion" style="font-size:.85rem" required>
                                        <option value="">+ Asignar aula…</option>
                                        <?php foreach ($gradosSecciones as $gs): ?>
                                            <option value="<?= (int)$gs['id_grado_seccion'] ?>">
                                                <?= htmlspecialchars(ucfirst(strtolower($gs['nivel']))) ?> · <?= htmlspecialchars($gs['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-mini">Asignar</button>
                                </form>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="profesores.php?editar=<?= (int)$p['id_profesor'] ?>" class="btn-mini">Editar</a>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar el perfil de este profesor?');">
                                    <?= csrf_field() ?>
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_profesor" value="<?= (int)$p['id_profesor'] ?>">
                                        <button type="submit" class="btn-mini btn-mini-reject">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</main>

</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js"></script>

</body>
</html>
