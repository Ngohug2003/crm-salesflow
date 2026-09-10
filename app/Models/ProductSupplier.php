<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ProductSupplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'tax_code',
        'contact_name',
        'email',
        'phone',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_supplier_variant')
            ->withPivot(['supplier_sku', 'purchase_price', 'lead_time_days', 'is_preferred'])
            ->withTimestamps();
    }
}
