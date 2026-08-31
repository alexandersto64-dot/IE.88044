<?php

// ==========================================
// Lista fija de cursos para "Unidades > Semana > Sem-0X".
// Definida explícitamente en los requerimientos del Dashboard
// del Profesor (no se inventan cursos adicionales ni se toma
// de la tabla `cursos`, porque esa tabla es de otro módulo y
// puede no coincidir con esta lista).
//
// La clave (a la izquierda) es un identificador corto y estable
// que se guarda en la base de datos (columna `curso`); el valor
// es el nombre visible.
// ==========================================

const MATERIALES_CURSOS = [
    "matematica"    => "Matemática",
    "comunicacion"  => "Comunicación",
    "ept"           => "EPT",
    "religion"      => "Religión",
    "cyt"           => "C y T",
    "arte"          => "Arte",
    "dpcc"          => "DPCC",
    "ccss"          => "CC.SS",
    "ed_fisica"     => "Ed. Física",
    "err"           => "E.R.R",
    "ingles"        => "Inglés",
];

const MATERIALES_TOTAL_UNIDADES = 8;
const MATERIALES_TOTAL_SEMANAS = 5;

function materiales_curso_nombre(string $clave): ?string {
    return MATERIALES_CURSOS[$clave] ?? null;
}

function materiales_semana_label(int $semana): string {
    return "Semana " . str_pad((string) $semana, 2, "0", STR_PAD_LEFT);
}

function materiales_sem_label(int $semana): string {
    return "Sem-" . str_pad((string) $semana, 2, "0", STR_PAD_LEFT);
}
