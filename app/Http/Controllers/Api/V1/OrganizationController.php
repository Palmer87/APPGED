<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrganizationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrganizationController extends Controller
{
    /**
     * Get the authenticated user's organization.
     */
    public function current(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;

        if (! $organization) {
            abort(404, 'Organization not found.');
        }

        Gate::authorize('view', $organization);

        return response()->json([
            'data' => new OrganizationResource($organization),
        ]);
    }
}
