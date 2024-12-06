<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AssetEnum;
use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'personal_discount' => 'string'
        ];
    }

    protected function completedOrdersCount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->orders()
                    ->where('status', StatusEnum::COMPLETED->value)
                    ->count();
            }
        );
    }

    /**
     * Возвращает сумму обменов в рублях
     */
    protected function totalAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $totalAmountSatoshi = $this->orders()
                    ->where('status', StatusEnum::COMPLETED->value)
                    ->sum('amount');
                $setting = ExchangerSetting::where('asset', AssetEnum::BTC->value)
                    ->where('wallet_type', WalletTypeEnum::EXTERNAL->value)
                    ->first();

                return BTCHelper::convertSatoshiToRub($totalAmountSatoshi, $setting->rate);
            }
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }
}
