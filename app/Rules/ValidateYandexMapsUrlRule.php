<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

use App\Utils\UrlParser;

class ValidateYandexMapsUrlRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            UrlParser::parseYandexMapsOrgUrl($value);
        } catch (\InvalidArgumentException $e) {
            $fail("Поле {$attribute} должно быть корректным URL Яндекс.Карт (формат: https://yandex.ru/maps/org/{slug}/{id})");
        }
    }
}
