<?php

namespace App\Services;

/**
 * EntityConfidenceScorer
 * ----------------------------------------------------------------------------
 * Asigna a cada entidad detectada por el anonimizador un porcentaje 0–100 que
 * estima la probabilidad de que sea EFECTIVAMENTE una entidad real (y no ruido
 * como abreviaturas «Tª», romanos «TII», siglas «ART», etc.).
 *
 * Diseño:
 *   - Sin acceso a BD ni HTTP: función pura sobre (entidades agrupadas + texto).
 *   - Todas las señales y sus pesos están declarados como constantes en el
 *     bloque «PESOS DE SEÑALES» para poder tunearlos en futuras iteraciones
 *     sin tocar la lógica.
 *   - `score_signals` devuelve el desglose de deltas aplicados: sirve para
 *     depurar/tunear y, a futuro, para telemetría.
 *
 * Entrada de `scoreGrouped()`:
 *   $groupedEntities : salida de WordAnonymizerController::buildGroupedEntities()
 *                      [ ['text','label','count','positions','variants'], ... ]
 *   $originalText    : texto plano que se envió al servicio NLP.
 *   $sourceHints     : mapa opcional para saber el origen de cada entidad:
 *                        "normText|LABEL" => 'spacy' | 'regex'
 *                        "WL::normText"   => 'whitelist'
 *                      (lo arma el controller ANTES de que injectWhitelistEntities
 *                       reconstruya el array y pierda la clave 'source').
 *
 * Salida: el mismo array, cada item con:
 *   'score'         => int 0..100
 *   'is_alert'      => bool  (score < ALERT_THRESHOLD)
 *   'score_signals' => array<string,int>  deltas aplicados
 */
class EntityConfidenceScorer
{
    // ===================== PESOS DE SEÑALES (tunear aquí) =====================

    /** Bases por fuente de detección. */
    private const BASE_WHITELIST              = 100; // hard: retorna 100 sin más cálculo
    private const BASE_REGEX_EMAIL            = 98;
    private const BASE_REGEX_CUIT_VALID       = 99;  // dígito verificador módulo 11 OK
    private const BASE_REGEX_CUIT_INVALID     = 60;  // formato CUIT pero dígito inválido
    private const BASE_REGEX_PATENTE_MERCOSUR = 95;  // AB123CD / A123BCD
    private const BASE_REGEX_PATENTE_VIEJA    = 55;  // ABC123 / 123ABC (regex flojo)
    private const BASE_REGEX_DNI              = 70;  // \b\d{7,8}\b (flojo)
    private const BASE_REGEX_PHONE            = 75;
    private const BASE_REGEX_OTHER            = 88;
    private const BASE_SPACY                  = 55;

    /** Topes por longitud (regla dura del usuario). */
    private const CAP_LEN_LE3 = 9;  // len(trim) <= 3  ⇒  score = min(score, 9)
    private const CAP_LEN_4   = 25; // len(trim) == 4  ⇒  score = min(score, 25)

    /** Penalizaciones. */
    private const PENALTY_ORDINAL_CHAR      = 60; // contiene «ª» / «º» y es label de nombre
    private const HARDCAP_ROMAN_ALLCAPS     = 5;  // romano / allcaps corto ⇒ score = 5
    private const PENALTY_DIGIT_RATIO_NAME  = 35; // escalado por el ratio de dígitos
    private const PENALTY_SINGLE_SHORT_WORD = 15; // PER de una sola palabra <= 4 chars
    private const FLOOR_NOISE_LIST          = 3;  // término en NOISE_TERMS ⇒ score = min(score, 3)

    /** Bonificaciones. */
    private const BOOST_TITLECASE_MULTIWORD = 12;
    private const BOOST_FREQUENCY           = 6;  // count >= 3
    private const BOOST_CONTEXT_KEYWORD     = 18; // una sola vez por entidad
    private const BOOST_DIGIT_RATIO_OK      = 10; // ratio alto en DNI/PHONE/PATENTE/CUIT

    /**
     * Patente con formato "viejo" (ABC123 / 123ABC) SIN ninguna palabra de
     * contexto vehicular cerca: el regex es muy laxo y colisiona con citas
     * legales ("LEY 264/09"), por eso se degrada a alerta.
     */
    private const PENALTY_PATENTE_VIEJA_NO_CONTEXT = 50;

