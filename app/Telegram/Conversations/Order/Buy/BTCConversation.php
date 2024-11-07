<?php

namespace App\Telegram\Conversations\Order\Buy;

use App\Enums\Order\AssetEnum;
use App\Enums\Order\StatusEnum;
use App\Enums\Order\TypeEnum;
use App\Enums\Order\WalletTypeEnum;
use App\Models\Order;
use App\Telegram\Services\Order\Buy\BTCService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class BTCConversation extends Conversation
{
    public int $walletType;
    public string $amount;
    public string $walletAddress;

    protected ?string $step = 'requestWalletType';

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

        /**
         * TODO: возможно нужна будет доп. валидация, чтобы с предыдущих шагов не падало
         */
        $this->walletType = (int) $bot->callbackQuery()->data;
        $this->requestAmount($bot);
    }

    public function requestAmount(Nutgram $bot)
    {
        $bot->sendMessageWithSaveId(
            text: view('telegram.order.buy.btc.amount'),
            parse_mode: ParseMode::HTML
        );

        $this->next('handleAmount');
    }

    public function handleAmount(Nutgram $bot)
    {
        $amount = $bot->message()->text;

        if(!$amount OR !BTCService::validateAmount($amount)) {
            $this->requestAmount($bot);
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
