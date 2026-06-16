<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\ParsingService;

class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->getOrganizationName(),
            'rating' => $this->getRating(),
            'ratingCount' => $this->getRatingCount(),
            'reviewCount' => $this->getReviewCount(),
            'reviews' => $this->getReviews(),
        ];
    }
}
