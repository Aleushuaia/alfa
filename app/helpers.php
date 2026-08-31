<?php

use Illuminate\Support\Facades\App;

if (! function_exists('alfa_asset')) {
    /**
     * Devuelve la URL de un asset público adaptada al entorno.
     *
     * - En producción y beta se usa secure_asset() para forzar HTTPS.
     * - En local (y cualquier otro entorno) se usa asset(), porque el
     *   entorno de desarrollo no tiene certificado SSL instalado y
     *   secure_asset() generaría URLs https:// que el navegador no
     *   puede cargar.
     */
    function alfa_asset(string $path): string
    {
        return App::environment(['production', 'beta'])
            ? secure_asset($path)
            : asset($path);
    }
}
