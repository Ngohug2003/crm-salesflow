<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PriceBookEntry extends Model
{
    use HasFactory;

    protected $fillable = ['price_book_id', 'product_id', 'unit_price', 'vat_percent', 'min_quantity', 'effective_from', 'effective_until', 'is_active'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'vat_percent' => 'decimal:2',
            'min_quantity' => 'integer',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function priceBook(): BelongsTo
    {
        return $this->belongsTo(PriceBook::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @param Builder<PriceBookEntry> $query */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(static fn (Builder $query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
            ->where(static fn (Builder $query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()));
    }
}
