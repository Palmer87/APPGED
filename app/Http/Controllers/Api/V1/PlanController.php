<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanController extends Controller
{
    /**
     * Display a listing of public active plans.
     */
    public function index(): AnonymousResourceCollection
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return PlanResource::collection($plans);
    }
}
