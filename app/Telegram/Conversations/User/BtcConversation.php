<?php

namespace App\Telegram\Conversations\User;

use App\Enums\AssetEnum;
use App\Enums\Order\TypeEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Jobs\VerifyOrderTimeoutJob;
use App\Models\ExchangerSetting;
use App\Models\Order;
use App\Models\Requisite;
use App\Models\User;
use App\Services\API\MempoolSpaceAPIService;
use App\Services\BTCService;
use App\Services\ExchangerSettingService;
use App\Services\OrderService;
use App\Telegram\Services\BotService;
use Illuminate\Support\Facades\DB;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class BtcConversation extends Conversation
{
    /**
     * Сумма к получению в сатоши
     */
    public string $amount;

    /**
     * Сумма для оплаты в сатоши
     */
    public string $sum_to_pay_satoshi;

    public string $wallet_address;
    public int $exchanger_setting_model_id;
    protected ?string $step = 'requestWalletType';
    private BTCService $btc_service;
    private ExchangerSettingService $exchanger_setting_service;
    private MempoolSpaceAPIService $mempool_space_service;
    private OrderService $order_service;

    public function __construct(
        BTCService $BTCService,
        ExchangerSettingService $exchangerSettingService,
        MempoolSpaceAPIService $mempoolSpaceAPIService,
        OrderService $orderService,
    )
    {
        $this->btc_service = $BTCService;
        $this->exchanger_setting_service = $exchangerSettingService;
        $this->mempool_space_service = $mempoolSpaceAPIService;
        $this->order_service = $orderService;
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
        $inlineKeyboardMarkup->addRow(BotService::getReturnToMenuButton());

        $bot->sendMessageWithSaveId(
            text: 'Выберите кошелёк куда будем пополнять:',
            reply_markup: $inlineKeyboardMarkup,
            chat_id: $bot->chatId()
        );

        $this->next('handleWalletType');
    }

    public function handleWalletType(Nutgram $bot): void
    {
        if(!$bot->isCallbackQuery()) {
            $this->requestWalletType($bot);
            return;
        }

        $walletType = (int) $bot->callbackQuery()->data;

        switch ($walletType) {
            case WalletTypeEnum::BIGMAFIA->value:
                // получаем настройки для работы с BMB
                $bot->sendMessageWithSaveId(
                    text: 'пока недоступно, выберите внешний кошелек',
                    chat_id: $bot->chatId()
                );
                return;
            case WalletTypeEnum::EXTERNAL->value:
                $this->exchanger_setting_model_id = ExchangerSetting::where([
                    ['asset', '=', AssetEnum::BTC->value],
                    ['wallet_type', '=', WalletTypeEnum::EXTERNAL->value],
                ])->pluck('id')->first();
                break;
        }

        $this->requestAmount($bot);
        $bot->answerCallbackQuery();
    }

    public function requestAmount(Nutgram $bot)
    {
        $setting = ExchangerSetting::find($this->exchanger_setting_model_id);
        $this->exchanger_setting_service->updateBalanceBTC($setting);
        $rate = $setting->rate;

        $viewData = [
            'walletTypeName' => WalletTypeEnum::getWalletTypesName()[$setting->wallet_type],
            'minAmountRUB' => $setting->min_amount,
            'minAmountBTC' => $setting->min_amount_btc,
            'maxAmountRUB' => $setting->max_amount,
            'maxAmountBTC' => $setting->max_amount_btc,
            'rate' => $rate
        ];

        $bot->sendMessageWithSaveId(
            text: view('telegram.user.amount', $viewData),
            parse_mode: ParseMode::HTML,
            chat_id: $bot->chatId(),
            reply_markup: InlineKeyboardMarkup::make()
                ->addRow(BotService::getReturnToMenuButton())
        );

        $this->next('handleAmount');
    }

    public function handleAmount(Nutgram $bot)
    {
        $setting = ExchangerSetting::find($this->exchanger_setting_model_id);
        $user = User::where('chat_id', $bot->userId())->first();
        $amount = $bot->message()->text;

        $amountSatoshi = $this->btc_service->convertAmountToSatoshi($amount, $setting->rate);

        // если не прошла валидация формата суммы
        if($amountSatoshi === null) {
            $this->bot->sendMessageWithSaveId(
                text: 'Некорректный формат суммы, повтрите попытку.',
                chat_id: $bot->chatId()
            );
            return;
        }

        if((int)$amountSatoshi < $setting->min_amount_satoshi) {
            $this->bot->sendMessageWithSaveId(
                text: 'Введённая сумма меньше минимальной.',
                chat_id: $bot->chatId()
            );
            return;
        } else if((int)$amountSatoshi > $setting->max_amount_satoshi) {
            $this->bot->sendMessageWithSaveId(
                text: 'Введённая сумма больше максимальной. Напишите менеджеру.',
                chat_id: $bot->chatId(),
                reply_markup: InlineKeyboardMarkup::make()
                    ->addRow(InlineKeyboardButton::make(
                        text: 'Написать менеджеру',
                        url: 'https://t.me/Lilchikbitchik'
                    ))
            );
            return;
        }

        // подсчет суммы для отправки
        if(!$this->exchanger_setting_service->updateNetworkFee($setting)) {
            $this->bot->sendMessageWithSaveId(
                text: 'Произошла ошибка, введите сумму еще раз.',
                chat_id: $bot->chatId()
            );
            return;
        }

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


        // сравнение итоговой суммы которая нужна для отправки транзакции (получение + комиссия) с резервом
        $compareResult = bccomp((int) $amountSatoshi + $setting->network_fee, $setting->balance);

        if($compareResult !== -1) {
            $this->bot->sendMessageWithSaveId(
                text: 'Введённая сумма с учетом комиссии превышает резерв обменника. Попробуйте позже.',
                chat_id: $bot->chatId()
            );
            return;
        }

        $sumToPaySatoshi = (int) $amountSatoshi + $setting->network_fee + (int)$finalExchangerFee; // сумма которую нужно оплатить

        $this->amount = $amountSatoshi;
        $this->sum_to_pay_satoshi = $sumToPaySatoshi;

        $this->requestWalletAddress($bot);
    }

    public function requestWalletAddress(Nutgram $bot)
    {
        $setting = ExchangerSetting::where('id', $this->exchanger_setting_model_id)->first();

        $exchangerFeeSatoshi = (int)$this->sum_to_pay_satoshi - (int)$this->amount - (int)$setting->network_fee;

        $message = view('telegram.user.wallet-address', [
            'walletType' => WalletTypeEnum::getWalletTypesName()[$setting->wallet_type],
            'amountBTC' => BTCHelper::convertSatoshiToBTC($this->amount),
            'amountRUB' => BTCHelper::convertSatoshiToRub($this->amount, $setting->rate),
            'sumToPayRUB' => BTCHelper::convertSatoshiToRub($this->sum_to_pay_satoshi, $setting->rate),
            'networkFeeBTC' => BTCHelper::convertSatoshiToBTC($setting->network_fee),
            'networkFeeRUB' => BTCHelper::convertSatoshiToRub($setting->network_fee, $setting->rate),
            'exchangerFeeBTC' => BTCHelper::convertSatoshiToBTC($exchangerFeeSatoshi),
            'exchangerFeeRUB' => BTCHelper::convertSatoshiToRub($exchangerFeeSatoshi, $setting->rate)
        ]);

        $bot->sendMessageWithSaveId(
            text: $message,
            parse_mode: ParseMode::HTML,
            chat_id: $bot->chatId(),
            reply_markup: InlineKeyboardMarkup::make()
                ->addRow(BotService::getReturnToMenuButton())
        );
        $this->next('handleWalletAddress');
    }

    public function handleWalletAddress(Nutgram $bot): void
    {
        $walletAddress = $bot->message()->text;

        if(!$walletAddress) {
            $bot->sendMessageWithSaveId(
                text: 'Вам нужно ввести адрес своего BTC-адрес.',
                chat_id: $bot->chatId()
            );
            return;
        }

        if(!$this->mempool_space_service->validateAddress($walletAddress)) {
            $bot->sendMessageWithSaveId(
                text: 'Произошла ошибка при проверке адреса, введите свой BTC-адрес еще раз.',
                chat_id: $bot->chatId()
            );
            return;
        }

        $this->wallet_address = $walletAddress;
        $this->requestPayment($bot);
    }

    public function requestPayment(Nutgram $bot)
    {
        $user = User::where('chat_id', $bot->user()->id)->first();
        $requisite = Requisite::where('status', \App\Enums\Requisite\StatusEnum::ENABLED->value)->first();
        $setting = ExchangerSetting::where('id', $this->exchanger_setting_model_id)->first();

        $unusedKopecks = $this->order_service->getUnusedKopecks();
        // если равен 00 тогда пиздец ошибку
        $sumToPayRub = BTCHelper::convertSatoshiToRub($this->sum_to_pay_satoshi, $setting->rate);
        $sumToPayRubWithKopecks = "$sumToPayRub.$unusedKopecks";

        try {
            DB::beginTransaction();
            $order = Order::create([
                'type' => TypeEnum::BUY,
                'user_id' => $user->id,
                'requisite_id' => $requisite->id,
                'exchanger_setting_id' => $setting->id,
                'status' => \App\Enums\Order\StatusEnum::PENDING_PAYMENT->value,
                'amount' => $this->amount,
                'network_fee' => $setting->network_fee,
                'sum_to_pay' => $sumToPayRubWithKopecks,
                'wallet_address' => $this->wallet_address,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $bot->sendMessageWithSaveId(
                text: 'Что-то пошло не так, повторите последний шаг еще раз.',
                chat_id: $bot->chatId()
            );
            return;
        }

        $this->end();
        PendingPaymentOrderMenu::begin(
            bot: $bot,
            userId: $bot->userId(),
            chatId: $bot->chatId(),
        );

//        BotService::clearBotHistory($bot, $bot->userId());

        VerifyOrderTimeoutJob::dispatch($order)
            ->onQueue('orders');
    }
}
