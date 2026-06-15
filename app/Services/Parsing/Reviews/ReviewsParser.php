<?php

namespace App\Services\Parsing\Reviews;

use App\Services\Parsing\Browser\BrowserManager;
use App\Services\Parsing\Utils\ReviewHtmlExtractor;
use App\Services\Parsing\Utils\UrlParser;
use App\Services\Parsing\Exceptions\ParsingException;
use Illuminate\Support\Facades\Log;

class ReviewsParser
{
    public function __construct(
        private readonly BrowserManager $browser,
        private readonly ReviewHtmlExtractor $extractor,
        private readonly ReviewsUrlBuilder $urlBuilder,
        private readonly ReviewsCache $cache,
    ) {}

    public function parse(string $url): void
    {
        $params = UrlParser::parseYandexMapsOrgUrl($url);

        if ($cachedReviews = $this->cache->tryLoad($params)) {
            $this->reviews = $cachedReviews;
            return;
        }

        $reviewsUrl = $this->urlBuilder->build($params['slug'], $params['id']);
        Log::info("Парсинг отзывов: {$reviewsUrl}");

        $this->browser->loadPage($reviewsUrl);
        $this->browser->waitForSelector('.business-reviews-card-view__review');

        // Скроллим, пока количество отзывов не стабилизируется
        $this->browser->scrollUntilStable('.business-reviews-card-view__review');

        $html = $this->browser->getPageContent();
        if ($html === null) {
            throw new ParsingException('Пустой HTML-контент страницы отзывов');
        }

        $this->extractor->extract($html);
        $this->cache->save($params, $this->extractor->getReviews());
    }

    public function getReviews(): array
    {
        return $this->reviews;
    }

    private array $reviews = [];
}
