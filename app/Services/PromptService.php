<?php

namespace App\Services;

use App\Models\Prompt;
use Illuminate\Database\Eloquent\Collection;

class PromptService
{
    /**
     * Retorna todos los prompts ordenados por descripcion.
     */
    public function all(): Collection
    {
        return Prompt::orderBy('descripcion')->get();
    }

    /**
     * Busca un prompt por su ID y construye el contenido final
     * reemplazando el placeholder {{texto}} con el texto provisto.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function buildPrompt(string $promptId, string $texto): string
    {
        $prompt = Prompt::findOrFail($promptId);

        return str_replace('{{texto}}', $texto, $prompt->contenido);
    }
}
