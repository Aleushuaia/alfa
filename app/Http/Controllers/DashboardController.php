<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardRepository $repo
    ) {}

    /**
     * GET /dashboard
     */
    public function index(Request $request): View
    {
        $mes  = (int) $request->get('mes',  now()->month);
        $anio = (int) $request->get('anio', now()->year);
        $demo = filter_var(env('DASHBOARD_DEMO', true), FILTER_VALIDATE_BOOLEAN);

        // Widget 1 — Cards
        $cards = $this->repo->getResumenCards($mes, $anio);

        // Valor específico: Expedientes ingresados (primer card)
        $expedientesIngresados = 2;

        // Widget 2 — Expedientes por organismo
        $expedientesPorOrganismo = $this->repo->getExpedientesPorOrganismo($mes, $anio);

        // Widget 3 — Actuaciones por tipo (filtreable)
        $fechaDesde = $request->get('desde', now()->startOfMonth()->toDateString());
        $fechaHasta = $request->get('hasta', now()->toDateString());
        $actuacionesPorTipo = $this->repo->getActuacionesPorTipo($fechaDesde, $fechaHasta);

        // Widget 4 — Escritos últimos 12 meses
        $escritos = $this->repo->getEscritos(12);

        // Widget 5 — Notificaciones
        $notificaciones = $this->repo->getNotificaciones($mes, $anio);

        // Widget 6 — Actividad reciente
        $actividadReciente = $this->repo->getActividadReciente(10);

        return view('dashboard.index', compact(
            'cards',
            'expedientesIngresados',
            'expedientesPorOrganismo',
            'actuacionesPorTipo',
            'escritos',
            'notificaciones',
            'actividadReciente',
            'mes',
            'anio',
            'fechaDesde',
            'fechaHasta',
            'demo',
        ));
    }

    /**
     * AJAX — Re-filtrar actuaciones por rango de fechas
     * GET /dashboard/actuaciones?desde=&hasta=
     */
    public function actuaciones(Request $request)
    {
        $request->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ]);

        $data = $this->repo->getActuacionesPorTipo(
            $request->desde,
            $request->hasta
        );

        return response()->json($data);
    }


    public function ingresadosFuero(Request $request)
    {
        $organismos = [
            ['id' => 1, 'nombre' => 'Dir. Gral. de Rentas',
                'total' => Expediente::where('organismo_id', 1)->count(),
                'pendientes' => Expediente::where('organismo_id', 1)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 1)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 1)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 2, 'nombre' => 'Secretaría de Hacienda',
                'total' => Expediente::where('organismo_id', 2)->count(),
                'pendientes' => Expediente::where('organismo_id', 2)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 2)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 2)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 3, 'nombre' => 'Min. de Salud Pública',
                'total' => Expediente::where('organismo_id', 3)->count(),
                'pendientes' => Expediente::where('organismo_id', 3)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 3)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 3)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 4, 'nombre' => 'Dir. de Catastro',
                'total' => Expediente::where('organismo_id', 4)->count(),
                'pendientes' => Expediente::where('organismo_id', 4)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 4)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 4)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
   
        ];

        return view('dashboard.ingresados_fuero', compact('organismos'));
    }

    /* ═══════════════════════════════════
     *  V2 — Nuevo diseño
     * ═══════════════════════════════════ */

    /**
     * GET /v2/dashboard
     */
    public function indexV2(Request $request): View
    {
        $mes  = (int) $request->get('mes',  now()->month);
        $anio = (int) $request->get('anio', now()->year);
        $demo = filter_var(env('DASHBOARD_DEMO', true), FILTER_VALIDATE_BOOLEAN);

        $cards                   = $this->repo->getResumenCards($mes, $anio);
        $expedientesIngresados   = 2;
        $expedientesPorOrganismo = $this->repo->getExpedientesPorOrganismo($mes, $anio);
        $fechaDesde              = $request->get('desde', now()->startOfMonth()->toDateString());
        $fechaHasta              = $request->get('hasta', now()->toDateString());
        $actuacionesPorTipo      = $this->repo->getActuacionesPorTipo($fechaDesde, $fechaHasta);
        $escritos                = $this->repo->getEscritos(12);
        $notificaciones          = $this->repo->getNotificaciones($mes, $anio);
        $actividadReciente       = $this->repo->getActividadReciente(10);

        return view('dashboard.index2', compact(
            'cards',
            'expedientesIngresados',
            'expedientesPorOrganismo',
            'actuacionesPorTipo',
            'escritos',
            'notificaciones',
            'actividadReciente',
            'mes',
            'anio',
            'fechaDesde',
            'fechaHasta',
            'demo',
        ));
    }

    /**
     * GET /v2/ingresados_fuero
     */
    public function ingresadosFueroV2(Request $request)
    {
        $organismos = [
            ['id' => 1, 'nombre' => 'Dir. Gral. de Rentas',
                'total' => Expediente::where('organismo_id', 1)->count(),
                'pendientes' => Expediente::where('organismo_id', 1)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 1)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 1)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 2, 'nombre' => 'Secretaría de Hacienda',
                'total' => Expediente::where('organismo_id', 2)->count(),
                'pendientes' => Expediente::where('organismo_id', 2)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 2)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 2)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 3, 'nombre' => 'Min. de Salud Pública',
                'total' => Expediente::where('organismo_id', 3)->count(),
                'pendientes' => Expediente::where('organismo_id', 3)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 3)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 3)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 4, 'nombre' => 'Dir. de Catastro',
                'total' => Expediente::where('organismo_id', 4)->count(),
                'pendientes' => Expediente::where('organismo_id', 4)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 4)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 4)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 5, 'nombre' => 'Min. de Educación',
                'total' => Expediente::where('organismo_id', 5)->count(),
                'pendientes' => Expediente::where('organismo_id', 5)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 5)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 5)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 6, 'nombre' => 'Min. de Seguridad',
                'total' => Expediente::where('organismo_id', 6)->count(),
                'pendientes' => Expediente::where('organismo_id', 6)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 6)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 6)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 7, 'nombre' => 'Municipalidad de Ushuaia',
                'total' => Expediente::where('organismo_id', 7)->count(),
                'pendientes' => Expediente::where('organismo_id', 7)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 7)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 7)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
            ['id' => 8, 'nombre' => 'Municipalidad de Río Grande',
                'total' => Expediente::where('organismo_id', 8)->count(),
                'pendientes' => Expediente::where('organismo_id', 8)->where('estado_id', 0)->count(),
                'resueltos' => Expediente::where('organismo_id', 8)->where('estado_id', '<>', 0)->count(),
                'mes' => Expediente::where('organismo_id', 8)->whereMonth('fecha_ingreso', now()->month)->count(),
            ],
        ];

        return view('dashboard.ingresados_fuero2', compact('organismos'));
    }

}
