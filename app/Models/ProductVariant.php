<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'length_mm',
        'width_mm',
        'height_mm',
        'material',
        'color',
        'finish',
        'unit',
        'standard_price',
        'vat_percent',
        'warranty_months',
        'lead_time_days',
        'commercial_status',
        'is_default',
        'is_active',
        'specifications',
    ];

    protected function casts(): array
    {
        return [
            'standard_price' => 'decimal:2',
            'vat_percent' => 'decimal:2',
            'length_mm' => 'integer',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
            'warranty_months' => 'integer',
            'lead_time_days' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'specifications' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(ProductSupplier::class, 'product_supplier_variant')
            ->withPivot(['supplier_sku', 'purchase_price', 'lead_time_days', 'is_preferred'])
            ->withTimestamps();
    }
}
