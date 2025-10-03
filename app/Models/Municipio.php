<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
     protected $fillable = ['codigo_dane','nombre','departamento'];
}
