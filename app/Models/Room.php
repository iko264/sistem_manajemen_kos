<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_MAINTENANCE = 'maintenance';

    public const TYPES = ['standard', 'deluxe'];

    protected $fillable = [
        'number',
        'type',
        'price',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }

    public function tenancies(): HasMany
    {
        return $this->hasMany(Tenancy::class);
    }

    public function activeTenancy(): HasOne
    {
        return $this->hasOne(Tenancy::class)->where('status', 'active');
    }

    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(Invoice::class, Tenancy::class);
    }

    /**
     * Filter daftar kamar: status, type, min_price, max_price, search (nomor).
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when(isset($filters['min_price']), fn (Builder $q) => $q->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $q) => $q->where('price', '<=', $filters['max_price']))
            ->when($filters['search'] ?? null, fn (Builder $q, $v) => $q->where('number', 'like', "%{$v}%"));
    }
}
