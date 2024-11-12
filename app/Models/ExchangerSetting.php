<?php

namespace App\Models;

use App\Helpers\BTCHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
class ExchangerSettings extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'exchanger_settings';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'balance' => 'string'
        ];
    }

    protected function balanceBtc(): Attribute
    {
        return Attribute::make(
            get: function (string $value) {
                return BTCHelper::convertSatoshiToBTC($this->balance);
            }
        );
    }

    protected function balanceRub(): Attribute
    {
        return Attribute::make(
            get: function (string $value) {
                return BTCHelper::convertSatoshiToRub($this->balance);
            }
        );
    }

    public function updateBalance()
    {

    }
}
