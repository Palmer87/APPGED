<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Dismiss the dashboard onboarding checklist banner.
     */
    public function dismiss(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->put('onboarding_dismissed', true);

        if ($request->wantsJson()) {
            return response()->json(['dismissed' => true]);
        }

        return back()->with('message', 'Guide d\'accueil masqué.');
    }
}
