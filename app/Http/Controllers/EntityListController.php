<?php

namespace App\Http\Controllers;

use App\Models\EntityBlacklist;
use App\Models\EntityWhitelist;
use App\Services\UnidadActivaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * EntityListController
 * ----------------------------------------------------------------------------
 * CRUD de la Blacklist / Whitelist de entidades:
 *   - Páginas de gestión del menú (blacklist.index / whitelist.index + delete).
 *   - Endpoints AJAX que consume el Anonimizador (blacklist.add / .add-bulk /
 *     whitelist.add) para ignorar o reconocer entidades desde el análisis.
 *
 * El filtrado real de la blacklist/whitelist durante el análisis vive en
 * WordAnonymizerController.
 */
class EntityListController extends Controller
{
    // ═════════════════════════════════════════════════════════════════════════
    //  BLACKLIST
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Agrega un término a entity_blacklist (AJAX, un término por request).
     * Body JSON: { term: string, entity_type: string|null }
     */
    public function addToBlacklist(Request $request)
    {
        $request->validate([
            'term'        => ['required', 'string', 'max:500'],
            'entity_type' => ['nullable', 'string', 'max:30'],
        ]);

        $term       = trim($request->input('term'));
        $entityType = $request->input('entity_type') ?: null;
        $unidadId   = $this->activeUnidadId();
        $addedBy    = Auth::check() ? Auth::user()->name : 'usuario';

        try {
            $status = $this->upsertBlacklistTerm($term, $entityType, $unidadId, $addedBy);

            \Log::info('Blacklist: ' . $status, [
                'term' => $term, 'entity_type' => $entityType, 'unidad_id' => $unidadId,
            ]);

            return response()->json([
                'success' => true,
                'message' => $status === 'exists'
                    ? "Término \"$term\" ya existe en la lista negra."
                    : "Término \"$term\" agregado a la lista negra. No aparecerá en futuros análisis.",
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Blacklist: error de base de datos al agregar término', [
                'term' => $term, 'exception' => get_class($e), 'message' => $e->getMessage(),
                'sqlerror' => $e->getPrevious()?->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos al guardar en la lista negra. Intente nuevamente.',
            ], 500);

        } catch (\Exception $e) {
            \Log::error('Blacklist: error inesperado al agregar término', [
                'term' => $term, 'exception' => get_class($e),
                'message' => $e->getMessage(), 'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar en la lista negra. Intente nuevamente.',
            ], 500);
        }
    }

    /**
     * Agrega varios términos a la vez (barra de acciones masivas del Anonimizador).
     * Best-effort por ítem: en PostgreSQL un statement fallido aborta la
     * transacción entera, por eso NO se envuelve todo en una sola transacción.
     *
     * Body JSON: { items: [ { term: string, entity_type: string|null } ] }
     * Respuesta: { success, message, stats: {created,reactivated,exists,failed} }
     */
    public function addToBlacklistBulk(Request $request)
    {
        $request->validate([
            'items'               => ['required', 'array', 'min:1', 'max:500'],
            'items.*.term'        => ['required', 'string', 'max:500'],
            'items.*.entity_type' => ['nullable', 'string', 'max:30'],
        ]);

        $unidadId = $this->activeUnidadId();
        $addedBy  = Auth::check() ? Auth::user()->name : 'usuario';

        $stats = ['created' => 0, 'reactivated' => 0, 'exists' => 0, 'failed' => 0];
        $seen  = [];

        foreach ($request->input('items') as $item) {
            $term = trim((string) ($item['term'] ?? ''));
            if ($term === '') {
                continue;
            }
            $type = ($item['entity_type'] ?? null) ?: null;

            $dedupKey = mb_strtolower($term) . '|' . ($type ?? '');
            if (isset($seen[$dedupKey])) {
                continue;
            }
            $seen[$dedupKey] = true;

            try {
                $stats[$this->upsertBlacklistTerm($term, $type, $unidadId, $addedBy)]++;
            } catch (\Throwable $e) {
                $stats['failed']++;
                \Log::error('Blacklist bulk: fallo al agregar término', [
                    'term' => $term, 'message' => $e->getMessage(),
                ]);
            }
        }

        $ok = $stats['created'] + $stats['reactivated'] + $stats['exists'];

        return response()->json([
            'success' => $stats['failed'] === 0,
            'message' => "{$ok} término(s) en la lista negra"
                . ($stats['failed'] ? " · {$stats['failed']} con error" : '') . '.',
            'stats'   => $stats,
        ]);
    }

