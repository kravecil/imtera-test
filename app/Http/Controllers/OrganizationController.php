<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

use App\Rules\ValidateYandexMapsUrlRule;
use App\Jobs\ParseUrlJob;
use App\Services\ParsingService;
use App\Http\Resources\OrganizationResource;

class OrganizationController extends Controller
{
    public function show(Request $request): OrganizationResource
    {
        $validated = $request->validate([
            'link' => [
                'required',
                'string',
                new ValidateYandexMapsUrlRule(),
            ]
        ]);
        $organization = Organization::firstOrCreate($validated);

        // $job = new ParseUrlJob($organization->link);
        // dispatch($job);

        $service = new ParsingService();

        $service->parseOrganization($organization->link);
        $service->parseReviews($organization->link);


        return OrganizationResource::make($service);
    }
}
