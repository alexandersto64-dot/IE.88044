<?php

session_start();


// ==========================================
// 1. COMPROBAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../index.html");
    exit;

}


// ==========================================
// 2. COMPROBAR ROL
// ==========================================

if ($_SESSION["rol"] !== "ADMIN") {

    die("Acceso no autorizado.");

}


// ==========================================
// 3. CONECTAR A MYSQL
// ==========================================

require_once "../backend/config/database.php";


// ==========================================
// 4. OBTENER DATOS DEL ADMINISTRADOR
// ==========================================

$sql = "
    SELECT
        id_usuario,
        nombres,
        apellidos,
        correo,
        rol

    FROM usuarios

    WHERE id_usuario = ?
";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    $_SESSION["id_usuario"]
]);

$usuario = $stmt->fetch();


// ==========================================
// 5. COMPROBAR USUARIO
// ==========================================

if (!$usuario) {

    die("No se encontró el usuario.");

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
        Administrador - IE 88044
    </title>

    <link
        rel="stylesheet"
        href="../css/dashboard.css"
    >

</head>


<body>


<header>

    <div>

        <h1>
            Panel de Administración
        </h1>

        <p>

            Bienvenido,

            <?= htmlspecialchars($usuario["nombres"]) ?>

            <?= htmlspecialchars($usuario["apellidos"]) ?>

        </p>

    </div>


    <a href="../backend/auth/logout.php">
        Cerrar sesión
    </a>

</header>



<main>


    <section class="profile-card">

        <h2>
            Mi cuenta
        </h2>

        <p>

            <strong>
                Nombre:
            </strong>

            <?= htmlspecialchars($usuario["nombres"]) ?>

            <?= htmlspecialchars($usuario["apellidos"]) ?>

        </p>


        <p>

            <strong>
                Correo:
            </strong>

            <?= htmlspecialchars($usuario["correo"]) ?>

        </p>


        <p>

            <strong>
                Rol:
            </strong>

            <?= htmlspecialchars($usuario["rol"]) ?>

        </p>

    </section>



    <h2>
        Administración del sistema
    </h2>


    <div class="cards">


        <div class="card">

            <h3>
                👥 Usuarios
            </h3>

            <p>
                Crear, editar y administrar
                usuarios del sistema.
            </p>

            <a href="#">
                Gestionar usuarios
            </a>

        </div>



        <div class="card">

            <h3>
                👨‍🏫 Profesores
            </h3>

            <p>
                Administrar la información
                de los profesores.
            </p>

            <a href="#">
                Gestionar profesores
            </a>

        </div>



        <div class="card">

            <h3>
                📄 Documentos
            </h3>

            <p>
                Administrar los documentos
                institucionales.
            </p>

            <a href="#">
                Ver documentos
            </a>

        </div>



        <div class="card">

            <h3>
                📊 Reportes
            </h3>

            <p>
                Consultar reportes
                del sistema.
            </p>

            <a href="#">
                Ver reportes
            </a>

        </div>



        <div class="card">

            <h3>
                📚 Cursos
            </h3>

            <p>
                Administrar cursos
                académicos.
            </p>

            <a href="#">
                Gestionar cursos
            </a>

        </div>



        <div class="card">

            <h3>
                ⚙️ Configuración
            </h3>

            <p>
                Configurar opciones
                del sistema.
            </p>

            <a href="#">
                Configuración
            </a>

        </div>


    </div>

</main>


</body>

</html>
