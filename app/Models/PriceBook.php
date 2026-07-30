<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PriceBook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'customer_segment', 'region', 'currency_code', 'effective_from', 'effective_until', 'is_active', 'owner_id', 'department_id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PriceBookEntry::class);
    }

    /** @param Builder<PriceBook> $query */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(static fn (Builder $query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
            ->where(static fn (Builder $query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()));
    }
}
