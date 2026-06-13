<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function store(Request $request): Organization
    {
        $validated = $request->validate([
            'link' => [
                'required',
                'string',
                'unique:organizations,link',
                'regex:/https:\/\/yandex.ru\/maps\/-\/[A-Za-z0-9_-]{8}/i'
            ]
        ]);

        return Organization::create($validated);
    }
}
