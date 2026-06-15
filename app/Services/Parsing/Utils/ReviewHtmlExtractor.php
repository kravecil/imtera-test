<?php

namespace App\Services\Parsing\Utils;

use DOMDocument;
use DOMXPath;
use App\Services\Parsing\Exceptions\ParsingException;
use Carbon\Carbon;

class ReviewHtmlExtractor
{
    private array $reviews = [];

    public function extract(string $html): void
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $reviewNodes = $xpath->query('.business-reviews-card-view__review');
        $reviewCount = $reviewNodes->length;

        for ($i = 0; $i < $reviewCount; $i++) {
            $reviewNode = $reviewNodes->item($i);
            $reviewXPath = new DOMXPath($reviewNode->ownerDocument);
            $reviewXPath->document = $reviewNode;

            try {
                // Expand button (если есть)
                $expandBtn = $reviewXPath->query('.//button[contains(@class, "business-review-view__expand")]');
                if ($expandBtn->length > 0) {
                    // В DOM нет метода click(), поэтому используем JS (опционально — если нужен полный фидбек)
                    // Но так как мы парсим уже после `scrollUntilStable`, отзывы, скорее всего, уже раскрыты — можно пропустить
                }

                $author = $this->queryText($reviewXPath, './/span[@itemprop="name"]');
                if ($author === null) throw new ParsingException('Не удалось получить имя автора');

                $text = $this->queryText($reviewXPath, './/span[contains(@class, "spoiler-view__text-container")]');
                if ($text === null) throw new ParsingException('Не удалось получить текст отзыва');

                $ratingLabel = $this->queryAttr($reviewXPath, './/div[contains(@class, "business-rating-badge-view__stars")]', 'aria-label');
                if ($ratingLabel === null) throw new ParsingException('Не удалось получить рейтинг отзыва');
                $rating = $this->extractRatingFromAriaLabel($ratingLabel);

                $dateContent = $this->queryAttr($reviewXPath, './/meta[@itemprop="datePublished"]', 'content');
                if ($dateContent === null) throw new ParsingException('Не удалось получить дату отзыва');
                $date = Carbon::parse($dateContent)->format('Y.m.d');

                $this->reviews[] = compact('author', 'text', 'rating', 'date');
            } catch (ParsingException $e) {
                logger()->warning("Ошибка при парсинге отзыва #{$i}: " . $e->getMessage());
            }
        }
    }

    public function getReviews(): array
    {
        return $this->reviews;
    }

    private function queryText(DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        return $nodes->length ? trim($nodes->item(0)->textContent ?? '') : null;
    }

    private function queryAttr(DOMXPath $xpath, string $query, string $attr): ?string
    {
        $nodes = $xpath->query($query);
        return $nodes->length ? $nodes->item(0)->getAttribute($attr) : null;
    }

    private function extractRatingFromAriaLabel(?string $label): ?int
    {
        if (!$label || !preg_match('/\d+(?:[.,]\d+)?/', $label, $match)) {
            return null;
        }
        return (int) $match[0];
    }
}
