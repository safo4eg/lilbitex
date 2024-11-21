<?php

namespace App\Services;

use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Services\API\BlockStreamAPIService;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use BitWasp\Bitcoin\Transaction\Factory\Signer;

final class BTCService
{
    private string $exchanger_btc_address;
    private string $exchanger_btc_private_key;

    private BlockStreamAPIService $block_stream_api;

    public function __construct(BlockStreamAPIService $blockStreamAPI)
    {
        $this->exchanger_btc_address = config('app.exchanger_btc_address');
        $this->exchanger_btc_private_key = config('app.exchanger_btc_private_key');
        $this->block_stream_api = $blockStreamAPI;
    }

    /**
     * Конвертация введенной суммы в сатоши
     */

    public function convertAmountToSatoshi(string $amount, string $rate): ?string
    {
        preg_match('#^((?<rub>\d{0,20})|(?<btc>\d{0,20}\.\d{0,8}))$#u', $amount, $matches);

        if(empty($matches) === false) {
            if(empty($matches['rub']) === false) {
                return BTCHelper::convertRubToSatoshi($amount, $rate);
            } else {
                return BTCHelper::convertBTCToSatoshi($amount);
            }
        }

        return null;
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

    /**
     * Отправить биток и завершить обмен
     */
    public function createSignedTransaction(Order $order): int|string
    {
        try {
            $utxos = $this->block_stream_api->getAddressUTXO($this->exchanger_btc_address);

            if ($utxos === -1) {
                throw new \Exception('UTXO list is empty.');
            }

            $totalInputSum = 0;
            $selectedUtxos = [];

            foreach ($utxos as $utxo) {
                $selectedUtxos[] = $utxo;
                $totalInputSum += $utxo['value'];
                if ($totalInputSum >= $order->amount + $order->network_fee) {
                    break;
                }
            }

            if ($totalInputSum < $order->amount + $order->network_fee) {
                throw new \Exception('Недостаточно средств на кошельке');
            }

            $network = NetworkFactory::bitcoinTestnet(); // Для Testnet
            // $network = NetworkFactory::bitcoin(); // Для Mainnet
            $privateKeyFactory = new PrivateKeyFactory();
            $privateKey = $privateKeyFactory->fromWif($this->exchanger_btc_private_key, $network);
            $addressCreator = new AddressCreator();

            $transactionBuilder = TransactionFactory::build();
            foreach ($selectedUtxos as $utxo) {
                $transactionBuilder->input($utxo['txid'], $utxo['vout']);
            }

            $transactionBuilder->payToAddress($order->amount, $addressCreator->fromString($order->wallet_address, $network));
            $change = $totalInputSum - $order->amount - $order->network_fee;
            // если есть сдача, то добавляем еще output
            if($change > 0) {
                $transactionBuilder->payToAddress($change, $addressCreator->fromString($this->exchanger_btc_address, $network));
            }

            $transaction = $transactionBuilder->get();
            $signer = new Signer($transaction);
            foreach ($selectedUtxos as $index => $utxo) {
                $txOut = new TransactionOutput(
                    $utxo['value'],
                    ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPubKeyHash())
                );
                $input = $signer->input($index, $txOut);
                $input->sign($privateKey);
            }
            $signed = $signer->get();

            return $signed->getHex();
        } catch (\Exception $e) {
            Log::channel('single')->debug("MAIN LOG: " . $e->getMessage() . 'trace: ' . $e->getTraceAsString());
            return -1;
        }
    }
}