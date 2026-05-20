<?php

namespace App\Services;

/**
 * TextNormalizationService
 *
 * Proporciona utilidades de normalización y transformación de texto
 * para usar en distintos pipelines (anonimizador, NLP, OCR, etc.).
 */
class TextNormalizationService
{
    /**
     * Convierte un nombre completo a sus iniciales con puntos.
     *
     * Ejemplos:
     *   "Juan Martin Perez Gonzales" → "J.M.P.G."
     *   "Maria Elena Lopez"          → "M.E.L."
     *   "José Álvarez"               → "J.Á."
     *
     * @param  string  $fullName  Nombre completo a convertir.
     * @return string             Iniciales en mayúsculas separadas por punto, terminadas en ".".
     *                            Devuelve cadena vacía si el input es vacío.
     */
    public function toInitials(string $fullName): string
    {
        // Limpiar espacios duplicados y bordes
        $cleaned = preg_replace('/\s+/', ' ', trim($fullName));

        if ($cleaned === '') {
            return '';
        }

        $words = explode(' ', $cleaned);

        $initials = [];
        foreach ($words as $word) {
            if (mb_strlen($word, 'UTF-8') === 0) {
                continue;
            }
            // Tomar primera letra y convertir a mayúscula (soporta UTF-8/acentos)
            $initials[] = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
        }

        if (empty($initials)) {
            return '';
        }

        return implode('.', $initials) . '.';
    }
}
