<?php

namespace App\Models\Index;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'ix_documents';

    protected $guarded = ['id'];
}
