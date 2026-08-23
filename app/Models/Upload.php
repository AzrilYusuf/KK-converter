<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Upload extends Model
{
    protected $fillable = [
        'session_id',
        'original_filename',
        'file_path',
        'file_type',
        'status',
    ];

    public function keluarga(): HasOne
    {
        return $this->hasOne(Keluarga::class);
    }
}
