<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sum_to_pay' => 'string',
            'last_transaction_check' => 'datetime',
            'created_at' => 'datetime'
        ];
    }

    protected $with = ['user', 'requisite', 'setting'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function requisite(): BelongsTo
    {
        return $this->belongsTo(Requisite::class, 'requisite_id', 'id');
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(ExchangerSetting::class, 'exchanger_setting_id', 'id');
    }
}
