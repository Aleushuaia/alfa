<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AdministradorUnidad;

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

    public function blacklist(): HasMany
    {
        return $this->hasMany(EntityBlacklist::class, 'unidad_id');
    }

    public function whitelist(): HasMany
    {
        return $this->hasMany(EntityWhitelist::class, 'unidad_id');
    }

    /**
     * Usuarios designados como administradores de esta unidad.
     */
    public function administradores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'administradores_unidades')
            ->using(AdministradorUnidad::class)
            ->withTimestamps();
    }
}
