<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTenantRequest;
use App\Http\Resources\TenantIdentityResource;
use App\Http\Resources\TenantResource;
use App\Models\Room;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function index(IndexTenantRequest $request): JsonResponse
    {
        $activeTenancy = fn ($q) => $q->where('status', 'active');

        $tenants = User::query()
            ->where('role', User::ROLE_TENANT)
            ->withExists(['tenancies as has_room' => $activeTenancy])
            ->when($request->validated('search'), fn ($q, $v) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")
            ))
            ->when($request->filled('has_room'), function ($q) use ($request, $activeTenancy) {
                return $request->boolean('has_room')
                    ? $q->whereHas('tenancies', $activeTenancy)
                    : $q->whereDoesntHave('tenancies', $activeTenancy);
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return ApiResponse::paginated($tenants, TenantResource::class, 'Daftar penghuni berhasil diambil');
    }

    public function roomTenant(Room $room): JsonResponse
    {
        $tenancy = $room->activeTenancy()
            ->with('user:id,name,email,phone,identity_number,address')
            ->first();

        if (! $tenancy) {
            return ApiResponse::error('Kamar sedang kosong', null, 404);
        }

        return ApiResponse::success(
            new TenantIdentityResource($tenancy),
            'Identitas penghuni berhasil diambil'
        );
    }
}