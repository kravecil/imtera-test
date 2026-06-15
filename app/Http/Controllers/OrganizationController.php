<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

use App\Rules\ValidateYandexMapsUrlRule;

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
        return Organization::create($validated);
    }
}
