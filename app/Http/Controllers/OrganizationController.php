<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function store(Request $request): Organization
    {
        $validated = $request->validate([
            'link' => ['required', 'string', 'unique:organizations,link',]
        ]);

        return Organization::create($validated);
    }
}
