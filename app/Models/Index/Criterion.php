<?php

namespace App\Models\Index;

use Illuminate\Database\Eloquent\Model;

class Criterion extends Model
{
    protected $table = 'ix_criteria';

    protected $guarded = ['id'];

    protected $casts = ['score' => 'integer', 'weight' => 'integer'];
}
