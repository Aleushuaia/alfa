<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unidad extends Model
{
    /** @use HasFactory<\Database\Factories\UnidadFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'unidades';

    protected $fillable = ['descripcion'];

    /**
     * Usuarios asignados a esta unidad (excluye pivot soft-deleted).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_unidad')
            ->using(UserUnidad::class)
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}
