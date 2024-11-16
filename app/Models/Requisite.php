<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisite extends Model
{
    use HasFactory;

    protected $table = 'requisites';
    protected $primaryKey = 'id';
    protected $guarded = [];

    /**
     * Инициалы получателя
     */
    public function initials(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                $firstNameFirstSymbol = mb_substr($this->first_name,0, 1);
                $firstSymbolMiddleName = mb_substr($this->middle_name, 0, 1);

                return "{$this->last_name} $firstNameFirstSymbol. $firstSymbolMiddleName.";
            }
        );
    }
}
