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
}