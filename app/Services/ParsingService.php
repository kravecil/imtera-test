<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Log;
use Playwright\Playwright;
use Playwright\Browser\BrowserContext;
use Playwright\Page\Page;
use App\Utils\UrlParser;

class ParsingService
{
    private const YANDEX_MAP_BASE_URL = 'https://yandex.ru/maps/org';

    private ?string $name = null;
    private ?float $rating = null;
    private ?int $ratesCount = null;
    private ?int $reviewsCount = null;

    private ?BrowserContext $browser = null;
    private ?Page $page = null;

    private function initPlaywright(): void
    {
        $this->browser = Playwright::chromium([
            'headless' => false,
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

        // Bypass bot detection
        $this->browser->addInitScript("Object.defineProperty(navigator, 'webdriver', { get: () => undefined });");
        $this->page->evaluate("delete navigator.__proto__.webdriver");
    }

    public function tearDown(): void
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

    public function parseCommon(string $url): void
    {
        ['slug' => $organizationSlug, 'id' => $organizationId] = UrlParser::parseYandexMapsOrgUrl($url);

        $url = self::YANDEX_MAP_BASE_URL . "/{$organizationSlug}/{$organizationId}";

        logger("Начинаем парсинг ссылки [{$url}]");

        $this->initPlaywright();
        $this->initPage();

        try {
            $this->page?->goto($url, ['waitUntil' => 'load']);
            $html = $this->page?->content();

            if ($html === null) {
                throw new Exception('Пустой HTML-контент страницы');
            }

            $this->loadAndExtractOrganizationData($html);
        } catch (Exception $e) {
            $message = "Ошибка при парсинге {$url}: " . $e->getMessage();
            Log::error($message, ['exception' => $e]);
            throw new Exception($message, 0, $e);
        } finally {
            $this->tearDown();
        }
    }

    private function loadAndExtractOrganizationData(string $html): void
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $this->extractNameFromDom($xpath);
        $this->extractRatingFromDom($xpath);
        $this->extractRatesCountFromDom($xpath);
        $this->extractReviewsCountFromDom($xpath);
    }

    private function extractNameFromDom(DOMXPath $xpath): void
    {
        $this->name = $this->extractTextFromNodes(
            $xpath->query('//h1[contains(@class, "orgpage-header-view__header")]')
        );
    }

    private function extractRatingFromDom(DOMXPath $xpath): void
    {
        $text = $this->extractTextFromNodes(
            $xpath->query('//span[contains(@class, "business-rating-badge-view__rating-text")]')
        );
        if ($text !== null) {
            $this->rating = $this->parseRatingValue($text);
        }
    }

    private function extractRatesCountFromDom(DOMXPath $xpath): void
    {
        $this->ratesCount = $this->extractIntegerFromNodes(
            $xpath->query('//div[contains(@class, "business-header-rating-view__text")]')
        );
    }

    private function extractReviewsCountFromDom(DOMXPath $xpath): void
    {
        $this->reviewsCount = $this->extractIntegerFromNodes(
            $xpath->query(
                '//div[contains(@class, "tabs-select-view__title") and contains(@class, "_name_reviews")]'
                    . '//div[contains(@class, "tabs-select-view__counter")]'
            )
        );
    }


    private function extractTextFromNodes(\DOMNodeList|false|null $nodes): ?string
    {
        return $nodes?->length ? trim($nodes->item(0)->textContent ?? '') : null;
    }

    private function extractIntegerFromNodes(\DOMNodeList|false|null $nodes): ?int
    {
        $text = $this->extractTextFromNodes($nodes);
        return $text !== null ? (int) filter_var($text, FILTER_SANITIZE_NUMBER_INT) : null;
    }

    private function parseRatingValue(string $text): ?float
    {
        $normalized = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $text));
        return filter_var($normalized, FILTER_VALIDATE_FLOAT) !== false ? (float) $normalized : null;
    }
}
