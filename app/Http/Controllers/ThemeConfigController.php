<?php

namespace App\Http\Controllers;

use App\Models\UserThemeColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeConfigController extends Controller
{
    /** Default color values per theme */
    private const DEFAULTS = [
        'light' => [
            'accent'     => '#3b82f6',
            'accent2'    => '#60a5fa',
            'body_bg'    => '#f0f2f7',
            'card_bg'    => '#ffffff',
            'sidebar_bg' => '#0d1117',
            'topbar_bg'  => '#ffffff',
        ],
        'dark' => [
            'accent'     => '#3b82f6',
            'accent2'    => '#60a5fa',
            'body_bg'    => '#0b0f19',
            'card_bg'    => '#111827',
            'sidebar_bg' => '#0d1117',
            'topbar_bg'  => '#0d1117',
        ],
    ];

    /** Preset palettes */
    private const PRESETS = [
        'azul'    => ['accent' => '#3b82f6', 'accent2' => '#60a5fa', 'label' => 'Azul'],
        'violeta' => ['accent' => '#6366f1', 'accent2' => '#8b5cf6', 'label' => 'Violeta'],
        'verde'   => ['accent' => '#10b981', 'accent2' => '#34d399', 'label' => 'Verde'],
        'teal'    => ['accent' => '#14b8a6', 'accent2' => '#2dd4bf', 'label' => 'Teal'],
        'rojo'    => ['accent' => '#ef4444', 'accent2' => '#f87171', 'label' => 'Rojo'],
        'ambar'   => ['accent' => '#f59e0b', 'accent2' => '#fbbf24', 'label' => 'Ámbar'],
        'rosa'    => ['accent' => '#ec4899', 'accent2' => '#f472b6', 'label' => 'Rosa'],
        'cyan'    => ['accent' => '#06b6d4', 'accent2' => '#22d3ee', 'label' => 'Cyan'],
    ];

    public function index()
    {
        $userId = Auth::id();

        // Load saved values (keyed by theme_mode.color_key)
        $saved = UserThemeColor::where('user_id', $userId)
            ->get()
            ->groupBy('theme_mode')
            ->map(fn($group) => $group->pluck('color_value', 'color_key')->toArray())
            ->toArray();

        // Merge with defaults
        $colors = [];
        foreach (['light', 'dark'] as $mode) {
            foreach (self::DEFAULTS[$mode] as $key => $default) {
                $colors[$mode][$key] = $saved[$mode][$key] ?? $default;
            }
        }

        return view('theme-config.index', [
            'colors'   => $colors,
            'defaults' => self::DEFAULTS,
            'presets'  => self::PRESETS,
        ]);
    }

    public function save(Request $request)
    {
        $userId = Auth::id();
        $data   = $request->validate([
            'colors'              => 'required|array',
            'colors.light'        => 'required|array',
            'colors.dark'         => 'required|array',
            'colors.*.accent'     => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'colors.*.accent2'    => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'colors.*.body_bg'    => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'colors.*.card_bg'    => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'colors.*.sidebar_bg' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'colors.*.topbar_bg'  => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        foreach (['light', 'dark'] as $mode) {
            foreach ($data['colors'][$mode] as $key => $value) {
                UserThemeColor::updateOrCreate(
                    ['user_id' => $userId, 'theme_mode' => $mode, 'color_key' => $key],
                    ['color_value' => $value]
                );
            }
        }

        return redirect()->route('theme-config.index')
            ->with('success', 'Colores del tema guardados correctamente.');
    }

    public function reset()
    {
        UserThemeColor::where('user_id', Auth::id())->delete();

        return redirect()->route('theme-config.index')
            ->with('success', 'Colores restaurados a los valores predeterminados.');
    }

    /**
     * API endpoint for the layout to load user theme colors (called via AJAX).
     */
    public function getUserColors()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['light' => self::DEFAULTS['light'], 'dark' => self::DEFAULTS['dark']]);
        }

        $saved = UserThemeColor::where('user_id', $userId)
            ->get()
            ->groupBy('theme_mode')
            ->map(fn($group) => $group->pluck('color_value', 'color_key')->toArray())
            ->toArray();

        $colors = [];
        foreach (['light', 'dark'] as $mode) {
            foreach (self::DEFAULTS[$mode] as $key => $default) {
                $colors[$mode][$key] = $saved[$mode][$key] ?? $default;
            }
        }

        return response()->json($colors);
    }
}
