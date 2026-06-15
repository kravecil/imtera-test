<?php

namespace App\Utils;

use InvalidArgumentException;

class UrlParser
{
    public const YANDEX_MAP_URL_PATTERN = '#^https?://yandex\.ru/maps/org/([a-zA-Z0-9._-]+)/(\d+)(?:/|\?|\#|$)#';

    /**
     * Извлекает slug и id из URL Яндекс.Карт.
     *
     * @return array{slug: string, id: string}
     * @throws InvalidArgumentException Если URL не соответствует формату
     */
    public static function parseYandexMapsOrgUrl(string $url): array
    {
        if (!preg_match(self::YANDEX_MAP_URL_PATTERN, $url, $matches)) {
            throw new InvalidArgumentException(
                "Некорректный URL Яндекс.Карт. Ожидается формат: https://yandex.ru/maps/org/{slug}/{id}"
            );
        }

        return [
            'slug' => $matches[1],
            'id'   => $matches[2],
        ];
    }
}
