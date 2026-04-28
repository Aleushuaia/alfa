<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Prompt extends Model
{
    protected $table = 'prompts';

    protected $primaryKey = 'id';
    public    $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'descripcion',
        'contenido',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
