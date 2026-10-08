<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTenancyRequest;
use App\Http\Requests\StoreTenancyRequest;
use App\Http\Resources\TenancyResource;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\User;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TenancyController extends Controller
{
    private const USER_COLUMNS = 'user:id,name,email,phone,identity_number,address';
    private const ROOM_COLUMNS = 'room:id,number,type,price,status';

    public function index(IndexTenancyRequest $request): JsonResponse
    {
        $user = $request->user();

        $tenancies = Tenancy::query()
            ->with([self::USER_COLUMNS, self::ROOM_COLUMNS])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->validated('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->validated('room_id'), fn ($q, $v) => $q->where('room_id', $v))
            ->when($request->validated('search'), fn ($q, $v) => $q->whereHas(
                'user',
                fn ($u) => $u->where('name', 'like', "%{$v}%")
            ))
            ->latest('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return ApiResponse::paginated($tenancies, TenancyResource::class, 'Daftar penghuni berhasil diambil');
    }

    public function show(Tenancy $tenancy): JsonResponse
    {
        Gate::authorize('view', $tenancy);

        $tenancy->load([self::USER_COLUMNS, self::ROOM_COLUMNS]);

        return ApiResponse::success(new TenancyResource($tenancy), 'Detail penghuni berhasil diambil');
    }

    public function store(StoreTenancyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $start = Carbon::parse($data['start_date']);

        $result = DB::transaction(function () use ($data, $start) {
            // Kunci baris agar dua check-in bersamaan tidak lolos bersamaan.
            $room = Room::whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            $tenant = User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();

            if ($room->status !== Room::STATUS_AVAILABLE) {
                return ApiResponse::error('Kamar tidak tersedia untuk check-in', [
                    'room_id' => ["Status kamar saat ini: {$room->status}."],
                ], 422);
            }

            if (Tenancy::where('user_id', $tenant->id)->where('status', 'active')->exists()) {
                return ApiResponse::error('Penghuni masih memiliki tenancy aktif', [
                    'user_id' => ['Penghuni ini belum check-out dari kamar sebelumnya.'],
                ], 422);
            }

            $tenancy = Tenancy::create([
                'user_id' => $tenant->id,
                'room_id' => $room->id,
                'start_date' => $start->toDateString(),
                'status' => 'active',
            ]);

            $room->update(['status' => Room::STATUS_OCCUPIED]);

            $tenancy->invoices()->forceCreate([
                'period' => $start->format('Y-m'),
                'amount' => $room->price,
                'due_date' => $start->copy()->addDays(7)->toDateString(),
                'status' => 'unpaid',
            ]);

            return $tenancy;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        $result->load([self::USER_COLUMNS, self::ROOM_COLUMNS]);

        return ApiResponse::created(new TenancyResource($result), 'Check-in berhasil');
    }

    public function checkout(Tenancy $tenancy): JsonResponse
    {
        Gate::authorize('checkout', $tenancy);

        $result = DB::transaction(function () use ($tenancy) {
            $locked = Tenancy::whereKey($tenancy->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'active') {
                return ApiResponse::error('Penghuni sudah check-out sebelumnya', [
                    'status' => ['Tenancy ini sudah berstatus ended.'],
                ], 422);
            }

            if ($locked->invoices()->where('status', '!=', 'paid')->exists()) {
                return ApiResponse::error('Check-out ditolak: masih ada tagihan yang belum lunas', [
                    'invoices' => ['Lunasi semua tagihan terlebih dahulu.'],
                ], 422);
            }

            $locked->update([
                'status' => 'ended',
                'end_date' => now()->toDateString(),
            ]);

            Room::whereKey($locked->room_id)->update(['status' => Room::STATUS_AVAILABLE]);

            return $locked;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        $result->load([self::USER_COLUMNS, self::ROOM_COLUMNS]);

        return ApiResponse::success(new TenancyResource($result), 'Check-out berhasil');
    }
}