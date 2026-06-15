<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

use App\Rules\ValidateYandexMapsUrlRule;
use App\Jobs\ParseUrlJob;

class OrganizationController extends Controller
{
    public function store(Request $request): Organization
    {
        $validated = $request->validate([
            'link' => [
                'required',
                'string',
                'unique:organizations,link',
                new ValidateYandexMapsUrlRule(),
            ]
        ]);
        $organization = Organization::create($validated);

        $job = new ParseUrlJob($organization->link);
        dispatch($job);


        return $organization;
    }
}
