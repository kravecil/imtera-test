<?php

namespace App\Services\Parsing\Reviews;

class ReviewsUrlBuilder
{
    private const BASE_URL = 'https://yandex.ru/maps/org';

    public static function build(string $slug, int $id): string
    {
        return self::BASE_URL . "/{$slug}/{$id}/reviews";
    }
}