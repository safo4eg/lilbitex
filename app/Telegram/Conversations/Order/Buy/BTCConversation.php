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
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class BTCConversation extends Conversation
{
    public string $amount;
    public string $wallet_address;
    protected ?string $step = 'requestWalletType';

    private ExchangerSetting $exchanger_setting;
    private BTCService $btc_service;
    private ExchangerSettingService $exchanger_setting_service;

    public function __construct(BTCService $BTCService, ExchangerSettingService $exchangerSettingService)
    {
        $this->btc_service = $BTCService;
        $this->exchanger_setting_service = $exchangerSettingService;
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
                $bot->sendMessage(text: 'пока недоступно, выберите внешний кошелек');
                // получаем настройки для работы с BMB
                $this->requestWalletType($bot);
                return;
            case WalletTypeEnum::EXTERNAL->value:
                $this->exchanger_setting = ExchangerSetting::where([
                    ['asset', '=', AssetEnum::BTC->value],
                    ['wallet_type', '=', WalletTypeEnum::EXTERNAL->value],
                ])->first();
                break;
        }

        $this->requestAmount($bot);
    }

    public function requestAmount(Nutgram $bot)
    {
        $this->exchanger_setting_service->updateBalanceBTC($this->exchanger_setting);
        $rate = $this->exchanger_setting->rate;

        $viewData = [
            'walletTypeName' => WalletTypeEnum::getWalletTypesName()[$this->exchanger_setting->wallet_type],
            'balanceRUB' => $this->exchanger_setting->balance_rub,
            'balanceBTC' => $this->exchanger_setting->balance_btc,
            'minAmountRUB' => $this->exchanger_setting->min_amount,
            'minAmountBTC' => BTCHelper::convertRubToBTC($this->exchanger_setting->min_amount, $rate),
            'maxAmountRUB' => $this->exchanger_setting->max_amount,
            'maxAmountBTC' => BTCHelper::convertRubToBTC($this->exchanger_setting->max_amount, $rate),
            'rate' => $rate
        ];

        $bot->sendMessageWithSaveId(
            text: view('telegram.order.buy.btc.amount', $viewData),
            parse_mode: ParseMode::HTML,
        );
    }

    public function handleAmount(Nutgram $bot)
    {
        $amount = $bot->message()->text;

        if(!$amount OR !BTCService::validateAmount($amount)) {
            $this->requestAmount($bot);
            $bot->answerCallbackQuery();
            return;
        }

        $this->amount = $amount;
        $this->requestWalletAddress($bot);
    }

    public function requestWalletAddress(Nutgram $bot)
    {
        $bot->sendMessageWithSaveId(
            text: view(
                view: 'telegram.order.buy.btc.wallet_address',
                data: ['walletType' => WalletTypeEnum::getWalletTypesName()[$this->walletType]]
            ),
            parse_mode: ParseMode::HTML
        );

        $this->next('handleWalletAddress');
    }

    public function handleWalletAddress(Nutgram $bot)
    {
        $walletAddress = $bot->message()->text;

        if(!$walletAddress OR !BTCService::validateWalletAddress($walletAddress)) {
            $this->requestWalletAddress($bot);
            return;
        }

        $this->walletAddress = $walletAddress;
        $this->requestPayment($bot);
    }

    public function requestPayment(Nutgram $bot)
    {
        // во-первых проверка на завершенные оплаты
        try {
            DB::beginTransaction();
            $user = User::where('chat_id', $bot->user()->id)->first();
            Order::create([
                'user_id' => $user->id,
                'type' => TypeEnum::BUY,
                'asset' => AssetEnum::BTC,
                'status' => StatusEnum::PENDING_PAYMENT,
                'amount' => $this->amount,
                'wallet_type' => $this->walletType,
                'wallet_address' => $this->walletAddress,
                'exchange_rate' => '12.22'
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }

        $bot->sendMessageWithSaveId(
            'здесь реквизиты с кнопкой оплаты, пока напиши /start будет очистка шагов'
        );
    }
}
