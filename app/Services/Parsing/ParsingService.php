<?php

namespace App\Services\Parsing;

use App\Services\Parsing\Organization\OrganizationParser;
use App\Services\Parsing\Reviews\ReviewsParser;
use App\Services\Parsing\Organization\OrganizationCache;
use App\Services\Parsing\Reviews\ReviewsCache;

class ParsingService
{
    public function __construct(
        private OrganizationParser $organizationParser,
        private ReviewsParser $reviewsParser,
        private OrganizationCache $orgCache,
        private ReviewsCache $reviewsCache
    ) {}

    public function parseOrganization(string $url): void
    {
        $this->organizationParser->parse($url);
    }

    public function parseReviews(string $url): void
    {
        $this->reviewsParser->parse($url);
    }

    // Геттеры — делегируют парсерам и кэшу
    public function getOrganizationName(): ?string
    {
        return $this->organizationParser->getOrganizationName();
    }

    public function getRating(): ?float
    {
        return $this->organizationParser->getRating();
    }

    public function getRatingCount(): ?int
    {
        return $this->organizationParser->getRatingCount();
    }

    public function getReviewCount(): ?int
    {
        return $this->organizationParser->getReviewCount();
    }

    public function getReviews(): array
    {
        return $this->reviewsParser->getReviews();
    }
}
