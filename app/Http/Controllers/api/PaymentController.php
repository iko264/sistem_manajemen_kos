<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PaymentFilterRequest;
use App\Http\Requests\Billing\StorePaymentRequest;
use App\Http\Requests\Billing\VerifyPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PaymentController extends Controller
{
    public function index(PaymentFilterRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['invoice.tenancy.user:id,name,email', 'invoice.tenancy.room:id,number,type', 'verifier:id,name'])
            ->when($request->validated('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 10), 1), 50))
            ->withQueryString();

        return ApiResponse::paginated($payments, PaymentResource::class, 'Daftar pembayaran.');
    }

    /** Aturan #5 (upload): tenant pemilik, invoice 'unpaid' -> payment 'pending', invoice 'pending'. */
    public function store(StorePaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $path = null;

        try {
            $result = DB::transaction(function () use ($request, $invoice, &$path) {
                $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== 'unpaid') {
                    $msg = $locked->status === 'pending'
                        ? 'Tagihan ini sedang menunggu verifikasi pembayaran.'
                        : 'Tagihan ini sudah lunas.';

                    return ApiResponse::error($msg, ['invoice' => [$msg]], 422);
                }

                $path = $request->file('proof')->store('payment-proofs', 'public');

                $payment = Payment::create([
                    'invoice_id' => $locked->id,
                    'amount' => $request->validated('amount'),
                    'proof_path' => $path,
                    'status' => 'pending',
                ]);
                $locked->update(['status' => 'pending']);

                return $payment;
            });
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path); // jangan tinggalkan file yatim
            }
            throw $e;
        }

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return ApiResponse::created(
            new PaymentResource($result->load('invoice')),
            'Bukti pembayaran berhasil diunggah, menunggu verifikasi admin.'
        );
    }

    /** Aturan #5 (verifikasi): approve -> paid; reject -> invoice kembali unpaid (note wajib). */
    public function verify(VerifyPaymentRequest $request, Payment $payment): JsonResponse
    {
        $data = $request->validated();
        $approve = $data['action'] === 'approve';

        $result = DB::transaction(function () use ($request, $payment, $data, $approve) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                $msg = 'Pembayaran ini sudah diverifikasi sebelumnya.';

                return ApiResponse::error($msg, ['payment' => [$msg]], 422);
            }

            $invoice = Invoice::whereKey($locked->invoice_id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status' => $approve ? 'approved' : 'rejected',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'note' => $data['note'] ?? null,
            ]);
            $invoice->update(['status' => $approve ? 'paid' : 'unpaid']);

            return $locked;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return ApiResponse::success(
            new PaymentResource($result->load(['invoice', 'verifier:id,name'])),
            $approve ? 'Pembayaran disetujui, tagihan lunas.' : 'Pembayaran ditolak, tagihan kembali belum dibayar.'
        );
    }
}
