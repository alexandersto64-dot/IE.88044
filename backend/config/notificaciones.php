<?php

// ==========================================
// Helpers de notificaciones — genérico y reutilizable.
// Requiere $conexion (PDO) ya definido, ver database.php.
// ==========================================

function notificar_crear(PDO $conexion, int $id_usuario, string $tipo, string $mensaje, ?string $url = null): void {

    $stmt = $conexion->prepare("
        INSERT INTO notificaciones (id_usuario, tipo, mensaje, url)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$id_usuario, $tipo, $mensaje, $url]);

}

function notificaciones_no_leidas(PDO $conexion, int $id_usuario): int {

    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total
        FROM notificaciones
        WHERE id_usuario = ? AND leido = 0
    ");

    $stmt->execute([$id_usuario]);

    return (int) $stmt->fetch()["total"];

}

function notificaciones_listar(PDO $conexion, int $id_usuario, int $limite = 10): array {

    $stmt = $conexion->prepare("
        SELECT id_notificacion, tipo, mensaje, url, leido, creado_en
        FROM notificaciones
        WHERE id_usuario = ?
        ORDER BY creado_en DESC
        LIMIT " . (int) $limite . "
    ");

    $stmt->execute([$id_usuario]);

    return $stmt->fetchAll();

}

function notificaciones_marcar_leidas(PDO $conexion, int $id_usuario): void {

    $stmt = $conexion->prepare("
        UPDATE notificaciones
        SET leido = 1
        WHERE id_usuario = ? AND leido = 0
    ");

    $stmt->execute([$id_usuario]);

}
