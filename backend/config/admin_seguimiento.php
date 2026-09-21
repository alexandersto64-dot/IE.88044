<?php

// ==========================================================
// Mejoras de Administración: auditoría de acciones, papelera
// (borrado suave/restaurar) y panel de salud del sistema.
// ==========================================================

// ----------------------------------------------------------
// AUDITORÍA
// ----------------------------------------------------------

function auditoria_registrar(PDO $conexion, int $idUsuario, string $accion, string $entidad, ?int $idEntidad, string $detalle): void {
    $stmt = $conexion->prepare("
        INSERT INTO auditoria (id_usuario, accion, entidad, id_entidad, detalle)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$idUsuario, $accion, $entidad, $idEntidad, $detalle]);
}

function auditoria_listar(PDO $conexion, int $limite = 150): array {
    $stmt = $conexion->prepare("
        SELECT a.id_auditoria, a.accion, a.entidad, a.id_entidad, a.detalle, a.creado_en,
               u.nombres, u.apellidos
        FROM auditoria a
        INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
        ORDER BY a.creado_en DESC
        LIMIT " . max(1, $limite) . "
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// ----------------------------------------------------------
// PAPELERA (borrado suave / restaurar)
//
// $tabla y $columnaId SIEMPRE vienen fijos desde el código (nunca de
// $_GET/$_POST), así que interpolarlos en el SQL es seguro — no hay
// forma de que el usuario los cambie.
// ----------------------------------------------------------

function papelera_mover(PDO $conexion, string $tabla, string $columnaId, int $id): void {
    $stmt = $conexion->prepare("UPDATE `$tabla` SET eliminado_en = NOW() WHERE `$columnaId` = ?");
    $stmt->execute([$id]);
}

function papelera_restaurar(PDO $conexion, string $tabla, string $columnaId, int $id): void {
    $stmt = $conexion->prepare("UPDATE `$tabla` SET eliminado_en = NULL WHERE `$columnaId` = ?");
    $stmt->execute([$id]);
}

function papelera_usuarios(PDO $conexion): array {
    return $conexion->query("
        SELECT u.id_usuario, u.nombres, u.apellidos, u.correo, u.eliminado_en, r.nombre AS rol
        FROM usuarios u
        INNER JOIN roles r ON r.id_rol = u.id_rol
        WHERE u.eliminado_en IS NOT NULL
        ORDER BY u.eliminado_en DESC
    ")->fetchAll();
}

function papelera_alumnos(PDO $conexion): array {
    return $conexion->query("
        SELECT id_alumno, nombres, apellidos, dni, eliminado_en
        FROM alumnos
        WHERE eliminado_en IS NOT NULL
        ORDER BY eliminado_en DESC
    ")->fetchAll();
}

function papelera_cursos(PDO $conexion): array {
    return $conexion->query("
        SELECT id_curso, nombre, eliminado_en
        FROM cursos
        WHERE eliminado_en IS NOT NULL
        ORDER BY eliminado_en DESC
    ")->fetchAll();
}

// ----------------------------------------------------------
// SALUD DEL SISTEMA
// ----------------------------------------------------------

/** Tamaño total (bytes) y cantidad de archivos dentro de una carpeta, recursivo. */
function salud_tamano_carpeta(string $ruta): array {

    if (!is_dir($ruta)) {
        return ["bytes" => 0, "archivos" => 0];
    }

    $bytes = 0;
    $archivos = 0;

    $iterador = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterador as $item) {
        if ($item->isFile()) {
            $bytes += $item->getSize();
            $archivos++;
        }
    }

    return ["bytes" => $bytes, "archivos" => $archivos];

}

/** "1.2 MB", "340 KB", etc. */
function salud_formato_bytes(int $bytes): string {

    if ($bytes < 1024) {
        return $bytes . " B";
    }

    $unidades = ["KB", "MB", "GB", "TB"];
    $valor = $bytes / 1024;

    foreach ($unidades as $unidad) {
        if ($valor < 1024 || $unidad === end($unidades)) {
            return round($valor, 1) . " $unidad";
        }
        $valor /= 1024;
    }

    return $bytes . " B";

}

/** Conteo de filas de las tablas más relevantes, para el panel de salud. */
function salud_conteos_tablas(PDO $conexion): array {

    $tablas = [
        "usuarios" => "Usuarios",
        "alumnos" => "Alumnos",
        "documentos" => "Documentos institucionales",
        "trabajos" => "Trabajos",
        "comunicados" => "Comunicados",
        "eventos_institucionales" => "Eventos del calendario",
    ];

    $conteos = [];

    foreach ($tablas as $tabla => $etiqueta) {
        try {
            $total = (int) $conexion->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
        } catch (PDOException $e) {
            // La tabla podría no existir todavía si faltó correr
            // alguna migración anterior — no se cae el panel por eso.
            $total = null;
        }
        $conteos[] = ["etiqueta" => $etiqueta, "total" => $total];
    }

    return $conteos;

}
