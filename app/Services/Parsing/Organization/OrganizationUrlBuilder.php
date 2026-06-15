<?php

namespace App\Services\Parsing\Organization;

class OrganizationUrlBuilder
{
    private const BASE_URL = 'https://yandex.ru/maps/org';

    public static function build(string $slug, int $id): string
    {
        return self::BASE_URL . "/{$slug}/{$id}";
    }
}
