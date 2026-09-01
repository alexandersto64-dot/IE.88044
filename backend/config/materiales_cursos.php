<?php

// ==========================================
// Cursos/áreas para "PCA > Unidades / Sesiones > Sem-0X" del
// Dashboard del Profesor, SEPARADOS POR NIVEL EDUCATIVO.
//
// Cada entrada fue verificada contra la tabla real `cursos` de la
// BD (colegio_ie88044): la clave de la izquierda es el identificador
// corto y estable que se guarda en profesor_unidad_archivos.curso /
// profesor_sesion_archivos.curso; el valor es el nombre visible
// (se muestran algunos nombres abreviados a pedido, pero cada uno
// corresponde 1 a 1 a un curso real de esa `nivel` en la BD, nunca
// a un curso inventado). El comentario de cada línea indica el
// id_curso real y el nombre completo tal como está en `cursos`.
//
// PRIMARIA: la tabla `cursos` tiene 9 cursos para este nivel
// (Matemática, Comunicación, Ciencia y Tecnología, Personal Social,
// Inglés, Educación Física, Arte y Cultura, Computación, y ahora
// Educación Religiosa, id_curso 19, código PRI-REL, agregada y
// confirmada en la BD). De esos 9, aquí solo se listan las 7 que
// corresponden a las áreas pedidas — "Inglés" y "Computación" quedan
// fuera porque no fueron pedidas. Primaria = 7/7 cursos confirmados.
// ==========================================

const MATERIALES_CURSOS_PRIMARIA = [
    "arte"               => "Arte y Cultura",       // id_curso 7  · Arte y Cultura (PRIMARIA)
    "ciencia_tecnologia" => "Ciencia y Tecnología",  // id_curso 3  · Ciencia y Tecnología (PRIMARIA)
    "comunicacion"       => "Comunicación",          // id_curso 2  · Comunicación (PRIMARIA)
    "ed_fisica"          => "Educación Física",      // id_curso 6  · Educación Física (PRIMARIA)
    "ed_religiosa"       => "Educación Religiosa",   // id_curso 19 · Educación Religiosa (PRIMARIA)
    "matematica"         => "Matemática",            // id_curso 1  · Matemática (PRIMARIA)
    "personal_social"    => "Personal Social",       // id_curso 4  · Personal Social (PRIMARIA)
];

// SECUNDARIA: la tabla `cursos` tiene exactamente 10 cursos para
// este nivel y los 10 corresponden 1 a 1 a las 10 áreas pedidas
// (3 con nombre abreviado a pedido: Arte, CC.SS y Religión).
const MATERIALES_CURSOS_SECUNDARIA = [
    "arte"               => "Arte",                  // id_curso 16 · Arte y Cultura (SECUNDARIA)
    "ccss"               => "CC.SS",                 // id_curso 12 · Ciencias Sociales (SECUNDARIA)
    "ciencia_tecnologia" => "Ciencia y Tecnología",   // id_curso 11 · Ciencia y Tecnología (SECUNDARIA)
    "comunicacion"       => "Comunicación",           // id_curso 10 · Comunicación (SECUNDARIA)
    "dpcc"               => "DPCC",                   // id_curso 13 · DPCC (SECUNDARIA)
    "ed_fisica"          => "Educación Física",       // id_curso 15 · Educación Física (SECUNDARIA)
    "ept"                => "EPT",                    // id_curso 14 · EPT (SECUNDARIA)
    "ingles"             => "Inglés",                 // id_curso 18 · Inglés (SECUNDARIA)
    "matematica"         => "Matemática",             // id_curso 9  · Matemática (SECUNDARIA)
    "religion"           => "Religión",               // id_curso 17 · Educación Religiosa (SECUNDARIA)
];

const MATERIALES_TOTAL_UNIDADES = 8;
const MATERIALES_TOTAL_SEMANAS = 5;

/**
 * Cursos/áreas visibles para un nivel educativo dado, en el orden
 * alfabético exacto en que deben mostrarse (el orden de declaración
 * de las constantes de arriba ya es alfabético por nombre visible).
 * Nunca mezcla PRIMARIA y SECUNDARIA.
 *
 * @return array<string,string> clave => nombre visible
 */
function materiales_cursos_por_nivel(string $nivel): array {
    return match ($nivel) {
        "PRIMARIA" => MATERIALES_CURSOS_PRIMARIA,
        "SECUNDARIA" => MATERIALES_CURSOS_SECUNDARIA,
        default => [],
    };
}

/**
 * Nombre visible de una clave de curso, validado SIEMPRE contra el
 * nivel educativo del grado en el que se está trabajando (nunca
 * contra la lista completa): una clave válida en SECUNDARIA (p.ej.
 * "ept") debe rechazarse si el grado actual es de PRIMARIA, y
 * viceversa. Devuelve null si la clave no existe para ese nivel.
 */
function materiales_curso_nombre(string $clave, string $nivel): ?string {
    return materiales_cursos_por_nivel($nivel)[$clave] ?? null;
}

function materiales_semana_label(int $semana): string {
    return "Semana " . str_pad((string) $semana, 2, "0", STR_PAD_LEFT);
}

function materiales_sem_label(int $semana): string {
    return "Sem-" . str_pad((string) $semana, 2, "0", STR_PAD_LEFT);
}
