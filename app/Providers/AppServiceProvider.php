<?php

namespace App\Providers;

use App\Models\UserThemeColor;
use App\Repositories\DashboardRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el DashboardRepository como singleton
        $this->app->singleton(DashboardRepository::class, function ($app) {
            return new DashboardRepository();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzar HTTPS en producción
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Share user theme colors with all dashboard views
        View::composer('layouts.dashboard', function ($view) {
            $userThemeCSS = '';
            if (Auth::check()) {
                $defaults = [
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

                try {
                    $saved = UserThemeColor::where('user_id', Auth::id())
                        ->get()
                        ->groupBy('theme_mode')
                        ->map(fn($g) => $g->pluck('color_value', 'color_key')->toArray())
                        ->toArray();
                } catch (\Exception $e) {
                    $saved = [];
                }

                // Only generate CSS if user has custom colors
                if (!empty($saved)) {
                    $cssMap = [
                        'accent'     => '--accent',
                        'accent2'    => '--accent2',
                        'body_bg'    => '--body-bg',
                        'card_bg'    => '--card-bg',
                        'sidebar_bg' => '--sidebar-bg',
                        'topbar_bg'  => '--topbar-bg',
                    ];

                    foreach (['light', 'dark'] as $mode) {
                        $rules = [];
                        foreach ($cssMap as $key => $var) {
                            $val = $saved[$mode][$key] ?? null;
                            if ($val && $val !== $defaults[$mode][$key]) {
                                $rules[] = "{$var}:{$val}";
                            }
                        }
                        // Derived variables for accent
                        $accent = $saved[$mode]['accent'] ?? null;
                        if ($accent && $accent !== $defaults[$mode]['accent']) {
                            $r = hexdec(substr($accent, 1, 2));
                            $g = hexdec(substr($accent, 3, 2));
                            $b = hexdec(substr($accent, 5, 2));
                            $rules[] = "--nav-active-bg:rgba({$r},{$g},{$b},.18)";
                            $rules[] = "--nav-active-border:{$accent}";
                            if ($mode === 'dark') {
                                $accent2 = $saved[$mode]['accent2'] ?? $defaults[$mode]['accent2'];
                                $rules[] = "--link-color:{$accent2}";
                            } else {
                                $rules[] = "--link-color:{$accent}";
                            }
                        }

                        if (!empty($rules)) {
                            $selector = $mode === 'dark'
                                ? 'html[data-theme="dark"]'
                                : 'html[data-theme="light"],html:not([data-theme])';
                            $userThemeCSS .= "{$selector}{" . implode(';', $rules) . "}\n";
                        }
                    }
                }
            }
            $view->with('userThemeCSS', $userThemeCSS);
        });
    }
}
