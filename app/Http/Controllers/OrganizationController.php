<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Illuminate\Http\JsonResponse;

use \App\Models\Organization;

class OrganizationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['link' => ['required', 'string']]);

        return Organization::create($validated);
    }
}
