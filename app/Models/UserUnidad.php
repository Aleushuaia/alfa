<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserUnidad extends Pivot
{
    use SoftDeletes;

    protected $table = 'user_unidad';

    public $incrementing = false;
}