    /** Umbrales / límites. */
    private const ALERT_THRESHOLD         = 20; // score < 20  ⇒  is_alert = true
    private const CONTEXT_RADIUS          = 40; // caracteres a cada lado de la ocurrencia
    private const MAX_OCCURRENCES_SCANNED = 20;

    /** Labels considerados «nombres» (para reglas de ruido). */
    private const NAME_LABELS = ['PER', 'PERSON', 'ORG', 'LOC', 'GPE', 'MISC'];

    /** Labels donde los dígitos son esperables. */
    private const NUMERIC_LABELS = ['DNI', 'PHONE', 'PATENTE', 'CUIT'];

    /**
     * Términos que casi siempre son ruido en documentos judiciales argentinos.
     * Normalizados (minúsculas, sin tildes, sin punto final).
     */
    private const NOISE_TERMS = [
        'ta', 'ti', 'tii', 'tiii', 'tiv', 'tv',
        'fs', 'fojas', 'fo',
        'art', 'arts', 'inc', 'incs', 'ss', 'sgtes', 'sig', 'sigtes',
        'cpr', 'cpp', 'cpccn', 'cpc', 'cn', 'cc', 'ccyc', 'lct',
        'cfr', 'conf', 'cit', 'op', 'cap',
        'expte', 'exptes', 'cuij',
        'dr', 'dra', 'sr', 'sra', 'sres', 'srta',
    ];

    /**
     * Palabras de contexto que suben la confianza si aparecen cerca de la entidad.
     * Clave = label; valores normalizados.
     */
    private const CTX_KEYWORDS = [
        'DNI'     => ['dni', 'documento', 'd.n.i', 'libreta', 'l.c', 'l.e'],
        'LOC'     => ['domicilio', 'calle', 'av.', 'avenida', 'barrio', 'localidad', 'ciudad de', 'partido de', 'ruta'],
        'PATENTE' => ['dominio', 'patente', 'vehiculo', 'automotor', 'rodado', 'marca', 'chapa', 'chasis'],
        'CUIT'    => ['cuit', 'cuil', 'c.u.i.t', 'c.u.i.l', 'clave unica', 'clave de identificacion'],
        'PER'     => ['dr.', 'sr.', 'sra.', 'dra.', 'abogad', 'doctor', 'senor', 'senora', 'imputad', 'testigo', 'perito', 'juez'],
        'ORG'     => ['s.a', 'srl', 's.r.l', 'sociedad', 'empresa', 'ministerio', 'secretaria', 'municipalidad', 'banco'],
    ];

    // ========================================================================

    /**
     * @param  array<int,array<string,mixed>>  $groupedEntities
     * @param  array<string,string>            $sourceHints
     * @return array<int,array<string,mixed>>
     */
    public function scoreGrouped(array $groupedEntities, string $originalText, array $sourceHints = []): array
    {
        foreach ($groupedEntities as &$entity) {
            $result = $this->scoreOne($entity, $originalText, $sourceHints);
            $entity['score']         = $result['score'];
            $entity['is_alert']      = $result['score'] < self::ALERT_THRESHOLD;
            $entity['score_signals'] = $result['signals'];
        }
        unset($entity);

        return $groupedEntities;
    }

