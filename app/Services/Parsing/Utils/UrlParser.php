<?php

namespace App\Services\Parsing\Utils;

use App\Services\Parsing\Exceptions\ParsingException;

class UrlParser
{
    public static function parseYandexMapsOrgUrl(string $url): array
    {
        $pattern = '#^https?://yandex\.ru/maps/org/([a-zA-Z0-9._-]+)/(\d+)(?:/|\?|\#|$)#';

        if (!preg_match($pattern, $url, $matches)) {
            throw new ParsingException("Неверный формат URL организации Яндекс.Карт: {$url}");
        }

        return [
            'id' => (int) $matches[1],
            'slug' => $matches[2],
        ];
    }
}
