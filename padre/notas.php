<?php

require_once __DIR__ . "/../backend/partials/padre_bootstrap.php";

if (!$alumnoActivo) {
    header("Location: dashboard.php");
    exit;
}

$idAlumno = (int) $alumnoActivo["id_alumno"];

// ==================================================
// PERIODOS con notas registradas para este alumno (no solo el de
// su matrícula activa: si cambió de grado entre periodos, sus notas
// anteriores igual deben poder consultarse). Si no tiene ninguna
// nota todavía, se usa el periodo de su matrícula activa como único
// filtro disponible (o ninguno, si tampoco tiene matrícula).
// ==================================================

$stmtPeriodos = $conexion->prepare("
    SELECT DISTINCT n.id_periodo, per.nombre
    FROM notas n
    INNER JOIN periodos_academicos per ON per.id_periodo = n.id_periodo
    WHERE n.id_alumno = ?
    ORDER BY n.id_periodo DESC
");
$stmtPeriodos->execute([$idAlumno]);
$periodosConNotas = $stmtPeriodos->fetchAll();

if (count($periodosConNotas) === 0 && $alumnoActivo["id_periodo"]) {
    $periodosConNotas = [["id_periodo" => $alumnoActivo["id_periodo"], "nombre" => $alumnoActivo["periodo_nombre"]]];
}

$idPeriodoSeleccionado = null;

if (isset($_GET["id_periodo"])) {
    $candidato = (int) $_GET["id_periodo"];
    foreach ($periodosConNotas as $p) {
        if ((int) $p["id_periodo"] === $candidato) { $idPeriodoSeleccionado = $candidato; break; }
    }
}

if ($idPeriodoSeleccionado === null && count($periodosConNotas) > 0) {
    $idPeriodoSeleccionado = (int) $periodosConNotas[0]["id_periodo"];
}

$notas = $idPeriodoSeleccionado !== null ? padre_notas($conexion, $idAlumno, $idPeriodoSeleccionado) : [];

$notasPorBimestre = [];
foreach ($notas as $n) {
    $notasPorBimestre[(int) $n["bimestre"]][] = $n;
}
ksort($notasPorBimestre);

$etiquetasNotaLiteral = ["AD" => "Logro destacado", "A" => "Logro esperado", "B" => "En proceso", "C" => "En inicio"];

?>



<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Boleta de notas - I.E.P. 88044 Abraham Valdelomar
    </title>

    <link rel="stylesheet" href="../css/styles.css?v=202609211855">
    <link rel="stylesheet" href="../css/dashboard.css?v=202609211855">

</head>


<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">

<header>
    <div>
        <span class="header-eyebrow">Padre de familia</span>
        <h1>Boleta de notas</h1>
    </div>
</header>

<main>

    <section class="panel-section">
        <div class="panel-section-head">
            <h2><?= icon("backpack") ?> <?= htmlspecialchars(trim($alumnoActivo["nombres"] . " " . $alumnoActivo["apellidos"])) ?></h2>
            <p class="panel-section-sub">
                <?= $alumnoActivo["grado_nombre"] ? htmlspecialchars($alumnoActivo["grado_nombre"] . " " . ucfirst(strtolower($alumnoActivo["nivel"]))) : "Sin matrícula activa" ?>
            </p>
        </div>

        <?php if (count($periodosConNotas) > 1): ?>
            <form method="get" class="filtro-inline">
                <?php if (isset($_GET["id_alumno"])): ?>
                    <input type="hidden" name="id_alumno" value="<?= (int) $_GET["id_alumno"] ?>">
                <?php endif; ?>
                <label for="id_periodo">Periodo académico</label>
                <select name="id_periodo" id="id_periodo" onchange="this.form.submit()">
                    <?php foreach ($periodosConNotas as $p): ?>
                        <option value="<?= (int) $p["id_periodo"] ?>" <?= (int) $p["id_periodo"] === $idPeriodoSeleccionado ? "selected" : "" ?>>
                            <?= htmlspecialchars($p["nombre"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </section>

    <?php if (count($notasPorBimestre) === 0): ?>

        <div class="empty-state">
            <span class="empty-state-icon" aria-hidden="true"><?= icon("clipboard") ?></span>
            <h2>Sin notas registradas todavía</h2>
            <p>Cuando el profesor registre las primeras notas del bimestre, aparecerán aquí.</p>
        </div>

    <?php else: ?>

        <?php foreach ($notasPorBimestre as $bimestre => $filas): ?>
            <section class="panel-section">
                <div class="panel-section-head">
                    <h2>Bimestre <?= (int) $bimestre ?></h2>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Nota</th>
                                <th>Última actualización</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filas as $n): ?>
                                <tr>
                                    <td><?= htmlspecialchars($n["curso_nombre"]) ?></td>
                                    <td>
                                        <?php if ($n["nota_vigesimal"] !== null): ?>
                                            <span class="status-badge <?= (int) $n["nota_vigesimal"] < 11 ? "status-inactivo" : "status-activo" ?>">
                                                <?= (int) $n["nota_vigesimal"] ?>
                                            </span>
                                        <?php elseif ($n["nota_literal"] !== null): ?>
                                            <span class="status-badge status-nota-<?= strtolower($n["nota_literal"]) ?>" title="<?= htmlspecialchars($etiquetasNotaLiteral[$n["nota_literal"]] ?? "") ?>">
                                                <?= htmlspecialchars($n["nota_literal"]) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="placeholder-text">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="placeholder-text"><?= htmlspecialchars(date("d/m/Y", strtotime($n["actualizado_en"]))) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>

    <?php endif; ?>

</main>

</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js?v=202609211901"></script>

</body>

</html>
