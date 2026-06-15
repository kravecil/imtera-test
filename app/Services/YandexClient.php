<?php

namespace App\Services\Yandex;

use Illuminate\Support\Facades\Http;

class YandexMapsClient
{
    public function __construct(
        private string $csrfToken,
        private string $sessionId,
        private string $hostConfig,
        private string $hostExp
    ) {}

    /**
     * MAIN API
     */
    public function fetchReviews(
        string $businessId,
        string $reqId,
        int $page = 1,
        int $pageSize = 50,
        array $extra = []
    ): array {
        $params = array_merge([
            'ajax' => '1',
            'businessId' => $businessId,
            'csrfToken' => $this->csrfToken,
            'locale' => 'ru_RU',
            'page' => $page,
            'pageSize' => $pageSize,
            'ranking' => 'by_relevance_org',
            'reqId' => $reqId,
            'sessionId' => $this->sessionId,
            'host_config' => $this->hostConfig,
            'host_exp' => $this->hostExp,
        ], $extra);

        $params['s'] = $this->sign($params);

        $response = Http::withHeaders([
            'X-Retpath-Y' => 'https://yandex.ru/maps/',
            'User-Agent' => $this->ua(),
            'Accept' => 'application/json',
            'Referer' => 'https://yandex.ru/maps/',
        ])->get(
            'https://yandex.ru/maps/api/business/fetchReviews',
            $params
        );

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Yandex error: {$response->status()} | {$response->body()}"
            );
        }

        return $response->json();
    }

    /**
     * SIGNATURE = JS-LIKE IMPLEMENTATION
     */
    private function sign(array $params): string
    {
        $string = $this->stringify($params);
        $hash = $this->djb2($string);

        return $hash . ':' . $this->sessionId;
    }

    /**
     * JS stringify with case-insensitive sort (CRITICAL)
     */
    private function stringify(array $params): string
    {
        // remove nulls EXACTLY like JS
        $params = array_filter($params, fn($v) => $v !== null);

        // JS: sort keys case-insensitive
        uksort($params, fn($a, $b) => strcmp(strtolower($a), strtolower($b)));

        $pairs = [];

        foreach ($params as $k => $v) {

            if (is_bool($v)) {
                $v = $v ? 'true' : 'false';
            }

            if (is_array($v)) {
                // JS behavior in this API is NOT JSON.stringify here
                // it behaves like join or primitive coercion
                $v = $this->flattenArray($v);
            }

            $pairs[] =
                $this->encode($k) . '=' . $this->encode((string)$v);
        }

        return implode('&', $pairs);
    }

    /**
     * array handling (JS-style coercion approximation)
     */
    private function flattenArray(array $arr): string
    {
        return implode(',', $arr);
    }

    /**
     * JS encodeURIComponent equivalent (important edge fixes)
     */
    private function encode(string $value): string
    {
        $encoded = rawurlencode($value);

        return str_replace(
            ['%21', '%27', '%28', '%29', '%2A'],
            ['!', "'", '(', ')', '*'],
            $encoded
        );
    }

    /**
     * djb2 XOR hash (JS equivalent)
     */
    private function djb2(string $str): int
    {
        $hash = 5381;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $hash = (($hash << 5) + $hash) ^ ord($str[$i]);
            $hash &= 0xFFFFFFFF;
        }

        return $hash < 0 ? $hash + 4294967296 : $hash;
    }

    /**
     * safe UA rotation (optional but helps stability)
     */
    private function ua(): string
    {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
    }
}
