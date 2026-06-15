<?php

namespace App\Services\Parsing\Organization;

use App\Services\Parsing\Browser\BrowserManager;
use App\Services\Parsing\Utils\HtmlParser;
use App\Services\Parsing\Utils\UrlParser;
use App\Services\Parsing\Exceptions\ParsingException;
use Illuminate\Support\Facades\Log;

class OrganizationParser
{
    public function __construct(
        private readonly BrowserManager $browser,
        private readonly HtmlParser $htmlParser,
        private readonly OrganizationUrlBuilder $urlBuilder,
        private readonly OrganizationCache $cache,
    ) {}

    public function parse(string $url): void
    {
        $params = UrlParser::parseYandexMapsOrgUrl($url);

        if ($cached = $this->cache->tryLoad($params)) {
            $this->fillFromCache($cached);
            return;
        }

        $orgUrl = $this->urlBuilder->build($params['slug'], $params['id']);
        Log::info("Парсинг данных организации: {$orgUrl} (из кеша не найдено)");

        $this->browser->loadPage($orgUrl);
        $html = $this->browser->getPageContent();

        if ($html === null) {
            throw new ParsingException('Пустой HTML-контент страницы организации');
        }

        $this->htmlParser->parseOrganization($html);
        $this->cache->save($params, [
            'organizationName' => $this->organizationName,
            'rating' => $this->rating,
            'ratingCount' => $this->ratingCount,
            'reviewCount' => $this->reviewCount,
        ]);
    }

    public function getOrganizationName(): ?string { return $this->organizationName; }
    public function getRating(): ?float { return $this->rating; }
    public function getRatingCount(): ?int { return $this->ratingCount; }
    public function getReviewCount(): ?int { return $this->reviewCount; }

    private ?string $organizationName = null;
    private ?float $rating = null;
    private ?int $ratingCount = null;
    private ?int $reviewCount = null;

    private function fillFromCache(array $data): void
    {
        $this->organizationName = $data['organizationName'] ?? null;
        $this->rating = $data['rating'] ?? null;
        $this->ratingCount = $data['ratingCount'] ?? null;
        $this->reviewCount = $data['reviewCount'] ?? null;
    }
}