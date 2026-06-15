<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Playwright\Playwright;
use Playwright\Browser\BrowserContext;
use Playwright\Page\Page;

class ParsingService
{
    protected string $url;

    protected ?string $name = null;
    protected ?float $rating = null;
    protected ?int $ratesCount = null;
    protected ?int $reviewsCount = null;

    protected ?BrowserContext $browser = null;
    protected ?Page $page = null;

    public function parse(string $url)
    {
        $this->url = $url;

        logger("Начинаем парсинг ссылки [{$url}]");

        try {
            $this->initPlaywright();
            $this->initPage();

            $this->page->goto($this->url, ['waitUntil' => 'networkidle']);

            $this->page->waitForSelector('//h1[contains(@class, "orgpage-header-view__header")]', [
                'timeout' => 20000,
                'state' => 'visible',
            ]);

            $html = $this->page->content();
            $this->parseHtml($html);
        } catch (\Exception $e) {
            Log::error("Ошибка при парсинге {$this->url}: " . $e->getMessage());
            throw new \RuntimeException("Не удалось загрузить страницу через Playwright: " . $e->getMessage(), 0, $e);
        } finally {
            $this->close();
        }
    }

    private function initPlaywright(): void
    {
        $this->browser = Playwright::chromium([
            'headless' => true,
            'args' => [
                '--disable-blink-features=AutomationControlled',
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
            ],
        ]);
    }

    private function initPage(): void
    {
        $this->page = $this->browser->newPage([
            'viewport' => ['width' => 1920, 'height' => 1080],
            'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'extraHTTPHeaders' => [
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://yandex.ru/',
            ],
        ]);


        $this->browser->addInitScript("Object.defineProperty(navigator, 'webdriver', { get: () => undefined });");
        $this->page->evaluate("delete navigator.__proto__.webdriver");
    }

    private function parseHtml(string $html): void
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $this->parseName($xpath);
        $this->parseRating($xpath);
        $this->parseRatesCount($xpath);
        $this->parseReviewsCount($xpath);

        dd($this);
    }

    private function parseName(\DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//h1[contains(@class, "orgpage-header-view__header")]');
        $this->name = $nodes && $nodes->length ? trim($nodes->item(0)->textContent) : null;
    }

    private function parseRating(\DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//span[contains(@class, "business-rating-badge-view__rating-text")]');
        $rating = $nodes && $nodes->length ? trim($nodes->item(0)->textContent) : null;

        if ($rating) {
            $this->rating = (float) preg_replace('/[,\s]+/', '.', $rating);
        }
    }

    private function parseRatesCount(\DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//div[contains(@class, "business-header-rating-view__text")]');
        $ratesCount = $nodes && $nodes->length ? trim($nodes->item(0)->textContent) : null;

        if ($ratesCount) {
            $this->ratesCount = (int) preg_replace('/\D+/', '', $ratesCount);
        }
    }

    private function parseReviewsCount(\DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//div[contains(@class, "tabs-select-view__title") and contains(@class, "_name_reviews")]//div[contains(@class, "tabs-select-view__counter")]');
        $this->reviewsCount = $nodes && $nodes->length ? (int) preg_replace('/\D+/', '', trim($nodes->item(0)->textContent)) : null;
    }

    public function close(): void
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
}
