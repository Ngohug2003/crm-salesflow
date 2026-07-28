<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Ward extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'province_id',
        'code',
        'name',
        'name_en',
        'full_name',
        'full_name_en',
        'code_name',
        'administrative_unit_id',
        'administrative_unit_short_name',
        'administrative_unit_full_name',
        'administrative_unit_short_name_en',
        'administrative_unit_full_name_en',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'administrative_unit_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Province, $this> */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
