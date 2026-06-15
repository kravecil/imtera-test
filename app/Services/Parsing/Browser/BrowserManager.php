<?php

namespace App\Services\Parsing\Browser;

use App\Exceptions\ParsingException;
use Playwright\Playwright;
use Playwright\Browser\BrowserContext;
use Playwright\Page\Page;
use Illuminate\Support\Facades\Log;

class BrowserManager
{
    private ?BrowserContext $browser = null;
    private ?Page $page = null;

    public function __construct()
    {
        // можно вынести в config, но пока оставим как в оригинале
    }

    public function loadPage(string $url): void
    {
        $this->initBrowser();
        $this->page?->goto($url, ['waitUntil' => 'load']);
    }

    public function waitForSelector(string $selector): void
    {
        $this->page?->waitForSelector($selector);
    }

    public function scrollUntilStable(string $locator, int $maxScrolls = 20, int $maxRepeats = 3): void
    {
        $previousCount = 0;
        $repeatCount = 0;

        for ($i = 0; $i < $maxScrolls; $i++) {
            $this->page?->mouse()->wheel(0, 50000);
            $currentCount = $this->page?->locator($locator)->count() ?? 0;

            Log::info("Получено элементов: $currentCount");

            if ($currentCount === $previousCount) {
                $repeatCount++;
                if ($repeatCount >= $maxRepeats) {
                    Log::info("Достигнуто стабильное число элементов: $currentCount");
                    break;
                }
            } else {
                $repeatCount = 0;
                $previousCount = $currentCount;
            }

            usleep(500_000);
        }
    }

    public function getPageContent(): ?string
    {
        return $this->page?->content();
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    private function initBrowser(): void
    {
        if ($this->browser !== null) return;

        $this->browser = Playwright::chromium([
            'headless' => true,
            'args' => [
                '--disable-blink-features=AutomationControlled',
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
            ],
        ]);

        $this->page = $this->browser->newPage([
            'viewport' => ['width' => 1920, 'height' => 1080],
            'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'extraHTTPHeaders' => [
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://yandex.ru/',
            ],
        ]);

        $this->browser->addInitScript("Object.defineProperty(navigator, 'webdriver', { get: () => undefined });");
        $this->page->evaluate("delete navigator.__proto__.webdriver");
    }

    public function close(): void
    {
        try {
            $this->page?->close();
        } finally {
            $this->page = null;
            $this->browser?->close();
            $this->browser = null;
        }
    }
}
