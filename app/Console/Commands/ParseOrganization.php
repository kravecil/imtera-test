<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;


use \App\Services\ParsingService;
use App\Jobs\ParseUrlJob;

#[Signature('parse:organization {url}')]
#[Description('Загрузить сведения об организации по ссылке')]
class ParseOrganization extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ParsingService $service)
    {
        $url = $this->argument('url');

        // $service->parseOrganization($url);
        // $service->parseReviews($url);

        // dd($service);

        $job = new ParseUrlJob($url);
        dispatch($job);

        return 0;
    }
}
