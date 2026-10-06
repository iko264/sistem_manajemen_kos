<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenancy;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $rooms = Room::selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $invoices = Invoice::selectRaw('status, COUNT(*) AS total, COALESCE(SUM(amount), 0) AS amount')
            ->groupBy('status')->get()->keyBy('status');

        $pending = Payment::query()
            ->with(['invoice.tenancy.user:id,name,email', 'invoice.tenancy.room:id,number,type'])
            ->where('status', 'pending')
            ->latest('id')
            ->limit(5)
            ->get();

        return ApiResponse::success([
            'total_rooms' => (int) $rooms->sum(),
            'available_rooms' => (int) ($rooms['available'] ?? 0),
            'occupied_rooms' => (int) ($rooms['occupied'] ?? 0),
            'maintenance_rooms' => (int) ($rooms['maintenance'] ?? 0),
            'active_tenants' => Tenancy::where('status', 'active')->count(),
            'unpaid_count' => (int) ($invoices['unpaid']->total ?? 0),
            'pending_count' => (int) ($invoices['pending']->total ?? 0),
            'overdue_count' => Invoice::overdue()->count(), // dihitung, bukan kolom
            // belum lunas = unpaid + pending
            'total_unpaid_amount' => (int) (($invoices['unpaid']->amount ?? 0) + ($invoices['pending']->amount ?? 0)),
            'revenue_this_month' => (int) Payment::where('status', 'approved')
                ->whereBetween('verified_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
            'pending_payments' => PaymentResource::collection($pending)->resolve(request()),
        ], 'Ringkasan dashboard.');
    }
}
