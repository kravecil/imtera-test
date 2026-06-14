<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;

class ParsingService
{
    protected string $url;

    protected ?string $name = null;
    protected ?float $rating = null;
    protected ?int $ratesCount = null;
    protected ?int $reviewsCount = null;

    public function parse(string $url)
    {
        $this->url = $url;

        logger("Начинаем парсинг ссылки [{$url}]");

        $html = $this->getHtmlFromBrowsershot();
        $this->parseHtml($html);
    }

    protected function getHtmlFromBrowsershot(): string
    {
        try {
            return Browsershot::url($this->url)
                ->userAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->setExtraHttpHeaders([
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Referer' => 'https://yandex.ru/',
                ])
                ->viewport(1920, 1080)
                ->waitDelay(5000)
                ->timeout(10)
                ->ignoreCertificateErrors()
                ->evaluateExpression('Object.defineProperty(navigator, "webdriver", { get: () => undefined })')
                ->evaluateExpression('delete navigator.__proto__.webdriver')
                ->bodyHtml();
        } catch (\Exception $e) {
            Log::error("Browsershot error for {$this->url}: " . $e->getMessage());
            throw new \RuntimeException("Не удалось загрузить страницу через Browsershot: " . $e->getMessage(), 0, $e);
        }
    }

    protected function parseHtml(string $html)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $this->parseName($xpath);
        $this->parseRating($xpath);
        $this->parseRatesCount($xpath);
        $this->parseReviewsCount($xpath);

        dd($this);
    }

    protected function parseName(DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//h1[contains(@class, "orgpage-header-view__header")]');
        $this->name = $nodes && $nodes->length ? trim($nodes->item(0)->textContent) : null;
    }

    protected function parseRating(DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//span[contains(@class, "business-rating-badge-view__rating-text")]');
        $rating = $nodes && $nodes->length ? trim($nodes->item(0)->textContent) : null;

        if ($rating) {
            $this->rating = (float) str($rating)->trim()->replace(',', '.')->toString();
        }
    }

    protected function parseRatesCount(DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//div[contains(@class, "business-header-rating-view__text")]');
        $ratesCount = $nodes && $nodes->length ? trim($nodes->item(0)->textContent) : null;

        if ($ratesCount) {
            $this->ratesCount = (int) str($ratesCount)->trim()->replaceMatches('/[^\d]/', '')?->toString();
        }
    }

    protected function parseReviewsCount(DOMXPath $xpath): void
    {
        $nodes = $xpath->query(
            '//div[contains(@class, "tabs-select-view__title") and contains(@class, "_name_reviews")]'
                . '//div[contains(@class, "tabs-select-view__counter")]'
        );
        $this->reviewsCount = $nodes && $nodes->length ? trim($nodes->item(0)->textContent) : null;
    }
}
