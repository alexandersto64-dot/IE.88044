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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_verificar();

    $accion = $_POST["accion"] ?? "crear";

    if ($accion === "crear") {

        $nombre = trim($_POST["nombre"] ?? "");

        if ($nombre === "") {

            $mensaje = "El nombre del curso es obligatorio.";
            $mensajeTipo = "error";

        } else {

            try {

                $stmt = $conexion->prepare("INSERT INTO cursos (nombre) VALUES (?)");
                $stmt->execute([$nombre]);

                $mensaje = "Curso agregado correctamente.";
                $mensajeTipo = "success";

            } catch (PDOException $e) {

                $mensaje = "No se pudo agregar el curso (¿ya existe uno con ese nombre?).";
                $mensajeTipo = "error";

            }

        }

    } elseif ($accion === "guardar_edicion") {

        $id_curso = (int)($_POST["id_curso"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");

        if ($id_curso === 0 || $nombre === "") {

            $mensaje = "Debe indicar el nombre del curso.";
            $mensajeTipo = "error";

        } else {

            try {

                $stmt = $conexion->prepare("UPDATE cursos SET nombre = ? WHERE id_curso = ?");
                $stmt->execute([$nombre, $id_curso]);

                $mensaje = "Curso actualizado correctamente.";
                $mensajeTipo = "success";

            } catch (PDOException $e) {

                $mensaje = "No se pudo actualizar el curso (¿ya existe uno con ese nombre?).";
                $mensajeTipo = "error";

            }

        }

        if ($mensaje !== null) {
            flash_set($mensaje, $mensajeTipo);
        }
        header("Location: cursos.php");
        exit;

    } elseif ($accion === "eliminar") {

        $id_curso = (int)($_POST["id_curso"] ?? 0);

        try {

            $stmt = $conexion->prepare("DELETE FROM cursos WHERE id_curso = ?");
            $stmt->execute([$id_curso]);

            $mensaje = "Curso eliminado correctamente.";
            $mensajeTipo = "success";

        } catch (PDOException $e) {

            $mensaje = "No se pudo eliminar: el curso tiene trabajos asociados.";
            $mensajeTipo = "error";

        }

    }

    if ($accion !== "guardar_edicion") {
        if ($mensaje !== null) {
            flash_set($mensaje, $mensajeTipo);
        }
        header("Location: cursos.php");
        exit;
    }

}

$cursoEditar = null;

if (isset($_GET["editar"]) && (int)$_GET["editar"] > 0) {

    $stmt = $conexion->prepare("SELECT id_curso, nombre FROM cursos WHERE id_curso = ?");
    $stmt->execute([(int)$_GET["editar"]]);
    $cursoEditar = $stmt->fetch();

}

$cursos = $conexion->query("SELECT id_curso, nombre FROM cursos ORDER BY nombre")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos · Panel de Administración - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">


<header>
    <div>
        <h1>Gestionar cursos</h1>
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

    <?php if ($cursoEditar): ?>
        <form class="panel-form" method="POST">
        <?= csrf_field() ?>
            <h3>Editar curso</h3>
            <input type="hidden" name="accion" value="guardar_edicion">
            <input type="hidden" name="id_curso" value="<?= (int)$cursoEditar["id_curso"] ?>">
            <div class="field">
                <label for="nombre">Nombre del curso</label>
                <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($cursoEditar["nombre"]) ?>" required>
            </div>
            <div class="row-actions">
                <button type="submit" class="btn-submit">Guardar cambios</button>
                <a href="cursos.php" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    <?php else: ?>
        <form class="panel-form" method="POST">
        <?= csrf_field() ?>
            <h3>Agregar curso</h3>
            <input type="hidden" name="accion" value="crear">
            <div class="field">
                <label for="nombre">Nombre del curso</label>
                <input type="text" name="nombre" id="nombre" placeholder="Ej. Comunicación" required>
            </div>
            <button type="submit" class="btn-submit">Guardar curso</button>
        </form>
    <?php endif; ?>

    <h2>Cursos registrados (<?= count($cursos) ?>)</h2>

    <?php if (count($cursos) === 0): ?>
        <p class="placeholder-text">Todavía no hay cursos registrados.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Curso</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($cursos as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nombre']) ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="cursos.php?editar=<?= (int)$c['id_curso'] ?>" class="btn-mini">Editar</a>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar este curso?');">
                                    <?= csrf_field() ?>
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_curso" value="<?= (int)$c['id_curso'] ?>">
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
