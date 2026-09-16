<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected ?AuditService $auditService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
    }

    /**
     * Authenticate user and issue a Personal Access Token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'required_without:login', 'string'],
            'phone' => ['sometimes', 'nullable', 'string'],
            'login' => ['sometimes', 'required_without:email', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $identifier = $validated['login'] ?? $validated['email'] ?? null;
        $phone = $validated['phone'] ?? null;

        // Query user by email or phone
        $user = User::query()
            ->where(function ($query) use ($identifier, $phone) {
                if ($identifier) {
                    $query->where('email', $identifier)
                        ->orWhere('phone', $identifier);
                }
                if ($phone) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->status && $user->status !== 'active') {
            abort(403, 'Account is inactive. Please contact your organization administrator.');
        }

        // Update login timestamp
        $user->update(['last_login_at' => now()]);

        // Audit log
        $this->auditService->success(
            action: 'auth.login',
            auditable: $user,
            user: $user,
            description: "User '{$user->email}' logged in via API."
        );

        $deviceName = $validated['device_name'] ?? 'ged-mobile';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user->load(['organization', 'roles'])),
            ],
        ]);
    }

    /**
     * Log out current user (revoke current personal access token).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();

            $this->auditService->success(
                action: 'auth.logout',
                auditable: $user,
                user: $user,
                description: "User '{$user->email}' logged out via API."
            );
        }

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Revoke all personal access tokens for authenticated user.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();

            $this->auditService->success(
                action: 'auth.logout_all',
                auditable: $user,
                user: $user,
                description: "All tokens revoked for user '{$user->email}'."
            );
        }

        return response()->json([
            'message' => 'Successfully logged out from all devices.',
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['organization', 'roles']);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }
}
