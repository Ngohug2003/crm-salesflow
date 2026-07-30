<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OpportunityItem extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'opportunity_id',
        'product_id',
        'price_book_entry_id',
        'product_name',
        'sku',
        'unit_price',
        'quantity',
        'discount_percent',
        'vat_percent',
        'total_price',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'discount_percent' => 'decimal:2',
            'vat_percent' => 'decimal:2',
            'total_price' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<PriceBookEntry, $this> */
    public function priceBookEntry(): BelongsTo
    {
        return $this->belongsTo(PriceBookEntry::class);
    }
}
