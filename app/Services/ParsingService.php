<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Log;
use Playwright\HttpRequest;
use Playwright\Page\Page;
use Playwright\Playwright;
use Playwright\Browser\BrowserContext;
use Playwright\BrowserType;
use Playwright\BrowserType\LaunchOptions;
use Playwright\Devtools\Devtools\Har;
use Illuminate\Support\Facades\Http;
use App\Services\Yandex\YandexMapsClient;


class ParsingService
{
    protected string $url;

    protected ?string $name = null;
    protected ?float $rating = null;
    protected ?int $ratesCount = null;
    protected ?int $reviewsCount = null;

    protected ?BrowserContext $browser = null;
    protected ?Page $page = null;

    public function parse(string $url): self
    {
        $this->url = $url;

        logger("Начинаем парсинг ссылки [{$url}]");

        $this->initPlaywright();

        $html = $this->getHtmlFromPlaywright();
        // $this->parseHtml($html);

        $this->closeResources();

        return $this;
    }

    private function initPlaywright(): void
    {
        $this->browser = Playwright::chromium([
            'headless' => true,
            'args' => [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
            ],
        ]);

        $this->page = $this->browser->newPage([
            'viewport' => ['width' => 1920, 'height' => 1080],
            'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'extraHttpHeaders' => [
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://yandex.ru/',
            ],
        ]);
    }

    private function getHtmlFromPlaywright(): void
    {
        $this->page->goto($this->url, ['waitUntil' => 'load']);

        $this->page->evaluate("
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
            delete navigator.__proto__.webdriver;
        ");

        $json = $this->page->evaluate('
            () => document.querySelector(
                "script.state-view"
            )?.textContent
        ');
        $data = json_decode($json, true);

        $csrfToken = $data['config']['csrfToken'] ?? null;

        $sessionId = $data['config']['counters']['analytics']['sessionId'] ?? null;

        $requestId = $data['stack'][0]['results']['requestId'] ?? null;

        $businessId = $data['config']['query']['orgpage']['id'];

        //         $params = [
        //             'ajax' => 1,
        //             'businessId' => $businessId,
        //             'csrfToken' => $csrfToken,
        //             'locale' => 'ru_RU',
        //             'page' => 1,
        //             'pageSize' => 50,
        //             'ranking' => 'by_relevance_org',
        //             'reqId' => $requestId,
        //             's' => 2527369185, //$s ?? null,
        //             'sessionId' => $sessionId,
        //         ];

        //         $s = $this->buildS($params, $sessionId);
        //         $params['s'] = $s;



        //         $queryParams = $this->jsStringify($params);
        //         $url = "https://yandex.ru/maps/api/business/fetchReviews?{$queryParams}";
        //         $js = <<<'JS'
        // async (url) => {
        //     try {
        //         const headers = new Headers({
        //             'Accept': 'application/json',
        //             'X-Requested-With': 'XMLHttpRequest',
        //             'Referer': window.location.href,
        //             'Origin': 'https://yandex.ru/maps'
        //         });


        //         const res = await fetch(url, { headers });
        //         if (!res.ok) {
        //             const text = await res.text();
        //             throw new Error(`HTTP ${res.status}: ${text.substring(0, 200)}`);
        //         }
        //         return await res.json();
        //     } catch (error) {
        //         return {
        //             error: error.message,
        //             stack: error.stack,
        //             ok: false
        //         };
        //     }
        // }

        // JS;
        //         $response = $this->page->evaluate($js, [$url]);

        //         dd($response);

        $this->page->goto('https://yandex.ru/maps/', [
            'waitUntil' => 'networkidle'
        ]);

        $host = $this->page->evaluate(<<<'JS'
(() => window.__INITIAL_STATE__ || window.Ya?.initialState)
JS);
        dd($host);
        $client = new YandexMapsClient(
            csrfToken: $csrfToken,
            sessionId: $sessionId,
            hostConfig: $hostConfig,
            hostExp: $hostExp
        );

        $data = $client->fetchReviews(
            businessId: $businessId,
            reqId: $requestId
        );

        dd($data);
    }

    private function closeResources(): void
    {
        if ($this->page) {
            $this->page->close();
            $this->page = null;
        }

        if ($this->browser) {
            $this->browser->close();
            $this->browser = null;
        }
    }

    private function sortKeysCaseInsensitive(array $params): array
    {
        uksort($params, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        return $params;
    }

    private function jsStringify(array $params): string
    {
        // remove nulls EXACTLY like JS
        $params = array_filter($params, fn($v) => $v !== null);

        uksort($params, function ($a, $b) {
            return strcmp(strtolower($a), strtolower($b));
        });

        $pairs = [];

        foreach ($params as $k => $v) {

            if (is_bool($v)) {
                $v = $v ? 'true' : 'false';
            }

            // JS does NOT JSON encode arrays in this case — this is critical
            if (is_array($v)) {
                $v = implode(',', $v);
            }

            // IMPORTANT: encode exactly like JS encodeURIComponent
            $pairs[] = $this->encodeURIComponent($k) . '=' . $this->encodeURIComponent((string)$v);
        }

        return implode('&', $pairs);
    }

    private function djb2_xor(string $str): int
    {
        $hash = 5381;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $hash = (33 * $hash) ^ ord($str[$i]);
            $hash &= 0xFFFFFFFF; // emulate 32-bit overflow
        }

        // unsigned
        return $hash < 0 ? $hash + 4294967296 : $hash;
    }

    private function buildS(array $params, string $sessionSeed): string
    {
        $str = $this->jsStringify($params);
        $hash = $this->djb2_xor($str);

        return $hash; // . ':' . $sessionSeed;
    }

    private function encodeURIComponent(string $str): string
    {
        $replacements = [
            '%21' => '!',
            '%27' => "'",
            '%28' => '(',
            '%29' => ')',
            '%2A' => '*',
        ];

        $encoded = rawurlencode($str);

        return str_replace(array_keys($replacements), array_values($replacements), $encoded);
    }
}
