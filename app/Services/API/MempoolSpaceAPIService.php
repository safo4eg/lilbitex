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
            $response = Http::timeout(10)->get($this->url . '/fees/recommended');

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

}