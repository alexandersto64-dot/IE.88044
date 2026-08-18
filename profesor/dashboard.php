<?php

session_start();


// ==================================================
// 1. COMPROBAR QUE EL USUARIO ESTÉ LOGUEADO
// ==================================================

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../index.html");
    exit;

}


// ==================================================
// 2. COMPROBAR QUE SEA PROFESOR
// ==================================================

if ($_SESSION["rol"] !== "PROFESOR") {

    die("Acceso no autorizado.");

}


// ==================================================
// 3. CONECTAR CON LA BASE DE DATOS
// ==================================================

require_once "../backend/config/database.php";


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
        Panel del Profesor - IE 88044
    </title>

    <link
        rel="stylesheet"
        href="../css/dashboard.css"
    >

</head>


<body>


<!-- ==================================================
     ENCABEZADO
================================================== -->

<header>

    <h1>
        Panel del Profesor
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
         TRABAJOS
    ================================================== -->

    <section>

        <h2>
            Mis trabajos
        </h2>


        <?php if (count($trabajos) === 0): ?>

            <p>
                No tienes trabajos registrados.
            </p>


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


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>


</main>


</body>

</html>
