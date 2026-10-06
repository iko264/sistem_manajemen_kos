<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\GenerateInvoiceRequest;
use App\Http\Requests\Billing\InvoiceFilterRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Tenancy;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    public function index(InvoiceFilterRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);
        $user = $request->user();
        $f = $request->validated();

        $invoices = Invoice::query()
            ->with(['tenancy.user:id,name,email', 'tenancy.room:id,number,type', 'payments']) // anti N+1
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('tenancy', fn ($t) => $t->where('user_id', $user->id)))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('invoices.status', $v))
            ->when($f['period'] ?? null, fn ($q, $v) => $q->where('invoices.period', $v))
            ->when($request->filled('overdue'), fn ($q) => $request->boolean('overdue') ? $q->overdue() : $q->notOverdue())
            ->when($f['room_id'] ?? null, fn ($q, $v) => $q->whereHas('tenancy', fn ($t) => $t->where('room_id', $v)))
            ->orderByDesc('period')
            ->orderByDesc('id')
            ->paginate(ApiResponse::perPage())
            ->withQueryString();

        return ApiResponse::paginated($invoices, InvoiceResource::class, 'Daftar tagihan.');
    }

    public function show(Invoice $invoice): JsonResponse
    {
        Gate::authorize('view', $invoice); // tenant lain -> 403

        return ApiResponse::success(
            new InvoiceResource($invoice->load(['tenancy.user:id,name,email', 'tenancy.room:id,number,type', 'payments.verifier:id,name'])),
            'Detail tagihan.'
        );
    }

    /** Aturan #3: generate per periode untuk semua tenancy aktif; yang sudah punya invoice periode itu dilewati. */
    public function generate(GenerateInvoiceRequest $request): JsonResponse
    {
        $period = $request->validated('period');
        $periodStart = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $dueDate = $periodStart->copy()->day(10)->toDateString(); // tanggal 10 pada periode tsb

        $activeTotal = Tenancy::where('status', 'active')->count();

        $targets = Tenancy::query()
            ->with('room:id,price')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $periodEnd->toDateString()) // belum masuk kos pada periode itu -> tidak ditagih
            ->whereDoesntHave('invoices', fn ($q) => $q->where('period', $period))
            ->get();

        $now = now();
        $rows = $targets->map(fn ($t) => [
            'tenancy_id' => $t->id,
            'period' => $period,
            'amount' => $t->room->price,
            'due_date' => $dueDate,
            'status' => 'unpaid',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        // insertOrIgnore: unique(tenancy_id, period) jadi pengaman bila ada generate bersamaan.
        $created = $rows ? Invoice::insertOrIgnore($rows) : 0;

        $data = ['period' => $period, 'created' => $created, 'skipped' => $activeTotal - $created];
        $message = "{$created} tagihan periode {$period} berhasil dibuat.";

        return $created > 0
            ? ApiResponse::created($data, $message)
            : ApiResponse::success($data, 'Tidak ada tagihan baru: semua tenancy aktif sudah memiliki tagihan periode '.$period.'.');
    }
}
