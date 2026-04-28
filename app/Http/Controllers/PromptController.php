<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromptController extends Controller
{
    /**
     * Lista todos los prompts.
     */
    public function index(): View
    {
        $prompts = Prompt::orderBy('descripcion')->get();

        return view('admin.prompts.index', compact('prompts'));
    }

    /**
     * Guarda un nuevo prompt.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'descripcion' => ['required', 'string', 'max:150', 'unique:prompts,descripcion'],
            'contenido'   => ['required', 'string'],
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.unique'   => 'Ya existe un prompt con esa descripción.',
            'descripcion.max'      => 'La descripción no puede superar los 150 caracteres.',
            'contenido.required'   => 'El contenido es obligatorio.',
        ]);

        Prompt::create($validated);

        return redirect()->route('admin.prompts.index')
            ->with('success', 'Prompt creado correctamente.');
    }

    /**
     * Actualiza un prompt existente.
     */
    public function update(Request $request, Prompt $prompt): RedirectResponse
    {
        $validated = $request->validate([
            'descripcion' => ['required', 'string', 'max:150', 'unique:prompts,descripcion,' . $prompt->id . ',id'],
            'contenido'   => ['required', 'string'],
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.unique'   => 'Ya existe un prompt con esa descripción.',
            'descripcion.max'      => 'La descripción no puede superar los 150 caracteres.',
            'contenido.required'   => 'El contenido es obligatorio.',
        ]);

        $prompt->update($validated);

        return redirect()->route('admin.prompts.index')
            ->with('success', 'Prompt actualizado correctamente.');
    }

    /**
     * Elimina un prompt.
     */
    public function destroy(Prompt $prompt): RedirectResponse
    {
        $prompt->delete();

        return redirect()->route('admin.prompts.index')
            ->with('success', 'Prompt eliminado correctamente.');
    }
}
