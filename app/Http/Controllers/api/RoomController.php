<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\IndexRoomRequest;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Support\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function index(IndexRoomRequest $request): JsonResponse
    {
        $rooms = Room::query()
            ->filter($request->validated())
            ->orderBy('number')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return ApiResponse::paginated($rooms, RoomResource::class, 'Daftar kamar berhasil diambil');
    }

    public function show(Room $room): JsonResponse
    {
        return ApiResponse::success(new RoomResource($room), 'Detail kamar berhasil diambil');
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = Room::create($request->validated() + ['status' => Room::STATUS_AVAILABLE]);

        return ApiResponse::created(new RoomResource($room), 'Kamar berhasil dibuat');
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $data = $request->validated();

        // Status 'occupied' hanya boleh berubah lewat check-in/check-out (bagian B).
        if (isset($data['status']) && $data['status'] !== $room->status
            && ($room->status === Room::STATUS_OCCUPIED || $data['status'] === Room::STATUS_OCCUPIED)) {
            return ApiResponse::error('Status kamar tidak dapat diubah manual', [
                'status' => ['Status "occupied" hanya berubah lewat check-in atau check-out.'],
            ], 422);
        }

        $room->update($data);

        return ApiResponse::success(new RoomResource($room), 'Kamar berhasil diperbarui');
    }

    public function destroy(Room $room): JsonResponse
    {
        if ($room->status === Room::STATUS_OCCUPIED) {
            return ApiResponse::error('Kamar sedang terisi dan tidak dapat dihapus', null, 409);
        }

        try {
            $room->delete();
        } catch (QueryException $e) {
            // Foreign key dari tenancies (riwayat penghunian) menahan penghapusan.
            if ((string) $e->getCode() === '23000') {
                return ApiResponse::error('Kamar memiliki riwayat penghunian dan tidak dapat dihapus', null, 409);
            }

            throw $e;
        }

        return ApiResponse::success(null, 'Kamar berhasil dihapus');
    }
}
