<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class QuoteDocument extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'quote_id', 'quote_version_id', 'disk', 'path', 'original_name', 'mime_type',
        'size', 'sha256', 'status', 'failure_reason', 'generated_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['size' => 'integer', 'generated_at' => 'datetime'];
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return BelongsTo<QuoteVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(QuoteVersion::class, 'quote_version_id');
    }
}
