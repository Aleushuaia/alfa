<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * DashboardRepository
 *
 * Abstrae todas las consultas al sistema SAE Kayen.
 * Cuando DASHBOARD_DEMO=true devuelve datos ficticios realistas.
 */
class DashboardRepository
{
    protected string $connection = 'sae_kayen';
    protected int    $cacheTtl;
    protected bool   $demo;

    public function __construct()
    {
        $this->cacheTtl = (int) env('DASHBOARD_CACHE_TTL', 300);
        $this->demo     = filter_var(env('DASHBOARD_DEMO', true), FILTER_VALIDATE_BOOLEAN);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // WIDGET 1 — Tarjetas de resumen
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Retorna los 4 indicadores principales del mes actual y anterior.
     */
    public function getResumenCards(int $mes, int $anio): array
    {
        $key = "dashboard.cards.{$mes}.{$anio}";

        return Cache::remember($key, $this->cacheTtl, function () use ($mes, $anio) {
            // rangos [start, end)
            $currentStart = date('Y-m-d 00:00:00', strtotime("$anio-$mes-01"));
            $currentEnd   = date('Y-m-d 00:00:00', strtotime($currentStart . ' +1 month'));
            $prevStart    = date('Y-m-d 00:00:00', strtotime($currentStart . ' -1 month'));
            $prevEnd      = $currentStart;

            $sql = <<<SQL
                SELECT
                    (SELECT COUNT(*) FROM expedientes WHERE fecha_ingreso >= ? AND fecha_ingreso < ?) AS expedientes_actual,
                    (SELECT COUNT(*) FROM expedientes WHERE fecha_ingreso >= ? AND fecha_ingreso < ?) AS expedientes_prev,
                    (SELECT COUNT(*) FROM actuaciones WHERE fecha_firma >= ? AND fecha_firma < ?) AS actuaciones_actual,
                    (SELECT COUNT(*) FROM actuaciones WHERE fecha_firma >= ? AND fecha_firma < ?) AS actuaciones_prev,
                    (SELECT COUNT(*) FROM escritos WHERE fecha_hora_agregado >= ? AND fecha_hora_agregado < ?) AS escritos_actual,
                    (SELECT COUNT(*) FROM escritos WHERE fecha_hora_agregado >= ? AND fecha_hora_agregado < ?) AS escritos_prev,
                    (SELECT COUNT(*) FROM notificaciones WHERE fecha_enviado >= ? AND fecha_enviado < ?) AS notificaciones_actual,
                    (SELECT COUNT(*) FROM notificaciones WHERE fecha_enviado >= ? AND fecha_enviado < ?) AS notificaciones_prev
            SQL;

            $params = [
                $currentStart, $currentEnd, // expedientes_actual
                $prevStart,    $prevEnd,    // expedientes_prev
                $currentStart, $currentEnd, // actuaciones_actual (usa fecha_firma)
                $prevStart,    $prevEnd,    // actuaciones_prev
                $currentStart, $currentEnd, // escritos_actual
                $prevStart,    $prevEnd,    // escritos_prev
                $currentStart, $currentEnd, // notificaciones_actual
                $prevStart,    $prevEnd,    // notificaciones_prev
            ];

            $row = DB::connection($this->connection)->select($sql, $params)[0] ?? null;

            return $this->formatResumenCards($row);
        });
    }

    private function formatResumenCards(?object $row): array
    {
        if (!$row) {
            return $this->demoResumenCards(now()->month, now()->year);
        }

        return [
            [
                'titulo'    => 'Expedientes Ingresados',
                'valor'     => (int) $row->expedientes_actual,
                'anterior'  => (int) $row->expedientes_prev,
                'icono'     => 'fas fa-folder-open',
                'color'     => 'primary',
            ],
            [
                'titulo'    => 'Actuaciones Firmadas',
                'valor'     => (int) $row->actuaciones_actual,
                'anterior'  => (int) $row->actuaciones_prev,
                'icono'     => 'fas fa-pen-nib',
                'color'     => 'success',
            ],
            [
                'titulo'    => 'Escritos Ingresados',
                'valor'     => (int) $row->escritos_actual,
                'anterior'  => (int) $row->escritos_prev,
                'icono'     => 'fas fa-file-alt',
                'color'     => 'warning',
            ],
            [
                'titulo'    => 'Notificaciones Enviadas',
                'valor'     => (int) $row->notificaciones_actual,
                'anterior'  => (int) $row->notificaciones_prev,
                'icono'     => 'fas fa-bell',
                'color'     => 'info',
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // WIDGET 2 — Expedientes por Organismo
    // ──────────────────────────────────────────────────────────────────────────

    public function getExpedientesPorOrganismo(int $mes, int $anio): array
    {
        $key = "dashboard.organismos.{$mes}.{$anio}";

        return Cache::remember($key, $this->cacheTtl, function () use ($mes, $anio) {
            if ($this->demo) {
                return $this->demoExpedientesPorOrganismo();
            }

            $mesPrev  = $mes === 1 ? 12 : $mes - 1;
            $anioPrev = $mes === 1 ? $anio - 1 : $anio;

            $sql = <<<SQL
                SELECT
                    o.descripcion AS organismo,
                    SUM(CASE WHEN MONTH(e.fecha_ingreso)=? AND YEAR(e.fecha_ingreso)=? THEN 1 ELSE 0 END) AS actual,
                    SUM(CASE WHEN MONTH(e.fecha_ingreso)=? AND YEAR(e.fecha_ingreso)=? THEN 1 ELSE 0 END) AS anterior
                FROM expedientes e
                INNER JOIN organismos o ON o.id = e.organismo_id
                WHERE (
                    (MONTH(e.fecha_ingreso)=? AND YEAR(e.fecha_ingreso)=?)
                    OR
                    (MONTH(e.fecha_ingreso)=? AND YEAR(e.fecha_ingreso)=?)
                )
                GROUP BY o.descripcion
                ORDER BY actual DESC
                LIMIT 10
            SQL;

            $rows = DB::connection($this->connection)->select($sql, [
                $mes, $anio, $mesPrev, $anioPrev,
                $mes, $anio, $mesPrev, $anioPrev,
            ]);

            return [
                'organismos' => array_column($rows, 'organismo'),
                'actual'     => array_column($rows, 'actual'),
                'anterior'   => array_column($rows, 'anterior'),
            ];
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // WIDGET 3 — Actuaciones por Tipo (Donut)
    // ──────────────────────────────────────────────────────────────────────────

    public function getActuacionesPorTipo(string $fechaDesde, string $fechaHasta): array
    {
        $key = "dashboard.actuaciones.{$fechaDesde}.{$fechaHasta}";

        return Cache::remember($key, $this->cacheTtl, function () use ($fechaDesde, $fechaHasta) {
            if ($this->demo) {
                return $this->demoActuacionesPorTipo();
            }

            // Tabla real: actuaciones_tipos (no tipos_actuacion)
            // Campo real en actuaciones: fecha_firma (datetime) — se usa rango índice-amigable
            $sql = <<<SQL
                SELECT
                    at2.descripcion AS tipo,
                    COUNT(*) AS cantidad
                FROM actuaciones a
                INNER JOIN actuaciones_tipos at2 ON at2.id = a.tipo_actuacion_id
                WHERE a.fecha_firma IS NOT NULL
                  AND a.fecha_firma >= ?
                  AND a.fecha_firma < DATE_ADD(?, INTERVAL 1 DAY)
                GROUP BY at2.descripcion
                ORDER BY cantidad DESC
            SQL;

            $rows = DB::connection($this->connection)->select($sql, [$fechaDesde, $fechaHasta]);

            return [
                'tipos'      => array_column($rows, 'tipo'),
                'cantidades' => array_map('intval', array_column($rows, 'cantidad')),
            ];
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // WIDGET 4 — Evolución de Escritos (últimos N meses)
    // ──────────────────────────────────────────────────────────────────────────

    public function getEscritos(int $meses = 12): array
    {
        $key = "dashboard.escritos.{$meses}";

        return Cache::remember($key, $this->cacheTtl, function () use ($meses) {
            if ($this->demo) {
                return $this->demoEscritos($meses);
            }

            // escritos NO tiene tipo_escrito_id ni tabla tipos_escrito.
            // Se agrupa por estado (estados_escritos) y campo real fecha_hora_agregado.
            $sql = <<<SQL
                SELECT
                    DATE_FORMAT(e.fecha_hora_agregado, '%Y-%m') AS periodo,
                    ee.descripcion AS tipo,
                    COUNT(*) AS cantidad
                FROM escritos e
                INNER JOIN estados_escritos ee ON ee.id = e.estado_escrito_id
                WHERE e.fecha_hora_agregado IS NOT NULL
                  AND e.fecha_hora_agregado >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                  AND e.deleted_at IS NULL
                GROUP BY periodo, ee.descripcion
                ORDER BY periodo ASC, cantidad DESC
            SQL;

            $rows = DB::connection($this->connection)->select($sql, [$meses]);

            // Transformar a series para ApexCharts
            $periodos = [];
            $series   = [];

            foreach ($rows as $row) {
                $periodos[$row->periodo] = true;
                $series[$row->tipo][$row->periodo] = (int) $row->cantidad;
            }

            $labels = array_keys($periodos);
            $seriesData = [];
            foreach ($series as $tipo => $valores) {
                $data = [];
                foreach ($labels as $label) {
                    $data[] = $valores[$label] ?? 0;
                }
                $seriesData[] = ['name' => $tipo, 'data' => $data];
            }

            return ['labels' => $labels, 'series' => $seriesData];
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // WIDGET 5 — Notificaciones con sparkline
    // ──────────────────────────────────────────────────────────────────────────

    public function getNotificaciones(int $mes, int $anio): array
    {
        $key = "dashboard.notificaciones.{$mes}.{$anio}";

        return Cache::remember($key, $this->cacheTtl, function () use ($mes, $anio) {
            if ($this->demo) {
                return $this->demoNotificaciones();
            }

            // notificaciones NO tiene tipo_notificacion_id ni tabla tipos_notificacion.
            // Se agrupa por estado (notificaciones_estados). Campo real: fecha_enviado (datetime).

            // Total del mes para calcular porcentajes
            $totalSql = <<<SQL
                SELECT COUNT(*) AS total FROM notificaciones
                WHERE MONTH(fecha_enviado)=? AND YEAR(fecha_enviado)=?
                  AND deleted_at IS NULL
            SQL;
            $total = DB::connection($this->connection)->select($totalSql, [$mes, $anio])[0]->total ?? 0;

            // Agrupado por estado
            $sql = <<<SQL
                SELECT
                    ne.descripcion AS tipo,
                    COUNT(*) AS cantidad
                FROM notificaciones n
                INNER JOIN notificaciones_estados ne ON ne.id = n.estado_id
                WHERE MONTH(n.fecha_enviado)=? AND YEAR(n.fecha_enviado)=?
                  AND n.deleted_at IS NULL
                GROUP BY ne.descripcion
                ORDER BY cantidad DESC
            SQL;

            $rows = DB::connection($this->connection)->select($sql, [$mes, $anio]);

            // Sparkline: últimos 6 meses por estado
            $sparkSql = <<<SQL
                SELECT
                    ne.descripcion AS tipo,
                    DATE_FORMAT(n.fecha_enviado, '%Y-%m') AS periodo,
                    COUNT(*) AS cantidad
                FROM notificaciones n
                INNER JOIN notificaciones_estados ne ON ne.id = n.estado_id
                WHERE n.fecha_enviado >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  AND n.deleted_at IS NULL
                GROUP BY ne.descripcion, periodo
                ORDER BY ne.descripcion, periodo
            SQL;

            $sparkRows = DB::connection($this->connection)->select($sparkSql);
            $sparklines = [];
            foreach ($sparkRows as $s) {
                $sparklines[$s->tipo][] = (int) $s->cantidad;
            }

            $result = [];
            foreach ($rows as $row) {
                $result[] = [
                    'tipo'      => $row->tipo,
                    'cantidad'  => (int) $row->cantidad,
                    'porcentaje'=> $total > 0 ? round(($row->cantidad / $total) * 100, 1) : 0,
                    'sparkline' => $sparklines[$row->tipo] ?? [0, 0, 0, 0, 0, 0],
                ];
            }

            return $result;
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // WIDGET 6 — Actividad reciente
    // ──────────────────────────────────────────────────────────────────────────

    public function getActividadReciente(int $limite = 10): array
    {
        $key = "dashboard.actividad.{$limite}";

        return Cache::remember($key, $this->cacheTtl, function () use ($limite) {
            if ($this->demo) {
                return $this->demoActividadReciente($limite);
            }

            $sql = <<<SQL
                SELECT
                    'expediente' AS tipo,
                    CONCAT('Expediente #', e.nro, ' ingresado') AS descripcion,
                    e.fecha_ingreso AS fecha
                FROM expedientes e
                UNION ALL
                SELECT
                    'actuacion' AS tipo,
                    CONCAT('Actuación firmada: ', COALESCE(at2.descripcion, CAST(a.tipo_actuacion_id AS CHAR))) AS descripcion,
                    a.fecha_firma AS fecha
                FROM actuaciones a
                LEFT JOIN actuaciones_tipos at2 ON at2.id = a.tipo_actuacion_id
                WHERE a.fecha_firma IS NOT NULL
                  AND a.deleted_at IS NULL
                UNION ALL
                SELECT
                    'escrito' AS tipo,
                    CONCAT('Escrito ingresado: ', es.descripcion) AS descripcion,
                    es.fecha_hora_agregado AS fecha
                FROM escritos es
                ORDER BY fecha DESC
                LIMIT ?
            SQL;

            return DB::connection($this->connection)->select($sql, [$limite]);
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  DATOS DEMO (modo sin BD)
    // ══════════════════════════════════════════════════════════════════════════

    private function demoResumenCards(int $mes, int $anio): array
    {
        return [
            [
                'titulo'   => 'Expedientes Ingresados',
                'valor'    => 1_284,
                'anterior' => 1_150,
                'icono'    => 'fas fa-folder-open',
                'color'    => 'primary',
            ],
            [
                'titulo'   => 'Actuaciones Firmadas',
                'valor'    => 3_472,
                'anterior' => 3_800,
                'icono'    => 'fas fa-pen-nib',
                'color'    => 'success',
            ],
            [
                'titulo'   => 'Escritos Ingresados',
                'valor'    => 876,
                'anterior' => 790,
                'icono'    => 'fas fa-file-alt',
                'color'    => 'warning',
            ],
            [
                'titulo'   => 'Notificaciones Enviadas',
                'valor'    => 2_105,
                'anterior' => 1_980,
                'icono'    => 'fas fa-bell',
                'color'    => 'info',
            ],
        ];
    }

    private function demoExpedientesPorOrganismo(): array
    {
        $organismos = [
            'Dir. Gral. de Rentas',
            'Secretaría de Hacienda',
            'Min. de Salud Pública',
            'Dir. de Catastro',
            'Sec. de Obras Públicas',
            'Min. de Educación',
            'Dir. de Registro Civil',
            'Sec. de Medio Ambiente',
            'Min. de Trabajo',
            'Dir. de Vialidad',
        ];
        $actual   = [342, 298, 275, 241, 218, 196, 173, 154, 138, 112];
        $anterior = [310, 315, 250, 228, 235, 182, 196, 143, 120, 98];

        return compact('organismos', 'actual', 'anterior');
    }

    private function demoActuacionesPorTipo(): array
    {
        return [
            'tipos'      => ['Resolución', 'Disposición', 'Nota', 'Dictamen', 'Providencia', 'Decreto'],
            'cantidades' => [1240, 876, 654, 432, 189, 81],
        ];
    }

    private function demoEscritos(int $meses): array
    {
        $now    = now();
        $labels = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $labels[] = $now->copy()->subMonths($i)->format('Y-m');
        }

        $series = [
            ['name' => 'Presentaciones', 'data' => [120, 145, 132, 178, 162, 189, 201, 175, 194, 210, 228, 243]],
            ['name' => 'Recursos',       'data' => [45,  52,  48,  61,  58,  67,  73,  69,  75,  81,  78,  85]],
            ['name' => 'Notas',          'data' => [30,  28,  35,  42,  38,  29,  44,  51,  47,  53,  49,  58]],
        ];

        // Ajustar al número de meses requerido
        foreach ($series as &$s) {
            $s['data'] = array_slice($s['data'], -$meses);
        }

        return ['labels' => $labels, 'series' => $series];
    }

    private function demoNotificaciones(): array
    {
        return [
            ['tipo' => 'Cédula de Notificación', 'cantidad' => 843,  'porcentaje' => 40.0, 'sparkline' => [120, 134, 98, 145, 152, 143]],
            ['tipo' => 'Carta Documento',         'cantidad' => 521,  'porcentaje' => 24.8, 'sparkline' => [78,  85,  72, 91,  88,  95]],
            ['tipo' => 'Telegrama',               'cantidad' => 312,  'porcentaje' => 14.8, 'sparkline' => [45,  52,  48, 55,  51,  58]],
            ['tipo' => 'E-mail Oficial',          'cantidad' => 287,  'porcentaje' => 13.6, 'sparkline' => [40,  44,  38, 47,  49,  53]],
            ['tipo' => 'Edicto',                  'cantidad' => 142,  'porcentaje' => 6.8,  'sparkline' => [20,  24,  19, 26,  22,  28]],
        ];
    }

    private function demoActividadReciente(int $limite): array
    {
        $items = [
            (object)['tipo' => 'expediente',  'descripcion' => 'Expediente #2847-I/25 ingresado — Municipalidad de Cosquín',          'fecha' => now()->subMinutes(5)],
            (object)['tipo' => 'actuacion',   'descripcion' => 'Resolución N° 145/25 firmada por el Sr. Director',                    'fecha' => now()->subMinutes(12)],
            (object)['tipo' => 'escrito',     'descripcion' => 'Presentación ingresada en Expediente #2830-A/25',                     'fecha' => now()->subMinutes(28)],
            (object)['tipo' => 'notificacion','descripcion' => 'Cédula de notificación enviada — Dr. García, Juan Carlos',           'fecha' => now()->subMinutes(45)],
            (object)['tipo' => 'expediente',  'descripcion' => 'Expediente #2846-H/25 pase a instrucción',                           'fecha' => now()->subHours(1)],
            (object)['tipo' => 'actuacion',   'descripcion' => 'Dictamen N° 089/25 emitido por Asesoría Legal',                      'fecha' => now()->subHours(2)],
            (object)['tipo' => 'escrito',     'descripcion' => 'Recurso de apelación ingresado — Exp. #2801-G/25',                   'fecha' => now()->subHours(3)],
            (object)['tipo' => 'notificacion','descripcion' => 'Carta documento enviada — RAMIREZ S.A.',                             'fecha' => now()->subHours(4)],
            (object)['tipo' => 'expediente',  'descripcion' => 'Expediente #2845-B/25 archivado',                                    'fecha' => now()->subHours(5)],
            (object)['tipo' => 'actuacion',   'descripcion' => 'Disposición N° 412/25 firmada — Depto. de Urbanismo',               'fecha' => now()->subHours(6)],
        ];

        return array_slice($items, 0, $limite);
    }
}
