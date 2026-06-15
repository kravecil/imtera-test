<?php

namespace App\Services\Parsing\Reviews;

use App\Services\Parsing\Utils\UrlParser;
use Illuminate\Support\Facades\Cache;

class ReviewsCache
{
    private const CACHE_KEY_PREFIX = 'yandex_reviews_';

    public function __construct(private readonly int $ttl = 3600) {}

    public function tryLoad(array $params): ?array
    {
        $key = self::CACHE_KEY_PREFIX . $params['id'];
        $reviews = Cache::get($key);

        if (is_array($reviews)) {
            $count = count($reviews);
            logger()->info("Загружено из кеша {$count} отзывов для организации #{$params['id']}");
            return $reviews;
        }

        return null;
    }

    public function save(array $params, array $reviews): void
    {
        $key = self::CACHE_KEY_PREFIX . $params['id'];
        Cache::put($key, $reviews, $this->ttl);
        $count = count($reviews);
        logger()->debug("Сохранено в кеш {$count} отзывов для организации #{$params['id']}");
    }
}
