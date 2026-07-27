<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RlaDocument extends Model
{
    protected $fillable = ['unit', 'tanggal', 'nama_file', 'path'];

    protected $casts = [
        'tanggal' => 'date',
    ];
}