    /**
     * @param  array<string,mixed>   $entity
     * @param  array<string,string>  $hints
     * @return array{score:int,signals:array<string,int>}
     */
    private function scoreOne(array $entity, string $text, array $hints): array
    {
        $raw     = trim((string) ($entity['text'] ?? ''));
        $label   = strtoupper((string) ($entity['label'] ?? ''));
        $count   = (int) ($entity['count'] ?? 1);
        $variants = $entity['variants'] ?? [$raw];
        if (!is_array($variants) || $variants === []) {
            $variants = [$raw];
        }

        $signals = [];
        if ($raw === '') {
            return ['score' => 0, 'signals' => ['empty' => 0]];
        }

        $source = $this->resolveSource($raw, $label, $hints);

        // 1. Whitelist ⇒ el usuario ya la reconoció explícitamente.
        if ($source === 'whitelist') {
            return ['score' => self::BASE_WHITELIST, 'signals' => ['whitelist' => self::BASE_WHITELIST]];
        }

        // 2. Base según fuente + label.
        $score = $this->baseScore($raw, $label, $source, $signals);

        $len       = mb_strlen($raw);
        $isNumeric = in_array($label, self::NUMERIC_LABELS, true);
        $isName    = in_array($label, self::NAME_LABELS, true);

        // 3. Romano / allcaps corto ⇒ tope duro (sólo para labels de nombre).
        if ($isName && $this->isRomanOrAllcapsShort($raw)) {
            return ['score' => self::HARDCAP_ROMAN_ALLCAPS, 'signals' => ['roman_allcaps' => self::HARDCAP_ROMAN_ALLCAPS]];
        }

        // 4. Indicador ordinal «ª» / «º» en un nombre ⇒ penalización fuerte.
        if ($isName && $this->hasOrdinalChar($raw)) {
            $score -= self::PENALTY_ORDINAL_CHAR;
            $signals['ordinal_char'] = -self::PENALTY_ORDINAL_CHAR;
        }

        // 5. Ratio de dígitos.
        $ratio = $this->digitRatio($raw);
        if ($isName && $ratio > 0) {
            $delta = (int) round(self::PENALTY_DIGIT_RATIO_NAME * $ratio);
            $score -= $delta;
            $signals['digit_ratio_name'] = -$delta;
        } elseif ($isNumeric && $ratio >= 0.5) {
            $score += self::BOOST_DIGIT_RATIO_OK;
            $signals['digit_ratio_ok'] = self::BOOST_DIGIT_RATIO_OK;
        }

        // 6. Forma de nombre propio.
        if (in_array($label, ['PER', 'PERSON'], true)) {
            if ($this->isTitleCaseMultiword($raw)) {
                $score += self::BOOST_TITLECASE_MULTIWORD;
                $signals['titlecase_multiword'] = self::BOOST_TITLECASE_MULTIWORD;
            } elseif ($this->wordCount($raw) === 1 && $len <= 4) {
                $score -= self::PENALTY_SINGLE_SHORT_WORD;
                $signals['single_short_word'] = -self::PENALTY_SINGLE_SHORT_WORD;
            }
        }

        // 7. Frecuencia: lo que se repite suele ser real.
        if ($count >= 3) {
            $score += self::BOOST_FREQUENCY;
            $signals['frequency'] = self::BOOST_FREQUENCY;
        }

        // 8. Contexto: keywords cerca de alguna ocurrencia.
        $windows = $this->contextWindows($text, $variants);
        if ($this->windowsMatchKeywords($windows, $this->keywordsFor($label))) {
            $score += self::BOOST_CONTEXT_KEYWORD;
            $signals['context_keyword'] = self::BOOST_CONTEXT_KEYWORD;
        }

        // 8b. Patente "vieja" (regex laxo LLL NNN): sin contexto vehicular cerca
        //     es casi siempre una cita normativa ("LEY 264") ⇒ se degrada a alerta.
        if ($label === 'PATENTE' && $this->matchesPatenteVieja($raw)) {
            $hasVehic = $this->windowsMatchKeywords($windows, self::CTX_KEYWORDS['PATENTE']);
            if (!$hasVehic) {
                $score -= self::PENALTY_PATENTE_VIEJA_NO_CONTEXT;
                $signals['patente_vieja_no_context'] = -self::PENALTY_PATENTE_VIEJA_NO_CONTEXT;
            }
        }

        // 9. Topes por longitud (regla dura del usuario).
        if ($len <= 3 && $score > self::CAP_LEN_LE3) {
            $score = self::CAP_LEN_LE3;
            $signals['cap_len_le3'] = self::CAP_LEN_LE3;
        } elseif ($len === 4 && $score > self::CAP_LEN_4) {
            $score = self::CAP_LEN_4;
            $signals['cap_len_4'] = self::CAP_LEN_4;
        }

        // 10. Piso de ruido: término en lista negra de siglas/abreviaturas.
        if (in_array($this->normalizeNoise($raw), self::NOISE_TERMS, true) && $score > self::FLOOR_NOISE_LIST) {
            $score = self::FLOOR_NOISE_LIST;
            $signals['noise_list'] = self::FLOOR_NOISE_LIST;
        }

        $score = max(0, min(100, (int) round($score)));

        return ['score' => $score, 'signals' => $signals];
    }

