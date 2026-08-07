<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TubePhoto extends Model
{
    protected $table = 'tube_photos';

    protected $fillable = ['unit', 'section', 'tube_number', 'nama_file', 'path'];

    public $timestamps = true;
}