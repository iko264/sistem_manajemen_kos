<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = new User($request->validated());
        $user->role = User::ROLE_TENANT; // role selalu tenant, tidak bisa dikirim dari client
        $user->save();

        return ApiResponse::created([
            'user' => (new UserResource($user))->resolve(),
            'token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Registrasi berhasil');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return ApiResponse::error('Email atau password salah', null, 401);
        }

        return ApiResponse::success([
            'user' => (new UserResource($user))->resolve(),
            'token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Login berhasil');
    }

    public function logout(Request $request): JsonResponse
    {
        // Hanya mencabut token yang sedang dipakai (bukan semua token user).
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout berhasil');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()), 'Data pengguna berhasil diambil');
    }
}
    