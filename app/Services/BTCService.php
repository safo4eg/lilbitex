<?php

namespace App\Services;

use App\Enums\Order\BitcoinSendReasonEnum;
use App\Enums\Order\StatusEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Services\API\BlockStreamAPIService;
use App\Services\API\MempoolSpaceAPIService;
use App\Telegram\Services\ManagerService;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use BitWasp\Bitcoin\Transaction\Factory\Signer;

final class BTCService
{
    private string $exchanger_btc_address;
    private string $exchanger_btc_private_key;

    private BlockStreamAPIService $block_stream_api;
    private MempoolSpaceAPIService $mempool_api;
    private ManagerService $manager_service;

    public function __construct(
        BlockStreamAPIService $blockStreamAPI,
        ManagerService $managerService,
        MempoolSpaceAPIService $mempoolSpaceAPI,
    )
    {
        $this->exchanger_btc_address = config('app.exchanger_btc_address');
        $this->exchanger_btc_private_key = config('app.exchanger_btc_private_key');
        $this->block_stream_api = $blockStreamAPI;
        $this->manager_service = $managerService;
        $this->mempool_api = $mempoolSpaceAPI;
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

            if ($utxos === -1 || empty($utxos)) {
                throw new \Exception('Ошибка со списком UTXO при созданиии и подписи транзакции.');
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
                throw new \Exception('Сумма выбранных UTXO-входов меньше чем сумма получения с комиссей.');
            }

//            $network = NetworkFactory::bitcoinTestnet(); // Для Testnet
             $network = NetworkFactory::bitcoin(); // Для Mainnet
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
                    ScriptFactory::scriptPubKey()->p2wkh($privateKey->getPubKeyHash())
                );
                $input = $signer->input($index, $txOut);
                $input->sign($privateKey);
            }
            $signed = $signer->get();

            return $signed->getHex();
        } catch (\Exception $e) {
            return -1;
        }
    }

    /**
     * Создать, подписать и отправить биток
     */
    public function sendBitcoin(Order $order): void
    {
        $txHex = $this->createSignedTransaction($order);
//        $txHex = -1;
        if($txHex === -1) {
            $this->manager_service->showSendBitcoinMessage(
                $order->id,
                BitcoinSendReasonEnum::TRANSACTION_CREATE_ERROR->value
            );
            return;
        }

//        $txid = $this->block_stream_api->sendTransaction($txHex);
        $txid = $this->mempool_api->sendTransaction($txHex);

        if($txid === -1) {
            $this->manager_service->showSendBitcoinMessage(
                $order->id,
                BitcoinSendReasonEnum::TRANSACTION_SEND_ERROR->value
            );
            return;
        }

        $order->update([
            'txid' => $txid,
            'status' => StatusEnum::COMPLETED->value
        ]);
    }
}