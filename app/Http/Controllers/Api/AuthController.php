<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\HttpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected HttpService $httpService
    ) {}

    /**
     * Authenticate the user and start a session.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->httpService->error('Invalid credentials.', 422);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->httpService->success([
            'user' => new UserResource($request->user()),
        ], 'Login successful.');
    }

    /**
     * Return the currently authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        return $this->httpService->success([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Log the user out and invalidate the session.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->httpService->success([], 'Logout successful.');
    }
}
