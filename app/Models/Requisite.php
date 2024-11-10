<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisite extends Model
{
    use HasFactory;

    protected $table = 'requisites';
    protected $primaryKey = 'id';
    protected $guarded = [];
}
