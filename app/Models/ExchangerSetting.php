<?php

namespace App\Models;

use App\Helpers\BTCHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
class ExchangerSetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'exchanger_settings';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'balance' => 'string',
            'rate' => 'string'
        ];
    }

    protected function balanceBtc(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                return BTCHelper::convertSatoshiToBTC($this->balance);
            }
        );
    }

    protected function balanceRub(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                return BTCHelper::convertSatoshiToRub($this->balance, $this->rate);
            }
        );
    }

    /**
     * Минимальная сумма в битках
     */
    protected function minAmountBtc(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                return BTCHelper::convertRubToBTC($this->min_amount, $this->rate);
            }
        );
    }

    /**
     * Минимальная сумма в сатоши
     */
    protected function minAmountSatoshi(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                return BTCHelper::convertBTCToSatoshi($this->min_amount_btc);
            }
        );
    }

    /**
     * Максимальная сумма в битках
     */
    protected function maxAmountBtc(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                return BTCHelper::convertRubToBTC($this->max_amount, $this->rate);
            }
        );
    }

    /**
     * Минимальная сумма в сатоши
     */
    protected function maxAmountSatoshi(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                return BTCHelper::convertBTCToSatoshi($this->max_amount_btc);
            }
        );
    }
}
