<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangerSettings extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'exchanger_settings';
    protected $primaryKey = 'id';
    protected $guarded = [];
}
