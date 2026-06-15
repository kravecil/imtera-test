<?php

namespace App\Services\Parsing\Utils;

use DOMDocument;
use DOMXPath;
use App\Services\Parsing\Exceptions\ParsingException;

class HtmlParser
{
    private ?string $organizationName = null;
    private ?float $rating = null;
    private ?int $ratingCount = null;
    private ?int $reviewCount = null;

    public function parseOrganization(string $html): void
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $this->organizationName = $this->getTextFromNode(
            $xpath->query('//h1[contains(@class, "orgpage-header-view__header")]')
        );
        if ($this->organizationName === null) {
            throw new ParsingException('Не удалось получить название организации');
        }

        $this->rating = $this->parseRating(
            $xpath->query('//span[contains(@class, "business-rating-badge-view__rating-text")]')
        );
        if ($this->rating === null) {
            throw new ParsingException('Не удалось получить рейтинг организации');
        }

        $this->ratingCount = $this->getIntFromNode(
            $xpath->query('//div[contains(@class, "business-header-rating-view__text")]')
        );
        if ($this->ratingCount === null) {
            throw new ParsingException('Не удалось получить количество оценок организации');
        }

        $this->reviewCount = $this->getIntFromNode(
            $xpath->query('//div[contains(@class, "tabs-select-view__title") and contains(@class, "_name_reviews")]//div[contains(@class, "tabs-select-view__counter")]')
        );
        if ($this->reviewCount === null) {
            throw new ParsingException('Не удалось получить количество отзывов');
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

    private function getTextFromNode($nodes): ?string
    {
        return $nodes?->length ? trim($nodes->item(0)?->textContent ?? '') : null;
    }

    private function getIntFromNode($nodes): ?int
    {
        return $this->getIntFromString($this->getTextFromNode($nodes));
    }

    private function getIntFromString(?string $text): ?int
    {
        return $text !== null ? (int) filter_var($text, FILTER_SANITIZE_NUMBER_INT) : null;
    }

    private function parseRating($nodes): ?float
    {
        $text = $this->getTextFromNode($nodes);
        if ($text === null) return null;

        $normalized = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $text));
        return filter_var($normalized, FILTER_VALIDATE_FLOAT) !== false ? (float) $normalized : null;
    }
}
