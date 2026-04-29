<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Unidades de trabajo asignadas a este usuario (excluye pivot soft-deleted).
     */
    public function unidades(): BelongsToMany
    {
        return $this->belongsToMany(Unidad::class, 'user_unidad')
            ->using(UserUnidad::class)
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /**
     * Unidades de trabajo que este usuario administra.
     */
    public function unidadesAdministradas(): BelongsToMany
    {
        return $this->belongsToMany(Unidad::class, 'administradores_unidades')
            ->using(AdministradorUnidad::class)
            ->withTimestamps();
    }

    /**
     * Todas las unidades accesibles: como miembro (user_unidad)
     * O como administrador (administradores_unidades).
     * Devuelve una Collection de Unidad ordenada alfabéticamente.
     */
    public function allAccessibleUnidades(): \Illuminate\Database\Eloquent\Collection
    {
        $memberIds = $this->unidades()->pluck('unidades.id');
        $adminIds  = $this->unidadesAdministradas()->pluck('unidades.id');
        $ids       = $memberIds->merge($adminIds)->unique();

        return Unidad::whereIn('id', $ids)->orderBy('descripcion')->get();
    }
}