    // ── Bases ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string,int>  $signals  (por referencia)
     */
    private function baseScore(string $raw, string $label, ?string $source, array &$signals): int
    {
        if ($source === 'regex') {
            $base = match ($label) {
                'EMAIL' => self::BASE_REGEX_EMAIL,
                'DNI'   => self::BASE_REGEX_DNI,
                'PHONE' => self::BASE_REGEX_PHONE,
                'CUIT'  => $this->looksLikeCuit($raw) ? self::BASE_REGEX_CUIT_VALID : self::BASE_REGEX_CUIT_INVALID,
                'PATENTE' => $this->matchesPatenteMercosur($raw)
                    ? self::BASE_REGEX_PATENTE_MERCOSUR
                    : self::BASE_REGEX_PATENTE_VIEJA,
                default => self::BASE_REGEX_OTHER,
            };
            $signals['base_regex'] = $base;
            return $base;
        }

        // spacy / desconocido: si «parece» un identificador fuerte, subir la base.
        if ($this->looksLikeCuit($raw)) {
            $signals['base_cuit_like'] = self::BASE_REGEX_CUIT_VALID;
            return self::BASE_REGEX_CUIT_VALID;
        }

        $signals['base_spacy'] = self::BASE_SPACY;
        return self::BASE_SPACY;
    }

    private function resolveSource(string $raw, string $label, array $hints): ?string
    {
        $norm = $this->normalize($raw);

        if (isset($hints['WL::' . $norm])) {
            return 'whitelist';
        }
        $key = $norm . '|' . $label;
        if (isset($hints[$key])) {
            return $hints[$key];
        }
        // Sin label (por si el hint quedó con label vacío u otro sinónimo).
        foreach (['PER' => 'PERSON', 'PERSON' => 'PER', 'LOC' => 'GPE', 'GPE' => 'LOC'] as $a => $b) {
            if ($label === $a && isset($hints[$norm . '|' . $b])) {
                return $hints[$norm . '|' . $b];
            }
        }

        return null;
    }

    // ── Detectores de forma ────────────────────────────────────────────────

    private function isRomanOrAllcapsShort(string $t): bool
    {
        $t = trim($t);
        if (mb_strlen($t) > 5) {
            return false;
        }
        if (preg_match('/^[IVXLCDM]+$/i', $t)) {
            return true;
        }
        if (preg_match('/^T[IVXLCDM]+$/i', $t)) {
            return true;
        }
        // Allcaps corto que además figura como ruido (ART, INC, CPP...).
        if ($t === mb_strtoupper($t) && in_array($this->normalizeNoise($t), self::NOISE_TERMS, true)) {
            return true;
        }

        return false;
    }

    private function hasOrdinalChar(string $t): bool
    {
        return mb_strpos($t, 'ª') !== false || mb_strpos($t, 'º') !== false
            || mb_strpos($t, '°') !== false;
    }

    private function digitRatio(string $t): float
    {
        $chars = preg_replace('/\s+/u', '', $t) ?? '';
        $total = mb_strlen($chars);
        if ($total === 0) {
            return 0.0;
        }
        $digits = preg_match_all('/\d/u', $chars);

        return $digits / $total;
    }

