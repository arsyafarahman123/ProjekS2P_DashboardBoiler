<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoilerImage extends Model
{
    protected $table = 'boiler_images';

    protected $fillable = ['unit', 'nama_file', 'path'];

    public $timestamps = true;
}