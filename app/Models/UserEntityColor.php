<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEntityColor extends Model
{
    protected $connection = 'alfa_pg';

    protected $fillable = [
        'user_id',
        'entity_type',
        'color',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