    private function wordCount(string $t): int
    {
        $parts = preg_split('/\s+/u', trim($t), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? count($parts) : 0;
    }

    private function isTitleCaseMultiword(string $t): bool
    {
        $parts = preg_split('/\s+/u', trim($t), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            return false;
        }
        foreach ($parts as $w) {
            // Palabras cortas de enlace admitidas en minúscula: de, del, la, los, y
            if (in_array(mb_strtolower($w), ['de', 'del', 'la', 'las', 'los', 'y', 'da', 'do'], true)) {
                continue;
            }
            if (!preg_match('/^[\p{Lu}\p{Lt}]/u', $w)) {
                return false;
            }
        }

        return true;
    }

    private function matchesPatenteMercosur(string $t): bool
    {
        $t = trim($t);

        return (bool) preg_match('/^[A-Z][ \-]?\d{3}[ \-]?[A-Z]{3}$/i', $t)   // moto
            || (bool) preg_match('/^[A-Z]{2}[ \-]?\d{3}[ \-]?[A-Z]{2}$/i', $t); // auto
    }

    private function matchesPatenteVieja(string $t): bool
    {
        $t = trim($t);

        return (bool) preg_match('/^[A-Z]{3}[ \-]?\d{3}$/i', $t)   // auto
            || (bool) preg_match('/^\d{3}[ \-]?[A-Z]{3}$/i', $t);  // moto
    }

    /**
     * ¿Es un CUIT/CUIL argentino válido? Formato XX-XXXXXXXX-X con dígito
     * verificador módulo 11.
     */
    private function looksLikeCuit(string $t): bool
    {
        $digits = preg_replace('/\D/', '', $t) ?? '';
        if (strlen($digits) !== 11) {
            return false;
        }
        if (!in_array(substr($digits, 0, 2), ['20', '23', '24', '27', '30', '33', '34'], true)) {
            return false;
        }

        $weights = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int) $digits[$i]) * $weights[$i];
        }
        $mod = 11 - ($sum % 11);
        if ($mod === 11) {
            $mod = 0;
        } elseif ($mod === 10) {
            $mod = 9;
        }

        return $mod === (int) $digits[10];
    }

    // ── Contexto ───────────────────────────────────────────────────────────

    /** @return array<int,string> */
    private function keywordsFor(string $label): array
    {
        $keywords = self::CTX_KEYWORDS[$label] ?? null;
        if ($keywords === null) {
            $alias = ['PERSON' => 'PER', 'GPE' => 'LOC'][$label] ?? null;
            $keywords = $alias ? (self::CTX_KEYWORDS[$alias] ?? []) : [];
        }

        return $keywords;
    }

    /**
     * @param  array<int,string>  $windows
     * @param  array<int,string>  $keywords
     */
    private function windowsMatchKeywords(array $windows, array $keywords): bool
    {
        if (!$keywords) {
            return false;
        }
        foreach ($windows as $window) {
            foreach ($keywords as $kw) {
                if (mb_strpos($window, $kw) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Devuelve ventanas de texto normalizado alrededor de cada ocurrencia de
     * cualquiera de las variantes.
     *
     * @param  array<int,string>  $variants
     * @return array<int,string>
     */
    private function contextWindows(string $text, array $variants): array
    {
        $windows = [];
        $scanned = 0;

        foreach ($variants as $variant) {
            $needle = trim((string) $variant);
            if ($needle === '') {
                continue;
            }
            $offset = 0;
            while (($pos = mb_stripos($text, $needle, $offset)) !== false) {
                $from = max(0, $pos - self::CONTEXT_RADIUS);
                $length = mb_strlen($needle) + self::CONTEXT_RADIUS * 2;
                $windows[] = $this->normalize(mb_substr($text, $from, $length));

                $offset = $pos + mb_strlen($needle);
                if (++$scanned >= self::MAX_OCCURRENCES_SCANNED) {
                    return $windows;
                }
            }
        }

        return $windows;
    }

    // ── Normalización ──────────────────────────────────────────────────────

    /**
     * Clave de normalización compartida con los controllers para armar
     * $sourceHints: minúsculas + sin diacríticos + espacios colapsados.
     */
    public static function normalizeKey(string $s): string
    {
        if (class_exists('Normalizer')) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_KD) ?: $s;
        }
        $s = preg_replace('/\p{M}/u', '', $s) ?? $s;
        $s = mb_strtolower($s);

        return trim((string) preg_replace('/\s+/u', ' ', $s));
    }

    /** minúsculas + sin diacríticos + espacios colapsados. */
    private function normalize(string $s): string
    {
        return self::normalizeKey($s);
    }

    /** Como normalize() pero además quita puntos finales (para siglas «art.»). */
    private function normalizeNoise(string $s): string
    {
        return rtrim($this->normalize($s), '.');
    }
}
