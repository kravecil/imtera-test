<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Services\ParsingService;
use App\Events\ParseUrlSuccessEvent;
use App\Events\ParseUrlFailedEvent;

class ParseUrlJob implements ShouldQueue
{
    use Queueable;

    protected string $url;

    /**
     * Create a new job instance.
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = new ParsingService();

        $service->parseOrganization($this->url);
        $service->parseReviews($this->url);

        $data = [
            "name" => $service->getOrganizationName(),
            "rating" => $service->getRating(),
            "ratingCount" => $service->getRatingCount(),
            "reviewCount" => $service->getReviewCount(),
            "reviews" => $service->getReviews(),
        ];

        $result = [
            "status" => "success",
            "message" => "Парсинг страницы успешно завершён",
            "data" => $data,
        ];

        event(new ParseUrlSuccessEvent($result));
    }

    public function failed(\Exception $exception): void
    {
        $result = [
            "status" => "failed",
            "message" => "Парсинг страницы завершён с ошибкой",
            "data" => $exception->getMessage(),
        ];

        event(new ParseUrlFailedEvent($result));
    }
}
