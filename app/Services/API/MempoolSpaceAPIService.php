<?php

namespace App\Services\API;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MempoolSpaceAPIService
{
    private string $url;

    public function __construct()
    {
        $this->url = config('services.mempool_space.api_url');
    }

    /**
     * Получить рекомендованную комиссию в сатоши/байт
     */
    public function getRecommendedFees(): int
    {
        try {
            $response = Http::timeout(15)->get($this->url . '/v1/fees/recommended');

            if($response->ok() === false) {
                throw new \Exception('Ошибка при запросе рекомендованной комиссии с mempool.space');
            }

            $recommendedFastestFee = isset($response['fastestFee'])
                ? $response['fastestFee']
                : null;

            if($recommendedFastestFee === null) {
                throw new \Exception('Ошибка при запросе рекомендованной комисии, осуствует fastestFee ключ.');
            }

            return (int) $recommendedFastestFee;
        } catch (\Exception $e) {
            return -1;
        }
    }

    /**
     * Валидация адрес BTC
     */
    public function validateAddress(string $address): bool
    {
        try {
            $response = Http::timeout(15)->get($this->url . '/v1/validate-address/' . $address);

            if($response->ok() === false) {
                throw new \Exception('Ошибка в ответе при валидации адреса BTC, status !== ok');
            }

            $isValid = isset($response['isvalid'])
                ? $response['isvalid']
                : null;

            if($isValid === null) {
                throw new \Exception('Ошибка в ответе при валидации адреса BTC, отсутствует ключ isvalid');
            }

            return $isValid;
        } catch (\Exception $e) {
            Log::channel('single')->debug($e->getMessage());
            return false;
        }
    }

    /**
     * Отправить биток
     */
    public function sendTransaction(string $hex): int|string
    {
        try {
            $response = Http::timeout(15)->withHeaders([
                'Content-Type' => 'text/plain',
            ])
                ->withBody($hex, 'text/plain')
                ->post($this->url . '/tx');

            if($response->successful()) {
                $txid = $response->body();

                return $txid;
            } else {
                throw new \Exception("Error broadcasting transaction (Mempool.space): " . $response->body());
            }
        } catch (\Exception $e) {
            Log::channel('single')->debug($e->getMessage());
            return -1;
        }
    }

}