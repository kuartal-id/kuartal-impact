<?php

namespace App\Models\Index;

use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    protected $table = 'ix_sectors';

    protected $guarded = ['id'];

    public function organisations()
    {
        return $this->belongsToMany(Organisation::class, 'ix_organisation_sector', 'sector_id', 'organisation_id');
    }

    public static function ordered()
    {
        return static::orderBy('sort')->orderBy('name')->get();
    }
}
