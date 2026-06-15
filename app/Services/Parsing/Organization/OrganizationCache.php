<?php

namespace App\Services\Parsing\Organization;

use App\Services\Parsing\Utils\UrlParser;
use Illuminate\Support\Facades\Cache;

class OrganizationCache
{
    private const CACHE_KEY_PREFIX = 'yandex_org_';

    public function __construct(private readonly int $ttl = 3600) {}

    public function tryLoad(array $params): ?array
    {
        $key = self::CACHE_KEY_PREFIX . $params['id'];
        $data = Cache::get($key);

        if ($data && is_array($data)) {
            logger()->info("Загружено из кеша данные организации: {$key}");
            return [
                'organizationName' => $data['organizationName'] ?? null,
                'rating' => $data['rating'] ?? null,
                'ratingCount' => $data['ratingCount'] ?? null,
                'reviewCount' => $data['reviewCount'] ?? null,
            ];
        }

        return null;
    }

    public function save(array $params, array $data): void
    {
        $key = self::CACHE_KEY_PREFIX . $params['id'];
        Cache::put($key, $data, $this->ttl);
        logger()->debug("Сохранено в кеш данные организации: {$key}");
    }
}
