<?php

// ==========================================
// Sidebar compartido del Intranet.
// Requiere: sesión iniciada (para $_SESSION["rol"])
// y $currentFile = basename(__FILE__) definido antes del include.
//
// Contador global de notificaciones: reutiliza la tabla
// `notificaciones` y las funciones de backend/config/notificaciones.php
// ya existentes (no se crea ninguna fuente de datos nueva). Se
// carga el helper aquí mismo (con ruta relativa a este archivo)
// para que funcione sin importar qué página incluya el sidebar.
// ==========================================

$rolActual = $_SESSION["rol"] ?? "";

// Los 3 roles de profesor (el genérico "PROFESOR" y los específicos
// "PROFESOR_PRIMARIA"/"PROFESOR_SECUNDARIO" agregados en la
// migración de niveles) comparten el mismo menú del Dashboard del
// Profesor: la separación primaria/secundaria ya la hace el propio
// contenido (grados asignados), no el menú.
$esRolProfesor = in_array($rolActual, ["PROFESOR", "PROFESOR_PRIMARIA", "PROFESOR_SECUNDARIO"], true);
$rolMenu = $esRolProfesor ? "PROFESOR" : $rolActual;

$notifNoLeidas = 0;

if (isset($conexion) && isset($_SESSION["id_usuario"])) {

    if (!function_exists("notificaciones_no_leidas")) {
        require_once __DIR__ . "/../config/notificaciones.php";
    }

    $notifNoLeidas = notificaciones_no_leidas($conexion, (int) $_SESSION["id_usuario"]);

}

// Página a la que debe apuntar el contador: solo enlazamos a una
// vista que realmente exista y liste las notificaciones del rol.
// Por ahora solo el Profesor tiene una sección de notificaciones
// (profesor/dashboard.php). Para ADMIN/SUBDIRECTOR el contador
// mostrará la cifra real (hoy siempre 0, porque el sistema aún no
// genera notificaciones dirigidas a esos roles), pero sin enlace,
// para no inventar una página de notificaciones que no existe.
$notifHref = $esRolProfesor ? "dashboard.php" : null;

// PCA/Unidades/Sesiones/Documentos ahora son por nivel+grado: si la
// página que incluye este sidebar ya tiene un grado en contexto
// (definido como $idNivelGradoSidebar antes del include), los
// enlaces del menú lo conservan; si no hay ninguno seleccionado
// todavía (p.ej. parado en "Mi panel"), esos enlaces llevan de
// vuelta al panel para elegir un grado primero.
$sufijoGrado = isset($idNivelGradoSidebar) && (int) $idNivelGradoSidebar > 0
    ? "?id_nivel_grado=" . (int) $idNivelGradoSidebar
    : null;

$menus = [

    "ADMIN" => [
        "titulo" => "Administración",
        "items" => [
            ["label" => "Dashboard", "icon" => "🏠", "href" => "dashboard.php"],
            ["label" => "Usuarios", "icon" => "👥", "href" => "usuarios.php"],
            ["label" => "Alumnos", "icon" => "🎓", "children" => [
                ["label" => "Alumnos", "href" => "alumnos.php"],
                ["label" => "Matrículas", "href" => "matriculas.php"],
            ]],
            ["label" => "Profesores", "icon" => "👨‍🏫", "href" => "profesores.php"],
            ["label" => "Cursos", "icon" => "📚", "href" => "cursos.php"],
            ["label" => "Documentos", "icon" => "📄", "href" => "documentos.php"],
            ["label" => "Reportes", "icon" => "📊", "href" => "reportes.php"],
            ["label" => "Configuración", "icon" => "⚙️", "href" => "configuracion.php"],
        ],
    ],

    "SUBDIRECTOR" => [
        "titulo" => "Subdirección",
        "items" => [
            ["label" => "Dashboard", "icon" => "🏠", "href" => "dashboard.php"],
            ["label" => "Alumnos", "icon" => "🎓", "href" => "alumnos.php"],
            ["label" => "Profesores", "icon" => "👨‍🏫", "href" => "profesores.php"],
            ["label" => "Documentos", "icon" => "📄", "href" => "documentos.php"],
            ["label" => "Revisión de trabajos", "icon" => "📝", "href" => "revision.php"],
            ["label" => "Solicitudes", "icon" => "📋", "href" => "solicitudes.php"],
            ["label" => "Comunicados", "icon" => "📢", "href" => "comunicados.php"],
            ["label" => "Periodos", "icon" => "🗓️", "href" => "periodos.php"],
            ["label" => "Reportes", "icon" => "📊", "href" => "reportes.php"],
        ],
    ],

    "PROFESOR" => [
        "titulo" => "Profesor",
        "items" => [
            ["label" => "Mi panel", "icon" => "🏠", "href" => "dashboard.php"],
            ["label" => "PCA", "icon" => "📘", "href" => "pca.php" . ($sufijoGrado ?? ""), "requiereGrado" => true],
            ["label" => "Unidades", "icon" => "🗂️", "href" => "unidades.php" . ($sufijoGrado ?? ""), "requiereGrado" => true],
            ["label" => "Sesiones", "icon" => "📝", "href" => "sesiones.php" . ($sufijoGrado ?? ""), "requiereGrado" => true],
            ["label" => "Documentos Institucionales", "icon" => "📄", "href" => "documentos.php" . ($sufijoGrado ?? ""), "requiereGrado" => true],
        ],
    ],

];

