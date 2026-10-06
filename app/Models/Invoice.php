<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = ['tenancy_id', 'period', 'amount', 'due_date', 'status'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_date' => 'date',
        ];
    }

    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Aturan #4: overdue TIDAK disimpan, dihitung: status != 'paid' dan due_date < hari ini. */
    protected function isOverdue(): Attribute
    {
        return Attribute::get(
            fn () => $this->status !== 'paid' && $this->due_date !== null && $this->due_date->lt(today())
        );
    }

    /** Padanan query dari accessor is_overdue. */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('invoices.status', '!=', 'paid')
            ->where('invoices.due_date', '<', today()->toDateString());
    }

    public function scopeNotOverdue(Builder $query): Builder
    {
        return $query->where(fn ($w) => $w->where('invoices.status', 'paid')
            ->orWhere('invoices.due_date', '>=', today()->toDateString()));
    }
}
