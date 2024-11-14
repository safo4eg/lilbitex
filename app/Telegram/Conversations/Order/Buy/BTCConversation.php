<?php

namespace App\Telegram\Conversations\Order\Buy;

use App\Enums\AssetEnum;
use App\Enums\Order\StatusEnum;
use App\Enums\Order\TypeEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\ExchangerSetting;
use App\Models\Order;
use App\Models\User;
use App\Services\BTCService;
use App\Services\ExchangerSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class BTCConversation extends Conversation
{
    /**
     * Сумма к получению RUB
     */
    public string $amount_rub;

    /**
     * Сумма к получению BTC
     */
    public string $amount_btc;

    /**
     * Сумма для оплаты в RUB
     */
    public string $sum_to_pay_rub;

    /**
     * Сумма для отправки в Satoshi
     */
    public string $sum_to_send_satoshi;

    public string $wallet_address;
    public int $user_model_id;
    public int $exchanger_setting_model_id;
    protected ?string $step = 'requestWalletType';
    private BTCService $btc_service;
    private ExchangerSettingService $exchanger_setting_service;

    public function __construct(Nutgram $bot, BTCService $BTCService, ExchangerSettingService $exchangerSettingService)
    {
        $this->btc_service = $BTCService;
        $this->exchanger_setting_service = $exchangerSettingService;
        $this->user_model_id = User::where('chat_id', $bot->userId())
            ->pluck('id')
            ->first();
    }

    public function requestWalletType(Nutgram $bot)
    {
        $inlineKeyboardMarkup = InlineKeyboardMarkup::make();
        foreach (WalletTypeEnum::cases() as $walletType) {
            $inlineKeyboardMarkup->addRow(InlineKeyboardButton::make(
                __('commands.buy.btc.wallet_types.' . $walletType->value),
                callback_data: $walletType->value
            ));
        }

        $bot->sendMessageWithSaveId(
            text: 'Выберите кошелёк куда будем пополнять:',
            reply_markup: $inlineKeyboardMarkup
        );

        $this->next('handleWalletType');
    }

    public function handleWalletType(Nutgram $bot)
    {
        if(!$bot->isCallbackQuery()) {
            $this->requestWalletType($bot);
            return;
        }

        $walletType = (int) $bot->callbackQuery()->data;

        switch ($walletType) {
            case WalletTypeEnum::BIGMAFIA->value:
                // получаем настройки для работы с BMB
                $bot->sendMessageWithSaveId(text: 'пока недоступно, выберите внешний кошелек');
                return;
            case WalletTypeEnum::EXTERNAL->value:
                $this->exchanger_setting_model_id = ExchangerSetting::where([
                    ['asset', '=', AssetEnum::BTC->value],
                    ['wallet_type', '=', WalletTypeEnum::EXTERNAL->value],
                ])->pluck('id')->first();
                break;
        }

        $this->requestAmount($bot);
    }

    public function requestAmount(Nutgram $bot)
    {
        $setting = ExchangerSetting::find($this->exchanger_setting_model_id);
        $this->exchanger_setting_service->updateBalanceBTC($setting);
        $rate = $setting->rate;

        $viewData = [
            'walletTypeName' => WalletTypeEnum::getWalletTypesName()[$setting->wallet_type],
            'balanceRUB' => $setting->balance_rub,
            'balanceBTC' => $setting->balance_btc,
            'minAmountRUB' => $setting->min_amount,
            'minAmountBTC' => $setting->min_amount_btc,
            'maxAmountRUB' => $setting->max_amount,
            'maxAmountBTC' => $setting->max_amount_btc,
            'rate' => $rate
        ];

        $bot->sendMessageWithSaveId(
            text: view('telegram.order.buy.btc.amount', $viewData),
            parse_mode: ParseMode::HTML,
        );

        $this->next('handleAmount');
    }

    public function handleAmount(Nutgram $bot)
    {
        $setting = ExchangerSetting::find($this->exchanger_setting_model_id);
        $user = User::find($this->user_model_id);
        $amount = $bot->message()->text;

        $amountSatoshi = $this->btc_service->convertAmountToSatoshi($amount, $setting->rate);
        Log::channel('single')->debug(BTCHelper::convertSatoshiToRub($amountSatoshi, $setting->rate));
        // если не прошла валидация формата суммы
        if($amountSatoshi === null) {
            $this->bot->sendMessageWithSaveId(text: 'Некорректный формат суммы, повтрите попытку.');
            return;
        }

        if((int)$amountSatoshi < $setting->min_amount_satoshi) {
            $this->bot->sendMessageWithSaveId(text: 'Введённая сумма меньше минимальной.');
            return;
        } else if((int)$amountSatoshi > $setting->max_amount_satoshi) {
            $this->bot->sendMessageWithSaveId(text: 'Введённая сумма больше максимальной.');
            return;
        }

        // подсчет суммы для отправки
        $this->exchanger_setting_service->updateNetworkFee($setting);

        $baseExchangerFee = bcmul(
            $amountSatoshi,
            bcdiv($setting->exchanger_fee, '100', 10),
            0
        );
        $personalDiscount = bcmul(
            $baseExchangerFee,
            bcdiv($user->personal_discount, '100', 10),
            0
        );
        $finalExchangerFee = bcsub($baseExchangerFee, $personalDiscount, 0);

        $sumToSendSatoshi = (int) $amountSatoshi + $setting->network_fee; // сумма которая будет отправляться с кошеля

        // сравнение итоговой суммы с резервом
        $compareResult = bccomp($sumToSendSatoshi, $setting->balance);

        if($compareResult !== -1) {
            $this->bot->sendMessageWithSaveId(text: 'Введённая сумма с учетом комиссии превышает резерв обменника. Попробуйте позже.');
            return;
        }

        $sumToPaySatoshi = $sumToSendSatoshi + (int)$finalExchangerFee; // сумма которую нужно оплатить
        // переводим суммы для оплаты
        $sumToPayRUB = BTCHelper::convertSatoshiToRub($sumToPaySatoshi, $setting->rate);
        $amountRUB = BTCHelper::convertSatoshiToRub($amountSatoshi, $setting->rate);
        $amountBTC = BTCHelper::convertSatoshiToBTC($amountSatoshi);


        $this->sum_to_pay_rub = $sumToPayRUB;
        $this->amount_rub = $amountRUB;
        $this->amount_btc = $amountBTC;
        $this->sum_to_send_satoshi = $sumToSendSatoshi;

        $this->requestWalletAddress($bot);
    }

    public function requestWalletAddress(Nutgram $bot)
    {
        $setting = ExchangerSetting::where('id', $this->exchanger_setting_model_id)->first();

        $message = view('telegram.order.buy.btc.wallet_address', [
            'walletType' => WalletTypeEnum::getWalletTypesName()[$setting->wallet_type],
            'amountBTC' => $this->amount_btc,
            'amountRUB' => $this->amount_rub,
            'sumToPayRUB' => $this->sum_to_pay_rub
        ]);

        $bot->sendMessageWithSaveId(text: $message, parse_mode: ParseMode::HTML);
    }

//    public function handleWalletAddress(Nutgram $bot)
//    {
//        $walletAddress = $bot->message()->text;
//
//        if(!$walletAddress OR !BTCService::validateWalletAddress($walletAddress)) {
//            $this->requestWalletAddress($bot);
//            return;
//        }
//
//        $this->walletAddress = $walletAddress;
//        $this->requestPayment($bot);
//    }
//
//    public function requestPayment(Nutgram $bot)
//    {
//        // во-первых проверка на завершенные оплаты
//        try {
//            DB::beginTransaction();
//            $user = User::where('chat_id', $bot->user()->id)->first();
//            Order::create([
//                'user_id' => $user->id,
//                'type' => TypeEnum::BUY,
//                'asset' => AssetEnum::BTC,
//                'status' => StatusEnum::PENDING_PAYMENT,
//                'amount' => $this->amount,
//                'wallet_type' => $this->walletType,
//                'wallet_address' => $this->walletAddress,
//                'exchange_rate' => '12.22'
//            ]);
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollBack();
//        }
//
//        $bot->sendMessageWithSaveId(
//            'здесь реквизиты с кнопкой оплаты, пока напиши /start будет очистка шагов'
//        );
//    }
}
