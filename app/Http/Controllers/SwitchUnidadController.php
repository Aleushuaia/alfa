<?php

namespace App\Http\Controllers;

use App\Services\UnidadActivaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SwitchUnidadController extends Controller
{
    public function __invoke(Request $request, UnidadActivaService $service)
    {
        $data = $request->validate([
            'unidad_id' => ['required', 'integer'],
        ]);

        $ok = $service->set(Auth::user(), (int) $data['unidad_id']);

        if (!$ok) {
            return back()->with('error', 'No tenés permiso para acceder a esa unidad.');
        }

        return back()->with('success', 'Unidad de trabajo cambiada correctamente.');
    }
}
