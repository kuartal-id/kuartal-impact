<?php

namespace App\Models\Index;

use Illuminate\Database\Eloquent\Model;

class Pillar extends Model
{
    protected $table = 'ix_pillars';

    protected $guarded = ['id'];

    protected $casts = ['weight' => 'integer', 'sort' => 'integer'];

    public static function ordered()
    {
        return static::orderBy('sort')->orderBy('id')->get();
    }
}
