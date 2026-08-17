<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'key', 'label', 'section', 'icon',
        'route_name', 'route_pattern', 'sort_order',
    ];

    /** Nombre del permiso Spatie correspondiente a este ítem. */
    public function permissionName(): string
    {
        return 'menu.' . $this->key;
    }
}
