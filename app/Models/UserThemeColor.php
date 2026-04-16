<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserThemeColor extends Model
{
    protected $connection = 'alfa_pg';

    protected $fillable = [
        'user_id',
        'theme_mode',
        'color_key',
        'color_value',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
