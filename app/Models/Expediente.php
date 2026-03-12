<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Expediente Eloquent model — representa la tabla `expedientes`.
 */
class Expediente extends Model
{
    use HasFactory;

    /** Conexión a la base SAE Kayen (la misma usada por Repository). */
    protected $connection = 'sae_kayen';

    /** Tabla asociada. */
    protected $table = 'expedientes';

    /** PK no incremental y de tipo string (varchar(32)). */
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    /** Atributos asignables masivamente (ejemplos basados en el dump). */
    protected $fillable = [
        'id',
        'id_origen',
        'organismo_id',
        'nro',
        'anio',
        'fecha_ingreso',
        'fecha_modificacion',
        'caratula',
        'estado_id',
        'fecha_estado',
        'ubicacion_id',
        'fecha_anulado',
        'cbu',
        'cbu_dolares',
        'bloqueado',
        'observaciones',
        'es_incidente',
        'expurgado',
        'tipo_visibilidad_id',
        'oralidad',
    ];

    /** Casts para fechas y tipos comunes. */
    protected function casts(): array
    {
        return [
            'organismo_id' => 'integer',
            'nro' => 'integer',
            'anio' => 'integer',
            'fecha_ingreso' => 'datetime',
            'fecha_modificacion' => 'datetime',
            'fecha_estado' => 'date',
            'fecha_anulado' => 'date',
            'bloqueado' => 'boolean',
            'es_incidente' => 'boolean',
            'expurgado' => 'boolean',
            'tipo_visibilidad_id' => 'integer',
            'oralidad' => 'boolean',
        ];
    }
}
