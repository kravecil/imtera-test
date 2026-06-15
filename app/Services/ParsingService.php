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
use Carbon\Carbon;
use App\Exceptions\ParsingException;

class ParsingService
{
    private const YANDEX_MAP_BASE_URL = 'https://yandex.ru/maps/org';

    private ?string $organizationName = null;
    private ?float $rating = null;
    private ?int $ratingCount = null;
    private ?int $reviewCount = null;

    private array $reviews = [];

    private ?BrowserContext $browser = null;
    private ?Page $page = null;

    private function createBrowser(): void
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

    private function createPage(): void
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

    private function closeBrowser(): void
    {
        try {
            $this->page?->close();
        } finally {
            $this->page = null;
            $this->browser?->close();
            $this->browser = null;
        }
    }

    private function parseUrl(string $url): array
    {
        return UrlParser::parseYandexMapsOrgUrl($url);
    }

    private function buildOrganizationUrl(array $params): string
    {
        return self::YANDEX_MAP_BASE_URL . "/{$params['slug']}/{$params['id']}";
    }

    private function buildReviewsUrl(array $params): string
    {
        return self::YANDEX_MAP_BASE_URL . "/{$params['slug']}/{$params['id']}/reviews";
    }

    private function extractDataFromHtml(string $html): void
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $this->organizationName = $this->getTextFromNode($xpath->query('//h1[contains(@class, "orgpage-header-view__header")]'));
        if ($this->organizationName === null) throw new ParsingException('Не удалось получить название организации');

        $this->rating = $this->parseRating($xpath->query('//span[contains(@class, "business-rating-badge-view__rating-text")]'));
        if ($this->rating === null) throw new ParsingException('Не удалось получить рейтинг организации');

        $this->ratingCount = $this->getIntFromNode($xpath->query('//div[contains(@class, "business-header-rating-view__text")]'));
        if ($this->ratingCount === null) throw new ParsingException('Не удалось получить количество оценок организации');

        $this->reviewCount = $this->getIntFromNode(
            $xpath->query('//div[contains(@class, "tabs-select-view__title") and contains(@class, "_name_reviews")]//div[contains(@class, "tabs-select-view__counter")]')
        );
        if ($this->reviewCount === null) throw new ParsingException('Не удалось получить количество отзывов');
    }

    private function getTextFromNode(\DOMNodeList|false|null $nodes): ?string
    {
        return $nodes?->length ? trim($nodes->item(0)?->textContent ?? '') : null;
    }

    private function getIntFromNode(\DOMNodeList|false|null $nodes): ?int
    {
        return $this->getIntFromString($this->getTextFromNode($nodes));
    }

    private function getIntFromString(?string $text): ?int
    {
        return $text !== null ? (int) filter_var($text, FILTER_SANITIZE_NUMBER_INT) : null;
    }

    private function parseRating(\DOMNodeList|false|null $nodes): ?float
    {
        $text = $this->getTextFromNode($nodes);
        if ($text === null) return null;

        $normalized = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $text));
        return filter_var($normalized, FILTER_VALIDATE_FLOAT) !== false ? (float) $normalized : null;
    }

    private function scrollPageUntilStable(string $locator, int $maxScrolls = 20, int $maxRepeats = 3): void
    {
        $previousCount = 0;
        $repeatCount = 0;

        for ($i = 0; $i < $maxScrolls; $i++) {
            $this->page?->mouse()->wheel(0, 50000);
            $currentCount = $this->page?->locator($locator)->count() ?? 0;

            Log::info("Получено элементов: $currentCount");

            if ($currentCount === $previousCount) {
                $repeatCount++;
                if ($repeatCount >= $maxRepeats) {
                    Log::info("Достигнуто стабильное число элементов: $currentCount");
                    break;
                }
            } else {
                $repeatCount = 0;
                $previousCount = $currentCount;
            }

            usleep(500_000);
        }
    }

    private function extractReviews(): void
    {
        $reviewLocators = $this->page->locator('.business-reviews-card-view__review');
        $reviewCount = $reviewLocators->count();

        for ($i = 0; $i < $reviewCount; $i++) {
            $review = $reviewLocators->nth($i);

            try {
                $expandBtn = $review->locator('.business-review-view__expand')->first();
                if ($expandBtn->count() > 0) {
                    $expandBtn->focus();
                    $this->page?->keyboard()->press('Enter');
                }

                $author = $review->locator('.business-review-view__author-name span[itemprop="name"]')->textContent();
                if ($author === null) throw new ParsingException('Не удалось получить имя автора');

                $text = $review->locator('span.spoiler-view__text-container')->textContent();
                if ($text === null) throw new ParsingException('Не удалось получить текст отзыва');

                $ratingLabel = $review->locator('div.business-rating-badge-view__stars')->getAttribute('aria-label');
                if ($ratingLabel === null) throw new ParsingException('Не удалось получить рейтинг отзыва');

                $rating = $this->extractRatingFromAriaLabel($ratingLabel);

                $dateContent = $review->locator('.business-review-view__date meta[itemprop="datePublished"]')->getAttribute('content');
                if ($dateContent === null) throw new ParsingException('Не удалось получить дату отзыва');

                $date = Carbon::parse($dateContent)->format('Y.m.d');

                $this->reviews[] = compact('author', 'text', 'rating', 'date');
            } catch (Exception $e) {
                Log::warning("Ошибка при парсинге отзыва #{$i}: " . $e->getMessage());
            }
        }
    }

    private function extractRatingFromAriaLabel(?string $label): ?int
    {
        return $label && preg_match('/\d+(?:[.,]\d+)?/', $label, $match)
            ? (int) $match[0]
            : null;
    }

    public function parseOrganization(string $url): void
    {
        $params = $this->parseUrl($url);
        $orgUrl = $this->buildOrganizationUrl($params);

        Log::info("Парсинг данных организации: {$orgUrl}");

        $this->createBrowser();
        $this->createPage();

        try {
            $this->page?->goto($orgUrl, ['waitUntil' => 'load']);
            $html = $this->page?->content();

            if ($html === null) {
                throw new ParsingException('Пустой HTML-контент страницы организации');
            }

            $this->extractDataFromHtml($html);
        } catch (Exception $e) {
            $msg = "Ошибка при парсинге {$orgUrl}: " . $e->getMessage();
            Log::error($msg, ['exception' => $e]);
            throw new Exception($msg, 0, $e);
        } finally {
            $this->closeBrowser();
        }
    }

    public function parseReviews(string $url): void
    {
        $params = $this->parseUrl($url);
        $reviewsUrl = $this->buildReviewsUrl($params);

        Log::info("Парсинг отзывов: {$reviewsUrl}");

        $this->createBrowser();
        $this->createPage();

        try {
            $this->page?->goto($reviewsUrl, ['waitUntil' => 'load']);
            $this->page?->waitForSelector('div.card-reviews-view');

            $this->scrollPageUntilStable('.business-reviews-card-view__review');
            $this->extractReviews();
        } catch (Exception $e) {
            $msg = "Ошибка при парсинге отзывов {$reviewsUrl}: " . $e->getMessage();
            Log::error($msg, ['exception' => $e]);
            throw new Exception($msg, 0, $e);
        } finally {
            $this->closeBrowser();
        }
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }
    public function getRating(): ?float
    {
        return $this->rating;
    }
    public function getRatingCount(): ?int
    {
        return $this->ratingCount;
    }
    public function getReviewCount(): ?int
    {
        return $this->reviewCount;
    }
    public function getReviews(): array
    {
        return $this->reviews;
    }
}
