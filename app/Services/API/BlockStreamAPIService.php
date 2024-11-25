<?php

namespace App\Services\API;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class BlockStreamAPIService
{
    private string $url;

    public function __construct()
    {
        $this->url = config('services.block_stream.api_url');
    }

    /**
     * Получить баланс кошелька обменника
     * @return void
     */
    public function getAddressBalance(string $address): int
    {
        try {
            $response = Http::timeout(10)->get($this->url . '/address/' . $address);

            if($response->ok() === false) {
                throw new \Exception('При запросе баланса кошелька вернулся статус отличный от 200');
            }

            return (int) $response['chain_stats']['funded_txo_sum'];
        } catch (\Exception $e) {
            return -1;
        }
    }

    /**
     * Получить UTXO адреса
     */
    public function getAddressUTXO(string $address): int|array
    {
        try {
            $response = Http::timeout(20)->get($this->url . '/address/' . $address . '/utxo');

            if($response->ok() === false) {
                throw new \Exception('Ошибка при получении UTXOs адреса');
            }

            return $response->json();
        } catch (\Exception $e) {
            return -1;
        }
    }

    public function getScriptPubKey(string $txid, string $ownerAddress): int|string
    {
        try {
            $response = Http::timeout(10)->get($this->url . '/tx/' . $txid);

            if($response->ok() === false) {
                throw new \Exception('Ошибка при получении информации по txid: статус !== 200');
            }

            if(!isset($response['vin'][0]['prevout']['scriptpubkey'])) {
                throw new \Exception('Ошибка при получении информации по txid: отсутствует scriptpubkey');
            }

            $vouts = $response['vout'];
            $scriptPubKey = '';
            foreach ($vouts as $vout) {
                if($vout['scriptpubkey_address'] === $ownerAddress) {
                    $scriptPubKey = $vout['scriptpubkey'];
                }
            }

            if(!empty($scriptPubKey)) {
                return $scriptPubKey;
            } else {
                return -1;
            }
        } catch (\Exception $e) {
            return -1;
        }
    }


    /**
     * Отправить биток
     */
    public function sendTransaction(string $hex): int|string
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'Content-Type' => 'text/plain',
            ])
                ->withBody($hex, 'text/plain')
                ->post($this->url . '/tx');

            if($response->successful()) {
                $txid = $response->body();

                return $txid;
            } else {
                throw new \Exception("Error broadcasting transaction: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::channel('single')->debug($e->getMessage());
            return -1;
        }
    }
}