    /**
     * Núcleo compartido por addToBlacklist / addToBlacklistBulk.
     * Devuelve 'created' | 'reactivated' | 'exists'. El 23505 de PostgreSQL
     * (par ya existente en otra unidad) se trata como 'exists'.
     */
    private function upsertBlacklistTerm(string $term, ?string $entityType, ?int $unidadId, ?string $addedBy): string
    {
        $entry = EntityBlacklist::where('term', $term)
            ->where(function ($q) use ($entityType) {
                if ($entityType === null) {
                    $q->whereNull('entity_type');
                } else {
                    $q->where('entity_type', $entityType);
                }
            })
            ->when($unidadId, fn($q) => $q->where('unidad_id', $unidadId))
            ->when(!$unidadId, fn($q) => $q->whereNull('unidad_id'))
            ->first();

        if ($entry) {
            if (!$entry->active) {
                $entry->update(['active' => true]);
                return 'reactivated';
            }
            return 'exists';
        }

        try {
            EntityBlacklist::create([
                'term'           => $term,
                'entity_type'    => $entityType,
                'match_mode'     => 'exact',
                'case_sensitive' => false,
                'added_by'       => $addedBy ?: 'usuario',
                'reason'         => 'Ignorado manualmente desde el Anonimizador.',
                'active'         => true,
                'unidad_id'      => $unidadId,
            ]);
            return 'created';
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), '23505')) {
                return 'exists';
            }
            throw $e;
        }
    }

    /** Página de gestión de la Blacklist (menú). */
    public function blacklistIndex()
    {
        $entries = $this->listEntries(EntityBlacklist::query());

        return view('blacklist.index', [
            'entries'      => $entries,
            'entityColors' => $this->userEntityColors(),
        ]);
    }

    /** Elimina permanentemente una entrada de la blacklist por id. */
    public function blacklistDelete(int $id)
    {
        return $this->deleteEntry(EntityBlacklist::class, $id, 'blacklist');
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  WHITELIST
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Agrega un término a entity_whitelist (AJAX).
     * Body JSON: { term: string, entity_type: string|null }
     */
    public function addToWhitelist(Request $request)
    {
        $request->validate([
            'term'        => ['required', 'string', 'max:500'],
            'entity_type' => ['nullable', 'string', 'max:30'],
        ]);

        $term       = trim($request->input('term'));
        $entityType = $request->input('entity_type') ?: null;
        $unidadId   = $this->activeUnidadId();

        try {
            $entry = EntityWhitelist::where('term', $term)
                ->where(function ($q) use ($entityType) {
                    if ($entityType === null) {
                        $q->whereNull('entity_type');
                    } else {
                        $q->where('entity_type', $entityType);
                    }
                })
                ->when($unidadId, fn($q) => $q->where('unidad_id', $unidadId))
                ->when(!$unidadId, fn($q) => $q->whereNull('unidad_id'))
                ->first();

            if ($entry) {
                if (!$entry->active) {
                    $entry->update(['active' => true]);
                }
            } else {
                EntityWhitelist::create([
                    'term'        => $term,
                    'entity_type' => $entityType,
                    'added_by'    => Auth::check() ? Auth::user()->name : 'usuario',
                    'reason'      => 'Agregado manualmente desde el Anonimizador.',
                    'active'      => true,
                    'unidad_id'   => $unidadId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Término \"$term\" agregado a la whitelist. Será reconocido en futuros análisis.",
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al agregar a whitelist: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar en la whitelist.'], 500);
        }
    }

    /** Página de gestión de la Whitelist (menú). */
    public function whitelistIndex()
    {
        $entries = $this->listEntries(EntityWhitelist::query());

        return view('whitelist.index', [
            'entries'      => $entries,
            'entityColors' => $this->userEntityColors(),
        ]);
    }

    /** Elimina permanentemente una entrada de la whitelist por id. */
    public function whitelistDelete(int $id)
    {
        return $this->deleteEntry(EntityWhitelist::class, $id, 'whitelist');
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    private function activeUnidadId(): ?int
    {
        return Auth::check()
            ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
            : null;
    }

    private function userEntityColors(): array
    {
        return EntityConfigController::getUserColors(auth()->id() ?? 0);
    }

    /** Lista las entradas de la unidad activa (o globales) ordenadas por término. */
    private function listEntries($query)
    {
        $unidadId = $this->activeUnidadId();

        try {
            return $query
                ->when($unidadId, fn($q) => $q->where('unidad_id', $unidadId))
                ->when(!$unidadId, fn($q) => $q->whereNull('unidad_id'))
                ->orderBy('term', 'asc')
                ->get();
        } catch (\Exception $e) {
            \Log::error('Error al cargar lista de entidades: ' . $e->getMessage());
            return collect();
        }
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    private function deleteEntry(string $model, int $id, string $listName)
    {
        try {
            $entry = $model::findOrFail($id);
            $term  = $entry->term;
            $entry->delete();

            return response()->json([
                'success' => true,
                'message' => "Término \"$term\" eliminado de la {$listName}.",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Entrada no encontrada.'], 404);
        } catch (\Exception $e) {
            \Log::error("Error al eliminar de {$listName}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar.'], 500);
        }
    }
}
