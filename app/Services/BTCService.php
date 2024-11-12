<?php

namespace App\Services;

use App\Models\ExchangerSetting;
use App\Services\API\BlockStreamAPIService;
use Illuminate\Support\Facades\Http;

final class BTCService
{
    private string $exchanger_btc_address;

    private BlockStreamAPIService $block_stream_api;

    public function __construct(BlockStreamAPIService $blockStreamAPI)
    {
        $this->exchanger_btc_address = config('app.exchanger_btc_address');
        $this->block_stream_api = $blockStreamAPI;
    }

    /**
     * Валидация суммы для покупки, вернёт false если не прошло валидацию, true в противном случае
     */
    public function validateAmount(string $amount): bool
    {
        // маска для введённого числа чтобы пропускало целые и btc
        if(preg_match('#^((\d){0,20}|(\d{0,20}\.\d{0,8}))$#u', $amount) === 0) {
            return false;
        }

        return true;
    }

    public function validateWalletAddress(string $walletAddress): bool
    {
        if (preg_match('/^(1|3|bc1)[a-zA-Z0-9]{25,39}$/', $walletAddress)) {
            return true;
        }

        return false;
    }

    /**
     * Получение курса биткоина к рублю
     * - если возвращает -1, значит что-то не так при получении курса
     * @return int
     */
    public function getExchangeRateToRub(): int
    {
        try {
            $response = Http::get('https://api.coinlore.net/api/ticker/?id=90');

            if(!$response->ok()) {
                throw new \Exception('Ошибка при запросе курса BTC -> USD в статусе ответа');
            }

            $btc_to_usd = (isset($response[0]['price_usd']))
                ? $response[0]['price_usd']
                : null;

            if($btc_to_usd === null) {
                throw new \Exception('Ошибка при запросе курса BTC -> USD в структуре ответа');
            }

            // получаем курс USD -> RUB
            $response = Http::get('https://www.cbr-xml-daily.ru/daily_json.js');

            if (!$response->ok()) {
                throw new \Exception('Ошибка при запросе курса USD -> RUB в статусе ответа');
            }

            $usd_to_rub = isset($response['Valute']['USD']['Value'])
                ? $response['Valute']['USD']['Value']
                : null;

            if($usd_to_rub === null) {
                throw new \Exception('Ошибка при запросе курса USD -> RUB в структуре ответа');
            }

            $btc_to_rub = bcmul($btc_to_usd, $usd_to_rub, 6);
            $btc_to_rub = (int) round((float) $btc_to_rub);

            return $btc_to_rub;
        } catch (\Exception $e) {
            return -1;
        }
    }
}