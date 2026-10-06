<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\BillingFilterRequest;
use App\Http\Resources\BillingRoomResource;
use App\Http\Resources\BillingTenantResource;
use App\Models\Room;
use App\Models\Tenancy;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    /** Status bayar tiap penghuni aktif pada satu periode (default bulan ini). */
    public function tenants(BillingFilterRequest $request): JsonResponse
    {
        $period = $request->validated('period') ?? now()->format('Y-m');
        $status = $request->validated('status');

        $tenancies = Tenancy::query()
            ->where('status', 'active')
            ->with([
                'user:id,name,email',
                'room:id,number,type',
                'invoices' => fn ($q) => $q->where('period', $period),
            ])
            ->when($status === 'overdue', fn ($q) => $q->whereHas('invoices', fn ($i) => $i->where('period', $period)->overdue()))
            ->when(in_array($status, ['paid', 'unpaid', 'pending'], true), fn ($q) => $q->whereHas(
                'invoices', fn ($i) => $i->where('period', $period)->where('status', $status)
            ))
            ->orderBy('room_id')
            ->paginate(ApiResponse::perPage())
            ->withQueryString();

        return ApiResponse::paginated($tenancies, BillingTenantResource::class, "Status pembayaran penghuni periode {$period}.");
    }

    /** Rekap tagihan per kamar memakai agregat SQL (withCount/withSum), tanpa loop. period opsional. */
    public function rooms(BillingFilterRequest $request): JsonResponse
    {
        $period = $request->validated('period');
        $scope = fn ($q) => $period ? $q->where('invoices.period', $period) : $q;

        $rooms = Room::query()
            ->withCount([
                'invoices as total_invoices' => $scope,
                'invoices as overdue_count' => fn ($q) => $scope($q)->overdue(),
            ])
            ->withSum(['invoices as total_amount' => $scope], 'amount')
            ->withSum(['invoices as total_paid' => fn ($q) => $scope($q)->where('invoices.status', 'paid')], 'amount')
            ->withSum(['invoices as total_unpaid' => fn ($q) => $scope($q)->where('invoices.status', '!=', 'paid')], 'amount')
            ->orderBy('number')
            ->paginate(ApiResponse::perPage())
            ->withQueryString();

        return ApiResponse::paginated($rooms, BillingRoomResource::class, 'Rekap tagihan per kamar.');
    }
}
