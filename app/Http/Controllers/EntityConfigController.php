<?php

namespace App\Http\Controllers;

use App\Models\UserEntityColor;
use Illuminate\Http\Request;

class EntityConfigController extends Controller
{
    /**
     * Default entity types with their display names and default colors.
     */
    private const ENTITY_TYPES = [
        'PER'     => ['label' => 'Persona',       'default' => '#ffcccc'],
        'ORG'     => ['label' => 'Organización',   'default' => '#cce5ff'],
        'LOC'     => ['label' => 'Lugar',          'default' => '#ccffcc'],
        'DATE'    => ['label' => 'Fecha',          'default' => '#ffe0b3'],
        'DNI'     => ['label' => 'DNI',            'default' => '#e0e0e0'],
        'EMAIL'   => ['label' => 'Email',          'default' => '#ccf2ff'],
        'PHONE'   => ['label' => 'Teléfono',       'default' => '#ffffcc'],
        'PATENTE' => ['label' => 'Patente',        'default' => '#f1c0e8'],
        'CUIT'    => ['label' => 'CUIT',           'default' => '#d0f4de'],
        'MISC'    => ['label' => 'Otros',          'default' => '#e0ccff'],
    ];

    public function index()
    {
        $userId = auth()->id();

        $savedColors = UserEntityColor::where('user_id', $userId)
            ->pluck('color', 'entity_type')
            ->toArray();

        $entityTypes = [];
        foreach (self::ENTITY_TYPES as $type => $info) {
            $entityTypes[] = [
                'type'    => $type,
                'label'   => $info['label'],
                'default' => $info['default'],
                'color'   => $savedColors[$type] ?? $info['default'],
            ];
        }

        return view('entity-config.index', compact('entityTypes'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'colors'   => ['required', 'array'],
            'colors.*' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $userId = auth()->id();
        $colors = $request->input('colors');

        foreach ($colors as $entityType => $color) {
            if (!array_key_exists($entityType, self::ENTITY_TYPES)) {
                continue;
            }

            UserEntityColor::updateOrCreate(
                ['user_id' => $userId, 'entity_type' => $entityType],
                ['color' => $color]
            );
        }

        return redirect()->route('entity-config.index')
            ->with('success', 'Colores de entidades guardados correctamente.');
    }

    /**
     * Get colors map for the current user (used by other controllers).
     */
    public static function getUserColors(int $userId): array
    {
        $saved = UserEntityColor::where('user_id', $userId)
            ->pluck('color', 'entity_type')
            ->toArray();

        $colors = [];
        foreach (self::ENTITY_TYPES as $type => $info) {
            $colors[$type] = $saved[$type] ?? $info['default'];
        }

        return $colors;
    }

    /**
     * Get entity types definition (for views that need the full list).
     */
    public static function getEntityTypes(): array
    {
        return self::ENTITY_TYPES;
    }
}