// Si el menú es el de Profesor y todavía no hay un grado en
// contexto, los ítems que lo requieren apuntan a "Mi panel" (donde
// se elige el grado) en vez de a una página que rechazaría el
// acceso por no traer id_nivel_grado.
if ($rolMenu === "PROFESOR" && !$sufijoGrado) {
    foreach ($menus["PROFESOR"]["items"] as &$item) {
        if (!empty($item["requiereGrado"])) {
            $item["href"] = "dashboard.php";
        }
    }
    unset($item);
}

$menu = $menus[$rolMenu] ?? ["titulo" => "Panel", "items" => []];

?>
<button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menú" aria-expanded="false">☰</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="panelSidebar">

    <div class="sidebar-brand">
        <span class="sidebar-brand-icon">🏫</span>
        <div>
            <strong>I.E.P. 88044</strong>
            <span><?= htmlspecialchars($menu["titulo"]) ?></span>
        </div>
    </div>

    <?php if (isset($etiqueta) && isset($idNivelGradoSidebar) && (int) $idNivelGradoSidebar > 0): ?>
        <div class="sidebar-context" title="Grado actual">
            <span class="nav-icon">🎒</span>
            <span><?= htmlspecialchars($etiqueta) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($notifHref): ?>
        <a href="<?= htmlspecialchars($notifHref) ?>" class="sidebar-notif<?= $notifNoLeidas > 0 ? " has-unread" : "" ?>">
            <span class="nav-icon">🔔</span>
            <span>Notificaciones</span>
            <?php if ($notifNoLeidas > 0): ?>
                <span class="notif-count"><?= $notifNoLeidas ?></span>
            <?php endif; ?>
        </a>
    <?php else: ?>
        <div class="sidebar-notif sidebar-notif-static" title="Notificaciones no leídas">
            <span class="nav-icon">🔔</span>
            <span>Notificaciones</span>
            <?php if ($notifNoLeidas > 0): ?>
                <span class="notif-count"><?= $notifNoLeidas ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <?php foreach ($menu["items"] as $item): ?>

            <?php if (!empty($item["children"])): ?>

                <?php
                    $abierto = false;
                    foreach ($item["children"] as $child) {
                        if ($child["href"] === $currentFile) { $abierto = true; }
                    }
                ?>

                <div class="nav-parent<?= $abierto ? " open" : "" ?>">
                    <button type="button" class="nav-link nav-parent-toggle">
                        <span class="nav-icon"><?= $item["icon"] ?></span>
                        <span><?= htmlspecialchars($item["label"]) ?></span>
                        <span class="nav-caret">▾</span>
                    </button>
                    <div class="nav-submenu">
                        <?php foreach ($item["children"] as $child): ?>
                            <a href="<?= htmlspecialchars($child["href"]) ?>" class="nav-sublink<?= $child["href"] === $currentFile ? " active" : "" ?>">
                                <?= htmlspecialchars($child["label"]) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php else: ?>

                <?php $hrefBase = strtok($item["href"], "?"); ?>
                <a href="<?= htmlspecialchars($item["href"]) ?>" class="nav-link<?= $hrefBase === $currentFile ? " active" : "" ?>">
                    <span class="nav-icon"><?= $item["icon"] ?></span>
                    <span><?= htmlspecialchars($item["label"]) ?></span>
                </a>

            <?php endif; ?>

        <?php endforeach; ?>
    </nav>

    <a href="../backend/auth/logout.php" class="sidebar-logout">🚪 Cerrar sesión</a>

</aside>
